<?php
require_once __DIR__ . '/backend/config/database.php';

echo "Adding missing database tables...\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Create progetti_competenze table
    $sql1 = "CREATE TABLE IF NOT EXISTS progetti_competenze (
        id INT PRIMARY KEY AUTO_INCREMENT,
        progetto_id INT NOT NULL,
        competenza_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
        FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
        UNIQUE KEY unique_progetto_competenza (progetto_id, competenza_id),
        INDEX idx_progetto (progetto_id),
        INDEX idx_competenza (competenza_id)
    )";
    
    $db->exec($sql1);
    echo "✅ progetti_competenze table created\n";
    
    // Create ricompense table
    $sql2 = "CREATE TABLE IF NOT EXISTS ricompense (
        id INT PRIMARY KEY AUTO_INCREMENT,
        progetto_id INT NOT NULL,
        titolo VARCHAR(200) NOT NULL,
        descrizione TEXT NOT NULL,
        importo_minimo DECIMAL(10,2) NOT NULL,
        quantita_disponibile INT NULL,
        data_consegna DATE,
        spese_spedizione DECIMAL(8,2) DEFAULT 0.00,
        attiva BOOLEAN DEFAULT TRUE,
        ordine_visualizzazione INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
        INDEX idx_progetto (progetto_id),
        INDEX idx_importo (importo_minimo),
        INDEX idx_attiva (attiva),
        INDEX idx_ordine (ordine_visualizzazione)
    )";
    
    $db->exec($sql2);
    echo "✅ ricompense table created\n";
      // Check if ricompensa_id column exists in finanziamenti
    $checkColumn = "SHOW COLUMNS FROM finanziamenti LIKE 'ricompensa_id'";
    $result = $db->query($checkColumn);
    
    if ($result->rowCount() == 0) {
        // Add ricompensa_id column
        $sql3 = "ALTER TABLE finanziamenti ADD COLUMN ricompensa_id INT NULL";
        $db->exec($sql3);
        echo "✅ ricompensa_id column added to finanziamenti\n";
        
        // Add foreign key
        $sql4 = "ALTER TABLE finanziamenti ADD CONSTRAINT fk_finanziamenti_ricompensa 
                 FOREIGN KEY (ricompensa_id) REFERENCES ricompense(id) ON DELETE SET NULL";
        $db->exec($sql4);
        echo "✅ Foreign key constraint added\n";
    } else {
        echo "ℹ️ ricompensa_id column already exists\n";
    }
    
    echo "\n🎉 All missing tables and columns have been added successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
