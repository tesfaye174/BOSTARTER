<?php
/**
 * Pagina di test per le estensioni del database BOSTARTER
 * Questo file permette di testare le viste e le stored procedures implementate
 */

// Includi i file necessari
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php'; // For DB constants if not already included by database.php
require_once 'stored_procedures.php';

// Funzione per visualizzare messaggi di stato
function showMessage($message, $isError = false) {
    echo '<div style="padding: 10px; margin: 10px 0; border-radius: 5px; ' . 
         'background-color: ' . ($isError ? '#ffebee' : '#e8f5e9') . '; ' .
         'color: ' . ($isError ? '#b71c1c' : '#1b5e20') . ';">'. 
         $message . '</div>';
}

// TODO: Define or include registraUtente function, possibly a wrapper for sp_registra_utente
/*
// Funzione per testare la registrazione utente
function testRegistraUtente() {
    // Dati di test
    $email = 'test_' . time() . '@example.com';
    $nickname = 'test_user_' . time();
    $password = 'password123';
    $nome = 'Utente';
    $cognome = 'Test';
    $anno_nascita = 1990;
    $luogo_nascita = 'Milano';
    $tipo_utente = 'creatore';
    
    // Chiama la funzione wrapper
    // $result = registraUtente($email, $nickname, $password, $nome, $cognome, $anno_nascita, $luogo_nascita, $tipo_utente);
    $result = ['success' => false, 'message' => 'registraUtente function is not defined'];
    
    // Mostra il risultato
    if ($result['success']) {
        showMessage("Registrazione utente riuscita: {$result['message']} (ID: {$result['user_id']})");
    } else {
        showMessage("Registrazione utente fallita: {$result['message']}", true);
    }
    
    return $result;
}
*/

// TODO: Define or include loginUtente function, possibly a wrapper for sp_login_utente
/*
// Funzione per testare il login utente
function testLoginUtente($email, $password) {
    // Chiama la funzione wrapper
    // $result = loginUtente($email, $password);
    $result = ['success' => false, 'message' => 'loginUtente function is not defined'];
    
    // Mostra il risultato
    if ($result['success']) {
        showMessage("Login utente riuscito: {$result['message']}");
        echo '<pre>' . print_r($result['user_data'], true) . '</pre>';
    } else {
        showMessage("Login utente fallito: {$result['message']}", true);
    }
    
    return $result;
}
*/

// Funzione per testare la creazione di un progetto
function testCreaProgetto($creatore_id) {
    // Dati di test
    $nome = 'Progetto Test ' . time();
    $descrizione = 'Questo è un progetto di test creato automaticamente';
    $tipo_progetto = 'software';
    $budget_richiesto = 5000.00;
    $data_scadenza = date('Y-m-d H:i:s', strtotime('+30 days'));
    $categoria = 'Tecnologia';
    
    // Chiama la funzione wrapper
    $result = creaProgetto($nome, $descrizione, $creatore_id, $tipo_progetto, $budget_richiesto, $data_scadenza, $categoria);
    
    // Mostra il risultato
    if ($result['success']) {
        showMessage("Creazione progetto riuscita: {$result['message']} (ID: {$result['progetto_id']})");
    } else {
        showMessage("Creazione progetto fallita: {$result['message']}", true);
    }
    
    return $result;
}

// Funzione per testare le viste
function testViste() {
    echo '<h3>Test Vista Top Creatori</h3>';
    $topCreatori = getTopCreatori();
    if (count($topCreatori) > 0) {
        echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
        echo '<tr><th>Nickname</th><th>Affidabilità</th><th>Nr. Progetti</th><th>Finanziamenti Ricevuti</th><th>Importo Raccolto</th></tr>';
        
        foreach ($topCreatori as $creatore) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($creatore['nickname']) . '</td>';
            echo '<td>' . htmlspecialchars($creatore['affidabilita']) . '</td>';
            echo '<td>' . htmlspecialchars($creatore['nr_progetti']) . '</td>';
            echo '<td>' . htmlspecialchars($creatore['totale_finanziamenti_ricevuti']) . '</td>';
            echo '<td>' . htmlspecialchars($creatore['totale_importo_raccolto']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        showMessage('Nessun creatore trovato', true);
    }
    
    echo '<h3>Test Vista Progetti Near Completion</h3>';
    $progettiNearCompletion = getProgettiNearCompletion();
    if (count($progettiNearCompletion) > 0) {
        echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
        echo '<tr><th>Nome</th><th>Budget Richiesto</th><th>Budget Raccolto</th><th>Differenza</th><th>% Completamento</th><th>Creatore</th><th>Scadenza</th></tr>';
        
        foreach ($progettiNearCompletion as $progetto) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($progetto['nome']) . '</td>';
            echo '<td>' . htmlspecialchars($progetto['budget_richiesto']) . '</td>';
            echo '<td>' . htmlspecialchars($progetto['budget_raccolto']) . '</td>';
            echo '<td>' . htmlspecialchars($progetto['differenza']) . '</td>';
            echo '<td>' . htmlspecialchars($progetto['percentuale_completamento']) . '%</td>';
            echo '<td>' . htmlspecialchars($progetto['creatore']) . '</td>';
            echo '<td>' . htmlspecialchars($progetto['data_scadenza']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        showMessage('Nessun progetto vicino al completamento trovato', true);
    }
    
    echo '<h3>Test Vista Top Finanziatori</h3>';
    $topFinanziatori = getTopFinanziatori();
    if (count($topFinanziatori) > 0) {
        echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
        echo '<tr><th>Nickname</th><th>Numero Finanziamenti</th><th>Totale Finanziato</th><th>Progetti Supportati</th></tr>';
        
        foreach ($topFinanziatori as $finanziatore) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($finanziatore['nickname']) . '</td>';
            echo '<td>' . htmlspecialchars($finanziatore['numero_finanziamenti']) . '</td>';
            echo '<td>' . htmlspecialchars($finanziatore['totale_finanziato']) . '</td>';
            echo '<td>' . htmlspecialchars($finanziatore['progetti_supportati']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        showMessage('Nessun finanziatore trovato', true);
    }
}

// Funzione per testare la chiusura dei progetti scaduti
function testChiudiProgettiScaduti() {
    $result = chiudiProgettiScaduti();
    
    if ($result) {
        showMessage('Chiusura progetti scaduti eseguita con successo');
    } else {
        showMessage('Errore durante la chiusura dei progetti scaduti', true);
    }
    
    return $result;
}

// Interfaccia HTML
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Estensioni Database BOSTARTER</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background-color: #f2f2f2;
            text-align: left;
        }
        table th, table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Test Estensioni Database BOSTARTER</h1>
    
    <div class="card">
        <h2>Test Stored Procedures</h2>
        <form method="post">
            <button type="submit" name="test_registra" class="btn">Test Registrazione Utente</button>
            <button type="submit" name="test_login" class="btn">Test Login Utente</button>
            <button type="submit" name="test_crea_progetto" class="btn">Test Creazione Progetto</button>
            <button type="submit" name="test_chiudi_progetti" class="btn">Test Chiusura Progetti Scaduti</button>
        </form>
    </div>
    
    <div class="card">
        <h2>Test Viste</h2>
        <form method="post">
            <button type="submit" name="test_viste" class="btn">Test Viste</button>
        </form>
    </div>
    
    <div class="card">
        <h2>Risultato</h2>
        <?php
        // Gestione delle azioni
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['test_registra'])) {
                // $registraResult = testRegistraUtente();
                showMessage("Test Registrazione Utente disabilitato: definire registraUtente.", true);
                // Se la registrazione è riuscita, testa anche il login
                // if ($registraResult['success']) {
                //     echo '<h3>Test Login con Utente Appena Registrato</h3>';
                //     testLoginUtente($registraResult['email'], 'password123');
                    
                //     // Se l'utente è un creatore, testa anche la creazione di un progetto
                //     if ($registraResult['tipo_utente'] == 'creatore') {
                //         echo '<h3>Test Creazione Progetto con Utente Appena Registrato</h3>';
                //         testCreaProgetto($registraResult['user_id']);
                //     }
                // }
            } elseif (isset($_POST['test_login'])) {
                showMessage("Test Login Utente disabilitato: definire loginUtente e registraUtente.", true);
                // // Usa un utente esistente o crea un nuovo utente per il test
                // $email = 'test_user@example.com';
                // $password = 'password123';
                
                // // Verifica se l'utente esiste
                // $db = new Database();
                // $conn = $db->getConnection();
                // $stmt = $conn->prepare("SELECT id FROM utenti WHERE email = ?");
                // $stmt->execute([$email]);
                // $userExists = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // if (!$userExists) {
                //     showMessage("Creazione utente di test per il login...");
                //     // $registraResult = registraUtente($email, 'test_user', $password, 'Utente', 'Test', 1990, 'Milano', 'standard');
                //     // if (!$registraResult['success']) {
                //     //     showMessage("Impossibile creare l'utente di test: {$registraResult['message']}", true);
                //     //     return;
                //     // }
                // }
                
                // testLoginUtente($email, $password);
            } elseif (isset($_POST['test_crea_progetto'])) {
                // Usa un creatore esistente o crea un nuovo creatore per il test
                $email = 'test_creator@example.com';
                // $password = 'password123'; // Password not needed if only checking existence or creating via SP without login
                
                $db = new Database();
                $conn = $db->getConnection();
                
                // Verifica se il creatore esiste
                $stmt = $conn->prepare("SELECT id FROM utenti WHERE email = ? AND tipo_utente = 'creatore'");
                $stmt->execute([$email]);
                $creatorExists = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $creatore_id = null;
                if (!$creatorExists) {
                    showMessage("Creatore di test non trovato ($email). Si prega di crearlo manualmente o implementare la registrazione.", true);
                    // TODO: Implementare la registrazione del creatore o creare manualmente per il test.
                    // showMessage("Creazione creatore di test per il progetto...");
                    // $registraResult = registraUtente($email, 'test_creator', $password, 'Creatore', 'Test', 1985, 'Roma', 'creatore');
                    // if (!$registraResult['success']) {
                    //     showMessage("Impossibile creare il creatore di test: {$registraResult['message']}", true);
                    //     return;
                    // }
                    // $creatore_id = $registraResult['user_id'];
                } else {
                    $creatore_id = $creatorExists['id'];
                }
                
                if ($creatore_id) {
                    testCreaProgetto($creatore_id);
                } else {
                    showMessage("Impossibile eseguire testCreaProgetto: ID creatore non disponibile.", true);
                }
            } elseif (isset($_POST['test_viste'])) {
                testViste();
            } elseif (isset($_POST['test_chiudi_progetti'])) {
                testChiudiProgettiScaduti();
            }
        } else {
            echo '<p>Seleziona un\'azione per iniziare i test.</p>';
        }
        ?>
    </div>
    
    <p><a href="apply_extensions.php" class="btn">Torna alla Gestione Estensioni</a></p>
    <p><a href="../index.html" class="btn">Torna alla Home</a></p>
</body>
</html>