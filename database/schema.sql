-- BOSTARTER Database Schema
-- A.A. 2024/2025

-- Creazione del database se non esiste
CREATE DATABASE IF NOT EXISTS bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bostarter;

-- Tabella Utente
CREATE TABLE IF NOT EXISTS Utente (
    email VARCHAR(255) PRIMARY KEY,
    nickname VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- Store hashed passwords
    nome VARCHAR(100),
    cognome VARCHAR(100),
    anno_nascita INT,
    luogo_nascita VARCHAR(100)
);

-- Tabella Competenza
CREATE TABLE IF NOT EXISTS Competenza (
    nome VARCHAR(100) PRIMARY KEY
);

-- Tabella associativa Utente_Skill
CREATE TABLE IF NOT EXISTS Utente_Skill (
    utente_email VARCHAR(255),
    competenza_nome VARCHAR(100),
    livello INT CHECK (livello >= 0 AND livello <= 5),
    PRIMARY KEY (utente_email, competenza_nome),
    FOREIGN KEY (utente_email) REFERENCES Utente(email) ON DELETE CASCADE,
    FOREIGN KEY (competenza_nome) REFERENCES Competenza(nome) ON DELETE CASCADE
);

-- Tabella Amministratore (sottoclasse di Utente)
CREATE TABLE IF NOT EXISTS Amministratore (
    utente_email VARCHAR(255) PRIMARY KEY,
    codice_sicurezza VARCHAR(255) NOT NULL,
    FOREIGN KEY (utente_email) REFERENCES Utente(email) ON DELETE CASCADE
);

-- Tabella Creatore (sottoclasse di Utente)
CREATE TABLE IF NOT EXISTS Creatore (
    utente_email VARCHAR(255) PRIMARY KEY,
    nr_progetti INT DEFAULT 0, -- Campo ridondante, gestito da trigger
    affidabilita DECIMAL(5, 2) DEFAULT 0.00, -- Calcolata da trigger
    FOREIGN KEY (utente_email) REFERENCES Utente(email) ON DELETE CASCADE
);

-- Tabella Progetto
CREATE TABLE IF NOT EXISTS Progetto (
    nome VARCHAR(255) PRIMARY KEY,
    descrizione TEXT,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    budget DECIMAL(10, 2) NOT NULL,
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    creatore_email VARCHAR(255) NOT NULL,
    tipo ENUM('hardware', 'software') NOT NULL,
    FOREIGN KEY (creatore_email) REFERENCES Creatore(utente_email) ON DELETE CASCADE
);

-- Tabella Foto_Progetto
CREATE TABLE IF NOT EXISTS Foto_Progetto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_nome VARCHAR(255) NOT NULL,
    url_foto VARCHAR(255) NOT NULL, -- O BLOB se si memorizzano direttamente nel DB
    FOREIGN KEY (progetto_nome) REFERENCES Progetto(nome) ON DELETE CASCADE
);

-- Tabella Reward
CREATE TABLE IF NOT EXISTS Reward (
    codice VARCHAR(50) PRIMARY KEY,
    progetto_nome VARCHAR(255) NOT NULL,
    descrizione TEXT,
    url_foto VARCHAR(255), -- O BLOB
    FOREIGN KEY (progetto_nome) REFERENCES Progetto(nome) ON DELETE CASCADE
);

-- Tabella Componente_Hardware (solo per progetti hardware)
CREATE TABLE IF NOT EXISTS Componente_Hardware (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_nome VARCHAR(255) NOT NULL,
    nome_componente VARCHAR(100) NOT NULL,
    descrizione TEXT,
    prezzo DECIMAL(10, 2) NOT NULL,
    quantita INT NOT NULL CHECK (quantita > 0),
    FOREIGN KEY (progetto_nome) REFERENCES Progetto(nome) ON DELETE CASCADE,
    UNIQUE (progetto_nome, nome_componente) -- Un componente è unico per progetto
);

-- Tabella Profilo_Software (solo per progetti software)
CREATE TABLE IF NOT EXISTS Profilo_Software (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_nome VARCHAR(255) NOT NULL,
    nome_profilo VARCHAR(100) NOT NULL,
    FOREIGN KEY (progetto_nome) REFERENCES Progetto(nome) ON DELETE CASCADE,
    UNIQUE(progetto_nome, nome_profilo)
);

-- Tabella associativa Skill_Profilo
CREATE TABLE IF NOT EXISTS Skill_Profilo (
    profilo_id INT,
    competenza_nome VARCHAR(100),
    livello_richiesto INT CHECK (livello_richiesto >= 0 AND livello_richiesto <= 5),
    PRIMARY KEY (profilo_id, competenza_nome),
    FOREIGN KEY (profilo_id) REFERENCES Profilo_Software(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_nome) REFERENCES Competenza(nome) ON DELETE CASCADE
);

-- Tabella Finanziamento
CREATE TABLE IF NOT EXISTS Finanziamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_email VARCHAR(255) NOT NULL,
    progetto_nome VARCHAR(255) NOT NULL,
    importo DECIMAL(10, 2) NOT NULL,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reward_codice VARCHAR(50) NOT NULL,
    FOREIGN KEY (utente_email) REFERENCES Utente(email) ON DELETE RESTRICT, -- Un utente non può essere cancellato se ha finanziamenti
    FOREIGN KEY (progetto_nome) REFERENCES Progetto(nome) ON DELETE CASCADE,
    FOREIGN KEY (reward_codice) REFERENCES Reward(codice) ON DELETE RESTRICT -- Una reward non può essere cancellata se associata a finanziamenti
);

-- Tabella Commento
CREATE TABLE IF NOT EXISTS Commento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_email VARCHAR(255) NOT NULL,
    progetto_nome VARCHAR(255) NOT NULL,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    testo TEXT NOT NULL,
    FOREIGN KEY (utente_email) REFERENCES Utente(email) ON DELETE CASCADE,
    FOREIGN KEY (progetto_nome) REFERENCES Progetto(nome) ON DELETE CASCADE
);

-- Tabella Risposta_Commento
CREATE TABLE IF NOT EXISTS Risposta_Commento (
    commento_id INT PRIMARY KEY,
    creatore_email VARCHAR(255) NOT NULL, -- Chi risponde è il creatore del progetto
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    testo TEXT NOT NULL,
    FOREIGN KEY (commento_id) REFERENCES Commento(id) ON DELETE CASCADE,
    FOREIGN KEY (creatore_email) REFERENCES Creatore(utente_email) ON DELETE CASCADE
);

-- Tabella Candidatura (solo per progetti software)
CREATE TABLE IF NOT EXISTS Candidatura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_email VARCHAR(255) NOT NULL,
    profilo_id INT NOT NULL,
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('inviata', 'accettata', 'rifiutata') DEFAULT 'inviata',
    FOREIGN KEY (utente_email) REFERENCES Utente(email) ON DELETE CASCADE,
    FOREIGN KEY (profilo_id) REFERENCES Profilo_Software(id) ON DELETE CASCADE,
    UNIQUE (utente_email, profilo_id) -- Un utente può candidarsi una sola volta per profilo
);

-- Viste per Statistiche --

-- Statistica 1: Classifica Creatori per Affidabilità (Top 3)
CREATE OR REPLACE VIEW ClassificaCreatoriAffidabilita AS
SELECT nickname
FROM Utente u
JOIN Creatore c ON u.email = c.utente_email
ORDER BY c.affidabilita DESC
LIMIT 3;

-- Statistica 2: Progetti Aperti più Vicini al Completamento (Top 3)
CREATE OR REPLACE VIEW ProgettiViciniCompletamento AS
SELECT
    p.nome,
    p.budget,
    COALESCE(SUM(f.importo), 0) AS totale_finanziato,
    (p.budget - COALESCE(SUM(f.importo), 0)) AS differenza_budget
FROM Progetto p
LEFT JOIN Finanziamento f ON p.nome = f.progetto_nome
WHERE p.stato = 'aperto'
GROUP BY p.nome, p.budget
HAVING differenza_budget > 0 -- Considera solo quelli non ancora completati
ORDER BY differenza_budget ASC
LIMIT 3;

-- Statistica 3: Classifica Utenti per Finanziamenti Totali (Top 3)
CREATE OR REPLACE VIEW ClassificaUtentiFinanziatori AS
SELECT
    u.nickname,
    COALESCE(SUM(f.importo), 0) AS totale_finanziato_utente
FROM Utente u
LEFT JOIN Finanziamento f ON u.email = f.utente_email
GROUP BY u.nickname
ORDER BY totale_finanziato_utente DESC
LIMIT 3;

-- Triggers --

-- Trigger per aggiornare #nr_progetti del Creatore
DELIMITER //
CREATE TRIGGER IncrementaNrProgetti
AFTER INSERT ON Progetto
FOR EACH ROW
BEGIN
    UPDATE Creatore
    SET nr_progetti = nr_progetti + 1
    WHERE utente_email = NEW.creatore_email;
END;//
DELIMITER ;

-- Trigger per aggiornare l'affidabilità del Creatore (quando crea un progetto)
DELIMITER //
CREATE TRIGGER AggiornaAffidabilitaCreaProgetto
AFTER INSERT ON Progetto
FOR EACH ROW
BEGIN
    DECLARE num_progetti_finanziati INT;
    DECLARE num_progetti_totali INT;

    -- Conta i progetti del creatore che hanno ricevuto almeno un finanziamento
    SELECT COUNT(DISTINCT p.nome)
    INTO num_progetti_finanziati
    FROM Progetto p
    JOIN Finanziamento f ON p.nome = f.progetto_nome
    WHERE p.creatore_email = NEW.creatore_email;

    -- Conta il numero totale di progetti del creatore (appena aggiornato dal trigger IncrementaNrProgetti)
    SELECT nr_progetti
    INTO num_progetti_totali
    FROM Creatore
    WHERE utente_email = NEW.creatore_email;

    -- Aggiorna l'affidabilità (evita divisione per zero)
    IF num_progetti_totali > 0 THEN
        UPDATE Creatore
        SET affidabilita = (num_progetti_finanziati / num_progetti_totali) * 100
        WHERE utente_email = NEW.creatore_email;
    ELSE
        UPDATE Creatore
        SET affidabilita = 0
        WHERE utente_email = NEW.creatore_email;
    END IF;
END;//
DELIMITER ;

-- Trigger per aggiornare l'affidabilità del Creatore (quando un progetto riceve il primo finanziamento)
-- Nota: Questo trigger è più complesso perché deve agire solo sul *primo* finanziamento.
-- Un approccio potrebbe essere controllare se il progetto aveva 0 finanziamenti prima dell'INSERT.
DELIMITER //
CREATE TRIGGER AggiornaAffidabilitaPrimoFinanziamento
AFTER INSERT ON Finanziamento
FOR EACH ROW
BEGIN
    DECLARE num_finanziamenti_prima INT;
    DECLARE creatore_email_proj VARCHAR(255);
    DECLARE num_progetti_finanziati INT;
    DECLARE num_progetti_totali INT;

    -- Trova il creatore del progetto finanziato
    SELECT creatore_email INTO creatore_email_proj FROM Progetto WHERE nome = NEW.progetto_nome;

    -- Conta quanti finanziamenti aveva il progetto *prima* di questo inserimento
    SELECT COUNT(*)
    INTO num_finanziamenti_prima
    FROM Finanziamento
    WHERE progetto_nome = NEW.progetto_nome AND id != NEW.id; -- Esclude il finanziamento appena inserito

    -- Se era il primo finanziamento per questo progetto, ricalcola l'affidabilità
    IF num_finanziamenti_prima = 0 THEN
        -- Conta i progetti del creatore che hanno ricevuto almeno un finanziamento (incluso questo)
        SELECT COUNT(DISTINCT p.nome)
        INTO num_progetti_finanziati
        FROM Progetto p
        JOIN Finanziamento f ON p.nome = f.progetto_nome
        WHERE p.creatore_email = creatore_email_proj;

        -- Conta il numero totale di progetti del creatore
        SELECT nr_progetti
        INTO num_progetti_totali
        FROM Creatore
        WHERE utente_email = creatore_email_proj;

        -- Aggiorna l'affidabilità (evita divisione per zero)
        IF num_progetti_totali > 0 THEN
            UPDATE Creatore
            SET affidabilita = (num_progetti_finanziati / num_progetti_totali) * 100
            WHERE utente_email = creatore_email_proj;
        ELSE
            UPDATE Creatore
            SET affidabilita = 0 -- Caso improbabile ma sicuro
            WHERE utente_email = creatore_email_proj;
        END IF;
    END IF;
END;//
DELIMITER ;

-- Trigger per chiudere un progetto quando raggiunge il budget
DELIMITER //
CREATE TRIGGER ChiudiProgettoBudgetRaggiunto
AFTER INSERT ON Finanziamento
FOR EACH ROW
BEGIN
    DECLARE totale_raccolto DECIMAL(10, 2);
    DECLARE budget_progetto DECIMAL(10, 2);

    -- Calcola il totale raccolto per il progetto
    SELECT SUM(importo) INTO totale_raccolto
    FROM Finanziamento
    WHERE progetto_nome = NEW.progetto_nome;

    -- Ottieni il budget del progetto
    SELECT budget INTO budget_progetto
    FROM Progetto
    WHERE nome = NEW.progetto_nome;

    -- Se il totale raccolto è maggiore o uguale al budget, chiudi il progetto
    IF totale_raccolto >= budget_progetto THEN
        UPDATE Progetto
        SET stato = 'chiuso'
        WHERE nome = NEW.progetto_nome;
    END IF;
END;//
DELIMITER ;

-- Evento per chiudere i progetti scaduti --

-- Assicurati che l'event scheduler sia abilitato:
-- SET GLOBAL event_scheduler = ON;

DELIMITER //
CREATE EVENT IF NOT EXISTS ChiudiProgettiScaduti
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 1 MINUTE -- Inizia tra 1 minuto per test, poi magari imposta un orario specifico (es. STARTS 'YYYY-MM-DD 01:00:00')
DO
BEGIN
    UPDATE Progetto
    SET stato = 'chiuso'
    WHERE stato = 'aperto' AND data_limite < CURDATE();
END;//
DELIMITER ;

-- Stored Procedures (Esempi, da implementare nel backend o come SP reali) --

-- Esempio: Procedura per inserire una candidatura verificando le skill
-- Questa logica è complessa per una SP pura e spesso gestita a livello applicativo.
-- Qui un placeholder concettuale:
/*
DELIMITER //
CREATE PROCEDURE InserisciCandidaturaConVerifica(
    IN p_utente_email VARCHAR(255),
    IN p_profilo_id INT
)
BEGIN
    DECLARE skill_match BOOLEAN DEFAULT TRUE;
    DECLARE req_competenza VARCHAR(100);
    DECLARE req_livello INT;
    DECLARE user_livello INT;
    DECLARE done INT DEFAULT FALSE;

    -- Cursor per iterare sulle skill richieste dal profilo
    DECLARE cur CURSOR FOR
        SELECT competenza_nome, livello_richiesto
        FROM Skill_Profilo
        WHERE profilo_id = p_profilo_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO req_competenza, req_livello;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Trova il livello dell'utente per quella competenza
        SELECT livello INTO user_livello
        FROM Utente_Skill
        WHERE utente_email = p_utente_email AND competenza_nome = req_competenza;

        -- Se l'utente non ha la skill o il livello è insufficiente, imposta match a FALSE
        IF user_livello IS NULL OR user_livello < req_livello THEN
            SET skill_match = FALSE;
            LEAVE read_loop;
        END IF;
    END LOOP;
    CLOSE cur;

    -- Se tutte le skill corrispondono, inserisci la candidatura
    IF skill_match THEN
        INSERT INTO Candidatura (utente_email, profilo_id, stato)
        VALUES (p_utente_email, p_profilo_id, 'inviata');
    ELSE
        -- Segnala errore o non fare nulla (dipende dalla gestione errori desiderata)
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le skill dell''utente non soddisfano i requisiti del profilo.';
    END IF;
END //
DELIMITER ;
*/

-- Inserimento dati di esempio (opzionale, per test)
/*
INSERT INTO Utente (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita) VALUES
('mario.rossi@email.com', 'MarioR', 'hashed_pw1', 'Mario', 'Rossi', 1990, 'Roma'),
('anna.verdi@email.com', 'AnnaV', 'hashed_pw2', 'Anna', 'Verdi', 1985, 'Milano'),
('luca.bianchi@email.com', 'LucaB', 'hashed_pw3', 'Luca', 'Bianchi', 1995, 'Napoli'),
('admin@bostarter.com', 'AdminBoss', 'hashed_pw_admin', 'Admin', 'User', 1980, 'System'),
('creator1@bostarter.com', 'CreatorUno', 'hashed_pw_creator1', 'Primo', 'Creatore', 1988, 'Firenze');

INSERT INTO Amministratore (utente_email, codice_sicurezza) VALUES
('admin@bostarter.com', 'secure_code_123');

INSERT INTO Creatore (utente_email) VALUES
('creator1@bostarter.com'),
('mario.rossi@email.com'); -- Mario è anche creatore

INSERT INTO Competenza (nome) VALUES
('PHP'), ('JavaScript'), ('CSS'), ('HTML'), ('MySQL'), ('MongoDB'), ('Python'), ('AI'), ('Hardware Design'), ('Project Management');

INSERT INTO Utente_Skill (utente_email, competenza_nome, livello) VALUES
('mario.rossi@email.com', 'PHP', 4),
('mario.rossi@email.com', 'MySQL', 3),
('anna.verdi@email.com', 'JavaScript', 5),
('anna.verdi@email.com', 'CSS', 4),
('anna.verdi@email.com', 'HTML', 5),
('luca.bianchi@email.com', 'Python', 3),
('luca.bianchi@email.com', 'AI', 2),
('creator1@bostarter.com', 'Hardware Design', 4),
('creator1@bostarter.com', 'Project Management', 5);

-- Aggiungere altri dati per Progetti, Finanziamenti, etc. per testare le viste e i trigger
*/