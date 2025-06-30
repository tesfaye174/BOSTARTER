<?php
namespace BOSTARTER\Services;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/MongoLogger.php';
require_once __DIR__ . '/SecurityService.php';
use BOSTARTER\Services\SecurityService;
use BOSTARTER\Services\MongoLogger;
use PDO;
class AuthService {
    private $db;
    private $mongoLogger;
    private $securityService;
    private const MAX_LOGIN_ATTEMPTS = 10;              
    private const LOCKOUT_TIME = 300;                   
    private const SESSION_LIFETIME = 1800;              
    private const REMEMBER_TOKEN_LIFETIME = 2592000;    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
        $this->mongoLogger = new MongoLogger();
        $this->securityService = new SecurityService($this->db);
    }
    private function ensureSessionStarted(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
    public function login(string $email, string $password, bool $rememberMe = false): array {
        try {
            $validation = $this->validateLoginInput($email, $password);
            if (!$validation['valid']) {
                return $this->loginFailure($email, $validation['errors']);
            }
            $user = $this->getUserByEmail($email);
            if (!$user || !$this->verifyPassword($password, $user['password_hash'])) {
                $this->recordFailedAttempt($email);
                return $this->loginFailure($email, ['Credenziali non valide']);
            }
            return $this->loginSuccess($user, $rememberMe);
        } catch (Exception $e) {
            $this->mongoLogger->logSystem('auth_error', [
                'error' => $e->getMessage(),
                'email' => $email,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return [
                'success' => false,
                'errors' => ['Errore interno del server']
            ];
        }
    }
    public function register(array $userData): array {
        try {
            $validation = Validator::validateRegistration($userData);
            if ($validation !== true) {
                return [
                    'success' => false,
                    'errors' => is_array($validation) ? array_values($validation) : [$validation]
                ];
            }
            if ($this->emailExists($userData['email'])) {
                return ['success' => false, 'errors' => ['Email già registrata']];
            }
            if ($this->nicknameExists($userData['nickname'])) {
                return ['success' => false, 'errors' => ['Nickname già in uso']];
            }
            $userId = $this->createUser($userData);
            $user = $this->getUserById($userId);
            return $this->loginSuccess($user, false);
        } catch (Exception $e) {
            $this->mongoLogger->logSystem('registration_error', [
                'error' => $e->getMessage(),
                'email' => $userData['email'] ?? 'unknown'
            ]);
            return [
                'success' => false,
                'errors' => ['Errore durante la registrazione']
            ];
        }
    }
    public function logout(): void {
        $this->ensureSessionStarted();
        if (isset($_SESSION['user_id'])) {
            $this->mongoLogger->logActivity($_SESSION['user_id'], 'user_logout', [
                'logout_time' => date('Y-m-d H:i:s'),
                'session_duration' => time() - ($_SESSION['login_time'] ?? time()),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        if (isset($_COOKIE['bostarter_remember'])) {
            $this->removeRememberToken();
            if (!headers_sent()) {
                setcookie('bostarter_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            }
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();     
            session_destroy();   
        }
        $this->ensureSessionStarted();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    public function isAuthenticated(): bool {
        $this->ensureSessionStarted();
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
            return false;
        }
        if ((time() - $_SESSION['login_time']) > self::SESSION_LIFETIME) {
            $this->logout();
            return false;
        }
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration']) > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        return true;
    }
    public function generateCSRFToken(): string {
        $this->ensureSessionStarted();
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }
    public function verifyCSRFToken(string $token): bool {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        if ((time() - $_SESSION['csrf_token_time']) > 3600) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    public function getRateLimitStatus(string $identifier): array {
        $key = 'login_attempts_' . hash('sha256', $identifier . $_SERVER['REMOTE_ADDR']);
        if (!isset($_SESSION[$key])) {
            return [
                'blocked' => false,
                'attempts' => 0,
                'remaining_attempts' => self::MAX_LOGIN_ATTEMPTS,
                'lockout_expires' => null,
                'time_remaining' => 0
            ];
        }
        $attempts = $_SESSION[$key];
        $time_since_last = time() - $attempts['last_attempt'];
        $is_locked_out = $attempts['count'] >= self::MAX_LOGIN_ATTEMPTS && $time_since_last < self::LOCKOUT_TIME;
        return [
            'blocked' => $is_locked_out,
            'attempts' => $attempts['count'],
            'remaining_attempts' => max(0, self::MAX_LOGIN_ATTEMPTS - $attempts['count']),
            'lockout_expires' => $is_locked_out ? $attempts['last_attempt'] + self::LOCKOUT_TIME : null,
            'time_remaining' => $is_locked_out ? self::LOCKOUT_TIME - $time_since_last : 0
        ];
    }
    public function resetRateLimit(string $identifier): bool {
        $key = 'login_attempts_' . hash('sha256', $identifier . $_SERVER['REMOTE_ADDR']);
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            return true;
        }
        return false;
    }
    private function validateLoginInput(string $email, string $password): array {
        $errors = [];
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email non valida';
        }
        if (empty($password)) {
            $errors[] = 'Password obbligatoria';
        }
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
      private function checkRateLimit(string $identifier): bool {
        return true;
    }
    private function recordFailedAttempt(string $identifier): void {
        return;
    }
      private function getUserByEmail(string $email): ?array {
        $stmt = $this->db->prepare("
            SELECT id, email, nickname, password_hash, nome, cognome, 
                   tipo_utente, last_access 
            FROM utenti 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
      private function getUserById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT id, email, nickname, nome, cognome, tipo_utente 
            FROM utenti 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    private function verifyPassword(string $password, string $hash): bool {
        if (password_verify($password, $hash)) {
            return true;
        }
        return false;
    }
    private function updatePasswordHash(string $oldHash, string $newHash): void {
        $stmt = $this->db->prepare("UPDATE utenti SET password_hash = ? WHERE password_hash = ?");
        $stmt->execute([$newHash, $oldHash]);
    }
    private function loginFailure(string $email, array $errors): array {
        $this->recordFailedAttempt($email);
        return [
            'success' => false,
            'errors' => $errors
        ];
    }    private function loginSuccess(array $user, bool $rememberMe): array {
        $this->ensureSessionStarted();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_regeneration'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['nickname'],
            'tipo_utente' => $user['tipo_utente'],
            'nome' => $user['nome'],
            'cognome' => $user['cognome']
        ];
        $this->updateLastAccess($user['id']);
        if ($rememberMe) {
            $this->setRememberToken($user['id'], $user['email']);
        }
        $key = 'login_attempts_' . hash('sha256', $user['email'] . $_SERVER['REMOTE_ADDR']);
        $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        $this->mongoLogger->logActivity($user['id'], 'user_login', [
            'email' => $user['email'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'remember_me' => $rememberMe
        ]);
        return [
            'success' => true,
            'message' => 'Login effettuato con successo',
            'user' => $_SESSION['user']
        ];
    }
    private function updateLastAccess(int $userId): void {
        $stmt = $this->db->prepare("UPDATE utenti SET last_access = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$userId]);
    }
    private function setRememberToken(int $userId, string $email): void {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        $expires = time() + self::REMEMBER_TOKEN_LIFETIME;
        try {
            $check = $this->db->query("SHOW TABLES LIKE 'remember_tokens'");
            if ($check->rowCount() > 0) {
                $stmt = $this->db->prepare("
                    INSERT INTO remember_tokens (user_id, token, expires_at) 
                    VALUES (?, ?, FROM_UNIXTIME(?))
                    ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
                ");
                $stmt->execute([$userId, $hashedToken, $expires]);
            }
            $cookieData = json_encode([
                'email' => $email,
                'token' => $token,
                'expires' => $expires
            ]);
            setcookie('bostarter_remember', $cookieData, $expires, '/', '', 
                     isset($_SERVER['HTTPS']), true);
        } catch (Exception $e) {
            error_log("Remember token error: " . $e->getMessage());
        }
    }
    private function removeRememberToken(): void {
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
            } catch (Exception $e) {
                error_log("Remove remember token error: " . $e->getMessage());
            }
        }
    }
    private function emailExists(string $email): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM utenti WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }
    private function nicknameExists(string $nickname): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM utenti WHERE nickname = ?");
        $stmt->execute([$nickname]);
        return $stmt->fetchColumn() > 0;
    }
      private function createUser(array $userData): int {        $stmt = $this->db->prepare("
            INSERT INTO utenti (
                email, nickname, password_hash, nome, cognome, 
                anno_nascita, luogo_nascita, tipo_utente
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userData['email'],
            $userData['nickname'],
            password_hash($userData['password'], PASSWORD_DEFAULT),
            $userData['nome'],
            $userData['cognome'],
            $userData['anno_nascita'] ?? null,
            $userData['luogo_nascita'] ?? null,
            $userData['tipo_utente'] ?? 'standard'
        ]);
        $userId = $this->db->lastInsertId();
        $this->mongoLogger->logActivity($userId, 'user_register', [
            'email' => $userData['email'],
            'register_time' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        return $userId;
    }
}
