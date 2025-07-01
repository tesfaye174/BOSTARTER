<?php
/**
 * Test completo funzionalità BOSTARTER
 * Verifica conformità alla specifica
 */

require_once __DIR__ . "/backend/config/database.php";

echo "=== TEST FUNZIONALITÀ BOSTARTER ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "✅ Connessione database: OK\n\n";
    
    // Test 1: Verifica struttura tabelle
    echo "📋 VERIFICA STRUTTURA DATABASE:\n";
    echo str_repeat("-", 40) . "\n";
    
    $tables = [
        'utenti' => ['email', 'nickname', 'password_hash', 'nome', 'cognome', 'anno_nascita', 'luogo_nascita', 'tipo_utente', 'codice_sicurezza', 'nr_progetti', 'affidabilita'],
        'progetti' => ['nome', 'descrizione', 'data_inserimento', 'budget_richiesto', 'data_limite', 'stato', 'creatore_id'],
        'competenze' => ['nome'],
        'skill_utenti' => ['utente_id', 'competenza_id', 'livello'],
        'rewards' => ['codice', 'descrizione', 'progetto_id'],
        'finanziamenti' => ['utente_id', 'progetto_id', 'importo', 'data_finanziamento', 'reward_id'],
        'commenti' => ['utente_id', 'progetto_id', 'testo', 'data_commento'],
        'candidature' => ['utente_id', 'profilo_id'],
        'componenti_hardware' => ['nome', 'descrizione', 'prezzo', 'quantita', 'progetto_id'],
        'profili_software' => ['nome', 'descrizione', 'progetto_id']
    ];
    
    foreach($tables as $table => $required_columns) {
        echo "  📊 $table: ";
        try {
            $stmt = $conn->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $missing = array_diff($required_columns, $columns);
            if(empty($missing)) {
                echo "✅ OK\n";
            } else {
                echo "❌ Mancanti: " . implode(', ', $missing) . "\n";
            }
        } catch(Exception $e) {
            echo "❌ TABELLA NON TROVATA\n";
        }
    }
    
    echo "\n📈 VERIFICA VISTE STATISTICHE:\n";
    echo str_repeat("-", 40) . "\n";
    
    $viste = [
        'vista_top_creatori_affidabilita' => 'Top 3 creatori per affidabilità',
        'vista_top_progetti_completamento' => 'Top 3 progetti vicini completamento',
        'vista_top_finanziatori' => 'Top 3 finanziatori'
    ];
    
    foreach($viste as $vista => $descrizione) {
        echo "  📊 $descrizione: ";
        try {
            $stmt = $conn->query("SELECT * FROM $vista LIMIT 1");
            echo "✅ OK\n";
        } catch(Exception $e) {
            echo "❌ ERRORE: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🔧 VERIFICA FUNZIONALITÀ FRONTEND:\n";
    echo str_repeat("-", 40) . "\n";
    
    $pagine = [
        'frontend/index.php' => 'Homepage',
        'frontend/auth/login.php' => 'Login',
        'frontend/auth/signup.php' => 'Registrazione',
        'frontend/home.php' => 'Dashboard',
        'frontend/new.php' => 'Nuovo progetto',
        'frontend/view.php' => 'Visualizza progetto',
        'frontend/statistiche.php' => 'Statistiche',
        'frontend/skill.php' => 'Gestione skill utenti',
        'frontend/finanzia.php' => 'Sistema finanziamenti',
        'frontend/commenti.php' => 'Sistema commenti',
        'frontend/candidature.php' => 'Sistema candidature',
        'frontend/admin/competenze.php' => 'Pannello admin competenze'
    ];
    
    foreach($pagine as $file => $nome) {
        echo "  📄 $nome: ";
        if(file_exists(__DIR__ . "/$file")) {
            echo "✅ Presente\n";
        } else {
            echo "❌ MANCANTE\n";
        }
    }
    
    echo "\n⚙️ FUNZIONALITÀ MANCANTI IDENTIFICATE:\n";
    echo str_repeat("-", 50) . "\n";
    
    $mancanti = [
        '✅ Gestione skill curriculum utenti',
        '✅ Sistema finanziamenti progetti',
        '✅ Scelta reward dopo finanziamento',
        '✅ Sistema commenti/risposte',
        '✅ Sistema candidature profili',
        '✅ Pannello admin (gestione competenze)',
        '❌ Gestione rewards da creatore',
        '❌ Gestione profili software da creatore',
        '❌ Accettazione/rifiuto candidature (implementato ma da testare)',
        '✅ Validazione skill per candidature',
        '✅ Log eventi MongoDB (con fallback su file)',
        '❌ Stored procedures complete',
        '❌ Trigger automatici',
        '❌ Eventi programmati'
    ];
    
    foreach($mancanti as $item) {
        echo "  $item\n";
    }
    
    echo "\n🎯 PRIORITÀ IMPLEMENTAZIONE:\n";
    echo str_repeat("-", 40) . "\n";
    echo "1. 🔴 ALTA: Sistema autenticazione completo\n";
    echo "2. 🔴 ALTA: Gestione finanziamenti\n";
    echo "3. 🔴 ALTA: Sistema commenti\n";
    echo "4. 🟠 MEDIA: Gestione skill e candidature\n";
    echo "5. 🟠 MEDIA: Pannello amministratore\n";
    echo "6. 🟡 BASSA: Log eventi MongoDB\n";
    echo "7. 🟡 BASSA: Ottimizzazioni database\n";
    
} catch(Exception $e) {
    echo "❌ ERRORE GENERALE: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Test completato. Vedere risultati sopra per dettagli.\n";
?>
