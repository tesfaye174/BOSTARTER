-- Additional Stored Procedures for BOSTARTER
USE bostarter;

DELIMITER //

-- Stored Procedure per aggiungere una competenza al curriculum
CREATE PROCEDURE sp_add_user_skill(
    IN p_user_id INT,
    IN p_skill_id INT,
    IN p_proficiency_level INT
)
BEGIN
    IF p_proficiency_level < 1 OR p_proficiency_level > 5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Livello di competenza deve essere tra 1 e 5';
    END IF;

    INSERT INTO user_skills (user_id, skill_id, proficiency_level)
    VALUES (p_user_id, p_skill_id, p_proficiency_level)
    ON DUPLICATE KEY UPDATE proficiency_level = p_proficiency_level;
END //

-- Stored Procedure per inserire un commento
CREATE PROCEDURE sp_add_comment(
    IN p_project_id INT,
    IN p_user_id INT,
    IN p_content TEXT,
    IN p_parent_id INT
)
BEGIN
    -- Verifica che il progetto esista
    IF NOT EXISTS (SELECT 1 FROM projects WHERE project_id = p_project_id) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Progetto non trovato';
    END IF;

    -- Verifica parent comment se specificato
    IF p_parent_id IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM comments 
        WHERE comment_id = p_parent_id 
        AND project_id = p_project_id
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Commento padre non trovato';
    END IF;

    INSERT INTO comments (project_id, user_id, content, parent_id)
    VALUES (p_project_id, p_user_id, p_content, p_parent_id);
END //

-- Stored Procedure per inviare una candidatura
CREATE PROCEDURE sp_submit_application(
    IN p_project_id INT,
    IN p_user_id INT,
    IN p_motivation TEXT
)
BEGIN
    DECLARE v_project_status VARCHAR(20);
    
    -- Verifica stato progetto
    SELECT status INTO v_project_status
    FROM projects
    WHERE project_id = p_project_id;
    
    IF v_project_status != 'APERTO' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Il progetto non è aperto alle candidature';
    END IF;
    
    -- Verifica candidatura esistente
    IF EXISTS (
        SELECT 1 FROM applications
        WHERE project_id = p_project_id AND user_id = p_user_id
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Hai già inviato una candidatura per questo progetto';
    END IF;
    
    INSERT INTO applications (project_id, user_id, motivation)
    VALUES (p_project_id, p_user_id, p_motivation);
END //

-- Stored Procedure per gestire una candidatura (accetta/rifiuta)
CREATE PROCEDURE sp_manage_application(
    IN p_application_id INT,
    IN p_creator_id INT,
    IN p_status ENUM('ACCEPTED', 'REJECTED')
)
BEGIN
    DECLARE v_project_id INT;
    DECLARE v_project_creator INT;
    
    -- Ottieni info progetto
    SELECT p.project_id, p.creator_id
    INTO v_project_id, v_project_creator
    FROM applications a
    JOIN projects p ON a.project_id = p.project_id
    WHERE a.application_id = p_application_id;
    
    -- Verifica autorizzazione
    IF v_project_creator != p_creator_id THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Non autorizzato a gestire questa candidatura';
    END IF;
    
    UPDATE applications
    SET status = p_status
    WHERE application_id = p_application_id;
END //

-- Stored Procedure per aggiungere un reward
CREATE PROCEDURE sp_add_reward(
    IN p_project_id INT,
    IN p_creator_id INT,
    IN p_title VARCHAR(100),
    IN p_description TEXT,
    IN p_minimum_amount DECIMAL(10,2),
    IN p_quantity INT
)
BEGIN
    -- Verifica autorizzazione
    IF NOT EXISTS (
        SELECT 1 FROM projects
        WHERE project_id = p_project_id
        AND creator_id = p_creator_id
        AND status = 'APERTO'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Non autorizzato ad aggiungere reward';
    END IF;
    
    INSERT INTO rewards (
        project_id, title, description, 
        minimum_amount, quantity_available
    )
    VALUES (
        p_project_id, p_title, p_description,
        p_minimum_amount, p_quantity
    );
END //

-- Stored Procedure per la login
CREATE PROCEDURE sp_user_login(
    IN p_email VARCHAR(255),
    IN p_password_hash VARCHAR(255),
    OUT p_user_id INT,
    OUT p_is_admin BOOLEAN,
    OUT p_security_code VARCHAR(6)
)
BEGIN
    -- Verifica credenziali
    SELECT user_id, is_admin, security_code
    INTO p_user_id, p_is_admin, p_security_code
    FROM users
    WHERE email = p_email
    AND password_hash = p_password_hash;
    
    IF p_user_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Credenziali non valide';
    END IF;
END //

-- Stored Procedure per aggiungere una competenza (admin)
CREATE PROCEDURE sp_admin_add_skill(
    IN p_admin_id INT,
    IN p_skill_name VARCHAR(50),
    IN p_description TEXT
)
BEGIN
    -- Verifica admin
    IF NOT EXISTS (
        SELECT 1 FROM users
        WHERE user_id = p_admin_id
        AND is_admin = TRUE
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Operazione riservata agli admin';
    END IF;
    
    INSERT INTO skills (name, description, created_by)
    VALUES (p_skill_name, p_description, p_admin_id);
END //

DELIMITER ;
