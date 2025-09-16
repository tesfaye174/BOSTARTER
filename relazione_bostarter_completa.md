# 📋 Progetto BOSTARTER - Piattaforma di Crowdfunding

**Corso di Basi di Dati - CdS Informatica per il Management**  
**A.A. 2024/2025**

**Studente:** [Nome Studente]  
**Matricola:** [Matricola]  
**Data:** 17 Settembre 2025

---

## 🎯 Indice

1. [Raccolta e Analisi dei Requisiti](#raccolta-requisiti)
2. [Progettazione Concettuale](#progettazione-concettuale)
3. [Progettazione Logica](#progettazione-logica)
4. [Normalizzazione](#normalizzazione)
5. [Funzionalità Applicazione Web](#funzionalita-web)
6. [Appendice: Codice SQL Completo](#appendice-sql)

---

## 1. 📋 Raccolta e Analisi dei Requisiti

### 1.1 Specifiche sui Dati

La piattaforma BOSTARTER gestisce i seguenti dati principali:

#### Entità Principali:
- **Utenti**: email (univoca), nickname, password, nome, cognome, anno nascita, luogo nascita
- **Competenze**: nome, descrizione, categoria
- **Skill Curriculum**: competenza + livello (0-5)
- **Progetti**: nome univoco, descrizione, data inserimento, budget, data limite, stato (aperto/chiuso)
- **Reward**: codice univoco, descrizione, foto
- **Finanziamenti**: importo, data
- **Commenti**: id univoco, data, testo
- **Candidature**: profilo richiesto, skill matching
- **Eventi**: log attività (MongoDB)

#### Relazioni:
- Utente → Progetti (1:N, creatore)
- Progetto → Reward (1:N)
- Utente → Finanziamenti (N:N, tramite progetto)
- Utente → Commenti (1:N, su progetto)
- Utente → Candidature (N:N, per profilo progetto)

### 1.2 Lista delle Operazioni

#### Operazioni Generali (Tutti gli Utenti):
1. Autenticazione/registrazione
2. Inserimento skill curriculum
3. Visualizzazione progetti disponibili
4. Finanziamento progetti
5. Scelta reward
6. Inserimento commenti
7. Inserimento candidature software

#### Operazioni Amministratori:
8. Inserimento competenze
9. Autenticazione con codice sicurezza

#### Operazioni Creatori:
10. Inserimento nuovi progetti
11. Inserimento reward
12. Risposta commenti
13. Inserimento profili software
14. Accettazione candidature

#### Operazioni Statistiche:
15. Top 3 creatori per affidabilità
16. Top 3 progetti vicini completamento
17. Top 3 finanziatori per importo

### 1.3 Tavola dei Volumi

| Operazione | Tipo | Frequenza |
|------------|------|-----------|
| Nuovo progetto | Interattiva | 1/mese |
| Visualizzazione progetti + finanziamenti | Batch | 1/mese |
| Conteggio progetti per utente | Batch | 3/mese |
| Finanziamenti | Interattiva | Alta |

**Parametri analisi ridondanza #nr_progetti:**
- wI = 1 (peso operazioni interattive)
- wB = 0.5 (peso operazioni batch)
- a = 2 (parametro algoritmico)

**Formula:** Costo = (wI × 1) + (wB × 3) + a = 1 + 1.5 + 2 = 4.5

### 1.4 Glossario dei Dati

| Termine | Descrizione |
|---------|-------------|
| **Progetto Hardware** | Progetto con lista componenti (nome, descrizione, prezzo, quantità) |
| **Progetto Software** | Progetto con profili richiesti (nome, skill richieste) |
| **Affidabilità** | % progetti creati che hanno ricevuto almeno un finanziamento |
| **Stato Progetto** | Aperto (raccolta fondi attiva) / Chiuso (completato o scaduto) |
| **Skill Matching** | Verifica livello utente ≥ livello richiesto per candidatura |
| **Reward** | Ricompensa non economica per finanziatori |
| **Evento** | Log attività salvato in MongoDB |

---

## 2. 🏗️ Progettazione Concettuale

### 2.1 Diagramma E-R

```
┌─────────────────┐       ┌─────────────────┐
│     UTENTE      │       │   COMPETENZA    │
├─────────────────┤       ├─────────────────┤
│ email (PK)      │◄────┐ │ nome (PK)       │
│ nickname        │     │ ├─────────────────┤
│ password        │     │ │ descrizione     │
│ nome            │     │ │ categoria       │
│ cognome         │     │ └─────────────────┘
│ tipo_utente     │     │          ▲
│ codice_sicurezza│     │          │
│ nr_progetti     │     │ ┌─────────────────┐
│ affidabilita    │     │ │   SKILL_USER    │
└─────────────────┘     │ ├─────────────────┤
          ▲             │ │ utente_email (FK)│
          │             │ │ competenza (FK)  │
          │             │ │ livello (0-5)    │
          │             └─────────────────┘
          │                      ▲
          │                      │
┌─────────────────┐       ┌─────────────────┐
│    PROGETTO     │       │   CATEGORIA     │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ progetto_id (FK)│
│ titolo          │       │ tipo (HW/SW)    │
│ descrizione     │       └─────────────────┘
│ creatore_email  │                ▲
│ budget_richiesto│                │
│ data_limite     │       ┌─────────────────┐
│ stato           │       │   COMPONENTE    │
└─────────────────┘       ├─────────────────┤
          ▲               │ id (PK)         │
          │               │ progetto_id (FK)│
          │               │ nome            │
          │               │ descrizione     │
          │               │ prezzo          │
          │               │ quantita        │
          └─────────────────┘

          │
          ▼
┌─────────────────┐       ┌─────────────────┐
│    REWARD       │       │  FINANZIAMENTO │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │◄────┐ │ id (PK)         │
│ progetto_id (FK)│     │ ├─────────────────┤
│ descrizione     │     │ │ utente_email (FK│
│ foto            │     │ │ progetto_id (FK)│
└─────────────────┘     │ │ importo         │
                        │ │ data            │
                        │ │ reward_id (FK)  │
                        └─────────────────┘

┌─────────────────┐       ┌─────────────────┐
│    COMMENTO     │       │   CANDIDATURA   │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ id (PK)         │
│ progetto_id (FK)│       │ utente_email (FK│
│ utente_email (FK│       │ profilo_id (FK) │
│ testo           │       │ stato           │
│ data            │       └─────────────────┘
│ risposta        │
└─────────────────┘
```

### 2.2 Dizionario delle Entità

#### UTENTE
- **email**: VARCHAR(255), PK, UNIQUE - Indirizzo email univoco
- **nickname**: VARCHAR(50) - Nome visualizzato
- **password_hash**: VARCHAR(255) - Password criptata (Argon2ID)
- **nome**: VARCHAR(100) - Nome proprio
- **cognome**: VARCHAR(100) - Cognome
- **tipo_utente**: ENUM('base', 'creatore', 'amministratore')
- **codice_sicurezza**: VARCHAR(20) - Solo per amministratori
- **nr_progetti**: INT - Ridondanza per creatori
- **affidabilita**: DECIMAL(5,2) - Percentuale progetti finanziati

#### PROGETTO
- **id**: INT, PK, AUTO_INCREMENT
- **titolo**: VARCHAR(255), UNIQUE
- **descrizione**: TEXT
- **creatore_id**: INT, FK → UTENTE(id)
- **budget_richiesto**: DECIMAL(10,2)
- **data_limite**: DATE
- **stato**: ENUM('aperto', 'chiuso')

#### COMPETENZA
- **nome**: VARCHAR(100), PK
- **descrizione**: TEXT
- **categoria**: VARCHAR(50)

### 2.3 Business Rules

1. **Progetto Chiusura**: Stato = 'chiuso' quando:
   - Budget raggiunto (somma finanziamenti ≥ budget)
   - Data corrente > data_limite

2. **Affidabilità**: % progetti creati con almeno 1 finanziamento

3. **Skill Matching**: Candidatura possibile solo se ∀ skill richieste:
   livello_utente ≥ livello_richiesto

4. **Tipi Utente**:
   - Base: operazioni standard
   - Creatore: + creazione progetti, risposte commenti
   - Amministratore: + gestione competenze

5. **Reward Association**: Ogni finanziamento collegato a una reward

6. **Commenti**: Max 1 risposta da creatore per commento

---

## 3. 🔧 Progettazione Logica

### 3.1 Schema Relazionale

#### Tabelle Principali:
```sql
utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    tipo_utente ENUM('base', 'creatore', 'amministratore') DEFAULT 'base',
    codice_sicurezza VARCHAR(20),
    nr_progetti INT DEFAULT 0,
    affidabilita DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)

progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(255) UNIQUE NOT NULL,
    descrizione TEXT,
    creatore_id INT NOT NULL,
    budget_richiesto DECIMAL(10,2) NOT NULL,
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id)
)

competenze (
    nome VARCHAR(100) PRIMARY KEY,
    descrizione TEXT,
    categoria VARCHAR(50)
)

skill_user (
    utente_id INT,
    competenza VARCHAR(100),
    livello INT CHECK (livello BETWEEN 0 AND 5),
    PRIMARY KEY (utente_id, competenza),
    FOREIGN KEY (utente_id) REFERENCES utenti(id),
    FOREIGN KEY (competenza) REFERENCES competenze(nome)
)
```

#### Tabelle Derivate:
```sql
reward (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    descrizione TEXT,
    foto VARCHAR(255),
    FOREIGN KEY (progetto_id) REFERENCES progetti(id)
)

finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    reward_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato_pagamento ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    FOREIGN KEY (utente_id) REFERENCES utenti(id),
    FOREIGN KEY (progetto_id) REFERENCES progetti(id),
    FOREIGN KEY (reward_id) REFERENCES reward(id)
)

commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    risposta TEXT,
    risposta_data TIMESTAMP NULL,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id),
    FOREIGN KEY (utente_id) REFERENCES utenti(id)
)
```

### 3.2 Vincoli di Integrità

#### Chiavi Primarie:
- utenti.id
- progetti.id
- competenze.nome
- skill_user.(utente_id, competenza)

#### Chiavi Estere:
- progetti.creatore_id → utenti.id
- skill_user.utente_id → utenti.id
- skill_user.competenza → competenze.nome
- reward.progetto_id → progetti.id
- finanziamenti.(utente_id, progetto_id, reward_id) → (utenti.id, progetti.id, reward.id)
- commenti.(progetto_id, utente_id) → (progetti.id, utenti.id)

#### Vincoli di Dominio:
- livello ∈ [0,5]
- tipo_utente ∈ {'base', 'creatore', 'amministratore'}
- stato ∈ {'aperto', 'chiuso'}
- stato_pagamento ∈ {'pending', 'completed', 'failed'}
- importo > 0
- budget_richiesto > 0

#### Vincoli di Integrità Business:
```sql
-- Solo amministratori possono avere codice_sicurezza
ALTER TABLE utenti ADD CONSTRAINT chk_amministratore
CHECK (tipo_utente = 'amministratore' OR codice_sicurezza IS NULL);

-- Solo creatori possono avere nr_progetti > 0
ALTER TABLE utenti ADD CONSTRAINT chk_creatore_progetti
CHECK (tipo_utente = 'creatore' OR nr_progetti = 0);

-- Data limite futura per progetti aperti
ALTER TABLE progetti ADD CONSTRAINT chk_data_limite
CHECK (stato = 'chiuso' OR data_limite >= CURRENT_DATE);
```

### 3.3 Analisi delle Ridondanze

#### Campo nr_progetti (Ridondanza Concettuale)
**Vantaggi:**
- Query efficienti per statistiche creatori
- Riduce JOIN per conteggi frequenti

**Svantaggi:**
- Inconsistenza potenziale
- Spazio storage aggiuntivo

**Analisi Costo-Beneficio:**
- **Costo Query senza ridondanza:** COUNT(*) su progetti per ogni richiesta
- **Costo manutenzione:** Trigger per aggiornamenti
- **Frequenza:** Alta per statistiche, bassa per modifiche

**Decisione:** Mantenere ridondanza per performance

---

## 4. 📊 Normalizzazione

### 4.1 Forma Normale 1FN
✅ **Soddisfatta**: Tutti gli attributi sono atomici

**Problemi risolti:**
- Skill curriculum: separata in tabella skill_user
- Componenti hardware: tabella dedicata
- Profili software: relazione separata

### 4.2 Forma Normale 2FN
✅ **Soddisfatta**: Nessuna dipendenza parziale

**Verifica:**
- utenti: PK={id}, tutte le dipendenze da PK completa
- progetti: PK={id}, tutte le dipendenze da PK completa
- skill_user: PK={utente_id, competenza}, dipendenze da PK completa

### 4.3 Forma Normale 3FN
✅ **Soddisfatta**: Nessuna dipendenza transitiva

**Verifica:**
- Nessun attributo non-chiave dipende da altro attributo non-chiave
- Tipo utente determina codice_sicurezza → corretto
- Stato progetto determina logica business → corretto

### 4.4 Forma Normale BCNF
✅ **Soddisfatta**: Ogni determinante è chiave candidata

**Verifica:**
- email → altri attributi utente (email è chiave)
- titolo → altri attributi progetto (titolo è chiave)
- nome competenza → descrizione (nome è chiave)

### 4.5 Analisi Finale

| Forma Normale | Stato | Motivazione |
|---------------|-------|-------------|
| 1FN | ✅ Soddisfatta | Attributi atomici |
| 2FN | ✅ Soddisfatta | No dipendenze parziali |
| 3FN | ✅ Soddisfatta | No dipendenze transitive |
| BCNF | ✅ Soddisfatta | Determinanti sono chiavi |

**Schema completamente normalizzato al livello BCNF**

---

## 5. 🌐 Funzionalità Applicazione Web

### 5.1 Architettura Tecnologica

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │    Backend      │    │   Database      │
│   (PHP + HTML)  │◄──►│   (PHP API)     │◄──►│   (MySQL)       │
│                 │    │                 │    │                 │
│ - Bootstrap CSS │    │ - REST API      │    │ - Stored Proc   │
│ - JavaScript    │    │ - JWT Auth      │    │ - Triggers      │
│ - Responsive    │    │ - CSRF Protect  │    │ - Views         │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │   MongoDB       │
                       │   (Logging)     │
                       └─────────────────┘
```

### 5.2 Moduli Principali

#### 5.2.1 Autenticazione e Sicurezza
```php
// backend/api/login.php - Autenticazione sicura
- Validazione input con filtri
- Hash password Argon2ID
- Rate limiting tentativi
- CSRF protection
- Session management sicura
- Logging attività
```

#### 5.2.2 Gestione Progetti
```php
// backend/api/project.php - CRUD Progetti
- Creazione progetti con validazione
- Upload immagini
- Gestione categorie (HW/SW)
- Calcolo progresso finanziamento
- Trigger automatici stato
```

#### 5.2.3 Sistema Finanziamenti
```php
// backend/api/finanziamenti.php
- Elaborazione pagamenti sicuri
- Associazione reward
- Calcolo affidabilità creatore
- Trigger aggiornamenti budget
```

#### 5.2.4 Sistema Commenti
```php
// backend/api/commenti.php
- Moderazione contenuti
- Risposte creatori
- Like/dislike system
- Notifiche real-time
```

### 5.3 Interfacce Utente

#### Dashboard Principale
```html
<!-- frontend/home.php -->
- Progetti in evidenza
- Statistiche piattaforma
- Top creatori affidabili
- Progetti vicini completamento
- Carousel immagini responsive
```

#### Creazione Progetto
```html
<!-- frontend/new.php -->
- Form multi-step
- Upload immagini drag&drop
- Editor WYSIWYG descrizione
- Gestione componenti/reward
- Validazione real-time
```

#### Profilo Utente
```html
<!-- frontend/dash.php -->
- Gestione skill curriculum
- Storico progetti
- Statistiche personali
- Impostazioni sicurezza
```

### 5.4 Sicurezza Implementata

#### Backend Security
```php
// backend/services/AuthService.php
- Password hashing Argon2ID
- JWT token generation
- CSRF token validation
- Rate limiting
- Input sanitization
- SQL injection prevention
```

#### Frontend Security
```javascript
// frontend/assets/js/security.js
- XSS prevention
- CSRF token handling
- Secure AJAX requests
- Form validation
- Session timeout handling
```

### 5.5 Performance e Scalabilità

#### Ottimizzazioni Database
```sql
-- Indici strategici
CREATE INDEX idx_progetti_stato_data ON progetti(stato, data_limite);
CREATE INDEX idx_finanziamenti_progetto ON finanziamenti(progetto_id);
CREATE INDEX idx_commenti_progetto ON commenti(progetto_id);

-- Query ottimizzate con prepared statements
-- Connection pooling
-- Caching risultati frequenti
```

#### Frontend Performance
```html
<!-- Lazy loading immagini -->
<!-- Minification CSS/JS -->
<!-- CDN Bootstrap -->
<!-- Compression GZIP -->
<!-- Browser caching -->
```

### 5.6 API REST Endpoints

| Endpoint | Metodo | Descrizione | Autenticazione |
|----------|--------|-------------|----------------|
| `/api/login` | POST | Autenticazione utente | No |
| `/api/progetti` | GET | Lista progetti | Optional |
| `/api/progetti` | POST | Crea progetto | Sì (Creatore) |
| `/api/finanziamenti` | POST | Nuovo finanziamento | Sì |
| `/api/commenti` | POST | Nuovo commento | Sì |
| `/api/candidature` | POST | Candidatura profilo | Sì |
| `/api/statistiche` | GET | Statistiche piattaforma | No |

---

## 6. 📊 Demo e Presentazione

### 6.1 Scenari di Test

#### Scenario 1: Registrazione e Creazione Progetto
1. Utente si registra come creatore
2. Aggiunge skill curriculum
3. Crea progetto hardware/software
4. Definisce reward e componenti

#### Scenario 2: Finanziamento Progetto
1. Utente visualizza progetti aperti
2. Seleziona progetto interessante
3. Sceglie reward e importa
4. Completa pagamento simulato

#### Scenario 3: Sistema Commenti
1. Utente lascia commento
2. Creatore risponde
3. Sistema like/dislike
4. Moderazione contenuti

### 6.2 Statistiche Piattaforma

#### Metriche Implementate
- Totale utenti registrati
- Progetti attivi/chiusi
- Volume finanziamenti
- Affidabilità media creatori
- Tasso successo progetti

#### Dashboard Analytics
```php
// Visualizzazioni real-time
- Grafici andamento finanziamenti
- Top categorie progetti
- Distribuzione geografica utenti
- Trend temporali attività
```

---

# Appendice: Codice SQL Completo

## A.1 Schema Database

```sql
-- Schema completo BOSTARTER
-- Versione: 1.0
-- Data: 2025-09-17

-- =============================================
-- TABELLA: utenti
-- =============================================
CREATE TABLE utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    tipo_utente ENUM('base', 'creatore', 'amministratore') DEFAULT 'base',
    codice_sicurezza VARCHAR(20),
    nr_progetti INT DEFAULT 0,
    affidabilita DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Vincoli
    CONSTRAINT chk_amministratore CHECK (
        tipo_utente = 'amministratore' OR codice_sicurezza IS NULL
    ),
    CONSTRAINT chk_creatore_progetti CHECK (
        tipo_utente = 'creatore' OR nr_progetti = 0
    ),
    CONSTRAINT chk_affidabilita CHECK (
        affidabilita BETWEEN 0 AND 100
    )
);

-- =============================================
-- TABELLA: competenze
-- =============================================
CREATE TABLE competenze (
    nome VARCHAR(100) PRIMARY KEY,
    descrizione TEXT,
    categoria VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABELLA: skill_user
-- =============================================
CREATE TABLE skill_user (
    utente_id INT NOT NULL,
    competenza VARCHAR(100) NOT NULL,
    livello INT NOT NULL CHECK (livello BETWEEN 0 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (utente_id, competenza),
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza) REFERENCES competenze(nome) ON DELETE CASCADE
);

-- =============================================
-- TABELLA: progetti
-- =============================================
CREATE TABLE progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(255) UNIQUE NOT NULL,
    descrizione TEXT,
    creatore_id INT NOT NULL,
    budget_richiesto DECIMAL(10,2) NOT NULL,
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    categoria ENUM('hardware', 'software') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Vincoli
    CONSTRAINT chk_budget_positivo CHECK (budget_richiesto > 0),
    CONSTRAINT chk_data_limite_futura CHECK (
        stato = 'chiuso' OR data_limite >= CURRENT_DATE
    ),

    FOREIGN KEY (creatore_id) REFERENCES utenti(id)
);

-- =============================================
-- TABELLA: componenti_hw
-- =============================================
CREATE TABLE componenti_hw (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descrizione TEXT,
    prezzo DECIMAL(10,2) NOT NULL,
    quantita INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Vincoli
    CONSTRAINT chk_prezzo_positivo CHECK (prezzo > 0),
    CONSTRAINT chk_quantita_positiva CHECK (quantita > 0),

    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE
);

-- =============================================
-- TABELLA: profili_sw
-- =============================================
CREATE TABLE profili_sw (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descrizione TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE
);

-- =============================================
-- TABELLA: skill_profilo
-- =============================================
CREATE TABLE skill_profilo (
    profilo_id INT NOT NULL,
    competenza VARCHAR(100) NOT NULL,
    livello_richiesto INT NOT NULL CHECK (livello_richiesto BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (profilo_id, competenza),
    FOREIGN KEY (profilo_id) REFERENCES profili_sw(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza) REFERENCES competenze(nome)
);

-- =============================================
-- TABELLA: reward
-- =============================================
CREATE TABLE reward (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    descrizione TEXT NOT NULL,
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE
);

-- =============================================
-- TABELLA: finanziamenti
-- =============================================
CREATE TABLE finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    reward_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stato_pagamento ENUM('pending', 'completed', 'failed') DEFAULT 'pending',

    -- Vincoli
    CONSTRAINT chk_importo_positivo CHECK (importo > 0),

    FOREIGN KEY (utente_id) REFERENCES utenti(id),
    FOREIGN KEY (progetto_id) REFERENCES progetti(id),
    FOREIGN KEY (reward_id) REFERENCES reward(id)
);

-- =============================================
-- TABELLA: commenti
-- =============================================
CREATE TABLE commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    utente_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    risposta TEXT,
    risposta_data TIMESTAMP NULL,

    FOREIGN KEY (progetto_id) REFERENCES progetti(id),
    FOREIGN KEY (utente_id) REFERENCES utenti(id)
);

-- =============================================
-- TABELLA: candidature
-- =============================================
CREATE TABLE candidature (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    profilo_id INT NOT NULL,
    stato ENUM('in_attesa', 'accettata', 'rifiutata') DEFAULT 'in_attesa',
    motivazione TEXT,
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (utente_id) REFERENCES utenti(id),
    FOREIGN KEY (profilo_id) REFERENCES profili_sw(id)
);

-- =============================================
-- TABELLA: like_commenti
-- =============================================
CREATE TABLE like_commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commento_id INT NOT NULL,
    utente_id INT NOT NULL,
    tipo ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_like (commento_id, utente_id),
    FOREIGN KEY (commento_id) REFERENCES commenti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id)
);

-- =============================================
-- INDICI OTTIMIZZAZIONE
-- =============================================
CREATE INDEX idx_progetti_stato_data ON progetti(stato, data_limite);
CREATE INDEX idx_progetti_creatore ON progetti(creatore_id);
CREATE INDEX idx_finanziamenti_progetto ON finanziamenti(progetto_id);
CREATE INDEX idx_finanziamenti_utente ON finanziamenti(utente_id);
CREATE INDEX idx_commenti_progetto ON commenti(progetto_id);
CREATE INDEX idx_skill_user_competenza ON skill_user(competenza);
CREATE INDEX idx_candidature_profilo ON candidature(profilo_id);
```

## A.2 Stored Procedures

```sql
DELIMITER //

-- =============================================
-- AUTENTICAZIONE E REGISTRAZIONE
-- =============================================

CREATE PROCEDURE autentica_utente(
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255)
)
BEGIN
    DECLARE user_id INT;
    DECLARE stored_hash VARCHAR(255);
    DECLARE user_status ENUM('attivo', 'sospeso', 'bannato');

    -- Recupera dati utente
    SELECT id, password_hash, status INTO user_id, stored_hash, user_status
    FROM utenti
    WHERE email = p_email;

    -- Verifica esistenza utente
    IF user_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Email o password non validi';
    END IF;

    -- Verifica stato utente
    IF user_status != 'attivo' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Account non attivo';
    END IF;

    -- Verifica password
    IF NOT CHECK_PASSWORD(p_password, stored_hash) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Email o password non validi';
    END IF;

    -- Aggiorna ultimo accesso
    UPDATE utenti SET ultimo_accesso = NOW() WHERE id = user_id;

    -- Restituisci dati utente
    SELECT
        id, email, nickname, nome, cognome,
        tipo_utente, avatar, created_at
    FROM utenti
    WHERE id = user_id;
END //

CREATE PROCEDURE registra_utente(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100)
)
BEGIN
    DECLARE hashed_password VARCHAR(255);

    -- Genera hash password
    SET hashed_password = GENERATE_PASSWORD_HASH(p_password);

    -- Inserisci nuovo utente
    INSERT INTO utenti (
        email, nickname, password_hash,
        nome, cognome, tipo_utente
    ) VALUES (
        p_email, p_nickname, hashed_password,
        p_nome, p_cognome, 'base'
    );
END //

-- =============================================
-- GESTIONE PROGETTI
-- =============================================

CREATE PROCEDURE crea_progetto(
    IN p_creatore_id INT,
    IN p_titolo VARCHAR(255),
    IN p_descrizione TEXT,
    IN p_budget DECIMAL(10,2),
    IN p_data_limite DATE,
    IN p_categoria ENUM('hardware', 'software')
)
BEGIN
    DECLARE nuovo_progetto_id INT;

    -- Inserisci progetto
    INSERT INTO progetti (
        titolo, descrizione, creatore_id,
        budget_richiesto, data_limite, categoria
    ) VALUES (
        p_titolo, p_descrizione, p_creatore_id,
        p_budget, p_data_limite, p_categoria
    );

    SET nuovo_progetto_id = LAST_INSERT_ID();

    -- Aggiorna contatore progetti creatore
    UPDATE utenti
    SET nr_progetti = nr_progetti + 1
    WHERE id = p_creatore_id;

    -- Restituisci ID progetto creato
    SELECT nuovo_progetto_id AS progetto_id;
END //

CREATE PROCEDURE get_progetti_aperti()
BEGIN
    SELECT
        p.id,
        p.titolo,
        p.descrizione,
        p.budget_richiesto,
        COALESCE(SUM(f.importo), 0) AS budget_raccolto,
        ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) AS percentuale,
        DATEDIFF(p.data_limite, CURDATE()) AS giorni_rimanenti,
        u.nickname AS creatore,
        p.created_at
    FROM progetti p
    LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completed'
    LEFT JOIN utenti u ON p.creatore_id = u.id
    WHERE p.stato = 'aperto' AND p.data_limite >= CURDATE()
    GROUP BY p.id, p.titolo, p.descrizione, p.budget_richiesto,
             p.data_limite, u.nickname, p.created_at
    ORDER BY p.created_at DESC;
END //

-- =============================================
-- GESTIONE FINANZIAMENTI
-- =============================================

CREATE PROCEDURE finanzia_progetto(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_reward_id INT,
    IN p_importo DECIMAL(10,2)
)
BEGIN
    DECLARE budget_richiesto DECIMAL(10,2);
    DECLARE budget_attuale DECIMAL(10,2);
    DECLARE progetto_stato ENUM('aperto', 'chiuso');

    -- Verifica progetto esistente e aperto
    SELECT budget_richiesto, stato INTO budget_richiesto, progetto_stato
    FROM progetti
    WHERE id = p_progetto_id;

    IF progetto_stato = 'chiuso' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Progetto già chiuso';
    END IF;

    -- Inserisci finanziamento
    INSERT INTO finanziamenti (
        utente_id, progetto_id, reward_id, importo
    ) VALUES (
        p_utente_id, p_progetto_id, p_reward_id, p_importo
    );

    -- Calcola nuovo budget
    SELECT COALESCE(SUM(importo), 0) INTO budget_attuale
    FROM finanziamenti
    WHERE progetto_id = p_progetto_id AND stato_pagamento = 'completed';

    -- Se budget raggiunto, chiudi progetto
    IF budget_attuale >= budget_richiesto THEN
        UPDATE progetti SET stato = 'chiuso' WHERE id = p_progetto_id;
    END IF;
END //

-- =============================================
-- GESTIONE COMMENTI
-- =============================================

CREATE PROCEDURE inserisci_commento(
    IN p_progetto_id INT,
    IN p_utente_id INT,
    IN p_testo TEXT
)
BEGIN
    INSERT INTO commenti (
        progetto_id, utente_id, testo
    ) VALUES (
        p_progetto_id, p_utente_id, p_testo
    );
END //

CREATE PROCEDURE inserisci_risposta_commento(
    IN p_commento_id INT,
    IN p_risposta TEXT
)
BEGIN
    UPDATE commenti
    SET risposta = p_risposta,
        risposta_data = NOW()
    WHERE id = p_commento_id;
END //

-- =============================================
-- GESTIONE CANDIDATURE
-- =============================================

CREATE PROCEDURE verifica_skill_candidatura(
    IN p_utente_id INT,
    IN p_profilo_id INT
)
BEGIN
    DECLARE skill_ok BOOLEAN DEFAULT TRUE;
    DECLARE skill_count INT DEFAULT 0;
    DECLARE skill_match INT DEFAULT 0;

    -- Conta skill richieste
    SELECT COUNT(*) INTO skill_count
    FROM skill_profilo
    WHERE profilo_id = p_profilo_id;

    -- Conta skill matching
    SELECT COUNT(*) INTO skill_match
    FROM skill_profilo sp
    JOIN skill_user su ON sp.competenza = su.competenza
    WHERE sp.profilo_id = p_profilo_id
      AND su.utente_id = p_utente_id
      AND su.livello >= sp.livello_richiesto;

    -- Verifica matching completo
    IF skill_match < skill_count THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Skill insufficienti per candidatura';
    END IF;
END //

CREATE PROCEDURE candida_profilo(
    IN p_utente_id INT,
    IN p_profilo_id INT,
    IN p_motivazione TEXT
)
BEGIN
    -- Verifica skill
    CALL verifica_skill_candidatura(p_utente_id, p_profilo_id);

    -- Inserisci candidatura
    INSERT INTO candidature (
        utente_id, profilo_id, motivazione
    ) VALUES (
        p_utente_id, p_profilo_id, p_motivazione
    );
END //

-- =============================================
-- STATISTICHE
-- =============================================

CREATE PROCEDURE get_top_creatori_affidabilita()
BEGIN
    SELECT
        u.id,
        u.nickname,
        u.nome,
        u.cognome,
        u.affidabilita,
        u.nr_progetti,
        COUNT(DISTINCT CASE WHEN p.stato = 'chiuso' THEN p.id END) AS progetti_completati
    FROM utenti u
    LEFT JOIN progetti p ON u.id = p.creatore_id
    WHERE u.tipo_utente = 'creatore' AND u.affidabilita > 0
    GROUP BY u.id, u.nickname, u.nome, u.cognome, u.affidabilita, u.nr_progetti
    ORDER BY u.affidabilita DESC, progetti_completati DESC
    LIMIT 3;
END //

CREATE PROCEDURE get_progetti_vicini_completamento()
BEGIN
    SELECT
        p.id,
        p.titolo,
        p.descrizione,
        p.budget_richiesto,
        COALESCE(SUM(f.importo), 0) AS budget_raccolto,
        ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) AS percentuale,
        DATEDIFF(p.data_limite, CURDATE()) AS giorni_rimanenti,
        u.nickname AS creatore
    FROM progetti p
    LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completed'
    LEFT JOIN utenti u ON p.creatore_id = u.id
    WHERE p.stato = 'aperto' AND p.data_limite > CURDATE()
    GROUP BY p.id, p.titolo, p.descrizione, p.budget_richiesto, p.data_limite, u.nickname
    HAVING budget_raccolto > 0 AND percentuale >= 50
    ORDER BY percentuale DESC
    LIMIT 3;
END //

CREATE PROCEDURE get_top_finanziatori()
BEGIN
    SELECT
        u.id,
        u.nickname,
        u.nome,
        u.cognome,
        COUNT(f.id) AS numero_finanziamenti,
        SUM(f.importo) AS totale_finanziato,
        AVG(f.importo) AS importo_medio,
        MAX(f.data_finanziamento) AS ultimo_finanziamento
    FROM utenti u
    JOIN finanziamenti f ON u.id = f.utente_id
    WHERE f.stato_pagamento = 'completed'
    GROUP BY u.id, u.nickname, u.nome, u.cognome
    ORDER BY totale_finanziato DESC
    LIMIT 3;
END //

DELIMITER ;
```

## A.3 Trigger

```sql
DELIMITER //

-- =============================================
-- TRIGGER AFFIDABILITA'
-- =============================================

CREATE TRIGGER aggiorna_affidabilita_creatore
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE progetti_totali INT DEFAULT 0;
    DECLARE progetti_finanziati INT DEFAULT 0;
    DECLARE creatore_id INT;

    -- Trova creatore del progetto
    SELECT creatore_id INTO creatore_id
    FROM progetti
    WHERE id = NEW.progetto_id;

    -- Conta progetti totali creatore
    SELECT COUNT(*) INTO progetti_totali
    FROM progetti
    WHERE creatore_id = creatore_id;

    -- Conta progetti con almeno un finanziamento
    SELECT COUNT(DISTINCT p.id) INTO progetti_finanziati
    FROM progetti p
    JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE p.creatore_id = creatore_id AND f.stato_pagamento = 'completed';

    -- Calcola affidabilità
    IF progetti_totali > 0 THEN
        UPDATE utenti
        SET affidabilita = ROUND((progetti_finanziati / progetti_totali) * 100, 2)
        WHERE id = creatore_id;
    END IF;
END //

-- =============================================
-- TRIGGER STATO PROGETTO
-- =============================================

CREATE TRIGGER chiudi_progetto_budget_raggiunto
AFTER UPDATE ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE budget_richiesto DECIMAL(10,2);
    DECLARE budget_attuale DECIMAL(10,2);

    -- Solo se pagamento completato
    IF NEW.stato_pagamento = 'completed' AND OLD.stato_pagamento != 'completed' THEN
        -- Calcola budget attuale
        SELECT
            p.budget_richiesto,
            COALESCE(SUM(f.importo), 0)
        INTO budget_richiesto, budget_attuale
        FROM progetti p
        LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completed'
        WHERE p.id = NEW.progetto_id
        GROUP BY p.id, p.budget_richiesto;

        -- Chiudi progetto se budget raggiunto
        IF budget_attuale >= budget_richiesto THEN
            UPDATE progetti SET stato = 'chiuso' WHERE id = NEW.progetto_id;
        END IF;
    END IF;
END //

-- =============================================
-- TRIGGER CONTEGGIO PROGETTI
-- =============================================

CREATE TRIGGER incrementa_nr_progetti
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti
    SET nr_progetti = nr_progetti + 1
    WHERE id = NEW.creatore_id;
END //

-- =============================================
-- TRIGGER CHIUSURA PROGETTI SCADUTI
-- =============================================

CREATE TRIGGER chiudi_progetti_scaduti
AFTER UPDATE ON progetti
FOR EACH ROW
BEGIN
    -- Se data limite passata e progetto ancora aperto
    IF NEW.data_limite < CURDATE() AND NEW.stato = 'aperto' THEN
        UPDATE progetti SET stato = 'chiuso' WHERE id = NEW.id;
    END IF;
END //

DELIMITER ;
```

## A.4 Viste Statistiche

```sql
-- =============================================
-- VISTA: Statistiche Generali
-- =============================================
CREATE VIEW statistiche_generali AS
SELECT
    (SELECT COUNT(*) FROM utenti WHERE status = 'attivo') AS totale_utenti,
    (SELECT COUNT(*) FROM utenti WHERE tipo_utente = 'creatore' AND status = 'attivo') AS totale_creatori,
    (SELECT COUNT(*) FROM progetti) AS totale_progetti,
    (SELECT COUNT(*) FROM progetti WHERE stato = 'aperto') AS progetti_aperti,
    (SELECT COUNT(*) FROM progetti WHERE stato = 'chiuso') AS progetti_chiusi,
    (SELECT COUNT(*) FROM commenti) AS totale_commenti,
    (SELECT COUNT(*) FROM candidature WHERE stato = 'accettata') AS candidature_accettate,
    (SELECT COALESCE(SUM(importo), 0) FROM finanziamenti WHERE stato_pagamento = 'completed') AS totale_finanziato,
    (SELECT AVG(affidabilita) FROM utenti WHERE tipo_utente = 'creatore' AND affidabilita > 0) AS affidabilita_media
FROM dual;

-- =============================================
-- VISTA: Top Creatori per Affidabilità
-- =============================================
CREATE VIEW top_creatori_affidabilita AS
SELECT
    u.id,
    u.nickname,
    u.nome,
    u.cognome,
    u.affidabilita,
    u.nr_progetti AS progetti_creati,
    COUNT(DISTINCT CASE WHEN p.stato = 'chiuso' THEN p.id END) AS progetti_completati
FROM utenti u
LEFT JOIN progetti p ON u.id = p.creatore_id
WHERE u.tipo_utente = 'creatore' AND u.status = 'attivo' AND u.affidabilita > 0
GROUP BY u.id, u.nickname, u.nome, u.cognome, u.affidabilita, u.nr_progetti
ORDER BY u.affidabilita DESC, progetti_completati DESC
LIMIT 3;

-- =============================================
-- VISTA: Progetti Vicini Completamento
-- =============================================
CREATE VIEW progetti_vicini_completamento AS
SELECT
    p.id,
    p.titolo,
    p.descrizione,
    p.budget_richiesto,
    COALESCE(SUM(f.importo), 0) AS budget_raccolto,
    ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) AS percentuale_completamento,
    DATEDIFF(p.data_limite, CURDATE()) AS giorni_rimanenti,
    p.data_limite,
    u.nickname AS creatore
FROM progetti p
LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completed'
LEFT JOIN utenti u ON p.creatore_id = u.id
WHERE p.stato = 'aperto' AND p.data_limite > CURDATE()
GROUP BY p.id, p.titolo, p.descrizione, p.budget_richiesto, p.data_limite, u.nickname
HAVING budget_raccolto > 0 AND percentuale_completamento >= 50
ORDER BY percentuale_completamento DESC
LIMIT 3;

-- =============================================
-- VISTA: Top Finanziatori
-- =============================================
CREATE VIEW top_finanziatori AS
SELECT
    u.id,
    u.nickname,
    u.nome,
    u.cognome,
    COUNT(f.id) AS numero_finanziamenti,
    SUM(f.importo) AS totale_finanziato,
    AVG(f.importo) AS importo_medio,
    MAX(f.data_finanziamento) AS ultimo_finanziamento
FROM utenti u
JOIN finanziamenti f ON u.id = f.utente_id
WHERE f.stato_pagamento = 'completed'
GROUP BY u.id, u.nickname, u.nome, u.cognome
ORDER BY totale_finanziato DESC
LIMIT 3;

-- =============================================
-- VISTA: Dashboard Progetti Recenti
-- =============================================
CREATE VIEW progetti_recenti AS
SELECT
    p.id,
    p.titolo,
    LEFT(p.descrizione, 200) AS descrizione_breve,
    p.budget_richiesto,
    COALESCE(SUM(f.importo), 0) AS budget_raccolto,
    ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) AS percentuale,
    p.data_limite,
    p.stato,
    u.nickname AS creatore,
    p.created_at
FROM progetti p
LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completed'
LEFT JOIN utenti u ON p.creatore_id = u.id
GROUP BY p.id, p.titolo, p.descrizione, p.budget_richiesto, p.data_limite, p.stato, u.nickname, p.created_at
ORDER BY p.created_at DESC
LIMIT 10;
```

## A.5 Dati di Esempio

```sql
-- =============================================
-- DATI DI ESEMPIO PER TESTING
-- =============================================

-- Amministratore di default
INSERT INTO utenti (
    email, nickname, password_hash, nome, cognome,
    tipo_utente, codice_sicurezza, status
) VALUES (
    'admin@bostarter.it',
    'admin',
    PASSWORD('admin123'),
    'Amministratore',
    'Sistema',
    'amministratore',
    'ADMIN001',
    'attivo'
);

-- Competenze base
INSERT INTO competenze (nome, descrizione, categoria) VALUES
('PHP', 'Linguaggio di programmazione web lato server', 'Programmazione'),
('JavaScript', 'Linguaggio di programmazione web lato client', 'Programmazione'),
('Python', 'Linguaggio di programmazione generale', 'Programmazione'),
('HTML/CSS', 'Linguaggi per markup e styling web', 'Web Development'),
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
('Marketing Digitale', 'Strategie di marketing online', 'Marketing');

-- Utenti di test
INSERT INTO utenti (
    email, nickname, password_hash, nome, cognome, tipo_utente, status
) VALUES
('mario.rossi@email.com', 'mario_dev', PASSWORD('password123'), 'Mario', 'Rossi', 'creatore', 'attivo'),
('giulia.verdi@email.com', 'giulia_design', PASSWORD('password123'), 'Giulia', 'Verdi', 'creatore', 'attivo'),
('luca.bianchi@email.com', 'luca_finance', PASSWORD('password123'), 'Luca', 'Bianchi', 'base', 'attivo'),
('anna.neri@email.com', 'anna_test', PASSWORD('password123'), 'Anna', 'Neri', 'base', 'attivo');

-- Skill per utenti
INSERT INTO skill_user (utente_id, competenza, livello) VALUES
(2, 'PHP', 4),
(2, 'JavaScript', 3),
(2, 'MySQL', 4),
(3, 'UI/UX Design', 5),
(3, 'HTML/CSS', 4),
(3, 'React', 3);

-- Progetto di esempio
INSERT INTO progetti (
    titolo, descrizione, creatore_id, budget_richiesto, data_limite, categoria
) VALUES (
    'Smart Home Controller',
    'Un controller intelligente per la domotica basato su Raspberry Pi',
    2,
    5000.00,
    '2025-12-31',
    'hardware'
);

-- Reward per il progetto
INSERT INTO reward (progetto_id, descrizione) VALUES
(1, 'Controller base con manuale utente'),
(1, 'Controller premium + supporto tecnico 6 mesi'),
(1, 'Controller deluxe + workshop personalizzato');

-- Componenti hardware
INSERT INTO componenti_hw (progetto_id, nome, descrizione, prezzo, quantita) VALUES
(1, 'Raspberry Pi 4', 'Computer a scheda singola', 35.00, 1),
(1, 'Sensori di temperatura', 'Sensori digitali DS18B20', 5.00, 3),
(1, 'Relè module', 'Modulo relè 4 canali', 15.00, 2),
(1, 'Case protettiva', 'Custodia ABS con dissipatore', 10.00, 1);

COMMIT;
```

---

## 📞 Contatti e Riferimenti

**Progetto:** BOSTARTER - Piattaforma Crowdfunding  
**Tecnologie:** PHP, MySQL, Bootstrap, JavaScript  
**Repository:** [Link repository]  
**Documentazione:** [Link documentazione completa]

---

**Fine Documento**  
*Generato automaticamente dal sistema di documentazione BOSTARTER*
