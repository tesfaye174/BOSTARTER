-- Database BOSTARTER completo
CREATE DATABASE IF NOT EXISTS bostarter;
USE bostarter;

-- Elimina tabelle esistenti per ricrearle
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;

-- Tabella utenti italiana
CREATE TABLE IF NOT EXISTS utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    tipo_utente ENUM('admin', 'creatore', 'standard') DEFAULT 'standard',
    anno_nascita INT,
    luogo_nascita VARCHAR(100),
    nr_progetti INT DEFAULT 0,
    attivo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabella progetti italiana
CREATE TABLE IF NOT EXISTS progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    descrizione TEXT,
    budget_richiesto DECIMAL(10,2) NOT NULL,
    budget_raccolto DECIMAL(10,2) DEFAULT 0,
    data_limite DATE,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_scadenza DATE,
    creatore_id INT,
    tipo_progetto ENUM('hardware', 'software') NOT NULL,
    stato ENUM('aperto', 'chiuso', 'scaduto') DEFAULT 'aperto',
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE
);

-- Inserisci utenti di test con hash bcrypt per password "password"
INSERT IGNORE INTO utenti (email, nickname, password_hash, nome, cognome, tipo_utente, anno_nascita, luogo_nascita) VALUES
('admin@bostarter.it', 'adminbos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 1990, 'Roma'),
('mario.rossi@email.it', 'mario', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario', 'Rossi', 'creatore', 1985, 'Milano'),
('user@test.it', 'testuser2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', 'standard', 1992, 'Napoli');

-- Inserisci progetti di test per Mario (ID: assume sarà 2)
INSERT IGNORE INTO progetti (nome, descrizione, budget_richiesto, budget_raccolto, data_limite, data_scadenza, creatore_id, tipo_progetto) VALUES
('App di Crowdfunding', 'Applicazione per gestire campagne di crowdfunding', 5000.00, 1500.00, DATE_ADD(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 2, 'software'),
('Sistema IoT Casa', 'Sistema per automatizzare la casa con sensori IoT', 3000.00, 800.00, DATE_ADD(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 45 DAY), 2, 'hardware'),
('Piattaforma E-learning', 'Piattaforma per corsi online', 8000.00, 2500.00, DATE_ADD(CURDATE(), INTERVAL 90 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 2, 'software');
