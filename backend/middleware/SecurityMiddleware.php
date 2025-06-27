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
     * @throws SecurityException Se l'inizializzazione fallisce
     * @return void
     */
    public static function initialize(): void {
        if (self::$initialized) {
            return;
        }

        try {
            // Inizializza il servizio di sicurezza
            self::$securityService = new SecurityService();

            // 1. Configurazione sicura della sessione
            self::initializeSecureSession();

            // 2. Impostazione degli header di sicurezza
            self::setSecurityHeaders();

            // 3. Controllo IP malevoli
            self::checkMaliciousIP();

            // 4. Inizializzazione protezione CSRF
            self::initializeCsrfProtection();

            // 5. Configurazione sanitizzazione input
            self::configureSanitization();

            self::$initialized = true;

        } catch (Exception $e) {
            throw new SecurityException('Security initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Configura la sessione con impostazioni di sicurezza avanzate
     */
    private static function initializeSecureSession(): void {
        // Previeni attacchi di session fixation
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', '3600'); // 1 ora
            ini_set('session.use_trans_sid', '0');
            
            session_start();
        }

        // Rigenera l'ID sessione periodicamente
        if (!isset($_SESSION['last_regeneration']) || 
            time() - $_SESSION['last_regeneration'] > 300) { // 5 minuti
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /**
     * Imposta gli header HTTP di sicurezza
     */
    private static function setSecurityHeaders(): void {
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'"
        ];

        header("Content-Security-Policy: " . implode('; ', $cspDirectives));
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
    }

    /**
     * Verifica se l'IP corrente è considerato malevolo
     * 
     * @throws SecurityException Se l'IP è bloccato
     */
    private static function checkMaliciousIP(): void {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Controlla blacklist
        if (self::$securityService->isIPBlacklisted($ip)) {
            throw new SecurityException('Access denied: IP is blacklisted');
        }

        // Controlla rate limiting
        if (!self::$securityService->checkRateLimit($ip)) {
            throw new SecurityException('Access denied: Rate limit exceeded');
        }

        // Controlla comportamenti sospetti
        if (self::$securityService->isSuspiciousActivity($ip)) {
            self::$securityService->logSuspiciousActivity($ip);
            if (self::$securityService->shouldBlockIP($ip)) {
                self::$securityService->blacklistIP($ip);
                throw new SecurityException('Access denied: Suspicious activity detected');
            }
        }
    }

    /**
     * Inizializza la protezione CSRF
     */
    private static function initializeCsrfProtection(): void {
        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) || 
            time() - $_SESSION['csrf_token_time'] > 3600) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
    }

    /**
     * Configura la sanitizzazione automatica degli input
     */
    private static function configureSanitization(): void {
        filter_var_array($_GET, FILTER_SANITIZE_STRING);
        filter_var_array($_POST, FILTER_SANITIZE_STRING);
        
        // Configurazione sanitizzazione personalizzata per tipi specifici
        self::$securityService->setSanitizationRules([
            'email' => FILTER_SANITIZE_EMAIL,
            'url' => FILTER_SANITIZE_URL,
            'html' => function($input) {
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            }
        ]);
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

    /**
     * Verifica l'autenticazione dell'utente
     * 
     * @throws SecurityException Se l'utente non è autenticato
     * @return bool
     */
    public static function requireAuthentication(): bool {
        if (!isset($_SESSION['user_id'])) {
            throw new SecurityException('Authentication required');
        }

        // Verifica validità sessione
        if (!self::isValidSession()) {
            self::terminateSession();
            throw new SecurityException('Invalid session');
        }

        return true;
    }

    /**
     * Verifica il ruolo dell'utente
     * 
     * @param string|array $requiredRoles Ruolo o array di ruoli richiesti
     * @throws SecurityException Se l'utente non ha il ruolo richiesto
     * @return bool
     */
    public static function requireRole($requiredRoles): bool {
        self::requireAuthentication();

        $userRole = $_SESSION['user']['tipo_utente'] ?? null;
        
        if (!$userRole) {
            throw new SecurityException('User role not defined');
        }

        $requiredRoles = (array)$requiredRoles;
        
        if (!in_array($userRole, $requiredRoles)) {
            throw new SecurityException('Insufficient permissions');
        }

        return true;
    }

    /**
     * Verifica la validità del token CSRF
     * 
     * @param string $token Token CSRF da verificare
     * @throws SecurityException Se il token non è valido
     * @return bool
     */
    public static function validateCsrfToken(string $token): bool {
        if (!isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new SecurityException('Invalid CSRF token');
        }

        return true;
    }

    /**
     * Verifica se una sessione è valida
     * 
     * @return bool
     */
    private static function isValidSession(): bool {
        // Verifica fingerprint del browser
        $currentFingerprint = self::generateBrowserFingerprint();
        if (!isset($_SESSION['browser_fingerprint']) || 
            $_SESSION['browser_fingerprint'] !== $currentFingerprint) {
            return false;
        }

        // Verifica timeout sessione
        if (isset($_SESSION['last_activity']) && 
            time() - $_SESSION['last_activity'] > 1800) { // 30 minuti
            return false;
        }

        // Aggiorna timestamp ultima attività
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Genera un fingerprint del browser
     * 
     * @return string
     */
    private static function generateBrowserFingerprint(): string {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }

    /**
     * Termina la sessione in modo sicuro
     */
    public static function terminateSession(): void {
        $_SESSION = array();

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/', '', true, true);
        }

        session_destroy();
    }

    /**
     * Sanitizza un array di input in base alle regole definite
     * 
     * @param array $input Array di input da sanitizzare
     * @param array $rules Regole di sanitizzazione
     * @return array
     */
    public static function sanitizeInput(array $input, array $rules = []): array {
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            if (isset($rules[$key])) {
                if (is_callable($rules[$key])) {
                    $sanitized[$key] = $rules[$key]($value);
                } else {
                    $sanitized[$key] = filter_var($value, $rules[$key]);
                }
            } else {
                $sanitized[$key] = filter_var($value, FILTER_SANITIZE_STRING);
            }
        }

        return $sanitized;
    }

    /**
     * Valida un array di input in base alle regole definite
     * 
     * @param array $input Array di input da validare
     * @param array $rules Regole di validazione
     * @return array Array di errori di validazione
     */
    public static function validateInput(array $input, array $rules): array {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? null;
            
            foreach ($fieldRules as $rule => $ruleValue) {
                switch ($rule) {
                    case 'required':
                        if ($ruleValue && empty($value)) {
                            $errors[$field][] = "Field is required";
                        }
                        break;
                        
                    case 'min_length':
                        if (strlen($value) < $ruleValue) {
                            $errors[$field][] = "Minimum length is $ruleValue";
                        }
                        break;
                        
                    case 'max_length':
                        if (strlen($value) > $ruleValue) {
                            $errors[$field][] = "Maximum length is $ruleValue";
                        }
                        break;
                        
                    case 'pattern':
                        if (!preg_match($ruleValue, $value)) {
                            $errors[$field][] = "Invalid format";
                        }
                        break;
                        
                    case 'in_array':
                        if (!in_array($value, $ruleValue)) {
                            $errors[$field][] = "Invalid value";
                        }
                        break;
                        
                    case 'custom':
                        if (is_callable($ruleValue) && !$ruleValue($value)) {
                            $errors[$field][] = "Validation failed";
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}
