<?php
/**
 * BOSTARTER Security Configuration
 * Gestisce tutte le impostazioni di sicurezza della piattaforma
 */

class SecurityConfig {
    // Configurazione per l'autenticazione degli utenti
    public const AUTH_CONFIG = [
        'max_login_attempts' => 5,                // Tentativi di login prima del blocco
        'lockout_time' => 900,                    // Tempo di blocco in secondi (15 minuti)
        'session_lifetime' => 1800,               // Durata sessione attiva (30 minuti)
        'remember_token_lifetime' => 2592000,     // Token "ricordami" (30 giorni)
        'csrf_token_lifetime' => 3600,            // Validità token CSRF (1 ora)
        'session_regenerate_interval' => 300,     // Rinnovo ID sessione (5 minuti)
        'password_min_length' => 8,               // Lunghezza minima password
        'require_special_chars' => true,          // Richiede caratteri speciali
        'require_uppercase' => true,              // Richiede lettere maiuscole
        'require_lowercase' => true,              // Richiede lettere minuscole
        'require_numbers' => true                 // Richiede numeri
    ];

    // Limiti per prevenire abusi e attacchi
    public const RATE_LIMITS = [
        'login' => [
            'max_attempts' => 5,                 // Massimo 5 tentativi di login
            'time_window' => 900                 // in 15 minuti
        ],
        'register' => [
            'max_attempts' => 3,                 // Massimo 3 registrazioni
            'time_window' => 1800                // in 30 minuti
        ],
        'api' => [
            'max_requests' => 100,               // Massimo 100 chiamate API
            'time_window' => 3600                // in 1 ora
        ],
        'password_reset' => [
            'max_attempts' => 3,                 // Massimo 3 reset password
            'time_window' => 3600                // in 1 ora
        ]
    ];

    // Impostazioni sicurezza per i cookie
    public const COOKIE_CONFIG = [
        'secure' => true,                        // Solo HTTPS in produzione
        'httponly' => true,                      // Non accessibili via JavaScript
        'samesite' => 'Strict',                  // Protezione CSRF aggiuntiva
        'path' => '/',                           // Validi per tutto il sito
        'domain' => '',                          // Dominio automatico
        'lifetime' => [
            'session' => 0,                       // Cookie di sessione temporaneo
            'remember' => 2592000                 // Cookie persistente (30 giorni)
        ]
    ];

    // Headers di sicurezza per il browser
    public const SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',                              
        'X-Frame-Options' => 'DENY',                                        
        'X-XSS-Protection' => '1; mode=block',                              
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains', 
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'", 
        'Referrer-Policy' => 'strict-origin-when-cross-origin',            
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()' 
    ];
    public const INPUT_VALIDATION = [
        'max_field_length' => 1000,                                      
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'], 
        'max_file_size' => 5 * 1024 * 1024,                              
        'sanitize_defaults' => [
            'text' => FILTER_SANITIZE_STRING,                             
            'email' => FILTER_SANITIZE_EMAIL,                             
            'url' => FILTER_SANITIZE_URL,                                 
            'number' => FILTER_SANITIZE_NUMBER_INT                        
        ],
        'allowed_html_tags' => '<p><br><b><i><strong><em><ul><ol><li><h1><h2><h3><h4><h5><a><img>' 
    ];
    public const DB_SECURITY = [
        'use_prepared_statements' => true,         
        'escape_all_inputs' => true,               
        'log_failed_queries' => true,              
        'bind_params_only' => true,                
        'connection_timeout' => 10,                
        'encrypt_sensitive_data' => true,          
        'sensitive_columns' => [                   
            'password_hash',
            'credit_card',
            'token',
            'secret_answer'
        ]
    ];
    public const ATTACK_PROTECTION = [
        'csrf' => [
            'enabled' => true,                     
            'token_name' => '_csrf_token',         
            'check_all_post' => true,              
            'check_referer' => true,               
            'allowed_domains' => []                
        ],
        'xss' => [
            'enabled' => true,                     
            'auto_sanitize' => true,               
            'escape_output' => true,               
            'content_type_options' => true         
        ],
        'sql_injection' => [
            'enabled' => true,                     
            'use_prepared' => true,                
            'log_attempts' => true                 
        ]
    ];
    public const LOGGING_CONFIG = [
        'log_security_events' => true,                
        'log_file' => __DIR__ . '/../../logs/security-events.log', 
        'log_level' => 'INFO',                        
        'max_log_size' => 5 * 1024 * 1024,            
        'rotate_logs' => true                         
    ];
    public static function setSecurityHeaders() {
        foreach (self::SECURITY_HEADERS as $header => $value) {
            header("$header: $value");
        }
    }
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
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    public static function isWhitelistedIP($ip) {
        $whitelist = [
            '127.0.0.1',      
            '::1',            
        ];
        return in_array($ip, $whitelist, true);
    }
    public static function isBlacklistedIP($ip) {
        $blacklist = [
        ];
        return in_array($ip, $blacklist, true);
    }
    public static function sanitizeInput($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitizeInput'], $value);
        }
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }
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
    public static function setSecureCookie(string $name, string $value, int $expire = 0, array $options = []): bool {
        $defaultOptions = [
            'expires' => $expire,
            'path' => self::COOKIE_CONFIG['path'],
            'domain' => self::COOKIE_CONFIG['domain'],
            'secure' => self::COOKIE_CONFIG['secure'] && self::isHTTPS(),
            'httponly' => self::COOKIE_CONFIG['httponly'],
            'samesite' => self::COOKIE_CONFIG['samesite']
        ];
        $options = array_merge($defaultOptions, $options);
        return setcookie($name, $value, $options);
    }
    public static function deleteCookie(string $name): bool {
        if (isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
            return self::setSecureCookie($name, '', time() - 3600);
        }
        return true;
    }
}
