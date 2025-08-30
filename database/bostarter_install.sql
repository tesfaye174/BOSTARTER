-- =====================================================
-- BOSTARTER - INSTALLER DATABASE COMPLETO
-- Database ottimizzato per piattaforma crowdfunding
-- =====================================================

-- Sicurezza: Disabilita temporaneamente controlli per installazione
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

-- =====================================================
-- SEZIONE 1: CREAZIONE DATABASE
-- =====================================================

CREATE DATABASE IF NOT EXISTS bostarter
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE bostarter;

-- =====================================================
-- SEZIONE 2: TABELLE PRINCIPALI
-- =====================================================

-- Tabella utenti (entità principale)
CREATE TABLE IF NOT EXISTS utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita INT NOT NULL CHECK (anno_nascita BETWEEN 1900 AND 2020),
    luogo_nascita VARCHAR(100) NOT NULL,
    tipo_utente ENUM('normale', 'creatore', 'amministratore') DEFAULT 'normale',
    codice_sicurezza VARCHAR(50) NULL COMMENT 'Solo per amministratori',
    affidabilita DECIMAL(5,2) DEFAULT 0.00 CHECK (affidabilita BETWEEN 0 AND 100),
    nr_progetti INT DEFAULT 0 CHECK (nr_progetti >= 0),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_access TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indici per performance
    INDEX idx_email (email),
    INDEX idx_nickname (nickname),
    INDEX idx_tipo_utente (tipo_utente),
    INDEX idx_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB COMMENT='Utenti della piattaforma BOSTARTER';

-- Tabella competenze
CREATE TABLE IF NOT EXISTS competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) UNIQUE NOT NULL,
    descrizione TEXT,
    categoria VARCHAR(50) DEFAULT 'generale',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_nome (nome),
    INDEX idx_categoria (categoria),
    INDEX idx_active (is_active)
) ENGINE=InnoDB COMMENT='Competenze tecniche disponibili';

-- Tabella progetti
CREATE TABLE IF NOT EXISTS progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    descrizione TEXT NOT NULL,
    budget_richiesto DECIMAL(12,2) NOT NULL CHECK (budget_richiesto > 0),
    budget_raccolto DECIMAL(12,2) DEFAULT 0.00 CHECK (budget_raccolto >= 0),
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_limite DATE NOT NULL,
    stato ENUM('bozza', 'aperto', 'finanziato', 'chiuso', 'sospeso') DEFAULT 'bozza',
    tipo ENUM('hardware', 'software') NOT NULL,
    creatore_id INT NOT NULL,
    immagine_principale VARCHAR(500),
    views_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    
    -- Vincoli business
    CONSTRAINT chk_data_limite CHECK (data_limite > DATE(data_inserimento)),
    CONSTRAINT chk_budget_coerenza CHECK (budget_raccolto <= budget_richiesto),
    
    -- Indici per performance
    INDEX idx_nome (nome),
    INDEX idx_stato (stato),
    INDEX idx_tipo (tipo),
    INDEX idx_creatore (creatore_id),
    INDEX idx_data_limite (data_limite),
    INDEX idx_stato_tipo (stato, tipo)
) ENGINE=InnoDB COMMENT='Progetti di crowdfunding';

-- Tabella skill_utente (relazione N:M)
CREATE TABLE IF NOT EXISTS skill_utente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello TINYINT NOT NULL CHECK (livello BETWEEN 1 AND 5),
    data_acquisizione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_utente_competenza (utente_id, competenza_id),
    INDEX idx_utente (utente_id),
    INDEX idx_competenza (competenza_id),
    INDEX idx_livello (livello)
) ENGINE=InnoDB COMMENT='Competenze degli utenti';

-- Tabella rewards
CREATE TABLE IF NOT EXISTS rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    codice VARCHAR(50) UNIQUE NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    importo_minimo DECIMAL(10,2) NOT NULL CHECK (importo_minimo >= 0),
    quantita_disponibile INT DEFAULT NULL COMMENT 'NULL = illimitata',
    quantita_utilizzata INT DEFAULT 0 CHECK (quantita_utilizzata >= 0),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    
    INDEX idx_progetto (progetto_id),
    INDEX idx_codice (codice),
    INDEX idx_importo (importo_minimo)
) ENGINE=InnoDB COMMENT='Ricompense per finanziatori';

-- Tabella finanziamenti
CREATE TABLE IF NOT EXISTS finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    reward_id INT NULL,
    importo DECIMAL(10,2) NOT NULL CHECK (importo > 0),
    messaggio_supporto TEXT,
    stato_pagamento ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    metodo_pagamento VARCHAR(50),
    transaction_id VARCHAR(100),
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_completamento TIMESTAMP NULL,
    
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE SET NULL,
    
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_stato (stato_pagamento),
    INDEX idx_data (data_finanziamento)
) ENGINE=InnoDB COMMENT='Finanziamenti ai progetti';

-- Tabella commenti
CREATE TABLE IF NOT EXISTS commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    testo TEXT NOT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    
    INDEX idx_progetto (progetto_id),
    INDEX idx_data (data_commento)
) ENGINE=InnoDB COMMENT='Commenti sui progetti';

-- Tabella profili_richiesti (solo progetti software)
CREATE TABLE IF NOT EXISTS profili_richiesti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descrizione TEXT,
    numero_posizioni INT DEFAULT 1 CHECK (numero_posizioni > 0),
    posizioni_occupate INT DEFAULT 0 CHECK (posizioni_occupate >= 0),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    
    INDEX idx_progetto (progetto_id),
    CONSTRAINT chk_posizioni_coerenza CHECK (posizioni_occupate <= numero_posizioni)
) ENGINE=InnoDB COMMENT='Profili richiesti per progetti software';

-- Tabella skill_profili
CREATE TABLE IF NOT EXISTS skill_profili (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profilo_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello_minimo TINYINT NOT NULL CHECK (livello_minimo BETWEEN 1 AND 5),
    is_obbligatoria BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (profilo_id) REFERENCES profili_richiesti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_profilo_competenza (profilo_id, competenza_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_competenza (competenza_id)
) ENGINE=InnoDB COMMENT='Competenze richieste per profili';

-- Tabella candidature
CREATE TABLE IF NOT EXISTS candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    profilo_id INT NOT NULL,
    motivazione TEXT,
    stato ENUM('in_valutazione', 'accettata', 'rifiutata') DEFAULT 'in_valutazione',
    punteggio_matching DECIMAL(5,2) DEFAULT 0.00,
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_valutazione TIMESTAMP NULL,
    note_valutazione TEXT,
    valutatore_id INT NULL,
    
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (profilo_id) REFERENCES profili_richiesti(id) ON DELETE CASCADE,
    FOREIGN KEY (valutatore_id) REFERENCES utenti(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_utente_profilo (utente_id, profilo_id),
    INDEX idx_utente (utente_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_stato (stato)
) ENGINE=InnoDB COMMENT='Candidature per profili progetti';

-- =====================================================
-- SEZIONE 3: COMPETENZE BASE
-- =====================================================

INSERT INTO competenze (nome, descrizione, categoria) VALUES
-- Linguaggi di Programmazione
('JavaScript', 'Linguaggio per sviluppo web frontend e backend', 'programmazione'),
('Python', 'Linguaggio versatile per AI, web development, automation', 'programmazione'),
('Java', 'Linguaggio enterprise per applicazioni robuste', 'programmazione'),
('C++', 'Linguaggio per applicazioni ad alte performance', 'programmazione'),
('HTML/CSS', 'Markup e styling per web development', 'frontend'),
('React', 'Libreria JavaScript per interfacce utente', 'frontend'),
('Vue.js', 'Framework JavaScript progressivo', 'frontend'),
('Angular', 'Framework enterprise per applicazioni web', 'frontend'),
('Node.js', 'Runtime JavaScript lato server', 'backend'),
('PHP', 'Linguaggio per sviluppo web server-side', 'backend'),
('SQL', 'Linguaggio per gestione database relazionali', 'database'),

-- Specializzazioni Tecniche
('Machine Learning', 'Algoritmi di apprendimento automatico', 'ai'),
('Deep Learning', 'Reti neurali profonde', 'ai'),
('Data Science', 'Analisi e interpretazione dati', 'data'),
('DevOps', 'Pratiche per sviluppo e deployment', 'infrastructure'),
('Docker', 'Containerizzazione applicazioni', 'infrastructure'),
('Kubernetes', 'Orchestrazione container', 'infrastructure'),

-- Design e UX
('UI/UX Design', 'Progettazione interfacce utente', 'design'),
('Graphic Design', 'Design grafico e comunicazione visiva', 'design'),
('3D Modeling', 'Modellazione tridimensionale', 'design'),

-- Management e Business
('Project Management', 'Gestione progetti e team', 'management'),
('Digital Marketing', 'Marketing digitale e social media', 'business'),
('Cybersecurity', 'Sicurezza informatica', 'security'),

-- Tecnologie Emergenti
('Blockchain', 'Tecnologie blockchain e cryptocurrency', 'emerging'),
('IoT', 'Internet of Things e dispositivi connessi', 'hardware'),
('AR/VR', 'Realtà aumentata e virtuale', 'emerging'),
('Game Development', 'Sviluppo videogiochi', 'gamedev');

-- =====================================================
-- SEZIONE 4: UTENTE AMMINISTRATORE
-- =====================================================

-- Crea utente admin (password: admin123)
INSERT INTO utenti (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza, is_active) 
VALUES ('admin@bostarter.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'BOSTARTER', 1990, 'Milano', 'amministratore', 'ADMIN2025', TRUE);

-- =====================================================
-- SEZIONE 5: VISTE STATISTICHE ESSENZIALI
-- =====================================================

-- Vista progetti con statistiche
CREATE OR REPLACE VIEW view_progetti AS
SELECT 
    p.id as progetto_id,
    p.nome,
    p.descrizione,
    p.budget_richiesto,
    p.budget_raccolto,
    p.stato,
    p.tipo,
    p.data_limite,
    p.views_count,
    u.nickname as creatore_nickname,
    u.affidabilita as creatore_affidabilita,
    COALESCE(stats.totale_finanziato, 0) as totale_finanziato,
    COALESCE(stats.numero_sostenitori, 0) as numero_sostenitori,
    ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale_finanziamento,
    DATEDIFF(p.data_limite, CURDATE()) as giorni_rimanenti
FROM progetti p
JOIN utenti u ON p.creatore_id = u.id
LEFT JOIN (
    SELECT 
        progetto_id,
        SUM(CASE WHEN stato_pagamento = 'completed' THEN importo ELSE 0 END) as totale_finanziato,
        COUNT(DISTINCT CASE WHEN stato_pagamento = 'completed' THEN utente_id END) as numero_sostenitori
    FROM finanziamenti 
    GROUP BY progetto_id
) stats ON p.id = stats.progetto_id
WHERE p.is_active = TRUE;

-- Vista statistiche generali
CREATE OR REPLACE VIEW view_statistiche_generali AS
SELECT 
    (SELECT COUNT(*) FROM utenti WHERE is_active = TRUE) as utenti_attivi,
    (SELECT COUNT(*) FROM utenti WHERE tipo_utente = 'creatore') as creatori_totali,
    (SELECT COUNT(*) FROM progetti WHERE stato != 'bozza') as progetti_pubblicati,
    (SELECT COUNT(*) FROM progetti WHERE stato = 'finanziato') as progetti_finanziati,
    (SELECT COUNT(*) FROM progetti WHERE stato = 'aperto') as progetti_attivi,
    (SELECT COALESCE(SUM(budget_raccolto), 0) FROM progetti) as budget_totale_raccolto,
    (SELECT COUNT(*) FROM finanziamenti WHERE stato_pagamento = 'completed') as finanziamenti_completati;

-- =====================================================
-- SEZIONE 6: TRIGGER ESSENZIALI
-- =====================================================

DELIMITER //

-- Trigger per aggiornare budget raccolto
CREATE TRIGGER tr_aggiorna_budget_finanziamento
AFTER UPDATE ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE delta DECIMAL(12,2) DEFAULT 0;
    
    IF OLD.stato_pagamento != 'completed' AND NEW.stato_pagamento = 'completed' THEN
        SET delta = NEW.importo;
    ELSEIF OLD.stato_pagamento = 'completed' AND NEW.stato_pagamento != 'completed' THEN
        SET delta = -OLD.importo;
    END IF;
    
    IF delta != 0 THEN
        UPDATE progetti 
        SET budget_raccolto = GREATEST(budget_raccolto + delta, 0),
            updated_at = CURRENT_TIMESTAMP
        WHERE id = NEW.progetto_id;
    END IF;
END //

-- Trigger per aggiornare numero progetti
CREATE TRIGGER tr_aggiorna_nr_progetti
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti 
    SET nr_progetti = nr_progetti + 1,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.creatore_id;
END //

-- Trigger per validare creazione progetti
CREATE TRIGGER tr_valida_creatore_progetto
BEFORE INSERT ON progetti
FOR EACH ROW
BEGIN
    DECLARE utente_tipo VARCHAR(20);
    
    SELECT tipo_utente INTO utente_tipo 
    FROM utenti 
    WHERE id = NEW.creatore_id;
    
    IF utente_tipo NOT IN ('creatore', 'amministratore') THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Solo creatori e amministratori possono creare progetti';
    END IF;
    
    IF NEW.data_limite <= CURDATE() THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'La data limite deve essere futura';
    END IF;
END //

DELIMITER ;

-- =====================================================
-- SEZIONE 7: RIPRISTINO IMPOSTAZIONI
-- =====================================================

-- Ripristina impostazioni originali
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- =====================================================
-- INSTALLAZIONE COMPLETATA
-- =====================================================

SELECT 'Database BOSTARTER installato con successo!' as status,
       'Utente admin creato: admin@bostarter.com' as admin_info,
       'Password admin: admin123' as admin_password,
       'Codice sicurezza: ADMIN2025' as security_code;
