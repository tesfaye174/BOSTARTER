<?php
/**
 * BOSTARTER - Configurazioni Applicazione
 */

// Database MySQL
define("DB_HOST", "localhost");
define("DB_NAME", "bostarter_italiano");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_PORT", "3306");

// Sicurezza e Autenticazione
define("JWT_SECRET", "bostarter_secret_key_2025_ultra_sicura");
define("SESSION_LIFETIME", 3600);

/** Costo di hashing per bcrypt (più alto = più sicuro ma più lento) */
define("BCRYPT_COST", 12);

/** Numero massimo di tentativi di login falliti */
define("MAX_LOGIN_ATTEMPTS", 5);

/** Tempo di blocco account dopo tentativi falliti (15 minuti) */
define("LOCKOUT_TIME", 900);

// Gestione file upload
define("UPLOAD_MAX_SIZE", 5 * 1024 * 1024); // Limite di 5MB per file
define("UPLOAD_ALLOWED_TYPES", ["jpg", "jpeg", "png", "gif", "pdf"]);
define("UPLOAD_DIR", __DIR__ . "/../../uploads/");

// Configurazione email
define("SMTP_HOST", "localhost");
define("SMTP_PORT", 587);
define("SMTP_USER", "");
define("SMTP_PASS", "");
define("SMTP_FROM_EMAIL", "noreply@bostarter.local");
define("SMTP_FROM_NAME", "BOSTARTER");

// Sistema di cache
define("CACHE_ENABLED", true);
define("CACHE_DEFAULT_TTL", 3600); // Cache valida per un'ora
define("CACHE_DIR", __DIR__ . "/../../cache/");

// Modalità di funzionamento
define("DEBUG_MODE", false); // Disabilitato in produzione per sicurezza
define("ERROR_REPORTING_LEVEL", E_ALL);
define("LOG_LEVEL", "INFO"); // DEBUG, INFO, WARNING, ERROR
define("APP_ENV", "production"); // development, production
define("ERROR_LOG_FILE", getAppRoot() . '/logs/errors.log');

// Monitoraggio prestazioni
define("PERFORMANCE_MONITORING", true);
define("SLOW_QUERY_THRESHOLD", 0.1); // Soglia per query lente: 100ms
define("MEMORY_LIMIT_WARNING", "128M");

// Regole business per i progetti
define("MIN_FUNDING_GOAL", 100); // Obiettivo minimo in Euro
define("MAX_FUNDING_GOAL", 1000000); // Euro
define("MAX_PROJECT_DURATION", 90); // giorni
define("COMMISSION_RATE", 0.05); // 5%

define("API_RATE_LIMIT", 100); // richieste per ora
define("API_VERSION", "v1");

date_default_timezone_set("Europe/Rome");

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(ERROR_REPORTING_LEVEL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}
function isDebugMode(): bool {
    return defined('DEBUG_MODE') && DEBUG_MODE === true;
}
function getAppRoot(): string {
    return dirname(__DIR__, 2);
}
function getCacheDir(): string {
    return CACHE_DIR;
}
function getUploadDir(): string {
    return UPLOAD_DIR;
}

function logMessage($level, $message, $context = []) {
    // Non logghiamo i dettagli di debug in produzione
    if (!isDebugMode() && $level === 'DEBUG') {
        return;
    }
    
    // Rendiamo i log più leggibili per gli sviluppatori
    $levelNames = [
        'DEBUG' => 'Debug',
        'INFO' => 'Info',
        'WARNING' => 'Attenzione',
        'ERROR' => 'Errore'
    ];
    
    $readableLevel = $levelNames[strtoupper($level)] ?? $level;
    
    $logEntry = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        $readableLevel,
        $message,
        !empty($context) ? '(' . json_encode($context, JSON_UNESCAPED_UNICODE) . ')' : ''
    );
    
    error_log($logEntry, 3, getAppRoot() . '/logs/application.log');
}
?>