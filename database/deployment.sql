
-- BOSTARTER - DEPLOYMENT COMPLETO DATABASE

-- Database: bostarter_italiano
-- Versione: 2.0
-- Data creazione: 2025

-- File combinato per deployment completo
-- Include: Schema + Stored Procedures + Trigger + Viste


-- Creazione database
DROP DATABASE IF EXISTS bostarter_italiano;
CREATE DATABASE bostarter_italiano
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bostarter_italiano;


-- SCHEMA DATABASE


-- Utenti registrati
CREATE TABLE utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    nickname VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita YEAR,
    luogo_nascita VARCHAR(100),
    tipo_utente ENUM('utente', 'creatore', 'amministratore') NOT NULL DEFAULT 'utente',
    codice_sicurezza VARCHAR(10) UNIQUE,
    nr_progetti INT DEFAULT 0,
    affidabilita DECIMAL(5,2) DEFAULT 0.00,
    data_registrazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_accesso DATETIME,
    stato ENUM('attivo', 'sospeso', 'bloccato') DEFAULT 'attivo',
    INDEX idx_email (email),
    INDEX idx_nickname (nickname),
    INDEX idx_tipo_utente (tipo_utente),
    INDEX idx_stato (stato)
);

-- Catalogo competenze
CREATE TABLE competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT,
    categoria VARCHAR(50),
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    creato_da INT,
    FOREIGN KEY (creato_da) REFERENCES utenti(id) ON DELETE SET NULL,
    INDEX idx_nome (nome),
    INDEX idx_categoria (categoria)
);

-- Progetti pubblicati
CREATE TABLE progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    tipo_progetto ENUM('hardware', 'software') NOT NULL,
    budget_richiesto DECIMAL(10,2) NOT NULL,
    data_inserimento DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso', 'scaduto') DEFAULT 'aperto',
    creatore_id INT NOT NULL,
    immagine VARCHAR(255),
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_creatore (creatore_id),
    INDEX idx_stato (stato),
    INDEX idx_tipo (tipo_progetto),
    INDEX idx_data_limite (data_limite),
    INDEX idx_categoria (categoria)
);

-- Ricompense progetto
CREATE TABLE rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT,
    immagine VARCHAR(255),
    prezzo_minimo DECIMAL(10,2) DEFAULT 0,
    quantita_disponibile INT,
    quantita_rimanente INT,
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);

-- Transazioni finanziarie
CREATE TABLE finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_finanziamento DATETIME DEFAULT CURRENT_TIMESTAMP,
    stato_pagamento ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    reward_id INT,
    metodo_pagamento VARCHAR(50),
    note TEXT,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE SET NULL,
    INDEX idx_progetto (progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_stato (stato_pagamento),
    INDEX idx_data (data_finanziamento)
);

-- Sistema commenti
CREATE TABLE commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_commento DATETIME DEFAULT CURRENT_TIMESTAMP,
    num_likes INT DEFAULT 0,
    num_dislikes INT DEFAULT 0,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_data (data_commento)
);

-- Profili lavoro software
CREATE TABLE profili_software (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descrizione TEXT,
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);

-- Candidature lavoro
CREATE TABLE candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    profilo_id INT NOT NULL,
    messaggio TEXT,
    stato ENUM('in_valutazione', 'accettata', 'rifiutata') DEFAULT 'in_valutazione',
    data_candidatura DATETIME DEFAULT CURRENT_TIMESTAMP,
    valutata_da INT,
    data_valutazione DATETIME,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    FOREIGN KEY (valutata_da) REFERENCES utenti(id) ON DELETE SET NULL,
    INDEX idx_utente (utente_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_stato (stato)
);

-- Competenze utente
CREATE TABLE skill_curriculum (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello TINYINT UNSIGNED NOT NULL CHECK (livello BETWEEN 0 AND 5),
    data_aggiornamento DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_skill (utente_id, competenza_id),
    INDEX idx_utente (utente_id),
    INDEX idx_competenza (competenza_id)
);

-- Requisiti competenze progetto
CREATE TABLE skill_profilo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profilo_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello_richiesto TINYINT UNSIGNED NOT NULL CHECK (livello_richiesto BETWEEN 0 AND 5),
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    INDEX idx_profilo (profilo_id),
    INDEX idx_competenza (competenza_id)
);

-- Componenti hardware progetto
CREATE TABLE componenti_hardware (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descrizione TEXT,
    prezzo DECIMAL(10,2) NOT NULL,
    quantita INT NOT NULL DEFAULT 1,
    fornitore VARCHAR(100),
    link_acquisto VARCHAR(500),
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);

-- Like/dislike commenti
CREATE TABLE like_commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commento_id INT NOT NULL,
    utente_id INT NOT NULL,
    tipo ENUM('like','dislike') NOT NULL,
    data_like DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commento_id) REFERENCES commenti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (commento_id, utente_id),
    INDEX idx_commento (commento_id),
    INDEX idx_utente (utente_id)
);

-- Risposte commenti
CREATE TABLE risposte_commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commento_id INT NOT NULL UNIQUE,
    testo TEXT NOT NULL,
    data_risposta DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commento_id) REFERENCES commenti(id) ON DELETE CASCADE,
    INDEX idx_commento (commento_id)
);

-- Sistema notifiche
CREATE TABLE notifiche (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    messaggio TEXT NOT NULL,
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    letta BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_utente (utente_id),
    INDEX idx_tipo (tipo),
    INDEX idx_letta (letta)
);

-- Log eventi sistema
CREATE TABLE log_eventi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo_evento VARCHAR(50) NOT NULL,
    descrizione TEXT NOT NULL,
    data_evento DATETIME DEFAULT CURRENT_TIMESTAMP,
    utente_id INT,
    progetto_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE SET NULL,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE SET NULL,
    INDEX idx_tipo_evento (tipo_evento),
    INDEX idx_data (data_evento),
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id)
);

-- Sessioni utente
CREATE TABLE sessioni_utente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_attivita DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_utente (utente_id)
);

-- VISTE STATISTICHE

-- Top creators by reliability ranking
CREATE VIEW top_creatori_affidabilita AS
SELECT
    u.id,
    u.nickname,
    u.nome,
    u.cognome,
    u.affidabilita,
    u.nr_progetti as progetti_creati,
    COUNT(DISTINCT CASE WHEN p.stato = 'chiuso' THEN p.id END) as progetti_completati
FROM utenti u
LEFT JOIN progetti p ON u.id = p.creatore_id
WHERE u.tipo_utente = 'creatore' AND u.stato = 'attivo'
GROUP BY u.id, u.nickname, u.nome, u.cognome, u.affidabilita, u.nr_progetti
HAVING u.affidabilita > 0
ORDER BY u.affidabilita DESC, progetti_completati DESC
LIMIT 3;

-- Vista: Top 3 progetti vicini al completamento
CREATE VIEW top_progetti_vicini_completamento AS
SELECT
    p.id,
    p.titolo,
    p.descrizione,
    p.budget_richiesto,
    COALESCE(SUM(f.importo), 0) as budget_raccolto,
    ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) as percentuale_completamento,
    DATEDIFF(p.data_limite, CURDATE()) as giorni_rimanenti,
    p.data_limite,
    u.nickname as creatore
FROM progetti p
LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completed'
LEFT JOIN utenti u ON p.creatore_id = u.id
WHERE p.stato = 'aperto' AND p.data_limite > CURDATE()
GROUP BY p.id, p.titolo, p.descrizione, p.budget_richiesto, p.data_limite, u.nickname
HAVING budget_raccolto > 0 AND percentuale_completamento >= 50
ORDER BY percentuale_completamento DESC
LIMIT 3;

-- Vista: Top 3 finanziatori per importo totale
CREATE VIEW top_finanziatori_importo AS
SELECT
    u.id,
    u.nickname,
    u.nome,
    u.cognome,
    COUNT(f.id) as numero_finanziamenti,
    SUM(f.importo) as totale_finanziato,
    AVG(f.importo) as importo_medio,
    MAX(f.data_finanziamento) as ultimo_finanziamento
FROM utenti u
JOIN finanziamenti f ON u.id = f.utente_id
WHERE f.stato_pagamento = 'completed'
GROUP BY u.id, u.nickname, u.nome, u.cognome
ORDER BY totale_finanziato DESC
LIMIT 3;

-- Vista: Statistiche generali
CREATE VIEW statistiche_generali AS
SELECT
    (SELECT COUNT(*) FROM utenti WHERE stato = 'attivo') as totale_utenti,
    (SELECT COUNT(*) FROM utenti WHERE tipo_utente = 'creatore' AND stato = 'attivo') as totale_creatori,
    (SELECT COUNT(*) FROM progetti) as totale_progetti,
    (SELECT COUNT(*) FROM progetti WHERE stato = 'aperto') as progetti_aperti,
    (SELECT COUNT(*) FROM progetti WHERE stato = 'chiuso') as progetti_chiusi,
    (SELECT COUNT(*) FROM commenti) as totale_commenti,
    (SELECT COUNT(*) FROM candidature WHERE stato = 'accettata') as candidature_accettate,
    (SELECT COALESCE(SUM(importo), 0) FROM finanziamenti WHERE stato_pagamento = 'completed') as totale_finanziato,
    (SELECT AVG(affidabilita) FROM utenti WHERE tipo_utente = 'creatore' AND affidabilita > 0) as affidabilita_media
FROM dual;

-- STORED PROCEDURES


DELIMITER //

-- Procedura: Registrazione utente sicura
CREATE PROCEDURE registra_utente(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(100),
    IN p_password VARCHAR(255),
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100),
    IN p_anno_nascita YEAR,
    IN p_luogo_nascita VARCHAR(100),
    IN p_tipo ENUM('utente','creatore','amministratore')
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica unicità email
    IF EXISTS (SELECT 1 FROM utenti WHERE email = p_email) THEN
        SELECT FALSE AS success, 'Email già registrata' AS error, NULL AS user_id;
        ROLLBACK;
    END IF;

    -- Verifica unicità nickname
    IF EXISTS (SELECT 1 FROM utenti WHERE nickname = p_nickname) THEN
        SELECT FALSE AS success, 'Nickname già in uso' AS error, NULL AS user_id;
        ROLLBACK;
    END IF;

    -- Validazione tipo utente
    IF p_tipo NOT IN ('utente','creatore','amministratore') THEN
        SELECT FALSE AS success, 'Tipo utente non valido' AS error, NULL AS user_id;
        ROLLBACK;
    END IF;

    -- Inserisci utente con password hash sicura
    INSERT INTO utenti (
        email, nickname, password_hash, nome, cognome,
        anno_nascita, luogo_nascita, tipo_utente
    ) VALUES (
        p_email, p_nickname, PASSWORD(p_password), p_nome, p_cognome,
        p_anno_nascita, p_luogo_nascita, p_tipo
    );

    -- Restituisci successo con ID utente
    SELECT TRUE AS success, NULL AS error, LAST_INSERT_ID() AS user_id;

    COMMIT;
END //

-- Procedura: Autenticazione utente con supporto multi-ruolo
CREATE PROCEDURE autentica_utente(
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255)
)
BEGIN
    DECLARE user_count INT DEFAULT 0;
    DECLARE stored_password VARCHAR(255);
    DECLARE user_id INT;
    DECLARE user_tipo VARCHAR(20);
    DECLARE user_codice_sicurezza VARCHAR(10);
    DECLARE user_stato VARCHAR(20);

    -- Verifica se l'utente esiste e recupera i dati
    SELECT COUNT(*), password_hash, id, tipo_utente, codice_sicurezza, stato
    INTO user_count, stored_password, user_id, user_tipo, user_codice_sicurezza, user_stato
    FROM utenti
    WHERE email = p_email;

    -- Se l'utente non esiste o la password non corrisponde
    IF user_count = 0 OR stored_password != PASSWORD(p_password) THEN
        SELECT FALSE AS success, 'Credenziali non valide' AS message, NULL AS user_id, NULL AS tipo, NULL AS codice_sicurezza;
    ELSEIF user_stato != 'attivo' THEN
        SELECT FALSE AS success, 'Account non attivo' AS message, NULL AS user_id, NULL AS tipo, NULL AS codice_sicurezza;
    ELSE
        -- Aggiorna ultimo accesso
        UPDATE utenti SET ultimo_accesso = NOW() WHERE id = user_id;

        -- Restituisci dati utente (incluso codice sicurezza per verifica admin)
        SELECT TRUE AS success, 'Autenticazione riuscita' AS message, user_id, user_tipo AS tipo, user_codice_sicurezza AS codice_sicurezza;
    END IF;
END //

-- Procedura: Crea progetto
CREATE PROCEDURE crea_progetto(
    IN p_creatore_id INT,
    IN p_titolo VARCHAR(200),
    IN p_descrizione TEXT,
    IN p_categoria VARCHAR(50),
    IN p_tipo_progetto ENUM('hardware','software'),
    IN p_budget DECIMAL(10,2),
    IN p_data_limite DATE,
    IN p_immagine VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che il creatore esista e sia attivo
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_creatore_id AND stato = 'attivo') THEN
        SELECT FALSE AS success, 'Creatore non valido' AS error, NULL AS project_id;
        ROLLBACK;
    END IF;

    -- Verifica che il creatore abbia il ruolo appropriato
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_creatore_id AND tipo_utente IN ('creatore','amministratore')) THEN
        SELECT FALSE AS success, 'Solo creatori possono creare progetti' AS error, NULL AS project_id;
        ROLLBACK;
    END IF;

    -- Validazione budget minimo
    IF p_budget < 100 THEN
        SELECT FALSE AS success, 'Budget minimo €100' AS error, NULL AS project_id;
        ROLLBACK;
    END IF;

    -- Validazione data limite
    IF p_data_limite <= CURDATE() THEN
        SELECT FALSE AS success, 'Data limite deve essere futura' AS error, NULL AS project_id;
        ROLLBACK;
    END IF;

    -- Inserisci progetto
    INSERT INTO progetti (
        creatore_id, titolo, descrizione, categoria, tipo_progetto,
        budget_richiesto, data_limite, immagine
    ) VALUES (
        p_creatore_id, p_titolo, p_descrizione, p_categoria, p_tipo_progetto,
        p_budget, p_data_limite, p_immagine
    );

    -- Restituisci successo con ID progetto
    SELECT TRUE AS success, 'Progetto creato con successo' AS message, LAST_INSERT_ID() AS project_id;

    COMMIT;
END //

-- Procedura: Effettua finanziamento
CREATE PROCEDURE effettua_finanziamento(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_importo DECIMAL(10,2),
    IN p_reward_id INT,
    IN p_metodo_pagamento VARCHAR(50),
    IN p_note TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che l'utente esista e sia attivo
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_utente_id AND stato = 'attivo') THEN
        SELECT FALSE AS success, 'Utente non valido' AS error, NULL AS financing_id;
        ROLLBACK;
    END IF;

    -- Verifica che il progetto esista e sia aperto
    IF NOT EXISTS (SELECT 1 FROM progetti WHERE id = p_progetto_id AND stato = 'aperto') THEN
        SELECT FALSE AS success, 'Progetto non disponibile' AS error, NULL AS financing_id;
        ROLLBACK;
    END IF;

    -- Validazione importo minimo
    IF p_importo < 1 THEN
        SELECT FALSE AS success, 'Importo minimo €1' AS error, NULL AS financing_id;
        ROLLBACK;
    END IF;

    -- Inserisci finanziamento
    INSERT INTO finanziamenti (
        utente_id, progetto_id, importo, reward_id, metodo_pagamento, note
    ) VALUES (
        p_utente_id, p_progetto_id, p_importo, p_reward_id, p_metodo_pagamento, p_note
    );

    -- Restituisci successo
    SELECT TRUE AS success, 'Finanziamento effettuato con successo' AS message, LAST_INSERT_ID() AS financing_id;

    COMMIT;
END //

-- Procedura: Inserisci commento
CREATE PROCEDURE inserisci_commento(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_testo TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che l'utente esista e sia attivo
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_utente_id AND stato = 'attivo') THEN
        SELECT FALSE AS success, 'Utente non valido' AS error, NULL AS comment_id;
        ROLLBACK;
    END IF;

    -- Verifica che il progetto esista
    IF NOT EXISTS (SELECT 1 FROM progetti WHERE id = p_progetto_id) THEN
        SELECT FALSE AS success, 'Progetto non trovato' AS error, NULL AS comment_id;
        ROLLBACK;
    END IF;

    -- Validazione testo commento
    IF LENGTH(TRIM(p_testo)) < 5 THEN
        SELECT FALSE AS success, 'Commento troppo breve' AS error, NULL AS comment_id;
        ROLLBACK;
    END IF;

    -- Inserisci commento
    INSERT INTO commenti (utente_id, progetto_id, testo)
    VALUES (p_utente_id, p_progetto_id, p_testo);

    -- Restituisci successo
    SELECT TRUE AS success, 'Commento aggiunto con successo' AS message, LAST_INSERT_ID() AS comment_id;

    COMMIT;
END //

DELIMITER ;

-- TRIGGER DI AUTOMAZIONE


DELIMITER //

-- Trigger: Log registrazione utente
CREATE TRIGGER trg_log_registrazione_utente
AFTER INSERT ON utenti
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id
    ) VALUES (
        'UTENTE_REGISTRATO',
        CONCAT('Nuovo utente registrato: ', NEW.nickname, ' (', NEW.email, ')'),
        NEW.id
    );
END //

-- Trigger: Incrementa contatore progetti creatore
CREATE TRIGGER trg_incrementa_conteggio_progetti
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti
    SET nr_progetti = nr_progetti + 1
    WHERE id = NEW.creatore_id;
END //

-- Trigger: Log creazione progetto
CREATE TRIGGER trg_log_creazione_progetto
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id, progetto_id
    ) VALUES (
        'PROGETTO_CREATO',
        CONCAT('Nuovo progetto creato: ', NEW.titolo, ' (', NEW.tipo_progetto, ')'),
        NEW.creatore_id,
        NEW.id
    );
END //

-- Trigger: Chiudi progetto automaticamente per data scadenza
CREATE TRIGGER trg_chiudi_progetto_scadenza
AFTER UPDATE ON progetti
FOR EACH ROW
BEGIN
    IF OLD.stato = 'aperto' AND NEW.data_limite < CURDATE() THEN
        UPDATE progetti SET stato = 'scaduto' WHERE id = NEW.id;
    END IF;
END //

DELIMITER ;

-- DATI DI ESEMPIO


-- Amministratore di default
INSERT INTO utenti (email, nickname, password_hash, nome, cognome, tipo_utente, codice_sicurezza)
VALUES ('admin@bostarter.it', 'admin', PASSWORD('admin123'), 'Amministratore', 'Sistema', 'amministratore', 'ADMIN001');

-- Competenze di base
INSERT INTO competenze (nome, descrizione, categoria) VALUES
('PHP', 'Linguaggio di programmazione web lato server', 'Programmazione'),
('JavaScript', 'Linguaggio di programmazione web lato client', 'Programmazione'),
('Python', 'Linguaggio di programmazione generale', 'Programmazione'),
('HTML/CSS', 'Linguaggi per il markup e lo styling web', 'Web Development'),
('React', 'Libreria JavaScript per interfacce utente', 'Frontend'),
('Node.js', 'Runtime JavaScript lato server', 'Backend'),
('MySQL', 'Sistema di gestione database relazionale', 'Database'),
('MongoDB', 'Database NoSQL orientato ai documenti', 'Database'),
('Docker', 'Piattaforma di containerizzazione', 'DevOps'),
('Git', 'Sistema di controllo versione distribuito', 'Tools'),
('Arduino', 'Piattaforma di prototipazione elettronica', 'Hardware'),
('Raspberry Pi', 'Computer a scheda singola', 'Hardware'),
('3D Printing', 'Stampa 3D e modellazione', 'Manufacturing'),
('UI/UX Design', 'Design di interfacce utente', 'Design'),
('Marketing Digitale', 'Strategie di marketing online', 'Marketing'),
('Project Management', 'Gestione progetti e team', 'Management');

COMMIT;

