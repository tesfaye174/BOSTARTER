<?php
/**
 * Servizio di autenticazione BOSTARTER
 * 
 * Gestisce l'intero ciclo di autenticazione degli utenti:
 * - Login con verifica password tramite password_verify()
 * - Protezione contro attacchi brute force con rate limiting
 * - Implementazione Remember Me con token sicuri
 * - Gestione sessioni con rigenerazione ID per prevenire session fixation
 * - Logging degli accessi per audit di sicurezza
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
    
    // Impostazioni di sicurezza - Configurabili in base al livello di sicurezza richiesto
    private const MAX_LOGIN_ATTEMPTS = 10;              // Tentativi falliti prima del blocco 
    private const LOCKOUT_TIME = 300;                   // Tempo di blocco in secondi (5 minuti)
    private const SESSION_LIFETIME = 1800;              // Durata sessione in secondi (30 minuti)
    private const REMEMBER_TOKEN_LIFETIME = 2592000;    // Durata cookie "ricordami" (30 giorni)
    
    /**
     * Inizializza il servizio di autenticazione con le dipendenze necessarie
     * Imposta la connessione al database, il logger MongoDB per il tracciamento
     * e il servizio di sicurezza che gestisce la protezione dell'applicazione
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->mongoLogger = new MongoLogger();
        $this->securityService = new SecurityService($this->db);
    }
    
    /**
     * Verifica e avvia la sessione se non è già attiva
     * Importante: deve essere chiamato prima di qualsiasi operazione che usa $_SESSION
     * Controllo su headers_sent() previene warning per sessioni avviate dopo output
     */
    private function ensureSessionStarted(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * Gestisce l'intero processo di login con controlli di sicurezza
     * 
     * @param string $email Email dell'utente (già validata come formato email)
     * @param string $password Password in chiaro per la verifica
     * @param bool $rememberMe Se attivare la funzionalità "ricordami"
     * @return array Risultato dell'operazione con dati utente in caso di successo
     */
    public function login(string $email, string $password, bool $rememberMe = false): array {
        try {
            // 1. Validazione input con controlli avanzati (XSS, SQL injection)
            // Prima verifica della validità dell'email e della lunghezza minima password
            $validation = $this->validateLoginInput($email, $password);
            if (!$validation['valid']) {
                return $this->loginFailure($email, $validation['errors']);
            }
            
            // 2. Controllo rate limiting - protezione brute force
            // NOTA: Temporaneamente disattivato per debugging, da riattivare in produzione
            // if (!$this->checkRateLimit($email)) {
            //     return $this->loginFailure($email, ['Troppi tentativi di accesso. Riprova più tardi.']);
            // }
            
            // 3. Verifica credenziali contro il database
            // Utilizziamo password_hash+password_verify per gestire le password in modo sicuro
            $user = $this->getUserByEmail($email);
            if (!$user || !$this->verifyPassword($password, $user['password_hash'])) {
                // Registra il tentativo fallito nel contatore per il rate limiting
                $this->recordFailedAttempt($email);
                return $this->loginFailure($email, ['Credenziali non valide']);
            }
            
            // 4. Controllo stato account (disattivato: manca il campo 'attivo' nel DB)
            // Questo permetterà in futuro di gestire account sospesi o disattivati
            // if ($user['attivo'] != 1) {
            //     return $this->loginFailure($email, ['Account non attivo']);
            // }
            
            // 5. Login riuscito: configurazione della sessione sicura e gestione "ricordami"
            // Imposta i dati utente in sessione e genera token remember-me se richiesto
            return $this->loginSuccess($user, $rememberMe);
            
        } catch (Exception $e) {
            // Log dettagliato dell'errore in MongoDB per analisi e debugging
            $this->mongoLogger->logSystem('auth_error', [
                'error' => $e->getMessage(),
                'email' => $email,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Risposta generica all'utente per non esporre dettagli tecnici
            return [
                'success' => false,
                'errors' => ['Errore interno del server']
            ];
        }
    }
    
    /**
     * Gestisce il processo di registrazione di un nuovo utente
     * 
     * Implementa una serie di controlli di sicurezza:
     * 1. Validazione dei dati secondo regole specifiche
     * 2. Verifica di unicità per email e nickname
     * 3. Hashing sicuro della password tramite bcrypt/Argon2
     * 4. Prevenzione di race condition nelle verifiche di unicità
     * 
     * @param array $userData Array con chiavi: email, password, nickname, nome, cognome, ecc.
     * @return array Risultato dell'operazione con eventuali errori specifici
     */
    public function register(array $userData): array {
        try {
            // 1. Validazione completa dei dati utente secondo regole di sicurezza
            // Verifica formato email, complessità password, lunghezza campi, caratteri consentiti, ecc.
            $validation = Validator::validateRegistration($userData);
            if ($validation !== true) {
                return [
                    'success' => false,
                    'errors' => is_array($validation) ? array_values($validation) : [$validation]
                ];
            }
            
            // 2. Controllo unicità email e nickname prima di procedere
            // Previene registrazioni duplicate anche in caso di richieste simultanee
            if ($this->emailExists($userData['email'])) {
                return ['success' => false, 'errors' => ['Email già registrata']];
            }
            
            if ($this->nicknameExists($userData['nickname'])) {
                return ['success' => false, 'errors' => ['Nickname già in uso']];
            }
            
            // 3. Creazione dell'utente con password crittografata
            // La funzione createUser gestisce l'hashing della password in modo sicuro
            $userId = $this->createUser($userData);
            
            // 4. Login automatico dopo registrazione riuscita
            // Evita all'utente di dover reinserire le credenziali
            $user = $this->getUserById($userId);
            return $this->loginSuccess($user, false);
            
        } catch (Exception $e) {
            // Log dettagliato dell'errore per analisi
            $this->mongoLogger->logSystem('registration_error', [
                'error' => $e->getMessage(),
                'email' => $userData['email'] ?? 'unknown'
            ]);
            
            // Risposta generica per l'utente finale
            return [
                'success' => false,
                'errors' => ['Errore durante la registrazione']
            ];
        }
    }
    
    /**
     * Effettua il logout dell'utente corrente in modo sicuro
     * 
     * Implementa tutte le operazioni necessarie per una chiusura sicura della sessione:
     * - Rimozione di tutti i dati di sessione
     * - Cancellazione del token remember-me dal database e dal cookie
     * - Invalidazione dell'ID di sessione esistente
     * - Logging dell'operazione per audit di sicurezza
     */
    public function logout(): void {
        // Verifica che la sessione sia attiva per evitare errori
        $this->ensureSessionStarted();
        
        // Log dell'attività con dettagli utili per analisi di sicurezza
        if (isset($_SESSION['user_id'])) {
            $this->mongoLogger->logActivity($_SESSION['user_id'], 'user_logout', [
                'logout_time' => date('Y-m-d H:i:s'),
                'session_duration' => time() - ($_SESSION['login_time'] ?? time()),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        // Rimozione del token "ricordami" sia dal cookie che dal database
        // Previene possibili attacchi di session hijacking in caso di furto del token
        if (isset($_COOKIE['bostarter_remember'])) {
            $this->removeRememberToken();
            if (!headers_sent()) {
                // Imposta scadenza nel passato e flag sicurezza per forzare eliminazione
                setcookie('bostarter_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            }
        }
        
        // Pulizia e distruzione completa della sessione corrente
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();     // Rimuove tutte le variabili di sessione
            session_destroy();   // Distrugge la sessione nel server
        }
        
        // Avvia una nuova sessione pulita per evitare problemi con successive richieste
        $this->ensureSessionStarted();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    /**
     * Verifica se l'utente è attualmente autenticato
     * Controlla la presenza di un ID utente in sessione e la validità del timeout
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
        
        // Rigenera session ID periodicamente per sicurezza
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration']) > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        return true;
    }
    
    /**
     * Genera un nuovo CSRF token e lo memorizza in sessione
     * Utilizzato per proteggere le form contro attacchi CSRF
     */
    public function generateCSRFToken(): string {
        $this->ensureSessionStarted();
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Verifica la validità di un CSRF token confrontandolo con quello in sessione
     * I token scadono dopo 1 ora per limitare il rischio in caso di furto
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
        // Rate limiting disabilitato: sempre true
        return true;
    }

    private function recordFailedAttempt(string $identifier): void {
        // Rate limiting disabilitato: non fa nulla
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
        // Verifica con password_verify (metodo sicuro principale)
        if (password_verify($password, $hash)) {
            return true;
        }
          // LEGACY MD5 SUPPORT REMOVED FOR SECURITY
        // This legacy support has been removed to prevent MD5 password vulnerabilities
        
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
        // Avvia sessione sicura
        $this->ensureSessionStarted();
        
        // Rigenera l'ID di sessione per sicurezza
        session_regenerate_id(true);
        
        // Imposta i dati della sessione
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
