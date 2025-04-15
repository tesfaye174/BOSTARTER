-- BOSTARTER Database Schema

-- Drop existing tables if they exist
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS funding;
DROP TABLE IF EXISTS profile_skills;
DROP TABLE IF EXISTS software_profiles;
DROP TABLE IF EXISTS hardware_components;
DROP TABLE IF EXISTS rewards;
DROP TABLE IF EXISTS project_photos;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS user_skills;
DROP TABLE IF EXISTS competencies;
DROP TABLE IF EXISTS creator_users;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Drop existing views
DROP VIEW IF EXISTS top_creators;
DROP VIEW IF EXISTS top_projects;
DROP VIEW IF EXISTS top_funders;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birth_year INT NOT NULL CHECK (birth_year BETWEEN 1900 AND YEAR(CURRENT_TIMESTAMP)),
    birth_place VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_nickname (nickname)
);

-- Admin users
CREATE TABLE admin_users (
    user_id INT PRIMARY KEY,
    security_code VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Creator users
CREATE TABLE creator_users (
    user_id INT PRIMARY KEY,
    project_count INT DEFAULT 0 CHECK (project_count >= 0),
    reliability DECIMAL(5,2) DEFAULT 0.00 CHECK (reliability BETWEEN 0 AND 100),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Skills/Competencies master list
CREATE TABLE competencies (
    competency_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(user_id) ON DELETE RESTRICT,
    INDEX idx_competency_name (name)
);

-- User Skills
CREATE TABLE user_skills (
    user_id INT,
    competency_id INT,
    level TINYINT CHECK (level >= 0 AND level <= 5),
    PRIMARY KEY (user_id, competency_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (competency_id) REFERENCES competencies(competency_id) ON DELETE CASCADE
);

-- Projects
CREATE TABLE projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    creator_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    budget DECIMAL(10,2) NOT NULL CHECK (budget > 0),
    deadline DATE NOT NULL CHECK (deadline > CURRENT_DATE),
    status ENUM('open', 'closed') DEFAULT 'open',
    project_type ENUM('hardware', 'software') NOT NULL,
    FOREIGN KEY (creator_id) REFERENCES creator_users(user_id) ON DELETE RESTRICT,
    INDEX idx_project_status (status),
    INDEX idx_project_type (project_type),
    INDEX idx_project_deadline (deadline)
);

-- Project Photos
CREATE TABLE project_photos (
    photo_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE
);

-- Rewards
CREATE TABLE rewards (
    reward_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(255),
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    INDEX idx_reward_code (code)
);

-- Hardware Components
CREATE TABLE hardware_components (
    component_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL CHECK (price > 0),
    quantity INT CHECK (quantity > 0),
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    UNIQUE KEY unique_component (project_id, name)
);

-- Software Profiles
CREATE TABLE software_profiles (
    profile_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    UNIQUE KEY unique_profile (project_id, name)
);

-- Profile Required Skills
CREATE TABLE profile_skills (
    profile_id INT,
    competency_id INT,
    level TINYINT CHECK (level >= 0 AND level <= 5),
    PRIMARY KEY (profile_id, competency_id),
    FOREIGN KEY (profile_id) REFERENCES software_profiles(profile_id) ON DELETE CASCADE,
    FOREIGN KEY (competency_id) REFERENCES competencies(competency_id) ON DELETE CASCADE
);

-- Project Funding
CREATE TABLE funding (
    funding_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL CHECK (amount > 0),
    reward_id INT NOT NULL,
    funded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (reward_id) REFERENCES rewards(reward_id) ON DELETE RESTRICT,
    INDEX idx_funding_project (project_id),
    INDEX idx_funding_user (user_id)
);

-- Comments
CREATE TABLE comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response TEXT NULL,
    response_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_comment_project (project_id)
);

-- Software Project Applications
CREATE TABLE applications (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    profile_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES software_profiles(profile_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (profile_id, user_id),
    INDEX idx_application_status (status)
);

-- Create views for statistics
-- Top 3 creators by reliability
CREATE VIEW top_creators AS
SELECT u.nickname, cu.reliability
FROM users u
INNER JOIN creator_users cu ON u.user_id = cu.user_id
ORDER BY cu.reliability DESC
LIMIT 3;

-- Top 3 projects closest to completion
CREATE VIEW top_projects AS
SELECT 
    p.name,
    p.budget,
    COALESCE(SUM(f.amount), 0) as total_funded,
    (p.budget - COALESCE(SUM(f.amount), 0)) as remaining
FROM projects p
LEFT JOIN funding f ON p.project_id = f.project_id
WHERE p.status = 'open'
GROUP BY p.project_id
ORDER BY remaining ASC
LIMIT 3;

-- Top 3 funders
CREATE VIEW top_funders AS
SELECT 
    u.nickname,
    SUM(f.amount) as total_funded
FROM users u
INNER JOIN funding f ON u.user_id = f.user_id
GROUP BY u.user_id
ORDER BY total_funded DESC
LIMIT 3;

-- Create triggers
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

-- Create event to close expired projects
CREATE EVENT close_expired_projects
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE projects
    SET status = 'closed'
    WHERE deadline < CURRENT_DATE AND status = 'open';
END//

DELIMITER ;