<?php
/**
 * Configurazione di sicurezza centralizzata per BOSTARTER
 * 
 * Questo file funge da "centrale operativa" per tutte le impostazioni
 * di sicurezza dell'applicazione. Centralizzare queste configurazioni
 * permette di:
 * - Avere una visione d'insieme delle protezioni attive
 * - Modificare facilmente i parametri quando necessario
 * - Garantire coerenza in tutta l'applicazione
 * 
 * @author BOSTARTER Security Team
 * @version 2.0.0
 */

class SecurityConfig {
    
    // =============== AUTENTICAZIONE ===============
    /**
     * Configurazioni relative all'autenticazione e protezione degli account
     * Questi parametri determinano la robustezza del sistema di accesso
     */
    public const AUTH_CONFIG = [
        'max_login_attempts' => 5,                // Tentativi di login falliti prima del blocco
        'lockout_time' => 900,                    // Durata del blocco dopo tentativi falliti (15 minuti)
        'session_lifetime' => 1800,               // Durata della sessione attiva (30 minuti)
        'remember_token_lifetime' => 2592000,     // Durata del "ricordami" (30 giorni)
        'csrf_token_lifetime' => 3600,            // Validità token anti-CSRF (1 ora)
        'session_regenerate_interval' => 300,     // Intervallo rigenerazione ID sessione (5 minuti)
        'password_min_length' => 8,               // Lunghezza minima password
        'require_special_chars' => true,          // Richiedi caratteri speciali nelle password
        'require_uppercase' => true,              // Richiedi maiuscole nelle password
        'require_lowercase' => true,              // Richiedi minuscole nelle password
        'require_numbers' => true                 // Richiedi numeri nelle password
    ];
    
    // =============== RATE LIMITING ===============
    /**
     * Limiti di frequenza per prevenire attacchi di forza bruta
     * Proteggono il sistema da tentativi ripetuti e automatizzati
     */
    public const RATE_LIMITS = [
        'login' => [
            'max_attempts' => 5,                 // Tentativi massimi di login
            'time_window' => 900                 // Finestra temporale (15 minuti)
        ],
        'register' => [
            'max_attempts' => 3,                 // Tentativi massimi di registrazione
            'time_window' => 1800                // Finestra temporale (30 minuti)
        ],
        'api' => [
            'max_requests' => 100,               // Richieste API massime 
            'time_window' => 3600                // Finestra temporale (1 ora)
        ],
        'password_reset' => [
            'max_attempts' => 3,                 // Tentativi di reset password
            'time_window' => 3600                // Finestra temporale (1 ora)
        ]
    ];
    
    // =============== COOKIE SECURITY ===============
    /**
     * Configurazioni di sicurezza per i cookie
     * Proteggono da attacchi XSS, CSRF e session hijacking
     */
    public const COOKIE_CONFIG = [
        'secure' => true,                        // Solo HTTPS (obbligatorio in produzione)
        'httponly' => true,                      // Non accessibili via JavaScript
        'samesite' => 'Strict',                  // Limita invio a richieste dallo stesso sito
        'path' => '/',                           // Percorso di validità del cookie
        'domain' => '',                          // Dominio (lasciare vuoto = host corrente)
        'lifetime' => [
            'session' => 0,                       // Cookie di sessione (scade alla chiusura browser)
            'remember' => 2592000                 // Durata cookie "ricordami" (30 giorni)
        ]
    ];
    
    // =============== HEADERS DI SICUREZZA ===============
    /**
     * Headers HTTP di sicurezza da inviare con ogni risposta
     * Sono come "istruzioni di sicurezza" per il browser del visitatore,
     * che aiutano a proteggere l'applicazione da vari attacchi
     */
    public const SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',                              // Previene il MIME type sniffing e possibili attacchi
        'X-Frame-Options' => 'DENY',                                        // Impedisce l'inclusione in iframe (anti-clickjacking)
        'X-XSS-Protection' => '1; mode=block',                              // Attiva la protezione XSS integrata nei browser
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains', // Forza la connessione HTTPS (HSTS)
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'", // Controllo risorse caricate
        'Referrer-Policy' => 'strict-origin-when-cross-origin',            // Limita le informazioni di provenienza nelle richieste
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()' // Limita l'accesso a funzionalità sensibili
    ];
    
    // =============== VALIDAZIONE INPUT ===============
    /**
     * Regole per la validazione e sanitizzazione degli input utente
     * Sono come un "filtro di sicurezza" che verifica tutti i dati 
     * inseriti prima che possano causare danni
     */
    public const INPUT_VALIDATION = [
        'max_field_length' => 1000,                                      // Lunghezza massima dei campi di testo
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'], // Tipi di file consentiti
        'max_file_size' => 5 * 1024 * 1024,                              // Dimensione massima file (5MB)
        'sanitize_defaults' => [
            'text' => FILTER_SANITIZE_STRING,                             // Sanitizzazione testo standard
            'email' => FILTER_SANITIZE_EMAIL,                             // Sanitizzazione email
            'url' => FILTER_SANITIZE_URL,                                 // Sanitizzazione URL
            'number' => FILTER_SANITIZE_NUMBER_INT                        // Sanitizzazione numeri
        ],
        'allowed_html_tags' => '<p><br><b><i><strong><em><ul><ol><li><h1><h2><h3><h4><h5><a><img>' // Tag HTML consentiti
    ];
    
    // =============== DATABASE SECURITY ===============
    /**
     * Impostazioni per la sicurezza del database
     * Proteggono i dati archiviati e le query eseguite
     */
    public const DB_SECURITY = [
        'use_prepared_statements' => true,         // Usa sempre prepared statement (anti-SQL injection)
        'escape_all_inputs' => true,               // Escape di tutti gli input usati nelle query
        'log_failed_queries' => true,              // Registra le query fallite
        'bind_params_only' => true,                // Usa solo parametri bind, mai concatenazione
        'connection_timeout' => 10,                // Timeout connessione in secondi
        'encrypt_sensitive_data' => true,          // Cripta i dati sensibili nel database
        'sensitive_columns' => [                   // Elenco colonne con dati sensibili
            'password_hash',
            'credit_card',
            'token',
            'secret_answer'
        ]
    ];
    
    // =============== PROTEZIONE CONTRO ATTACCHI COMUNI ===============
    /**
     * Configurazioni per mitigare attacchi comuni
     * Implementano varie contromisure contro minacce note
     */
    public const ATTACK_PROTECTION = [
        'csrf' => [
            'enabled' => true,                     // Protezione CSRF attiva
            'token_name' => '_csrf_token',         // Nome del token CSRF
            'check_all_post' => true,              // Verifica tutte le richieste POST
            'check_referer' => true,               // Verifica l'header referer
            'allowed_domains' => []                // Domini consentiti (vuoto = solo stesso dominio)
        ],
        'xss' => [
            'enabled' => true,                     // Protezione XSS attiva
            'auto_sanitize' => true,               // Sanitizzazione automatica input
            'escape_output' => true,               // Escape automatico output HTML
            'content_type_options' => true         // Imposta header X-Content-Type-Options
        ],
        'sql_injection' => [
            'enabled' => true,                     // Protezione SQL Injection attiva
            'use_prepared' => true,                // Usa solo prepared statements
            'log_attempts' => true                 // Registra tentativi sospetti
        ]
    ];

    // =============== LOGGING DI SICUREZZA ===============
    /**
     * Configurazione centralizzata per il logging degli eventi di sicurezza
     * Permette di abilitare/disabilitare e personalizzare il comportamento dei log
     */
    public const LOGGING_CONFIG = [
        'log_security_events' => true,                // Abilita/disabilita il logging degli eventi di sicurezza
        'log_file' => __DIR__ . '/../../logs/security-events.log', // Percorso file di log
        'log_level' => 'INFO',                        // Livello di log: DEBUG, INFO, WARNING, ERROR
        'max_log_size' => 5 * 1024 * 1024,            // Dimensione massima file di log (5MB)
        'rotate_logs' => true                         // Abilita rotazione automatica dei log
    ];

    /**
     * Imposta tutti gli header di sicurezza HTTP definiti nella configurazione.
     * Da richiamare all'inizio di ogni richiesta per proteggere l'applicazione.
     * 
     * @return void
     */
    public static function setSecurityHeaders() {
        foreach (self::SECURITY_HEADERS as $header => $value) {
            header("$header: $value");
        }
    }

    /**
     * Restituisce l'indirizzo IP reale del client, gestendo proxy e header comuni.
     * Utile per logging, sicurezza e rate limiting.
     *
     * @return string Indirizzo IP del client
     */
    public static function getRealClientIP() {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        foreach ($headers as $key) {
            if (!empty($_SERVER[$key])) {
                $ipList = explode(',', $_SERVER[$key]);
                foreach ($ipList as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        // Fallback: restituisce REMOTE_ADDR anche se privato
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Verifica se l'indirizzo IP fornito è presente nella whitelist.
     * Utile per bypassare controlli di sicurezza su IP fidati (es. amministrazione).
     *
     * @param string $ip Indirizzo IP da verificare
     * @return bool True se l'IP è in whitelist, false altrimenti
     */
    public static function isWhitelistedIP($ip) {
        // Definisci qui la whitelist di IP (può essere spostata in config esterna)
        $whitelist = [
            '127.0.0.1',      // localhost
            '::1',            // IPv6 localhost
            // '192.168.1.100', // Esempio di IP interno
            // Aggiungi altri IP se necessario
        ];
        return in_array($ip, $whitelist, true);
    }

    /**
     * Verifica se l'indirizzo IP fornito è presente nella blacklist.
     * Utile per bloccare IP noti per attività malevole.
     *
     * @param string $ip Indirizzo IP da verificare
     * @return bool True se l'IP è in blacklist, false altrimenti
     */
    public static function isBlacklistedIP($ip) {
        // Definisci qui la blacklist di IP (può essere spostata in config esterna)
        $blacklist = [
            // '203.0.113.1', // Esempio di IP bloccato
            // Aggiungi altri IP se necessario
        ];
        return in_array($ip, $blacklist, true);
    }

    /**
     * Sanitizza un input secondo le regole di sicurezza definite.
     *
     * @param mixed $value Valore da sanitizzare
     * @return mixed Valore sanitizzato
     */
    public static function sanitizeInput($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitizeInput'], $value);
        }
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }

    /**
     * Configura la sessione in modo sicuro (cookie, parametri, ecc.).
     * Da richiamare all'avvio della sessione.
     *
     * @return void
     */
    public static function configureSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => self::COOKIE_CONFIG['lifetime']['session'],
                'path' => self::COOKIE_CONFIG['path'],
                'domain' => self::COOKIE_CONFIG['domain'],
                'secure' => self::COOKIE_CONFIG['secure'],
                'httponly' => self::COOKIE_CONFIG['httponly'],
                'samesite' => self::COOKIE_CONFIG['samesite']
            ]);
            session_start();
        }
    }

    /**
     * Verifica se la connessione corrente è in HTTPS.
     *
     * @return bool True se HTTPS, false altrimenti
     */
    public static function isHTTPS() {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        return false;
    }
}
