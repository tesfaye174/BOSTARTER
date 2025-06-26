<?php
/**
 * Configurazione principale BOSTARTER
 * 
 * Questo file gestisce tutte le impostazioni fondamentali dell'applicazione:
 * - Connessione al database
 * - URL e parametri dell'applicazione
 * - Configurazioni email
 * - Opzioni di sicurezza
 * 
 * Carica le configurazioni da variabili d'ambiente per maggiore sicurezza
 * e supporta il pattern di sviluppo tramite file .env
 * 
 * @author BOSTARTER Team
 * @version 2.1.0
 */

// Carica le variabili d'ambiente se esiste il file .env
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Salta i commenti che iniziano con #
        if (strpos(trim($line), '#') === 0) continue;
        
        // Estrai le coppie chiave=valore dal file .env
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Previene la ridefinizione delle costanti
if (!defined('CONFIG_LOADED')) {
    // Configurazione Database - usa variabili d'ambiente se disponibili
    // altrimenti utilizza valori predefiniti sicuri per lo sviluppo
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'bostarter_compliant');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    define('DB_CHARSET', 'utf8mb4');
    
    // Configurazione Applicazione
    define('APP_NAME', 'BOSTARTER');
    define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/BOSTARTER');
    define('APP_VERSION', $_ENV['APP_VERSION'] ?? '2.1.0');
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
    define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));

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

    // Configurazione Sicurezza - Parametri essenziali per la protezione dei dati
    define('HASH_COST', 12);                                                  // Costo dell'algoritmo di hashing per le password
    define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'change-this-in-production-to-32-chars');   // Chiave per i token JWT
    define('SESSION_LIFETIME', 3600);                                         // Durata sessione in secondi (1 ora)
    define('TOKEN_LIFETIME', 86400);                                          // Durata token in secondi (24 ore)
    define('SESSION_SECRET', $_ENV['SESSION_SECRET'] ?? 'change-this-session-secret-key');  // Chiave protezione sessioni
    define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'change-this-encryption-key-32ch'); // Chiave cifratura dati sensibili

    // Configurazione MongoDB - Per logging e analisi dati avanzati
    define('MONGO_URI', $_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
    define('MONGO_DB', $_ENV['MONGO_DB'] ?? 'bostarter');
    define('MONGO_COLLECTION', $_ENV['MONGO_COLLECTION'] ?? 'logs');

    // Configurazione Upload - Gestione sicura dei file caricati dagli utenti
    define('UPLOAD_DIR', __DIR__ . '/../../uploads');                         // Percorso cartella upload
    define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? 5 * 1024 * 1024);       // Dimensione massima file (5MB)
    define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);     // Formati immagine consentiti

    // Configurazione Performance - Ottimizzazione caricamento
    define('CACHE_ENABLED', filter_var($_ENV['CACHE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN));
    define('CACHE_TTL', $_ENV['CACHE_TTL'] ?? 3600);                          // Durata cache (1 ora)

    // Configurazione Progetti - Regole business per i progetti
    define('MIN_PROJECT_GOAL', 1000);                                         // Minimo obiettivo finanziamento in € 
    define('MAX_PROJECT_DURATION', 90);                                       // Durata massima progetto in giorni
    define('PLATFORM_FEE', 5);                                                // Commissione piattaforma in percentuale

    // Segna che la configurazione è stata caricata
    define('CONFIG_LOADED', true);
}
?>