<?php

// BOSTARTER Configuration

// Database Configuration
define('DB_HOST', 'localhost');      // Host del database (es. '127.0.0.1' o 'localhost')
define('DB_NAME', 'bostarter');      // Nome del database
define('DB_USER', 'root');           // Utente del database
define('DB_PASS', '');               // Password del database
define('DB_CHARSET', 'utf8mb4');     // Set di caratteri

// Application Settings
define('APP_BASE_PATH', '/BOSTARTER'); // Percorso base dell'applicazione web
define('API_BASE_URL', APP_BASE_PATH . '/backend/index.php/api'); // URL base per le API

// Abilita/Disabilita modalità debug (mostra errori)
// Impostare a false in produzione!
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Altre configurazioni (es. chiavi API, impostazioni email, etc.)
// define('EMAIL_HOST', 'smtp.example.com');
// define('EMAIL_USER', 'user@example.com');
// define('EMAIL_PASS', 'password');

?>