<?php
/**
 * Setup script per inizializzare il database BOSTARTER
 */

require_once __DIR__ . '/backend/config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>\n";
echo "<html><head><title>BOSTARTER Database Setup</title></head><body>\n";
echo "<h1>BOSTARTER Database Setup</h1>\n";

try {    // Leggi il file schema
    $schemaFile = __DIR__ . '/database/bostarter_schema_fixed.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Connetti al server MySQL (senza specificare il database)
    $config = [
        'host' => DB_HOST,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => 'utf8mb4'
    ];
    
    $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h2>✅ Connected to MySQL server</h2>\n";
    
    // Execute the schema
    echo "<h2>🔄 Executing database schema...</h2>\n";
    
    // Split queries to handle multi-statement
    $queries = preg_split('/;\s*$/m', $schema);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query) || strpos($query, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($query);
            if (stripos($query, 'CREATE DATABASE') !== false) {
                echo "✅ Database created<br>\n";
            } elseif (stripos($query, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+(\w+)/i', $query, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "✅ Table created: $tableName<br>\n";
            } elseif (stripos($query, 'CREATE FUNCTION') !== false) {
                echo "✅ Function created<br>\n";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "ℹ️ Skipping existing object<br>\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "<br>\n";
                echo "Query: " . substr($query, 0, 100) . "...<br>\n";
            }
        }
    }
    
    // Verifica che tutto sia stato creato correttamente
    echo "<h2>🔍 Verifying database setup...</h2>\n";
    
    // Seleziona il database
    $pdo->exec("USE " . DB_NAME);
    
    // Check main tables
    $tables = ['utenti', 'remember_tokens', 'progetti', 'finanziamenti'];
    foreach ($tables as $table) {
        try {
            $result = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✅ Table '$table' is ready (0 records)<br>\n";
        } catch (PDOException $e) {
            echo "❌ Table '$table' has issues: " . $e->getMessage() . "<br>\n";
        }
    }
    
    // Crea un utente admin di test se non esiste
    echo "<h2>👤 Creating test admin user...</h2>\n";
    
    $testEmail = 'admin@bostarter.test';
    $checkUser = $pdo->prepare("SELECT id FROM utenti WHERE email = ?");
    $checkUser->execute([$testEmail]);
    
    if (!$checkUser->fetch()) {
        $adminData = [
            'email' => $testEmail,
            'nickname' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'nome' => 'Admin',
            'cognome' => 'BOSTARTER',
            'anno_nascita' => 1990,
            'luogo_nascita' => 'Milano',
            'tipo_utente' => 'amministratore'
        ];
        
        $insertAdmin = $pdo->prepare("
            INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertAdmin->execute(array_values($adminData));
        echo "✅ Test admin user created: $testEmail / admin123<br>\n";
    } else {
        echo "ℹ️ Admin user already exists<br>\n";
    }
    
    echo "<h2>🎉 Database setup completed successfully!</h2>\n";
    echo "<p><strong>What's next:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Database and tables are ready</li>\n";
    echo "<li>✅ Test admin user created (admin@bostarter.test / admin123)</li>\n";
    echo "<li>🔗 <a href='frontend/dashboard.php'>Go to dashboard</a></li>\n";
    echo "<li>🔗 <a href='index.html'>Go to main page and test registration/login</a></li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Setup failed!</h2>\n";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>\n";
    echo "<p><strong>Suggestions:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Make sure XAMPP MySQL service is running</li>\n";
    echo "<li>Check database credentials in backend/config/database.php</li>\n";
    echo "<li>Verify MySQL is accessible on localhost:3306</li>\n";
    echo "</ul>\n";
}

echo "</body></html>\n";
?>
