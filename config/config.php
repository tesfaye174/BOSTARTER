<?php

// BOSTARTER Configuration

// MySQL Database Configuration
define('DB_HOST', 'localhost');      // Database host (e.g. '127.0.0.1' or 'localhost')
define('DB_NAME', 'bostarter');      // Database name
define('DB_USER', 'root');           // Database user
define('DB_PASS', '');               // Database password
define('DB_CHARSET', 'utf8mb4');     // Character set
define('DB_COLLATION', 'utf8mb4_unicode_ci'); // Collation

// MongoDB Configuration for Event Logging
define('MONGO_HOST', 'localhost');    // MongoDB host
define('MONGO_PORT', '27017');       // MongoDB port
define('MONGO_DB', 'bostarter_logs'); // MongoDB database name
define('MONGO_COLLECTION', 'events'); // MongoDB collection for event logs

// Application Settings
define('APP_NAME', 'BOSTARTER');
define('APP_BASE_PATH', '/BOSTARTER'); // Web application base path
define('APP_URL', 'http://localhost:8000');
define('API_BASE_URL', APP_BASE_PATH . '/backend/index.php/api'); // API base URL
define('APP_TIMEZONE', 'Europe/Rome');
define('APP_LOCALE', 'it');
define('APP_KEY', 'base64:' . base64_encode(random_bytes(32)));

// Debug Settings
define('DEBUG_MODE', true); // Set to false in production!

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');


?>