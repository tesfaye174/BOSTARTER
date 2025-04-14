<?php
namespace Controllers;

use Models\UserModel;
use Config\SecurityManager;
use Config\ConfigManager;
use Config\MongoDBManager;
use Config\EventLogger;
use MongoDB\BSON\UTCDateTime;

class AuthController
{
    private $userModel;
    private $securityManager;
    private $config;
    private $eventLogger;

    private $rateLimiter;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes

    public function __construct()
    {
        try {
            // Configure secure session before any output
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                ini_set('session.cookie_httponly', '1');
                ini_set('session.use_only_cookies', '1');
                ini_set('session.cookie_secure', '1');
                ini_set('session.use_strict_mode', '1');
                ini_set('session.cookie_samesite', 'Strict');
                ini_set('session.gc_maxlifetime', '3600');
                ini_set('session.cookie_lifetime', '3600');
                
                session_start();
            }

            $this->config = ConfigManager::getInstance();
            $this->userModel = new \Models\UserModel();
            $this->securityManager = new \Config\SecurityManager();
            $this->eventLogger = new EventLogger();
            $this->rateLimiter = new \Server\RateLimiter();
            
            // Load configuration
            $this->maxLoginAttempts = $this->config->get('security.max_login_attempts', 5);
            $this->lockoutDuration = $this->config->get('security.lockout_duration', 900);
        } catch (\Exception $e) {
            error_log("Authentication initialization failed: " . $e->getMessage());
            throw new \Exception('Failed to initialize authentication system');
        }
    }

    public function register(array $userData)
    {
        try {
            // Validate and sanitize input
            $userData = $this->securityManager->sanitizeInput($userData);
            
            // Generate CSRF token if not exists
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = $this->securityManager->generateCSRFToken();
            }

            // Validate CSRF token
            if (!empty($userData['csrf_token']) && !$this->securityManager->validateCSRF($userData['csrf_token'])) {
                throw new \Exception('Invalid CSRF token');
            }

            // Additional security checks for admin registration
            if (isset($userData['role']) && $userData['role'] === 'admin') {
                if (empty($userData['security_code']) || 
                    !$this->securityManager->validateSecurityCode($userData['security_code'])) {
                    throw new \Exception('Invalid security code for admin registration');
                }
            }

            // Create user
            $result = $this->userModel->createUser($userData);

            if ($result['success']) {
                // Log successful registration
                $this->eventLogger->logAuthEvent('user_registration', $result['user_id'], [
                    'email' => $userData['email'],
                    'role' => $userData['role'] ?? 'user'
                ]);

                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'user_id' => $result['user_id']
                ];
            }

            return $result;

        } catch (\Exception $e) {
            $this->eventLogger->logAuthEvent('registration_failed', null, [
                'error' => $e->getMessage(),
                'email' => $userData['email'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function checkRateLimit($ip): void {
        if ($this->rateLimiter->isLimited($ip)) {
            $this->eventLogger->logAuthEvent('rate_limit_exceeded', null, ['ip' => $ip]);
            throw new \Exception('Too many requests. Please try again later.');
        }
    }

    private function handleFailedLogin($ip, $email): void {
        $attempts = $this->rateLimiter->incrementAttempts($ip);
        if ($attempts >= $this->maxLoginAttempts) {
            $this->rateLimiter->blockIP($ip, $this->lockoutDuration);
            $this->eventLogger->logAuthEvent('account_locked', null, [
                'email' => $email,
                'ip' => $ip,
                'duration' => $this->lockoutDuration
            ]);
        }
    }

    public function login(array $credentials)
    {
        try {
            // Validate and sanitize input
            $credentials = $this->securityManager->sanitizeInput($credentials);
            $email = $credentials['email'] ?? '';
            $password = $credentials['password'] ?? '';

            if (empty($email) || empty($password)) {
                throw new \Exception('Email and password are required');
            }

            // Check login attempts
            $maxAttempts = $this->config->get('security.max_login_attempts', 5);
            $lockoutDuration = $this->config->get('security.lockout_duration', 900);

            if ($this->isLoginBlocked($email, $maxAttempts, $lockoutDuration)) {
                throw new \Exception('Too many login attempts. Please try again later.');
            }

            // Get user
            $user = $this->userModel->getUserByEmail($email);
            if (!$user) {
                $this->incrementLoginAttempts($email);
                throw new \Exception('Invalid credentials');
            }

            // Verify password
            if (!$this->securityManager->verifyPassword($password, $user['password'])) {
                $this->incrementLoginAttempts($email);
                throw new \Exception('Invalid credentials');
            }

            // Check if admin login
            if ($user['role'] === 'admin' && 
                (!isset($credentials['security_code']) || 
                !$this->securityManager->validateSecurityCode($credentials['security_code']))) {
                throw new \Exception('Invalid security code for admin login');
            }

            // Login successful
            $this->resetLoginAttempts($email);
            $this->userModel->updateLastLogin($user['id']);

            // Generate tokens
            $accessToken = $this->securityManager->generateToken($user['id'], 'access');
            $refreshToken = $this->securityManager->generateToken($user['id'], 'refresh');

            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = $this->securityManager->generateCSRFToken();
            $_SESSION['locked'] = true;
            session_regenerate_id(true);
            $_SESSION['locked'] = false;

            // Log successful login
            $this->eventLogger->logAuthEvent('user_login', $user['id'], [
                'email' => $email,
                'role' => $user['role']
            ]);

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'name' => $user['name']
                ],
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken
            ];

        } catch (\Exception $e) {
            $this->eventLogger->logAuthEvent('login_failed', null, [
                'error' => $e->getMessage(),
                'email' => $email ?? 'unknown'
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function isLoginBlocked($email, $maxAttempts, $lockoutDuration)
    {
        try {
            // Use the new getFailedLoginAttempts method from EventLogger
            $failedAttempts = $this->eventLogger->getFailedLoginAttempts($email, $lockoutDuration);
            return $failedAttempts >= $maxAttempts;
        } catch (\Exception $e) {
            // Log the error and return false to prevent blocking legitimate login attempts
            $this->eventLogger->logAuthEvent('login_check_error', null, [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            error_log("Error checking login attempts: {$e->getMessage()}");
            return false;
        }
    }

    private function incrementLoginAttempts($email)
    {
        try {
            // Use EventLogger consistently for tracking failed login attempts
            $this->eventLogger->logAuthEvent('failed_login', null, [
                'email' => $email,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            error_log("Error incrementing login attempts: {$e->getMessage()}");
        }
    }

    private function resetLoginAttempts($email)
    {
        try {
            // Clear failed login attempts
            $this->eventLogger->clearFailedLoginAttempts($email);
            
            // Log the reset
            $this->eventLogger->logAuthEvent('login_attempts_reset', null, [
                'email' => $email,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Error resetting login attempts: {$e->getMessage()}");
        }
    }

    public function logout()
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $email = $_SESSION['email'] ?? null;

            // Invalidate session
            $_SESSION = array();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time()-3600, '/', null, true, true);
            }
            session_destroy();

            // Log successful logout using EventLogger
            if ($userId) {
                $this->eventLogger->logAuthEvent('user_logout', $userId, [
                    'email' => $email,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }

            return [
                'success' => true,
                'message' => 'Logout successful'
            ];

        } catch (\Exception $e) {
            // Log failed logout using EventLogger
            $this->eventLogger->logAuthEvent('logout_failed', $userId ?? null, [
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => false,
                'message' => 'Logout failed'
            ];
        }
    }

    private function logAuthEvent($event, $userId = null, array $data = [])
    {
        try {
            // Use EventLogger for consistent logging
            $this->eventLogger->logAuthEvent($event, $userId, $data);
        } catch (\Exception $e) {
            error_log("MongoDB logging error: {$e->getMessage()}");
        }
    }
}