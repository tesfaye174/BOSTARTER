-- BOSTARTER Database Setup 
-- Database and table creation for the crowdfunding platform 

DROP DATABASE IF EXISTS bostarter; 
CREATE DATABASE bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; 
USE bostarter; 

-- Global skills table 
CREATE TABLE skills ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    name VARCHAR(100) NOT NULL UNIQUE, 
    description TEXT, 
    category VARCHAR(50), 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
); 

-- Base users table 
CREATE TABLE users ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    email VARCHAR(255) NOT NULL UNIQUE, 
    nickname VARCHAR(100) NOT NULL UNIQUE, 
    password_hash VARCHAR(255) NOT NULL, 
    first_name VARCHAR(100) NOT NULL, 
    last_name VARCHAR(100) NOT NULL, 
    birth_year YEAR NOT NULL, 
    birth_place VARCHAR(100) NOT NULL, 
    user_type ENUM('standard', 'creator', 'administrator') DEFAULT 'standard', 
    security_code VARCHAR(50) NULL, -- Only for administrators 
    reliability DECIMAL(3,2) DEFAULT 0.00, -- Only for creators (0-100) 
    project_count INT DEFAULT 0, -- Redundancy for creators 
    avatar VARCHAR(255) DEFAULT 'default-avatar.png', 
    bio TEXT, 
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    last_access TIMESTAMP NULL,    
    status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    INDEX idx_user_type (user_type),
    INDEX idx_status (status),
    INDEX idx_registration_date (registration_date),
    INDEX idx_reliability (reliability)
);

-- User skills relationship table
CREATE TABLE user_skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    experience_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    years_experience INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_skill (user_id, skill_id),
    INDEX idx_user_id (user_id),
    INDEX idx_skill_id (skill_id),
    INDEX idx_experience_level (experience_level)
);

-- Projects table
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    subtitle VARCHAR(300),
    description TEXT NOT NULL,
    story TEXT,
    category VARCHAR(50) NOT NULL,
    creator_id INT NOT NULL,
    funding_goal DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0.00,
    backer_count INT DEFAULT 0,
    campaign_duration INT NOT NULL, -- in days
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'pending', 'active', 'successful', 'failed', 'cancelled') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    main_image VARCHAR(255),
    video_url VARCHAR(500),
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    launch_date TIMESTAMP NULL,
    funding_deadline TIMESTAMP NOT NULL,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_creator_id (creator_id),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_is_featured (is_featured),
    INDEX idx_end_date (end_date),
    INDEX idx_funding_goal (funding_goal),
    INDEX idx_current_amount (current_amount)
);

-- Project images table
CREATE TABLE project_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    image_order INT DEFAULT 0,
    alt_text VARCHAR(255),
    is_main BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_image_order (image_order)
);

-- Rewards table
CREATE TABLE rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    estimated_delivery DATE,
    max_backers INT NULL, -- NULL = unlimited
    current_backers INT DEFAULT 0,
    is_available BOOLEAN DEFAULT TRUE,
    reward_order INT DEFAULT 0,
    shipping_included BOOLEAN DEFAULT FALSE,
    digital_reward BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_amount (amount),
    INDEX idx_is_available (is_available),
    INDEX idx_reward_order (reward_order)
);

-- Funding/Backing table
CREATE TABLE fundings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    backer_id INT NOT NULL,
    reward_id INT NULL, -- NULL if no reward selected
    amount DECIMAL(10,2) NOT NULL,
    anonymous BOOLEAN DEFAULT FALSE,
    comment TEXT,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (backer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE SET NULL,
    INDEX idx_project_id (project_id),
    INDEX idx_backer_id (backer_id),
    INDEX idx_reward_id (reward_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at),
    INDEX idx_amount (amount)
);

-- Project updates table
CREATE TABLE project_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    is_public BOOLEAN DEFAULT TRUE, -- FALSE = only for backers
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_is_public (is_public),
    INDEX idx_created_at (created_at)
);

-- Comments table
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT NULL, -- For reply comments
    content TEXT NOT NULL,
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_created_at (created_at)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'project_funded', 'project_successful', 'project_update', etc.
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    data JSON, -- Additional data for the notification
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Remember tokens for "Remember Me" functionality
CREATE TABLE remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(7), -- HEX color code
    is_active BOOLEAN DEFAULT TRUE,
    project_count INT DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
);

-- Insert default categories
INSERT INTO categories (name, slug, description, icon, color, display_order) VALUES
('Technology', 'technology', 'Innovative tech projects and gadgets', 'ri-smartphone-line', '#3176FF', 1),
('Art', 'art', 'Creative art projects and installations', 'ri-palette-line', '#FF6B35', 2),
('Design', 'design', 'Product and graphic design projects', 'ri-pencil-ruler-2-line', '#10B981', 3),
('Music', 'music', 'Music albums, instruments, and audio projects', 'ri-music-line', '#8B5CF6', 4),
('Film', 'film', 'Movies, documentaries, and video content', 'ri-film-line', '#F59E0B', 5),
('Games', 'games', 'Board games, video games, and interactive entertainment', 'ri-gamepad-line', '#EF4444', 6),
('Fashion', 'fashion', 'Clothing, accessories, and fashion innovation', 'ri-shirt-line', '#EC4899', 7),
('Food', 'food', 'Culinary projects, restaurants, and food products', 'ri-restaurant-line', '#F97316', 8),
('Publishing', 'publishing', 'Books, magazines, and written content', 'ri-book-open-line', '#6366F1', 9),
('Crafts', 'crafts', 'Handmade and artisanal products', 'ri-hammer-line', '#84CC16', 10);

-- Insert default skills
INSERT INTO skills (name, description, category) VALUES
-- Technology skills
('Web Development', 'Frontend and backend web development', 'Technology'),
('Mobile Development', 'iOS and Android app development', 'Technology'),
('UI/UX Design', 'User interface and user experience design', 'Technology'),
('Data Science', 'Data analysis and machine learning', 'Technology'),
('Cybersecurity', 'Information security and protection', 'Technology'),

-- Creative skills
('Graphic Design', 'Visual design and branding', 'Design'),
('Photography', 'Professional photography services', 'Art'),
('Video Production', 'Video creation and editing', 'Film'),
('Music Production', 'Audio recording and mixing', 'Music'),
('Writing', 'Content creation and copywriting', 'Publishing'),

-- Business skills
('Marketing', 'Digital and traditional marketing', 'Business'),
('Project Management', 'Planning and execution of projects', 'Business'),
('Finance', 'Financial planning and analysis', 'Business'),
('Sales', 'Sales strategy and execution', 'Business'),
('Customer Service', 'Customer support and relations', 'Business');
