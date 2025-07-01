-- Tabelle di sicurezza mancanti per BOSTARTER

USE bostarter_compliant;

-- Tabella per gestire IP bloccati
CREATE TABLE IF NOT EXISTS blocchi_ip (
    id INT PRIMARY KEY AUTO_INCREMENT,
    indirizzo_ip VARCHAR(45) NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    scadenza_blocco DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (indirizzo_ip),
    INDEX idx_scadenza (scadenza_blocco)
);

-- Tabella per log eventi di sicurezza 
CREATE TABLE IF NOT EXISTS security_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_id INT NULL,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_ip (ip_address),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
);

-- Tabella per tentative di login falliti
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255),
    success BOOLEAN DEFAULT FALSE,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_email (email),
    INDEX idx_time (attempt_time)
);

