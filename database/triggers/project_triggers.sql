-- Trigger per la gestione automatica dei progetti e finanziamenti

DELIMITER //

-- Trigger per aggiornare lo stato di un progetto quando raggiunge il budget
CREATE TRIGGER IF NOT EXISTS update_project_status_after_funding
AFTER UPDATE ON Projects
FOR EACH ROW
BEGIN
    -- Se il finanziamento ha raggiunto o superato il budget e lo stato è 'active', cambialo in 'funded'
    IF NEW.current_funding >= NEW.budget AND NEW.status = 'active' THEN
        UPDATE Projects SET status = 'funded' WHERE id = NEW.id;
    END IF;
END //

-- Trigger per aggiornare la reliability di un creatore quando un progetto viene completato
CREATE TRIGGER IF NOT EXISTS update_creator_reliability_after_project_completion
AFTER UPDATE ON Projects
FOR EACH ROW
BEGIN
    DECLARE total_projects INT;
    DECLARE completed_projects INT;
    DECLARE new_reliability DECIMAL(5,2);
    
    -- Se lo stato del progetto è cambiato in 'completed'
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- Conta il numero totale di progetti del creatore
        SELECT COUNT(*) INTO total_projects
        FROM Projects
        WHERE creator_id = NEW.creator_id;
        
        -- Conta il numero di progetti completati del creatore
        SELECT COUNT(*) INTO completed_projects
        FROM Projects
        WHERE creator_id = NEW.creator_id AND status = 'completed';
        
        -- Calcola la nuova reliability (percentuale di progetti completati)
        SET new_reliability = (completed_projects / total_projects) * 100;
        
        -- Aggiorna la reliability del creatore
        UPDATE Creator_Users
        SET reliability = new_reliability
        WHERE user_id = NEW.creator_id;
    END IF;
END //

-- Trigger per aggiornare il total_funded di un creatore quando un progetto riceve un finanziamento
CREATE TRIGGER IF NOT EXISTS update_creator_total_funded_after_funding
AFTER INSERT ON Funding
FOR EACH ROW
BEGIN
    DECLARE creator_id BIGINT UNSIGNED;
    
    -- Ottieni l'ID del creatore del progetto
    SELECT creator_id INTO creator_id
    FROM Projects
    WHERE id = NEW.project_id;
    
    -- Aggiorna il total_funded del creatore
    UPDATE Creator_Users
    SET total_funded = total_funded + NEW.amount
    WHERE user_id = creator_id;
END //

-- Trigger per verificare la disponibilità di una ricompensa prima di assegnarla
CREATE TRIGGER IF NOT EXISTS check_reward_availability_before_funding
BEFORE INSERT ON Funding
FOR EACH ROW
BEGIN
    DECLARE available INT;
    
    -- Se è stata specificata una ricompensa
    IF NEW.reward_id IS NOT NULL THEN
        -- Verifica se la ricompensa è disponibile
        SELECT available_count INTO available
        FROM Rewards
        WHERE id = NEW.reward_id;
        
        -- Se la ricompensa non è disponibile, genera un errore
        IF available <= 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La ricompensa selezionata non è più disponibile';
        END IF;
    END IF;
END //

DELIMITER ;