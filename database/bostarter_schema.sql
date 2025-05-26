-- BOSTARTER Database Setup 
-- Creazione database e tabelle per la piattaforma di crowdfunding 

DROP DATABASE IF EXISTS bostarter; 
CREATE DATABASE bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; 
USE bostarter; 

-- Tabella delle competenze globali 
CREATE TABLE competenze ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    nome VARCHAR(100) NOT NULL UNIQUE, 
    descrizione TEXT, 
    categoria VARCHAR(50), 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria)
); 

-- Tabella utenti base 
CREATE TABLE utenti ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    email VARCHAR(255) NOT NULL UNIQUE, 
    nickname VARCHAR(100) NOT NULL UNIQUE, 
    password_hash VARCHAR(255) NOT NULL, 
    nome VARCHAR(100) NOT NULL, 
    cognome VARCHAR(100) NOT NULL, 
    anno_nascita YEAR NOT NULL, 
    luogo_nascita VARCHAR(100) NOT NULL, 
    tipo_utente ENUM('standard', 'creatore', 'amministratore') DEFAULT 'standard', 
    codice_sicurezza VARCHAR(50) NULL, -- Solo per amministratori 
    affidabilita DECIMAL(3,2) DEFAULT 0.00, -- Solo per creatori (0-100) 
    nr_progetti INT DEFAULT 0, -- Ridondanza per creatori 
    avatar VARCHAR(255) DEFAULT 'default-avatar.png', 
    bio TEXT, 
    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    ultimo_accesso TIMESTAMP NULL, 
    stato ENUM('attivo', 'sospeso', 'eliminato') DEFAULT 'attivo',
    INDEX idx_tipo_utente (tipo_utente),
    INDEX idx_stato (stato),
    INDEX idx_data_registrazione (data_registrazione),
    INDEX idx_affidabilita (affidabilita)
); 

-- Tabella skill degli utenti 
CREATE TABLE utenti_skill ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    utente_id INT NOT NULL, 
    competenza_id INT NOT NULL, 
    livello TINYINT NOT NULL CHECK (livello BETWEEN 0 AND 5), 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE, 
    UNIQUE KEY unique_utente_competenza (utente_id, competenza_id),
    INDEX idx_livello (livello)
); 

-- Tabella progetti 
CREATE TABLE progetti ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    nome VARCHAR(200) NOT NULL UNIQUE, 
    descrizione TEXT NOT NULL, 
    creatore_id INT NOT NULL, 
    tipo_progetto ENUM('hardware', 'software') NOT NULL, 
    budget_richiesto DECIMAL(10,2) NOT NULL, 
    budget_raccolto DECIMAL(10,2) DEFAULT 0.00, 
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    data_scadenza TIMESTAMP NOT NULL, 
    stato ENUM('aperto', 'chiuso', 'completato', 'annullato') DEFAULT 'aperto', 
    immagine_principale VARCHAR(255), 
    categoria VARCHAR(50), 
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    INDEX idx_stato (stato), 
    INDEX idx_categoria (categoria), 
    INDEX idx_scadenza (data_scadenza),
    INDEX idx_budget (budget_richiesto, budget_raccolto),
    INDEX idx_data_inserimento (data_inserimento)
); 

-- Tabella immagini progetti 
CREATE TABLE progetti_immagini ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    percorso_file VARCHAR(255) NOT NULL, 
    descrizione VARCHAR(255), 
    ordine_visualizzazione INT DEFAULT 0, 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE 
); 

-- Tabella reward 
CREATE TABLE reward ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    codice VARCHAR(50) NOT NULL, 
    descrizione TEXT NOT NULL, 
    importo_minimo DECIMAL(10,2) NOT NULL, 
    immagine VARCHAR(255), 
    quantita_disponibile INT, 
    quantita_riservata INT DEFAULT 0, 
    data_consegna_stimata DATE, 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    UNIQUE KEY unique_progetto_codice (progetto_id, codice) 
); 

-- Tabella componenti (solo per progetti hardware) 
CREATE TABLE componenti_hardware ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    nome VARCHAR(200) NOT NULL, 
    descrizione TEXT, 
    prezzo DECIMAL(10,2) NOT NULL, 
    quantita INT NOT NULL CHECK (quantita > 0), 
    fornitore VARCHAR(200), 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    UNIQUE KEY unique_progetto_componente (progetto_id, nome) 
); 

-- Tabella profili richiesti (solo per progetti software) 
CREATE TABLE profili_software ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    nome VARCHAR(200) NOT NULL, 
    descrizione TEXT, 
    numero_posizioni INT DEFAULT 1, 
    posizioni_occupate INT DEFAULT 0, 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE 
); 

-- Tabella skill richieste per profili software 
CREATE TABLE profili_skill_richieste ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    profilo_id INT NOT NULL, 
    competenza_id INT NOT NULL, 
    livello_minimo TINYINT NOT NULL CHECK (livello_minimo BETWEEN 0 AND 5), 
    FOREIGN KEY (profilo_id) REFERENCES profili_software(id) ON DELETE CASCADE, 
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE, 
    UNIQUE KEY unique_profilo_competenza (profilo_id, competenza_id) 
); 

-- Tabella finanziamenti 
CREATE TABLE finanziamenti ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    utente_id INT NOT NULL, 
    reward_id INT NOT NULL, 
    importo DECIMAL(10,2) NOT NULL, 
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    stato_pagamento ENUM('pendente', 'completato', 'fallito', 'rimborsato') DEFAULT 'pendente', 
    metodo_pagamento VARCHAR(50), 
    transazione_id VARCHAR(100), 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    FOREIGN KEY (reward_id) REFERENCES reward(id) ON DELETE CASCADE, 
    INDEX idx_progetto (progetto_id), 
    INDEX idx_utente (utente_id), 
    INDEX idx_data (data_finanziamento),
    INDEX idx_stato_pagamento (stato_pagamento),
    INDEX idx_importo (importo),
    INDEX idx_progetto_stato (progetto_id, stato_pagamento)
); 

-- Tabella commenti 
CREATE TABLE commenti ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    utente_id INT NOT NULL, 
    testo TEXT NOT NULL, 
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    risposta_id INT NULL, -- Riferimento al commento padre per le risposte 
    stato ENUM('attivo', 'nascosto', 'eliminato') DEFAULT 'attivo', 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    FOREIGN KEY (risposta_id) REFERENCES commenti(id) ON DELETE SET NULL, 
    INDEX idx_progetto (progetto_id), 
    INDEX idx_utente (utente_id), 
    INDEX idx_risposta (risposta_id),
    INDEX idx_stato (stato),
    INDEX idx_data_commento (data_commento),
    INDEX idx_progetto_stato (progetto_id, stato)
); 

-- Tabella aggiornamenti progetto 
CREATE TABLE aggiornamenti_progetto ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    titolo VARCHAR(200) NOT NULL, 
    contenuto TEXT NOT NULL, 
    data_pubblicazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    visibile BOOLEAN DEFAULT TRUE, 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    INDEX idx_progetto (progetto_id), 
    INDEX idx_data (data_pubblicazione),
    INDEX idx_visibile (visibile),
    INDEX idx_progetto_visibile (progetto_id, visibile)
); 

-- Tabella notifiche 
CREATE TABLE notifiche ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    utente_id INT NOT NULL, 
    tipo VARCHAR(50) NOT NULL, 
    messaggio TEXT NOT NULL, 
    link VARCHAR(255), 
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    letta BOOLEAN DEFAULT FALSE, 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    INDEX idx_utente (utente_id), 
    INDEX idx_data (data_creazione),
    INDEX idx_tipo (tipo),
    INDEX idx_letta (letta),
    INDEX idx_utente_letta (utente_id, letta)
); 

-- Tabella preferiti 
CREATE TABLE preferiti ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    utente_id INT NOT NULL, 
    progetto_id INT NOT NULL, 
    data_aggiunta TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    UNIQUE KEY unique_utente_progetto (utente_id, progetto_id),
    INDEX idx_data_aggiunta (data_aggiunta)
); 

-- Tabella sessioni 
CREATE TABLE sessioni ( 
    id VARCHAR(128) PRIMARY KEY, 
    utente_id INT NOT NULL, 
    ip_address VARCHAR(45) NOT NULL, 
    user_agent TEXT, 
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    ultimo_accesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
    scadenza TIMESTAMP NOT NULL, 
    dati TEXT, 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    INDEX idx_scadenza (scadenza) 
); 

-- Tabella log attività 
CREATE TABLE log_attivita ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    utente_id INT, 
    tipo_attivita VARCHAR(50) NOT NULL, 
    descrizione TEXT, 
    ip_address VARCHAR(45), 
    data_attivita TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE SET NULL, 
    INDEX idx_utente (utente_id), 
    INDEX idx_tipo (tipo_attivita), 
    INDEX idx_data (data_attivita) 
); 

-- Tabella FAQ 
CREATE TABLE faq ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    domanda TEXT NOT NULL, 
    risposta TEXT NOT NULL, 
    ordine INT DEFAULT 0, 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    INDEX idx_progetto (progetto_id) 
); 

-- Tabella tag 
CREATE TABLE tag ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    nome VARCHAR(50) NOT NULL UNIQUE, 
    descrizione VARCHAR(255) 
); 

-- Tabella progetti_tag (relazione molti a molti) 
CREATE TABLE progetti_tag ( 
    progetto_id INT NOT NULL, 
    tag_id INT NOT NULL, 
    PRIMARY KEY (progetto_id, tag_id), 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    FOREIGN KEY (tag_id) REFERENCES tag(id) ON DELETE CASCADE 
); 

-- Tabella messaggi 
CREATE TABLE messaggi ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    mittente_id INT NOT NULL, 
    destinatario_id INT NOT NULL, 
    oggetto VARCHAR(255) NOT NULL, 
    contenuto TEXT NOT NULL, 
    data_invio TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    letto BOOLEAN DEFAULT FALSE, 
    eliminato_mittente BOOLEAN DEFAULT FALSE, 
    eliminato_destinatario BOOLEAN DEFAULT FALSE, 
    FOREIGN KEY (mittente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    FOREIGN KEY (destinatario_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    INDEX idx_mittente (mittente_id), 
    INDEX idx_destinatario (destinatario_id), 
    INDEX idx_data (data_invio) 
); 

-- Tabella impostazioni 
CREATE TABLE impostazioni ( 
    chiave VARCHAR(100) PRIMARY KEY, 
    valore TEXT NOT NULL, 
    descrizione VARCHAR(255), 
    tipo VARCHAR(50) DEFAULT 'string', 
    modificabile BOOLEAN DEFAULT TRUE, 
    data_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
); 

-- Inserimento di alcune impostazioni di base 
INSERT INTO impostazioni (chiave, valore, descrizione, tipo) VALUES 
('site_name', 'BOSTARTER', 'Nome del sito', 'string'), 
('site_description', 'Piattaforma di crowdfunding per progetti creativi', 'Descrizione del sito', 'string'), 
('contact_email', 'info@bostarter.it', 'Email di contatto', 'email'), 
('max_project_duration', '90', 'Durata massima di un progetto in giorni', 'integer'), 
('min_project_goal', '1000', 'Obiettivo minimo di finanziamento in euro', 'decimal'), 
('platform_fee', '5', 'Percentuale trattenuta dalla piattaforma', 'decimal'), 
('enable_registration', 'true', 'Abilita registrazione nuovi utenti', 'boolean'); 

-- Indici aggiuntivi per migliorare le performance 
CREATE INDEX idx_utenti_tipo ON utenti(tipo_utente); 
CREATE INDEX idx_progetti_creatore ON progetti(creatore_id); 
CREATE INDEX idx_progetti_tipo ON progetti(tipo_progetto); 
CREATE INDEX idx_commenti_data ON commenti(data_commento); 
CREATE INDEX idx_finanziamenti_stato ON finanziamenti(stato_pagamento); 

-- Viste per facilitare le query comuni 

-- Vista progetti attivi con statistiche 
CREATE VIEW v_progetti_attivi AS 
SELECT 
    p.id, 
    p.nome, 
    p.descrizione, 
    p.creatore_id, 
    u.nickname AS creatore_nickname, 
    p.tipo_progetto, 
    p.budget_richiesto, 
    p.budget_raccolto, 
    p.data_inserimento, 
    p.data_scadenza, 
    p.stato, 
    p.categoria, 
    p.immagine_principale, 
    DATEDIFF(p.data_scadenza, CURRENT_TIMESTAMP()) AS giorni_rimanenti, 
    (p.budget_raccolto / p.budget_richiesto * 100) AS percentuale_completamento, 
    (SELECT COUNT(*) FROM finanziamenti f WHERE f.progetto_id = p.id) AS numero_sostenitori 
FROM 
    progetti p 
JOIN 
    utenti u ON p.creatore_id = u.id 
WHERE 
    p.stato = 'aperto' AND p.data_scadenza > CURRENT_TIMESTAMP(); 

-- Vista utenti creatori con statistiche 
CREATE VIEW v_creatori AS 
SELECT 
    u.id, 
    u.nickname, 
    u.nome, 
    u.cognome, 
    u.email, 
    u.affidabilita, 
    u.avatar, 
    u.bio, 
    u.data_registrazione, 
    COUNT(DISTINCT p.id) AS progetti_totali, 
    SUM(CASE WHEN p.stato = 'completato' THEN 1 ELSE 0 END) AS progetti_completati, 
    SUM(p.budget_raccolto) AS totale_raccolto 
FROM 
    utenti u 
LEFT JOIN 
    progetti p ON u.id = p.creatore_id 
WHERE 
    u.tipo_utente = 'creatore' 
GROUP BY 
    u.id; 

-- Trigger per aggiornare il conteggio dei progetti dell'utente 
DELIMITER // 
CREATE TRIGGER after_project_insert 
AFTER INSERT ON progetti 
FOR EACH ROW 
BEGIN 
    UPDATE utenti 
    SET nr_progetti = nr_progetti + 1 
    WHERE id = NEW.creatore_id; 
END // 

CREATE TRIGGER after_project_delete 
AFTER DELETE ON progetti 
FOR EACH ROW 
BEGIN 
    UPDATE utenti 
    SET nr_progetti = nr_progetti - 1 
    WHERE id = OLD.creatore_id; 
END // 
DELIMITER ; 

-- Trigger per aggiornare il budget raccolto quando viene aggiunto un finanziamento 
DELIMITER // 
CREATE TRIGGER after_funding_insert 
AFTER INSERT ON finanziamenti 
FOR EACH ROW 
BEGIN 
    IF NEW.stato_pagamento = 'completato' THEN 
        UPDATE progetti 
        SET budget_raccolto = budget_raccolto + NEW.importo 
        WHERE id = NEW.progetto_id; 
        
        -- Aggiorna anche la quantità riservata del reward 
        UPDATE reward 
        SET quantita_riservata = quantita_riservata + 1 
        WHERE id = NEW.reward_id; 
    END IF; 
END // 

CREATE TRIGGER after_funding_update 
AFTER UPDATE ON finanziamenti 
FOR EACH ROW 
BEGIN 
    IF NEW.stato_pagamento = 'completato' AND OLD.stato_pagamento != 'completato' THEN 
        UPDATE progetti 
        SET budget_raccolto = budget_raccolto + NEW.importo 
        WHERE id = NEW.progetto_id; 
        
        -- Aggiorna anche la quantità riservata del reward 
        UPDATE reward 
        SET quantita_riservata = quantita_riservata + 1 
        WHERE id = NEW.reward_id; 
    ELSEIF NEW.stato_pagamento != 'completato' AND OLD.stato_pagamento = 'completato' THEN 
        UPDATE progetti 
        SET budget_raccolto = budget_raccolto - OLD.importo 
        WHERE id = NEW.progetto_id; 
        
        -- Aggiorna anche la quantità riservata del reward 
        UPDATE reward 
        SET quantita_riservata = quantita_riservata - 1 
        WHERE id = NEW.reward_id; 
    END IF; 
END // 
DELIMITER ; 

-- Procedure per verificare lo stato dei progetti scaduti 
DELIMITER // 
CREATE PROCEDURE check_expired_projects() 
BEGIN 
    -- Chiudi i progetti scaduti 
    UPDATE progetti 
    SET stato = 'chiuso' 
    WHERE stato = 'aperto' AND data_scadenza < CURRENT_TIMESTAMP(); 
    
    -- Completa i progetti che hanno raggiunto l'obiettivo 
    UPDATE progetti 
    SET stato = 'completato' 
    WHERE stato = 'chiuso' AND budget_raccolto >= budget_richiesto; 
    
    -- Crea notifiche per i creatori dei progetti appena completati o chiusi 
    INSERT INTO notifiche (utente_id, tipo, messaggio, link) 
    SELECT 
        p.creatore_id, 
        CASE 
            WHEN p.stato = 'completato' THEN 'progetto_completato' 
            ELSE 'progetto_chiuso' 
        END, 
        CASE 
            WHEN p.stato = 'completato' THEN CONCAT('Il tuo progetto "', p.nome, '" ha raggiunto l\'obiettivo!') 
            ELSE CONCAT('Il tuo progetto "', p.nome, '" è terminato senza raggiungere l\'obiettivo.') 
        END, 
        CONCAT('/progetti/', p.id) 
    FROM 
        progetti p 
    WHERE 
        p.stato IN ('completato', 'chiuso') 
        AND p.data_scadenza BETWEEN DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) AND CURRENT_TIMESTAMP(); 
END // 
DELIMITER ; 

-- Event scheduler per eseguire la procedura ogni giorno 
CREATE EVENT IF NOT EXISTS daily_check_expired_projects 
ON SCHEDULE EVERY 1 DAY 
STARTS CURRENT_TIMESTAMP 
DO 
    CALL check_expired_projects(); 

-- Funzione per calcolare la percentuale di completamento di un progetto 
DELIMITER // 
CREATE FUNCTION calcola_percentuale_completamento(progetto_id INT) 
RETURNS DECIMAL(5,2) 
DETERMINISTIC 
BEGIN 
    DECLARE budget_richiesto DECIMAL(10,2); 
    DECLARE budget_raccolto DECIMAL(10,2); 
    DECLARE percentuale DECIMAL(5,2); 
    
    SELECT p.budget_richiesto, p.budget_raccolto 
    INTO budget_richiesto, budget_raccolto 
    FROM progetti p 
    WHERE p.id = progetto_id; 
    
    IF budget_richiesto = 0 THEN 
        RETURN 0; 
    END IF; 
    
    SET percentuale = (budget_raccolto / budget_richiesto) * 100; 
    
    IF percentuale > 100 THEN 
        RETURN 100; 
    ELSE 
        RETURN percentuale; 
    END IF; 
END // 
DELIMITER ; 

-- Commento finale 
-- Schema database completato per la piattaforma BOSTARTER
-- Questo schema include tutte le tabelle necessarie per gestire utenti, progetti, finanziamenti,
-- competenze, notifiche e altre funzionalità essenziali per una piattaforma di crowdfunding.