-- BOSTARTER - Eventi programmati

-- Abilita scheduler eventi
SET GLOBAL event_scheduler = ON;

-- Evento per chiudere progetti scaduti (eseguito giornalmente alle 02:00)
DROP EVENT IF EXISTS chiudi_progetti_scaduti;

DELIMITER //
CREATE EVENT chiudi_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 1 HOUR
DO
BEGIN
    DECLARE progetti_chiusi INT DEFAULT 0;
    
    -- Chiudi progetti scaduti
    UPDATE progetti 
    SET stato = 'chiuso'
    WHERE stato = 'aperto' 
      AND data_limite < NOW();
    
    SET progetti_chiusi = ROW_COUNT();
    
    -- Log dell'operazione (opzionale)
    INSERT INTO log_sistema (evento, descrizione, data_evento)
    VALUES ('CHIUSURA_PROGETTI_SCADUTI', 
            CONCAT('Chiusi ', progetti_chiusi, ' progetti scaduti'), 
            NOW())
    ON DUPLICATE KEY UPDATE data_evento = NOW();
END //
DELIMITER ;

-- Evento per pulizia sessioni scadute (eseguito ogni ora)
DROP EVENT IF EXISTS pulisci_sessioni_scadute;

DELIMITER //
CREATE EVENT pulisci_sessioni_scadute
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    DELETE FROM sessioni_utente 
    WHERE expires_at < NOW();
END //
DELIMITER ;

-- Evento per aggiornamento statistiche cache (eseguito ogni 6 ore)
DROP EVENT IF EXISTS aggiorna_cache_statistiche;

DELIMITER //
CREATE EVENT aggiorna_cache_statistiche
ON SCHEDULE EVERY 6 HOUR
DO
BEGIN
    -- Crea/aggiorna tabella cache per statistiche pesanti
    CREATE TABLE IF NOT EXISTS cache_statistiche (
        chiave VARCHAR(100) PRIMARY KEY,
        valore JSON,
        ultima_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    
    -- Cache top creatori
    INSERT INTO cache_statistiche (chiave, valore)
    SELECT 'top_creatori', JSON_ARRAYAGG(
        JSON_OBJECT(
            'nickname', nickname,
            'affidabilita', affidabilita,
            'nr_progetti', nr_progetti
        )
    )
    FROM vista_top_creatori_affidabilita
    ON DUPLICATE KEY UPDATE valore = VALUES(valore);
    
    -- Cache progetti completamento
    INSERT INTO cache_statistiche (chiave, valore)
    SELECT 'progetti_completamento', JSON_ARRAYAGG(
        JSON_OBJECT(
            'nome', nome,
            'creatore', creatore_nickname,
            'percentuale', percentuale_completamento,
            'budget', budget_richiesto,
            'raccolto', totale_raccolto
        )
    )
    FROM vista_progetti_vicini_completamento
    ON DUPLICATE KEY UPDATE valore = VALUES(valore);
    
    -- Cache top finanziatori
    INSERT INTO cache_statistiche (chiave, valore)
    SELECT 'top_finanziatori', JSON_ARRAYAGG(
        JSON_OBJECT(
            'nickname', nickname,
            'totale_finanziato', totale_finanziato,
            'numero_finanziamenti', numero_finanziamenti
        )
    )
    FROM vista_top_finanziatori
    ON DUPLICATE KEY UPDATE valore = VALUES(valore);
END //
DELIMITER ;
