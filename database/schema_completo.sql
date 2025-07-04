-- BOSTARTER Database Schema - Completo secondo specifica
-- Creato il 2025-07-04

-- Database creation
CREATE DATABASE IF NOT EXISTS bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bostarter;

-- Tabella utenti
CREATE TABLE IF NOT EXISTS utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita INT NOT NULL,
    luogo_nascita VARCHAR(100) NOT NULL,
    tipo_utente ENUM('normale', 'creatore', 'amministratore') DEFAULT 'normale',
    codice_sicurezza VARCHAR(50) NULL, -- Solo per amministratori
    nr_progetti INT DEFAULT 0, -- Solo per creatori
    affidabilita DECIMAL(3,2) DEFAULT 0.00, -- Solo per creatori
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_tipo_utente (tipo_utente)
);

-- Tabella competenze (gestita solo dagli amministratori)
CREATE TABLE IF NOT EXISTS competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) UNIQUE NOT NULL,
    descrizione TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nome (nome)
);

-- Tabella skill utente (relazione utente-competenza con livello)
CREATE TABLE IF NOT EXISTS skill_utente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello TINYINT NOT NULL CHECK (livello >= 0 AND livello <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_utente_competenza (utente_id, competenza_id),
    INDEX idx_utente (utente_id),
    INDEX idx_competenza (competenza_id)
);

-- Tabella progetti
CREATE TABLE IF NOT EXISTS progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    descrizione TEXT NOT NULL,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    budget_richiesto DECIMAL(10,2) NOT NULL CHECK (budget_richiesto > 0),
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    tipo ENUM('hardware', 'software') NOT NULL,
    creatore_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_nome (nome),
    INDEX idx_stato (stato),
    INDEX idx_tipo (tipo),
    INDEX idx_creatore (creatore_id),
    INDEX idx_data_limite (data_limite)
);

-- Tabella foto progetti
CREATE TABLE IF NOT EXISTS foto_progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    path VARCHAR(500) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);

-- Tabella rewards
CREATE TABLE IF NOT EXISTS rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    codice VARCHAR(50) UNIQUE NOT NULL,
    descrizione TEXT NOT NULL,
    foto VARCHAR(500),
    importo_minimo DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_codice (codice)
);

-- Tabella componenti hardware (solo per progetti hardware)
CREATE TABLE IF NOT EXISTS componenti_hardware (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descrizione TEXT,
    prezzo DECIMAL(10,2) NOT NULL CHECK (prezzo > 0),
    quantita INT NOT NULL CHECK (quantita > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progetto_componente (progetto_id, nome),
    INDEX idx_progetto (progetto_id)
);

-- Tabella profili richiesti (solo per progetti software)
CREATE TABLE IF NOT EXISTS profili_richiesti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL, -- es. "Esperto AI"
    descrizione TEXT,
    numero_posizioni INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);

-- Tabella skill richieste per profili
CREATE TABLE IF NOT EXISTS skill_profili (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profilo_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello TINYINT NOT NULL CHECK (livello >= 0 AND livello <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profilo_id) REFERENCES profili_richiesti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_profilo_competenza (profilo_id, competenza_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_competenza (competenza_id)
);

-- Tabella candidature (per progetti software)
CREATE TABLE IF NOT EXISTS candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    profilo_id INT NOT NULL,
    motivazione TEXT,
    stato ENUM('in_valutazione', 'accettata', 'rifiutata') DEFAULT 'in_valutazione',
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_valutazione TIMESTAMP NULL,
    note_valutazione TEXT,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (profilo_id) REFERENCES profili_richiesti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_utente_profilo (utente_id, profilo_id),
    INDEX idx_utente (utente_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_stato (stato)
);

-- Tabella finanziamenti
CREATE TABLE IF NOT EXISTS finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    reward_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL CHECK (importo > 0),
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note TEXT,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_data (data_finanziamento)
);

-- Tabella commenti
CREATE TABLE IF NOT EXISTS commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_data (data_commento)
);

-- Tabella risposte ai commenti
CREATE TABLE IF NOT EXISTS risposte_commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commento_id INT NOT NULL,
    utente_id INT NOT NULL, -- Deve essere il creatore del progetto
    testo TEXT NOT NULL,
    data_risposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commento_id) REFERENCES commenti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_risposta_per_commento (commento_id),
    INDEX idx_commento (commento_id),
    INDEX idx_utente (utente_id)
);

-- Vista per progetti con statistiche
CREATE VIEW IF NOT EXISTS progetti_statistiche AS
SELECT 
    p.*,
    COALESCE(SUM(f.importo), 0) as totale_raccolto,
    COUNT(DISTINCT f.utente_id) as numero_sostenitori,
    COUNT(f.id) as numero_finanziamenti,
    CASE 
        WHEN p.data_limite < CURDATE() THEN 'scaduto'
        WHEN COALESCE(SUM(f.importo), 0) >= p.budget_richiesto THEN 'finanziato'
        ELSE 'attivo'
    END as stato_finanziamento,
    ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 2) as percentuale_completamento
FROM progetti p
LEFT JOIN finanziamenti f ON p.id = f.progetto_id
GROUP BY p.id;

-- Trigger per aggiornare nr_progetti quando viene inserito un progetto
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_nr_progetti_insert
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti 
    SET nr_progetti = nr_progetti + 1
    WHERE id = NEW.creatore_id;
END//

CREATE TRIGGER IF NOT EXISTS update_nr_progetti_delete
AFTER DELETE ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti 
    SET nr_progetti = nr_progetti - 1
    WHERE id = OLD.creatore_id;
END//

-- Trigger per chiudere automaticamente i progetti scaduti o che hanno raggiunto il budget
CREATE TRIGGER IF NOT EXISTS check_progetto_stato
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE total_amount DECIMAL(10,2);
    DECLARE target_amount DECIMAL(10,2);
    
    SELECT SUM(importo) INTO total_amount
    FROM finanziamenti
    WHERE progetto_id = NEW.progetto_id;
    
    SELECT budget_richiesto INTO target_amount
    FROM progetti
    WHERE id = NEW.progetto_id;
    
    -- Chiudi il progetto se ha raggiunto il budget
    IF total_amount >= target_amount THEN
        UPDATE progetti 
        SET stato = 'chiuso'
        WHERE id = NEW.progetto_id;
    END IF;
END//

-- Evento per chiudere progetti scaduti (eseguito giornalmente)
CREATE EVENT IF NOT EXISTS close_expired_projects
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE progetti 
    SET stato = 'chiuso'
    WHERE data_limite < CURDATE() AND stato = 'aperto';
END//

DELIMITER ;

-- Inserimento competenze base
INSERT IGNORE INTO competenze (nome, descrizione) VALUES
('JavaScript', 'Linguaggio di programmazione per sviluppo web'),
('Python', 'Linguaggio di programmazione versatile'),
('Java', 'Linguaggio di programmazione orientato agli oggetti'),
('C++', 'Linguaggio di programmazione di sistema'),
('HTML/CSS', 'Linguaggi per struttura e stile di pagine web'),
('React', 'Libreria JavaScript per interfacce utente'),
('Node.js', 'Runtime JavaScript lato server'),
('MySQL', 'Sistema di gestione database relazionale'),
('MongoDB', 'Database NoSQL orientato ai documenti'),
('Git', 'Sistema di controllo versione'),
('Docker', 'Piattaforma di containerizzazione'),
('AWS', 'Servizi cloud Amazon'),
('Machine Learning', 'Apprendimento automatico'),
('AI', 'Intelligenza artificiale'),
('UI/UX Design', 'Design interfacce utente ed esperienza utente'),
('Project Management', 'Gestione progetti'),
('DevOps', 'Pratiche di sviluppo e operazioni'),
('Security', 'Sicurezza informatica'),
('Mobile Development', 'Sviluppo applicazioni mobile'),
('Blockchain', 'Tecnologia blockchain e criptovalute');

-- Creazione utente amministratore di default
INSERT IGNORE INTO utenti (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza) 
VALUES ('admin@bostarter.local', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Sistema', 1990, 'Sistema', 'amministratore', 'ADMIN2025');
