-- Drop alias apply_to_project if exists
DROP PROCEDURE IF EXISTS apply_to_project;

-- Create alias stored procedure for apply_to_project
USE bostarter_compliant;
DELIMITER //
CREATE PROCEDURE apply_to_project(
    IN p_user_id INT,
    IN p_project_id INT,
    IN p_profilo_id INT
)
BEGIN
    CALL candidati_progetto(p_user_id, p_project_id, p_profilo_id);
END //
DELIMITER ;

-- Sample data for testing
USE bostarter_compliant;
-- Sample tester user (plain password for testing)
INSERT IGNORE INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente)
VALUES ('test@example.com', 'testuser', 'password', 'Test', 'User', 1990, 'TestCity', 'standard');

-- Sample projects for search testing
INSERT IGNORE INTO progetti (nome, descrizione, budget_richiesto, data_limite, creatore_id, tipo_progetto)
VALUES
('Progetto Alpha', 'Descrizione progetto Alpha', 1000.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'software'),
('Progetto Beta', 'Descrizione progetto Beta', 2000.00, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 1, 'hardware');
