-- Crea database e tabelle mancanti
CREATE DATABASE IF NOT EXISTS bostarter;
USE bostarter;

-- Tabella utenti (quella corretta)
CREATE TABLE IF NOT EXISTS utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    tipo_utente ENUM('admin', 'creatore', 'standard') DEFAULT 'standard',
    anno_nascita INT,
    nr_progetti INT DEFAULT 0,
    attivo BOOLEAN DEFAULT TRUE,
    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella progetti (quella corretta)
CREATE TABLE IF NOT EXISTS progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    descrizione TEXT,
    budget_richiesto DECIMAL(10,2) NOT NULL,
    totale_raccolto DECIMAL(10,2) DEFAULT 0,
    data_scadenza DATE,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creatore_id INT,
    tipo ENUM('hardware', 'software') NOT NULL,
    stato ENUM('aperto', 'chiuso', 'scaduto') DEFAULT 'aperto',
    FOREIGN KEY (creatore_id) REFERENCES utenti(id)
);

-- Inserisci utenti di test
INSERT IGNORE INTO utenti (email, nickname, password_hash, nome, cognome, tipo_utente, anno_nascita) VALUES
('admin@bostarter.it', 'adminbos', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 1990),
('mario.rossi@email.it', 'mario', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario', 'Rossi', 'creatore', 1985),
('user@test.it', 'testuser2', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', 'standard', 1992);