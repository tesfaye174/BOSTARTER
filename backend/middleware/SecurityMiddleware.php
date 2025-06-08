<?php
/**
 * Middleware di sicurezza unificato
 * Applica tutte le misure di sicurezza in modo centralizzato
 */

require_once __DIR__ . '/../config/SecurityConfig.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/SecurityService.php';

use BOSTARTER\Services\SecurityService;

class SecurityMiddleware {
    private static $initialized = false;
    private static $securityService;    public static function initialize(): void {
        if (self::$initialized) return;
        
        // Configura la sessione prima di inviare qualsiasi output
        if (session_status() === PHP_SESSION_NONE) {
            SecurityConfig::configureSecureSession();
        }
        
        // Applica headers di sicurezza solo se non sono già stati inviati
        if (!headers_sent()) {
            SecurityConfig::setSecurityHeaders();
        }
        
        self::$securityService = new SecurityService(Database::getInstance()->getConnection(), null);
        self::checkBlockedIP();
        
        // Avvia la sessione se non già attiva
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        self::$initialized = true;
    }    public static function csrfProtection(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($requestMethod === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (empty($token) || !self::verifyCSRFToken($token)) {
                http_response_code(403);
                die(json_encode(['error' => 'Token CSRF non valido']));
            }
        }
    }

    public static function rateLimit(string $action = 'general'): void {
        $clientIP = SecurityConfig::getRealClientIP();
        $identifier = $action . '_' . $clientIP;
        $limits = SecurityConfig::RATE_LIMITS[$action] ?? SecurityConfig::RATE_LIMITS['api'];
        if (!self::checkRateLimit($identifier, $limits['max_attempts'], $limits['time_window'])) {
            http_response_code(429);
            header('Retry-After: ' . $limits['time_window']);
            die(json_encode(['error' => 'Troppi tentativi. Riprova più tardi.']));
        }
    }

    public static function sanitizeInputs(): void {
        if (!empty($_GET)) {
            $_GET = array_map([SecurityConfig::class, 'sanitizeInput'], $_GET);
        }
        if (!empty($_POST)) {
            $excludeFields = ['password', 'password_confirm', 'csrf_token'];
            foreach ($_POST as $key => $value) {
                if (!in_array($key, $excludeFields)) {
                    $_POST[$key] = SecurityConfig::sanitizeInput($value);
                }
            }
        }
    }

    public static function validateFileUploads(): void {
        if (!empty($_FILES)) {
            foreach ($_FILES as $file) {
                if (!self::isValidFile($file)) {
                    http_response_code(400);
                    die(json_encode(['error' => 'File non valido o non sicuro']));
                }
            }
        }
    }    public static function logRequest(): void {
        if (SecurityConfig::LOGGING_CONFIG['log_security_events']) {
            $logData = [
                'ip' => SecurityConfig::getRealClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $_SESSION['user_id'] ?? null
            ];
            if (self::isSuspiciousRequest()) {
                self::$securityService->logSecurityEvent('suspicious_request', $logData);
            }
        }
    }

    public static function applyAll(array $options = []): void {
        self::initialize();
        if ($options['rate_limit'] ?? true) self::rateLimit($options['action'] ?? 'general');
        if ($options['sanitize'] ?? true) self::sanitizeInputs();
        if ($options['csrf'] ?? false) self::csrfProtection();
        if ($options['file_validation'] ?? true) self::validateFileUploads();
        if ($options['logging'] ?? true) self::logRequest();
    }

    // =============== METODI PRIVATI ===============
    private static function checkBlockedIP(): void {
        $clientIP = SecurityConfig::getRealClientIP();
        if (SecurityConfig::isWhitelistedIP($clientIP)) return;
        if (SecurityConfig::isBlacklistedIP($clientIP)) {
            http_response_code(403);
            die('Accesso negato');
        }
        if (self::$securityService && self::$securityService->isIPBlocked($clientIP)) {
            http_response_code(429);
            die('IP temporaneamente bloccato per attività sospette');
        }
    }
    private static function verifyCSRFToken(string $token): bool {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) return false;
        if ((time() - $_SESSION['csrf_token_time']) > SecurityConfig::AUTH_CONFIG['csrf_token_lifetime']) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    private static function checkRateLimit(string $identifier, int $maxAttempts, int $timeWindow): bool {
        $key = 'rate_limit_' . hash('sha256', $identifier);
        if (!isset($_SESSION[$key])) $_SESSION[$key] = [];
        $now = time();
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        if (count($_SESSION[$key]) >= $maxAttempts) return false;
        $_SESSION[$key][] = $now;
        return true;
    }
    private static function isValidFile(array $file): bool {
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) return false;
        if ($file['size'] > SecurityConfig::INPUT_VALIDATION['max_file_size']) return false;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, SecurityConfig::INPUT_VALIDATION['allowed_file_types'])) return false;
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $detectedMime = mime_content_type($file['tmp_name']);
        if (!isset($allowedMimes[$extension]) || $detectedMime !== $allowedMimes[$extension]) return false;
        return true;
    }
    private static function isSuspiciousRequest(): bool {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $suspiciousPatterns = [
            '/\.\.\/\//',
            '/<script>/',
            '/union.*select/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i'
        ];
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $uri)) return true;
        }
        $suspiciousAgents = ['bot', 'crawler', 'spider', 'scanner'];
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) return true;
        }        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $postKey = 'consecutive_posts_' . SecurityConfig::getRealClientIP();
            if (!isset($_SESSION[$postKey])) $_SESSION[$postKey] = 0;
            $_SESSION[$postKey]++;
            if ($_SESSION[$postKey] > 10) return true;
        } else {
            $postKey = 'consecutive_posts_' . SecurityConfig::getRealClientIP();
            $_SESSION[$postKey] = 0;
        }
        return false;
    }
}
