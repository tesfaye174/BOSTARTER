<?php
namespace Server;

class CSRF {
    private static $instance = null;
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = 'csrf_token';
    private const HEADER_NAME = 'X-CSRF-Token';
    private const COOKIE_NAME = 'csrf_token';

    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function generateToken(): string {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::SESSION_KEY] = $token;
        
        // Imposta il cookie con attributi di sicurezza
        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + 7200, // 2 ore
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        return $token;
    }

    public function validateToken(?string $token): bool {
        if (empty($token) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public function getTokenFromRequest(): ?string {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? null;
        }
        if ($token === null) {
            $input = json_decode(file_get_contents('php://input'), true);
            $token = $input['csrf_token'] ?? null;
        }
        return $token;
    }

    public function verifyRequest(): bool {
        $token = $this->getTokenFromRequest();
        return $this->validateToken($token);
    }

    public function removeToken(): void {
        unset($_SESSION[self::SESSION_KEY]);
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
} 