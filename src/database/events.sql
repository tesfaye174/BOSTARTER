DELIMITER //

CREATE EVENT check_project_deadlines
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE projects
    SET status = 'closed'
    WHERE deadline < CURDATE()
    AND status = 'open';
END//

DELIMITER ;