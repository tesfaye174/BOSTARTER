<?php
namespace BOSTARTER\Controllers;
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../utils/NavigationHelper.php';
require_once __DIR__ . '/../utils/BaseController.php';
use BOSTARTER\Utils\BaseController;
class GestoreAutenticazione extends BaseController
{
    private \BOSTARTER\Services\AuthService $authService;
    private array $config;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900; 
    private const PASSWORD_MIN_LENGTH = 8;
    public function __construct()
    {
        parent::__construct();
        $this->authService = new \BOSTARTER\Services\AuthService();
        $this->config = require __DIR__ . '/../config/auth_config.php';
        $this->initializeSecurityMeasures();
    }
    private function initializeSecurityMeasures(): void
    {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        $this->cleanOldAttempts();
    }
    public function gestisciLogin(string $email, string $password, bool $rememberMe = false): array
    {
        try {
            $validation = $this->validateLoginInput($email, $password);
            if (!$validation['valid']) {
                return $this->createErrorResponse($validation['message']);
            }
            if ($this->isRateLimited($email)) {
                $this->logger->logUserAction('login_rate_limited', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ], 'warning');
                return $this->createErrorResponse('Too many login attempts. Please try again later.');
            }
            if ($this->isAccountLocked($email)) {
                $this->logger->logUserAction('login_account_locked', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ], 'warning');
                return $this->createErrorResponse('Account is temporarily locked due to multiple failed attempts.');
            }
            $authResult = $this->authService->login($email, $password, $rememberMe);
            if ($authResult['success']) {
                $this->resetLoginAttempts($email);
                $this->logger->logUserAction('login_success', [
                    'user_id' => $authResult['user']['id'] ?? null,
                    'email' => $email,
                    'remember_me' => $rememberMe
                ], 'info');
                return $this->createSuccessResponse('Login successful', [
                    'user' => $this->sanitizeUserData($authResult['user'] ?? []),
                    'redirect_url' => $this->getRedirectUrl($authResult['user'] ?? [])
                ]);
            } else {
                $this->recordFailedAttempt($email);
                $this->logger->logUserAction('login_failed', [
                    'email' => $email,
                    'reason' => $authResult['message'] ?? 'Invalid credentials',
                    'ip' => $_SERVER['REMOTE_ADDR']
                ], 'warning');
                return $this->createErrorResponse($authResult['errors'] ?? ['Invalid credentials']);
            }
        } catch (Exception $e) {
            $this->logger->logError('Login error: ' . $e->getMessage(), [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            return $this->createErrorResponse('An error occurred during login. Please try again.');
        }
    }
    public function gestisciRegistrazione(array $userData): array
    {
        try {
            $validation = $this->validateRegistrationData($userData);
            if (!$validation['valid']) {
                return $this->createErrorResponse($validation['message'], $validation['errors']);
            }
            $emailValidation = Validator::validateEmail($userData['email']);
            if (!$emailValidation['valid']) {
                return $this->createErrorResponse('Email non valida', $emailValidation['errors']);
            }
            $result = $this->authService->register($userData);
            if ($result['success']) {
                $this->logger->logUserAction('registration_success', [
                    'email' => $userData['email']
                ], 'info');
                return $this->createSuccessResponse('Registration successful! Please check your email for confirmation');
            } else {
                $errorMessage = 'Registration failed';
                if (isset($result['errors']) && is_array($result['errors'])) {
                    $errorMessage = implode(', ', $result['errors']);
                } elseif (isset($result['message'])) {
                    $errorMessage = $result['message'];
                }
                return $this->createErrorResponse($errorMessage);
            }
        } catch (Exception $e) {
            $this->logger->logError('Registration error: ' . $e->getMessage(), [
                'email' => $userData['email'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            return $this->createErrorResponse('An error occurred during registration. Please try again.');
        }
    }
    public function eseguiLogout(): array
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                $this->logger->logUserAction('logout', [
                    'user_id' => $userId
                ], 'info');
            }
            $this->authService->logout();
            return $this->createSuccessResponse('Logout successful');
        } catch (Exception $e) {
            $this->logger->logError('Logout error: ' . $e->getMessage());
            return $this->createErrorResponse('An error occurred during logout');
        }
    }
    public function controllaSeLoggato(): bool {
        return $this->authService->isAuthenticated();
    }
    public function ottieniTokenSicurezza(): string {
        return $this->authService->generateCSRFToken();
    }
    public function richiedeAutenticazione(): void {
        if (!$this->controllaSeLoggato()) {            
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'dashboard';
            \NavigationHelper::redirect('login');
        }
    }        
    public function richiedeOspite(): void {
        $redirect_count = $_SESSION['redirect_count'] ?? 0;
        if ($redirect_count > 2) {
            return; 
        }
        if ($this->controllaSeLoggato()) {            $_SESSION['redirect_count'] = $redirect_count + 1;
            \NavigationHelper::redirect('dashboard');
        }
    }
    private function validateLoginInput(string $email, string $password): array
    {
        $errors = [];
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Password is too short';
        }
        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : 'Validation failed',
            'errors' => $errors
        ];
    }
    private function validateRegistrationData(array $userData): array
    {
        $errors = [];
        $required = ['nome', 'cognome', 'email', 'password', 'confirm_password'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        if (!empty($userData['email']) && !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        if (!empty($userData['password'])) {
            $passwordValidation = $this->validatePassword($userData['password']);
            if (!$passwordValidation['valid']) {
                $errors['password'] = $passwordValidation['message'];
            }
        }
        if (!empty($userData['password']) && !empty($userData['confirm_password'])) {
            if ($userData['password'] !== $userData['confirm_password']) {
                $errors['confirm_password'] = 'Passwords do not match';
            }
        }
        if (!empty($userData['nome']) && !preg_match('/^[a-zA-ZÀ-ÿ\s]{2,30}$/', $userData['nome'])) {
            $errors['nome'] = 'Name must be 2-30 characters and contain only letters';
        }
        if (!empty($userData['cognome']) && !preg_match('/^[a-zA-ZÀ-ÿ\s]{2,30}$/', $userData['cognome'])) {
            $errors['cognome'] = 'Surname must be 2-30 characters and contain only letters';
        }
        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : 'Validation failed',
            'errors' => $errors
        ];
    }
    private function validatePassword(string $password): array
    {
        $errors = [];
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        if ($this->isCommonPassword($password)) {
            $errors[] = 'This password is too common. Please choose a more secure one';
        }
        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : implode('. ', $errors)
        ];
    }
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', 'password123', '123456', '123456789', 'qwerty',
            'abc123', 'password1', 'admin', 'letmein', 'welcome'
        ];
        return in_array(strtolower($password), $commonPasswords);
    }
    private function isRateLimited(string $email): bool
    {
        $attempts = $_SESSION['login_attempts'][$email] ?? [];
        $recentAttempts = array_filter($attempts, function($attempt) {
            return (time() - $attempt) < 300; 
        });
        return count($recentAttempts) >= self::MAX_LOGIN_ATTEMPTS;
    }
    private function isAccountLocked(string $email): bool
    {
        $rateLimit = $this->authService->getRateLimitStatus($email);
        return $rateLimit['blocked'] ?? false;
    }
    private function recordFailedAttempt(string $email): void
    {
    }
    private function resetLoginAttempts(string $email): void
    {
        $this->authService->resetRateLimit($email);
    }
    private function cleanOldAttempts(): void
    {
        if (!isset($_SESSION['login_attempts'])) {
            return;
        }
        foreach ($_SESSION['login_attempts'] as $email => $attempts) {
            $_SESSION['login_attempts'][$email] = array_filter($attempts, function($attempt) {
                return (time() - $attempt) < 3600; 
            });
            if (empty($_SESSION['login_attempts'][$email])) {
                unset($_SESSION['login_attempts'][$email]);
            }
        }
    }
    private function initializeSecureSession(array $user, bool $rememberMe): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $this->sanitizeUserData($user);
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['browser_fingerprint'] = $this->generateBrowserFingerprint();
        if ($rememberMe) {
            $this->setRememberMeCookie($user['id']);
        }
    }
    private function generateBrowserFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }
    private function setRememberMeCookie(int $userId): void
    {
    }
    private function destroySecureSession(): void
    {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }
    private function sanitizeUserData(array $user): array
    {
        return [
            'id' => $user['id'],
            'nome' => htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'),
            'cognome' => htmlspecialchars($user['cognome'], ENT_QUOTES, 'UTF-8'),
            'email' => $user['email'],
            'tipo_utente' => $user['tipo_utente'],
            'avatar' => $user['avatar'] ?? null
        ];
    }
    private function getRedirectUrl(array $user): string
    {
        if (isset($_SESSION['redirect_after_login'])) {
            $url = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            return $url;
        }
        return match($user['tipo_utente']) {
            'amministratore' => '/admin/dashboard.php',
            'creatore' => '/creator/dashboard.php',
            default => '/dashboard.php'
        };
    }
    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, 
            'time_cost' => 4,       
            'threads' => 3          
        ]);
    }
    private function sendWelcomeEmail(string $email, string $nome): void
    {
    }
}
