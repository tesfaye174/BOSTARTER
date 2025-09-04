-- Test data population for BOSTARTER
USE bostarter;

-- Insert test users
INSERT INTO users (email, password_hash, nickname, full_name, is_creator, is_admin) VALUES
('admin@bostarter.com', SHA2('admin123', 256), 'admin', 'System Admin', false, true),
('mario.rossi@email.com', SHA2('pass123', 256), 'mario_creator', 'Mario Rossi', true, false),
('laura.bianchi@email.com', SHA2('pass123', 256), 'laura_creator', 'Laura Bianchi', true, false),
('giovanni.verdi@email.com', SHA2('pass123', 256), 'giovanni', 'Giovanni Verdi', false, false),
('anna.neri@email.com', SHA2('pass123', 256), 'anna', 'Anna Neri', false, false);

-- Insert skills
INSERT INTO skills (name, description, created_by) VALUES
('Python', 'Programmazione Python', 1),
('React', 'Frontend Development', 1),
('Node.js', 'Backend Development', 1),
('UI/UX', 'User Interface Design', 1),
('DevOps', 'Development Operations', 1);

-- Associate skills to users
INSERT INTO user_skills (user_id, skill_id, proficiency_level) VALUES
(2, 1, 5), -- Mario: Python expert
(2, 3, 4), -- Mario: Node.js advanced
(3, 2, 5), -- Laura: React expert
(3, 4, 4); -- Laura: UI/UX advanced

-- Insert projects
INSERT INTO projects (title, description, budget, creator_id, deadline, image_url) VALUES
('Smart City App', 'App per la gestione intelligente della città', 50000.00, 2, DATE_ADD(CURDATE(), INTERVAL 30 DAY), '/images/smart-city.jpg'),
('EcoTracker', 'Sistema di monitoraggio ambientale', 30000.00, 2, DATE_ADD(CURDATE(), INTERVAL 45 DAY), '/images/eco-tracker.jpg'),
('Social Learning', 'Piattaforma di apprendimento collaborativo', 40000.00, 3, DATE_ADD(CURDATE(), INTERVAL 60 DAY), '/images/social-learning.jpg'),
('HealthBot', 'Chatbot per assistenza sanitaria', 25000.00, 3, DATE_ADD(CURDATE(), INTERVAL 90 DAY), '/images/health-bot.jpg');

-- Insert project required skills
INSERT INTO project_required_skills (project_id, skill_id, minimum_level) VALUES
(1, 1, 4), -- Smart City: Python
(1, 3, 3), -- Smart City: Node.js
(2, 1, 3), -- EcoTracker: Python
(3, 2, 4), -- Social Learning: React
(3, 4, 3), -- Social Learning: UI/UX
(4, 1, 3), -- HealthBot: Python
(4, 2, 3); -- HealthBot: React

-- Insert rewards
INSERT INTO rewards (project_id, title, description, minimum_amount, quantity_available) VALUES
(1, 'Early Access', 'Accesso anticipato all\'app', 100.00, 50),
(1, 'Premium Support', 'Supporto premium per un anno', 500.00, 10),
(2, 'Device Beta', 'Dispositivo beta test', 200.00, 20),
(3, 'Premium Account', 'Account premium lifetime', 300.00, 30);

-- Insert funding
INSERT INTO funding (project_id, backer_id, amount, reward_id) VALUES
(1, 4, 500.00, 2),  -- Giovanni finanzia Smart City
(1, 5, 100.00, 1),  -- Anna finanzia Smart City
(2, 4, 200.00, 3),  -- Giovanni finanzia EcoTracker
(3, 5, 300.00, 4);  -- Anna finanzia Social Learning

-- Insert comments
INSERT INTO comments (project_id, user_id, content) VALUES
(1, 4, 'Progetto molto interessante!'),
(1, 5, 'Quale tecnologia userete per il backend?'),
(2, 4, 'Mi piace l''attenzione all''ambiente'),
(3, 5, 'Ottima iniziativa per l''educazione');

-- Insert applications
INSERT INTO applications (project_id, user_id, motivation, status) VALUES
(1, 3, 'Ho esperienza in progetti simili', 'PENDING'),
(2, 3, 'Vorrei contribuire con le mie competenze', 'ACCEPTED'),
(3, 2, 'Mi interessa il settore educational', 'PENDING');
