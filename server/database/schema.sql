-- Schema del database BOSTARTER

CREATE DATABASE IF NOT EXISTS bostarter;
USE bostarter;

-- Tabella Utente
CREATE TABLE Utente (
    email VARCHAR(255) PRIMARY KEY,
    nickname VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita INT NOT NULL,
    luogo_nascita VARCHAR(100) NOT NULL,
    tipo ENUM('normale', 'creatore', 'amministratore') DEFAULT 'normale',
    codice_sicurezza VARCHAR(50),
    nr_progetti INT DEFAULT 0,
    affidabilita DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella Competenza
CREATE TABLE Competenza (
    nome VARCHAR(100) PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella SkillCurriculum
CREATE TABLE SkillCurriculum (
    utente_email VARCHAR(255),
    competenza_nome VARCHAR(100),
    livello INT CHECK (livello BETWEEN 0 AND 5),
    PRIMARY KEY (utente_email, competenza_nome),
    FOREIGN KEY (utente_email) REFERENCES Utente(email) ON DELETE CASCADE,
    FOREIGN KEY (competenza_nome) REFERENCES Competenza(nome) ON DELETE CASCADE
);

-- Tabella Progetto
CREATE TABLE Progetto (
    nome VARCHAR(255) PRIMARY KEY,
    descrizione TEXT NOT NULL,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    budget DECIMAL(10,2) NOT NULL,
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    tipo ENUM('hardware', 'software') NOT NULL,
    email_creatore VARCHAR(255),
    FOREIGN KEY (email_creatore) REFERENCES Utente(email)
);

-- Tabella FotoProgetto
CREATE TABLE FotoProgetto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_progetto VARCHAR(255),
    url_foto VARCHAR(255) NOT NULL,
    FOREIGN KEY (nome_progetto) REFERENCES Progetto(nome) ON DELETE CASCADE
);

-- Tabella Reward
CREATE TABLE Reward (
    codice VARCHAR(50) PRIMARY KEY,
    descrizione TEXT NOT NULL,
    url_foto VARCHAR(255),
    nome_progetto VARCHAR(255),
    FOREIGN KEY (nome_progetto) REFERENCES Progetto(nome) ON DELETE CASCADE
);

-- Tabella Componente (per progetti hardware)
CREATE TABLE Componente (
    nome VARCHAR(100),
    progetto_nome VARCHAR(255),
    descrizione TEXT NOT NULL,
    prezzo DECIMAL(10,2) NOT NULL,
    quantita INT CHECK (quantita > 0),
    PRIMARY KEY (nome, progetto_nome),
    FOREIGN KEY (progetto_nome) REFERENCES Progetto(nome) ON DELETE CASCADE
);

-- Tabella ProfiloRichiesto (per progetti software)
CREATE TABLE ProfiloRichiesto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    progetto_nome VARCHAR(255),
    FOREIGN KEY (progetto_nome) REFERENCES Progetto(nome) ON DELETE CASCADE
);

-- Tabella SkillProfilo
CREATE TABLE SkillProfilo (
    profilo_id INT,
    competenza_nome VARCHAR(100),
    livello INT CHECK (livello BETWEEN 0 AND 5),
    PRIMARY KEY (profilo_id, competenza_nome),
    FOREIGN KEY (profilo_id) REFERENCES ProfiloRichiesto(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_nome) REFERENCES Competenza(nome) ON DELETE CASCADE
);

-- Tabella Finanziamento
CREATE TABLE Finanziamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    importo DECIMAL(10,2) NOT NULL,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utente_email VARCHAR(255),
    nome_progetto VARCHAR(255),
    codice_reward VARCHAR(50),
    FOREIGN KEY (utente_email) REFERENCES Utente(email),
    FOREIGN KEY (nome_progetto) REFERENCES Progetto(nome),
    FOREIGN KEY (codice_reward) REFERENCES Reward(codice)
);

-- Tabella Commento
CREATE TABLE Commento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    testo TEXT NOT NULL,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utente_email VARCHAR(255),
    nome_progetto VARCHAR(255),
    risposta TEXT,
    FOREIGN KEY (utente_email) REFERENCES Utente(email),
    FOREIGN KEY (nome_progetto) REFERENCES Progetto(nome)
);

-- Tabella Candidatura
CREATE TABLE Candidatura (
    utente_email VARCHAR(255),
    profilo_id INT,
    stato ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (utente_email, profilo_id),
    FOREIGN KEY (utente_email) REFERENCES Utente(email),
    FOREIGN KEY (profilo_id) REFERENCES ProfiloRichiesto(id)
);

-- Stored Procedures

-- Inserimento nuovo utente
DELIMITER //
CREATE PROCEDURE InsertUser(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100),
    IN p_anno_nascita INT,
    IN p_luogo_nascita VARCHAR(100),
    IN p_tipo ENUM('normale', 'creatore', 'amministratore'),
    IN p_codice_sicurezza VARCHAR(50)
)
BEGIN
    INSERT INTO Utente (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita, tipo, codice_sicurezza)
    VALUES (p_email, p_nickname, p_password, p_nome, p_cognome, p_anno_nascita, p_luogo_nascita, p_tipo, p_codice_sicurezza);
END //
DELIMITER ;

-- Inserimento nuovo progetto
DELIMITER //
CREATE PROCEDURE InsertProject(
    IN p_nome VARCHAR(255),
    IN p_descrizione TEXT,
    IN p_budget DECIMAL(10,2),
    IN p_data_limite DATE,
    IN p_email_creatore VARCHAR(255),
    IN p_tipo ENUM('hardware', 'software')
)
BEGIN
    INSERT INTO Progetto (nome, descrizione, budget, data_limite, email_creatore, tipo)
    VALUES (p_nome, p_descrizione, p_budget, p_data_limite, p_email_creatore, p_tipo);
END //
DELIMITER ;

-- Trigger per aggiornare l'affidabilità
DELIMITER //
CREATE TRIGGER UpdateAffidabilita AFTER INSERT ON Finanziamento
FOR EACH ROW
BEGIN
    DECLARE total_projects INT;
    DECLARE funded_projects INT;
    
    -- Ottieni il creatore del progetto
    SELECT email_creatore INTO @creator_email
    FROM Progetto
    WHERE nome = NEW.nome_progetto;
    
    -- Calcola il totale dei progetti
    SELECT COUNT(*) INTO total_projects
    FROM Progetto
    WHERE email_creatore = @creator_email;
    
    -- Calcola i progetti finanziati
    SELECT COUNT(DISTINCT p.nome) INTO funded_projects
    FROM Progetto p
    JOIN Finanziamento f ON p.nome = f.nome_progetto
    WHERE p.email_creatore = @creator_email;
    
    -- Aggiorna l'affidabilità
    UPDATE Utente
    SET affidabilita = (funded_projects / total_projects) * 100
    WHERE email = @creator_email;
END //
DELIMITER ;

-- Trigger per incrementare nr_progetti
DELIMITER //
CREATE TRIGGER IncrementProjects AFTER INSERT ON Progetto
FOR EACH ROW
BEGIN
    UPDATE Utente
    SET nr_progetti = nr_progetti + 1
    WHERE email = NEW.email_creatore;
END //
DELIMITER ;

-- Trigger per chiudere il progetto quando raggiunge il budget
DELIMITER //
CREATE TRIGGER CloseProjectOnBudget AFTER INSERT ON Finanziamento
FOR EACH ROW
BEGIN
    DECLARE total_amount DECIMAL(10,2);
    DECLARE project_budget DECIMAL(10,2);
    
    -- Calcola il totale dei finanziamenti
    SELECT SUM(importo), p.budget INTO total_amount, project_budget
    FROM Finanziamento f
    JOIN Progetto p ON f.nome_progetto = p.nome
    WHERE p.nome = NEW.nome_progetto
    GROUP BY p.nome, p.budget;
    
    -- Chiudi il progetto se ha raggiunto il budget
    IF total_amount >= project_budget THEN
        UPDATE Progetto
        SET stato = 'chiuso'
        WHERE nome = NEW.nome_progetto;
    END IF;
END //
DELIMITER ;

-- Event per chiudere progetti scaduti
DELIMITER //
CREATE EVENT CloseExpiredProjects
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    UPDATE Progetto
    SET stato = 'chiuso'
    WHERE data_limite < CURDATE() AND stato = 'aperto';
END //
DELIMITER ;

-- Viste

-- Classifica utenti creatori per affidabilità
CREATE VIEW TopCreatori AS
SELECT nickname, affidabilita
FROM Utente
WHERE tipo = 'creatore'
ORDER BY affidabilita DESC
LIMIT 3;

-- Progetti aperti più vicini al completamento
CREATE VIEW ProgettiQuasiCompletati AS
SELECT p.nome, p.budget, COALESCE(SUM(f.importo), 0) as totale_raccolto,
       (p.budget - COALESCE(SUM(f.importo), 0)) as differenza
FROM Progetto p
LEFT JOIN Finanziamento f ON p.nome = f.nome_progetto
WHERE p.stato = 'aperto'
GROUP BY p.nome, p.budget
ORDER BY differenza ASC
LIMIT 3;

-- Classifica utenti per totale finanziamenti erogati
CREATE VIEW TopFinanziatori AS
SELECT u.nickname, COALESCE(SUM(f.importo), 0) as totale_finanziato
FROM Utente u
LEFT JOIN Finanziamento f ON u.email = f.utente_email
GROUP BY u.email, u.nickname
ORDER BY totale_finanziato DESC
LIMIT 3;