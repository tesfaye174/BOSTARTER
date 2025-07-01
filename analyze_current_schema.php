<?php
require_once 'backend/config/database.php';
$db = Database::getInstance()->getConnection();

echo "=== ANALISI SCHEMA DATABASE ATTUALE ===\n\n";

// Mostra tutte le tabelle
echo "TABELLE PRESENTI:\n";
$result = $db->query('SHOW TABLES');
$tables = [];
while($row = $result->fetch()) {
    $tableName = $row[array_keys($row)[0]];
    $tables[] = $tableName;
    echo "- $tableName\n";
}

echo "\n=== STRUTTURA TABELLE ===\n";

// Analizza ogni tabella
foreach($tables as $table) {
    echo "\nTabella: $table\n";
    echo str_repeat("-", 50) . "\n";
    $result = $db->query("DESCRIBE $table");
    while($row = $result->fetch()) {
        echo sprintf("%-20s %-15s %s\n", 
            $row['Field'], 
            $row['Type'], 
            ($row['Key'] ? "KEY: ".$row['Key'] : '') . 
            ($row['Default'] ? " DEFAULT: ".$row['Default'] : '') .
            ($row['Extra'] ? " ".$row['Extra'] : '')
        );
    }
}

echo "\n=== FOREIGN KEYS ===\n";
$result = $db->query("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_SCHEMA = 'bostarter'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

while($row = $result->fetch()) {
    echo sprintf("%s.%s -> %s.%s (%s)\n",
        $row['TABLE_NAME'],
        $row['COLUMN_NAME'],
        $row['REFERENCED_TABLE_NAME'],
        $row['REFERENCED_COLUMN_NAME'],
        $row['CONSTRAINT_NAME']
    );
}
?>