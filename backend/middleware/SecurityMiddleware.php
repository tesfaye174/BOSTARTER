<?php
class SecurityMiddleware {
    public static function applyAll() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set("session.cookie_httponly", 1);
            ini_set("session.cookie_secure", 0);
            ini_set("session.use_only_cookies", 1);
            session_start();
        }
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
    }
    public static function validateCSRF($token) {
        return isset($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);
    }
    public static function generateCSRF() {
        if (!isset($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }
        return $_SESSION["csrf_token"];
    }
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, "sanitizeInput"], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, "UTF-8");
    }
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
?>
