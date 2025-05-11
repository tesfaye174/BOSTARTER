-- BOSTARTER - Seed dati di esempio

-- Utenti
INSERT INTO Users (email, nickname, password_hash, name, surname, birth_year, birth_place)
VALUES
('alice@example.com', 'alice', '$2y$10$abcdefghijklmnopqrstuv', 'Alice', 'Rossi', 1990, 'Milano'),
('bob@example.com', 'bob', '$2y$10$abcdefghijklmnopqrstuv', 'Bob', 'Bianchi', 1985, 'Roma'),
('carlo@example.com', 'carlo', '$2y$10$abcdefghijklmnopqrstuv', 'Carlo', 'Verdi', 1995, 'Napoli');

-- Creatori
INSERT INTO Creator_Users (user_id, project_count, reliability, total_funded)
SELECT id, 0, 0, 0 FROM Users WHERE nickname IN ('alice', 'carlo');

-- Progetti
INSERT INTO Projects (name, creator_id, description, budget, current_funding, backer_count, status, project_type)
VALUES
('Smart Lamp', (SELECT id FROM Users WHERE nickname='alice'), 'Lampada intelligente con controllo vocale.', 1000, 0, 0, 'active', 'hardware'),
('App Fitness', (SELECT id FROM Users WHERE nickname='carlo'), 'App mobile per allenamenti personalizzati.', 2000, 0, 0, 'active', 'software');

-- Ricompense
INSERT INTO Rewards (code, description, photo, project_id)
VALUES
('SL-BASIC', 'Lampada base', NULL, (SELECT id FROM Projects WHERE name='Smart Lamp')),
('SL-PREMIUM', 'Lampada premium con sensori', NULL, (SELECT id FROM Projects WHERE name='Smart Lamp')),
('APP-BASIC', 'Accesso base app', NULL, (SELECT id FROM Projects WHERE name='App Fitness')),
('APP-PRO', 'Accesso premium app', NULL, (SELECT id FROM Projects WHERE name='App Fitness'));

-- Skills
INSERT INTO Skills (competency, level, description)
VALUES
('Elettronica', 4, 'Progettazione circuiti elettronici'),
('Sviluppo Mobile', 5, 'App Android/iOS'),
('UI/UX Design', 3, 'Esperienza utente e interfaccia grafica');

-- User Skills
INSERT INTO User_Skills (user_id, skill_id, level, verified)
VALUES
((SELECT id FROM Users WHERE nickname='alice'), 1, 4, TRUE),
((SELECT id FROM Users WHERE nickname='carlo'), 2, 5, TRUE),
((SELECT id FROM Users WHERE nickname='bob'), 3, 3, FALSE);

-- Hardware Components (per Smart Lamp)
INSERT INTO Hardware_Components (project_id, component_name, description, price, quantity, supplier)
VALUES
((SELECT id FROM Projects WHERE name='Smart Lamp'), 'LED RGB', 'LED multicolore', 2.50, 10, 'LED Italia'),
((SELECT id FROM Projects WHERE name='Smart Lamp'), 'Microcontrollore', 'ESP32', 8.00, 1, 'ESP Store');

-- Software Profiles (per App Fitness)
INSERT INTO Software_Profiles (project_id, profile_name, required_experience, position_filled)
VALUES
((SELECT id FROM Projects WHERE name='App Fitness'), 'Mobile Developer', 2, FALSE),
((SELECT id FROM Projects WHERE name='App Fitness'), 'UI/UX Designer', 1, FALSE);

-- Profile Skills (per App Fitness)
INSERT INTO Profile_Skills (profile_id, skill_id, required_level)
VALUES
(1, 2, 5), -- Mobile Developer richiede Sviluppo Mobile
(2, 3, 3); -- UI/UX Designer richiede UI/UX Design

-- Candidature
INSERT INTO Applications (user_id, profile_id, status)
VALUES
((SELECT id FROM Users WHERE nickname='bob'), (SELECT id FROM Software_Profiles WHERE profile_name = 'Mobile Developer' AND project_id = (SELECT id FROM Projects WHERE name='App Fitness')), 'pending');

-- Commenti
INSERT INTO Comments (user_id, project_id, comment_text)
VALUES
((SELECT id FROM Users WHERE nickname='bob'), (SELECT id FROM Projects WHERE name='Smart Lamp'), 'Bellissima idea!'),
((SELECT id FROM Users WHERE nickname='alice'), (SELECT id FROM Projects WHERE name='App Fitness'), 'In bocca al lupo!');

-- Finanziamenti
INSERT INTO Funding (user_id, project_id, amount, payment_status)
VALUES
((SELECT id FROM Users WHERE nickname='bob'), (SELECT id FROM Projects WHERE name='Smart Lamp'), 50, 'completed'),
((SELECT id FROM Users WHERE nickname='alice'), (SELECT id FROM Projects WHERE name='App Fitness'), 100, 'completed');
