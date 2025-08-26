-- =================================================================
-- BOSTARTER STORED PROCEDURES v2.0
-- Procedure per gestione completa del sistema
-- =================================================================

USE bostarter;

DELIMITER $$

-- REGISTRAZIONE E LOGIN
CREATE PROCEDURE sp_registra_utente(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(100),
    IN p_password VARCHAR(255),
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100),
    IN p_anno_nascita YEAR,
    IN p_luogo_nascita VARCHAR(100),
    IN p_tipo_utente VARCHAR(20),
    IN p_codice_sicurezza VARCHAR(50),
    OUT p_utente_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_success = FALSE;
        SET p_message = 'Errore durante la registrazione';
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    IF EXISTS (SELECT 1 FROM utenti WHERE email = p_email OR nickname = p_nickname) THEN
        SET p_success = FALSE;
        SET p_message = 'Email o nickname già esistenti';
        ROLLBACK;
    ELSE
        INSERT INTO utenti (email, nickname, password, nome, cognome, 
                          anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza)
        VALUES (p_email, p_nickname, p_password, p_nome, p_cognome, 
                p_anno_nascita, p_luogo_nascita, p_tipo_utente, p_codice_sicurezza);
        
        SET p_utente_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Registrazione completata';
        COMMIT;
    END IF;
END$$

CREATE PROCEDURE sp_login_utente(
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_codice_sicurezza VARCHAR(50),
    OUT p_utente_id INT,
    OUT p_user_data JSON,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_password VARCHAR(255);
    DECLARE v_tipo_utente VARCHAR(20);
    DECLARE v_codice_sicurezza VARCHAR(50);
    DECLARE v_stato VARCHAR(20);
    DECLARE v_nickname VARCHAR(100);
    
    SELECT id, password, tipo_utente, codice_sicurezza, stato, nickname
    INTO p_utente_id, v_password, v_tipo_utente, v_codice_sicurezza, v_stato, v_nickname
    FROM utenti WHERE email = p_email;
    
    IF p_utente_id IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Credenziali non valide';
    ELSEIF v_stato != 'attivo' THEN
        SET p_success = FALSE;
        SET p_message = 'Account sospeso';
    ELSEIF v_tipo_utente = 'amministratore' AND p_codice_sicurezza != v_codice_sicurezza THEN
        SET p_success = FALSE;
        SET p_message = 'Codice sicurezza richiesto';
    ELSEIF v_password = p_password THEN
        SET p_success = TRUE;
        SET p_message = 'Login effettuato';
        SET p_user_data = JSON_OBJECT('id', p_utente_id, 'nickname', v_nickname, 'tipo', v_tipo_utente);
        UPDATE utenti SET last_access = NOW() WHERE id = p_utente_id;
    ELSE
        SET p_success = FALSE;
        SET p_message = 'Credenziali non valide';
    END IF;
END$$

-- GESTIONE PROGETTI
CREATE PROCEDURE sp_crea_progetto(
    IN p_nome VARCHAR(200),
    IN p_descrizione TEXT,
    IN p_creatore_id INT,
    IN p_tipo VARCHAR(20),
    IN p_budget_richiesto DECIMAL(12,2),
    IN p_data_limite DATE,
    OUT p_progetto_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_success = FALSE;
        SET p_message = 'Errore durante creazione progetto';
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_creatore_id AND tipo_utente = 'creatore') THEN
        SET p_success = FALSE;
        SET p_message = 'Solo i creatori possono creare progetti';
        ROLLBACK;
    ELSEIF p_data_limite <= CURDATE() THEN
        SET p_success = FALSE;
        SET p_message = 'Data limite deve essere futura';
        ROLLBACK;
    ELSE
        INSERT INTO progetti (nome, descrizione, creatore_id, tipo_progetto, budget_richiesto, data_limite)
        VALUES (p_nome, p_descrizione, p_creatore_id, p_tipo_progetto, p_budget_richiesto, p_data_limite);
        
        SET p_progetto_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Progetto creato con successo';
        COMMIT;
    END IF;
END$$

CREATE PROCEDURE sp_finanzia_progetto(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_reward_id INT,
    IN p_importo DECIMAL(10,2),
    OUT p_finanziamento_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_stato_progetto VARCHAR(20);
    
    SELECT stato INTO v_stato_progetto FROM progetti WHERE id = p_progetto_id;
    
    IF v_stato_progetto != 'aperto' THEN
        SET p_success = FALSE;
        SET p_message = 'Progetto non aperto';
    ELSEIF p_importo <= 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Importo non valido';
    ELSE
        INSERT INTO finanziamenti (utente_id, progetto_id, reward_id, importo, stato_pagamento)
        VALUES (p_utente_id, p_progetto_id, p_reward_id, p_importo, 'completato');
        
        SET p_finanziamento_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Finanziamento completato';
    END IF;
END$$

-- GESTIONE SKILL E CANDIDATURE
CREATE PROCEDURE sp_inserisci_skill_utente(
    IN p_utente_id INT,
    IN p_competenza_id INT,
    IN p_livello TINYINT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    IF p_livello < 0 OR p_livello > 5 THEN
        SET p_success = FALSE;
        SET p_message = 'Livello deve essere 0-5';
    ELSE
        INSERT INTO skill_utente (utente_id, competenza_id, livello)
        VALUES (p_utente_id, p_competenza_id, p_livello)
        ON DUPLICATE KEY UPDATE livello = p_livello;
        
        SET p_success = TRUE;
        SET p_message = 'Skill inserita';
    END IF;
END$$

CREATE PROCEDURE sp_inserisci_candidatura(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_profilo_id INT,
    OUT p_candidatura_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_skill_insufficienti INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_skill_insufficienti
    FROM skill_richieste_profilo srp
    LEFT JOIN skill_utente su ON (srp.competenza_id = su.competenza_id AND su.utente_id = p_utente_id AND su.livello >= srp.livello)
    WHERE srp.profilo_id = p_profilo_id AND su.id IS NULL;
    
    IF v_skill_insufficienti > 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Skill insufficienti';
    ELSE
        INSERT INTO candidature (utente_id, progetto_id, profilo_id)
        VALUES (p_utente_id, p_progetto_id, p_profilo_id);
        
        SET p_candidatura_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Candidatura inserita';
    END IF;
END$$

CREATE PROCEDURE sp_gestisci_candidatura(
    IN p_candidatura_id INT,
    IN p_creatore_id INT,
    IN p_stato VARCHAR(20),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_progetto_creatore_id INT;
    
    SELECT p.creatore_id INTO v_progetto_creatore_id
    FROM progetti p
    JOIN candidature c ON p.id = c.progetto_id
    WHERE c.id = p_candidatura_id;
    
    IF v_progetto_creatore_id != p_creatore_id THEN
        SET p_success = FALSE;
        SET p_message = 'Non autorizzato';
    ELSE
        UPDATE candidature SET stato = p_stato, data_risposta = NOW() WHERE id = p_candidatura_id;
        SET p_success = TRUE;
        SET p_message = CONCAT('Candidatura ', p_stato);
    END IF;
END$$

-- GESTIONE COMPETENZE (SOLO AMMINISTRATORI)
CREATE PROCEDURE sp_inserisci_competenza(
    IN p_utente_id INT,
    IN p_nome VARCHAR(100),
    IN p_descrizione TEXT,
    OUT p_competenza_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_tipo_utente VARCHAR(20);
    
    SELECT tipo_utente INTO v_tipo_utente FROM utenti WHERE id = p_utente_id;
    
    IF v_tipo_utente != 'amministratore' THEN
        SET p_success = FALSE;
        SET p_message = 'Solo gli amministratori possono inserire competenze';
    ELSE
        INSERT INTO competenze (nome, descrizione) VALUES (p_nome, p_descrizione);
        SET p_competenza_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Competenza inserita';
    END IF;
END$$

-- GESTIONE REWARD (SOLO CREATORI)
CREATE PROCEDURE sp_inserisci_reward(
    IN p_creatore_id INT,
    IN p_progetto_id INT,
    IN p_codice VARCHAR(50),
    IN p_descrizione TEXT,
    IN p_importo_minimo DECIMAL(10,2),
    OUT p_reward_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_progetto_creatore_id INT;
    
    SELECT creatore_id INTO v_progetto_creatore_id FROM progetti WHERE id = p_progetto_id;
    
    IF v_progetto_creatore_id != p_creatore_id THEN
        SET p_success = FALSE;
        SET p_message = 'Solo il creatore può inserire reward';
    ELSE
        INSERT INTO reward (codice, progetto_id, descrizione, importo_minimo)
        VALUES (p_codice, p_progetto_id, p_descrizione, p_importo_minimo);
        
        SET p_reward_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Reward inserita';
    END IF;
END$$

-- GESTIONE COMMENTI
CREATE PROCEDURE sp_inserisci_commento(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_testo TEXT,
    OUT p_commento_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    INSERT INTO commenti (utente_id, progetto_id, testo)
    VALUES (p_utente_id, p_progetto_id, p_testo);
    
    SET p_commento_id = LAST_INSERT_ID();
    SET p_success = TRUE;
    SET p_message = 'Commento inserito';
END$$

-- GESTIONE RISPOSTE AI COMMENTI (SOLO CREATORI)
CREATE PROCEDURE sp_inserisci_risposta_commento(
    IN p_creatore_id INT,
    IN p_commento_id INT,
    IN p_testo TEXT,
    OUT p_risposta_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_progetto_creatore_id INT;
    
    SELECT p.creatore_id INTO v_progetto_creatore_id
    FROM progetti p
    JOIN commenti c ON p.id = c.progetto_id
    WHERE c.id = p_commento_id;
    
    IF v_progetto_creatore_id != p_creatore_id THEN
        SET p_success = FALSE;
        SET p_message = 'Solo il creatore può rispondere';
    ELSE
        INSERT INTO risposte_commenti (commento_id, creatore_id, testo)
        VALUES (p_commento_id, p_creatore_id, p_testo);
        
        SET p_risposta_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Risposta inserita';
    END IF;
END$$

-- GESTIONE PROFILI SOFTWARE (SOLO CREATORI)
CREATE PROCEDURE sp_inserisci_profilo_software(
    IN p_creatore_id INT,
    IN p_progetto_id INT,
    IN p_nome VARCHAR(200),
    IN p_descrizione TEXT,
    IN p_max_contributori INT,
    OUT p_profilo_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_progetto_creatore_id INT;
    DECLARE v_tipo_progetto VARCHAR(20);
    
    SELECT creatore_id, tipo_progetto INTO v_progetto_creatore_id, v_tipo_progetto
    FROM progetti WHERE id = p_progetto_id;
    
    IF v_progetto_creatore_id != p_creatore_id THEN
        SET p_success = FALSE;
        SET p_message = 'Solo il creatore può inserire profili';
    ELSEIF v_tipo_progetto != 'software' THEN
        SET p_success = FALSE;
        SET p_message = 'Solo per progetti software';
    ELSE
        INSERT INTO profili_software (progetto_id, nome, descrizione, max_contributori)
        VALUES (p_progetto_id, p_nome, p_descrizione, p_max_contributori);
        
        SET p_profilo_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Profilo inserito';
    END IF;
END$$

-- GESTIONE SKILL RICHIESTE PER PROFILO
CREATE PROCEDURE sp_inserisci_skill_richiesta_profilo(
    IN p_profilo_id INT,
    IN p_competenza_id INT,
    IN p_livello TINYINT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    IF p_livello < 0 OR p_livello > 5 THEN
        SET p_success = FALSE;
        SET p_message = 'Livello deve essere 0-5';
    ELSE
        INSERT INTO skill_richieste_profilo (profilo_id, competenza_id, livello)
        VALUES (p_profilo_id, p_competenza_id, p_livello);
        
        SET p_success = TRUE;
        SET p_message = 'Skill richiesta inserita';
    END IF;
END$$

-- GESTIONE COMPONENTI HARDWARE (SOLO CREATORI)
CREATE PROCEDURE sp_inserisci_componente_hardware(
    IN p_creatore_id INT,
    IN p_progetto_id INT,
    IN p_nome VARCHAR(200),
    IN p_descrizione TEXT,
    IN p_prezzo DECIMAL(10,2),
    IN p_quantita INT,
    OUT p_componente_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_progetto_creatore_id INT;
    DECLARE v_tipo_progetto VARCHAR(20);
    
    SELECT creatore_id, tipo_progetto INTO v_progetto_creatore_id, v_tipo_progetto
    FROM progetti WHERE id = p_progetto_id;
    
    IF v_progetto_creatore_id != p_creatore_id THEN
        SET p_success = FALSE;
        SET p_message = 'Solo il creatore può inserire componenti';
    ELSEIF v_tipo_progetto != 'hardware' THEN
        SET p_success = FALSE;
        SET p_message = 'Solo per progetti hardware';
    ELSEIF p_quantita <= 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Quantità deve essere maggiore di 0';
    ELSE
        INSERT INTO componenti_hardware (progetto_id, nome, descrizione, prezzo, quantita)
        VALUES (p_progetto_id, p_nome, p_descrizione, p_prezzo, p_quantita);
        
        SET p_componente_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Componente inserito';
    END IF;
END$$

-- UTILITY
CREATE PROCEDURE sp_visualizza_progetti(
    IN p_stato VARCHAR(20),
    IN p_tipo_progetto VARCHAR(20),
    IN p_limit INT
)
BEGIN
    SELECT 
        p.id, p.nome, p.descrizione, p.budget_richiesto, p.budget_raccolto,
        p.data_limite, p.stato, p.tipo_progetto, u.nickname as creatore,
        ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale
    FROM progetti p
    JOIN utenti u ON p.creatore_id = u.id
    WHERE (p_stato IS NULL OR p.stato = p_stato)
    AND (p_tipo_progetto IS NULL OR p.tipo_progetto = p_tipo_progetto)
    ORDER BY p.data_inserimento DESC
    LIMIT COALESCE(p_limit, 50);
END$$

-- UTILITY E RICERCHE
CREATE PROCEDURE sp_ricerca_progetti(
    IN p_nome VARCHAR(200),
    IN p_tipo_progetto VARCHAR(20),
    IN p_stato VARCHAR(20),
    IN p_limit INT
)
BEGIN
    SELECT 
        p.id, p.nome, p.descrizione, p.budget_richiesto, p.budget_raccolto,
        p.data_limite, p.stato, p.tipo_progetto, u.nickname as creatore,
        ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale_completamento
    FROM progetti p
    JOIN utenti u ON p.creatore_id = u.id
    WHERE (p_nome IS NULL OR p.nome LIKE CONCAT('%', p_nome, '%'))
    AND (p_tipo_progetto IS NULL OR p.tipo_progetto = p_tipo_progetto)
    AND (p_stato IS NULL OR p.stato = p_stato)
    ORDER BY p.data_inserimento DESC
    LIMIT COALESCE(p_limit, 20);
END$$

CREATE PROCEDURE sp_get_progetti_utente(
    IN p_utente_id INT,
    IN p_tipo VARCHAR(20)
)
BEGIN
    IF p_tipo = 'creati' THEN
        SELECT p.*, u.nickname as creatore
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        WHERE p.creatore_id = p_utente_id
        ORDER BY p.data_inserimento DESC;
    ELSEIF p_tipo = 'finanziati' THEN
        SELECT DISTINCT p.*, u.nickname as creatore, SUM(f.importo) as totale_finanziato
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        JOIN finanziamenti f ON p.id = f.progetto_id
        WHERE f.utente_id = p_utente_id AND f.stato_pagamento = 'completato'
        GROUP BY p.id
        ORDER BY totale_finanziato DESC;
    END IF;
END$$

DELIMITER ;
