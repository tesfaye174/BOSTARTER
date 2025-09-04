-- Stored Procedure Aggiuntive per BOSTARTER
USE bostarter;

DELIMITER //

-- Gestione candidatura
CREATE PROCEDURE sp_gestisci_candidatura(
    IN p_id_candidatura INT,
    IN p_id_creator INT,
    IN p_stato ENUM('ACCETTATA', 'RIFIUTATA')
)
BEGIN
    DECLARE v_id_progetto INT;
    DECLARE v_id_creator INT;
    
    SELECT p.id_progetto, p.id_creator
    INTO v_id_progetto, v_id_creator
    FROM candidature c
    JOIN progetti p ON c.id_progetto = p.id_progetto
    WHERE c.id_candidatura = p_id_candidatura;
    
    IF v_id_creator != p_id_creator THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Non autorizzato a gestire questa candidatura';
    END IF;
    
    UPDATE candidature
    SET stato = p_stato
    WHERE id_candidatura = p_id_candidatura;
END //

-- Aggiunta ricompensa
CREATE PROCEDURE sp_aggiungi_ricompensa(
    IN p_id_progetto INT,
    IN p_id_creator INT,
    IN p_titolo VARCHAR(100),
    IN p_descrizione TEXT,
    IN p_importo_minimo DECIMAL(10,2),
    IN p_quantita INT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM progetti
        WHERE id_progetto = p_id_progetto
        AND id_creator = p_id_creator
        AND stato = 'APERTO'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Non autorizzato ad aggiungere ricompense';
    END IF;
    
    INSERT INTO ricompense (
        id_progetto, titolo, descrizione, 
        importo_minimo, quantita_disponibile
    )
    VALUES (
        p_id_progetto, p_titolo, p_descrizione,
        p_importo_minimo, p_quantita
    );
END //

-- Aggiunta competenza (admin)
CREATE PROCEDURE sp_admin_aggiungi_competenza(
    IN p_id_admin INT,
    IN p_nome VARCHAR(50),
    IN p_descrizione TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM utenti
        WHERE id_utente = p_id_admin
        AND is_admin = TRUE
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Operazione riservata agli admin';
    END IF;
    
    INSERT INTO competenze (nome, descrizione, creato_da)
    VALUES (p_nome, p_descrizione, p_id_admin);
END //

-- Aggiunta categoria
CREATE PROCEDURE sp_aggiungi_categoria(
    IN p_id_admin INT,
    IN p_nome VARCHAR(50),
    IN p_descrizione TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM utenti
        WHERE id_utente = p_id_admin
        AND is_admin = TRUE
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Operazione riservata agli admin';
    END IF;
    
    INSERT INTO categorie (nome, descrizione)
    VALUES (p_nome, p_descrizione);
END //

-- Aggiornamento progetto
CREATE PROCEDURE sp_aggiorna_progetto(
    IN p_id_progetto INT,
    IN p_id_creator INT,
    IN p_titolo VARCHAR(100),
    IN p_descrizione TEXT,
    IN p_url_immagine VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM progetti
        WHERE id_progetto = p_id_progetto
        AND id_creator = p_id_creator
        AND stato = 'APERTO'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Non autorizzato a modificare il progetto';
    END IF;
    
    UPDATE progetti
    SET titolo = p_titolo,
        descrizione = p_descrizione,
        url_immagine = p_url_immagine,
        data_modifica = CURRENT_TIMESTAMP
    WHERE id_progetto = p_id_progetto;
END //

-- Verifica competenza utente
CREATE PROCEDURE sp_verifica_competenza(
    IN p_id_admin INT,
    IN p_id_utente INT,
    IN p_id_competenza INT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM utenti
        WHERE id_utente = p_id_admin
        AND is_admin = TRUE
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Operazione riservata agli admin';
    END IF;
    
    UPDATE competenze_utenti
    SET verificata = TRUE
    WHERE id_utente = p_id_utente
    AND id_competenza = p_id_competenza;
END //

DELIMITER ;
