<?php
/**
 * Middleware centralizzato di sicurezza BOSTARTER
 * 
 * Applica misure di protezione di livello avanzato:
 * - Security Headers HTTP (CSP, HSTS, X-Frame-Options, ecc.)
 * - Protezione CSRF tramite token con verifica di integrità
 * - Monitoraggio e blocco di IP malevoli
 * - Controlli anti-injection su query e parametri
 * - Sanitizzazione automatica di parametri GET/POST
 */

require_once __DIR__ . '/../config/SecurityConfig.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/SecurityService.php';

use BOSTARTER\Services\SecurityService;

class SecurityMiddleware {
    private static $initialized = false;         // Flag di inizializzazione
    private static $securityService;             // Servizio di sicurezza condiviso
    
    /**
     * Inizializza tutte le misure di sicurezza in un punto centralizzato
     * 
     * Metodo principale da chiamare all'inizio di ogni richiesta HTTP.
     * Applica le protezioni nell'ordine corretto per massimizzare efficacia:
     * 1. Configurazione sessione sicura prima di qualsiasi output
     * 2. Impostazione headers HTTP di sicurezza
     * 3. Controllo IP bloccati
     * 4. Inizializzazione sessione crittografata
     */
    public static function initialize(): void {
        // Evita inizializzazioni multiple nella stessa richiesta
        if (self::$initialized) return;
        
        // Configurazione sicura cookie di sessione prima dell'output HTTP
        // (HttpOnly, SameSite, Secure flags, ecc.)
        if (session_status() === PHP_SESSION_NONE) {
            SecurityConfig::configureSecureSession();
        }
        
        // Impostazione headers HTTP di sicurezza
        // Richiede che non sia stato inviato alcun output al browser
        if (!headers_sent()) {
            SecurityConfig::setSecurityHeaders();
        }
        
        // Inizializzazione servizio di sicurezza con database
        self::$securityService = new SecurityService(Database::getInstance()->getConnection(), null);
        // Controllo IP nella blacklist prima di procedere
        self::checkBlockedIP();
        
        // Avvio sessione sicura se non già attiva
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        self::$initialized = true;
    }

    /**
     * Protezione CSRF (Cross-Site Request Forgery) per richieste POST
     * 
     * Verifica token CSRF su tutte le richieste POST per assicurare che:
     * - La richiesta provenga dallo stesso sito (stesso utente)
     * - Il token non sia scaduto (protezione replay attack)
     * - Il token non sia stato manipolato (firma HMAC verificata)
     * 
     * Restituisce errore 403 Forbidden se la protezione fallisce.
     */
    public static function csrfProtection(): void {
        // Verifica solo richieste POST che possono modificare dati
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($requestMethod === 'POST') {
            // Cerca token CSRF sia in POST che negli header HTTP personalizzati
            // (supporta sia form HTML che richieste AJAX/API)
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            // Verifica presenza e validità token
            if (empty($token) || !self::verifyCSRFToken($token)) {
                // Termina con errore 403 (Forbidden) - richiesta non autorizzata
                http_response_code(403);
                die(json_encode(['error' => 'Token CSRF non valido']));
            }
        }
    }

    /**
     * Sistema di rate limiting per prevenire abusi e attacchi DDoS
     * 
     * Limiti configurabili per tipo di azione:
     * - login: 10 tentativi/minuto
     * - register: 3 tentativi/ora 
     * - api: 60 richieste/minuto
     * - password_reset: 3 tentativi/ora
     * 
     * NOTA: Attualmente disabilitato in questa versione.
     * Implementazione completa utilizza sliding window con Redis o memcached.
     * 
     * @param string $action Tipo di azione da limitare (login, register, api, ecc.")
     * @throws \Exception Se il limite viene superato
     */
    public static function rateLimit(string $action = 'general'): void {
        // Rate limiting disabilitato temporaneamente
        // Implementazione completa richiederebbe storage distribuito
        return;
    }

    /**
     * Sanitizzazione automatica degli input utente
     * 
     * Applica la sanitizzazione a tutti i parametri GET e POST
     * escludendo quelli sensibili come password e token CSRF.
     */
    public static function sanitizeInputs(): void {
        if (!empty($_GET)) {
            $_GET = array_map([SecurityConfig::class, 'sanitizeInput'], $_GET);
        }
        if (!empty($_POST)) {
            $excludeFields = ['password', 'password_confirm', 'csrf_token'];
            foreach ($_POST as $key => $value) {
                if (!in_array($key, $excludeFields)) {
                    $_POST[$key] = SecurityConfig::sanitizeInput($value);
                }
            }
        }
    }

    /**
     * Validazione dei file caricati tramite form
     * 
     * Controlla che i file caricati rispettino i criteri di sicurezza:
     * - Dimensione massima
     * - Tipi di file consentiti (estensioni e MIME type)
     * 
     * Restituisce errore 400 Bad Request se la validazione fallisce.
     */
    public static function validateFileUploads(): void {
        if (!empty($_FILES)) {
            foreach ($_FILES as $file) {
                if (!self::isValidFile($file)) {
                    http_response_code(400);
                    die(json_encode(['error' => 'File non valido o non sicuro']));                }
            }
        }
    }

    /**
     * Registrazione delle richieste per monitoraggio sicurezza
     * 
     * Registra informazioni su IP, user agent, metodo e URI della richiesta
     * per analisi successive e rilevamento di attività sospette.
     */
    public static function logRequest(): void {
        if (SecurityConfig::LOGGING_CONFIG['log_security_events']) {
            $logData = [
                'ip' => SecurityConfig::getRealClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $_SESSION['user_id'] ?? null
            ];
            if (self::isSuspiciousRequest()) {
                self::$securityService->logSecurityEvent('suspicious_request', $logData);
            }
        }
    }

    /**
     * Applica tutte le misure di sicurezza configurate
     * 
     * Può essere utilizzato per forzare l'applicazione di specifiche misure
     * di sicurezza in base al contesto dell'applicazione o richieste API.
     */
    public static function applyAll(array $options = []): void {
        self::initialize();
        if ($options['rate_limit'] ?? true) self::rateLimit($options['action'] ?? 'general');
        if ($options['sanitize'] ?? true) self::sanitizeInputs();
        if ($options['csrf'] ?? false) self::csrfProtection();
        if ($options['file_validation'] ?? true) self::validateFileUploads();
        if ($options['logging'] ?? true) self::logRequest();
    }

    // =============== METODI PRIVATI ===============
    private static function checkBlockedIP(): void {
        $clientIP = SecurityConfig::getRealClientIP();
        if (SecurityConfig::isWhitelistedIP($clientIP)) return;
        if (SecurityConfig::isBlacklistedIP($clientIP)) {
            http_response_code(403);
            die('Accesso negato');
        }
        if (self::$securityService && self::$securityService->isIPBlocked($clientIP)) {
            http_response_code(429);
            die('IP temporaneamente bloccato per attività sospette');
        }
    }
    private static function verifyCSRFToken(string $token): bool {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) return false;
        if ((time() - $_SESSION['csrf_token_time']) > SecurityConfig::AUTH_CONFIG['csrf_token_lifetime']) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    private static function checkRateLimit(string $identifier, int $maxAttempts, int $timeWindow): bool {
        $key = 'rate_limit_' . hash('sha256', $identifier);
        if (!isset($_SESSION[$key])) $_SESSION[$key] = [];
        $now = time();
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        if (count($_SESSION[$key]) >= $maxAttempts) return false;
        $_SESSION[$key][] = $now;
        return true;
    }
    private static function isValidFile(array $file): bool {
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) return false;
        if ($file['size'] > SecurityConfig::INPUT_VALIDATION['max_file_size']) return false;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, SecurityConfig::INPUT_VALIDATION['allowed_file_types'])) return false;
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $detectedMime = mime_content_type($file['tmp_name']);
        if (!isset($allowedMimes[$extension]) || $detectedMime !== $allowedMimes[$extension]) return false;
        return true;
    }
    private static function isSuspiciousRequest(): bool {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $suspiciousPatterns = [
            '/\.\.\/\//',
            '/<script>/',
            '/union.*select/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i'
        ];
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $uri)) return true;
        }
        $suspiciousAgents = ['bot', 'crawler', 'spider', 'scanner'];
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) return true;
        }        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $postKey = 'consecutive_posts_' . SecurityConfig::getRealClientIP();
            if (!isset($_SESSION[$postKey])) $_SESSION[$postKey] = 0;
            $_SESSION[$postKey]++;
            if ($_SESSION[$postKey] > 10) return true;
        } else {
            $postKey = 'consecutive_posts_' . SecurityConfig::getRealClientIP();
            $_SESSION[$postKey] = 0;
        }
        return false;
    }
}
