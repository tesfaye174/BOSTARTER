<?php
/**
 * Configurazione centralizzata per BOSTARTER
 * Questo file contiene tutte le impostazioni di configurazione per l'applicazione
 */

// Avvia la sessione se non è già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurazione del database
define('DB_HOST', 'localhost');
define('DB_NAME', 'bostarter');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurazione dell'applicazione
define('APP_NAME', 'BOSTARTER');
define('APP_URL', 'http://localhost/BOSTARTER');
define('FRONTEND_URL', 'http://localhost:8000');
define('API_URL', 'http://localhost:8000/api');

// Configurazione JWT
define('JWT_SECRET', 'your-secret-key-here-change-in-production');
define('JWT_ALGORITHM', 'HS256');

// Configurazione della sicurezza
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 24); // 24 ore in secondi
define('PASSWORD_MIN_LENGTH', 8);

// Configurazione dei percorsi
define('BASE_PATH', __DIR__ . '/..');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Configurazione del logging
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', BASE_PATH . '/logs/error.log');

// Configurazione email (per notifiche)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// Impostazioni del fuso orario
date_default_timezone_set('Europe/Rome');

// Configurazione della codifica dei caratteri
mb_internal_encoding('UTF-8');

// Configurazione delle intestazioni di sicurezza
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Abilita CORS per development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Funzioni di utilità per la sicurezza

/**
 * Sanitizza l'input per prevenire XSS
 * @param string $data Input da sanitizzare
 * @return string Input sanitizzato
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Genera un token CSRF
 * @return string Token CSRF generato
 */
function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verifica un token CSRF
 * @param string $token Token da verificare
 * @return bool True se il token è valido, false altrimenti
 */
function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Registra un errore nel file di log
 * @param string $message Messaggio di errore
 * @param array $context Contesto dell'errore (opzionale)
 * @return bool True se il log è stato scritto, false altrimenti
 */
function log_error($message, $context = []) {
    if (!LOG_ERRORS) {
        return false;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logMessage = "[$timestamp] $message$contextStr" . PHP_EOL;
    
    // Crea la directory dei log se non esiste
    $logDir = dirname(ERROR_LOG_PATH);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    return file_put_contents(ERROR_LOG_PATH, $logMessage, FILE_APPEND) !== false;
}
?>