-- Schema di base per il test BOSTARTER
USE bostarter;

-- Tabella utenti
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100),
    cognome VARCHAR(100),
    anno_nascita INT,
    luogo_nascita VARCHAR(100),
    tipo_utente ENUM('standard', 'creatore', 'admin') DEFAULT 'standard',
    affidabilita DECIMAL(3,2) DEFAULT 5.00,
    nr_progetti INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabella competenze
CREATE TABLE IF NOT EXISTS competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) UNIQUE NOT NULL,
    descrizione TEXT
);

-- Tabella progetti
CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    descrizione TEXT,
    budget_richiesto DECIMAL(10,2) NOT NULL,
    budget_raccolto DECIMAL(10,2) DEFAULT 0.00,
    data_limite DATE NOT NULL,
    creatore_id INT NOT NULL,
    tipo_progetto ENUM('software', 'hardware') NOT NULL,
    stato ENUM('aperto', 'chiuso', 'scaduto') DEFAULT 'aperto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creatore_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabella candidature/applicazioni
CREATE TABLE IF NOT EXISTS applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    stato ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    messaggio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (user_id, project_id)
);

-- Tabella notifiche
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    titolo VARCHAR(255) NOT NULL,
    messaggio TEXT,
    tipo ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Inserimenti di test
INSERT IGNORE INTO competenze (nome, descrizione) VALUES
('PHP', 'Linguaggio di programmazione PHP'),
('JavaScript', 'Linguaggio di programmazione JavaScript'),
('MySQL', 'Database relazionale MySQL'),
('HTML/CSS', 'Markup e styling web'),
('Python', 'Linguaggio di programmazione Python');

-- Utente di test (password: test123)
INSERT IGNORE INTO users (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) VALUES
('test@bostarter.local', 'testuser', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', 1990, 'TestCity', 'standard'),
('admin@bostarter.local', 'admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 1985, 'AdminCity', 'admin');

-- Progetti di test
INSERT IGNORE INTO projects (nome, descrizione, budget_richiesto, data_limite, creatore_id, tipo_progetto) VALUES
('Progetto Test Alpha', 'Questo è un progetto di test per verificare il sistema', 1000.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'software'),
('Progetto Test Beta', 'Altro progetto di test per il sistema BOSTARTER', 2000.00, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 2, 'hardware');
