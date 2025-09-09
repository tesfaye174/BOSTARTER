-- =====================================================
-- BOSTARTER - Stored Procedures MySQL
-- Versione: 2.0 MySQL
-- Data: 2025-01-08
-- Descrizione: Procedure per piattaforma crowdfunding
-- =====================================================

DELIMITER //

-- =====================================================
-- PROCEDURE UTENTI
-- =====================================================

-- Procedura per calcolo e aggiornamento affidabilità creatore
DROP PROCEDURE IF EXISTS aggiorna_affidabilita_creatore//
CREATE PROCEDURE aggiorna_affidabilita_creatore(
    IN p_creatore_id INT
)
BEGIN
    DECLARE v_progetti_totali INT DEFAULT 0;
    DECLARE v_progetti_finanziati INT DEFAULT 0;
    DECLARE v_affidabilita DECIMAL(3,2) DEFAULT 0.00;
    
    -- Conta progetti totali del creatore
    SELECT COUNT(*) INTO v_progetti_totali
    FROM progetti 
    WHERE creatore_id = p_creatore_id;
    
    -- Conta progetti che hanno ricevuto almeno un finanziamento
    SELECT COUNT(DISTINCT p.id) INTO v_progetti_finanziati
    FROM progetti p
    JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE p.creatore_id = p_creatore_id 
      AND f.stato = 'COMPLETATO';
    
    -- Calcola affidabilità come percentuale
    IF v_progetti_totali > 0 THEN
        SET v_affidabilita = (v_progetti_finanziati / v_progetti_totali) * 100;
    END IF;
    
    -- Aggiorna affidabilità utente
    UPDATE utenti 
    SET affidabilita = LEAST(100.00, v_affidabilita)
    WHERE id = p_creatore_id;
END//

-- Procedura per validazione skill matching candidatura
DROP PROCEDURE IF EXISTS verifica_skill_candidatura//
CREATE PROCEDURE verifica_skill_candidatura(
    IN p_utente_id INT,
    IN p_profilo_id INT,
    OUT p_result JSON
)
BEGIN
    DECLARE v_skill_richieste INT DEFAULT 0;
    DECLARE v_skill_possedute INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1 v_error_msg = MESSAGE_TEXT;
        SET p_result = JSON_OBJECT('success', FALSE, 'error', v_error_msg);
    END;
    
    -- Conta skill richieste dal profilo
    SELECT COUNT(*) INTO v_skill_richieste
    FROM skill_profili sp
    WHERE sp.profilo_id = p_profilo_id;
    
    -- Conta skill possedute dall'utente che soddisfano i requisiti
    SELECT COUNT(*) INTO v_skill_possedute
    FROM skill_profili sp
    JOIN utenti_competenze uc ON sp.competenza_id = uc.competenza_id
    WHERE sp.profilo_id = p_profilo_id
      AND uc.utente_id = p_utente_id
      AND uc.livello >= sp.livello_richiesto;
    
    -- Verifica se tutte le skill obbligatorie sono soddisfatte
    IF v_skill_possedute >= v_skill_richieste THEN
        SET p_result = JSON_OBJECT('success', TRUE, 'message', 'Skill requirements met');
    ELSE
        SET p_result = JSON_OBJECT(
            'success', FALSE, 
            'error', CONCAT('Skill insufficienti: possedute ', v_skill_possedute, ' su ', v_skill_richieste, ' richieste')
        );
    END IF;
END//

-- Registrazione nuovo utente
DROP PROCEDURE IF EXISTS registra_utente//
CREATE PROCEDURE registra_utente(
    IN p_nickname VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_password_hash VARCHAR(255),
    IN p_nome VARCHAR(50),
    IN p_cognome VARCHAR(50),
    IN p_tipo_utente ENUM('UTENTE', 'CREATORE', 'ADMIN'),
    OUT p_result JSON
)
BEGIN
    DECLARE v_user_id INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1 v_error_msg = MESSAGE_TEXT;
        SET p_result = JSON_OBJECT('success', FALSE, 'error', v_error_msg);
    END;

    START TRANSACTION;
    
    -- Verifica duplicati
    IF EXISTS(SELECT 1 FROM utenti WHERE email = p_email) THEN
        SET p_result = JSON_OBJECT('success', FALSE, 'error', 'Email già registrata');
        ROLLBACK;
    ELSEIF EXISTS(SELECT 1 FROM utenti WHERE nickname = p_nickname) THEN
        SET p_result = JSON_OBJECT('success', FALSE, 'error', 'Nickname già in uso');
        ROLLBACK;
    ELSE
        INSERT INTO utenti (nickname, email, password_hash, nome, cognome, tipo_utente)
        VALUES (p_nickname, p_email, p_password_hash, p_nome, p_cognome, COALESCE(p_tipo_utente, 'UTENTE'));
        
        SET v_user_id = LAST_INSERT_ID();
        
        -- Log registrazione
        INSERT INTO system_log (utente_id, azione, tabella_interessata, record_id, dettagli)
        VALUES (v_user_id, 'REGISTRAZIONE', 'utenti', v_user_id, 
                JSON_OBJECT('tipo_utente', p_tipo_utente, 'email', p_email));
        
        SET p_result = JSON_OBJECT('success', TRUE, 'user_id', v_user_id, 'message', 'Utente registrato con successo');
        COMMIT;
    END IF;
END//

-- Aggiorna affidabilità utente
DROP PROCEDURE IF EXISTS aggiorna_affidabilita//
CREATE PROCEDURE aggiorna_affidabilita(
    IN p_utente_id INT,
    OUT p_result JSON
)
BEGIN
    DECLARE v_progetti_completati INT DEFAULT 0;
    DECLARE v_progetti_falliti INT DEFAULT 0;
    DECLARE v_budget_totale DECIMAL(15,2) DEFAULT 0;
    DECLARE v_nuova_affidabilita DECIMAL(3,2) DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error_msg = MESSAGE_TEXT;
        SET p_result = JSON_OBJECT('success', FALSE, 'error', v_error_msg);
    END;

    -- Calcola statistiche progetti
    SELECT 
        COUNT(CASE WHEN stato = 'COMPLETATO' THEN 1 END),
        COUNT(CASE WHEN stato = 'FALLITO' THEN 1 END),
        COALESCE(SUM(CASE WHEN stato = 'COMPLETATO' THEN budget_raccolto ELSE 0 END), 0)
    INTO v_progetti_completati, v_progetti_falliti, v_budget_totale
    FROM progetti 
    WHERE creatore_id = p_utente_id;

    -- Formula affidabilità: wI * (progetti_completati / progetti_totali) + wB * log10(budget_totale + 1) / 10
    -- wI = 1 (peso progetti), wB = 0.5 (peso budget), a = 2 (fattore amplificazione)
    SET v_nuova_affidabilita = LEAST(5.00, 
        1.0 * (v_progetti_completati / GREATEST(v_progetti_completati + v_progetti_falliti, 1)) +
        0.5 * (LOG10(v_budget_totale + 1) / 10) * 2
    );

    -- Aggiorna affidabilità
    UPDATE utenti 
    SET affidabilita = v_nuova_affidabilita,
        nr_progetti = v_progetti_completati + v_progetti_falliti
    WHERE id = p_utente_id;

    SET p_result = JSON_OBJECT(
        'success', TRUE, 
        'nuova_affidabilita', v_nuova_affidabilita,
        'progetti_completati', v_progetti_completati,
        'progetti_falliti', v_progetti_falliti,
        'budget_totale', v_budget_totale
    );
END//

-- =====================================================
-- PROCEDURE PROGETTI
-- =====================================================

-- Creazione nuovo progetto
DROP PROCEDURE IF EXISTS crea_progetto//
CREATE PROCEDURE crea_progetto(
    IN p_titolo VARCHAR(200),
    IN p_descrizione TEXT,
    IN p_categoria_id INT,
    IN p_creatore_id INT,
    IN p_budget_richiesto DECIMAL(12,2),
    IN p_data_fine DATE,
    OUT p_result JSON
)
BEGIN
    DECLARE v_progetto_id INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1 v_error_msg = MESSAGE_TEXT;
        SET p_result = JSON_OBJECT('success', FALSE, 'error', v_error_msg);
    END;

    START TRANSACTION;
    
    -- Validazioni
    IF p_data_fine <= CURDATE() THEN
        SET p_result = JSON_OBJECT('success', FALSE, 'error', 'Data fine deve essere futura');
        ROLLBACK;
    ELSEIF p_budget_richiesto <= 0 THEN
        SET p_result = JSON_OBJECT('success', FALSE, 'error', 'Budget deve essere positivo');
        ROLLBACK;
    ELSE
        INSERT INTO progetti (
            titolo, descrizione, categoria_id, creatore_id, 
            budget_richiesto, data_inizio, data_fine, stato
        ) VALUES (
            p_titolo, p_descrizione, p_categoria_id, p_creatore_id,
            p_budget_richiesto, CURDATE(), p_data_fine, 'BOZZA'
        );
        
        SET v_progetto_id = LAST_INSERT_ID();
        
        -- Log creazione
        INSERT INTO system_log (utente_id, azione, tabella_interessata, record_id, dettagli)
        VALUES (p_creatore_id, 'CREAZIONE_PROGETTO', 'progetti', v_progetto_id,
                JSON_OBJECT('titolo', p_titolo, 'budget', p_budget_richiesto));
        
        SET p_result = JSON_OBJECT('success', TRUE, 'progetto_id', v_progetto_id, 'message', 'Progetto creato con successo');
        COMMIT;
    END IF;
END//

-- Finanziamento progetto
DROP PROCEDURE IF EXISTS finanzia_progetto//
CREATE PROCEDURE finanzia_progetto(
    IN p_progetto_id INT,
    IN p_utente_id INT,
    IN p_importo DECIMAL(10,2),
    IN p_ricompensa_id INT,
    IN p_messaggio TEXT,
    OUT p_result JSON
)
BEGIN
    DECLARE v_finanziamento_id INT DEFAULT 0;
    DECLARE v_stato_progetto VARCHAR(20);
    DECLARE v_budget_attuale DECIMAL(12,2);
    DECLARE v_budget_richiesto DECIMAL(12,2);
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1 v_error_msg = MESSAGE_TEXT;
        SET p_result = JSON_OBJECT('success', FALSE, 'error', v_error_msg);
    END;

    START TRANSACTION;
    
    -- Verifica progetto
    SELECT stato, budget_raccolto, budget_richiesto
    INTO v_stato_progetto, v_budget_attuale, v_budget_richiesto
    FROM progetti WHERE id = p_progetto_id;
    
    IF v_stato_progetto != 'ATTIVO' THEN
        SET p_result = JSON_OBJECT('success', FALSE, 'error', 'Progetto non attivo');
        ROLLBACK;
    ELSEIF p_importo <= 0 THEN
        SET p_result = JSON_OBJECT('success', FALSE, 'error', 'Importo deve essere positivo');
        ROLLBACK;
    ELSE
        -- Inserisci finanziamento
        INSERT INTO finanziamenti (
            progetto_id, utente_id, ricompensa_id, importo, 
            messaggio, stato, metodo_pagamento
        ) VALUES (
            p_progetto_id, p_utente_id, p_ricompensa_id, p_importo,
            p_messaggio, 'COMPLETATO', 'CARD'
        );
        
        SET v_finanziamento_id = LAST_INSERT_ID();
        
        -- Aggiorna budget progetto (trigger si occuperà dei contatori)
        UPDATE progetti 
        SET budget_raccolto = budget_raccolto + p_importo
        WHERE id = p_progetto_id;
        
        -- Log finanziamento
        INSERT INTO system_log (utente_id, azione, tabella_interessata, record_id, dettagli)
        VALUES (p_utente_id, 'FINANZIAMENTO', 'finanziamenti', v_finanziamento_id,
                JSON_OBJECT('progetto_id', p_progetto_id, 'importo', p_importo));
        
        SET p_result = JSON_OBJECT(
            'success', TRUE, 
            'finanziamento_id', v_finanziamento_id,
            'nuovo_budget', v_budget_attuale + p_importo,
            'message', 'Finanziamento completato con successo'
        );
        COMMIT;
    END IF;
END//

-- Chiusura progetti scaduti
DROP PROCEDURE IF EXISTS chiudi_progetti_scaduti//
CREATE PROCEDURE chiudi_progetti_scaduti(
    OUT p_result JSON
)
BEGIN
    DECLARE v_progetti_chiusi INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1 v_error_msg = MESSAGE_TEXT;
        SET p_result = JSON_OBJECT('success', FALSE, 'error', v_error_msg);
    END;

    START TRANSACTION;
    
    -- Chiudi progetti scaduti che hanno raggiunto l'obiettivo
    UPDATE progetti 
    SET stato = 'COMPLETATO'
    WHERE stato = 'ATTIVO' 
      AND data_fine < CURDATE()
      AND budget_raccolto >= budget_richiesto;
    
    SET v_progetti_chiusi = ROW_COUNT();
    
    -- Chiudi progetti scaduti che NON hanno raggiunto l'obiettivo
    UPDATE progetti 
    SET stato = 'FALLITO'
    WHERE stato = 'ATTIVO' 
      AND data_fine < CURDATE()
      AND budget_raccolto < budget_richiesto;
    
    SET v_progetti_chiusi = v_progetti_chiusi + ROW_COUNT();
    
    -- Log operazione
    INSERT INTO system_log (azione, tabella_interessata, dettagli)
    VALUES ('CHIUSURA_AUTOMATICA', 'progetti', 
            JSON_OBJECT('progetti_chiusi', v_progetti_chiusi, 'data', CURDATE()));
    
    SET p_result = JSON_OBJECT('success', TRUE, 'progetti_chiusi', v_progetti_chiusi);
    COMMIT;
END//

-- =====================================================
-- PROCEDURE COMPETENZE
-- =====================================================

-- Assegna competenza a utente
DROP PROCEDURE IF EXISTS assegna_competenza_utente//
CREATE PROCEDURE assegna_competenza_utente(
    IN p_utente_id INT,
    IN p_competenza_id INT,
    IN p_livello ENUM('BASE', 'INTERMEDIO', 'AVANZATO', 'ESPERTO'),
    IN p_anni_esperienza INT,
    OUT p_result JSON
)
BEGIN
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error_msg = MESSAGE_TEXT;
        SET p_result = JSON_OBJECT('success', FALSE, 'error', v_error_msg);
    END;

    INSERT INTO utenti_competenze (utente_id, competenza_id, livello, anni_esperienza)
    VALUES (p_utente_id, p_competenza_id, p_livello, COALESCE(p_anni_esperienza, 0))
    ON DUPLICATE KEY UPDATE 
        livello = p_livello,
        anni_esperienza = COALESCE(p_anni_esperienza, anni_esperienza);
    
    SET p_result = JSON_OBJECT('success', TRUE, 'message', 'Competenza assegnata con successo');
END//

-- =====================================================
-- PROCEDURE STATISTICHE
-- =====================================================

-- Statistiche generali piattaforma
DROP PROCEDURE IF EXISTS get_statistiche_generali//
CREATE PROCEDURE get_statistiche_generali(
    OUT p_result JSON
)
BEGIN
    DECLARE v_totale_utenti INT DEFAULT 0;
    DECLARE v_totale_progetti INT DEFAULT 0;
    DECLARE v_progetti_attivi INT DEFAULT 0;
    DECLARE v_progetti_completati INT DEFAULT 0;
    DECLARE v_totale_finanziato DECIMAL(15,2) DEFAULT 0;
    DECLARE v_media_finanziamento DECIMAL(10,2) DEFAULT 0;
    
    SELECT COUNT(*) INTO v_totale_utenti FROM utenti WHERE attivo = TRUE;
    SELECT COUNT(*) INTO v_totale_progetti FROM progetti;
    SELECT COUNT(*) INTO v_progetti_attivi FROM progetti WHERE stato = 'ATTIVO';
    SELECT COUNT(*) INTO v_progetti_completati FROM progetti WHERE stato = 'COMPLETATO';
    
    SELECT 
        COALESCE(SUM(importo), 0),
        COALESCE(AVG(importo), 0)
    INTO v_totale_finanziato, v_media_finanziamento
    FROM finanziamenti WHERE stato = 'COMPLETATO';
    
    SET p_result = JSON_OBJECT(
        'totale_utenti', v_totale_utenti,
        'totale_progetti', v_totale_progetti,
        'progetti_attivi', v_progetti_attivi,
        'progetti_completati', v_progetti_completati,
        'totale_finanziato', v_totale_finanziato,
        'media_finanziamento', v_media_finanziamento,
        'tasso_successo', ROUND(v_progetti_completati / GREATEST(v_totale_progetti, 1) * 100, 2)
    );
END//

-- =====================================================
-- EVENT SCHEDULER
-- =====================================================

-- Evento per chiusura automatica progetti
DROP EVENT IF EXISTS chiusura_progetti_automatica//
CREATE EVENT chiusura_progetti_automatica
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DECLARE v_result JSON;
    CALL chiudi_progetti_scaduti(v_result);
END//

DELIMITER ;

-- Abilita event scheduler
SET GLOBAL event_scheduler = ON;

SELECT 'Stored Procedures MySQL BOSTARTER create con successo!' as messaggio;
