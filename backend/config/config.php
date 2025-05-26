<?php
// Configurazione Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'bostarter');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurazione Applicazione
define('APP_NAME', 'BOSTARTER');
define('APP_URL', 'http://localhost/BOSTARTER');
define('APP_VERSION', '1.0.0');

// Configurazione Email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@bostarter.it');
define('SMTP_FROM_NAME', 'BOSTARTER');

// Configurazione Upload
define('UPLOAD_DIR', __DIR__ . '/../../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Configurazione Sessione
define('SESSION_LIFETIME', 3600); // 1 ora
define('SESSION_NAME', 'bostarter_session');
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false);
define('SESSION_HTTP_ONLY', true);

// Configurazione Sicurezza
define('HASH_COST', 12); // Per password_hash()
define('TOKEN_LIFETIME', 3600); // 1 ora
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minuti

// Configurazione Progetti
define('MIN_PROJECT_GOAL', 1000); // €1000
define('MAX_PROJECT_DURATION', 90); // 90 giorni
define('PLATFORM_FEE', 5); // 5%

// Configurazione Cache
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../../cache');
define('CACHE_LIFETIME', 3600); // 1 ora

// Configurazione Log
define('LOG_DIR', __DIR__ . '/../../logs');
define('LOG_LEVEL', 'debug'); // debug, info, warning, error

// Configurazione API
define('API_VERSION', 'v1');
define('API_KEY', 'your-api-key');
define('API_RATE_LIMIT', 100); // richieste per ora

// Configurazione Social
define('FACEBOOK_APP_ID', '');
define('FACEBOOK_APP_SECRET', '');
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');

// Configurazione Pagamenti
define('STRIPE_PUBLIC_KEY', '');
define('STRIPE_SECRET_KEY', '');
define('STRIPE_WEBHOOK_SECRET', '');

// Configurazione Ambiente
define('ENVIRONMENT', 'development'); // development, production
define('DEBUG_MODE', true);

// Configurazione Timezone
date_default_timezone_set('Europe/Rome');

// Configurazione Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurazione Autoload
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
}); 