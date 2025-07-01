-- BOSTARTER - Schema Database Completo Conforme alla Specifica
-- Creato: 07/01/2025 22:25:58

DROP DATABASE IF EXISTS bostarter;
CREATE DATABASE bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bostarter;

-- Tabella utenti (base con tutti i tipi)
CREATE TABLE utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita INT NOT NULL,
    luogo_nascita VARCHAR(100) NOT NULL,
    tipo_utente ENUM('standard', 'admin', 'creatore') DEFAULT 'standard',
    codice_sicurezza VARCHAR(50) NULL, -- Solo per admin
    nr_progetti INT DEFAULT 0, -- Solo per creatori (ridondanza)
    affidabilita DECIMAL(5,2) DEFAULT 0.00, -- Solo per creatori
    attivo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_access TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_nickname (nickname),
    INDEX idx_tipo (tipo_utente)
);

-- Tabella competenze (lista comune)
CREATE TABLE competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) UNIQUE NOT NULL,
    descrizione TEXT,
    categoria VARCHAR(50) DEFAULT 'generale',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nome (nome),
    INDEX idx_categoria (categoria)
);

-- Tabella skill utenti (curriculum)
CREATE TABLE skill_utente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello TINYINT NOT NULL CHECK (livello BETWEEN 0 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_skill_user (utente_id, competenza_id),
    INDEX idx_utente (utente_id),
    INDEX idx_competenza (competenza_id),
    INDEX idx_livello (livello)
);

-- Tabella progetti
CREATE TABLE progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) UNIQUE NOT NULL,
    descrizione TEXT NOT NULL,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    budget_richiesto DECIMAL(12,2) NOT NULL CHECK (budget_richiesto > 0),
    data_limite DATETIME NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    creatore_id INT NOT NULL,
    tipo_progetto ENUM('hardware', 'software') NOT NULL,
    foto TEXT, -- JSON array di URLs
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_creatore (creatore_id),
    INDEX idx_stato (stato),
    INDEX idx_tipo (tipo_progetto),
    INDEX idx_data_limite (data_limite)
);

-- Tabella rewards
CREATE TABLE rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codice VARCHAR(50) UNIQUE NOT NULL,
    descrizione TEXT NOT NULL,
    foto VARCHAR(500),
    progetto_id INT NOT NULL,
    importo_minimo DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_codice (codice)
);

-- Tabella componenti hardware (solo per progetti hardware)
CREATE TABLE componenti_hardware (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    prezzo DECIMAL(10,2) NOT NULL,
    quantita INT NOT NULL CHECK (quantita > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_nome (nome),
    UNIQUE KEY unique_component_project (progetto_id, nome)
);

-- Tabella profili software (solo per progetti software)
CREATE TABLE profili_software (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT,
    max_contributori INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);

-- Tabella skill richieste per profili software
CREATE TABLE skill_richieste_profilo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profilo_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello_richiesto TINYINT NOT NULL CHECK (livello_richiesto BETWEEN 0 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_skill_profile (profilo_id, competenza_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_competenza (competenza_id)
);

-- Tabella finanziamenti
CREATE TABLE finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL CHECK (importo > 0),
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reward_id INT NOT NULL,
    stato_pagamento ENUM('pending', 'completato', 'fallito') DEFAULT 'completato',
    metodo_pagamento VARCHAR(50) DEFAULT 'carta',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_data (data_finanziamento),
    INDEX idx_reward (reward_id)
);

-- Tabella commenti
CREATE TABLE commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_data (data_commento)
);

-- Tabella risposte ai commenti (max 1 risposta per commento)
CREATE TABLE risposte_commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commento_id INT NOT NULL UNIQUE, -- Un commento ha al massimo 1 risposta
    creatore_id INT NOT NULL, -- Solo il creatore può rispondere
    testo TEXT NOT NULL,
    data_risposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commento_id) REFERENCES commenti(id) ON DELETE CASCADE,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_commento (commento_id),
    INDEX idx_creatore (creatore_id)
);

-- Tabella candidature per progetti software
CREATE TABLE candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    profilo_id INT NOT NULL,
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    data_risposta TIMESTAMP NULL,
    note_creatore TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    UNIQUE KEY unique_candidatura (utente_id, progetto_id, profilo_id),
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_stato (stato)
);

-- Tabella sessioni utenti
CREATE TABLE sessioni_utente (
    id VARCHAR(128) PRIMARY KEY,
    utente_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_utente (utente_id),
    INDEX idx_expires (expires_at)
);
