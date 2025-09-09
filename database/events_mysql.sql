-- =====================================================
-- BOSTARTER - Event Scheduler MySQL
-- Versione: 2.0 MySQL
-- Data: 2025-01-08
-- Descrizione: Eventi automatici per business logic
-- =====================================================

-- Abilita event scheduler
SET GLOBAL event_scheduler = ON;

DELIMITER //

-- =====================================================
-- EVENTI AUTOMATICI
-- =====================================================

-- Evento per chiusura progetti scaduti (eseguito giornalmente)
DROP EVENT IF EXISTS chiudi_progetti_scaduti//
CREATE EVENT chiudi_progetti_scaduti
    ON SCHEDULE EVERY 1 DAY
    STARTS CURRENT_TIMESTAMP
    DO
BEGIN
    DECLARE v_progetti_chiusi INT DEFAULT 0;
    
    -- Chiudi progetti scaduti che sono ancora attivi
    UPDATE progetti 
    SET stato = 'FALLITO',
        data_modifica = CURRENT_TIMESTAMP
    WHERE stato = 'ATTIVO' 
      AND data_fine < CURDATE()
      AND budget_raccolto < budget_richiesto;
    
    SET v_progetti_chiusi = ROW_COUNT();
    
    -- Log dell'operazione
    INSERT INTO system_log (
        azione, 
        tabella_interessata, 
        dettagli,
        timestamp
    ) VALUES (
        'CHIUSURA_AUTOMATICA_PROGETTI',
        'progetti',
        JSON_OBJECT('progetti_chiusi', v_progetti_chiusi, 'data_esecuzione', NOW()),
        CURRENT_TIMESTAMP
    );
END//

-- Evento per pulizia log sistema (settimanale)
DROP EVENT IF EXISTS pulisci_log_sistema//
CREATE EVENT pulisci_log_sistema
    ON SCHEDULE EVERY 1 WEEK
    STARTS CURRENT_TIMESTAMP
    DO
BEGIN
    -- Mantieni solo log degli ultimi 90 giorni
    DELETE FROM system_log 
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Log dell'operazione di pulizia
    INSERT INTO system_log (
        azione, 
        tabella_interessata, 
        dettagli,
        timestamp
    ) VALUES (
        'PULIZIA_LOG_AUTOMATICA',
        'system_log',
        JSON_OBJECT('data_esecuzione', NOW()),
        CURRENT_TIMESTAMP
    );
END//

-- Evento per aggiornamento statistiche cache (ogni ora)
DROP EVENT IF EXISTS aggiorna_statistiche//
CREATE EVENT aggiorna_statistiche
    ON SCHEDULE EVERY 1 HOUR
    STARTS CURRENT_TIMESTAMP
    DO
BEGIN
    -- Questo evento può essere utilizzato per aggiornare cache
    -- o statistiche pre-calcolate per migliorare le performance
    
    INSERT INTO system_log (
        azione, 
        tabella_interessata, 
        dettagli,
        timestamp
    ) VALUES (
        'AGGIORNAMENTO_STATISTICHE',
        'varie',
        JSON_OBJECT('data_esecuzione', NOW()),
        CURRENT_TIMESTAMP
    );
END//

DELIMITER ;

-- =====================================================
-- FINE EVENTI
-- =====================================================

SELECT 'Event Scheduler MySQL BOSTARTER configurato con successo!' as messaggio;
