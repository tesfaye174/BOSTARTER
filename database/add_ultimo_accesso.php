<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('ALTER TABLE utenti ADD COLUMN ultimo_accesso TIMESTAMP NULL');
    echo "✓ Added ultimo_accesso column\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
