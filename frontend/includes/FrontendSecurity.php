<?php
// Lightweight frontend security wrapper that delegates to backend Security when available.
if (session_status() === PHP_SESSION_NONE) session_start();

class FrontendSecurity {
    public static function verifyCSRFToken($token) {
        if (class_exists('Security')) {
            return Security::getInstance()->verifyCSRFToken($token);
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function generateCSRFToken() {
        if (class_exists('Security')) {
            return Security::getInstance()->generateCSRFToken();
        }

        if (empty($_SESSION['csrf_token'])) {
            try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); } catch (Exception $e) { $_SESSION['csrf_token'] = md5(uniqid('', true)); }
        }
        return $_SESSION['csrf_token'];
    }

    public static function sanitizeInput($input) {
        if (class_exists('Security')) {
            return Security::getInstance()->sanitizeInput($input);
        }
        if (is_array($input)) return array_map([__CLASS__, 'sanitizeInput'], $input);
        return htmlspecialchars(trim((string)$input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        if (class_exists('Security')) {
            $res = Security::getInstance()->checkRateLimit($identifier, $maxAttempts, $timeWindow);
            return $res['allowed'] ?? false;
        }
        // Basic session-based rate limit fallback
        $key = 'rl_' . md5($identifier);
        if (!isset($_SESSION[$key])) $_SESSION[$key] = ['count' => 0, 'reset' => time() + $timeWindow];
        if (time() > $_SESSION[$key]['reset']) { $_SESSION[$key] = ['count'=>1,'reset'=>time()+$timeWindow]; return true; }
        $_SESSION[$key]['count']++;
        return $_SESSION[$key]['count'] <= $maxAttempts;
    }

    public static function logSecurityEvent($event, $details = []) {
        if (class_exists('Security')) {
            return Security::getInstance()->logSecurityEvent($event, $details);
        }
        $entry = ['ts' => date('c'), 'event' => $event, 'details' => $details, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'];
        $logDir = __DIR__ . '/../../backend/logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/frontend_security.log', json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}

?>
