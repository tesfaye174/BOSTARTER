-- Script per aggiornare le tabelle esistenti e garantire la compatibilità con le nuove funzionalità

-- Assicurati di essere nel database corretto
USE bostarter;

-- Aggiorna la tabella Users se necessario
ALTER TABLE Users
MODIFY COLUMN password_hash VARCHAR(255) NOT NULL,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD INDEX IF NOT EXISTS idx_user_email (email),
ADD INDEX IF NOT EXISTS idx_user_nickname (nickname);

-- Aggiorna la tabella Projects se necessario
ALTER TABLE Projects
ADD COLUMN IF NOT EXISTS creator_id BIGINT UNSIGNED NOT NULL AFTER name,
ADD COLUMN IF NOT EXISTS current_funding DECIMAL(12,2) DEFAULT 0.00 AFTER budget,
ADD COLUMN IF NOT EXISTS backer_count INT UNSIGNED DEFAULT 0 AFTER current_funding,
ADD COLUMN IF NOT EXISTS start_date TIMESTAMP NULL AFTER backer_count,
ADD COLUMN IF NOT EXISTS end_date TIMESTAMP NULL AFTER start_date,
ADD COLUMN IF NOT EXISTS status ENUM('draft', 'active', 'funded', 'completed', 'cancelled') DEFAULT 'draft' AFTER end_date,
ADD COLUMN IF NOT EXISTS project_type ENUM('hardware', 'software', 'other') NOT NULL DEFAULT 'other' AFTER status,
ADD COLUMN IF NOT EXISTS visibility ENUM('public', 'private') DEFAULT 'public' AFTER project_type,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER insert_date,
ADD FOREIGN KEY IF NOT EXISTS (creator_id) REFERENCES Creator_Users(user_id) ON DELETE CASCADE,
ADD INDEX IF NOT EXISTS idx_project_creator (creator_id),
ADD INDEX IF NOT EXISTS idx_project_status (status),
ADD INDEX IF NOT EXISTS idx_project_type (project_type),
ADD INDEX IF NOT EXISTS idx_project_end_date (end_date);

-- Crea o aggiorna la tabella Rewards
CREATE TABLE IF NOT EXISTS Rewards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    available_count INT NOT NULL DEFAULT 0,
    claimed_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    INDEX idx_reward_project (project_id)
) ENGINE=InnoDB;

-- Crea o aggiorna la tabella Funding
CREATE TABLE IF NOT EXISTS Funding (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reward_id BIGINT UNSIGNED,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES Rewards(id) ON DELETE SET NULL,
    INDEX idx_funding_user (user_id),
    INDEX idx_funding_project (project_id),
    INDEX idx_funding_date (date)
) ENGINE=InnoDB;

-- Crea o aggiorna la tabella Comments
CREATE TABLE IF NOT EXISTS Comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    text TEXT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response TEXT,
    response_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    INDEX idx_comment_user (user_id),
    INDEX idx_comment_project (project_id),
    INDEX idx_comment_date (date)
) ENGINE=InnoDB;

-- Crea o aggiorna la tabella Hardware_Components
CREATE TABLE IF NOT EXISTS Hardware_Components (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL CHECK (price > 0),
    quantity INT NOT NULL CHECK (quantity > 0),
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    INDEX idx_component_project (project_id)
) ENGINE=InnoDB;

-- Crea o aggiorna la tabella Software_Profiles
CREATE TABLE IF NOT EXISTS Software_Profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    profile_name VARCHAR(100) NOT NULL,
    description TEXT,
    required_skills TEXT,
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    INDEX idx_profile_project (project_id)
) ENGINE=InnoDB;

-- Crea o aggiorna la tabella Candidatures
CREATE TABLE IF NOT EXISTS Candidatures (
    user_id BIGINT UNSIGNED NOT NULL,
    profile_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_date TIMESTAMP NULL,
    PRIMARY KEY (user_id, profile_id, project_id),
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES Software_Profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE,
    INDEX idx_candidature_status (status)
) ENGINE=InnoDB;