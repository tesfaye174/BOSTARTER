-- Tabella per il rate limiting
-- Traccia i tentativi di login falliti per prevenire attacchi brute force

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL UNIQUE COMMENT 'Identificatore univoco (email, IP, etc.)',
    attempts INT DEFAULT 0 COMMENT 'Numero di tentativi falliti',
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp ultimo tentativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creazione record',
    INDEX idx_identifier (identifier),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB COMMENT='Gestione rate limiting per prevenire attacchi brute force';

-- Clean up automatico dei record vecchi (opzionale)
-- Può essere eseguito da un job cron
-- DELETE FROM rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR);
