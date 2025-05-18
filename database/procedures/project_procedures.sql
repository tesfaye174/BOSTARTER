-- Procedure per la gestione dei progetti e il collegamento con i creatori

DELIMITER //

-- Procedura per creare un nuovo progetto
CREATE PROCEDURE IF NOT EXISTS create_project(
    IN p_name VARCHAR(255),
    IN p_creator_id BIGINT UNSIGNED,
    IN p_description TEXT,
    IN p_budget DECIMAL(12,2),
    IN p_project_type ENUM('hardware', 'software', 'other'),
    IN p_end_date TIMESTAMP,
    OUT p_project_id BIGINT UNSIGNED,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE creator_exists INT DEFAULT 0;
    DECLARE project_exists INT DEFAULT 0;
    
    -- Verifica se l'utente è un creatore
    SELECT COUNT(*) INTO creator_exists 
    FROM Creator_Users 
    WHERE user_id = p_creator_id;
    
    -- Verifica se esiste già un progetto con lo stesso nome
    SELECT COUNT(*) INTO project_exists 
    FROM Projects 
    WHERE name = p_name;
    
    IF creator_exists = 0 THEN
        SET p_success = FALSE;
        SET p_message = 'L\'utente non è registrato come creatore';
    ELSEIF project_exists > 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Esiste già un progetto con questo nome';
    ELSE
        -- Inserisci il nuovo progetto
        INSERT INTO Projects (
            name, 
            creator_id, 
            description, 
            budget, 
            project_type, 
            start_date,
            end_date,
            status
        )
        VALUES (
            p_name, 
            p_creator_id, 
            p_description, 
            p_budget, 
            p_project_type, 
            CURRENT_TIMESTAMP,
            p_end_date,
            'draft'
        );
        
        -- Ottieni l'ID del progetto appena inserito
        SET p_project_id = LAST_INSERT_ID();
        
        -- Aggiorna il conteggio dei progetti del creatore
        UPDATE Creator_Users 
        SET project_count = project_count + 1 
        WHERE user_id = p_creator_id;
        
        SET p_success = TRUE;
        SET p_message = 'Progetto creato con successo';
    END IF;
END //

-- Procedura per aggiungere una ricompensa a un progetto
CREATE PROCEDURE IF NOT EXISTS add_project_reward(
    IN p_project_id BIGINT UNSIGNED,
    IN p_name VARCHAR(255),
    IN p_description TEXT,
    IN p_amount DECIMAL(10,2),
    IN p_limit INT,
    OUT p_reward_id BIGINT UNSIGNED,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE project_exists INT DEFAULT 0;
    
    -- Verifica se il progetto esiste
    SELECT COUNT(*) INTO project_exists 
    FROM Projects 
    WHERE id = p_project_id;
    
    IF project_exists = 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Il progetto specificato non esiste';
    ELSE
        -- Inserisci la nuova ricompensa
        INSERT INTO Rewards (
            project_id,
            name,
            description,
            amount,
            available_count
        )
        VALUES (
            p_project_id,
            p_name,
            p_description,
            p_amount,
            p_limit
        );
        
        -- Ottieni l'ID della ricompensa appena inserita
        SET p_reward_id = LAST_INSERT_ID();
        
        SET p_success = TRUE;
        SET p_message = 'Ricompensa aggiunta con successo';
    END IF;
END //

-- Procedura per pubblicare un progetto (cambiare lo stato da draft a active)
CREATE PROCEDURE IF NOT EXISTS publish_project(
    IN p_project_id BIGINT UNSIGNED,
    IN p_creator_id BIGINT UNSIGNED,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE project_exists INT DEFAULT 0;
    DECLARE is_owner INT DEFAULT 0;
    DECLARE current_status VARCHAR(20);
    
    -- Verifica se il progetto esiste e se l'utente è il proprietario
    SELECT COUNT(*), status INTO project_exists, current_status
    FROM Projects 
    WHERE id = p_project_id AND creator_id = p_creator_id;
    
    IF project_exists = 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Progetto non trovato o non sei il proprietario';
    ELSEIF current_status != 'draft' THEN
        SET p_success = FALSE;
        SET p_message = 'Il progetto non è in stato di bozza';
    ELSE
        -- Cambia lo stato del progetto a 'active'
        UPDATE Projects 
        SET status = 'active' 
        WHERE id = p_project_id;
        
        SET p_success = TRUE;
        SET p_message = 'Progetto pubblicato con successo';
    END IF;
END //

-- Procedura per ottenere i progetti di un creatore
CREATE PROCEDURE IF NOT EXISTS get_creator_projects(
    IN p_creator_id BIGINT UNSIGNED
)
BEGIN
    -- Seleziona tutti i progetti del creatore
    SELECT 
        p.id,
        p.name,
        p.description,
        p.budget,
        p.current_funding,
        p.backer_count,
        p.start_date,
        p.end_date,
        p.status,
        p.project_type,
        p.visibility,
        p.insert_date,
        p.updated_at
    FROM 
        Projects p
    WHERE 
        p.creator_id = p_creator_id
    ORDER BY 
        p.insert_date DESC;
END //

-- Procedura per finanziare un progetto
CREATE PROCEDURE IF NOT EXISTS fund_project(
    IN p_project_id BIGINT UNSIGNED,
    IN p_user_id BIGINT UNSIGNED,
    IN p_amount DECIMAL(10,2),
    IN p_reward_id BIGINT UNSIGNED,
    OUT p_funding_id BIGINT UNSIGNED,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE project_exists INT DEFAULT 0;
    DECLARE project_status VARCHAR(20);
    DECLARE reward_exists INT DEFAULT 0;
    DECLARE reward_available INT DEFAULT 0;
    
    -- Verifica se il progetto esiste e il suo stato
    SELECT COUNT(*), status INTO project_exists, project_status
    FROM Projects 
    WHERE id = p_project_id;
    
    -- Se è stata specificata una ricompensa, verifica se esiste e se è disponibile
    IF p_reward_id IS NOT NULL THEN
        SELECT COUNT(*) INTO reward_exists
        FROM Rewards 
        WHERE id = p_reward_id AND project_id = p_project_id;
        
        IF reward_exists > 0 THEN
            SELECT available_count INTO reward_available
            FROM Rewards 
            WHERE id = p_reward_id;
        END IF;
    END IF;
    
    IF project_exists = 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Il progetto specificato non esiste';
    ELSEIF project_status != 'active' THEN
        SET p_success = FALSE;
        SET p_message = 'Il progetto non è attivo';
    ELSEIF p_reward_id IS NOT NULL AND reward_exists = 0 THEN
        SET p_success = FALSE;
        SET p_message = 'La ricompensa specificata non esiste per questo progetto';
    ELSEIF p_reward_id IS NOT NULL AND reward_available <= 0 THEN
        SET p_success = FALSE;
        SET p_message = 'La ricompensa specificata non è più disponibile';
    ELSE
        -- Inserisci il nuovo finanziamento
        INSERT INTO Funding (
            user_id,
            project_id,
            amount,
            reward_id,
            date
        )
        VALUES (
            p_user_id,
            p_project_id,
            p_amount,
            p_reward_id,
            CURRENT_TIMESTAMP
        );
        
        -- Ottieni l'ID del finanziamento appena inserito
        SET p_funding_id = LAST_INSERT_ID();
        
        -- Aggiorna il finanziamento corrente del progetto
        UPDATE Projects 
        SET 
            current_funding = current_funding + p_amount,
            backer_count = backer_count + 1
        WHERE id = p_project_id;
        
        -- Se è stata specificata una ricompensa, aggiorna il conteggio disponibile
        IF p_reward_id IS NOT NULL THEN
            UPDATE Rewards 
            SET available_count = available_count - 1 
            WHERE id = p_reward_id;
        END IF;
        
        -- Aggiorna lo stato del progetto se il finanziamento ha raggiunto o superato il budget
        UPDATE Projects 
        SET status = 'funded' 
        WHERE id = p_project_id AND current_funding >= budget AND status = 'active';
        
        SET p_success = TRUE;
        SET p_message = 'Finanziamento effettuato con successo';
    END IF;
END //

DELIMITER ;