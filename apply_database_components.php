<?php
require_once 'backend/config/database.php';

function executeSQLFile($filename, $description) {
    try {
        $db = Database::getInstance()->getConnection();
        $sql = file_get_contents($filename);
        
        if ($sql === false) {
            echo "❌ Errore: Impossibile leggere file $filename\n";
            return false;
        }
        
        echo "🔄 Applicando $description...\n";
        
        // Dividi per statement (semplificato)
        $statements = explode(';', $sql);
        $executed = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^(--|#)/', $statement)) {
                try {
                    $db->exec($statement);
                    $executed++;
                } catch (Exception $e) {
                    // Ignora errori per DROP e dichiarazioni duplicate
                    if (!strpos($e->getMessage(), 'does not exist') && 
                        !strpos($e->getMessage(), 'already exists')) {
                        echo "⚠️ Warning: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        
        echo "✅ $description applicato ($executed statements)\n\n";
        return true;
    } catch (Exception $e) {
        echo "❌ Errore in $description: " . $e->getMessage() . "\n\n";
        return false;
    }
}

echo "=== APPLICAZIONE COMPONENTI DATABASE ===\n\n";

// Applica componenti in ordine
$components = [
    ['database/stored_procedures.sql', 'Stored Procedures'],
    ['database/triggers.sql', 'Trigger'],
    ['database/views.sql', 'Viste'],
    ['database/events.sql', 'Eventi']
];

$success_count = 0;
foreach ($components as $component) {
    if (executeSQLFile($component[0], $component[1])) {
        $success_count++;
    }
}

echo "=== RIEPILOGO ===\n";
echo "Componenti applicati con successo: $success_count/" . count($components) . "\n";

if ($success_count == count($components)) {
    echo "🎉 Tutti i componenti database sono stati applicati correttamente!\n";
} else {
    echo "⚠️ Alcuni componenti potrebbero non essere stati applicati correttamente.\n";
}

// Test rapido delle stored procedures
echo "\n=== TEST STORED PROCEDURES ===\n";
try {
    $db = Database::getInstance()->getConnection();
    
    // Test esistenza stored procedures
    $result = $db->query("SHOW PROCEDURE STATUS WHERE Db = 'bostarter'");
    $procedures = $result->fetchAll();
    
    echo "Stored Procedures presenti:\n";
    foreach ($procedures as $proc) {
        echo "- " . $proc['Name'] . "\n";
    }
    
    // Test esistenza trigger
    $result = $db->query("SHOW TRIGGERS");
    $triggers = $result->fetchAll();
    
    echo "\nTrigger presenti:\n";
    foreach ($triggers as $trigger) {
        echo "- " . $trigger['Trigger'] . " su " . $trigger['Table'] . "\n";
    }
    
    // Test esistenza viste
    $result = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $result->fetchAll();
    
    echo "\nViste presenti:\n";
    foreach ($views as $view) {
        $viewName = $view[array_keys($view)[0]];
        echo "- $viewName\n";
    }
    
} catch (Exception $e) {
    echo "Errore nel test: " . $e->getMessage() . "\n";
}
?>
