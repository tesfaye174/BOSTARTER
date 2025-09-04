-- Stored Procedures and Triggers for BOSTARTER
USE bostarter;

DELIMITER //

-- Stored Procedure per la registrazione utente
CREATE PROCEDURE sp_register_user(
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_nickname VARCHAR(50),
    IN p_full_name VARCHAR(100),
    IN p_is_creator BOOLEAN
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore nella registrazione utente';
    END;

    START TRANSACTION;
    
    -- Verifica email unica
    IF EXISTS (SELECT 1 FROM users WHERE email = p_email) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Email già registrata';
    END IF;
    
    -- Verifica nickname unico
    IF EXISTS (SELECT 1 FROM users WHERE nickname = p_nickname) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Nickname già in uso';
    END IF;
    
    -- Inserisci nuovo utente
    INSERT INTO users (email, password_hash, nickname, full_name, is_creator)
    VALUES (p_email, p_password, p_nickname, p_full_name, p_is_creator);
    
    COMMIT;
END //

-- Stored Procedure per l'inserimento di un nuovo progetto
CREATE PROCEDURE sp_create_project(
    IN p_creator_id INT,
    IN p_title VARCHAR(100),
    IN p_description TEXT,
    IN p_budget DECIMAL(10,2),
    IN p_deadline DATE,
    IN p_image_url VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore nella creazione del progetto';
    END;

    START TRANSACTION;
    
    -- Verifica che l'utente sia un creator
    IF NOT EXISTS (SELECT 1 FROM users WHERE user_id = p_creator_id AND is_creator = TRUE) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Utente non autorizzato a creare progetti';
    END IF;
    
    -- Verifica deadline futura
    IF p_deadline <= CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La deadline deve essere futura';
    END IF;
    
    -- Inserisci progetto
    INSERT INTO projects (creator_id, title, description, budget, deadline, image_url)
    VALUES (p_creator_id, p_title, p_description, p_budget, p_deadline, p_image_url);
    
    COMMIT;
END //

-- Stored Procedure per effettuare un finanziamento
CREATE PROCEDURE sp_make_funding(
    IN p_project_id INT,
    IN p_backer_id INT,
    IN p_amount DECIMAL(10,2),
    IN p_reward_id INT
)
BEGIN
    DECLARE v_project_status VARCHAR(20);
    DECLARE v_current_amount DECIMAL(10,2);
    DECLARE v_budget DECIMAL(10,2);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Errore nel finanziamento';
    END;

    START TRANSACTION;
    
    -- Verifica stato progetto
    SELECT status, current_amount, budget 
    INTO v_project_status, v_current_amount, v_budget
    FROM projects 
    WHERE project_id = p_project_id
    FOR UPDATE;
    
    IF v_project_status != 'APERTO' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Il progetto non è aperto ai finanziamenti';
    END IF;
    
    -- Verifica reward se specificato
    IF p_reward_id IS NOT NULL THEN
        IF NOT EXISTS (
            SELECT 1 FROM rewards 
            WHERE reward_id = p_reward_id 
            AND project_id = p_project_id
            AND minimum_amount <= p_amount
            AND (quantity_available IS NULL OR quantity_claimed < quantity_available)
        ) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reward non disponibile o importo insufficiente';
        END IF;
    END IF;
    
    -- Inserisci finanziamento
    INSERT INTO funding (project_id, backer_id, amount, reward_id)
    VALUES (p_project_id, p_backer_id, p_amount, p_reward_id);
    
    -- Aggiorna importo progetto
    UPDATE projects 
    SET current_amount = current_amount + p_amount
    WHERE project_id = p_project_id;
    
    -- Aggiorna reward se specificato
    IF p_reward_id IS NOT NULL THEN
        UPDATE rewards
        SET quantity_claimed = quantity_claimed + 1
        WHERE reward_id = p_reward_id;
    END IF;
    
    COMMIT;
END //

-- Trigger per aggiornare l'affidabilità del creator
CREATE TRIGGER trg_update_reliability
AFTER INSERT ON funding
FOR EACH ROW
BEGIN
    DECLARE v_creator_id INT;
    DECLARE v_total_projects INT;
    DECLARE v_funded_projects INT;
    
    -- Ottieni creator_id del progetto
    SELECT creator_id INTO v_creator_id
    FROM projects
    WHERE project_id = NEW.project_id;
    
    -- Calcola totale progetti e progetti finanziati
    SELECT 
        COUNT(DISTINCT p.project_id) as total,
        COUNT(DISTINCT CASE WHEN p.current_amount > 0 THEN p.project_id END) as funded
    INTO v_total_projects, v_funded_projects
    FROM projects p
    WHERE p.creator_id = v_creator_id;
    
    -- Aggiorna affidabilità
    IF v_total_projects > 0 THEN
        UPDATE users
        SET reliability = (v_funded_projects * 100.0 / v_total_projects)
        WHERE user_id = v_creator_id;
    END IF;
END //

-- Trigger per chiudere progetto al raggiungimento del budget
CREATE TRIGGER trg_check_project_completion
AFTER UPDATE ON projects
FOR EACH ROW
BEGIN
    IF NEW.current_amount >= NEW.budget AND NEW.status = 'APERTO' THEN
        UPDATE projects
        SET status = 'CHIUSO'
        WHERE project_id = NEW.project_id;
    END IF;
END //

DELIMITER ;
