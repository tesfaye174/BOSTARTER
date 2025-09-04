-- Stored Procedure per BOSTARTER
USE bostarter;

DELIMITER //

-- Registrazione utente
CREATE PROCEDURE sp_registra_utente(
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_nickname VARCHAR(50),
    IN p_nome_completo VARCHAR(100),
    IN p_is_creator BOOLEAN
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore nella registrazione utente';
    END;

    START TRANSACTION;
    
    IF EXISTS (SELECT 1 FROM utenti WHERE email = p_email) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Email già registrata';
    END IF;
    
    IF EXISTS (SELECT 1 FROM utenti WHERE nickname = p_nickname) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Nickname già in uso';
    END IF;
    
    INSERT INTO utenti (email, password_hash, nickname, nome_completo, is_creator)
    VALUES (p_email, p_password, p_nickname, p_nome_completo, p_is_creator);
    
    COMMIT;
END //

-- Creazione nuovo progetto
CREATE PROCEDURE sp_crea_progetto(
    IN p_id_creator INT,
    IN p_titolo VARCHAR(100),
    IN p_descrizione TEXT,
    IN p_budget DECIMAL(10,2),
    IN p_data_scadenza DATE,
    IN p_url_immagine VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore nella creazione del progetto';
    END;

    START TRANSACTION;
    
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id_utente = p_id_creator AND is_creator = TRUE) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Utente non autorizzato a creare progetti';
    END IF;
    
    IF p_data_scadenza <= CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La data di scadenza deve essere futura';
    END IF;
    
    INSERT INTO progetti (id_creator, titolo, descrizione, budget, data_scadenza, url_immagine)
    VALUES (p_id_creator, p_titolo, p_descrizione, p_budget, p_data_scadenza, p_url_immagine);
    
    COMMIT;
END //

-- Aggiunta competenza utente
CREATE PROCEDURE sp_aggiungi_competenza_utente(
    IN p_id_utente INT,
    IN p_id_competenza INT,
    IN p_livello INT
)
BEGIN
    IF p_livello < 1 OR p_livello > 5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Livello di competenza deve essere tra 1 e 5';
    END IF;

    INSERT INTO competenze_utenti (id_utente, id_competenza, livello)
    VALUES (p_id_utente, p_id_competenza, p_livello)
    ON DUPLICATE KEY UPDATE livello = p_livello;
END //

-- Aggiunta commento
CREATE PROCEDURE sp_aggiungi_commento(
    IN p_id_progetto INT,
    IN p_id_utente INT,
    IN p_contenuto TEXT,
    IN p_id_padre INT
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM progetti WHERE id_progetto = p_id_progetto) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Progetto non trovato';
    END IF;

    IF p_id_padre IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM commenti 
        WHERE id_commento = p_id_padre 
        AND id_progetto = p_id_progetto
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Commento padre non trovato';
    END IF;

    INSERT INTO commenti (id_progetto, id_utente, contenuto, id_padre)
    VALUES (p_id_progetto, p_id_utente, p_contenuto, p_id_padre);
END //

-- Effettua finanziamento
CREATE PROCEDURE sp_effettua_finanziamento(
    IN p_id_progetto INT,
    IN p_id_finanziatore INT,
    IN p_importo DECIMAL(10,2),
    IN p_id_ricompensa INT
)
BEGIN
    DECLARE v_stato_progetto VARCHAR(20);
    DECLARE v_importo_attuale DECIMAL(10,2);
    DECLARE v_budget DECIMAL(10,2);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore nel finanziamento';
    END;

    START TRANSACTION;
    
    SELECT stato, importo_attuale, budget 
    INTO v_stato_progetto, v_importo_attuale, v_budget
    FROM progetti 
    WHERE id_progetto = p_id_progetto
    FOR UPDATE;
    
    IF v_stato_progetto != 'APERTO' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Il progetto non è aperto ai finanziamenti';
    END IF;
    
    IF p_id_ricompensa IS NOT NULL THEN
        IF NOT EXISTS (
            SELECT 1 FROM ricompense 
            WHERE id_ricompensa = p_id_ricompensa 
            AND id_progetto = p_id_progetto
            AND importo_minimo <= p_importo
            AND (quantita_disponibile IS NULL OR quantita_richiesta < quantita_disponibile)
        ) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ricompensa non disponibile o importo insufficiente';
        END IF;
    END IF;
    
    INSERT INTO finanziamenti (id_progetto, id_finanziatore, importo, id_ricompensa)
    VALUES (p_id_progetto, p_id_finanziatore, p_importo, p_id_ricompensa);
    
    UPDATE progetti 
    SET importo_attuale = importo_attuale + p_importo
    WHERE id_progetto = p_id_progetto;
    
    IF p_id_ricompensa IS NOT NULL THEN
        UPDATE ricompense
        SET quantita_richiesta = quantita_richiesta + 1
        WHERE id_ricompensa = p_id_ricompensa;
    END IF;
    
    COMMIT;
END //

-- Login utente
CREATE PROCEDURE sp_login_utente(
    IN p_email VARCHAR(255),
    IN p_password_hash VARCHAR(255),
    OUT p_id_utente INT,
    OUT p_is_admin BOOLEAN,
    OUT p_codice_sicurezza VARCHAR(6)
)
BEGIN
    SELECT id_utente, is_admin, codice_sicurezza
    INTO p_id_utente, p_is_admin, p_codice_sicurezza
    FROM utenti
    WHERE email = p_email
    AND password_hash = p_password_hash;
    
    IF p_id_utente IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Credenziali non valide';
    END IF;
END //

DELIMITER ;
