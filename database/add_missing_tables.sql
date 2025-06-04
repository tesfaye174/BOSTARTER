-- Add missing tables that the Project model expects
USE bostarter;

-- Create progetti_competenze table (many-to-many relationship for project skills)
CREATE TABLE IF NOT EXISTS progetti_competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    competenza_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progetto_competenza (progetto_id, competenza_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_competenza (competenza_id)
);

-- Create ricompense table (table for rewards)
CREATE TABLE IF NOT EXISTS ricompense (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    titolo VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    importo_minimo DECIMAL(10,2) NOT NULL,
    quantita_disponibile INT NULL, -- NULL = unlimited
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
);

-- Update finanziamenti table to work with ricompense
ALTER TABLE finanziamenti 
DROP FOREIGN KEY IF EXISTS finanziamenti_ibfk_3;

ALTER TABLE finanziamenti 
CHANGE COLUMN reward_id ricompensa_id INT NULL;

ALTER TABLE finanziamenti
ADD CONSTRAINT fk_finanziamenti_ricompensa 
FOREIGN KEY (ricompensa_id) REFERENCES ricompense(id) ON DELETE SET NULL;
