<?php
<?php
class AuthMiddleware {
    private static $rateLimits = [];
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300): bool {
        $now = time();
        $key = hash('sha256', $identifier);
        if (isset(self::$rateLimits[$key])) {
            self::$rateLimits[$key] = array_filter(
                self::$rateLimits[$key], 
                fn($timestamp) => ($now - $timestamp) < $timeWindow
            );
        } else {
            self::$rateLimits[$key] = [];
        }
        if (count(self::$rateLimits[$key]) >= $maxAttempts) {
            return false;
        }
        self::$rateLimits[$key][] = $now;
        return true;
    }
    public static function logFailedAttempt($identifier, $reason = 'authentication_failed'): void {
        if (class_exists('MongoLogger')) {
            $mongoLogger = new MongoLogger();
            $mongoLogger->logSecurity('failed_login', [
                'identifier' => hash('sha256', $identifier),
                'reason' => $reason,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
    }
    public static function validateSession(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            return false;
        }
        $_SESSION['last_activity'] = time();
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration'] > 300)) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        return isset($_SESSION['user_id']);
    }
    public static function validatePasswordStrength($password): array {
        $errors = [];
        $rules = [
            ['/^.{8,}$/', 'Password must be at least 8 characters long'],
            ['/[A-Z]/', 'Password must contain at least one uppercase letter'],
            ['/[a-z]/', 'Password must contain at least one lowercase letter'],
            ['/\d/', 'Password must contain at least one number'],
            ['/[^A-Za-z0-9]/', 'Password must contain at least one special character']
        ];
        foreach ($rules as [$pattern, $message]) {
            if (!preg_match($pattern, $password)) {
                $errors[] = $message;
            }
        }
        return $errors;
    }
}<?php
class AuthMiddleware {
    private static $rateLimits = [];     
    private static $loginAttempts = [];  
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $now = time();
        $key = md5($identifier);  
        if (isset(self::$rateLimits[$key])) {
            self::$rateLimits[$key] = array_filter(
                self::$rateLimits[$key], 
                function($timestamp) use ($now, $timeWindow) {
                    return ($now - $timestamp) < $timeWindow;
                }
            );
        } else {
            self::$rateLimits[$key] = [];
        }
        if (count(self::$rateLimits[$key]) >= $maxAttempts) {
            return false;  
        }
        self::$rateLimits[$key][] = $now;
        return true;
    }
    public static function logFailedAttempt($identifier, $reason = 'authentication_failed') {
        $mongoLogger = new MongoLogger();
        $mongoLogger->logSecurity('failed_login', [
            'identifier' => hash('sha256', $identifier),  
            'reason' => $reason,                          
            'timestamp' => date('Y-m-d H:i:s'),           
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown', 
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'  
        ]);
    }
    public static function validateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();     
            session_destroy();   
            return false;
        }
        $_SESSION['last_activity'] = time();
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration'] > 300)) {
            session_regenerate_id(true);  
            $_SESSION['last_regeneration'] = time();
        }
        return isset($_SESSION['user_id']);
    }
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    public static function validatePasswordStrength($password) {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        return $errors;
    }
}
