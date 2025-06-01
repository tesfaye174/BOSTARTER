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

-- Tabella per i token "ricordami" per la persistenza del login
CREATE TABLE remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_utente_id (utente_id)
);

-- Tabella competenze utente (relazione many-to-many) 
CREATE TABLE utenti_competenze ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    utente_id INT NOT NULL, 
    competenza_id INT NOT NULL, 
    livello ENUM('base', 'intermedio', 'avanzato', 'esperto') DEFAULT 'base', 
    acquisita_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE, 
    UNIQUE KEY unique_utente_competenza (utente_id, competenza_id),
    INDEX idx_utente (utente_id),
    INDEX idx_competenza (competenza_id),
    INDEX idx_livello (livello)
); 

-- Tabella categorie progetti 
CREATE TABLE categorie_progetti ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    nome VARCHAR(100) NOT NULL UNIQUE, 
    descrizione TEXT, 
    icona VARCHAR(100), 
    colore VARCHAR(7), -- Hex color code 
    attiva BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attiva (attiva)
); 

-- Tabella progetti 
CREATE TABLE progetti ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    nome VARCHAR(200) NOT NULL, 
    descrizione TEXT NOT NULL, 
    creatore_id INT NOT NULL, 
    categoria_id INT NOT NULL, 
    budget_richiesto DECIMAL(10,2) NOT NULL, 
    budget_raccolto DECIMAL(10,2) DEFAULT 0.00, 
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    data_lancio TIMESTAMP NULL, 
    data_scadenza TIMESTAMP NOT NULL, 
    stato ENUM('bozza', 'in_revisione', 'approvato', 'aperto', 'chiuso', 'completato', 'annullato') DEFAULT 'bozza', 
    immagine_principale VARCHAR(255), 
    video_presentazione VARCHAR(255), 
    storia_progetto TEXT, 
    obiettivi TEXT, 
    piano_utilizzo_fondi TEXT, 
    rischi_sfide TEXT, 
    aggiornamenti_previsti TEXT, 
    nr_sostenitori INT DEFAULT 0, 
    nr_condivisioni INT DEFAULT 0, 
    nr_visualizzazioni INT DEFAULT 0, 
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    FOREIGN KEY (categoria_id) REFERENCES categorie_progetti(id) ON DELETE RESTRICT, 
    INDEX idx_creatore (creatore_id), 
    INDEX idx_categoria (categoria_id), 
    INDEX idx_stato (stato),
    INDEX idx_data_scadenza (data_scadenza),
    INDEX idx_budget_raccolto (budget_raccolto),
    INDEX idx_stato_scadenza (stato, data_scadenza),
    INDEX idx_categoria_stato (categoria_id, stato)
); 

-- Tabella competenze richieste per progetti 
CREATE TABLE progetti_competenze_richieste ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    competenza_id INT NOT NULL, 
    livello_richiesto ENUM('base', 'intermedio', 'avanzato', 'esperto') DEFAULT 'base', 
    priorita ENUM('bassa', 'media', 'alta', 'critica') DEFAULT 'media', 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE, 
    UNIQUE KEY unique_progetto_competenza (progetto_id, competenza_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_competenza (competenza_id),
    INDEX idx_priorita (priorita)
); 

-- Tabella immagini progetto 
CREATE TABLE immagini_progetto ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    percorso_file VARCHAR(255) NOT NULL, 
    nome_originale VARCHAR(255), 
    descrizione TEXT, 
    ordine_visualizzazione INT DEFAULT 0, 
    tipo ENUM('gallery', 'documento', 'prototipo') DEFAULT 'gallery', 
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    INDEX idx_progetto (progetto_id), 
    INDEX idx_tipo (tipo),
    INDEX idx_ordine (ordine_visualizzazione)
); 

-- Tabella reward 
CREATE TABLE reward ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    progetto_id INT NOT NULL, 
    titolo VARCHAR(200) NOT NULL, 
    descrizione TEXT NOT NULL, 
    importo_minimo DECIMAL(10,2) NOT NULL, 
    numero_massimo INT NULL, -- NULL = illimitato 
    numero_selezionato INT DEFAULT 0, 
    data_consegna_stimata DATE, 
    spese_spedizione DECIMAL(8,2) DEFAULT 0.00, 
    disponibile BOOLEAN DEFAULT TRUE, 
    ordine_visualizzazione INT DEFAULT 0, 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    INDEX idx_progetto (progetto_id), 
    INDEX idx_importo (importo_minimo),
    INDEX idx_disponibile (disponibile),
    INDEX idx_ordine (ordine_visualizzazione)
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
    INDEX idx_visibile (visibile)
); 

-- Tabella notifiche 
CREATE TABLE notifiche ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    utente_id INT NOT NULL, 
    tipo VARCHAR(50) NOT NULL, 
    messaggio TEXT NOT NULL, 
    link VARCHAR(255), 
    letta BOOLEAN DEFAULT FALSE, 
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    data_lettura TIMESTAMP NULL, 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    INDEX idx_utente (utente_id), 
    INDEX idx_tipo (tipo),
    INDEX idx_letta (letta),
    INDEX idx_data_creazione (data_creazione),
    INDEX idx_utente_letta (utente_id, letta)
); 

-- Tabella seguiti (utenti che seguono progetti) 
CREATE TABLE seguiti ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    utente_id INT NOT NULL, 
    progetto_id INT NOT NULL, 
    data_inizio_follow TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    notifiche_attive BOOLEAN DEFAULT TRUE, 
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE, 
    UNIQUE KEY unique_utente_progetto (utente_id, progetto_id),
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_notifiche_attive (notifiche_attive)
); 

-- Tabella messaggi (sistema di messaggistica) 
CREATE TABLE messaggi ( 
    id INT PRIMARY KEY AUTO_INCREMENT, 
    mittente_id INT NOT NULL, 
    destinatario_id INT NOT NULL, 
    oggetto VARCHAR(200), 
    testo TEXT NOT NULL, 
    data_invio TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    data_lettura TIMESTAMP NULL, 
    eliminato_mittente BOOLEAN DEFAULT FALSE, 
    eliminato_destinatario BOOLEAN DEFAULT FALSE, 
    FOREIGN KEY (mittente_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    FOREIGN KEY (destinatario_id) REFERENCES utenti(id) ON DELETE CASCADE, 
    INDEX idx_mittente (mittente_id), 
    INDEX idx_destinatario (destinatario_id), 
    INDEX idx_data_invio (data_invio),
    INDEX idx_data_lettura (data_lettura),
    INDEX idx_destinatario_lettura (destinatario_id, data_lettura)
); 

-- Schema database completato per la piattaforma BOSTARTER
-- Questo schema include tutte le tabelle necessarie per gestire utenti, progetti, finanziamenti,
-- competenze, notifiche e altre funzionalità essenziali per una piattaforma di crowdfunding.
