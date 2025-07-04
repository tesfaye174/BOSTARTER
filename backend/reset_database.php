<?php
/**
 * BOSTARTER Database Reset
 * Resetta completamente il database
 */

require_once __DIR__ . '/config/database.php';

echo "🗑️  Resettando database BOSTARTER...\n";

// Carica configurazione ambiente
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    $dbName = $_ENV['DB_NAME'] ?? 'bostarter';
    
    // Connessione temporanea senza database specificato
    $db = new PDO(
        "mysql:host=" . ($_ENV['DB_HOST'] ?? 'localhost') . ";charset=utf8mb4",
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Elimina il database esistente
    $db->exec("DROP DATABASE IF EXISTS `$dbName`");
    echo "💥 Database esistente eliminato\n";
    
    // Ricrea il database
    $db->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "🆕 Database ricreato\n";
    
    echo "✅ Reset completato! Ora puoi eseguire le migrazioni.\n";
    
} catch (Exception $e) {
    echo "❌ Errore durante il reset: " . $e->getMessage() . "\n";
    exit(1);
}
