<?php
/**
 * Middleware di autenticazione BOSTARTER
 * 
 * Implementa controlli di sicurezza a livello HTTP per proteggere l'accesso alle risorse:
 * - Rate limiting basato su IP per prevenire attacchi brute force
 * - Validazione sessioni con timeout automatico e rotazione ID
 * - Sanitizzazione input per prevenire XSS e SQL injection
 * - Logging centralizzato tentativi di accesso falliti
 */

class AuthMiddleware {
    private static $rateLimits = [];     // Cache di tentativi per limitazione frequenza
    private static $loginAttempts = [];  // Registro tentativi login falliti
    
    /**
     * Implementa rate limiting per prevenire attacchi brute force
     * 
     * Utilizza una finestra temporale scorrevole invece del classico reset fisso,
     * rendendo più difficili gli attacchi temporizzati. Il sistema tiene traccia
     * di ogni richiesta e applica limiti basati sull'identificatore fornito
     * (tipicamente indirizzo IP o email utente).
     * 
     * @param string $identifier Identificatore univoco (IP, email, ecc.) 
     * @param int $maxAttempts Numero massimo di tentativi nella finestra temporale
     * @param int $timeWindow Finestra temporale in secondi (default: 5 minuti)
     * @return bool True se la richiesta è permessa, False se bloccata
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $now = time();
        $key = md5($identifier);  // Hash dell'identificatore per privacy
        
        // Pulizia tentativi obsoleti dalla finestra temporale scorrevole
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
        
        // Verifica se il limite è stato superato
        if (count(self::$rateLimits[$key]) >= $maxAttempts) {
            return false;  // Limite superato, blocca la richiesta
        }
        
        // Registra il tentativo corrente e consenti la richiesta
        self::$rateLimits[$key][] = $now;
        return true;
    }
    
    /**
     * Registra tentativi di accesso falliti in MongoDB per analisi di sicurezza
     * 
     * Il logging dei tentativi falliti permette di:
     * - Rilevare pattern di attacco distribuiti
     * - Identificare tentativi di compromissione account
     * - Fornire dati per analisi forensi in caso di intrusione
     * 
     * @param string $identifier Identificatore dell'utente (email/username)
     * @param string $reason Motivo del fallimento (default: authentication_failed)
     */
    public static function logFailedAttempt($identifier, $reason = 'authentication_failed') {
        $mongoLogger = new MongoLogger();
        $mongoLogger->logSecurity('failed_login', [
            'identifier' => hash('sha256', $identifier),  // Hash per privacy (non memorizza email in chiaro)
            'reason' => $reason,                          // Codice motivo fallimento (credenziali, account bloccato, ecc)
            'timestamp' => date('Y-m-d H:i:s'),           // Timestamp ISO 8601 per correlazione eventi
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown', // Indirizzo IP per rilevamento attacchi distribuiti
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'  // User-Agent per identificazione bot
        ]);
    }
    
    /**
     * Valida la sessione corrente e applica misure di sicurezza automatiche
     * 
     * Implementa diverse protezioni:
     * - Timeout sessione dopo inattività (30 minuti)
     * - Rigenerazione periodica ID sessione per prevenire session fixation
     * - Conservazione stato autenticazione tra richieste
     * 
     * @return bool True se la sessione è valida e autenticata, False altrimenti
     */
    public static function validateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verifica timeout sessione (30 minuti = 1800 secondi)
        // Termina automaticamente sessioni inattive per ridurre rischio di hijacking
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();     // Rimuove tutte le variabili di sessione
            session_destroy();   // Distrugge la sessione server-side
            return false;
        }
        
        // Aggiorna il timestamp dell'ultima attività
        $_SESSION['last_activity'] = time();
        
        // Rigenerazione periodica ID sessione (ogni 5 minuti = 300 secondi)
        // Previene attacchi session fixation mantenendo l'utente autenticato
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration'] > 300)) {
            session_regenerate_id(true);  // true = elimina vecchia sessione
            $_SESSION['last_regeneration'] = time();
        }
        
        // Verifica se l'utente è autenticato (user_id presente in sessione)
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Sanitizza dati di input per prevenire XSS e altri attacchi di injection
     * 
     * Converte caratteri speciali in entità HTML e rimuove whitespace indesiderato.
     * Supporta ricorsivamente array multidimensionali.
     * 
     * @param mixed $data Input da sanitizzare (stringa o array)
     * @return mixed Dati sanitizzati mantenendo la stessa struttura
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            // Sanitizzazione ricorsiva per array multidimensionali
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        // ENT_QUOTES converte sia doppi che singoli apici
        // UTF-8 assicura gestione corretta caratteri multibyte
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valida la robustezza di una password secondo criteri di sicurezza avanzati
     * 
     * NOTA: Funzione duplicata in Validator.php - considerare consolidamento
     * 
     * Verifica che la password rispetti tutti i seguenti criteri NIST:
     * - Lunghezza minima 8 caratteri
     * - Almeno una lettera maiuscola
     * - Almeno una lettera minuscola 
     * - Almeno un numero
     * - Almeno un carattere speciale
     * 
     * @param string $password Password da validare
     * @return array Lista errori (vuota se la password è valida)
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        // Verifica lunghezza minima (8 caratteri per bilanciare sicurezza e usabilità)
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        // Verifica presenza maiuscole (aumenta entropia della password)
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        // Verifica presenza minuscole (requisito base per completezza)
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        // Verifica presenza numeri (aumenta spazio caratteri disponibili)
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        // Verifica presenza caratteri speciali (aumenta significativamente l'entropia)
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
}