<?php
/**
 * Script per applicare le estensioni al database BOSTARTER
 * Questo file applica eventi, viste e stored procedures aggiuntive al database
 */

// Includi il file di connessione
require_once 'db_connect.php';

// Funzione per visualizzare messaggi di stato
function showMessage($message, $isError = false) {
    echo '<div style="padding: 10px; margin: 10px 0; border-radius: 5px; ' . 
         'background-color: ' . ($isError ? '#ffebee' : '#e8f5e9') . '; ' .
         'color: ' . ($isError ? '#b71c1c' : '#1b5e20') . ';">'. 
         $message . '</div>';
}

// Funzione per applicare le estensioni al database
function applyDatabaseExtensions() {
    $conn = getDbConnection();
    
    // Leggi il file SQL delle estensioni
    $sqlFile = __DIR__ . '/bostarter_extensions.sql';
    
    if (!file_exists($sqlFile)) {
        showMessage('File di estensioni del database non trovato: ' . $sqlFile, true);
        return false;
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Esegui le query multiple
    if ($conn->multi_query($sql)) {
        showMessage('Estensioni del database BOSTARTER applicate con successo!');
        
        // Consuma tutti i risultati
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        return true;
    } else {
        showMessage('Errore nell\'applicazione delle estensioni: ' . $conn->error, true);
        return false;
    }
}

// Funzione per verificare le estensioni applicate
function checkExtensions() {
    try {
        $conn = getDbConnection();
        
        // Verifica se l'evento esiste
        $eventResult = $conn->query("SHOW EVENTS WHERE Name = 'ev_close_expired_projects'");
        $eventExists = ($eventResult && $eventResult->num_rows > 0);
        
        // Verifica se le viste esistono
        $viewsToCheck = ['v_top_creatori', 'v_progetti_near_completion', 'v_top_finanziatori'];
        $viewsExist = [];
        
        foreach ($viewsToCheck as $view) {
            $viewResult = $conn->query("SHOW TABLES LIKE '$view'");
            $viewsExist[$view] = ($viewResult && $viewResult->num_rows > 0);
        }
        
        // Verifica se le stored procedures esistono
        $procsToCheck = ['sp_registra_utente', 'sp_login_utente', 'sp_crea_progetto'];
        $procsExist = [];
        
        foreach ($procsToCheck as $proc) {
            $procResult = $conn->query("SHOW PROCEDURE STATUS WHERE Name = '$proc'");
            $procsExist[$proc] = ($procResult && $procResult->num_rows > 0);
        }
        
        // Mostra i risultati
        echo '<h3>Stato delle estensioni:</h3>';
        
        echo '<h4>Eventi:</h4>';
        echo '<ul>';
        echo '<li>ev_close_expired_projects: ' . ($eventExists ? '<span style="color: green;">Installato</span>' : '<span style="color: red;">Non installato</span>') . '</li>';
        echo '</ul>';
        
        echo '<h4>Viste:</h4>';
        echo '<ul>';
        foreach ($viewsExist as $view => $exists) {
            echo '<li>' . $view . ': ' . ($exists ? '<span style="color: green;">Installata</span>' : '<span style="color: red;">Non installata</span>') . '</li>';
        }
        echo '</ul>';
        
        echo '<h4>Stored Procedures:</h4>';
        echo '<ul>';
        foreach ($procsExist as $proc => $exists) {
            echo '<li>' . $proc . ': ' . ($exists ? '<span style="color: green;">Installata</span>' : '<span style="color: red;">Non installata</span>') . '</li>';
        }
        echo '</ul>';
        
        return true;
    } catch (Exception $e) {
        showMessage('Errore durante la verifica delle estensioni: ' . $e->getMessage(), true);
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
    <title>Estensioni Database BOSTARTER</title>
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
    <h1>Estensioni Database BOSTARTER</h1>
    
    <div class="card">
        <h2>Gestione Estensioni</h2>
        <p>Questa pagina permette di applicare e verificare le estensioni del database BOSTARTER (eventi, viste e stored procedures).</p>
        
        <form method="post">
            <button type="submit" name="apply" class="btn btn-danger" onclick="return confirm('Sei sicuro di voler applicare le estensioni al database?');">Applica Estensioni</button>
            <button type="submit" name="check" class="btn">Verifica Estensioni</button>
        </form>
    </div>
    
    <div class="card">
        <h2>Risultato</h2>
        <?php
        // Gestione delle azioni
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['apply'])) {
                applyDatabaseExtensions();
            } elseif (isset($_POST['check'])) {
                checkExtensions();
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
        <p><strong>File Estensioni:</strong> <?php echo realpath(__DIR__ . '/bostarter_extensions.sql'); ?></p>
    </div>
    
    <p><a href="../index.html" class="btn">Torna alla Home</a></p>
</body>
</html>