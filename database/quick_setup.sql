-- Quick setup script to create essential tables for the frontend
USE bostarter;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS progetti;
DROP TABLE IF EXISTS categorie_progetti;
DROP TABLE IF EXISTS utenti;

-- Create minimal tables needed for frontend to work
CREATE TABLE utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    nickname VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita YEAR NOT NULL,
    tipo_utente ENUM('standard', 'creatore', 'amministratore') DEFAULT 'standard',
    affidabilita DECIMAL(3,2) DEFAULT 0.00,
    nr_progetti INT DEFAULT 0,
    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('attivo', 'sospeso', 'eliminato') DEFAULT 'attivo'
);

-- Create categories table
CREATE TABLE categorie_progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT,
    icona VARCHAR(100),
    attiva BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create projects table with proper timestamp handling
CREATE TABLE progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    creatore_id INT NOT NULL,
    categoria_id INT NOT NULL,
    budget_richiesto DECIMAL(10,2) NOT NULL,
    budget_raccolto DECIMAL(10,2) DEFAULT 0.00,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_lancio TIMESTAMP NULL,
    data_scadenza TIMESTAMP NOT NULL DEFAULT (DATE_ADD(NOW(), INTERVAL 30 DAY)),
    stato ENUM('bozza', 'in_revisione', 'approvato', 'aperto', 'chiuso', 'completato', 'annullato') DEFAULT 'bozza',
    immagine_principale VARCHAR(255),
    nr_sostenitori INT DEFAULT 0,
    nr_visualizzazioni INT DEFAULT 0,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorie_progetti(id) ON DELETE RESTRICT
);

-- Insert some sample categories
INSERT INTO categorie_progetti (nome, descrizione, icona) VALUES
('Arte', 'Progetti artistici e creativi', 'fas fa-palette'),
('Tecnologia', 'Innovazione e sviluppo tecnologico', 'fas fa-microchip'),
('Design', 'Design grafico e industriale', 'fas fa-drafting-compass'),
('Musica', 'Progetti musicali e sonori', 'fas fa-music'),
('Altro', 'Altri progetti innovativi', 'fas fa-ellipsis-h');

-- Insert a sample user
INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, tipo_utente) VALUES
('demo@bostarter.it', 'demo_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'User', 1990, 'creatore');

-- Insert some sample projects
INSERT INTO progetti (nome, descrizione, creatore_id, categoria_id, budget_richiesto, budget_raccolto, stato, data_scadenza) VALUES
('Progetto Arte Digitale', 'Un innovativo progetto di arte digitale che combina tecnologia e creatività', 1, 1, 5000.00, 1200.00, 'aperto', DATE_ADD(NOW(), INTERVAL 45 DAY)),
('App Innovativa', 'Sviluppo di una nuova applicazione mobile rivoluzionaria', 1, 2, 10000.00, 3500.00, 'aperto', DATE_ADD(NOW(), INTERVAL 60 DAY)),
('Album Musicale', 'Produzione di un nuovo album musicale indipendente', 1, 4, 3000.00, 800.00, 'aperto', DATE_ADD(NOW(), INTERVAL 30 DAY));

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
