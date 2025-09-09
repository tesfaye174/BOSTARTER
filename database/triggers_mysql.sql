-- =====================================================
-- BOSTARTER - Triggers MySQL
-- Versione: 2.0 MySQL
-- Data: 2025-01-08
-- Descrizione: Trigger per automazione business logic
-- =====================================================

DELIMITER //

-- =====================================================
-- TRIGGER PROGETTI
-- =====================================================

-- Trigger per aggiornamento budget e contatori dopo finanziamento
DROP TRIGGER IF EXISTS aggiorna_progetto_finanziamento//
CREATE TRIGGER aggiorna_progetto_finanziamento
    AFTER INSERT ON finanziamenti
    FOR EACH ROW
BEGIN
    DECLARE v_budget_richiesto DECIMAL(12,2);
    DECLARE v_budget_raccolto DECIMAL(12,2);
    DECLARE v_creatore_id INT;
    
    -- Aggiorna budget raccolto
    UPDATE progetti 
    SET budget_raccolto = budget_raccolto + NEW.importo,
        nr_sostenitori = (
            SELECT COUNT(DISTINCT utente_id) 
            FROM finanziamenti 
            WHERE progetto_id = NEW.progetto_id 
              AND stato = 'COMPLETATO'
        )
    WHERE id = NEW.progetto_id;
    
    -- Ottieni dati progetto per controlli
    SELECT budget_richiesto, budget_raccolto, creatore_id 
    INTO v_budget_richiesto, v_budget_raccolto, v_creatore_id
    FROM progetti WHERE id = NEW.progetto_id;
    
    -- Se progetto ha raggiunto obiettivo, cambia stato
    IF v_budget_raccolto >= v_budget_richiesto THEN
        UPDATE progetti SET stato = 'COMPLETATO' WHERE id = NEW.progetto_id;
    END IF;
    
    -- Aggiorna affidabilità creatore (primo finanziamento ricevuto)
    IF NOT EXISTS(
        SELECT 1 FROM finanziamenti 
        WHERE progetto_id = NEW.progetto_id 
          AND id < NEW.id 
          AND stato = 'COMPLETATO'
    ) THEN
        CALL aggiorna_affidabilita_creatore(v_creatore_id);
    END IF;
END//

-- Trigger per incremento nr_progetti quando creatore inserisce progetto
DROP TRIGGER IF EXISTS incrementa_nr_progetti//
CREATE TRIGGER incrementa_nr_progetti
    AFTER INSERT ON progetti
    FOR EACH ROW
BEGIN
    UPDATE utenti 
    SET nr_progetti = nr_progetti + 1 
    WHERE id = NEW.creatore_id;
    
    -- Ricalcola affidabilità (denominatore cambiato)
    CALL aggiorna_affidabilita_creatore(NEW.creatore_id);
END//

-- Trigger per aggiornamento stato progetto quando raggiunge obiettivo
DROP TRIGGER IF EXISTS verifica_obiettivo_raggiunto//
CREATE TRIGGER verifica_obiettivo_raggiunto
    AFTER UPDATE ON progetti
    FOR EACH ROW
BEGIN
    -- Se il progetto ha raggiunto l'obiettivo e non è ancora completato
    IF NEW.budget_raccolto >= NEW.budget_richiesto 
       AND OLD.budget_raccolto < OLD.budget_richiesto 
       AND NEW.stato = 'ATTIVO' THEN
        
        -- Log raggiungimento obiettivo
        INSERT INTO system_log (
            utente_id, azione, tabella_interessata, record_id, dettagli
        ) VALUES (
            NEW.creatore_id, 'OBIETTIVO_RAGGIUNTO', 'progetti', NEW.id,
            JSON_OBJECT('budget_richiesto', NEW.budget_richiesto, 'budget_raccolto', NEW.budget_raccolto)
        );
    END IF;
END//

-- Trigger per aggiornamento nr_progetti utente
DROP TRIGGER IF EXISTS aggiorna_nr_progetti_utente//
CREATE TRIGGER aggiorna_nr_progetti_utente
    AFTER UPDATE ON progetti
    FOR EACH ROW
BEGIN
    -- Aggiorna contatore progetti per il creatore
    IF OLD.stato != NEW.stato AND NEW.stato IN ('COMPLETATO', 'FALLITO') THEN
        UPDATE utenti 
        SET nr_progetti = (
            SELECT COUNT(*) 
            FROM progetti 
            WHERE creatore_id = NEW.creatore_id 
              AND stato IN ('COMPLETATO', 'FALLITO')
        )
        WHERE id = NEW.creatore_id;
    END IF;
END//

-- =====================================================
-- TRIGGER COMMENTI
-- =====================================================

-- Trigger per aggiornamento contatore commenti
DROP TRIGGER IF EXISTS aggiorna_contatore_commenti//
CREATE TRIGGER aggiorna_contatore_commenti
    AFTER INSERT ON commenti
    FOR EACH ROW
BEGIN
    UPDATE progetti 
    SET nr_commenti = (
        SELECT COUNT(*) 
        FROM commenti 
        WHERE progetto_id = NEW.progetto_id 
          AND approvato = TRUE
    )
    WHERE id = NEW.progetto_id;
END//

-- Trigger per decremento contatore commenti
DROP TRIGGER IF EXISTS decrementa_contatore_commenti//
CREATE TRIGGER decrementa_contatore_commenti
    AFTER DELETE ON commenti
    FOR EACH ROW
BEGIN
    UPDATE progetti 
    SET nr_commenti = (
        SELECT COUNT(*) 
        FROM commenti 
        WHERE progetto_id = OLD.progetto_id 
          AND approvato = TRUE
    )
    WHERE id = OLD.progetto_id;
END//

-- =====================================================
-- TRIGGER CANDIDATURE
-- =====================================================

-- Trigger per aggiornamento contatore candidature
DROP TRIGGER IF EXISTS aggiorna_contatore_candidature//
CREATE TRIGGER aggiorna_contatore_candidature
    AFTER INSERT ON candidature
    FOR EACH ROW
BEGIN
    UPDATE progetti 
    SET nr_candidature = (
        SELECT COUNT(*) 
        FROM candidature 
        WHERE progetto_id = NEW.progetto_id
    )
    WHERE id = NEW.progetto_id;
END//

-- Trigger per gestione candidature accettate
DROP TRIGGER IF EXISTS gestisci_candidatura_accettata//
CREATE TRIGGER gestisci_candidatura_accettata
    AFTER UPDATE ON candidature
    FOR EACH ROW
BEGIN
    -- Se candidatura accettata, aggiungi come componente del team
    IF OLD.stato != 'ACCETTATA' AND NEW.stato = 'ACCETTATA' THEN
        INSERT INTO componenti (progetto_id, utente_id, ruolo, descrizione_ruolo)
        VALUES (NEW.progetto_id, NEW.utente_id, 'Team Member', 'Membro del team tramite candidatura')
        ON DUPLICATE KEY UPDATE attivo = TRUE, data_uscita = NULL;
        
        -- Log accettazione
        INSERT INTO system_log (
            utente_id, azione, tabella_interessata, record_id, dettagli
        ) VALUES (
            NEW.utente_id, 'CANDIDATURA_ACCETTATA', 'candidature', NEW.id,
            JSON_OBJECT('progetto_id', NEW.progetto_id)
        );
    END IF;
END//

-- =====================================================
-- TRIGGER RICOMPENSE
-- =====================================================

-- Trigger per aggiornamento quantità prenotata ricompense
DROP TRIGGER IF EXISTS aggiorna_quantita_ricompensa//
CREATE TRIGGER aggiorna_quantita_ricompensa
    AFTER INSERT ON finanziamenti
    FOR EACH ROW
BEGIN
    -- Se è stata selezionata una ricompensa, aggiorna la quantità prenotata
    IF NEW.ricompensa_id IS NOT NULL AND NEW.stato = 'COMPLETATO' THEN
        UPDATE ricompense 
        SET quantita_prenotata = quantita_prenotata + 1
        WHERE id = NEW.ricompensa_id;
        
        -- Verifica se ricompensa è esaurita
        UPDATE ricompense 
        SET attiva = FALSE
        WHERE id = NEW.ricompensa_id 
          AND quantita_disponibile IS NOT NULL 
          AND quantita_prenotata >= quantita_disponibile;
    END IF;
END//

-- =====================================================
-- TRIGGER SYSTEM LOG
-- =====================================================

-- Trigger per log automatico modifiche utenti
DROP TRIGGER IF EXISTS log_modifica_utenti//
CREATE TRIGGER log_modifica_utenti
    AFTER UPDATE ON utenti
    FOR EACH ROW
BEGIN
    -- Log solo se campi significativi sono cambiati
    IF OLD.email != NEW.email 
       OR OLD.tipo_utente != NEW.tipo_utente 
       OR OLD.attivo != NEW.attivo THEN
        
        INSERT INTO system_log (
            utente_id, azione, tabella_interessata, record_id, dettagli
        ) VALUES (
            NEW.id, 'MODIFICA_UTENTE', 'utenti', NEW.id,
            JSON_OBJECT(
                'old_email', OLD.email, 'new_email', NEW.email,
                'old_tipo', OLD.tipo_utente, 'new_tipo', NEW.tipo_utente,
                'old_attivo', OLD.attivo, 'new_attivo', NEW.attivo
            )
        );
    END IF;
END//

-- Trigger per log automatico modifiche progetti
DROP TRIGGER IF EXISTS log_modifica_progetti//
CREATE TRIGGER log_modifica_progetti
    AFTER UPDATE ON progetti
    FOR EACH ROW
BEGIN
    -- Log cambio stato
    IF OLD.stato != NEW.stato THEN
        INSERT INTO system_log (
            utente_id, azione, tabella_interessata, record_id, dettagli
        ) VALUES (
            NEW.creatore_id, 'CAMBIO_STATO_PROGETTO', 'progetti', NEW.id,
            JSON_OBJECT(
                'old_stato', OLD.stato, 'new_stato', NEW.stato,
                'titolo', NEW.titolo, 'budget_raccolto', NEW.budget_raccolto
            )
        );
    END IF;
END//

DELIMITER ;

SELECT 'Triggers MySQL BOSTARTER creati con successo!' as messaggio;
