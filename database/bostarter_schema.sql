-- Main Tables

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL, -- Store hashed passwords
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `projects` (
  `project_id` INT AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `funding_goal` DECIMAL(10,2) NOT NULL,
  `current_funding` DECIMAL(10,2) DEFAULT 0.00,
  `deadline` DATE NOT NULL,
  `project_status` ENUM('open', 'funded', 'expired', 'cancelled') DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`creator_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `skills` (
  `skill_id` INT AUTO_INCREMENT PRIMARY KEY,
  `skill_name` VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS `user_skills` (
  `user_skill_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `level` TINYINT NOT NULL CHECK (`level` >= 0 AND `level` <= 5), -- Level 0-5
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills`(`skill_id`) ON DELETE CASCADE,
  UNIQUE KEY `user_skill_unique` (`user_id`, `skill_id`)
);

CREATE TABLE IF NOT EXISTS `project_requirements` (
  `requirement_id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `required_level` TINYINT NOT NULL CHECK (`required_level` >= 0 AND `required_level` <= 5),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`project_id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills`(`skill_id`) ON DELETE CASCADE,
  UNIQUE KEY `project_skill_unique` (`project_id`, `skill_id`)
);

CREATE TABLE IF NOT EXISTS `funding` (
  `funding_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `project_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `funding_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reward_choice` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT, -- Prevent user deletion if they funded projects
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`project_id`) ON DELETE RESTRICT -- Prevent project deletion if it has funds
);

CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `project_id` INT NOT NULL,
  `comment_text` TEXT NOT NULL,
  `comment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`project_id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `applications` (
  `application_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `project_id` INT NOT NULL,
  `application_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`project_id`) ON DELETE CASCADE,
  UNIQUE KEY `user_project_application_unique` (`user_id`, `project_id`)
);

-- Stored Procedures

DELIMITER //

CREATE PROCEDURE `register_user`(
    IN p_username VARCHAR(255),
    IN p_password VARCHAR(255), -- Plain password, to be hashed by PHP
    IN p_email VARCHAR(255)
)
BEGIN
    -- Password hashing should be handled in PHP before calling this SP
    INSERT INTO `users` (`username`, `password_hash`, `email`)
    VALUES (p_username, p_password, p_email);
    SELECT LAST_INSERT_ID() AS user_id;
END //

CREATE PROCEDURE `login_user`(
    IN p_username VARCHAR(255)
)
BEGIN
    SELECT `user_id`, `password_hash` FROM `users` WHERE `username` = p_username;
END //

CREATE PROCEDURE `add_user_skill`(
    IN p_user_id INT,
    IN p_skill_name VARCHAR(100),
    IN p_level TINYINT
)
BEGIN
    DECLARE v_skill_id INT;

    -- Find or create skill
    SELECT `skill_id` INTO v_skill_id FROM `skills` WHERE `skill_name` = p_skill_name;
    IF v_skill_id IS NULL THEN
        INSERT INTO `skills` (`skill_name`) VALUES (p_skill_name);
        SET v_skill_id = LAST_INSERT_ID();
    END IF;

    -- Insert or update user skill
    INSERT INTO `user_skills` (`user_id`, `skill_id`, `level`)
    VALUES (p_user_id, v_skill_id, p_level)
    ON DUPLICATE KEY UPDATE `level` = p_level;
    SELECT v_skill_id AS skill_id;
END //

CREATE PROCEDURE `fund_project`(
    IN p_user_id INT,
    IN p_project_id INT,
    IN p_amount DECIMAL(10,2),
    IN p_reward_choice VARCHAR(255)
)
BEGIN
    INSERT INTO `funding` (`user_id`, `project_id`, `amount`, `reward_choice`, `funding_date`)
    VALUES (p_user_id, p_project_id, p_amount, p_reward_choice, NOW());

    UPDATE `projects`
    SET `current_funding` = `current_funding` + p_amount
    WHERE `project_id` = p_project_id;

    SELECT LAST_INSERT_ID() AS funding_id;
END //

CREATE PROCEDURE `post_comment`(
    IN p_user_id INT,
    IN p_project_id INT,
    IN p_comment_text TEXT
)
BEGIN
    INSERT INTO `comments` (`user_id`, `project_id`, `comment_text`, `comment_date`)
    VALUES (p_user_id, p_project_id, p_comment_text, NOW());
    SELECT LAST_INSERT_ID() AS comment_id;
END //

-- Stored procedure for applying to a project.
-- Actual skill matching logic will be in PHP.
-- This SP just records the application.
CREATE PROCEDURE `apply_to_project`(
    IN p_user_id INT,
    IN p_project_id INT
)
BEGIN
    INSERT INTO `applications` (`user_id`, `project_id`, `application_date`, `status`)
    VALUES (p_user_id, p_project_id, NOW(), 'pending')
    ON DUPLICATE KEY UPDATE `application_date` = NOW(), `status` = 'pending'; -- Re-apply updates timestamp
    SELECT LAST_INSERT_ID() AS application_id;
END //


DELIMITER ;

-- SQL Views

CREATE OR REPLACE VIEW `view_open_projects` AS
SELECT
    p.`project_id`,
    p.`title`,
    p.`description`,
    p.`funding_goal`,
    p.`current_funding`,
    p.`deadline`,
    u.`username` AS `creator_username`,
    (p.`funding_goal` - p.`current_funding`) AS `amount_needed`,
    DATEDIFF(p.`deadline`, CURDATE()) AS `days_left`
FROM `projects` p
JOIN `users` u ON p.`creator_id` = u.`user_id`
WHERE p.`project_status` = 'open' AND p.`deadline` >= CURDATE();

-- For 'top_creators_by_reliability', we need a metric.
-- Let's assume reliability is based on the ratio of successfully funded projects.
-- This is a simplified example. A real system might have more complex metrics.
CREATE OR REPLACE VIEW `top_creators_by_reliability` AS
SELECT
    u.`user_id`,
    u.`username` AS `creator_username`,
    COUNT(p.`project_id`) AS `total_projects`,
    SUM(CASE WHEN p.`project_status` = 'funded' THEN 1 ELSE 0 END) AS `successful_projects`,
    (SUM(CASE WHEN p.`project_status` = 'funded' THEN 1 ELSE 0 END) / COUNT(p.`project_id`)) * 100 AS `success_rate_percentage`
FROM `users` u
JOIN `projects` p ON u.`user_id` = p.`creator_id`
GROUP BY u.`user_id`, u.`username`
HAVING COUNT(p.`project_id`) > 0 -- Only creators with projects
ORDER BY `success_rate_percentage` DESC, `successful_projects` DESC
LIMIT 3;


CREATE OR REPLACE VIEW `top_projects_closest_to_goal` AS
SELECT
    p.`project_id`,
    p.`title`,
    p.`funding_goal`,
    p.`current_funding`,
    (p.`current_funding` / p.`funding_goal`) * 100 AS `percentage_funded`,
    (p.`funding_goal` - p.`current_funding`) AS `amount_still_needed`
FROM `projects` p
WHERE p.`project_status` = 'open' AND p.`current_funding` < p.`funding_goal`
ORDER BY `percentage_funded` DESC
LIMIT 3;

CREATE OR REPLACE VIEW `top_users_by_total_donations` AS
SELECT
    u.`user_id`,
    u.`username`,
    SUM(f.`amount`) AS `total_donated`
FROM `users` u
JOIN `funding` f ON u.`user_id` = f.`user_id`
GROUP BY u.`user_id`, u.`username`
ORDER BY `total_donated` DESC
LIMIT 3;

-- Triggers (Example: Update project status if goal reached)
DELIMITER //
CREATE TRIGGER `after_funding_insert_update_project_status`
AFTER INSERT ON `funding`
FOR EACH ROW
BEGIN
    DECLARE v_project_id INT;
    DECLARE v_funding_goal DECIMAL(10,2);
    DECLARE v_current_funding DECIMAL(10,2);

    SET v_project_id = NEW.project_id;

    SELECT `funding_goal`, `current_funding`
    INTO v_funding_goal, v_current_funding
    FROM `projects`
    WHERE `project_id` = v_project_id;

    IF v_current_funding >= v_funding_goal THEN
        UPDATE `projects`
        SET `project_status` = 'funded'
        WHERE `project_id` = v_project_id;
    END IF;
END //
DELIMITER ;

-- Sample Data (Optional, for easier testing - can be commented out for production)
/*
-- Users
INSERT INTO `users` (`username`, `password_hash`, `email`) VALUES
('AliceWonder', 'hashed_password_alice', 'alice@example.com'),
('BobBuilder', 'hashed_password_bob', 'bob@example.com'),
('CharlieInvestor', 'hashed_password_charlie', 'charlie@example.com'),
('DianaDesigner', 'hashed_password_diana', 'diana@example.com');

-- Skills
INSERT INTO `skills` (`skill_name`) VALUES
('PHP'), ('JavaScript'), ('MySQL'), ('MongoDB'), ('Circuit Design'), ('Marketing');

-- Alice's Skills
CALL `add_user_skill` ( (SELECT user_id from `users` where username = 'AliceWonder'), 'PHP', 5);
CALL `add_user_skill` ( (SELECT user_id from `users` where username = 'AliceWonder'), 'JavaScript', 4);
CALL `add_user_skill` ( (SELECT user_id from `users` where username = 'AliceWonder'), 'MySQL', 3);

-- Bob's Skills
CALL `add_user_skill` ( (SELECT user_id from `users` where username = 'BobBuilder'), 'Circuit Design', 5);
CALL `add_user_skill` ( (SELECT user_id from `users` where username = 'BobBuilder'), 'MongoDB', 2);


-- Projects by Alice
INSERT INTO `projects` (`creator_id`, `title`, `description`, `funding_goal`, `deadline`) VALUES
((SELECT user_id from `users` where username = 'AliceWonder'), 'AI Recipe Generator', 'Generates recipes using AI.', 5000.00, DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
((SELECT user_id from `users` where username = 'AliceWonder'), 'Smart Garden System', 'Automated watering and monitoring.', 7500.00, DATE_ADD(CURDATE(), INTERVAL 90 DAY));

-- Project Requirements for Smart Garden System (Software Project)
INSERT INTO `project_requirements` (`project_id`, `skill_id`, `required_level`) VALUES
((SELECT project_id from `projects` where title = 'Smart Garden System'), (SELECT skill_id from `skills` where skill_name = 'PHP'), 4),
((SELECT project_id from `projects` where title = 'Smart Garden System'), (SELECT skill_id from `skills` where skill_name = 'MySQL'), 3);


-- Projects by Bob
INSERT INTO `projects` (`creator_id`, `title`, `description`, `funding_goal`, `deadline`, `project_status`) VALUES
((SELECT user_id from `users` where username = 'BobBuilder'), 'Portable Solar Charger', 'Compact and efficient solar charger.', 3000.00, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'funded'), -- Already funded
((SELECT user_id from `users` where username = 'BobBuilder'), 'DIY Drone Kit', 'Easy-to-assemble drone kit.', 4000.00, DATE_ADD(CURDATE(), INTERVAL 70 DAY));

-- Funding by Charlie
CALL `fund_project`((SELECT user_id from `users` where username = 'CharlieInvestor'), (SELECT project_id from `projects` where title = 'AI Recipe Generator'), 100.00, 'Early Bird Access');
CALL `fund_project`((SELECT user_id from `users` where username = 'CharlieInvestor'), (SELECT project_id from `projects` where title = 'DIY Drone Kit'), 250.00, 'Deluxe Kit');
CALL `fund_project`((SELECT user_id from `users` where username = 'CharlieInvestor'), (SELECT project_id from `projects` where title = 'Portable Solar Charger'), 3000.00, 'Full Product'); -- Funds Bob's project fully

-- Funding by Diana
CALL `fund_project`((SELECT user_id from `users` where username = 'DianaDesigner'), (SELECT project_id from `projects` where title = 'AI Recipe Generator'), 50.00, 'Sticker Pack');

-- Comments
CALL `post_comment`((SELECT user_id from `users` where username = 'CharlieInvestor'), (SELECT project_id from `projects` where title = 'AI Recipe Generator'), 'This looks amazing! Can we suggest features?');
CALL `post_comment`((SELECT user_id from `users` where username = 'AliceWonder'), (SELECT project_id from `projects` where title = 'AI Recipe Generator'), 'Thanks Charlie! Yes, feature suggestions are welcome in our community forum.');
*/