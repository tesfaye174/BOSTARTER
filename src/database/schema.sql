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