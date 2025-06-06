-- BOSTARTER DATABASE SCHEMA - FULLY COMPLIANT WITH PDF SPECIFICATIONS
-- A.A. 2024/2025 - Corso di Basi di Dati CdS Informatica per il Management

DROP DATABASE IF EXISTS bostarter_compliant;
CREATE DATABASE bostarter_compliant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bostarter_compliant;

-- =================================================================
-- TABELLE PRINCIPALI CONFORMI ALLE SPECIFICHE PDF
-- =================================================================

-- Tabella COMPETENZE (lista comune a tutti gli utenti)
CREATE TABLE competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nome (nome)
);

-- Tabella UTENTI base con tutti i campi richiesti
CREATE TABLE utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    nickname VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita YEAR NOT NULL,
    luogo_nascita VARCHAR(100) NOT NULL,
    tipo_utente ENUM('standard', 'creatore', 'amministratore') DEFAULT 'standard',
    -- Campi per amministratori
    codice_sicurezza VARCHAR(50) NULL,
    -- Campi per creatori
    nr_progetti INT DEFAULT 0, -- Ridondanza concettuale richiesta
    affidabilita DECIMAL(5,2) DEFAULT 0.00, -- Percentuale 0-100
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_access TIMESTAMP NULL,
    INDEX idx_tipo_utente (tipo_utente),
    INDEX idx_email (email),
    INDEX idx_nickname (nickname)
);

-- Tabella SKILL UTENTE (competenza, livello 0-5)
CREATE TABLE skill_utente (
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

-- Tabella PROGETTI
CREATE TABLE progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL UNIQUE, -- Nome univoco richiesto
    descrizione TEXT NOT NULL,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    foto JSON, -- Array di foto (una o più)
    budget_richiesto DECIMAL(12,2) NOT NULL,
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    creatore_id INT NOT NULL,
    tipo_progetto ENUM('hardware', 'software') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_creatore (creatore_id),
    INDEX idx_stato (stato),
    INDEX idx_tipo (tipo_progetto),
    INDEX idx_data_limite (data_limite)
);

-- Tabella REWARD (ricompense)
CREATE TABLE reward (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codice VARCHAR(50) NOT NULL UNIQUE, -- Codice univoco richiesto
    progetto_id INT NOT NULL,
    descrizione TEXT NOT NULL,
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_codice (codice)
);

-- Tabella COMPONENTI HARDWARE (solo per progetti hardware)
CREATE TABLE componenti_hardware (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL UNIQUE, -- Nome univoco
    descrizione TEXT NOT NULL,
    prezzo DECIMAL(10,2) NOT NULL,
    quantita INT NOT NULL CHECK (quantita > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_nome (nome)
);

-- Tabella PROFILI SOFTWARE (solo per progetti software)
CREATE TABLE profili_software (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL, -- Es. "Esperto AI"
    max_contributori INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);

-- Tabella SKILL RICHIESTE per profili software (competenza, livello 0-5)
CREATE TABLE skill_richieste_profilo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profilo_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello_richiesto TINYINT NOT NULL CHECK (livello_richiesto >= 0 AND livello_richiesto <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_profilo_competenza (profilo_id, competenza_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_competenza (competenza_id)
);

-- Tabella FINANZIAMENTI
CREATE TABLE finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reward_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE RESTRICT,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE RESTRICT,
    FOREIGN KEY (reward_id) REFERENCES reward(id) ON DELETE RESTRICT,
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_data (data_finanziamento)
);

-- Tabella COMMENTI
CREATE TABLE commenti (
    id INT PRIMARY KEY AUTO_INCREMENT, -- ID univoco richiesto
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

-- Tabella RISPOSTE ai commenti (max 1 risposta per commento)
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

-- Tabella CANDIDATURE per progetti software
CREATE TABLE candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    profilo_id INT NOT NULL,
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    data_risposta TIMESTAMP NULL,
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

-- =================================================================
-- STORED PROCEDURES CONFORMI ALLE SPECIFICHE
-- =================================================================

DELIMITER //

-- SP: Registrazione utente
CREATE PROCEDURE registra_utente(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(100),
    IN p_password_hash VARCHAR(255),
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100),
    IN p_anno_nascita YEAR,
    IN p_luogo_nascita VARCHAR(100),
    IN p_tipo_utente ENUM('standard', 'creatore', 'amministratore'),
    IN p_codice_sicurezza VARCHAR(50)
)
BEGIN
    INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza)
    VALUES (p_email, p_nickname, p_password_hash, p_nome, p_cognome, p_anno_nascita, p_luogo_nascita, p_tipo_utente, p_codice_sicurezza);
    SELECT LAST_INSERT_ID() AS utente_id;
END //

-- SP: Login utente (con codice sicurezza per admin)
CREATE PROCEDURE login_utente(
    IN p_email VARCHAR(255),
    IN p_codice_sicurezza VARCHAR(50)
)
BEGIN
    SELECT id, password_hash, tipo_utente, codice_sicurezza 
    FROM utenti 
    WHERE email = p_email 
    AND (tipo_utente != 'amministratore' OR codice_sicurezza = p_codice_sicurezza);
END //

-- SP: Inserimento skill utente
CREATE PROCEDURE inserisci_skill_utente(
    IN p_utente_id INT,
    IN p_nome_competenza VARCHAR(100),
    IN p_livello TINYINT
)
BEGIN
    DECLARE v_competenza_id INT;
    
    -- Trova o crea competenza
    SELECT id INTO v_competenza_id FROM competenze WHERE nome = p_nome_competenza;
    IF v_competenza_id IS NULL THEN
        INSERT INTO competenze (nome) VALUES (p_nome_competenza);
        SET v_competenza_id = LAST_INSERT_ID();
    END IF;
    
    -- Inserisci/aggiorna skill utente
    INSERT INTO skill_utente (utente_id, competenza_id, livello)
    VALUES (p_utente_id, v_competenza_id, p_livello)
    ON DUPLICATE KEY UPDATE livello = p_livello;
END //

-- SP: Finanziamento progetto
CREATE PROCEDURE finanzia_progetto(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_importo DECIMAL(10,2),
    IN p_reward_id INT
)
BEGIN
    DECLARE v_budget_richiesto DECIMAL(12,2);
    DECLARE v_totale_raccolto DECIMAL(12,2);
    
    -- Inserisci finanziamento
    INSERT INTO finanziamenti (utente_id, progetto_id, importo, reward_id)
    VALUES (p_utente_id, p_progetto_id, p_importo, p_reward_id);
    
    -- Calcola totale raccolto
    SELECT budget_richiesto INTO v_budget_richiesto 
    FROM progetti WHERE id = p_progetto_id;
    
    SELECT COALESCE(SUM(importo), 0) INTO v_totale_raccolto
    FROM finanziamenti WHERE progetto_id = p_progetto_id;
    
    -- Se obiettivo raggiunto, chiudi progetto
    IF v_totale_raccolto >= v_budget_richiesto THEN
        UPDATE progetti SET stato = 'chiuso' WHERE id = p_progetto_id;
    END IF;
END //

-- SP: Inserimento commento
CREATE PROCEDURE inserisci_commento(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_testo TEXT
)
BEGIN
    INSERT INTO commenti (utente_id, progetto_id, testo)
    VALUES (p_utente_id, p_progetto_id, p_testo);
    SELECT LAST_INSERT_ID() AS commento_id;
END //

-- SP: Inserimento risposta commento (solo creatore)
CREATE PROCEDURE inserisci_risposta_commento(
    IN p_commento_id INT,
    IN p_creatore_id INT,
    IN p_testo TEXT
)
BEGIN
    DECLARE v_progetto_id INT;
    DECLARE v_creatore_progetto INT;
    
    -- Verifica che sia il creatore del progetto
    SELECT p.id, p.creatore_id INTO v_progetto_id, v_creatore_progetto
    FROM progetti p
    JOIN commenti c ON p.id = c.progetto_id
    WHERE c.id = p_commento_id;
    
    IF v_creatore_progetto = p_creatore_id THEN
        INSERT INTO risposte_commenti (commento_id, creatore_id, testo)
        VALUES (p_commento_id, p_creatore_id, p_testo);
    END IF;
END //

-- SP: Candidatura progetto software (con controllo skill)
CREATE PROCEDURE candidati_progetto(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_profilo_id INT
)
BEGIN
    DECLARE skill_match_ok BOOLEAN DEFAULT TRUE;
    DECLARE done BOOLEAN DEFAULT FALSE;
    DECLARE v_competenza_id INT;
    DECLARE v_livello_richiesto TINYINT;
    DECLARE v_livello_utente TINYINT;
    
    DECLARE skill_cursor CURSOR FOR 
        SELECT competenza_id, livello_richiesto 
        FROM skill_richieste_profilo 
        WHERE profilo_id = p_profilo_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Controlla tutte le skill richieste
    OPEN skill_cursor;
    check_skills: LOOP
        FETCH skill_cursor INTO v_competenza_id, v_livello_richiesto;
        IF done THEN
            LEAVE check_skills;
        END IF;
        
        -- Ottieni livello utente per questa competenza
        SELECT livello INTO v_livello_utente
        FROM skill_utente
        WHERE utente_id = p_utente_id AND competenza_id = v_competenza_id;
        
        -- Se utente non ha la competenza o livello insufficiente
        IF v_livello_utente IS NULL OR v_livello_utente < v_livello_richiesto THEN
            SET skill_match_ok = FALSE;
            LEAVE check_skills;
        END IF;
    END LOOP;
    CLOSE skill_cursor;
    
    -- Se skill match OK, inserisci candidatura
    IF skill_match_ok THEN
        INSERT INTO candidature (utente_id, progetto_id, profilo_id)
        VALUES (p_utente_id, p_progetto_id, p_profilo_id);
        SELECT 'Candidatura inserita con successo' AS messaggio;
    ELSE
        SELECT 'Skill insufficienti per candidarsi' AS messaggio;
    END IF;
END //

-- SP: Accettazione/rifiuto candidatura (solo creatore)
CREATE PROCEDURE gestisci_candidatura(
    IN p_candidatura_id INT,
    IN p_creatore_id INT,
    IN p_stato ENUM('accepted', 'rejected')
)
BEGIN
    DECLARE v_progetto_creatore INT;
    
    -- Verifica che sia il creatore del progetto
    SELECT p.creatore_id INTO v_progetto_creatore
    FROM progetti p
    JOIN candidature c ON p.id = c.progetto_id
    WHERE c.id = p_candidatura_id;
    
    IF v_progetto_creatore = p_creatore_id THEN
        UPDATE candidature 
        SET stato = p_stato, data_risposta = NOW()
        WHERE id = p_candidatura_id;
    END IF;
END //

-- SP: Inserimento nuova competenza (solo admin)
CREATE PROCEDURE inserisci_competenza(
    IN p_nome VARCHAR(100)
)
BEGIN
    INSERT INTO competenze (nome) VALUES (p_nome);
    SELECT LAST_INSERT_ID() AS competenza_id;
END //

DELIMITER ;

-- =================================================================
-- VISTE STATISTICHE CONFORMI ALLE SPECIFICHE
-- =================================================================

-- Vista: Top 3 creatori per affidabilità
CREATE VIEW vista_top_creatori_affidabilita AS
SELECT 
    nickname,
    affidabilita
FROM utenti
WHERE tipo_utente = 'creatore' AND nr_progetti > 0
ORDER BY affidabilita DESC
LIMIT 3;

-- Vista: Top 3 progetti aperti più vicini al completamento
CREATE VIEW vista_progetti_vicini_completamento AS
SELECT 
    p.nome,
    p.budget_richiesto,
    COALESCE(SUM(f.importo), 0) as totale_raccolto,
    (p.budget_richiesto - COALESCE(SUM(f.importo), 0)) as differenza
FROM progetti p
LEFT JOIN finanziamenti f ON p.id = f.progetto_id
WHERE p.stato = 'aperto'
GROUP BY p.id, p.nome, p.budget_richiesto
ORDER BY differenza ASC
LIMIT 3;

-- Vista: Top 3 utenti per finanziamenti erogati
CREATE VIEW vista_top_finanziatori AS
SELECT 
    u.nickname,
    SUM(f.importo) as totale_finanziato
FROM utenti u
JOIN finanziamenti f ON u.id = f.utente_id
GROUP BY u.id, u.nickname
ORDER BY totale_finanziato DESC
LIMIT 3;

-- =================================================================
-- TRIGGER CONFORMI ALLE SPECIFICHE
-- =================================================================

DELIMITER //

-- Trigger: Aggiorna affidabilità creatore quando si crea un progetto
CREATE TRIGGER aggiorna_affidabilita_nuovo_progetto
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    DECLARE v_progetti_totali INT;
    DECLARE v_progetti_finanziati INT;
    DECLARE v_nuova_affidabilita DECIMAL(5,2);
    
    -- Conta progetti totali del creatore
    SELECT COUNT(*) INTO v_progetti_totali
    FROM progetti WHERE creatore_id = NEW.creatore_id;
    
    -- Conta progetti che hanno ricevuto almeno un finanziamento
    SELECT COUNT(DISTINCT p.id) INTO v_progetti_finanziati
    FROM progetti p
    JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE p.creatore_id = NEW.creatore_id;
    
    -- Calcola nuova affidabilità (percentuale)
    IF v_progetti_totali > 0 THEN
        SET v_nuova_affidabilita = (v_progetti_finanziati / v_progetti_totali) * 100;
    ELSE
        SET v_nuova_affidabilita = 0;
    END IF;
    
    -- Aggiorna campo nr_progetti e affidabilità
    UPDATE utenti 
    SET nr_progetti = v_progetti_totali,
        affidabilita = v_nuova_affidabilita
    WHERE id = NEW.creatore_id;
END //

-- Trigger: Aggiorna affidabilità quando un progetto riceve il primo finanziamento
CREATE TRIGGER aggiorna_affidabilita_primo_finanziamento
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE v_progetti_totali INT;
    DECLARE v_progetti_finanziati INT;
    DECLARE v_nuova_affidabilita DECIMAL(5,2);
    DECLARE v_creatore_id INT;
    
    -- Ottieni ID creatore
    SELECT creatore_id INTO v_creatore_id
    FROM progetti WHERE id = NEW.progetto_id;
    
    -- Conta progetti totali del creatore
    SELECT COUNT(*) INTO v_progetti_totali
    FROM progetti WHERE creatore_id = v_creatore_id;
    
    -- Conta progetti che hanno ricevuto almeno un finanziamento
    SELECT COUNT(DISTINCT p.id) INTO v_progetti_finanziati
    FROM progetti p
    JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE p.creatore_id = v_creatore_id;
    
    -- Calcola nuova affidabilità
    IF v_progetti_totali > 0 THEN
        SET v_nuova_affidabilita = (v_progetti_finanziati / v_progetti_totali) * 100;
    ELSE
        SET v_nuova_affidabilita = 0;
    END IF;
    
    -- Aggiorna affidabilità
    UPDATE utenti 
    SET affidabilita = v_nuova_affidabilita
    WHERE id = v_creatore_id;
END //

-- Trigger: Chiudi progetto quando raggiunge il budget
CREATE TRIGGER chiudi_progetto_budget_raggiunto
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE v_budget_richiesto DECIMAL(12,2);
    DECLARE v_totale_raccolto DECIMAL(12,2);
    
    -- Ottieni budget richiesto
    SELECT budget_richiesto INTO v_budget_richiesto
    FROM progetti WHERE id = NEW.progetto_id;
    
    -- Calcola totale raccolto
    SELECT SUM(importo) INTO v_totale_raccolto
    FROM finanziamenti WHERE progetto_id = NEW.progetto_id;
    
    -- Se budget raggiunto, chiudi progetto
    IF v_totale_raccolto >= v_budget_richiesto THEN
        UPDATE progetti SET stato = 'chiuso' WHERE id = NEW.progetto_id;
    END IF;
END //

-- Trigger: Incrementa nr_progetti quando creatore inserisce progetto
CREATE TRIGGER incrementa_nr_progetti
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti 
    SET nr_progetti = nr_progetti + 1
    WHERE id = NEW.creatore_id;
END //

DELIMITER ;

-- =================================================================
-- EVENTO PER CHIUSURA AUTOMATICA PROGETTI SCADUTI
-- =================================================================

DELIMITER //

CREATE EVENT chiudi_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE progetti 
    SET stato = 'chiuso'
    WHERE stato = 'aperto' 
    AND data_limite < CURDATE();
END //

DELIMITER ;

-- Abilita scheduler eventi
SET GLOBAL event_scheduler = ON;

-- =================================================================
-- INDICI OTTIMIZZATI PER LE OPERAZIONI
-- =================================================================

-- Indici per l'analisi di ridondanza (#nr_progetti)
CREATE INDEX idx_creatore_progetti ON progetti(creatore_id);
CREATE INDEX idx_data_finanziamenti ON finanziamenti(data_finanziamento);

-- =================================================================
-- DATI DI ESEMPIO PER TEST
-- =================================================================

-- Inserimento competenze base
INSERT INTO competenze (nome) VALUES 
('PHP'), ('JavaScript'), ('Python'), ('MySQL'), ('MongoDB'), 
('React'), ('Node.js'), ('AI/ML'), ('UI/UX Design'), ('Project Management');

-- Inserimento utenti di test
INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza) VALUES
('admin@bostarter.it', 'AdminBOS', '$2y$10$hashedpassword1', 'Mario', 'Rossi', 1985, 'Roma', 'amministratore', 'ADMIN2024'),
('creator1@test.it', 'CreatorOne', '$2y$10$hashedpassword2', 'Luca', 'Verdi', 1990, 'Milano', 'creatore', NULL),
('creator2@test.it', 'CreatorTwo', '$2y$10$hashedpassword3', 'Anna', 'Bianchi', 1988, 'Torino', 'creatore', NULL),
('user1@test.it', 'UserOne', '$2y$10$hashedpassword4', 'Paolo', 'Neri', 1992, 'Napoli', 'standard', NULL),
('user2@test.it', 'UserTwo', '$2y$10$hashedpassword5', 'Sara', 'Blu', 1995, 'Firenze', 'standard', NULL);

-- Esempio di progetti e dati per test
-- (da completare secondo necessità)
