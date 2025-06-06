-- STORED PROCEDURES PER AUTENTICAZIONE - DATABASE COMPLIANT
-- A.A. 2024/2025 - Corso di Basi di Dati CdS Informatica per il Management

USE bostarter_compliant;

DELIMITER $$

-- Procedura per registrazione utente compatibile con il sistema esistente
DROP PROCEDURE IF EXISTS sp_registra_utente$$
CREATE PROCEDURE sp_registra_utente(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(100),
    IN p_password VARCHAR(255),
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100),
    IN p_anno_nascita YEAR,
    IN p_luogo_nascita VARCHAR(100),
    IN p_tipo_utente VARCHAR(20),
    OUT p_user_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_success = FALSE;
        SET p_message = 'Errore durante la registrazione';
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Verifica se email o nickname esistono già
    IF EXISTS (SELECT 1 FROM utenti WHERE email = p_email OR nickname = p_nickname) THEN
        SET p_success = FALSE;
        SET p_message = 'Email o nickname già esistenti';
        ROLLBACK;
    ELSE
        INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente)
        VALUES (p_email, p_nickname, p_password, p_nome, p_cognome, p_anno_nascita, p_luogo_nascita, p_tipo_utente);
        
        SET p_user_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = 'Registrazione completata con successo';
        
        COMMIT;
    END IF;
END$$

-- Procedura per login utente compatibile con il sistema esistente
DROP PROCEDURE IF EXISTS sp_login_utente$$
CREATE PROCEDURE sp_login_utente(
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    OUT p_user_id INT,
    OUT p_user_data JSON,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_stored_password VARCHAR(255);
    DECLARE v_tipo_utente VARCHAR(50);
    DECLARE v_nickname VARCHAR(100);
    DECLARE v_nome VARCHAR(100);
    DECLARE v_cognome VARCHAR(100);
    DECLARE v_anno_nascita YEAR;
    DECLARE v_luogo_nascita VARCHAR(100);
    DECLARE v_nr_progetti INT;
    DECLARE v_affidabilita DECIMAL(5,2);
    
    SELECT id, password_hash, tipo_utente, nickname, nome, cognome, anno_nascita, luogo_nascita, nr_progetti, affidabilita
    INTO p_user_id, v_stored_password, v_tipo_utente, v_nickname, v_nome, v_cognome, v_anno_nascita, v_luogo_nascita, v_nr_progetti, v_affidabilita
    FROM utenti
    WHERE email = p_email;
    
    IF p_user_id IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Credenziali non valide';
        SET p_user_data = NULL;
    ELSEIF NOT (p_password = v_stored_password OR v_stored_password = p_password) THEN
        -- Verifica password (supporta sia hash che plain per compatibilità)
        SET p_success = FALSE;
        SET p_message = 'Credenziali non valide';
        SET p_user_data = NULL;
        SET p_user_id = NULL;
    ELSE
        SET p_success = TRUE;
        SET p_message = 'Login effettuato con successo';
        
        -- Aggiorna ultimo accesso
        UPDATE utenti SET last_access = CURRENT_TIMESTAMP WHERE id = p_user_id;
        
        -- Crea JSON con dati utente
        SET p_user_data = JSON_OBJECT(
            'id', p_user_id,
            'email', p_email,
            'nickname', v_nickname,
            'nome', v_nome,
            'cognome', v_cognome,
            'anno_nascita', v_anno_nascita,
            'luogo_nascita', v_luogo_nascita,
            'tipo_utente', v_tipo_utente,
            'nr_progetti', COALESCE(v_nr_progetti, 0),
            'affidabilita', COALESCE(v_affidabilita, 0.00)
        );
    END IF;
END$$

-- Procedura per ottenere dati utente per sessioni
DROP PROCEDURE IF EXISTS sp_get_user_data$$
CREATE PROCEDURE sp_get_user_data(
    IN p_user_id INT,
    OUT p_user_data JSON,
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_email VARCHAR(255);
    DECLARE v_nickname VARCHAR(100);
    DECLARE v_nome VARCHAR(100);
    DECLARE v_cognome VARCHAR(100);
    DECLARE v_tipo_utente VARCHAR(50);
    DECLARE v_anno_nascita YEAR;
    DECLARE v_luogo_nascita VARCHAR(100);
    DECLARE v_nr_progetti INT;
    DECLARE v_affidabilita DECIMAL(5,2);
    
    SELECT email, nickname, nome, cognome, tipo_utente, anno_nascita, luogo_nascita, nr_progetti, affidabilita
    INTO v_email, v_nickname, v_nome, v_cognome, v_tipo_utente, v_anno_nascita, v_luogo_nascita, v_nr_progetti, v_affidabilita
    FROM utenti
    WHERE id = p_user_id;
    
    IF v_email IS NOT NULL THEN
        SET p_success = TRUE;
        SET p_user_data = JSON_OBJECT(
            'id', p_user_id,
            'email', v_email,
            'nickname', v_nickname,
            'nome', v_nome,
            'cognome', v_cognome,
            'anno_nascita', v_anno_nascita,
            'luogo_nascita', v_luogo_nascita,
            'tipo_utente', v_tipo_utente,
            'nr_progetti', COALESCE(v_nr_progetti, 0),
            'affidabilita', COALESCE(v_affidabilita, 0.00)
        );
    ELSE
        SET p_success = FALSE;
        SET p_user_data = NULL;
    END IF;
END$$

DELIMITER ;

-- Inserimento competenze base per il sistema
INSERT IGNORE INTO competenze (nome) VALUES 
('Programmazione'),
('Web Development'),
('Database Design'),
('UI/UX Design'),
('Project Management'),
('Marketing'),
('Business Development'),
('Hardware Design'),
('Electronics'),
('3D Modeling'),
('Graphic Design'),
('Testing'),
('DevOps'),
('Mobile Development'),
('AI/Machine Learning');

SHOW PROCEDURE STATUS WHERE Db = 'bostarter_compliant';
