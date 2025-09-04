-- Sistema di Cache e Ottimizzazioni per BOSTARTER
USE bostarter;

-- Tabella per cache delle statistiche
CREATE TABLE cache_statistiche (
    chiave VARCHAR(100) PRIMARY KEY,
    valore JSON,
    ultimo_aggiornamento TIMESTAMP,
    INDEX idx_aggiornamento (ultimo_aggiornamento)
);

-- Vista materializzata per progetti in evidenza
CREATE TABLE mv_progetti_evidenza (
    id_progetto INT PRIMARY KEY,
    titolo VARCHAR(100),
    descrizione TEXT,
    percentuale_completamento DECIMAL(5,2),
    giorni_rimanenti INT,
    nr_finanziatori INT,
    ultimo_aggiornamento TIMESTAMP,
    INDEX idx_completamento (percentuale_completamento)
);

DELIMITER //

-- Procedura per aggiornare la cache statistiche
CREATE PROCEDURE sp_aggiorna_cache_statistiche()
BEGIN
    -- Cache top creator
    INSERT INTO cache_statistiche (chiave, valore, ultimo_aggiornamento)
    SELECT 
        'top_creator',
        JSON_ARRAYAGG(
            JSON_OBJECT(
                'nickname', nickname,
                'affidabilita', affidabilita
            )
        ),
        NOW()
    FROM (
        SELECT nickname, affidabilita
        FROM utenti
        WHERE is_creator = TRUE
        ORDER BY affidabilita DESC
        LIMIT 3
    ) t
    ON DUPLICATE KEY UPDATE
        valore = VALUES(valore),
        ultimo_aggiornamento = NOW();
    
    -- Cache progetti quasi completi
    INSERT INTO cache_statistiche (chiave, valore, ultimo_aggiornamento)
    SELECT 
        'progetti_quasi_completi',
        JSON_ARRAYAGG(
            JSON_OBJECT(
                'titolo', titolo,
                'percentuale', (importo_attuale / budget * 100),
                'mancante', (budget - importo_attuale)
            )
        ),
        NOW()
    FROM (
        SELECT titolo, budget, importo_attuale
        FROM progetti
        WHERE stato = 'APERTO'
        ORDER BY (importo_attuale / budget) DESC
        LIMIT 3
    ) t
    ON DUPLICATE KEY UPDATE
        valore = VALUES(valore),
        ultimo_aggiornamento = NOW();
END //

-- Procedura per aggiornare vista materializzata
CREATE PROCEDURE sp_aggiorna_mv_progetti()
BEGIN
    TRUNCATE TABLE mv_progetti_evidenza;
    
    INSERT INTO mv_progetti_evidenza
    SELECT 
        p.id_progetto,
        p.titolo,
        p.descrizione,
        (p.importo_attuale / p.budget * 100) as percentuale_completamento,
        DATEDIFF(p.data_scadenza, CURDATE()) as giorni_rimanenti,
        p.nr_finanziatori,
        NOW() as ultimo_aggiornamento
    FROM progetti p
    WHERE p.stato = 'APERTO'
    AND p.importo_attuale > 0;
END //

-- Event per aggiornamento periodico cache
CREATE EVENT evt_aggiorna_cache
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    CALL sp_aggiorna_cache_statistiche();
    CALL sp_aggiorna_mv_progetti();
END //

-- Funzione per ottenere statistiche dalla cache
CREATE FUNCTION fn_get_cached_stats(p_chiave VARCHAR(100))
RETURNS JSON
READS SQL DATA
BEGIN
    DECLARE v_valore JSON;
    DECLARE v_aggiornamento TIMESTAMP;
    
    SELECT valore, ultimo_aggiornamento
    INTO v_valore, v_aggiornamento
    FROM cache_statistiche
    WHERE chiave = p_chiave;
    
    -- Se cache non esiste o è vecchia più di un'ora, rigenera
    IF v_valore IS NULL OR v_aggiornamento < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN
        CALL sp_aggiorna_cache_statistiche();
        
        SELECT valore INTO v_valore
        FROM cache_statistiche
        WHERE chiave = p_chiave;
    END IF;
    
    RETURN v_valore;
END //

DELIMITER ;
