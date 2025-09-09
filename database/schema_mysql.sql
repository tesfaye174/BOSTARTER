-- =====================================================
-- BOSTARTER - Schema MySQL Completo
-- Versione: 3.0 MySQL
-- Data: 2025-09-09
-- Descrizione: Schema aggiornato per piattaforma crowdfunding
-- =====================================================

-- Creazione database
CREATE DATABASE IF NOT EXISTS bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bostarter;

-- Abilita il log delle query lente
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
SET GLOBAL log_queries_not_using_indexes = 1;

-- Rimozione tabelle esistenti (ordine inverso per foreign keys)
SET FOREIGN_KEY_CHECKS = 0;

-- Tabelle di sistema
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS system_log;

-- Tabelle di relazione
DROP TABLE IF EXISTS competenze_utente;
DROP TABLE IF EXISTS candidature;
DROP TABLE IF EXISTS commenti;
DROP TABLE IF EXISTS finanziamenti;

-- Tabelle principali
DROP TABLE IF EXISTS riconoscimenti;
DROP TABLE IF EXISTS profili_software;
DROP TABLE IF EXISTS componenti_hardware;
DROP TABLE IF EXISTS progetti;
DROP TABLE IF EXISTS competenze;
DROP TABLE IF EXISTS amministratori;
DROP TABLE IF EXISTS creatori;
DROP TABLE IF EXISTS utenti;
DROP TABLE IF EXISTS profili_competenze;
DROP TABLE IF EXISTS profili;
DROP TABLE IF EXISTS progetti_competenze;
DROP TABLE IF EXISTS progetti;
DROP TABLE IF EXISTS utenti_competenze;
DROP TABLE IF EXISTS competenze;
DROP TABLE IF EXISTS categorie;
DROP TABLE IF EXISTS utenti;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CREAZIONE TABELLE PRINCIPALI
-- =====================================================

-- Tabella UTENTI
CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    nickname VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita YEAR NOT NULL,
    luogo_nascita VARCHAR(100),
    bio TEXT,
    avatar_url VARCHAR(255),
    data_registrazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_accesso DATETIME,
    stato ENUM('attivo', 'sospeso', 'disattivato') DEFAULT 'attivo',
    token_verifica VARCHAR(100),
    token_recupero VARCHAR(100),
    token_scadenza DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_utente_email (email),
    INDEX idx_utente_nickname (nickname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella AMMINISTRATORI
CREATE TABLE amministratori (
    utente_id INT PRIMARY KEY,
    codice_sicurezza VARCHAR(100) NOT NULL,
    livello_permessi ENUM('base', 'avanzato', 'superadmin') DEFAULT 'base',
    data_inserimento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_amministratore_utente (utente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella CREATORI
CREATE TABLE creatori (
    utente_id INT PRIMARY KEY,
    nr_progetti INT DEFAULT 0,
    affidabilita DECIMAL(5,2) DEFAULT 0.00,
    sito_web VARCHAR(255),
    facebook_url VARCHAR(255),
    twitter_url VARCHAR(255),
    instagram_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_creatore_utente (utente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella COMPETENZE
CREATE TABLE competenze (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descrizione TEXT,
    categoria ENUM('programmazione', 'design', 'marketing', 'business', 'altro') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_competenza_nome (nome, categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella di relazione UTENTI-COMPETENZE
CREATE TABLE competenze_utente (
    utente_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello ENUM('base', 'intermedio', 'avanzato', 'esperto') NOT NULL DEFAULT 'intermedio',
    anni_esperienza DECIMAL(3,1) DEFAULT 1.0,
    certificata BOOLEAN DEFAULT FALSE,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_aggiornamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (utente_id, competenza_id),
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    INDEX idx_competenza_utente (utente_id, competenza_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella PROGETTI
CREATE TABLE progetti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creatore_id INT NOT NULL,
    titolo VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    descrizione LONGTEXT NOT NULL,
    descrizione_breve VARCHAR(500),
    budget DECIMAL(12,2) NOT NULL,
    finanziamento_attuale DECIMAL(12,2) DEFAULT 0.00,
    tipo_progetto ENUM('hardware', 'software') NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    copertina_url VARCHAR(255),
    video_url VARCHAR(255),
    data_inizio DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_fine DATETIME NOT NULL,
    stato ENUM('bozza', 'in_revisione', 'pubblicato', 'rifiutato', 'sospeso', 'completato', 'fallito') DEFAULT 'bozza',
    motivazione_rifiuto TEXT,
    paese VARCHAR(100),
    citta VARCHAR(100),
    latitudine DECIMAL(10,8),
    longitudine DECIMAL(11,8),
    privacy_termini BOOLEAN DEFAULT FALSE,
    privacy_dati BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creatore_id) REFERENCES creatori(utente_id) ON DELETE CASCADE,
    UNIQUE KEY uk_progetto_slug (slug),
    INDEX idx_progetto_creatore (creatore_id),
    INDEX idx_progetto_stato (stato),
    INDEX idx_progetto_data (data_fine, stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella COMPONENTI HARDWARE
CREATE TABLE componenti_hardware (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT,
    prezzo_unitario DECIMAL(10,2) NOT NULL,
    quantita INT NOT NULL DEFAULT 1,
    link_acquisto VARCHAR(255),
    note TEXT,
    ordine INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_componente_progetto (progetto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella PROFILI SOFTWARE
CREATE TABLE profili_software (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    titolo VARCHAR(200) NOT NULL,
    descrizione TEXT,
    competenze_richieste JSON,
    quantita_richiesta INT DEFAULT 1,
    quantita_disponibile INT DEFAULT 1,
    note TEXT,
    ordine INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_profilo_progetto (progetto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella RICONOSCIMENTI (Rewards)
CREATE TABLE riconoscimenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    titolo VARCHAR(200) NOT NULL,
    descrizione TEXT,
    importo_minimo DECIMAL(10,2) NOT NULL,
    quantita_disponibile INT,
    quantita_riservata INT DEFAULT 0,
    quantita_erogata INT DEFAULT 0,
    data_consegna_stimata DATE,
    spedizione_disponibile BOOLEAN DEFAULT TRUE,
    costo_spedizione DECIMAL(10,2) DEFAULT 0.00,
    paesi_spedizione JSON,
    limite_per_utente INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_riconoscimento_progetto (progetto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella FINANZIAMENTI
CREATE TABLE finanziamenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    riconoscimento_id INT,
    importo DECIMAL(10,2) NOT NULL,
    commissione_platform DECIMAL(10,2) NOT NULL,
    importo_netto DECIMAL(10,2) NOT NULL,
    metodo_pagamento VARCHAR(50) NOT NULL,
    id_transazione VARCHAR(255),
    stato ENUM('in_attesa', 'elaborazione', 'completato', 'annullato', 'rifiutato', 'rimborsato') DEFAULT 'in_attesa',
    dati_pagamento JSON,
    indirizzo_spedizione JSON,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (riconoscimento_id) REFERENCES riconoscimenti(id) ON DELETE SET NULL,
    INDEX idx_finanziamento_utente (utente_id),
    INDEX idx_finanziamento_progetto (progetto_id),
    INDEX idx_finanziamento_stato (stato, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella COMMENTI
CREATE TABLE commenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    commento_genitore_id INT,
    contenuto TEXT NOT NULL,
    modificato BOOLEAN DEFAULT FALSE,
    segnalato INT DEFAULT 0,
    motivo_segnalazione TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (commento_genitore_id) REFERENCES commenti(id) ON DELETE CASCADE,
    INDEX idx_commento_utente (utente_id),
    INDEX idx_commento_progetto (progetto_id),
    INDEX idx_commento_genitore (commento_genitore_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella CANDIDATURE
CREATE TABLE candidature (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    profilo_software_id INT NOT NULL,
    messaggio TEXT,
    stato ENUM('in_attesa', 'visionato', 'selezionato', 'scartato') DEFAULT 'in_attesa',
    valutazione_creatore TEXT,
    punteggio INT DEFAULT 0,
    data_colloquio DATETIME,
    note_colloquio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (profilo_software_id) REFERENCES profili_software(id) ON DELETE CASCADE,
    UNIQUE KEY uk_candidatura (utente_id, profilo_software_id),
    INDEX idx_candidatura_utente (utente_id),
    INDEX idx_candidatura_profilo (profilo_software_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella di LOG DI SISTEMA
CREATE TABLE system_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT,
    azione VARCHAR(100) NOT NULL,
    entita_tipo VARCHAR(50),
    entita_id INT,
    dettagli JSON,
    indirizzo_ip VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE SET NULL,
    INDEX idx_log_azione (azione),
    INDEX idx_log_entita (entita_tipo, entita_id),
    INDEX idx_log_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella AUDIT LOGS (per tracciamento modifiche)
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT,
    azione VARCHAR(100) NOT NULL,
    tabella VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    valori_vecchi JSON,
    valori_nuovi JSON,
    indirizzo_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE SET NULL,
    INDEX idx_audit_tabella (tabella, record_id),
    INDEX idx_audit_utente (utente_id),
    INDEX idx_audit_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INDICI AGGIUNTIVI PER PERFORMANCE
-- =====================================================

-- Indici per ricerche full-text
CREATE FULLTEXT INDEX ft_progetti_titolo_descrizione ON progetti(titolo, descrizione_breve);
CREATE FULLTEXT INDEX ft_utenti_nome_cognome ON utenti(nome, cognome, nickname);
CREATE FULLTEXT INDEX ft_competenze_nome_descrizione ON competenze(nome, descrizione);

-- Indici per geolocalizzazione
CREATE SPATIAL INDEX sp_progetti_location ON progetti(latitudine, longitudine);

-- =====================================================
-- VISTE PER STATISTICHE E REPORTING
-- =====================================================

-- Vista per la classifica dei creatori
CREATE OR REPLACE VIEW vw_classifica_creatori AS
SELECT 
    c.utente_id,
    u.nickname,
    u.avatar_url,
    COUNT(p.id) as progetti_totali,
    SUM(CASE WHEN p.stato = 'completato' THEN 1 ELSE 0 END) as progetti_completati,
    ROUND(AVG(CASE WHEN p.stato = 'completato' THEN 1 ELSE 0 END) * 100, 2) as tasso_successo,
    SUM(CASE WHEN p.stato = 'completato' THEN p.finanziamento_attuale ELSE 0 END) as totale_raccolto,
    COUNT(DISTINCT f.utente_id) as sostenitori_totali,
    MAX(p.updated_at) as ultimo_aggiornamento
FROM creatori c
JOIN utenti u ON c.utente_id = u.id
LEFT JOIN progetti p ON c.utente_id = p.creatore_id
LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato = 'completato'
GROUP BY c.utente_id, u.nickname, u.avatar_url
ORDER BY progetti_completati DESC, totale_raccolto DESC
LIMIT 100;

-- Vista per l'avanzamento dei finanziamenti
CREATE OR REPLACE VIEW vw_avanzamento_finanziamenti AS
SELECT 
    p.id,
    p.titolo,
    p.slug,
    p.creatore_id,
    u.nickname as creatore,
    p.copertina_url,
    p.budget,
    p.finanziamento_attuale,
    ROUND((p.finanziamento_attuale / p.budget * 100), 2) as percentuale_raccolta,
    DATEDIFF(p.data_fine, CURDATE()) as giorni_rimanenti,
    p.data_fine,
    p.stato,
    p.categoria,
    p.paese,
    p.citta,
    COUNT(DISTINCT f.utente_id) as sostenitori,
    p.created_at,
    p.updated_at
FROM progetti p
JOIN utenti u ON p.creatore_id = u.id
LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato = 'completato'
WHERE p.stato = 'pubblicato'
GROUP BY p.id, p.titolo, p.slug, p.creatore_id, u.nickname, p.copertina_url, 
         p.budget, p.finanziamento_attuale, p.data_fine, p.stato, p.categoria,
         p.paese, p.citta, p.created_at, p.updated_at
ORDER BY percentuale_raccolta DESC, giorni_rimanenti ASC;

-- Vista per i migliori sostenitori
CREATE OR REPLACE VIEW vw_migliori_sostenitori AS
SELECT 
    u.id,
    u.nickname,
    u.avatar_url,
    COUNT(DISTINCT f.progetto_id) as progetti_sostenuti,
    COUNT(DISTINCT p.creatore_id) as creatori_sostenuti,
    SUM(f.importo) as totale_contribuito,
    MAX(f.created_at) as ultima_donazione,
    MIN(f.created_at) as prima_donazione,
    COUNT(DISTINCT MONTH(f.created_at)) as mesi_attivi,
    ROUND(SUM(f.importo) / COUNT(DISTINCT f.progetto_id), 2) as media_per_progetto
FROM utenti u
JOIN finanziamenti f ON u.id = f.utente_id
JOIN progetti p ON f.progetto_id = p.id
WHERE f.stato = 'completato'
GROUP BY u.id, u.nickname, u.avatar_url
ORDER BY totale_contribuito DESC, progetti_sostenuti DESC
LIMIT 100;

-- =====================================================
-- FUNZIONI E PROCEDURE UTILITY
-- =====================================================

-- Funzione per generare uno slug a partire da una stringa
DELIMITER //
CREATE FUNCTION slugify(original VARCHAR(255)) 
RETURNS VARCHAR(255) DETERMINISTIC
BEGIN
    DECLARE result VARCHAR(255);
    SET result = LOWER(original);
    -- Rimuovi caratteri speciali
    SET result = REGEXP_REPLACE(result, '[^a-z0-9]+', '-');
    -- Rimuovi trattini multipli
    SET result = REGEXP_REPLACE(result, '-+', '-');
    -- Rimuovi trattini all'inizio e alla fine
    SET result = TRIM(BOTH '-' FROM result);
    RETURN result;
END //
DELIMITER ;

-- Procedura per aggiornare l'affidabilità di un creatore
DELIMITER //
CREATE PROCEDURE aggiorna_affidabilita_creatore(IN p_creatore_id INT)
BEGIN
    DECLARE v_progetti_totali INT;
    DECLARE v_progetti_completati INT;
    DECLARE v_affidabilita DECIMAL(5,2);
    
    -- Conta progetti totali e completati
    SELECT 
        COUNT(*),
        SUM(CASE WHEN stato = 'completato' THEN 1 ELSE 0)
    INTO v_progetti_totali, v_progetti_completati
    FROM progetti
    WHERE creatore_id = p_creatore_id;
    
    -- Calcola affidabilità (in percentuale)
    IF v_progetti_totali > 0 THEN
        SET v_affidabilita = (v_progetti_completati / v_progetti_totali) * 100;
    ELSE
        SET v_affidabilita = 0;
    END IF;
    
    -- Aggiorna il record del creatore
    UPDATE creatori 
    SET affidabilita = v_affidabilita
    WHERE utente_id = p_creatore_id;
    
    -- Log dell'operazione
    INSERT INTO system_log (utente_id, azione, entita_tipo, entita_id, dettagli)
    VALUES (p_creatore_id, 'aggiornamento_affidabilita', 'creatore', p_creatore_id, 
            JSON_OBJECT('affidabilita', v_affidabilita, 'progetti_totali', v_progetti_totali, 'progetti_completati', v_progetti_completati));
END //
DELIMITER ;

-- =====================================================
-- TRIGGERS PER GESTIONE AUTOMATICA
-- =====================================================

-- Trigger per aggiornare il contatore progetti di un creatore
DELIMITER //
CREATE TRIGGER trg_aggiorna_conteggio_progetti
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE creatori 
    SET nr_progetti = nr_progetti + 1
    WHERE utente_id = NEW.creatore_id;
END //

-- Trigger per aggiornare il finanziamento attuale di un progetto
CREATE TRIGGER trg_aggiorna_finanziamento_progetto
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    IF NEW.stato = 'completato' THEN
        UPDATE progetti 
        SET finanziamento_attuale = finanziamento_attuale + NEW.importo_netto,
            updated_at = NOW()
        WHERE id = NEW.progetto_id;
    END IF;
END //

-- Trigger per aggiornare la quantità di riconoscimenti riservati
CREATE TRIGGER trg_aggiorna_riconoscimento
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    IF NEW.riconoscimento_id IS NOT NULL AND NEW.stato = 'completato' THEN
        UPDATE riconoscimenti 
        SET quantita_riservata = quantita_riservata + 1,
            quantita_disponibile = quantita_disponibile - 1,
            updated_at = NOW()
        WHERE id = NEW.riconoscimento_id;
    END IF;
END //

-- Trigger per il log delle modifiche
CREATE TRIGGER trg_log_modifiche_utenti
AFTER UPDATE ON utenti
FOR EACH ROW
BEGIN
    DECLARE changes JSON;
    SET changes = JSON_OBJECT();
    
    IF OLD.nome != NEW.nome THEN
        SET changes = JSON_SET(changes, '$.nome', JSON_OBJECT('old', OLD.nome, 'new', NEW.nome));
    END IF;
    
    IF OLD.email != NEW.email THEN
        SET changes = JSON_SET(changes, '$.email', JSON_OBJECT('old', OLD.email, 'new', NEW.email));
    END IF;
    
    -- Aggiungi altri campi da tracciare...
    
    IF JSON_LENGTH(changes) > 0 THEN
        INSERT INTO audit_logs (utente_id, azione, tabella, record_id, valori_vecchi, valori_nuovi)
        VALUES (NEW.id, 'aggiornamento', 'utenti', NEW.id, 
                (SELECT JSON_OBJECT('nome', OLD.nome, 'email', OLD.email, 'stato', OLD.stato)),
                (SELECT JSON_OBJECT('nome', NEW.nome, 'email', NEW.email, 'stato', NEW.stato)));
    END IF;
END //

DELIMITER ;

-- =====================================================
-- EVENTI PROGRAMMATI
-- =====================================================

-- Evento per chiudere automaticamente i progetti scaduti
DELIMITER //
CREATE EVENT IF NOT EXISTS chiudi_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURRENT_DATE, '00:05:00')
DO
BEGIN
    -- Aggiorna i progetti scaduti a 'fallito' se non hanno raggiunto il budget
    UPDATE progetti p
    SET stato = 'fallito',
        updated_at = NOW()
    WHERE p.stato = 'pubblicato'
    AND p.data_fine < NOW()
    AND p.finanziamento_attuale < p.budget;
    
    -- Aggiorna i progetti scaduti a 'completato' se hanno raggiunto il budget
    UPDATE progetti p
    SET stato = 'completato',
        updated_at = NOW()
    WHERE p.stato = 'pubblicato'
    AND p.data_fine < NOW()
    AND p.finanziamento_attuale >= p.budget;
    
    -- Log dell'operazione
    INSERT INTO system_log (azione, entita_tipo, dettagli)
    VALUES ('chiusura_automatica_progetti', 'sistema', 
            JSON_OBJECT('timestamp', NOW(), 'progetti_aggiornati', ROW_COUNT()));
END //

-- Evento per aggiornare le statistiche giornaliere
CREATE EVENT IF NOT EXISTS aggiorna_statistiche_giornaliere
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURRENT_DATE, '03:00:00')
DO
BEGIN
    -- Inserisci qui la logica per aggiornare le statistiche giornaliere
    -- Esempio: contatori, metriche, report, ecc.
    
    -- Log dell'operazione
    INSERT INTO system_log (azione, entita_tipo, dettagli)
    VALUES ('aggiornamento_statistiche_giornaliere', 'sistema', 
            JSON_OBJECT('timestamp', NOW()));
END //

DELIMITER ;

-- =====================================================
-- POPOLAMENTO INIZIALE DATI DI SISTEMA
-- =====================================================

-- Inserimento competenze di base
INSERT INTO competenze (nome, descrizione, categoria) VALUES
('PHP', 'Linguaggio di programmazione server-side', 'programmazione'),
('JavaScript', 'Linguaggio di programmazione lato client', 'programmazione'),
('HTML/CSS', 'Linguaggi di markup e stile per il web', 'programmazione'),
('MySQL', 'Database relazionale', 'programmazione'),
('Node.js', 'Runtime JavaScript lato server', 'programmazione'),
('React', 'Libreria JavaScript per interfacce utente', 'programmazione'),
('UI/UX Design', 'Progettazione interfacce ed esperienze utente', 'design'),
('Graphic Design', 'Grafica vettoriale e bitmap', 'design'),
('Social Media Marketing', 'Marketing attraverso i social network', 'marketing'),
('SEO', 'Ottimizzazione per i motori di ricerca', 'marketing'),
('Business Development', 'Sviluppo strategie di business', 'business'),
('Project Management', 'Gestione progetti e team', 'business'),
('Copywriting', 'Scrittura persuasiva per il web', 'marketing'),
('Video Editing', 'Montaggio e post-produzione video', 'design'),
('Fotografia', 'Fotografia digitale e post-produzione', 'design');

-- Inserimento utente amministratore predefinito (password: Admin123!)
INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, stato)
VALUES ('admin@bostarter.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Sistema', '1990', 'Milano', 'attivo');

-- Assegna il ruolo di amministratore
INSERT INTO amministratori (utente_id, codice_sicurezza, livello_permessi)
VALUES (LAST_INSERT_ID(), UUID(), 'superadmin');

-- Inserimento categoria progetti predefinite
-- (Da implementare se si utilizza una tabella separata per le categorie)

-- =====================================================
-- FINE SCRIPT
-- =====================================================

-- =====================================================
-- TABELLE PRINCIPALI
-- =====================================================

-- Tabella utenti
CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(50),
    cognome VARCHAR(50),
    data_nascita DATE,
    citta VARCHAR(100),
    biografia TEXT,
    tipo_utente ENUM('UTENTE', 'CREATORE', 'ADMIN') DEFAULT 'UTENTE',
    codice_sicurezza VARCHAR(255) NULL, -- Solo per amministratori
    affidabilita DECIMAL(3,2) DEFAULT 0.00,
    nr_progetti INT DEFAULT 0,
    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_accesso TIMESTAMP NULL,
    attivo BOOLEAN DEFAULT TRUE,
    email_verificata BOOLEAN DEFAULT FALSE,
    token_verifica VARCHAR(255),
    reset_token VARCHAR(255),
    reset_scadenza TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_tipo_utente (tipo_utente),
    INDEX idx_affidabilita (affidabilita),
    INDEX idx_data_registrazione (data_registrazione)
) ENGINE=InnoDB;

-- Tabella categorie
CREATE TABLE categorie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT,
    icona VARCHAR(50),
    colore VARCHAR(7),
    attiva BOOLEAN DEFAULT TRUE,
    
    INDEX idx_nome (nome),
    INDEX idx_attiva (attiva)
) ENGINE=InnoDB;

-- Tabella competenze
CREATE TABLE competenze (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT,
    categoria_id INT,
    livello_richiesto ENUM('BASE', 'INTERMEDIO', 'AVANZATO', 'ESPERTO') DEFAULT 'BASE',
    attiva BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (categoria_id) REFERENCES categorie(id) ON DELETE SET NULL,
    INDEX idx_nome (nome),
    INDEX idx_categoria (categoria_id),
    INDEX idx_livello (livello_richiesto)
) ENGINE=InnoDB;

-- Tabella utenti_competenze (relazione many-to-many)
CREATE TABLE utenti_competenze (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello INT NOT NULL DEFAULT 0 CHECK (livello BETWEEN 0 AND 5),
    anni_esperienza INT DEFAULT 0,
    certificato BOOLEAN DEFAULT FALSE,
    data_aggiunta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_utente_competenza (utente_id, competenza_id),
    INDEX idx_utente (utente_id),
    INDEX idx_competenza (competenza_id),
    INDEX idx_livello (livello)
) ENGINE=InnoDB;

-- Tabella progetti
CREATE TABLE progetti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titolo VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    descrizione_breve VARCHAR(500),
    categoria_id INT,
    creatore_id INT NOT NULL,
    budget_richiesto DECIMAL(12,2) NOT NULL,
    budget_raccolto DECIMAL(12,2) DEFAULT 0.00,
    data_inizio DATE NOT NULL,
    data_fine DATE NOT NULL,
    tipo_progetto ENUM('HARDWARE', 'SOFTWARE') NOT NULL,
    stato ENUM('BOZZA', 'ATTIVO', 'COMPLETATO', 'FALLITO', 'CANCELLATO') DEFAULT 'BOZZA',
    immagine_copertina VARCHAR(255),
    video_pitch VARCHAR(255),
    localizzazione VARCHAR(200),
    nr_sostenitori INT DEFAULT 0,
    nr_commenti INT DEFAULT 0,
    nr_candidature INT DEFAULT 0,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    visibilita ENUM('PUBBLICA', 'PRIVATA', 'NASCOSTA') DEFAULT 'PUBBLICA',
    
    FOREIGN KEY (categoria_id) REFERENCES categorie(id) ON DELETE SET NULL,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_titolo (titolo),
    INDEX idx_categoria (categoria_id),
    INDEX idx_creatore (creatore_id),
    INDEX idx_stato (stato),
    INDEX idx_data_fine (data_fine),
    INDEX idx_budget_raccolto (budget_raccolto),
    INDEX idx_data_creazione (data_creazione)
) ENGINE=InnoDB;

-- Tabella progetti_competenze (relazione many-to-many)
CREATE TABLE progetti_competenze (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello_richiesto ENUM('BASE', 'INTERMEDIO', 'AVANZATO', 'ESPERTO') DEFAULT 'BASE',
    obbligatoria BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progetto_competenza (progetto_id, competenza_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_competenza (competenza_id)
) ENGINE=InnoDB;

-- Tabella profili (team members per progetti)
CREATE TABLE profili (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    ruolo VARCHAR(100) NOT NULL,
    descrizione TEXT,
    email VARCHAR(100),
    linkedin VARCHAR(200),
    foto VARCHAR(255),
    ordine_visualizzazione INT DEFAULT 0,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_ordine (ordine_visualizzazione)
) ENGINE=InnoDB;

-- Tabella profili_competenze (competenze dei team members)
CREATE TABLE profili_competenze (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profilo_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello ENUM('BASE', 'INTERMEDIO', 'AVANZATO', 'ESPERTO') DEFAULT 'BASE',
    
    FOREIGN KEY (profilo_id) REFERENCES profili(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_profilo_competenza (profilo_id, competenza_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_competenza (competenza_id)
) ENGINE=InnoDB;

-- Tabella ricompense
CREATE TABLE ricompense (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    titolo VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    importo_minimo DECIMAL(10,2) NOT NULL,
    quantita_disponibile INT,
    quantita_prenotata INT DEFAULT 0,
    data_consegna_stimata DATE,
    spedizione_inclusa BOOLEAN DEFAULT FALSE,
    digitale BOOLEAN DEFAULT FALSE,
    attiva BOOLEAN DEFAULT TRUE,
    ordine_visualizzazione INT DEFAULT 0,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_importo (importo_minimo),
    INDEX idx_attiva (attiva)
) ENGINE=InnoDB;

-- Tabella finanziamenti
CREATE TABLE finanziamenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    ricompensa_id INT,
    importo DECIMAL(10,2) NOT NULL,
    messaggio TEXT,
    anonimo BOOLEAN DEFAULT FALSE,
    stato ENUM('PENDING', 'COMPLETATO', 'FALLITO', 'RIMBORSATO') DEFAULT 'PENDING',
    metodo_pagamento VARCHAR(50),
    transaction_id VARCHAR(100),
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_elaborazione TIMESTAMP NULL,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (ricompensa_id) REFERENCES ricompense(id) ON DELETE SET NULL,
    INDEX idx_progetto (progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_stato (stato),
    INDEX idx_data (data_finanziamento),
    INDEX idx_importo (importo)
) ENGINE=InnoDB;

-- Tabella commenti
CREATE TABLE commenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    parent_id INT NULL,
    contenuto TEXT NOT NULL,
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modificato BOOLEAN DEFAULT FALSE,
    data_modifica TIMESTAMP NULL,
    approvato BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES commenti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_parent (parent_id),
    INDEX idx_data (data_commento)
) ENGINE=InnoDB;

-- Tabella candidature
CREATE TABLE candidature (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    messaggio TEXT NOT NULL,
    cv_allegato VARCHAR(255),
    portfolio_url VARCHAR(255),
    stato ENUM('INVIATA', 'IN_VALUTAZIONE', 'ACCETTATA', 'RIFIUTATA') DEFAULT 'INVIATA',
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_valutazione TIMESTAMP NULL,
    note_valutazione TEXT,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_candidatura (progetto_id, utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_stato (stato),
    INDEX idx_data (data_candidatura)
) ENGINE=InnoDB;

-- Tabella componenti_hardware (per progetti hardware)
CREATE TABLE componenti_hardware (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT,
    prezzo DECIMAL(10,2) NOT NULL,
    quantita INT NOT NULL DEFAULT 1,
    fornitore VARCHAR(200),
    link_acquisto VARCHAR(500),
    data_aggiunta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_nome (nome)
) ENGINE=InnoDB;

-- Tabella profili_richiesti (per progetti software)
CREATE TABLE profili_richiesti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT,
    numero_posizioni INT DEFAULT 1,
    posizioni_occupate INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_nome (nome),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Tabella skill_profili (competenze richieste per profili software)
CREATE TABLE skill_profili (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profilo_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello_richiesto INT NOT NULL CHECK (livello_richiesto BETWEEN 0 AND 5),
    obbligatoria BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (profilo_id) REFERENCES profili_richiesti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_profilo_skill (profilo_id, competenza_id),
    INDEX idx_profilo (profilo_id),
    INDEX idx_competenza (competenza_id)
) ENGINE=InnoDB;

-- Tabella team_membri (membri del team progetti)
CREATE TABLE team_membri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    ruolo VARCHAR(100) NOT NULL,
    descrizione_ruolo TEXT,
    data_ingresso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_uscita TIMESTAMP NULL,
    attivo BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_membro_attivo (progetto_id, utente_id, attivo),
    INDEX idx_progetto (progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_attivo (attivo)
) ENGINE=InnoDB;

-- Tabella foto_progetti
CREATE TABLE foto_progetti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    path_completo VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    descrizione TEXT,
    ordine_visualizzazione INT DEFAULT 0,
    principale BOOLEAN DEFAULT FALSE,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_ordine (ordine_visualizzazione),
    INDEX idx_principale (principale)
) ENGINE=InnoDB;

-- Tabella system_log
CREATE TABLE system_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT,
    azione VARCHAR(100) NOT NULL,
    tabella_interessata VARCHAR(50),
    record_id INT,
    dettagli JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE SET NULL,
    INDEX idx_utente (utente_id),
    INDEX idx_azione (azione),
    INDEX idx_tabella (tabella_interessata),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB;

-- =====================================================
-- VISTE STATISTICHE
-- =====================================================

-- Vista top creatori
CREATE VIEW top_creatori AS
SELECT 
    u.id,
    u.nickname,
    u.nome,
    u.cognome,
    u.affidabilita,
    u.nr_progetti,
    COUNT(p.id) as progetti_attivi,
    COALESCE(SUM(p.budget_raccolto), 0) as totale_raccolto,
    COALESCE(AVG(p.budget_raccolto / p.budget_richiesto * 100), 0) as percentuale_successo_media
FROM utenti u
LEFT JOIN progetti p ON u.id = p.creatore_id AND p.stato IN ('ATTIVO', 'COMPLETATO')
WHERE u.tipo_utente = 'CREATORE'
GROUP BY u.id, u.nickname, u.nome, u.cognome, u.affidabilita, u.nr_progetti
ORDER BY u.affidabilita DESC, totale_raccolto DESC;

-- Vista progetti quasi completati
CREATE VIEW progetti_quasi_completati AS
SELECT 
    p.id,
    p.titolo,
    p.creatore_id,
    u.nickname as creatore_nickname,
    p.budget_richiesto,
    p.budget_raccolto,
    ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale_completamento,
    p.data_fine,
    DATEDIFF(p.data_fine, CURDATE()) as giorni_rimanenti,
    p.nr_sostenitori
FROM progetti p
JOIN utenti u ON p.creatore_id = u.id
WHERE p.stato = 'ATTIVO'
  AND (p.budget_raccolto / p.budget_richiesto) >= 0.75
  AND p.data_fine >= CURDATE()
ORDER BY percentuale_completamento DESC, giorni_rimanenti ASC;

-- Vista top finanziatori
CREATE VIEW top_finanziatori AS
SELECT 
    u.id,
    u.nickname,
    u.nome,
    u.cognome,
    COUNT(f.id) as nr_finanziamenti,
    SUM(f.importo) as totale_finanziato,
    AVG(f.importo) as media_finanziamento,
    COUNT(DISTINCT f.progetto_id) as progetti_sostenuti
FROM utenti u
JOIN finanziamenti f ON u.id = f.utente_id
WHERE f.stato = 'COMPLETATO'
GROUP BY u.id, u.nickname, u.nome, u.cognome
HAVING totale_finanziato > 0
ORDER BY totale_finanziato DESC, nr_finanziamenti DESC;

-- =====================================================
-- FINE SCHEMA
-- =====================================================

SELECT 'Schema MySQL BOSTARTER creato con successo!' as messaggio;
