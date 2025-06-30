-- CANDIDATURE table for software project role applications
-- This table handles user applications to help with software projects

CREATE TABLE IF NOT EXISTS CANDIDATURE (
    candidature_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    skill_id INT NOT NULL,
    motivation TEXT NOT NULL,
    experience_years INT DEFAULT 0,
    portfolio_url VARCHAR(500),
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    creator_response TEXT,
    response_date TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES PROJECTS(project_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES SKILLS(skill_id) ON DELETE CASCADE,
    
    -- Prevent duplicate applications for same user/project/skill combination
    UNIQUE KEY unique_application (user_id, project_id, skill_id),
    
    INDEX idx_project_status (project_id, status),
    INDEX idx_user_applications (user_id, application_date),
    INDEX idx_skill_applications (skill_id, status)
);

-- Trigger to update project contributor count when application is accepted
DELIMITER //
CREATE TRIGGER update_project_contributors_on_accept
    AFTER UPDATE ON CANDIDATURE
    FOR EACH ROW
BEGIN
    IF NEW.status = 'accepted' AND OLD.status != 'accepted' THEN
        UPDATE PROJECTS 
        SET current_contributors = (
            SELECT COUNT(*) 
            FROM CANDIDATURE 
            WHERE project_id = NEW.project_id AND status = 'accepted'
        )
        WHERE project_id = NEW.project_id;
    END IF;
END //

-- Trigger to update project contributor count when application is rejected after being accepted
CREATE TRIGGER update_project_contributors_on_reject
    AFTER UPDATE ON CANDIDATURE
    FOR EACH ROW
BEGIN
    IF OLD.status = 'accepted' AND NEW.status != 'accepted' THEN
        UPDATE PROJECTS 
        SET current_contributors = (
            SELECT COUNT(*) 
            FROM CANDIDATURE 
            WHERE project_id = NEW.project_id AND status = 'accepted'
        )
        WHERE project_id = NEW.project_id;
    END IF;
END //
DELIMITER ;

-- Add current_contributors column to PROJECTS table if it doesn't exist
ALTER TABLE PROJECTS ADD COLUMN IF NOT EXISTS current_contributors INT DEFAULT 0;

-- Initialize current_contributors for existing projects
UPDATE PROJECTS SET current_contributors = (
    SELECT COUNT(*) 
    FROM CANDIDATURE 
    WHERE CANDIDATURE.project_id = PROJECTS.project_id AND status = 'accepted'
);
