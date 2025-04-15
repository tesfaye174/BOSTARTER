DELIMITER //

-- Update project count for creator
CREATE TRIGGER after_project_insert
AFTER INSERT ON projects
FOR EACH ROW
BEGIN
    UPDATE creator_users 
    SET project_count = project_count + 1
    WHERE user_id = NEW.creator_id;
END//

-- Update creator reliability
CREATE TRIGGER after_funding_insert
AFTER INSERT ON funding
FOR EACH ROW
BEGIN
    DECLARE total_projects INT;
    DECLARE funded_projects INT;
    DECLARE creator_id INT;
    
    SELECT p.creator_id INTO creator_id
    FROM projects p
    WHERE p.project_id = NEW.project_id;
    
    SELECT COUNT(DISTINCT p.project_id) INTO total_projects
    FROM projects p
    WHERE p.creator_id = creator_id;
    
    SELECT COUNT(DISTINCT p.project_id) INTO funded_projects
    FROM projects p
    INNER JOIN funding f ON p.project_id = f.project_id
    WHERE p.creator_id = creator_id;
    
    UPDATE creator_users
    SET reliability = (funded_projects * 100.0 / total_projects)
    WHERE user_id = creator_id;
END//

-- Update project status when funding goal is reached
CREATE TRIGGER after_funding_update_status
AFTER INSERT ON funding
FOR EACH ROW
BEGIN
    DECLARE total_funding DECIMAL(10,2);
    DECLARE project_budget DECIMAL(10,2);
    
    SELECT SUM(amount) INTO total_funding
    FROM funding
    WHERE project_id = NEW.project_id;
    
    SELECT budget INTO project_budget
    FROM projects
    WHERE project_id = NEW.project_id;
    
    IF total_funding >= project_budget THEN
        UPDATE projects
        SET status = 'closed'
        WHERE project_id = NEW.project_id;
    END IF;
END//

DELIMITER ;