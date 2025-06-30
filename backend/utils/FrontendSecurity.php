<?php
class FrontendSecurity {
    public static function setSecurityHeaders() {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https:
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
    public static function getCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > 1800) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    public static function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    public static function validateInput($input, $rules) {
        $errors = [];
        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? '';
            foreach ($fieldRules as $rule => $ruleValue) {
                switch ($rule) {
                    case 'required':
                        if ($ruleValue && empty($value)) {
                            $errors[] = "$field è obbligatorio";
                        }
                        break;
                    case 'min_length':
                        if (strlen($value) < $ruleValue) {
                            $errors[] = "$field deve essere di almeno $ruleValue caratteri";
                        }
                        break;
                    case 'max_length':
                        if (strlen($value) > $ruleValue) {
                            $errors[] = "$field non può superare $ruleValue caratteri";
                        }
                        break;
                    case 'email':
                        if ($ruleValue && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "$field deve essere un'email valida";
                        }
                        break;
                    case 'numeric':
                        if ($ruleValue && !is_numeric($value)) {
                            $errors[] = "$field deve essere un numero";
                        }
                        break;
                    case 'min_value':
                        if (is_numeric($value) && $value < $ruleValue) {
                            $errors[] = "$field deve essere almeno $ruleValue";
                        }
                        break;
                    case 'max_value':
                        if (is_numeric($value) && $value > $ruleValue) {
                            $errors[] = "$field non può essere maggiore di $ruleValue";
                        }
                        break;
                    case 'in':
                        if (!in_array($value, $ruleValue)) {
                            $errors[] = "$field contiene un valore non valido";
                        }
                        break;
                    case 'url':
                        if ($ruleValue && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[] = "$field deve essere un URL valido";
                        }
                        break;
                }
            }
        }
        return empty($errors) ? true : $errors;
    }
    public static function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        $sessionKey = "rate_limit_{$action}";
        $now = time();
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ['count' => 0, 'reset_time' => $now + $timeWindow];
        }
        $rateLimitData = $_SESSION[$sessionKey];
        if ($now > $rateLimitData['reset_time']) {
            $_SESSION[$sessionKey] = ['count' => 0, 'reset_time' => $now + $timeWindow];
            $rateLimitData = $_SESSION[$sessionKey];
        }
        if ($rateLimitData['count'] >= $maxAttempts) {
            return false;
        }
        return true;
    }
    public static function incrementRateLimit($action, $timeWindow = 300) {
        $sessionKey = "rate_limit_{$action}";
        $now = time();
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ['count' => 0, 'reset_time' => $now + $timeWindow];
        }
        $_SESSION[$sessionKey]['count']++;
    }
    public static function csrfField() {
        $token = self::getCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . dirname($_SERVER['PHP_SELF']) . "/../auth/login.php");
            exit();
        }
    }
    public static function requireRole($role) {
        self::requireAuth();
        if (!isset($_SESSION['user']) || $_SESSION['user']['tipo_utente'] !== $role) {
            header("Location: " . dirname($_SERVER['PHP_SELF']) . "/../dashboard.php");
            exit();
        }
    }
    public static function logSecurityEvent($event, $details = []) {
        try {
            require_once __DIR__ . '/../services/MongoLogger.php';
            $mongoLogger = new MongoLogger();
            $mongoLogger->logActivity($_SESSION['user_id'] ?? null, "security_$event", array_merge([
                'timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ], $details));
        } catch (Exception $e) {
            error_log("Security logging failed: " . $e->getMessage());
        }
    }
    public static function escape($value, $doubleEncode = true) {
        if (is_array($value)) {
            return array_map(function($item) use ($doubleEncode) {
                return self::escape($item, $doubleEncode);
            }, $value);
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
    public static function generateNonce() {
        $nonce = base64_encode(random_bytes(16));
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['script_nonce'] = $nonce;
        }
        return $nonce;
    }
    public static function setCSPWithNonce() {
        $nonce = self::generateNonce();
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' https:
        return $nonce;
    }
}
