-- Migliorie alle Stored Procedure BOSTARTER
USE bostarter;

DELIMITER //

-- Procedura migliorata per il login con tracking accessi
CREATE PROCEDURE sp_login_utente_v2(
    IN p_email VARCHAR(255),
    IN p_password_hash VARCHAR(255),
    OUT p_id_utente INT,
    OUT p_is_admin BOOLEAN,
    OUT p_codice_sicurezza VARCHAR(6)
)
BEGIN
    DECLARE v_tentativi_falliti INT;
    DECLARE v_ultimo_tentativo TIMESTAMP;
    
    -- Verifica blocco account
    SELECT COUNT(*) INTO v_tentativi_falliti
    FROM log_accessi
    WHERE email = p_email 
    AND successo = FALSE
    AND data_tentativo > DATE_SUB(NOW(), INTERVAL 30 MINUTE);
    
    IF v_tentativi_falliti >= 5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Account temporaneamente bloccato. Riprova più tardi.';
    END IF;

    -- Verifica credenziali
    SELECT id_utente, is_admin, codice_sicurezza
    INTO p_id_utente, p_is_admin, p_codice_sicurezza
    FROM utenti
    WHERE email = p_email
    AND password_hash = p_password_hash;
    
    IF p_id_utente IS NULL THEN
        -- Registra tentativo fallito
        INSERT INTO log_accessi (email, successo, ip_address)
        VALUES (p_email, FALSE, CONNECTION_ID());
        
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Credenziali non valide';
    ELSE
        -- Aggiorna ultimo accesso e resetta tentativi
        UPDATE utenti 
        SET ultimo_accesso = CURRENT_TIMESTAMP
        WHERE id_utente = p_id_utente;
        
        -- Registra accesso riuscito
        INSERT INTO log_accessi (email, successo, ip_address, id_utente)
        VALUES (p_email, TRUE, CONNECTION_ID(), p_id_utente);
    END IF;
END //

-- Procedura migliorata per la creazione progetto con validazione avanzata
CREATE PROCEDURE sp_crea_progetto_v2(
    IN p_id_creator INT,
    IN p_titolo VARCHAR(100),
    IN p_descrizione TEXT,
    IN p_budget DECIMAL(10,2),
    IN p_data_scadenza DATE,
    IN p_url_immagine VARCHAR(255),
    IN p_categorie JSON,
    IN p_competenze_richieste JSON
)
BEGIN
    DECLARE v_id_progetto INT;
    DECLARE v_categoria JSON;
    DECLARE v_competenza JSON;
    DECLARE i INT DEFAULT 0;
    DECLARE j INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore nella creazione del progetto';
    END;

    START TRANSACTION;
    
    -- Validazioni
    IF NOT EXISTS (
        SELECT 1 FROM utenti 
        WHERE id_utente = p_id_creator 
        AND is_creator = TRUE
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Utente non autorizzato a creare progetti';
    END IF;
    
    IF p_data_scadenza <= CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La data di scadenza deve essere futura';
    END IF;
    
    IF p_budget <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Il budget deve essere positivo';
    END IF;
    
    -- Inserisci progetto
    INSERT INTO progetti (
        id_creator, 
        titolo, 
        descrizione, 
        budget, 
        data_scadenza, 
        url_immagine
    )
    VALUES (
        p_id_creator, 
        p_titolo, 
        p_descrizione, 
        p_budget, 
        p_data_scadenza, 
        p_url_immagine
    );
    
    SET v_id_progetto = LAST_INSERT_ID();
    
    -- Inserisci categorie
    WHILE i < JSON_LENGTH(p_categorie) DO
        SET v_categoria = JSON_EXTRACT(p_categorie, CONCAT('$[', i, ']'));
        
        INSERT INTO categorie_progetto (id_progetto, id_categoria)
        VALUES (v_id_progetto, JSON_UNQUOTE(v_categoria));
        
        SET i = i + 1;
    END WHILE;
    
    -- Inserisci competenze richieste
    WHILE j < JSON_LENGTH(p_competenze_richieste) DO
        SET v_competenza = JSON_EXTRACT(p_competenze_richieste, CONCAT('$[', j, ']'));
        
        INSERT INTO competenze_richieste (
            id_progetto, 
            id_competenza, 
            livello_minimo
        )
        VALUES (
            v_id_progetto,
            JSON_UNQUOTE(JSON_EXTRACT(v_competenza, '$.id')),
            JSON_UNQUOTE(JSON_EXTRACT(v_competenza, '$.livello'))
        );
        
        SET j = j + 1;
    END WHILE;
    
    COMMIT;
END //

-- Procedura migliorata per il finanziamento con notifiche
CREATE PROCEDURE sp_effettua_finanziamento_v2(
    IN p_id_progetto INT,
    IN p_id_finanziatore INT,
    IN p_importo DECIMAL(10,2),
    IN p_id_ricompensa INT
)
BEGIN
    DECLARE v_stato_progetto VARCHAR(20);
    DECLARE v_importo_attuale DECIMAL(10,2);
    DECLARE v_budget DECIMAL(10,2);
    DECLARE v_id_creator INT;
    DECLARE v_id_finanziamento INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore nel finanziamento';
    END;

    START TRANSACTION;
    
    SELECT stato, importo_attuale, budget, id_creator
    INTO v_stato_progetto, v_importo_attuale, v_budget, v_id_creator
    FROM progetti 
    WHERE id_progetto = p_id_progetto
    FOR UPDATE;
    
    -- Validazioni
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
    
    -- Inserisci finanziamento
    INSERT INTO finanziamenti (
        id_progetto, 
        id_finanziatore, 
        importo, 
        id_ricompensa
    )
    VALUES (
        p_id_progetto, 
        p_id_finanziatore, 
        p_importo, 
        p_id_ricompensa
    );
    
    SET v_id_finanziamento = LAST_INSERT_ID();
    
    -- Aggiorna importo progetto
    UPDATE progetti 
    SET importo_attuale = importo_attuale + p_importo,
        nr_finanziatori = (
            SELECT COUNT(DISTINCT id_finanziatore)
            FROM finanziamenti
            WHERE id_progetto = p_id_progetto
        )
    WHERE id_progetto = p_id_progetto;
    
    -- Aggiorna reward se specificato
    IF p_id_ricompensa IS NOT NULL THEN
        UPDATE ricompense
        SET quantita_richiesta = quantita_richiesta + 1
        WHERE id_ricompensa = p_id_ricompensa;
    END IF;
    
    -- Inserisci notifica per il creator
    INSERT INTO notifiche (
        id_utente,
        tipo,
        messaggio,
        id_riferimento
    )
    VALUES (
        v_id_creator,
        'NUOVO_FINANZIAMENTO',
        CONCAT('Nuovo finanziamento di €', p_importo, ' per il tuo progetto'),
        v_id_finanziamento
    );
    
    COMMIT;
END //

DELIMITER ;
