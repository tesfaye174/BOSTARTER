-- =================================================================
-- BOSTARTER DATABASE SCHEMA v2.0
-- Schema principale: tabelle, indici, trigger, viste, eventi
-- =================================================================

DROP DATABASE IF EXISTS bostarter;
CREATE DATABASE bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bostarter;

-- TABELLE PRINCIPALI
CREATE TABLE competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nome (nome)
);

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
    nr_progetti INT DEFAULT 0,
    affidabilita DECIMAL(5,2) DEFAULT 0.00,
    stato ENUM('attivo', 'sospeso', 'disattivato') DEFAULT 'attivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_access TIMESTAMP NULL,
    INDEX idx_tipo_utente (tipo_utente),
    INDEX idx_email (email),
    INDEX idx_nickname (nickname)
);

CREATE TABLE progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL UNIQUE,
    descrizione TEXT NOT NULL,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    foto JSON,
    budget_richiesto DECIMAL(12,2) NOT NULL,
    budget_raccolto DECIMAL(12,2) DEFAULT 0.00,
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    creatore_id INT NOT NULL,
    tipo_progetto ENUM('hardware', 'software') NOT NULL,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_creatore (creatore_id),
    INDEX idx_stato (stato),
    INDEX idx_tipo (tipo_progetto),
    INDEX idx_data_limite (data_limite)
);

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

CREATE TABLE reward (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codice VARCHAR(50) NOT NULL UNIQUE,
    progetto_id INT NOT NULL,
    descrizione TEXT NOT NULL,
    foto VARCHAR(255),
    importo_minimo DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_codice (codice)
);

CREATE TABLE finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    reward_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato_pagamento ENUM('pending', 'completato', 'fallito') DEFAULT 'pending',
    metodo_pagamento VARCHAR(50),
    note TEXT,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES reward(id) ON DELETE CASCADE,
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_data (data_finanziamento),
    INDEX idx_stato (stato_pagamento)
);

CREATE TABLE componenti_hardware (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    prezzo DECIMAL(10,2) NOT NULL,
    quantita INT NOT NULL CHECK (quantita > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_componente_progetto (progetto_id, nome),
    INDEX idx_progetto (progetto_id)
);

CREATE TABLE profili_software (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    max_contributori INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);

CREATE TABLE skill_richieste_profilo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profilo_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello TINYINT NOT NULL CHECK (livello >= 0 AND livello <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_profilo_competenza (profilo_id, competenza_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_competenza (competenza_id)
);

CREATE TABLE commenti (
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

CREATE TABLE risposte_commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commento_id INT NOT NULL UNIQUE,
    creatore_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_risposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commento_id) REFERENCES commenti(id) ON DELETE CASCADE,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_commento (commento_id),
    INDEX idx_creatore (creatore_id)
);

CREATE TABLE candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    profilo_id INT NOT NULL,
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('in_attesa', 'accettata', 'rifiutata') DEFAULT 'in_attesa',
    data_risposta TIMESTAMP NULL,
    note_creatore TEXT,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    UNIQUE KEY unique_candidatura (utente_id, progetto_id, profilo_id),
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_stato (stato)
);

CREATE TABLE log_attivita (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo_attivita VARCHAR(50) NOT NULL,
    utente_id INT NULL,
    progetto_id INT NULL,
    descrizione TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo_attivita),
    INDEX idx_utente (utente_id),
    INDEX idx_timestamp (timestamp)
);

-- TRIGGER AUTOMATICI
DELIMITER $$
CREATE TRIGGER tr_incrementa_nr_progetti
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti SET nr_progetti = nr_progetti + 1 WHERE id = NEW.creatore_id;
END$$

CREATE TRIGGER tr_decrementa_nr_progetti
AFTER DELETE ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti SET nr_progetti = nr_progetti - 1 WHERE id = OLD.creatore_id;
END$$

CREATE TRIGGER tr_chiudi_progetto_budget
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE budget_totale DECIMAL(12,2);
    DECLARE budget_richiesto DECIMAL(12,2);
    
    IF NEW.stato_pagamento = 'completato' THEN
        SELECT SUM(importo), p.budget_richiesto
        INTO budget_totale, budget_richiesto
        FROM finanziamenti f
        JOIN progetti p ON f.progetto_id = p.id
        WHERE f.progetto_id = NEW.progetto_id AND f.stato_pagamento = 'completato';
        
        UPDATE progetti SET budget_raccolto = budget_totale WHERE id = NEW.progetto_id;
        
        IF budget_totale >= budget_richiesto THEN
            UPDATE progetti SET stato = 'chiuso' WHERE id = NEW.progetto_id;
        END IF;
    END IF;
END$$

CREATE TRIGGER tr_aggiorna_affidabilita
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE progetti_totali INT;
    DECLARE progetti_finanziati INT;
    DECLARE v_creatore_id INT;
    
    IF NEW.stato_pagamento = 'completato' THEN
        SELECT creatore_id INTO v_creatore_id FROM progetti WHERE id = NEW.progetto_id;
        
        SELECT COUNT(*) INTO progetti_totali FROM progetti WHERE creatore_id = v_creatore_id;
        
        SELECT COUNT(DISTINCT p.id) INTO progetti_finanziati
        FROM progetti p
        JOIN finanziamenti f ON p.id = f.progetto_id
        WHERE p.creatore_id = v_creatore_id AND f.stato_pagamento = 'completato';
        
        IF progetti_totali > 0 THEN
            UPDATE utenti SET affidabilita = (progetti_finanziati / progetti_totali) * 100
            WHERE id = v_creatore_id;
        END IF;
    END IF;
END$$

CREATE TRIGGER tr_aggiorna_affidabilita_progetto
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    DECLARE progetti_totali INT;
    DECLARE progetti_finanziati INT;
    
    IF NEW.creatore_id IS NOT NULL THEN
        SELECT COUNT(*) INTO progetti_totali FROM progetti WHERE creatore_id = NEW.creatore_id;
        
        SELECT COUNT(DISTINCT p.id) INTO progetti_finanziati
        FROM progetti p
        JOIN finanziamenti f ON p.id = f.progetto_id
        WHERE p.creatore_id = NEW.creatore_id AND f.stato_pagamento = 'completato';
        
        IF progetti_totali > 0 THEN
            UPDATE utenti SET affidabilita = (progetti_finanziati / progetti_totali) * 100
            WHERE id = NEW.creatore_id;
        ELSE
            UPDATE utenti SET affidabilita = 0 WHERE id = NEW.creatore_id;
        END IF;
    END IF;
END$$

CREATE TRIGGER tr_log_inserimento_utente
AFTER INSERT ON utenti
FOR EACH ROW
BEGIN
    INSERT INTO log_attivita (tipo_attivita, utente_id, descrizione)
    VALUES ('utente_registrato', NEW.id, CONCAT('Nuovo utente ', NEW.tipo_utente, ' registrato: ', NEW.nickname));
END$$

CREATE TRIGGER tr_log_inserimento_progetto
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    INSERT INTO log_attivita (tipo_attivita, utente_id, progetto_id, descrizione)
    VALUES ('progetto_creato', NEW.creatore_id, NEW.id, CONCAT('Creato progetto ', NEW.tipo_progetto, ': ', NEW.nome));
END$$

CREATE TRIGGER tr_log_finanziamento
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    IF NEW.stato_pagamento = 'completato' THEN
        INSERT INTO log_attivita (tipo_attivita, utente_id, progetto_id, descrizione)
        VALUES ('finanziamento_effettuato', NEW.utente_id, NEW.progetto_id, 
                CONCAT('Finanziamento €', NEW.importo, ' per progetto ID ', NEW.progetto_id));
    END IF;
END$$
DELIMITER ;

-- VISTE STATISTICHE
CREATE VIEW vista_top_creatori_affidabilita AS
SELECT u.nickname, u.affidabilita, u.nr_progetti
FROM utenti u
WHERE u.tipo_utente = 'creatore'
ORDER BY u.affidabilita DESC, u.nr_progetti DESC
LIMIT 3;

CREATE VIEW vista_progetti_vicini_completamento AS
SELECT 
    p.nome,
    p.budget_richiesto,
    p.budget_raccolto,
    (p.budget_richiesto - p.budget_raccolto) as differenza,
    ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale
FROM progetti p
WHERE p.stato = 'aperto' AND p.budget_raccolto < p.budget_richiesto
ORDER BY differenza ASC
LIMIT 3;

CREATE VIEW vista_top_finanziatori AS
SELECT 
    u.nickname,
    SUM(f.importo) as totale_finanziato,
    COUNT(f.id) as numero_finanziamenti
FROM utenti u
JOIN finanziamenti f ON u.id = f.utente_id
WHERE f.stato_pagamento = 'completato'
GROUP BY u.id, u.nickname
ORDER BY totale_finanziato DESC
LIMIT 3;

-- EVENTI AUTOMATICI
SET GLOBAL event_scheduler = ON;

DELIMITER $$
CREATE EVENT ev_chiudi_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE progetti SET stato = 'chiuso' 
    WHERE data_limite < CURDATE() AND stato = 'aperto';
    
    INSERT INTO log_attivita (tipo_attivita, descrizione)
    VALUES ('progetti_scaduti_chiusi', CONCAT('Progetti chiusi: ', ROW_COUNT()));
END$$
DELIMITER ;
