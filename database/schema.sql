-- Database Schema for BOSTARTER
-- MySQL 8.0+

CREATE DATABASE IF NOT EXISTS bostarter;
USE bostarter;

-- Enumerazioni per stati
CREATE TABLE project_status (
    status_id VARCHAR(20) PRIMARY KEY,
    description VARCHAR(100)
);

INSERT INTO project_status VALUES
('APERTO', 'Progetto aperto ai finanziamenti'),
('CHIUSO', 'Progetto ha raggiunto il budget'),
('SCADUTO', 'Data limite superata senza raggiungere budget'),
('COMPLETATO', 'Progetto completato con successo');

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nickname VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    bio TEXT,
    profile_image_url VARCHAR(255),
    is_creator BOOLEAN DEFAULT FALSE,
    is_admin BOOLEAN DEFAULT FALSE,
    security_code VARCHAR(6),
    reliability DECIMAL(5,2) DEFAULT 0.00,
    projects_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_nickname (nickname)
);

-- Skills/Competenze table
CREATE TABLE skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- User Skills junction table
CREATE TABLE user_skills (
    user_id INT,
    skill_id INT,
    proficiency_level INT CHECK (proficiency_level BETWEEN 1 AND 5),
    verified BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (user_id, skill_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);

-- Projects table
CREATE TABLE projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0.00,
    creator_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'APERTO',
    image_url VARCHAR(255),
    deadline DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(user_id),
    FOREIGN KEY (status) REFERENCES project_status(status_id),
    INDEX idx_status (status),
    INDEX idx_deadline (deadline)
);

-- Project Skills required
CREATE TABLE project_required_skills (
    project_id INT,
    skill_id INT,
    minimum_level INT CHECK (minimum_level BETWEEN 1 AND 5),
    PRIMARY KEY (project_id, skill_id),
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);

-- Rewards table
CREATE TABLE rewards (
    reward_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    minimum_amount DECIMAL(10,2) NOT NULL,
    quantity_available INT DEFAULT NULL,
    quantity_claimed INT DEFAULT 0,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    INDEX idx_project_amount (project_id, minimum_amount)
);

-- Funding table
CREATE TABLE funding (
    funding_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    backer_id INT,
    amount DECIMAL(10,2) NOT NULL,
    reward_id INT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id),
    FOREIGN KEY (backer_id) REFERENCES users(user_id),
    FOREIGN KEY (reward_id) REFERENCES rewards(reward_id),
    INDEX idx_project_backer (project_id, backer_id)
);

-- Comments table
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    user_id INT,
    content TEXT NOT NULL,
    parent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (parent_id) REFERENCES comments(comment_id),
    INDEX idx_project_date (project_id, created_at)
);

-- Applications/Candidature table
CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    user_id INT,
    motivation TEXT NOT NULL,
    status ENUM('PENDING', 'ACCEPTED', 'REJECTED') DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY uk_project_user (project_id, user_id),
    INDEX idx_status (status)
);

-- Project Updates table
CREATE TABLE project_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    INDEX idx_project_date (project_id, created_at)
);

-- Categories table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- Project Categories junction
CREATE TABLE project_categories (
    project_id INT,
    category_id INT,
    PRIMARY KEY (project_id, category_id),
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

-- Views for statistics
CREATE VIEW top_creators AS
SELECT u.nickname, u.reliability
FROM users u
WHERE u.is_creator = TRUE
ORDER BY u.reliability DESC
LIMIT 3;

CREATE VIEW near_completion_projects AS
SELECT p.title, p.budget, p.current_amount, (p.budget - p.current_amount) as remaining
FROM projects p
WHERE p.status = 'APERTO'
ORDER BY remaining ASC
LIMIT 3;

CREATE VIEW top_backers AS
SELECT u.nickname, SUM(f.amount) as total_funded
FROM users u
JOIN funding f ON u.user_id = f.backer_id
GROUP BY u.user_id, u.nickname
ORDER BY total_funded DESC
LIMIT 3;

-- Event for closing expired projects
DELIMITER //

CREATE EVENT close_expired_projects
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE
DO
BEGIN
    UPDATE projects 
    SET status = 'SCADUTO'
    WHERE status = 'APERTO' 
    AND deadline < CURDATE();
END //

DELIMITER ;
