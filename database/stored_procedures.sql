-- BOSTARTER - Stored Procedures
-- Tutte le operazioni sui dati implementate via stored procedure

DELIMITER //

-- SP: Registrazione utente
DROP PROCEDURE IF EXISTS registra_utente//
CREATE PROCEDURE registra_utente(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(100),
    IN p_password_hash VARCHAR(255),
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100),
    IN p_anno_nascita INT,
    IN p_luogo_nascita VARCHAR(100),
    IN p_tipo_utente ENUM('standard', 'admin', 'creatore'),
    IN p_codice_sicurezza VARCHAR(50),
    OUT p_utente_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Errore durante la registrazione';
        SET p_utente_id = 0;
    END;
    
    START TRANSACTION;
    
    -- Verifica email univoca
    IF EXISTS(SELECT 1 FROM utenti WHERE email = p_email) THEN
        SET p_success = FALSE;
        SET p_message = 'Email già in uso';
        SET p_utente_id = 0;
        ROLLBACK;
    -- Verifica nickname univoco
    ELSEIF EXISTS(SELECT 1 FROM utenti WHERE nickname = p_nickname) THEN
        SET p_success = FALSE;
        SET p_message = 'Nickname già in uso';
        SET p_utente_id = 0;
        ROLLBACK;
    ELSE
        INSERT INTO utenti (
            email, nickname, password_hash, nome, cognome, 
            anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza,
            nr_progetti, affidabilita
        ) VALUES (
            p_email, p_nickname, p_password_hash, p_nome, p_cognome,
            p_anno_nascita, p_luogo_nascita, p_tipo_utente, p_codice_sicurezza,
            0, 0.00
        );
        
        SET p_utente_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Utente registrato con successo';
        COMMIT;
    END IF;
END //

-- SP: Login utente
DROP PROCEDURE IF EXISTS login_utente//
CREATE PROCEDURE login_utente(
    IN p_email VARCHAR(255),
    IN p_password_hash VARCHAR(255),
    IN p_codice_sicurezza VARCHAR(50),
    OUT p_utente_id INT,
    OUT p_tipo_utente VARCHAR(20),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_stored_password VARCHAR(255);
    DECLARE v_stored_codice VARCHAR(50);
    DECLARE v_tipo VARCHAR(20);
    
    -- Cerca utente
    SELECT id, password_hash, codice_sicurezza, tipo_utente
    INTO p_utente_id, v_stored_password, v_stored_codice, v_tipo
    FROM utenti 
    WHERE email = p_email AND attivo = TRUE;
    
    IF p_utente_id IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Credenziali non valide';
        SET p_utente_id = 0;
        SET p_tipo_utente = '';
    ELSEIF v_stored_password != p_password_hash THEN
        SET p_success = FALSE;
        SET p_message = 'Password non corretta';
        SET p_utente_id = 0;
        SET p_tipo_utente = '';
    ELSEIF v_tipo = 'admin' AND (p_codice_sicurezza IS NULL OR v_stored_codice != p_codice_sicurezza) THEN
        SET p_success = FALSE;
        SET p_message = 'Codice sicurezza richiesto per amministratori';
        SET p_utente_id = 0;
        SET p_tipo_utente = '';
    ELSE
        -- Aggiorna ultimo accesso
        UPDATE utenti SET last_access = NOW() WHERE id = p_utente_id;
        SET p_success = TRUE;
        SET p_message = 'Login effettuato con successo';
        SET p_tipo_utente = v_tipo;
    END IF;
END //

-- SP: Inserimento skill utente
DROP PROCEDURE IF EXISTS inserisci_skill_utente//
CREATE PROCEDURE inserisci_skill_utente(
    IN p_utente_id INT,
    IN p_competenza_nome VARCHAR(100),
    IN p_livello TINYINT
)
BEGIN
    DECLARE v_competenza_id INT;
    
    -- Trova competenza esistente
    SELECT id INTO v_competenza_id FROM competenze WHERE nome = p_competenza_nome;
    
    IF v_competenza_id IS NOT NULL THEN
        -- Inserisci o aggiorna skill
        INSERT INTO skill_utente (utente_id, competenza_id, livello)
        VALUES (p_utente_id, v_competenza_id, p_livello)
        ON DUPLICATE KEY UPDATE livello = p_livello;
        
        SELECT 'Skill aggiornata con successo' AS messaggio;
    ELSE
        SELECT 'Competenza non trovata' AS messaggio;
    END IF;
END //

-- SP: Inserimento progetto
DROP PROCEDURE IF EXISTS inserisci_progetto//
CREATE PROCEDURE inserisci_progetto(
    IN p_nome VARCHAR(200),
    IN p_descrizione TEXT,
    IN p_budget_richiesto DECIMAL(12,2),
    IN p_data_limite DATETIME,
    IN p_creatore_id INT,
    IN p_tipo_progetto ENUM('hardware', 'software'),
    IN p_foto TEXT,
    OUT p_progetto_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Errore durante creazione progetto';
        SET p_progetto_id = 0;
    END;
    
    START TRANSACTION;
    
    -- Verifica che il creatore sia effettivamente un creatore
    IF NOT EXISTS(SELECT 1 FROM utenti WHERE id = p_creatore_id AND tipo_utente = 'creatore') THEN
        SET p_success = FALSE;
        SET p_message = 'Solo i creatori possono inserire progetti';
        SET p_progetto_id = 0;
        ROLLBACK;
    -- Verifica nome univoco
    ELSEIF EXISTS(SELECT 1 FROM progetti WHERE nome = p_nome) THEN
        SET p_success = FALSE;
        SET p_message = 'Nome progetto già esistente';
        SET p_progetto_id = 0;
        ROLLBACK;
    ELSE
        INSERT INTO progetti (
            nome, descrizione, budget_richiesto, data_limite,
            creatore_id, tipo_progetto, foto
        ) VALUES (
            p_nome, p_descrizione, p_budget_richiesto, p_data_limite,
            p_creatore_id, p_tipo_progetto, p_foto
        );
        
        SET p_progetto_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Progetto creato con successo';
        COMMIT;
    END IF;
END //

-- SP: Finanziamento progetto
DROP PROCEDURE IF EXISTS finanzia_progetto//
CREATE PROCEDURE finanzia_progetto(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_importo DECIMAL(10,2),
    IN p_reward_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_stato_progetto VARCHAR(10);
    DECLARE v_budget_richiesto DECIMAL(12,2);
    DECLARE v_totale_raccolto DECIMAL(12,2);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Errore durante il finanziamento';
    END;
    
    START TRANSACTION;
    
    -- Verifica che il progetto sia aperto
    SELECT stato, budget_richiesto INTO v_stato_progetto, v_budget_richiesto
    FROM progetti WHERE id = p_progetto_id;
    
    IF v_stato_progetto != 'aperto' THEN
        SET p_success = FALSE;
        SET p_message = 'Il progetto non è più aperto ai finanziamenti';
        ROLLBACK;
    ELSE
        -- Inserisci finanziamento
        INSERT INTO finanziamenti (utente_id, progetto_id, importo, reward_id)
        VALUES (p_utente_id, p_progetto_id, p_importo, p_reward_id);
        
        -- Calcola nuovo totale
        SELECT COALESCE(SUM(importo), 0) INTO v_totale_raccolto
        FROM finanziamenti WHERE progetto_id = p_progetto_id;
        
        SET p_success = TRUE;
        SET p_message = CONCAT('Finanziamento di €', p_importo, ' effettuato con successo');
        
        -- Il trigger si occuperà di chiudere il progetto se necessario
        COMMIT;
    END IF;
END //

-- SP: Inserimento commento
DROP PROCEDURE IF EXISTS inserisci_commento//
CREATE PROCEDURE inserisci_commento(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_testo TEXT,
    OUT p_commento_id INT
)
BEGIN
    INSERT INTO commenti (utente_id, progetto_id, testo)
    VALUES (p_utente_id, p_progetto_id, p_testo);
    SET p_commento_id = LAST_INSERT_ID();
END //

-- SP: Inserimento risposta commento (solo creatore)
DROP PROCEDURE IF EXISTS inserisci_risposta_commento//
CREATE PROCEDURE inserisci_risposta_commento(
    IN p_commento_id INT,
    IN p_creatore_id INT,
    IN p_testo TEXT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
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
        SET p_success = TRUE;
        SET p_message = 'Risposta inserita con successo';
    ELSE
        SET p_success = FALSE;
        SET p_message = 'Solo il creatore del progetto può rispondere';
    END IF;
END //

-- SP: Candidatura progetto software (con controllo skill)
DROP PROCEDURE IF EXISTS candidati_progetto//
CREATE PROCEDURE candidati_progetto(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_profilo_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE skill_match_ok BOOLEAN DEFAULT TRUE;
    DECLARE done BOOLEAN DEFAULT FALSE;
    DECLARE v_competenza_id INT;
    DECLARE v_livello_richiesto TINYINT;
    DECLARE v_livello_utente TINYINT;
    DECLARE v_tipo_progetto VARCHAR(10);
    
    -- Cursor per le skill richieste
    DECLARE skill_cursor CURSOR FOR
        SELECT competenza_id, livello_richiesto
        FROM skill_richieste_profilo
        WHERE profilo_id = p_profilo_id;
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Verifica che sia un progetto software
    SELECT tipo_progetto INTO v_tipo_progetto
    FROM progetti WHERE id = p_progetto_id;
    
    IF v_tipo_progetto != 'software' THEN
        SET p_success = FALSE;
        SET p_message = 'Le candidature sono solo per progetti software';
    ELSE
        -- Controlla skills
        check_skills: BEGIN
            OPEN skill_cursor;
            skill_loop: LOOP
                FETCH skill_cursor INTO v_competenza_id, v_livello_richiesto;
                IF done THEN
                    LEAVE skill_loop;
                END IF;
                
                -- Controlla se l'utente ha la skill richiesta
                SELECT livello INTO v_livello_utente
                FROM skill_utente
                WHERE utente_id = p_utente_id AND competenza_id = v_competenza_id;
                
                IF v_livello_utente IS NULL OR v_livello_utente < v_livello_richiesto THEN
                    SET skill_match_ok = FALSE;
                    LEAVE check_skills;
                END IF;
            END LOOP;
            CLOSE skill_cursor;
        END check_skills;
        
        -- Se skill match OK, inserisci candidatura
        IF skill_match_ok THEN
            INSERT INTO candidature (utente_id, progetto_id, profilo_id)
            VALUES (p_utente_id, p_progetto_id, p_profilo_id);
            SET p_success = TRUE;
            SET p_message = 'Candidatura inserita con successo';
        ELSE
            SET p_success = FALSE;
            SET p_message = 'Skill insufficienti per questo profilo';
        END IF;
    END IF;
END //

-- SP: Gestione candidatura (solo creatore)
DROP PROCEDURE IF EXISTS gestisci_candidatura//
CREATE PROCEDURE gestisci_candidatura(
    IN p_candidatura_id INT,
    IN p_creatore_id INT,
    IN p_stato ENUM('accepted', 'rejected'),
    IN p_note TEXT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
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
        SET stato = p_stato, data_risposta = NOW(), note_creatore = p_note
        WHERE id = p_candidatura_id;
        SET p_success = TRUE;
        SET p_message = 'Candidatura aggiornata con successo';
    ELSE
        SET p_success = FALSE;
        SET p_message = 'Solo il creatore può gestire le candidature';
    END IF;
END //

-- SP: Inserimento competenza (solo admin)
DROP PROCEDURE IF EXISTS inserisci_competenza//
CREATE PROCEDURE inserisci_competenza(
    IN p_nome VARCHAR(100),
    IN p_descrizione TEXT,
    IN p_categoria VARCHAR(50),
    OUT p_competenza_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_success = FALSE;
        SET p_message = 'Errore durante inserimento competenza';
        SET p_competenza_id = 0;
    END;
    
    IF EXISTS(SELECT 1 FROM competenze WHERE nome = p_nome) THEN
        SET p_success = FALSE;
        SET p_message = 'Competenza già esistente';
        SET p_competenza_id = 0;
    ELSE
        INSERT INTO competenze (nome, descrizione, categoria) 
        VALUES (p_nome, p_descrizione, p_categoria);
        SET p_competenza_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Competenza inserita con successo';
    END IF;
END //

-- SP: Inserimento reward
DROP PROCEDURE IF EXISTS inserisci_reward//
CREATE PROCEDURE inserisci_reward(
    IN p_codice VARCHAR(50),
    IN p_descrizione TEXT,
    IN p_foto VARCHAR(500),
    IN p_progetto_id INT,
    IN p_importo_minimo DECIMAL(10,2),
    OUT p_reward_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_success = FALSE;
        SET p_message = 'Errore durante inserimento reward';
        SET p_reward_id = 0;
    END;
    
    IF EXISTS(SELECT 1 FROM rewards WHERE codice = p_codice) THEN
        SET p_success = FALSE;
        SET p_message = 'Codice reward già esistente';
        SET p_reward_id = 0;
    ELSE
        INSERT INTO rewards (codice, descrizione, foto, progetto_id, importo_minimo)
        VALUES (p_codice, p_descrizione, p_foto, p_progetto_id, p_importo_minimo);
        SET p_reward_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Reward inserita con successo';
    END IF;
END //

DELIMITER ;
