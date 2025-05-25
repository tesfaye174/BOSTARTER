<?php
/**
 * Test script per verificare la connessione al database BOSTARTER
 * Questo script aiuta a diagnosticare problemi di connessione al database
 */

// Include la configurazione
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>Test di Connessione Database BOSTARTER</h2>";
echo "<hr>";

// Mostra le impostazioni di connessione (senza password)
echo "<h3>Impostazioni di Connessione:</h3>";
echo "<ul>";
echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
echo "<li><strong>Database:</strong> " . DB_NAME . "</li>";
echo "<li><strong>Username:</strong> " . DB_USER . "</li>";
echo "<li><strong>Password:</strong> " . (empty(DB_PASS) ? "(vuota)" : "(impostata)") . "</li>";
echo "<li><strong>Charset:</strong> " . DB_CHARSET . "</li>";
echo "</ul>";
echo "<hr>";

// Test 1: Connessione al server MySQL senza specificare il database
echo "<h3>Test 1: Connessione al Server MySQL</h3>";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Connessione al server MySQL riuscita!</p>";
    
    // Verifica la versione di MySQL
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "<p><strong>Versione MySQL:</strong> $version</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Errore di connessione al server MySQL:</p>";
    echo "<p style='color: red; font-family: monospace;'>" . $e->getMessage() . "</p>";
    echo "<p><strong>Possibili soluzioni:</strong></p>";
    echo "<ul>";
    echo "<li>Verificare che XAMPP sia avviato</li>";
    echo "<li>Verificare che il servizio MySQL sia attivo</li>";
    echo "<li>Controllare le credenziali in config/config.php</li>";
    echo "</ul>";
    exit;
}

echo "<hr>";

// Test 2: Verifica esistenza del database
echo "<h3>Test 2: Verifica Database</h3>";
try {
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([DB_NAME]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Database '" . DB_NAME . "' esiste!</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Database '" . DB_NAME . "' non esiste.</p>";
        echo "<p><strong>Soluzione:</strong> Creare il database eseguendo:</p>";
        echo "<p style='font-family: monospace; background: #f0f0f0; padding: 10px;'>";
        echo "CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        echo "</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Errore nella verifica del database: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 3: Connessione al database specifico
echo "<h3>Test 3: Connessione al Database Specifico</h3>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p style='color: green;'>✓ Connessione al database '" . DB_NAME . "' riuscita!</p>";
    
    // Verifica le tabelle esistenti
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<p><strong>Tabelle trovate (" . count($tables) . "):</strong></p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ Nessuna tabella trovata nel database.</p>";
        echo "<p><strong>Soluzione:</strong> Eseguire lo script di inizializzazione del database.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Errore di connessione al database:</p>";
    echo "<p style='color: red; font-family: monospace;'>" . $e->getMessage() . "</p>";
    
    // Analizza il tipo di errore
    if (strpos($e->getMessage(), '1698') !== false || strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<h4 style='color: red;'>Errore di Autenticazione MySQL (SQLSTATE[HY000] [1698])</h4>";
        echo "<p>Questo errore indica un problema con l'autenticazione dell'utente 'root' in MySQL.</p>";
        echo "<p><strong>Soluzioni raccomandate:</strong></p>";
        echo "<ol>";
        echo "<li><strong>Opzione 1 - phpMyAdmin (Raccomandato):</strong>";
        echo "<ul>";
        echo "<li>Accedi a <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
        echo "<li>Vai su 'Account utente' → 'root' → 'Modifica privilegi'</li>";
        echo "<li>Cambia il plugin di autenticazione da 'auth_socket' a 'mysql_native_password'</li>";
        echo "<li>Imposta una password se necessario</li>";
        echo "</ul>";
        echo "</li>";
        echo "<li><strong>Opzione 2 - Crea nuovo utente:</strong>";
        echo "<ul>";
        echo "<li>Crea un nuovo utente MySQL dedicato per BOSTARTER</li>";
        echo "<li>Aggiorna le credenziali in config/config.php</li>";
        echo "</ul>";
        echo "</li>";
        echo "<li><strong>Opzione 3 - Reset XAMPP:</strong>";
        echo "<ul>";
        echo "<li>Ferma MySQL in XAMPP</li>";
        echo "<li>Vai in XAMPP Control Panel → MySQL → Config → Reset Root Password</li>";
        echo "<li>Riavvia MySQL</li>";
        echo "</ul>";
        echo "</li>";
        echo "</ol>";
    }
}

echo "<hr>";
echo "<h3>Prossimi Passi</h3>";
echo "<p>Una volta risolto il problema di connessione:</p>";
echo "<ol>";
echo "<li>Esegui <a href='database/init_db.php'>database/init_db.php</a> per inizializzare il database</li>";
echo "<li>Testa l'applicazione principale su <a href='index.html'>index.html</a></li>";
echo "<li>Verifica le funzionalità di autenticazione</li>";
echo "</ol>";

echo "<hr>";
echo "<p><em>Script eseguito il: " . date('Y-m-d H:i:s') . "</em></p>";
?>