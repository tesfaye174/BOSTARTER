-- =================================================================
-- BOSTARTER - DATI DI ESEMPIO
-- Popolamento database con dati di test
-- =================================================================

USE bostarter;

-- COMPETENZE BASE (popolate dall'amministratore)
INSERT INTO competenze (nome, descrizione) VALUES
('Python', 'Linguaggio di programmazione Python'),
('Java', 'Linguaggio di programmazione Java'),
('JavaScript', 'Linguaggio di programmazione JavaScript'),
('AI/ML', 'Intelligenza Artificiale e Machine Learning'),
('Database', 'Progettazione e gestione database'),
('Web Development', 'Sviluppo applicazioni web'),
('Mobile Development', 'Sviluppo applicazioni mobile'),
('DevOps', 'Deployment e operations'),
('UI/UX Design', 'Design interfacce utente'),
('Project Management', 'Gestione progetti');

-- UTENTE AMMINISTRATORE
INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza) VALUES
('admin@bostarter.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System', 1990, 'Milano', 'amministratore', 'ADMIN2024');

-- UTENTI CREATORI
INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) VALUES
('mario.rossi@email.com', 'mariorossi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario', 'Rossi', 1985, 'Roma', 'creatore'),
('anna.verdi@email.com', 'annaverdi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna', 'Verdi', 1990, 'Milano', 'creatore');

-- UTENTI STANDARD
INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) VALUES
('giulia.bianchi@email.com', 'giuliabianchi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Giulia', 'Bianchi', 1995, 'Napoli', 'standard'),
('luca.neri@email.com', 'lucaneri', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Luca', 'Neri', 1988, 'Torino', 'standard');

-- PROGETTI (secondo la tabella dei volumi: 10 progetti, 2 progetti per utente)
INSERT INTO progetti (nome, descrizione, creatore_id, tipo_progetto, budget_richiesto, data_limite) VALUES
('SmartHome IoT', 'Sistema domotico intelligente con sensori e controllo remoto', 2, 'hardware', 5000.00, '2025-12-31'),
('Robot Aspirapolvere', 'Robot autonomo per pulizia domestica', 2, 'hardware', 3500.00, '2025-11-30'),
('App Mobile Fitness', 'Applicazione per tracking fitness e allenamenti', 3, 'software', 3000.00, '2025-10-31'),
('Piattaforma E-learning', 'Sistema di gestione corsi online', 3, 'software', 4500.00, '2025-09-30'),
('Drone Delivery', 'Drone per consegne automatiche', 2, 'hardware', 8000.00, '2025-08-31'),
('Smart Garden', 'Sistema di irrigazione intelligente', 3, 'hardware', 2500.00, '2025-07-31'),
('Social Network', 'Piattaforma social per sviluppatori', 2, 'software', 6000.00, '2025-06-30'),
('IoT Weather Station', 'Stazione meteorologica IoT', 3, 'hardware', 1800.00, '2025-05-31'),
('Task Manager App', 'App per gestione progetti e task', 2, 'software', 2200.00, '2025-04-30'),
('VR Training Platform', 'Piattaforma VR per formazione', 3, 'software', 7500.00, '2025-03-31');

-- SKILL UTENTI
INSERT INTO skill_utente (utente_id, competenza_id, livello) VALUES
(4, 1, 4), (4, 3, 3), (4, 6, 5), (4, 9, 3),
(5, 2, 5), (5, 5, 4), (5, 8, 3), (5, 10, 4);

-- REWARD PROGETTI
INSERT INTO reward (codice, progetto_id, descrizione, importo_minimo) VALUES
('SH001', 1, 'Accesso beta SmartHome', 50.00),
('SH002', 1, 'Kit completo SmartHome', 200.00),
('RA001', 2, 'Prototipo robot', 150.00),
('FM001', 3, 'App premium lifetime', 25.00),
('FM002', 3, 'Consulenza personal trainer', 100.00),
('EL001', 4, 'Corso gratuito premium', 80.00),
('DD001', 5, 'Test drone privato', 300.00),
('SG001', 6, 'Kit sensori garden', 75.00),
('SN001', 7, 'Account premium', 40.00),
('WS001', 8, 'Stazione meteo personale', 60.00),
('TM001', 9, 'Licenza premium', 30.00),
('VR001', 10, 'Accesso VR esclusivo', 250.00);

-- COMPONENTI HARDWARE
INSERT INTO componenti_hardware (progetto_id, nome, descrizione, prezzo, quantita) VALUES
(1, 'Sensore temperatura', 'Sensore DHT22 per temperatura e umidità', 15.50, 10),
(1, 'Microcontrollore', 'Arduino Uno R3', 25.00, 5),
(2, 'Motore brushless', 'Motore per aspirazione', 45.00, 2),
(2, 'Sensore lidar', 'Sensore per navigazione', 120.00, 1),
(5, 'Eliche carbonio', 'Set eliche per drone', 35.00, 4),
(6, 'Pompa acqua', 'Pompa per irrigazione', 28.00, 3),
(8, 'Sensore pressione', 'Barometro digitale', 18.00, 2);

-- PROFILI SOFTWARE
INSERT INTO profili_software (progetto_id, nome, descrizione, max_contributori) VALUES
(3, 'Sviluppatore Mobile', 'Sviluppo app iOS/Android', 2),
(3, 'UI/UX Designer', 'Design interfacce utente', 1),
(4, 'Full Stack Developer', 'Sviluppo frontend e backend', 3),
(4, 'DevOps Engineer', 'Deployment e infrastruttura', 1),
(7, 'Backend Developer', 'Sviluppo API e database', 2),
(7, 'Frontend Developer', 'Sviluppo interfaccia web', 2),
(9, 'Mobile Developer', 'Sviluppo app mobile', 1),
(10, 'VR Developer', 'Sviluppo applicazioni VR', 2);

-- SKILL RICHIESTE PER PROFILI
INSERT INTO skill_richieste_profilo (profilo_id, competenza_id, livello) VALUES
(1, 7, 4), (1, 3, 3),
(2, 9, 4),
(3, 6, 4), (3, 5, 3),
(4, 8, 4),
(5, 1, 4), (5, 5, 4),
(6, 3, 4), (6, 6, 3),
(7, 7, 3),
(8, 1, 4), (8, 4, 3);

-- FINANZIAMENTI (3 finanziamenti per progetto come da tabella volumi)
INSERT INTO finanziamenti (utente_id, progetto_id, reward_id, importo, stato_pagamento) VALUES
(4, 1, 1, 50.00, 'completato'), (5, 1, 2, 200.00, 'completato'), (4, 1, 1, 50.00, 'completato'),
(4, 2, 3, 150.00, 'completato'), (5, 2, 3, 150.00, 'completato'), (4, 2, 3, 150.00, 'completato'),
(4, 3, 4, 25.00, 'completato'), (5, 3, 5, 100.00, 'completato'), (4, 3, 4, 25.00, 'completato'),
(5, 4, 6, 80.00, 'completato'), (4, 4, 6, 80.00, 'completato'), (5, 4, 6, 80.00, 'completato'),
(4, 5, 7, 300.00, 'completato'), (5, 5, 7, 300.00, 'completato'), (4, 5, 7, 300.00, 'completato'),
(5, 6, 8, 75.00, 'completato'), (4, 6, 8, 75.00, 'completato'), (5, 6, 8, 75.00, 'completato'),
(4, 7, 9, 40.00, 'completato'), (5, 7, 9, 40.00, 'completato'), (4, 7, 9, 40.00, 'completato'),
(5, 8, 10, 60.00, 'completato'), (4, 8, 10, 60.00, 'completato'), (5, 8, 10, 60.00, 'completato'),
(4, 9, 11, 30.00, 'completato'), (5, 9, 11, 30.00, 'completato'), (4, 9, 11, 30.00, 'completato'),
(5, 10, 12, 250.00, 'completato'), (4, 10, 12, 250.00, 'completato'), (5, 10, 12, 250.00, 'completato');

-- COMMENTI
INSERT INTO commenti (utente_id, progetto_id, testo) VALUES
(4, 1, 'Progetto molto interessante! Quando sarà disponibile il beta?'),
(5, 2, 'Il robot supporterà anche la mappatura degli ambienti?'),
(4, 3, 'Ottima idea per l''app fitness, supporterete Android?'),
(5, 4, 'Che tipo di corsi saranno disponibili sulla piattaforma?'),
(4, 7, 'Sarà possibile integrare repository GitHub?');

-- RISPOSTE AI COMMENTI
INSERT INTO risposte_commenti (commento_id, creatore_id, testo) VALUES
(1, 2, 'Il beta sarà disponibile entro fine anno!'),
(2, 2, 'Sì, includerà la mappatura LIDAR avanzata.'),
(3, 3, 'Certamente, svilupperemo per iOS e Android.'),
(4, 3, 'Offriremo corsi di programmazione, design e business.');

-- CANDIDATURE
INSERT INTO candidature (utente_id, progetto_id, profilo_id, stato) VALUES
(4, 3, 1, 'accettata'),
(5, 4, 3, 'in_attesa'),
(4, 7, 5, 'accettata'),
(5, 10, 8, 'in_attesa');

-- LOG ATTIVITÀ
INSERT INTO log_attivita (tipo_attivita, utente_id, progetto_id, descrizione) VALUES
('utente_registrato', 1, NULL, 'Nuovo utente amministratore registrato'),
('utente_registrato', 2, NULL, 'Nuovo utente creatore registrato'),
('utente_registrato', 3, NULL, 'Nuovo utente creatore registrato'),
('progetto_creato', 2, 1, 'Creato progetto SmartHome IoT'),
('progetto_creato', 3, 3, 'Creato progetto App Mobile Fitness'),
('finanziamento_effettuato', 4, 1, 'Finanziamento €50 per SmartHome IoT'),
('finanziamento_effettuato', 5, 1, 'Finanziamento €200 per SmartHome IoT'),
('candidatura_inviata', 4, 3, 'Candidatura inviata per Mobile Developer'),
('candidatura_accettata', 4, 3, 'Candidatura accettata per Mobile Developer');