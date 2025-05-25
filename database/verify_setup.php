<?php
/**
 * Script di verifica completa per BOSTARTER
 * Verifica la connessione al database e lo stato dell'applicazione
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h1>Verifica Setup BOSTARTER</h1>";
echo "<hr>";

$allGood = true;

// Test 1: Connessione Database
echo "<h3>1. Test Connessione Database</h3>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p style='color: green;'>✓ Connessione al database riuscita</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Errore di connessione: " . $e->getMessage() . "</p>";
    echo "<p><a href='test_db_connection.php'>→ Esegui test dettagliato della connessione</a></p>";
    $allGood = false;
}

// Test 2: Verifica Tabelle
if ($allGood) {
    echo "<h3>2. Verifica Tabelle Database</h3>";
    try {
        $requiredTables = ['users', 'projects', 'categories'];
        $stmt = $conn->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredTables as $table) {
            if (in_array($table, $existingTables)) {
                echo "<p style='color: green;'>✓ Tabella '$table' presente</p>";
            } else {
                echo "<p style='color: red;'>✗ Tabella '$table' mancante</p>";
                $allGood = false;
            }
        }
        
        if (!$allGood) {
            echo "<p><a href='database/init_db.php'>→ Inizializza il database</a></p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Errore nella verifica delle tabelle: " . $e->getMessage() . "</p>";
        $allGood = false;
    }
}

// Test 3: Verifica File di Configurazione
echo "<h3>3. Verifica File di Configurazione</h3>";
$configFiles = [
    'config/config.php' => 'Configurazione principale',
    'config/database.php' => 'Configurazione database',
    'backend/auth_api.php' => 'API di autenticazione',
    'frontend/js/config.js' => 'Configurazione frontend'
];

foreach ($configFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p style='color: green;'>✓ $description ($file)</p>";
    } else {
        echo "<p style='color: red;'>✗ $description mancante ($file)</p>";
        $allGood = false;
    }
}

// Test 4: Test API di Base
if ($allGood) {
    echo "<h3>4. Test API di Base</h3>";
    
    // Test endpoint di stato
    $apiUrl = APP_URL . '/backend/auth_api.php';
    echo "<p>Testando: $apiUrl</p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 5
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        echo "<p style='color: green;'>✓ API raggiungibile</p>";
    } else {
        echo "<p style='color: orange;'>⚠ API non raggiungibile (normale se il server web non è avviato)</p>";
    }
}

// Test 5: Verifica Permessi Directory
echo "<h3>5. Verifica Permessi Directory</h3>";
$directories = [
    'logs' => 'Directory log',
    'uploads' => 'Directory upload'
];

foreach ($directories as $dir => $description) {
    $fullPath = __DIR__ . '/' . $dir;
    
    if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "<p style='color: green;'>✓ $description creata ($dir)</p>";
        } else {
            echo "<p style='color: red;'>✗ Impossibile creare $description ($dir)</p>";
            $allGood = false;
        }
    } else {
        if (is_writable($fullPath)) {
            echo "<p style='color: green;'>✓ $description scrivibile ($dir)</p>";
        } else {
            echo "<p style='color: orange;'>⚠ $description non scrivibile ($dir)</p>";
        }
    }
}

echo "<hr>";

// Risultato finale
if ($allGood) {
    echo "<h2 style='color: green;'>🎉 Setup Completato con Successo!</h2>";
    echo "<p>L'applicazione BOSTARTER è pronta per l'uso.</p>";
    echo "<h3>Prossimi Passi:</h3>";
    echo "<ol>";
    echo "<li><a href='index.html'>Apri l'applicazione principale</a></li>";
    echo "<li>Testa la registrazione di un nuovo utente</li>";
    echo "<li>Verifica il login</li>";
    echo "<li>Esplora le funzionalità dei progetti</li>";
    echo "</ol>";
} else {
    echo "<h2 style='color: red;'>⚠ Setup Incompleto</h2>";
    echo "<p>Alcuni problemi devono essere risolti prima di utilizzare l'applicazione.</p>";
    echo "<h3>Azioni Raccomandate:</h3>";
    echo "<ol>";
    echo "<li><a href='test_db_connection.php'>Esegui test dettagliato del database</a></li>";
    echo "<li>Risolvi i problemi di connessione al database</li>";
    echo "<li><a href='database/init_db.php'>Inizializza il database</a></li>";
    echo "<li>Riesegui questo script di verifica</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<h3>Link Utili</h3>";
echo "<ul>";
echo "<li><a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
echo "<li><a href='test_db_connection.php'>Test Connessione Database</a></li>";
echo "<li><a href='database/init_db.php'>Inizializzazione Database</a></li>";
echo "<li><a href='index.html'>Applicazione BOSTARTER</a></li>";
echo "</ul>";

echo "<p><em>Verifica eseguita il: " . date('Y-m-d H:i:s') . "</em></p>";
?>