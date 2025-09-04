-- Sistema di Notifiche per BOSTARTER
USE bostarter;

-- Tabella per le notifiche
CREATE TABLE notifiche (
    id_notifica BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,
    tipo_notifica ENUM(
        'NUOVO_FINANZIAMENTO',
        'PROGETTO_COMPLETATO',
        'NUOVA_CANDIDATURA',
        'CANDIDATURA_ACCETTATA',
        'CANDIDATURA_RIFIUTATA',
        'NUOVO_COMMENTO',
        'RISPOSTA_COMMENTO',
        'SCADENZA_IMMINENTE',
        'PROGETTO_CHIUSO'
    ),
    titolo VARCHAR(100) NOT NULL,
    messaggio TEXT NOT NULL,
    letta BOOLEAN DEFAULT FALSE,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_lettura TIMESTAMP NULL,
    dati_aggiuntivi JSON,
    FOREIGN KEY (id_utente) REFERENCES utenti(id_utente),
    INDEX idx_utente_letta (id_utente, letta),
    INDEX idx_data_creazione (data_creazione)
);

-- Tabella per le preferenze notifiche
CREATE TABLE preferenze_notifiche (
    id_utente INT,
    tipo_notifica ENUM(
        'NUOVO_FINANZIAMENTO',
        'PROGETTO_COMPLETATO',
        'NUOVA_CANDIDATURA',
        'CANDIDATURA_ACCETTATA',
        'CANDIDATURA_RIFIUTATA',
        'NUOVO_COMMENTO',
        'RISPOSTA_COMMENTO',
        'SCADENZA_IMMINENTE',
        'PROGETTO_CHIUSO'
    ),
    email BOOLEAN DEFAULT TRUE,
    push BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (id_utente, tipo_notifica),
    FOREIGN KEY (id_utente) REFERENCES utenti(id_utente)
);

DELIMITER //

-- Procedura per inviare notifica
CREATE PROCEDURE sp_invia_notifica(
    IN p_id_utente INT,
    IN p_tipo_notifica VARCHAR(50),
    IN p_titolo VARCHAR(100),
    IN p_messaggio TEXT,
    IN p_dati_aggiuntivi JSON
)
BEGIN
    DECLARE v_email_attivo BOOLEAN;
    DECLARE v_push_attivo BOOLEAN;
    
    -- Verifica preferenze utente
    SELECT email, push 
    INTO v_email_attivo, v_push_attivo
    FROM preferenze_notifiche
    WHERE id_utente = p_id_utente
    AND tipo_notifica = p_tipo_notifica;
    
    -- Inserisci notifica
    INSERT INTO notifiche (
        id_utente,
        tipo_notifica,
        titolo,
        messaggio,
        dati_aggiuntivi
    )
    VALUES (
        p_id_utente,
        p_tipo_notifica,
        p_titolo,
        p_messaggio,
        p_dati_aggiuntivi
    );
    
    -- Se email attiva, inserisci in coda email
    IF v_email_attivo THEN
        INSERT INTO coda_email (
            id_utente,
            tipo,
            oggetto,
            contenuto,
            dati_template
        )
        VALUES (
            p_id_utente,
            p_tipo_notifica,
            p_titolo,
            p_messaggio,
            p_dati_aggiuntivi
        );
    END IF;
    
    -- Se push attivo, inserisci in coda notifiche push
    IF v_push_attivo THEN
        INSERT INTO coda_push (
            id_utente,
            tipo,
            titolo,
            messaggio,
            dati
        )
        VALUES (
            p_id_utente,
            p_tipo_notifica,
            p_titolo,
            p_messaggio,
            p_dati_aggiuntivi
        );
    END IF;
END //

-- Procedura per segnare notifica come letta
CREATE PROCEDURE sp_segna_notifica_letta(
    IN p_id_notifica BIGINT,
    IN p_id_utente INT
)
BEGIN
    UPDATE notifiche
    SET letta = TRUE,
        data_lettura = CURRENT_TIMESTAMP
    WHERE id_notifica = p_id_notifica
    AND id_utente = p_id_utente;
END //

-- Event per notifiche scadenza imminente
CREATE EVENT evt_notifiche_scadenza
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE
DO
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id_progetto INT;
    DECLARE v_id_creator INT;
    DECLARE v_titolo VARCHAR(100);
    
    DECLARE cur CURSOR FOR
        SELECT id_progetto, id_creator, titolo
        FROM progetti
        WHERE stato = 'APERTO'
        AND data_scadenza BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY);
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_id_progetto, v_id_creator, v_titolo;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        CALL sp_invia_notifica(
            v_id_creator,
            'SCADENZA_IMMINENTE',
            'Progetto in scadenza',
            CONCAT('Il tuo progetto "', v_titolo, '" scadrà tra meno di 3 giorni'),
            JSON_OBJECT('id_progetto', v_id_progetto)
        );
    END LOOP;
    
    CLOSE cur;
END //

DELIMITER ;
