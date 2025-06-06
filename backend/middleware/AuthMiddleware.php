<?php

class AuthMiddleware {
    private static $rateLimits = [];
    private static $loginAttempts = [];
    
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $now = time();
        $key = md5($identifier);
        
        // Clean old attempts
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
        
        // Check if limit exceeded
        if (count(self::$rateLimits[$key]) >= $maxAttempts) {
            return false;
        }
        
        // Add current attempt
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
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID periodically (every 5 minutes)
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