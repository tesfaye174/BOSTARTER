<?php
/**
 * Test Database BOSTARTER Italiano
 *
 * Script per verificare la creazione e connessione al database bostarter_italiano
 * Esegue test su tabelle, stored procedures e trigger
 */

// Configurazione database
$host = 'localhost';
$user = 'root'; // Cambia se necessario
$password = ''; // Cambia se necessario
$database = 'bostarter_italiano';

echo "🔧 TEST DATABASE BOSTARTER ITALIANO\n";
echo "==================================\n\n";

// Test connessione
try {
    $conn = new mysqli($host, $user, $password);

    if ($conn->connect_error) {
        throw new Exception("Connessione fallita: " . $conn->connect_error);
    }

    echo "✅ Connessione al server MySQL riuscita\n";

    // Verifica se il database esiste
    $result = $conn->query("SHOW DATABASES LIKE '$database'");
    if ($result->num_rows == 0) {
        echo "❌ Database '$database' non trovato\n";
        echo "💡 Esegui prima: bostarter_italiano_deployment.sql\n";
        exit(1);
    }

    echo "✅ Database '$database' trovato\n";

    // Connessione al database specifico
    $conn->select_db($database);

    // Test tabelle principali
    echo "\n📋 VERIFICA TABELLE:\n";
    $tables = [
        'utenti', 'competenze', 'progetti', 'finanziamenti',
        'commenti', 'profili_software', 'candidature', 'rewards'
    ];

    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✅ Tabella '$table' presente\n";
        } else {
            echo "❌ Tabella '$table' mancante\n";
        }
    }

    // Test viste
    echo "\n📊 VERIFICA VISTE:\n";
    $views = [
        'top_creatori_affidabilita',
        'top_progetti_vicini_completamento',
        'top_finanziatori_importo',
        'statistiche_generali'
    ];

    foreach ($views as $view) {
        $result = $conn->query("SHOW TABLES LIKE '$view'");
        if ($result->num_rows > 0) {
            echo "✅ Vista '$view' presente\n";
        } else {
            echo "❌ Vista '$view' mancante\n";
        }
    }

    // Test dati di esempio
    echo "\n📝 VERIFICA DATI DI ESEMPIO:\n";

    // Utenti
    $result = $conn->query("SELECT COUNT(*) as count FROM utenti");
    $row = $result->fetch_assoc();
    echo "👥 Utenti totali: " . $row['count'] . "\n";

    // Competenze
    $result = $conn->query("SELECT COUNT(*) as count FROM competenze");
    $row = $result->fetch_assoc();
    echo "🎯 Competenze totali: " . $row['count'] . "\n";

    // Test stored procedure (se esistono)
    echo "\n⚙️ TEST STORED PROCEDURES:\n";

    // Lista delle procedure che dovrebbero esistere
    $procedures = [
        'registra_utente',
        'autentica_utente',
        'crea_progetto',
        'effettua_finanziamento',
        'inserisci_commento'
    ];

    foreach ($procedures as $procedure) {
        $result = $conn->query("SHOW PROCEDURE STATUS WHERE Name = '$procedure' AND Db = '$database'");
        if ($result->num_rows > 0) {
            echo "✅ Procedura '$procedure' presente\n";
        } else {
            echo "❌ Procedura '$procedure' mancante\n";
        }
    }

    // Test trigger
    echo "\n🔄 TEST TRIGGER:\n";

    $triggers = [
        'trg_incrementa_conteggio_progetti',
        'trg_log_creazione_progetto',
        'trg_chiudi_progetto_budget_raggiunto',
        'trg_aggiorna_affidabilita_finanziamento'
    ];

    foreach ($triggers as $trigger) {
        $result = $conn->query("SHOW TRIGGERS LIKE '$trigger'");
        if ($result->num_rows > 0) {
            echo "✅ Trigger '$trigger' presente\n";
        } else {
            echo "❌ Trigger '$trigger' mancante\n";
        }
    }

    echo "\n🎉 TEST COMPLETATO!\n";

    $conn->close();

} catch (Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n📋 ISTRUZIONI PER IL DEPLOYMENT:\n";
echo "================================\n";
echo "1. Apri phpMyAdmin: http://localhost/phpmyadmin\n";
echo "2. Seleziona il database 'bostarter_italiano'\n";
echo "3. Importa il file: stored_bostarter_italiano.sql\n";
echo "4. Importa il file: trigger_bostarter_italiano.sql\n";
echo "5. Rileggo questo script per verificare tutto\n";
echo "\n🚀 Database pronto per BOSTARTER!\n";
?>
