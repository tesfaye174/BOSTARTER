-- Procedure per l'autenticazione e la gestione degli utenti

DELIMITER //

-- Procedura per registrare un nuovo utente
CREATE PROCEDURE IF NOT EXISTS register_user(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(50),
    IN p_password_hash VARCHAR(255),
    IN p_name VARCHAR(100),
    IN p_surname VARCHAR(100),
    IN p_birth_year INT,
    IN p_birth_place VARCHAR(100),
    OUT p_user_id BIGINT UNSIGNED,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE email_exists INT DEFAULT 0;
    DECLARE nickname_exists INT DEFAULT 0;
    
    -- Verifica se l'email esiste già
    SELECT COUNT(*) INTO email_exists FROM Users WHERE email = p_email;
    
    -- Verifica se il nickname esiste già
    SELECT COUNT(*) INTO nickname_exists FROM Users WHERE nickname = p_nickname;
    
    -- Se email o nickname esistono già, restituisci errore
    IF email_exists > 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Email già registrata';
    ELSEIF nickname_exists > 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Nickname già in uso';
    ELSE
        -- Inserisci il nuovo utente
        INSERT INTO Users (email, nickname, password_hash, name, surname, birth_year, birth_place)
        VALUES (p_email, p_nickname, p_password_hash, p_name, p_surname, p_birth_year, p_birth_place);
        
        -- Ottieni l'ID dell'utente appena inserito
        SET p_user_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Registrazione completata con successo';
    END IF;
END //

-- Procedura per il login di un utente
CREATE PROCEDURE IF NOT EXISTS login_user(
    IN p_login VARCHAR(255), -- Può essere email o nickname
    OUT p_user_id BIGINT UNSIGNED,
    OUT p_email VARCHAR(255),
    OUT p_nickname VARCHAR(50),
    OUT p_name VARCHAR(100),
    OUT p_password_hash VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE user_exists INT DEFAULT 0;
    
    -- Verifica se l'utente esiste (per email o nickname)
    SELECT COUNT(*) INTO user_exists 
    FROM Users 
    WHERE email = p_login OR nickname = p_login;
    
    IF user_exists = 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Utente non trovato';
    ELSE
        -- Ottieni i dati dell'utente
        SELECT id, email, nickname, name, password_hash
        INTO p_user_id, p_email, p_nickname, p_name, p_password_hash
        FROM Users
        WHERE email = p_login OR nickname = p_login;
        
        SET p_success = TRUE;
        SET p_message = 'Utente trovato';
    END IF;
END //

-- Procedura per verificare se un utente è un creatore
CREATE PROCEDURE IF NOT EXISTS check_creator_status(
    IN p_user_id BIGINT UNSIGNED,
    OUT p_is_creator BOOLEAN,
    OUT p_reliability DECIMAL(5,2),
    OUT p_project_count INT UNSIGNED
)
BEGIN
    DECLARE creator_exists INT DEFAULT 0;
    
    -- Verifica se l'utente è un creatore
    SELECT COUNT(*) INTO creator_exists 
    FROM Creator_Users 
    WHERE user_id = p_user_id;
    
    IF creator_exists = 0 THEN
        SET p_is_creator = FALSE;
        SET p_reliability = 0;
        SET p_project_count = 0;
    ELSE
        -- Ottieni i dati del creatore
        SELECT TRUE, reliability, project_count
        INTO p_is_creator, p_reliability, p_project_count
        FROM Creator_Users
        WHERE user_id = p_user_id;
    END IF;
END //

-- Procedura per registrare un utente come creatore
CREATE PROCEDURE IF NOT EXISTS register_creator(
    IN p_user_id BIGINT UNSIGNED,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE creator_exists INT DEFAULT 0;
    
    -- Verifica se l'utente è già un creatore
    SELECT COUNT(*) INTO creator_exists 
    FROM Creator_Users 
    WHERE user_id = p_user_id;
    
    IF creator_exists > 0 THEN
        SET p_success = FALSE;
        SET p_message = 'L\'utente è già registrato come creatore';
    ELSE
        -- Inserisci il nuovo creatore
        INSERT INTO Creator_Users (user_id, project_count, reliability, total_funded)
        VALUES (p_user_id, 0, 0.00, 0.00);
        
        SET p_success = TRUE;
        SET p_message = 'Registrazione come creatore completata con successo';
    END IF;
END //

DELIMITER ;