-- BOSTARTER - Trigger per automatismi database

DELIMITER //

-- Trigger: Aggiorna affidabilità e nr_progetti quando si crea un progetto
DROP TRIGGER IF EXISTS tr_progetto_inserito//
CREATE TRIGGER tr_progetto_inserito
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    DECLARE v_progetti_totali INT;
    DECLARE v_progetti_finanziati INT;
    DECLARE v_nuova_affidabilita DECIMAL(5,2);
    
    -- Conta progetti totali del creatore
    SELECT COUNT(*) INTO v_progetti_totali
    FROM progetti WHERE creatore_id = NEW.creatore_id;
    
    -- Conta progetti che hanno ricevuto almeno un finanziamento
    SELECT COUNT(DISTINCT p.id) INTO v_progetti_finanziati
    FROM progetti p
    JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE p.creatore_id = NEW.creatore_id;
    
    -- Calcola nuova affidabilità (percentuale)
    IF v_progetti_totali > 0 THEN
        SET v_nuova_affidabilita = (v_progetti_finanziati / v_progetti_totali) * 100;
    ELSE
        SET v_nuova_affidabilita = 0;
    END IF;
    
    -- Aggiorna campo nr_progetti e affidabilità
    UPDATE utenti 
    SET nr_progetti = v_progetti_totali,
        affidabilita = v_nuova_affidabilita
    WHERE id = NEW.creatore_id;
END //

-- Trigger: Aggiorna affidabilità quando un progetto riceve finanziamento
DROP TRIGGER IF EXISTS tr_finanziamento_inserito//
CREATE TRIGGER tr_finanziamento_inserito
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE v_progetti_totali INT;
    DECLARE v_progetti_finanziati INT;
    DECLARE v_nuova_affidabilita DECIMAL(5,2);
    DECLARE v_creatore_id INT;
    DECLARE v_budget_richiesto DECIMAL(12,2);
    DECLARE v_totale_raccolto DECIMAL(12,2);
    
    -- Ottieni ID creatore e budget
    SELECT creatore_id, budget_richiesto INTO v_creatore_id, v_budget_richiesto
    FROM progetti WHERE id = NEW.progetto_id;
    
    -- Calcola totale raccolto per questo progetto
    SELECT SUM(importo) INTO v_totale_raccolto
    FROM finanziamenti WHERE progetto_id = NEW.progetto_id;
    
    -- Se budget raggiunto, chiudi progetto
    IF v_totale_raccolto >= v_budget_richiesto THEN
        UPDATE progetti SET stato = 'chiuso' WHERE id = NEW.progetto_id;
    END IF;
    
    -- Aggiorna affidabilità creatore
    SELECT COUNT(*) INTO v_progetti_totali
    FROM progetti WHERE creatore_id = v_creatore_id;
    
    SELECT COUNT(DISTINCT p.id) INTO v_progetti_finanziati
    FROM progetti p
    JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE p.creatore_id = v_creatore_id;
    
    IF v_progetti_totali > 0 THEN
        SET v_nuova_affidabilita = (v_progetti_finanziati / v_progetti_totali) * 100;
        UPDATE utenti 
        SET affidabilita = v_nuova_affidabilita
        WHERE id = v_creatore_id;
    END IF;
END //

-- Trigger: Decrementa nr_progetti quando un progetto viene eliminato
DROP TRIGGER IF EXISTS tr_progetto_eliminato//
CREATE TRIGGER tr_progetto_eliminato
AFTER DELETE ON progetti
FOR EACH ROW
BEGIN
    DECLARE v_progetti_totali INT;
    DECLARE v_progetti_finanziati INT;
    DECLARE v_nuova_affidabilita DECIMAL(5,2);
    
    -- Conta progetti rimanenti del creatore
    SELECT COUNT(*) INTO v_progetti_totali
    FROM progetti WHERE creatore_id = OLD.creatore_id;
    
    -- Conta progetti finanziati rimanenti
    SELECT COUNT(DISTINCT p.id) INTO v_progetti_finanziati
    FROM progetti p
    JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE p.creatore_id = OLD.creatore_id;
    
    -- Calcola nuova affidabilità
    IF v_progetti_totali > 0 THEN
        SET v_nuova_affidabilita = (v_progetti_finanziati / v_progetti_totali) * 100;
    ELSE
        SET v_nuova_affidabilita = 0;
    END IF;
    
    -- Aggiorna nr_progetti e affidabilità
    UPDATE utenti 
    SET nr_progetti = v_progetti_totali,
        affidabilita = v_nuova_affidabilita
    WHERE id = OLD.creatore_id;
END //

-- Trigger: Validazione prima dell'inserimento candidatura
DROP TRIGGER IF EXISTS tr_valida_candidatura//
CREATE TRIGGER tr_valida_candidatura
BEFORE INSERT ON candidature
FOR EACH ROW
BEGIN
    DECLARE v_tipo_progetto VARCHAR(10);
    
    -- Verifica che sia un progetto software
    SELECT tipo_progetto INTO v_tipo_progetto
    FROM progetti WHERE id = NEW.progetto_id;
    
    IF v_tipo_progetto != 'software' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Le candidature sono ammesse solo per progetti software';
    END IF;
END //

DELIMITER ;
