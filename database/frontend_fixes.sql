-- Add missing tables and columns for frontend compatibility
USE bostarter;

-- Create finanziamenti (funding) table
CREATE TABLE IF NOT EXISTS finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    stato_pagamento ENUM('in_attesa', 'completato', 'fallito', 'rimborsato') DEFAULT 'in_attesa',
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metodo_pagamento VARCHAR(50),
    note TEXT,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_stato (stato_pagamento)
);

-- Add missing columns to progetti table if they don't exist
ALTER TABLE progetti 
ADD COLUMN IF NOT EXISTS categoria VARCHAR(100),
ADD COLUMN IF NOT EXISTS tipo_progetto VARCHAR(100) DEFAULT 'standard';

-- Update categoria column with category names from categorie_progetti
UPDATE progetti p 
JOIN categorie_progetti c ON p.categoria_id = c.id 
SET p.categoria = c.nome;

-- Insert some sample funding data
INSERT INTO finanziamenti (progetto_id, utente_id, importo, stato_pagamento) VALUES
(1, 1, 500.00, 'completato'),
(1, 1, 300.00, 'completato'),
(1, 1, 400.00, 'completato'),
(2, 1, 1000.00, 'completato'),
(2, 1, 1500.00, 'completato'),
(2, 1, 1000.00, 'completato'),
(3, 1, 400.00, 'completato'),
(3, 1, 400.00, 'completato');
