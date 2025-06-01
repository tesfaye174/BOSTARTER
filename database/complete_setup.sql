-- BOSTARTER Complete Database Setup
-- Enhanced schema with stored procedures, views, triggers, and events

DROP DATABASE IF EXISTS bostarter;
CREATE DATABASE bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bostarter;

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- =====================================================
-- TABLES
-- =====================================================

-- Tabella delle competenze globali
CREATE TABLE competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT,
    categoria VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria)
);

-- Tabella utenti base
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
    codice_sicurezza VARCHAR(50) NULL,
    affidabilita DECIMAL(3,2) DEFAULT 0.00,
    nr_progetti INT DEFAULT 0,
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    bio TEXT,
    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_accesso TIMESTAMP NULL,
    stato ENUM('attivo', 'sospeso', 'eliminato') DEFAULT 'attivo',
    INDEX idx_tipo_utente (tipo_utente),
    INDEX idx_stato (stato),
    INDEX idx_data_registrazione (data_registrazione),
    INDEX idx_affidabilita (affidabilita)
);

-- Tabella skill degli utenti
CREATE TABLE utenti_skill (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello TINYINT NOT NULL CHECK (livello BETWEEN 0 AND 5),
    data_aggiunta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_utente_competenza (utente_id, competenza_id),
    INDEX idx_livello (livello)
);

-- Tabella progetti
CREATE TABLE progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    creatore_id INT NOT NULL,
    tipo_progetto ENUM('hardware', 'software') NOT NULL,
    budget_richiesto DECIMAL(10,2) NOT NULL,
    budget_raccolto DECIMAL(10,2) DEFAULT 0.00,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_scadenza TIMESTAMP NOT NULL,
    stato ENUM('aperto', 'chiuso', 'completato', 'annullato') DEFAULT 'aperto',
    immagine_principale VARCHAR(255),
    categoria VARCHAR(50),
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_stato (stato),
    INDEX idx_categoria (categoria),
    INDEX idx_scadenza (data_scadenza),
    INDEX idx_budget (budget_richiesto, budget_raccolto),
    INDEX idx_data_inserimento (data_inserimento)
);

-- Tabella reward
CREATE TABLE reward (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    codice VARCHAR(50) NOT NULL,
    descrizione TEXT NOT NULL,
    importo_minimo DECIMAL(10,2) NOT NULL,
    immagine VARCHAR(255),
    quantita_disponibile INT,
    quantita_riservata INT DEFAULT 0,
    data_consegna_stimata DATE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progetto_codice (progetto_id, codice)
);

-- Tabella profili richiesti (solo per progetti software)
CREATE TABLE profili_software (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT,
    numero_posizioni INT DEFAULT 1,
    posizioni_occupate INT DEFAULT 0,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE
);

-- Tabella skill richieste per profili software
CREATE TABLE profili_skill_richieste (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profilo_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello_minimo TINYINT NOT NULL CHECK (livello_minimo BETWEEN 0 AND 5),
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_profilo_competenza (profilo_id, competenza_id)
);

-- Tabella candidature
CREATE TABLE candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    profilo_id INT NOT NULL,
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('pendente', 'accettata', 'rifiutata') DEFAULT 'pendente',
    messaggio TEXT,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    UNIQUE KEY unique_utente_profilo (utente_id, profilo_id),
    INDEX idx_stato (stato),
    INDEX idx_data_candidatura (data_candidatura)
);

-- Tabella finanziamenti
CREATE TABLE finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    reward_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato_pagamento ENUM('pendente', 'completato', 'fallito', 'rimborsato') DEFAULT 'pendente',
    metodo_pagamento VARCHAR(50),
    transazione_id VARCHAR(100),
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES reward(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_data (data_finanziamento),
    INDEX idx_stato_pagamento (stato_pagamento),
    INDEX idx_importo (importo),
    INDEX idx_progetto_stato (progetto_id, stato_pagamento)
);

-- Tabella commenti
CREATE TABLE commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    risposta_id INT NULL,
    stato ENUM('attivo', 'nascosto', 'eliminato') DEFAULT 'attivo',
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (risposta_id) REFERENCES commenti(id) ON DELETE SET NULL,
    INDEX idx_progetto (progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_risposta (risposta_id),
    INDEX idx_stato (stato),
    INDEX idx_data_commento (data_commento),
    INDEX idx_progetto_stato (progetto_id, stato)
);

-- =====================================================
-- VIEWS
-- =====================================================

-- Vista per top 3 creatori per affidabilità
CREATE VIEW v_top_creatori_affidabilita AS
SELECT 
    u.id,
    u.nome,
    u.cognome,
    u.nickname,
    u.avatar,
    u.affidabilita,
    u.nr_progetti,
    COUNT(DISTINCT p.id) as progetti_attivi
FROM utenti u
LEFT JOIN progetti p ON u.id = p.creatore_id AND p.stato = 'aperto'
WHERE u.tipo_utente = 'creatore' AND u.stato = 'attivo'
GROUP BY u.id
ORDER BY u.affidabilita DESC, u.nr_progetti DESC
LIMIT 3;

-- Vista per top 3 progetti più vicini al goal
CREATE VIEW v_top_progetti_goal AS
SELECT 
    p.id,
    p.nome,
    p.descrizione,
    p.immagine_principale,
    p.budget_richiesto,
    p.budget_raccolto,
    p.data_scadenza,
    p.categoria,
    u.nome as creatore_nome,
    u.cognome as creatore_cognome,
    u.avatar as creatore_avatar,
    ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale_raggiunta,
    COUNT(DISTINCT f.id) as numero_finanziatori
FROM progetti p
JOIN utenti u ON p.creatore_id = u.id
LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato'
WHERE p.stato = 'aperto' AND p.data_scadenza > NOW()
GROUP BY p.id
ORDER BY percentuale_raggiunta DESC, p.budget_raccolto DESC
LIMIT 3;

-- Vista per top 3 finanziatori
CREATE VIEW v_top_finanziatori AS
SELECT 
    u.id,
    u.nome,
    u.cognome,
    u.nickname,
    u.avatar,
    SUM(f.importo) as totale_donato,
    COUNT(DISTINCT f.progetto_id) as progetti_finanziati,
    COUNT(f.id) as numero_finanziamenti
FROM utenti u
JOIN finanziamenti f ON u.id = f.utente_id
WHERE f.stato_pagamento = 'completato' AND u.stato = 'attivo'
GROUP BY u.id
ORDER BY totale_donato DESC, progetti_finanziati DESC
LIMIT 3;

-- Vista progetti aperti con dettagli
CREATE VIEW v_progetti_aperti AS
SELECT 
    p.id,
    p.nome,
    p.descrizione,
    p.tipo_progetto,
    p.budget_richiesto,
    p.budget_raccolto,
    p.data_inserimento,
    p.data_scadenza,
    p.immagine_principale,
    p.categoria,
    u.nome as creatore_nome,
    u.cognome as creatore_cognome,
    u.nickname as creatore_nickname,
    u.avatar as creatore_avatar,
    u.affidabilita as creatore_affidabilita,
    ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale_raggiunta,
    COUNT(DISTINCT f.id) as numero_finanziatori,
    DATEDIFF(p.data_scadenza, NOW()) as giorni_rimasti
FROM progetti p
JOIN utenti u ON p.creatore_id = u.id
LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato'
WHERE p.stato = 'aperto' AND p.data_scadenza > NOW()
GROUP BY p.id
ORDER BY p.data_inserimento DESC;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Procedura per registrazione utente
CREATE PROCEDURE sp_registra_utente(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(100),
    IN p_password_hash VARCHAR(255),
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100),
    IN p_anno_nascita YEAR,
    IN p_luogo_nascita VARCHAR(100),
    OUT p_user_id INT,
    OUT p_result VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            p_result = MESSAGE_TEXT;
        SET p_user_id = 0;
    END;
    
    START TRANSACTION;
    
    -- Verifica se email o nickname già esistono
    IF EXISTS(SELECT 1 FROM utenti WHERE email = p_email) THEN
        SET p_result = 'Email già registrata';
        SET p_user_id = 0;
        ROLLBACK;
    ELSEIF EXISTS(SELECT 1 FROM utenti WHERE nickname = p_nickname) THEN
        SET p_result = 'Nickname già in uso';
        SET p_user_id = 0;
        ROLLBACK;
    ELSE
        INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita)
        VALUES (p_email, p_nickname, p_password_hash, p_nome, p_cognome, p_anno_nascita, p_luogo_nascita);
        
        SET p_user_id = LAST_INSERT_ID();
        SET p_result = 'SUCCESS';
        COMMIT;
    END IF;
END //

-- Procedura per login
CREATE PROCEDURE sp_login_utente(
    IN p_email VARCHAR(255),
    OUT p_user_id INT,
    OUT p_password_hash VARCHAR(255),
    OUT p_tipo_utente VARCHAR(20),
    OUT p_stato VARCHAR(20),
    OUT p_result VARCHAR(255)
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_count FROM utenti WHERE email = p_email;
    
    IF v_count = 0 THEN
        SET p_result = 'Utente non trovato';
        SET p_user_id = 0;
    ELSE
        SELECT id, password_hash, tipo_utente, stato 
        INTO p_user_id, p_password_hash, p_tipo_utente, p_stato
        FROM utenti WHERE email = p_email;
        
        IF p_stato != 'attivo' THEN
            SET p_result = 'Account sospeso o eliminato';
            SET p_user_id = 0;
        ELSE
            -- Aggiorna ultimo accesso
            UPDATE utenti SET ultimo_accesso = NOW() WHERE id = p_user_id;
            SET p_result = 'SUCCESS';
        END IF;
    END IF;
END //

-- Procedura per aggiungere skill utente
CREATE PROCEDURE sp_aggiungi_skill_utente(
    IN p_utente_id INT,
    IN p_competenza_id INT,
    IN p_livello TINYINT,
    OUT p_result VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            p_result = MESSAGE_TEXT;
    END;
    
    START TRANSACTION;
    
    -- Verifica se la skill esiste già
    IF EXISTS(SELECT 1 FROM utenti_skill WHERE utente_id = p_utente_id AND competenza_id = p_competenza_id) THEN
        -- Aggiorna il livello esistente
        UPDATE utenti_skill 
        SET livello = p_livello, data_aggiunta = NOW()
        WHERE utente_id = p_utente_id AND competenza_id = p_competenza_id;
        SET p_result = 'UPDATED';
    ELSE
        -- Inserisci nuova skill
        INSERT INTO utenti_skill (utente_id, competenza_id, livello)
        VALUES (p_utente_id, p_competenza_id, p_livello);
        SET p_result = 'INSERTED';
    END IF;
    
    COMMIT;
END //

-- Procedura per finanziare progetto
CREATE PROCEDURE sp_finanzia_progetto(
    IN p_progetto_id INT,
    IN p_utente_id INT,
    IN p_reward_id INT,
    IN p_importo DECIMAL(10,2),
    IN p_metodo_pagamento VARCHAR(50),
    OUT p_finanziamento_id INT,
    OUT p_result VARCHAR(255)
)
BEGIN
    DECLARE v_budget_richiesto DECIMAL(10,2);
    DECLARE v_budget_raccolto DECIMAL(10,2);
    DECLARE v_stato_progetto VARCHAR(20);
    DECLARE v_data_scadenza TIMESTAMP;
    DECLARE v_importo_minimo DECIMAL(10,2);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            p_result = MESSAGE_TEXT;
        SET p_finanziamento_id = 0;
    END;
    
    START TRANSACTION;
    
    -- Verifica progetto e reward
    SELECT p.budget_richiesto, p.budget_raccolto, p.stato, p.data_scadenza, r.importo_minimo
    INTO v_budget_richiesto, v_budget_raccolto, v_stato_progetto, v_data_scadenza, v_importo_minimo
    FROM progetti p
    JOIN reward r ON p.id = r.progetto_id
    WHERE p.id = p_progetto_id AND r.id = p_reward_id;
    
    -- Validazioni
    IF v_stato_progetto != 'aperto' THEN
        SET p_result = 'Progetto non più aperto';
        SET p_finanziamento_id = 0;
        ROLLBACK;
    ELSEIF v_data_scadenza <= NOW() THEN
        SET p_result = 'Progetto scaduto';
        SET p_finanziamento_id = 0;
        ROLLBACK;
    ELSEIF p_importo < v_importo_minimo THEN
        SET p_result = 'Importo inferiore al minimo richiesto';
        SET p_finanziamento_id = 0;
        ROLLBACK;
    ELSE
        -- Inserisci finanziamento
        INSERT INTO finanziamenti (progetto_id, utente_id, reward_id, importo, metodo_pagamento, stato_pagamento)
        VALUES (p_progetto_id, p_utente_id, p_reward_id, p_importo, p_metodo_pagamento, 'completato');
        
        SET p_finanziamento_id = LAST_INSERT_ID();
        
        -- Aggiorna budget raccolto
        UPDATE progetti 
        SET budget_raccolto = budget_raccolto + p_importo
        WHERE id = p_progetto_id;
        
        SET p_result = 'SUCCESS';
        COMMIT;
    END IF;
END //

-- Procedura per aggiungere commento
CREATE PROCEDURE sp_aggiungi_commento(
    IN p_progetto_id INT,
    IN p_utente_id INT,
    IN p_testo TEXT,
    IN p_risposta_id INT,
    OUT p_commento_id INT,
    OUT p_result VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            p_result = MESSAGE_TEXT;
        SET p_commento_id = 0;
    END;
    
    START TRANSACTION;
    
    -- Verifica che il progetto esista
    IF NOT EXISTS(SELECT 1 FROM progetti WHERE id = p_progetto_id) THEN
        SET p_result = 'Progetto non trovato';
        SET p_commento_id = 0;
        ROLLBACK;
    ELSE
        INSERT INTO commenti (progetto_id, utente_id, testo, risposta_id)
        VALUES (p_progetto_id, p_utente_id, p_testo, p_risposta_id);
        
        SET p_commento_id = LAST_INSERT_ID();
        SET p_result = 'SUCCESS';
        COMMIT;
    END IF;
END //

-- Procedura per candidatura a profilo software
CREATE PROCEDURE sp_candida_profilo(
    IN p_utente_id INT,
    IN p_profilo_id INT,
    IN p_messaggio TEXT,
    OUT p_candidatura_id INT,
    OUT p_result VARCHAR(255)
)
BEGIN
    DECLARE v_posizioni_disponibili INT DEFAULT 0;
    DECLARE v_skills_match INT DEFAULT 0;
    DECLARE v_skills_required INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
            p_result = MESSAGE_TEXT;
        SET p_candidatura_id = 0;
    END;
    
    START TRANSACTION;
    
    -- Verifica posizioni disponibili
    SELECT (numero_posizioni - posizioni_occupate) INTO v_posizioni_disponibili
    FROM profili_software WHERE id = p_profilo_id;
    
    IF v_posizioni_disponibili <= 0 THEN
        SET p_result = 'Nessuna posizione disponibile';
        SET p_candidatura_id = 0;
        ROLLBACK;
    ELSE
        -- Verifica skill match
        SELECT COUNT(*) INTO v_skills_required
        FROM profili_skill_richieste psr
        WHERE psr.profilo_id = p_profilo_id;
        
        SELECT COUNT(*) INTO v_skills_match
        FROM profili_skill_richieste psr
        JOIN utenti_skill us ON psr.competenza_id = us.competenza_id
        WHERE psr.profilo_id = p_profilo_id 
        AND us.utente_id = p_utente_id 
        AND us.livello >= psr.livello_minimo;
        
        IF v_skills_match != v_skills_required THEN
            SET p_result = 'Competenze non sufficienti';
            SET p_candidatura_id = 0;
            ROLLBACK;
        ELSE
            -- Verifica candidatura esistente
            IF EXISTS(SELECT 1 FROM candidature WHERE utente_id = p_utente_id AND profilo_id = p_profilo_id) THEN
                SET p_result = 'Candidatura già inviata';
                SET p_candidatura_id = 0;
                ROLLBACK;
            ELSE
                INSERT INTO candidature (utente_id, profilo_id, messaggio)
                VALUES (p_utente_id, p_profilo_id, p_messaggio);
                
                SET p_candidatura_id = LAST_INSERT_ID();
                SET p_result = 'SUCCESS';
                COMMIT;
            END IF;
        END IF;
    END IF;
END //

DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger per aggiornare numero progetti del creatore
CREATE TRIGGER tr_progetti_insert
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti 
    SET nr_progetti = nr_progetti + 1
    WHERE id = NEW.creatore_id;
END //

-- Trigger per aggiornare quantità riservata reward
CREATE TRIGGER tr_finanziamenti_insert
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    IF NEW.stato_pagamento = 'completato' THEN
        UPDATE reward 
        SET quantita_riservata = quantita_riservata + 1
        WHERE id = NEW.reward_id;
    END IF;
END //

DELIMITER ;

-- =====================================================
-- EVENTS
-- =====================================================

DELIMITER //

-- Event per chiudere progetti scaduti
CREATE EVENT ev_chiudi_progetti_scaduti
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    UPDATE progetti 
    SET stato = 'chiuso'
    WHERE stato = 'aperto' 
    AND data_scadenza <= NOW();
END //

DELIMITER ;

-- =====================================================
-- DATI DI ESEMPIO
-- =====================================================

-- Inserimento competenze di base
INSERT INTO competenze (nome, descrizione, categoria) VALUES
('JavaScript', 'Linguaggio di programmazione per sviluppo web', 'Programmazione'),
('Python', 'Linguaggio di programmazione versatile', 'Programmazione'),
('Java', 'Linguaggio di programmazione object-oriented', 'Programmazione'),
('React', 'Libreria JavaScript per interfacce utente', 'Frontend'),
('Node.js', 'Runtime JavaScript per backend', 'Backend'),
('MySQL', 'Sistema di gestione database relazionale', 'Database'),
('MongoDB', 'Database NoSQL orientato ai documenti', 'Database'),
('UI/UX Design', 'Progettazione interfacce e esperienza utente', 'Design'),
('Project Management', 'Gestione di progetti e team', 'Management'),
('Machine Learning', 'Apprendimento automatico e AI', 'AI/ML');

-- Inserimento utente admin
INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza)
VALUES ('admin@bostarter.it', 'admin', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxvWMPWjRvIWRy.2LQ3QR1Q1Q1Q', 'Admin', 'BOSTARTER', 1990, 'Milano', 'amministratore', 'BS2024ADMIN');

-- Inserimento utenti di esempio
INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) VALUES
('marco.rossi@email.com', 'marcodev', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marco', 'Rossi', 1985, 'Roma', 'creatore'),
('giulia.bianchi@email.com', 'giuliatech', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Giulia', 'Bianchi', 1990, 'Milano', 'standard'),
('luca.verdi@email.com', 'lucamaker', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Luca', 'Verdi', 1988, 'Torino', 'creatore');

-- Aggiornamento affidabilità creatori
UPDATE utenti SET affidabilita = 85.50 WHERE nickname = 'marcodev';
UPDATE utenti SET affidabilita = 92.30 WHERE nickname = 'lucamaker';

-- =====================================================
-- INDEXES AGGIUNTIVI PER PERFORMANCE
-- =====================================================

CREATE INDEX idx_progetti_composite ON progetti(stato, data_scadenza, budget_raccolto);
CREATE INDEX idx_finanziamenti_composite ON finanziamenti(utente_id, stato_pagamento, data_finanziamento);
CREATE INDEX idx_commenti_composite ON commenti(progetto_id, stato, data_commento);
CREATE INDEX idx_candidature_composite ON candidature(utente_id, stato, data_candidatura);
