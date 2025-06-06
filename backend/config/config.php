<?php
// Previene la ridefinizione delle costanti
if (!defined('CONFIG_LOADED')) {

    // Configurazione Database
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'bostarter_compliant');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');

    // Configurazione Applicazione
    define('APP_NAME', 'BOSTARTER');
    define('APP_URL', 'http://localhost/BOSTARTER');
    define('APP_VERSION', '1.0.0');

    // Configurazione Email
    define('EMAIL_CONFIG', [
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'secure' => 'tls',
            'auth' => true,
            'username' => 'your-email@gmail.com',
            'password' => 'your-app-password',
            'from' => [
                'email' => 'noreply@bostarter.it',
                'name' => 'BOSTARTER'
            ]
        ],
        'templates' => [
            'base_path' => __DIR__ . '/../../templates/email/',
            'welcome' => 'welcome.html',
            'reset_password' => 'reset_password.html',
            'project_approved' => 'project_approved.html',
            'funding_received' => 'funding_received.html'
        ],
        'queue' => [
            'enabled' => true,
            'path' => __DIR__ . '/../../storage/email_queue',
            'batch_size' => 50,
            'retry_attempts' => 3,
            'retry_delay' => 300 // 5 minuti
        ],
        'debug' => false
    ]);

    // Configurazione Sicurezza
    define('HASH_COST', 12);
    define('JWT_SECRET', 'your-secret-key');
    define('SESSION_LIFETIME', 3600);
    define('TOKEN_LIFETIME', 86400);

    // Configurazione MongoDB
    define('MONGO_URI', 'mongodb://localhost:27017');
    define('MONGO_DB', 'bostarter');
    define('MONGO_COLLECTION', 'logs');

    // Configurazione Upload
    define('UPLOAD_DIR', __DIR__ . '/../../uploads');
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
    define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

    // Configurazione Progetti
    define('MIN_PROJECT_GOAL', 1000); // €1000
    define('MAX_PROJECT_DURATION', 90); // 90 giorni
    define('PLATFORM_FEE', 5); // 5%

    define('CONFIG_LOADED', true);
}