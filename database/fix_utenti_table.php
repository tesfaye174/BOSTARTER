<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== UTENTI TABLE STRUCTURE ===\n";
    $stmt = $db->query("DESCRIBE utenti");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
    
    echo "\n=== CHECKING FOR MISSING COLUMNS ===\n";
    $expected_columns = [
        'id', 'email', 'nickname', 'password_hash', 'nome', 'cognome',
        'anno_nascita', 'luogo_nascita', 'tipo_utente', 'stato', 'data_registrazione',
        'ultimo_accesso', 'avatar', 'bio', 'affidabilita', 'nr_progetti'
    ];
    
    $existing_columns = array_column($columns, 'Field');
    $missing_columns = array_diff($expected_columns, $existing_columns);
    
    if (!empty($missing_columns)) {
        echo "Missing columns:\n";
        foreach ($missing_columns as $col) {
            echo "- $col\n";
        }
        
        echo "\n=== ADDING MISSING COLUMNS ===\n";
        
        if (in_array('luogo_nascita', $missing_columns)) {
            $db->exec("ALTER TABLE utenti ADD COLUMN luogo_nascita VARCHAR(100) NULL");
            echo "✓ Added luogo_nascita column\n";
        }
        
        if (in_array('anno_nascita', $missing_columns)) {
            $db->exec("ALTER TABLE utenti ADD COLUMN anno_nascita YEAR NULL");
            echo "✓ Added anno_nascita column\n";
        }
        
        if (in_array('avatar', $missing_columns)) {
            $db->exec("ALTER TABLE utenti ADD COLUMN avatar VARCHAR(255) NULL");
            echo "✓ Added avatar column\n";
        }
        
        if (in_array('bio', $missing_columns)) {
            $db->exec("ALTER TABLE utenti ADD COLUMN bio TEXT NULL");
            echo "✓ Added bio column\n";
        }
        
        if (in_array('affidabilita', $missing_columns)) {
            $db->exec("ALTER TABLE utenti ADD COLUMN affidabilita DECIMAL(3,2) DEFAULT 0.00");
            echo "✓ Added affidabilita column\n";
        }
        
        if (in_array('nr_progetti', $missing_columns)) {
            $db->exec("ALTER TABLE utenti ADD COLUMN nr_progetti INT DEFAULT 0");
            echo "✓ Added nr_progetti column\n";
        }
        
    } else {
        echo "All expected columns are present.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
