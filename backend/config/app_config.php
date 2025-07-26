<?php
/**
 * Configurazione Centralizzata BOSTARTER
 * 
 * File unico per tutte le configurazioni del progetto
 */
// === CONFIGURAZIONE DATABASE ===
define("DB_HOST", "localhost");
define("DB_NAME", "bostarter");
define("DB_USER", "root");
define("DB_PASS", "");
// === CONFIGURAZIONE MONGODB ===
define("MONGO_HOST", "localhost");
define("MONGO_PORT", 27017);
define("MONGO_DB", "bostarter_logs");
define("MONGO_COLLECTION", "eventi");
// === CONFIGURAZIONE SICUREZZA ===
define("JWT_SECRET", "bostarter_secret_key_2025");
define("SESSION_LIFETIME", 3600); // 1 ora
define("BCRYPT_COST", 12);
define("MAX_LOGIN_ATTEMPTS", 5);
define("LOCKOUT_TIME", 900); // 15 minuti
// === CONFIGURAZIONE FILE ===
define("UPLOAD_MAX_SIZE", 5 * 1024 * 1024); // 5MB
define("UPLOAD_ALLOWED_TYPES", ["jpg", "jpeg", "png", "gif", "pdf"]);
define("UPLOAD_DIR", __DIR__ . "/../../uploads/");
// === CONFIGURAZIONE EMAIL ===
define("SMTP_HOST", "localhost");
define("SMTP_PORT", 587);
define("SMTP_USER", "");
define("SMTP_PASS", "");
define("SMTP_FROM_EMAIL", "noreply@bostarter.local");
define("SMTP_FROM_NAME", "BOSTARTER");
// === CONFIGURAZIONE CACHE ===
define("CACHE_ENABLED", true);
define("CACHE_DEFAULT_TTL", 3600); // 1 ora
define("CACHE_DIR", __DIR__ . "/../../cache/");
// === CONFIGURAZIONE DEBUG ===
define("DEBUG_MODE", true);
define("ERROR_REPORTING_LEVEL", E_ALL);
define("LOG_LEVEL", "INFO"); // DEBUG, INFO, WARNING, ERROR
define("APP_ENV", "development"); // development, production
define("ERROR_LOG_FILE", getAppRoot() . '/logs/errors.log');
// === CONFIGURAZIONE PRESTAZIONI ===
define("PERFORMANCE_MONITORING", true);
define("SLOW_QUERY_THRESHOLD", 0.1); // 100ms
define("MEMORY_LIMIT_WARNING", "128M");
// === CONFIGURAZIONE BUSINESS ===
define("MIN_FUNDING_GOAL", 100); // Euro
define("MAX_FUNDING_GOAL", 1000000); // Euro
define("MAX_PROJECT_DURATION", 90); // giorni
define("COMMISSION_RATE", 0.05); // 5%
// === CONFIGURAZIONE API ===
define("API_RATE_LIMIT", 100); // richieste per ora
define("API_VERSION", "v1");
// === TIMEZONE ===
date_default_timezone_set("Europe/Rome");
// === INIZIALIZZAZIONE ERRORI ===
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(ERROR_REPORTING_LEVEL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
// === HEADERS DI SICUREZZA ===
if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}
// === FUNZIONI UTILIT� ===
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
// === LOG HELPER ===
function logMessage($level, $message, $context = []) {
    if (!isDebugMode() && $level === 'DEBUG') {
        return;
    }
    $logEntry = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    error_log($logEntry, 3, getAppRoot() . '/logs/application.log');
}
?>
