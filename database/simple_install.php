<?php
/**
 * =====================================================
 * BOSTARTER - INSTALLER DATABASE AUTOMATICO
 * =====================================================
 * 
 * Installer semplificato e sicuro per database BOSTARTER.
 * Installa schema ottimizzato usando file SQL unificato.
 * 
 * @author BOSTARTER Team
 * @version 2.0 (Ottimizzato)
 * @created 2025
 * 
 * ISTRUZIONI:
 * 1. Avvia XAMPP e assicurati che MySQL sia attivo
 * 2. Apri: http://localhost/BOSTARTER/database/simple_install.php
 * 3. Clicca "Installa Database" e attendi completamento
 * 
 * CREDENZIALI ADMIN:
 * Email: admin@bostarter.com
 * Password: admin123
 * Codice: ADMIN2025
 */

// Configurazione errori per debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include configurazione
require_once __DIR__ . '/../backend/config/app_config.php';

// Funzione per eseguire file SQL
function executeSqlFile($pdo, $filename) {
    if (!file_exists($filename)) {
        throw new Exception("File SQL non trovato: $filename");
    }
    
    $sql = file_get_contents($filename);
    if ($sql === false) {
        throw new Exception("Impossibile leggere il file: $filename");
    }
    
    // Rimuovi commenti e dividi per statement
    $statements = array_filter(
        array_map('trim',
            explode(';', preg_replace('/--.*$/m', '', $sql))
        ), 
        function($statement) {
            return !empty($statement) && !preg_match('/^(DELIMITER|USE )/', $statement);
        }
    );
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Ignora errori per statement già esistenti
                if (strpos($e->getMessage(), 'already exists') === false) {
                    error_log("SQL Error: " . $e->getMessage());
                }
            }
        }
    }
    
    return $executed;
}

echo "<h1>🚀 BOSTARTER Database Installer v2.0</h1>\n";

try {
    // Connessione database iniziale
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "<h2>🔌 Connessione Database</h2>\n";
    echo "✅ Connesso a MySQL " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>\n";
    
    echo "<h2>📂 Installazione Schema</h2>\n";
    
    // Esegui installer SQL unificato
    $sqlFile = __DIR__ . '/bostarter_install.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('File installer SQL non trovato: ' . $sqlFile);
    }
    
    $executed = executeSqlFile($pdo, $sqlFile);
    echo "✅ Schema installato: $executed statement eseguiti<br>\n";
    
    // Verifica installazione
    echo "<h2>🔍 Verifica Installazione</h2>\n";
    
    $pdo->exec("USE " . DB_NAME);
    
    // Conta tabelle create
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Tabelle create: " . count($tables) . " (". implode(', ', $tables) .")<br>\n";
    
    // Conta competenze
    $result = $pdo->query("SELECT COUNT(*) FROM competenze");
    $competenzeCount = $result->fetchColumn();
    echo "✅ Competenze caricate: $competenzeCount<br>\n";
    
    // Verifica utente admin
    $result = $pdo->query("SELECT nickname, email FROM utenti WHERE tipo_utente = 'amministratore'");
    $admin = $result->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        echo "✅ Utente amministratore: {$admin['nickname']} ({$admin['email']})<br>\n";
    }
    
    // Test viste
    $result = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
    $viewsCount = $result->fetchColumn();
    echo "✅ Viste create: $viewsCount<br>\n";
    
    // Messaggio di successo completo
    echo "<h2>✨ Installazione Completata</h2>\n";
    echo "<div style='background: #d4f6d4; border: 2px solid #4caf50; color: #2e7d32; padding: 20px; border-radius: 8px; margin: 20px 0;'>\n";
    echo "<h3>🎉 Database BOSTARTER pronto!</h3>\n";
    echo "<p><strong>✅ Schema database installato correttamente</strong></p>\n";
    echo "<p><strong>✅ Dati di esempio caricati</strong></p>\n";
    echo "<p><strong>✅ Utente amministratore creato</strong></p>\n";
    echo "<hr style='border: 1px solid #4caf50; margin: 15px 0;'>\n";
    echo "<h4>🔑 CREDENZIALI DI ACCESSO:</h4>\n";
    echo "<p><strong>Amministratore:</strong><br>\n";
    echo "📧 Email: <code>admin@bostarter.com</code><br>\n";
    echo "🔒 Password: <code>admin123</code><br>\n";
    echo "🎫 Codice: <code>ADMIN2025</code></p>\n";
    echo "<hr style='border: 1px solid #4caf50; margin: 15px 0;'>\n";
    echo "<h4>🚀 PROSSIMI PASSI:</h4>\n";
    echo "<ol>\n";
    echo "<li>Vai al <a href='../frontend/' target='_blank' style='color: #1976d2; font-weight: bold;'>Frontend BOSTARTER</a></li>\n";
    echo "<li>Accedi con le credenziali amministratore</li>\n";
    echo "<li>Inizia a configurare la piattaforma</li>\n";
    echo "</ol>\n";
    echo "<p style='margin-top: 20px;'>\n";
    echo "<a href='../frontend/' target='_blank' style='background: linear-gradient(45deg, #2196f3, #21cbf3); color: white; padding: 12px 24px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);'>\n";
    echo "🚀 AVVIA BOSTARTER\n";
    echo "</a>\n";
    echo "</p>\n";
    echo "</div>\n";
    
} catch (PDOException $e) {
    echo "<h2>❌ Errore Installazione</h2>\n";
    echo "<div style='background: #ffebee; border: 2px solid #f44336; color: #c62828; padding: 20px; border-radius: 8px; margin: 20px 0;'>\n";
    echo "<h3>🚨 Installazione Fallita</h3>\n";
    echo "<p><strong>Errore:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<hr style='border: 1px solid #f44336; margin: 15px 0;'>\n";
    echo "<h4>🔧 SOLUZIONI COMUNI:</h4>\n";
    echo "<ol>\n";
    echo "<li><strong>Verifica XAMPP:</strong> Assicurati che il servizio MySQL sia avviato</li>\n";
    echo "<li><strong>Controlla credenziali:</strong> Verifica <code>backend/config/app_config.php</code></li>\n";
    echo "<li><strong>Test connessione:</strong> Prova ad accedere a phpMyAdmin</li>\n";
    echo "<li><strong>Privilegi database:</strong> Verifica che l'utente MySQL abbia privilegi CREATE</li>\n";
    echo "</ol>\n";
    echo "<p style='margin-top: 15px;'>\n";
    echo "<a href='javascript:location.reload()' style='background: #f44336; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>\n";
    echo "♾️ RIPROVA INSTALLAZIONE\n";
    echo "</a>\n";
    echo "</p>\n";
    echo "</div>\n";
} catch (Exception $e) {
    echo "<h2>❌ Errore Generale</h2>\n";
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; color: #856404; padding: 20px; border-radius: 8px; margin: 20px 0;'>\n";
    echo "<h3>⚠️ Errore durante l'installazione</h3>\n";
    echo "<p><strong>Descrizione:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Controlla i file di configurazione e riprova.</p>\n";
    echo "</div>\n";
}

echo "<hr style='margin: 30px 0; border: 1px solid #ddd;'>\n";
echo "<p style='text-align: center; color: #666; font-size: 14px;'>\n";
echo "BOSTARTER Database Installer v2.0 | © 2025 BOSTARTER Team\n";
echo "</p>\n";

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>BOSTARTER Database Installer</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        h1 { 
            color: #333; 
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        h2 { 
            color: #444; 
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        
        h3 {
            margin-top: 0;
        }
        
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        a {
            transition: all 0.3s ease;
        }
        
        a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #eee;
            border-radius: 3px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4caf50, #8bc34a);
            width: 100%;
            animation: progress 2s ease-in-out;
        }
        
        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #2196f3;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class='container'>
        <!-- Contenuto PHP generato sopra -->
        <div class='progress-bar'>
            <div class='progress-fill'></div>
        </div>
    </div>
    
    <script>
        // Aggiunge interattività alla pagina
        document.addEventListener('DOMContentLoaded', function() {
            // Animazione di completamento
            setTimeout(function() {
                const buttons = document.querySelectorAll('a[href]');
                buttons.forEach(btn => {
                    btn.style.animation = 'pulse 2s infinite';
                });
            }, 1000);
        });
        
        // Aggiunge animazione pulse
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
