-- BOSTARTER Database Schema
-- A.A. 2024/2025

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bostarter;

-- Enable strict mode for better data integrity
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTE';

-- Core Tables --

-- Users table with enhanced validation
CREATE TABLE IF NOT EXISTS Users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    nickname VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    birth_year INT CHECK (birth_year BETWEEN 1900 AND YEAR(CURRENT_DATE)),
    birth_place VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_email (email),
    INDEX idx_user_nickname (nickname)
) ENGINE=InnoDB;

-- Skills table with competency levels
CREATE TABLE IF NOT EXISTS Skills (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competency VARCHAR(100) NOT NULL UNIQUE,
    level INT NOT NULL CHECK (level BETWEEN 0 AND 5),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_skill_competency (competency)
) ENGINE=InnoDB;

-- User-Skill association table
CREATE TABLE IF NOT EXISTS User_Skills (
    user_id BIGINT UNSIGNED,
    skill_id BIGINT UNSIGNED,
    level INT NOT NULL CHECK (level BETWEEN 0 AND 5),
    verified BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (user_id, skill_id),
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES Skills(id) ON DELETE CASCADE,
    INDEX idx_user_skills (user_id, skill_id)
) ENGINE=InnoDB;

-- Admin users table
CREATE TABLE IF NOT EXISTS Admin_Users (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    security_code VARCHAR(255) NOT NULL,
    last_login TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Creator users table with reliability tracking
CREATE TABLE IF NOT EXISTS Creator_Users (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    project_count INT UNSIGNED DEFAULT 0,
    reliability DECIMAL(5,2) DEFAULT 0.00 CHECK (reliability BETWEEN 0 AND 100),
    total_funded DECIMAL(12,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    INDEX idx_creator_reliability (reliability)
) ENGINE=InnoDB;

-- Projects table with enhanced tracking
CREATE TABLE IF NOT EXISTS Projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    creator_id BIGINT UNSIGNED NOT NULL, -- ADDED
    description TEXT NOT NULL,
    budget DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- Assuming this field exists or is needed
    current_funding DECIMAL(12,2) DEFAULT 0.00, -- Referenced by trigger
    backer_count INT UNSIGNED DEFAULT 0, -- Referenced by trigger
    start_date TIMESTAMP NULL, -- Assuming
    end_date TIMESTAMP NULL, -- Assuming
    status ENUM('draft', 'active', 'funded', 'completed', 'cancelled') DEFAULT 'draft', -- Referenced by trigger
    project_type ENUM('hardware', 'software', 'other') NOT NULL, -- Assuming
    visibility ENUM('public', 'private') DEFAULT 'public', -- Assuming
    insert_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES Creator_Users(user_id) ON DELETE CASCADE, -- ADDED
    INDEX idx_project_name (name), -- Assuming
    INDEX idx_project_creator (creator_id), -- ADDED
    INDEX idx_project_status (status), -- Assuming
    INDEX idx_project_type (project_type), -- Assuming
    INDEX idx_project_end_date (end_date) -- Assuming
) ENGINE=InnoDB;

-- Hardware specific project details
CREATE TABLE IF NOT EXISTS Hardware_Components (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL CHECK (price > 0),
    quantity INT NOT NULL CHECK (quantity > 0),
    supplier VARCHAR(100),
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    UNIQUE KEY uk_project_component (project_id, component_name),
    INDEX idx_component_price (price)
) ENGINE=InnoDB;

-- Software profiles for software projects
CREATE TABLE IF NOT EXISTS Software_Profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    profile_name VARCHAR(100) NOT NULL,
    required_experience INT CHECK (required_experience BETWEEN 0 AND 10),
    position_filled BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    UNIQUE KEY uk_project_profile (project_id, profile_name),
    INDEX idx_profile_filled (position_filled)
) ENGINE=InnoDB;

-- Profile required skills
CREATE TABLE IF NOT EXISTS Profile_Skills (
    profile_id BIGINT UNSIGNED,
    skill_id BIGINT UNSIGNED,
    required_level INT CHECK (required_level BETWEEN 0 AND 5),
    PRIMARY KEY (profile_id, skill_id),
    FOREIGN KEY (profile_id) REFERENCES Software_Profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES Skills(id) ON DELETE CASCADE,
    INDEX idx_profile_skills (profile_id, skill_id)
) ENGINE=InnoDB;

-- Project funding with enhanced tracking
CREATE TABLE IF NOT EXISTS Funding (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL CHECK (amount > 0),
    funding_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reward_id BIGINT UNSIGNED,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE RESTRICT,
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES Rewards(id) ON DELETE RESTRICT,
    INDEX idx_funding_project (project_id),
    INDEX idx_funding_user (user_id),
    INDEX idx_funding_date (funding_date)
) ENGINE=InnoDB;

-- Project comments with threading support
CREATE TABLE IF NOT EXISTS Comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED,
    comment_text TEXT NOT NULL,
    comment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_creator_response BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES Comments(id) ON DELETE CASCADE,
    INDEX idx_comment_project (project_id),
    INDEX idx_comment_user (user_id),
    INDEX idx_comment_parent (parent_id)
) ENGINE=InnoDB;

-- Developer applications for software projects
CREATE TABLE IF NOT EXISTS Applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    profile_id BIGINT UNSIGNED NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    cover_letter TEXT,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES Software_Profiles(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_profile (user_id, profile_id),
    INDEX idx_application_status (status)
) ENGINE=InnoDB;

-- MongoDB Collection Structure (in JavaScript format for documentation)
/*
// Event Logging Collection
db.createCollection('event_logs', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['event_type', 'user_id', 'timestamp', 'details'],
            properties: {
                event_type: {
                    bsonType: 'string',
                    enum: ['project_create', 'project_update', 'funding', 'comment', 'application']
                },
                user_id: { bsonType: 'long' },
                timestamp: { bsonType: 'date' },
                details: { bsonType: 'object' },
                ip_address: { bsonType: 'string' },
                user_agent: { bsonType: 'string' }
            }
        }
    }
});

// Create indexes for the event_logs collection
db.event_logs.createIndex({ 'event_type': 1, 'timestamp': -1 });
db.event_logs.createIndex({ 'user_id': 1, 'timestamp': -1 });
*/

-- Create necessary triggers and procedures
DELIMITER //

-- Trigger to update project funding status
CREATE TRIGGER update_project_funding
AFTER INSERT ON Funding
FOR EACH ROW
BEGIN
    UPDATE Projects
    SET current_funding = current_funding + NEW.amount,
        backer_count = (SELECT COUNT(DISTINCT user_id) FROM Funding WHERE project_id = NEW.project_id)
    WHERE id = NEW.project_id;
    
    -- Check if project is fully funded
    UPDATE Projects
    SET status = 'funded'
    WHERE id = NEW.project_id
    AND current_funding >= budget;
END //

-- Trigger to update creator reliability
CREATE TRIGGER update_creator_reliability
AFTER UPDATE ON Projects
FOR EACH ROW
BEGIN
    IF NEW.status = 'funded' AND OLD.status != 'funded' THEN
        UPDATE Creator_Users
        SET reliability = (
            SELECT (COUNT(*) * 100.0 / project_count)
            FROM Projects
            WHERE creator_id = NEW.creator_id
            AND status = 'funded'
        )
        WHERE user_id = NEW.creator_id;
    END IF;
END //

DELIMITER ;

-- Create event to close expired projects
CREATE EVENT IF NOT EXISTS close_expired_projects
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    UPDATE Projects
    SET status = 'closed'
    WHERE status = 'open'
    AND deadline < CURRENT_DATE;