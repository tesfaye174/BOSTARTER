<?php
/**
 * Script di inizializzazione del database BOSTARTER
 * Questo file crea il database e le tabelle necessarie per la piattaforma
 */

// Includi il file di connessione
require_once 'db_connect.php';

// Funzione per visualizzare messaggi di stato
function showMessage($message, $isError = false) {
    echo '<div style="padding: 10px; margin: 10px 0; border-radius: 5px; ' . 
         'background-color: ' . ($isError ? '#ffebee' : '#e8f5e9') . '; ' .
         'color: ' . ($isError ? '#b71c1c' : '#1b5e20') . ';">' . 
         $message . '</div>';
}

// Funzione per inizializzare il database
function initializeDatabase() {
    // Connessione al server MySQL senza selezionare un database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        showMessage('Errore di connessione al server MySQL: ' . $conn->connect_error, true);
        return false;
    }
    
    // Leggi il file SQL
    $sqlFile = __DIR__ . '/bostarter_schema.sql';
    
    if (!file_exists($sqlFile)) {
        showMessage('File di schema del database non trovato: ' . $sqlFile, true);
        return false;
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Esegui le query multiple
    if ($conn->multi_query($sql)) {
        showMessage('Database BOSTARTER inizializzato con successo!');
        
        // Consuma tutti i risultati
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        return true;
    } else {
        showMessage('Errore nell\'inizializzazione del database: ' . $conn->error, true);
        return false;
    }
}

// Funzione per verificare la connessione al database
function testConnection() {
    try {
        $conn = getDbConnection();
        showMessage('Connessione al database BOSTARTER riuscita!');
        
        // Verifica se le tabelle esistono
        $result = $conn->query("SHOW TABLES");
        $tableCount = $result->num_rows;
        
        showMessage("Numero di tabelle nel database: $tableCount");
        
        if ($tableCount > 0) {
            echo '<h3>Tabelle presenti nel database:</h3>';
            echo '<ul>';
            while ($row = $result->fetch_row()) {
                echo '<li>' . $row[0] . '</li>';
            }
            echo '</ul>';
        }
        
        return true;
    } catch (Exception $e) {
        showMessage('Errore durante il test di connessione: ' . $e->getMessage(), true);
        return false;
    }
}

// Funzione per verificare i dati di base
function checkBasicData() {
    try {
        // Verifica le impostazioni
        $settings = fetchAll("SELECT * FROM impostazioni LIMIT 10");
        
        if (count($settings) > 0) {
            echo '<h3>Impostazioni di base:</h3>';
            echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
            echo '<tr><th>Chiave</th><th>Valore</th><th>Descrizione</th></tr>';
            
            foreach ($settings as $setting) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($setting['chiave']) . '</td>';
                echo '<td>' . htmlspecialchars($setting['valore']) . '</td>';
                echo '<td>' . htmlspecialchars($setting['descrizione']) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            showMessage('Nessuna impostazione trovata nel database.', true);
        }
        
        return true;
    } catch (Exception $e) {
        showMessage('Errore durante la verifica dei dati: ' . $e->getMessage(), true);
        return false;
    }
}

// Interfaccia HTML
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inizializzazione Database BOSTARTER</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #3176FF;
            border-bottom: 2px solid #3176FF;
            padding-bottom: 10px;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            background-color: #3176FF;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 10px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #2567e8;
        }
        .btn-danger {
            background-color: #FF6B35;
        }
        .btn-danger:hover {
            background-color: #e55a2a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Inizializzazione Database BOSTARTER</h1>
    
    <div class="card">
        <h2>Gestione Database</h2>
        <p>Questa pagina permette di inizializzare il database BOSTARTER e verificare la connessione.</p>
        
        <form method="post" onsubmit="return confirm('Sei sicuro di voler inizializzare il database? Tutti i dati esistenti saranno eliminati!')">
            <button type="submit" name="init" class="btn btn-danger">Inizializza Database</button>
            <button type="submit" name="test" class="btn">Testa Connessione</button>
            <button type="submit" name="check" class="btn">Verifica Dati</button>
        </form>
    </div>
    
    <div class="card">
        <h2>Risultato</h2>
        <?php
        // Gestione delle azioni
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['init'])) {
                initializeDatabase();
            } elseif (isset($_POST['test'])) {
                testConnection();
            } elseif (isset($_POST['check'])) {
                if (testConnection()) {
                    checkBasicData();
                }
            }
        } else {
            echo '<p>Seleziona un\'azione per iniziare.</p>';
        }
        ?>
    </div>
    
    <div class="card">
        <h2>Informazioni</h2>
        <p><strong>Host:</strong> <?php echo DB_HOST; ?></p>
        <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
        <p><strong>Charset:</strong> <?php echo DB_CHARSET; ?></p>
        <p><strong>File Schema:</strong> <?php echo realpath(__DIR__ . '/bostarter_schema.sql'); ?></p>
    </div>
    
    <p><a href="../index.html" class="btn">Torna alla Home</a></p>
</body>
</html>