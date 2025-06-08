<?php
/**
 * Configurazione di sicurezza centralizzata per BOSTARTER
 * Definisce tutte le impostazioni di sicurezza in un unico posto
 */

class SecurityConfig {
    
    // =============== AUTENTICAZIONE ===============
    public const AUTH_CONFIG = [
        'max_login_attempts' => 5,
        'lockout_time' => 900, // 15 minuti
        'session_lifetime' => 1800, // 30 minuti
        'remember_token_lifetime' => 2592000, // 30 giorni
        'csrf_token_lifetime' => 3600, // 1 ora
        'session_regenerate_interval' => 300, // 5 minuti
        'password_min_length' => 8,
        'require_special_chars' => true,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true
    ];
    
    // =============== RATE LIMITING ===============
    public const RATE_LIMITS = [
        'login' => [
            'max_attempts' => 5,
            'time_window' => 900 // 15 minuti
        ],
        'register' => [
            'max_attempts' => 3,
            'time_window' => 1800 // 30 minuti
        ],
        'api' => [
            'max_requests' => 100,
            'time_window' => 3600 // 1 ora
        ],
        'password_reset' => [
            'max_attempts' => 3,
            'time_window' => 3600 // 1 ora
        ]
    ];
    
    // =============== COOKIE SECURITY ===============
    public const COOKIE_CONFIG = [
        'secure' => true, // Solo HTTPS in produzione
        'httponly' => true,
        'samesite' => 'Strict',
        'path' => '/',
        'domain' => '', // Impostare in base al dominio
        'lifetime' => [
            'session' => 0, // Session cookie
            'remember' => 2592000 // 30 giorni
        ]
    ];
    
    // =============== HEADERS DI SICUREZZA ===============
    public const SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
    ];
    
    // =============== VALIDAZIONE INPUT ===============
    public const INPUT_VALIDATION = [
        'max_field_length' => 1000,
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'max_file_size' => 5242880, // 5MB
        'sanitize_html' => true,
        'strip_tags' => ['script', 'iframe', 'object', 'embed', 'form']
    ];
    
    // =============== LOGGING E MONITORING ===============
    public const LOGGING_CONFIG = [
        'log_failed_logins' => true,
        'log_successful_logins' => true,
        'log_admin_actions' => true,
        'log_file_uploads' => true,
        'log_database_errors' => true,
        'log_security_events' => true,
        'retention_days' => 90,
        'alert_on_multiple_failures' => true,
        'alert_threshold' => 10
    ];
    
    // =============== IP BLOCKING ===============
    public const IP_BLOCKING = [
        'enable_auto_block' => true,
        'block_duration' => 3600, // 1 ora
        'whitelist' => [
            '127.0.0.1',
            '::1'
        ],
        'blacklist' => []
    ];
      /**
     * Applica gli headers di sicurezza
     */
    public static function setSecurityHeaders(): void {
        // Verifica che gli headers non siano già stati inviati
        if (!headers_sent()) {
            foreach (self::SECURITY_HEADERS as $header => $value) {
                header("{$header}: {$value}");
            }
        }
    }
      /**
     * Configura le impostazioni di sessione sicure
     */
    public static function configureSecureSession(): void {
        // Solo impostare le configurazioni se la sessione non è già attiva
        if (session_status() === PHP_SESSION_NONE) {
            // Impostazioni di sicurezza della sessione
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', self::isHTTPS() ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', self::AUTH_CONFIG['session_lifetime']);
            ini_set('session.cookie_lifetime', 0);
            
            // Nome di sessione personalizzato
            session_name('BOSTARTER_SESSION');
        }
    }
      /**
     * Verifica se la connessione è HTTPS
     */
    public static function isHTTPS(): bool {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }
    
    /**
     * Sanitizza input in modo sicuro
     */
    public static function sanitizeInput($input, array $options = []): string {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input, array_fill(0, count($input), $options));
        }
        
        $sanitized = trim($input);
        
        // Rimuovi tag pericolosi
        if ($options['strip_dangerous_tags'] ?? true) {
            foreach (self::INPUT_VALIDATION['strip_tags'] as $tag) {
                $sanitized = preg_replace('/<\s*' . $tag . '.*?>/i', '', $sanitized);
            }
        }
        
        // Limite lunghezza
        $maxLength = $options['max_length'] ?? self::INPUT_VALIDATION['max_field_length'];
        if (strlen($sanitized) > $maxLength) {
            $sanitized = substr($sanitized, 0, $maxLength);
        }
        
        // HTML entities
        if ($options['html_entities'] ?? true) {
            $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        }
        
        return $sanitized;
    }
    
    /**
     * Genera token sicuro
     */
    public static function generateSecureToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Verifica se un IP è nella whitelist
     */
    public static function isWhitelistedIP(string $ip): bool {
        return in_array($ip, self::IP_BLOCKING['whitelist']);
    }
    
    /**
     * Verifica se un IP è nella blacklist
     */
    public static function isBlacklistedIP(string $ip): bool {
        return in_array($ip, self::IP_BLOCKING['blacklist']);
    }
    
    /**
     * Ottiene l'IP reale del client
     */
    public static function getRealClientIP(): string {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Valida la forza della password
     */
    public static function validatePasswordStrength(string $password): array {
        $errors = [];
        $config = self::AUTH_CONFIG;
        
        if (strlen($password) < $config['password_min_length']) {
            $errors[] = "La password deve essere di almeno {$config['password_min_length']} caratteri";
        }
        
        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La password deve contenere almeno una lettera maiuscola';
        }
        
        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'La password deve contenere almeno una lettera minuscola';
        }
        
        if ($config['require_numbers'] && !preg_match('/\d/', $password)) {
            $errors[] = 'La password deve contenere almeno un numero';
        }
        
        if ($config['require_special_chars'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'La password deve contenere almeno un carattere speciale';
        }
        
        // Controlla password comuni
        $commonPasswords = ['password', '123456', 'qwerty', 'admin', 'letmein'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = 'Password troppo comune, scegline una più sicura';
        }
        
        return $errors;
    }
}
