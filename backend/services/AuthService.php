<?php
/**
 * Servizio centralizzato per l'autenticazione
 * Gestisce login, registrazione e sicurezza in modo unificato
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/MongoLogger.php';
require_once __DIR__ . '/SecurityService.php';

use BOSTARTER\Services\SecurityService;

class AuthService {
    private $db;
    private $mongoLogger;
    private $securityService;
      // Configurazione sicurezza
    private const MAX_LOGIN_ATTEMPTS = 10; // Aumentato da 5 a 10
    private const LOCKOUT_TIME = 300; // Ridotto da 900 (15 min) a 300 (5 min)
    private const SESSION_LIFETIME = 1800; // 30 minuti
    private const REMEMBER_TOKEN_LIFETIME = 2592000; // 30 giorni
      public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->mongoLogger = new MongoLogger();
        $this->securityService = new SecurityService($this->db);
    }
    
    /**
     * Assicura che la sessione sia avviata in modo sicuro
     */
    private function ensureSessionStarted(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * Gestisce il processo di login con sicurezza avanzata
     */
    public function login(string $email, string $password, bool $rememberMe = false): array {
        try {
            // 1. Validazione input
            $validation = $this->validateLoginInput($email, $password);
            if (!$validation['valid']) {
                return $this->loginFailure($email, $validation['errors']);
            }
            
            // 2. Controllo rate limiting
            if (!$this->checkRateLimit($email)) {
                return $this->loginFailure($email, ['Troppi tentativi di accesso. Riprova più tardi.']);
            }
            
            // 3. Verifica credenziali
            $user = $this->getUserByEmail($email);
            if (!$user || !$this->verifyPassword($password, $user['password_hash'])) {
                $this->recordFailedAttempt($email);
                return $this->loginFailure($email, ['Credenziali non valide']);
            }
              // 4. Controllo stato utente rimosso - campo 'attivo' non presente nel DB
            // if ($user['attivo'] != 1) {
            //     return $this->loginFailure($email, ['Account non attivo']);
            // }
            
            // 5. Login riuscito
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
    
    /**
     * Gestisce il processo di registrazione
     */
    public function register(array $userData): array {
        try {
            // 1. Validazione completa
            $validation = Validator::validateRegistration($userData);
            if ($validation !== true) {
                return [
                    'success' => false,
                    'errors' => is_array($validation) ? array_values($validation) : [$validation]
                ];
            }
            
            // 2. Controllo unicità email/nickname
            if ($this->emailExists($userData['email'])) {
                return ['success' => false, 'errors' => ['Email già registrata']];
            }
            
            if ($this->nicknameExists($userData['nickname'])) {
                return ['success' => false, 'errors' => ['Nickname già in uso']];
            }
            
            // 3. Creazione utente
            $userId = $this->createUser($userData);
            
            // 4. Login automatico
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
    
    /**
     * Logout sicuro con pulizia completa
     */    public function logout(): void {
        // Ensure we have an active session first
        $this->ensureSessionStarted();
        
        // Log logout prima di distruggere la sessione
        if (isset($_SESSION['user_id'])) {
            $this->mongoLogger->logActivity($_SESSION['user_id'], 'user_logout', [
                'logout_time' => date('Y-m-d H:i:s'),
                'session_duration' => time() - ($_SESSION['login_time'] ?? time())
            ]);
        }
        
        // Rimuovi remember token se presente
        if (isset($_COOKIE['bostarter_remember'])) {
            $this->removeRememberToken();
            if (!headers_sent()) {
                setcookie('bostarter_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            }
        }
        
        // Distruggi sessione solo se è attiva
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        // Restart a new session
        $this->ensureSessionStarted();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
      /**
     * Verifica se l'utente è autenticato
     */
    public function isAuthenticated(): bool {
        $this->ensureSessionStarted();
        
        // Controllo sessione base
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
            return false;
        }
        
        // Controllo timeout sessione
        if ((time() - $_SESSION['login_time']) > self::SESSION_LIFETIME) {
            $this->logout();
            return false;
        }
        
        // Rigenera session ID periodicamente
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration']) > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        return true;
    }
      /**
     * Genera e gestisce CSRF token
     */
    public function generateCSRFToken(): string {
        $this->ensureSessionStarted();
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Verifica CSRF token
     */
    public function verifyCSRFToken(string $token): bool {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Token scaduto dopo 1 ora
        if ((time() - $_SESSION['csrf_token_time']) > 3600) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Ottieni informazioni sullo stato del rate limiting per un identificatore
     */
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

    /**
     * Reset manuale del rate limiting per un identificatore
     */
    public function resetRateLimit(string $identifier): bool {
        $key = 'login_attempts_' . hash('sha256', $identifier . $_SERVER['REMOTE_ADDR']);
        
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            return true;
        }
        
        return false;
    }

    // =============== METODI PRIVATI ===============
    
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
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'login_attempts_' . hash('sha256', $identifier . $remoteAddr);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        }
        
        $attempts = $_SESSION[$key];
        
        // Reset se è passato il tempo di lockout
        if ((time() - $attempts['last_attempt']) > self::LOCKOUT_TIME) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
            return true;
        }
        
        return $attempts['count'] < self::MAX_LOGIN_ATTEMPTS;
    }
      private function recordFailedAttempt(string $identifier): void {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'login_attempts_' . hash('sha256', $identifier . $remoteAddr);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        }
        
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last_attempt'] = time();
        
        // Log tentativo fallito
        $this->mongoLogger->logSecurity('failed_login', [
            'email_hash' => hash('sha256', $identifier),
            'ip' => $remoteAddr,
            'attempts' => $_SESSION[$key]['count'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Blocca IP se troppi tentativi (solo se non è unknown)
        if ($_SESSION[$key]['count'] >= self::MAX_LOGIN_ATTEMPTS && $remoteAddr !== 'unknown') {
            try {
                $this->securityService->blockIP($remoteAddr, 'Troppi tentativi di login');
            } catch (Exception $e) {
                error_log("Failed to block IP: " . $e->getMessage());
            }
        }
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
        // Verifica con password_verify (metodo sicuro principale)
        if (password_verify($password, $hash)) {
            return true;
        }
        
        // RIMUOVI QUESTO BLOCCO IN PRODUZIONE - Solo per migrazione legacy
        if (strlen($hash) === 32 && md5($password) === $hash) {
            // Aggiorna automaticamente a hash sicuro
            $this->updatePasswordHash($hash, password_hash($password, PASSWORD_DEFAULT));
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
    }
      private function loginSuccess(array $user, bool $rememberMe): array {
        // Avvia sessione sicura
        $this->ensureSessionStarted();
        
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_regeneration'] = time();
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['nickname'],
            'tipo_utente' => $user['tipo_utente'],
            'nome' => $user['nome'],
            'cognome' => $user['cognome']
        ];
        
        // Aggiorna ultimo accesso
        $this->updateLastAccess($user['id']);
        
        // Gestione Remember Me
        if ($rememberMe) {
            $this->setRememberToken($user['id'], $user['email']);
        }
        
        // Reset tentativi falliti
        $key = 'login_attempts_' . hash('sha256', $user['email'] . $_SERVER['REMOTE_ADDR']);
        $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        
        // Log login riuscito
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
            // Verifica se la tabella remember_tokens esiste
            $check = $this->db->query("SHOW TABLES LIKE 'remember_tokens'");
            if ($check->rowCount() > 0) {
                $stmt = $this->db->prepare("
                    INSERT INTO remember_tokens (user_id, token, expires_at) 
                    VALUES (?, ?, FROM_UNIXTIME(?))
                    ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
                ");
                $stmt->execute([$userId, $hashedToken, $expires]);
            }
            
            // Imposta cookie sicuro
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
        
        // Log registrazione
        $this->mongoLogger->logActivity($userId, 'user_register', [
            'email' => $userData['email'],
            'register_time' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return $userId;
    }
}
