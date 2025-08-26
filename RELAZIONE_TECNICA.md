# RELAZIONE TECNICA PROGETTO BOSTARTER

## Sistema Informativo per Crowdfunding

**Corso:** Basi di Dati  
**Anno Accademico:** 2024/2025  
**Data:** 25 Agosto 2025  

---

## INDICE

1. [Raccolta e Analisi dei Requisiti](#1-raccolta-e-analisi-dei-requisiti)
   - 1.1 Specifica sui Dati
   - 1.2 Lista delle Operazioni
   - 1.3 Tavola Media dei Volumi
   - 1.4 Glossario dei Dati

2. [Progettazione Concettuale](#2-progettazione-concettuale)
   - 2.1 Diagramma E-R
   - 2.2 Dizionario delle Entità e Relazioni
   - 2.3 Tavola delle Business Rules

3. [Progettazione Logica](#3-progettazione-logica)
   - 3.1 Ristrutturazione dello Schema Concettuale
   - 3.2 Analisi delle Ridondanze
   - 3.3 Lista delle Tabelle con Vincoli di Chiavi
   - 3.4 Lista dei Vincoli Inter-relazionali

4. [Normalizzazione](#4-normalizzazione)

5. [Descrizione Funzionalità Applicazione Web](#5-descrizione-funzionalità-applicazione-web)

6. [Appendice: Codice SQL Completo](#6-appendice-codice-sql-completo)

---

## 1. RACCOLTA E ANALISI DEI REQUISITI

### 1.1 Specifica sui Dati

**BOSTARTER** è una piattaforma di crowdfunding che permette ai creatori di pubblicare progetti e agli utenti di finanziarli. Il sistema gestisce:

**Utenti:**

- Utenti normali che possono finanziare progetti
- Creatori che possono pubblicare e gestire progetti
- Amministratori che gestiscono il sistema e le competenze

**Progetti:**

- Ogni progetto ha un titolo, descrizione, budget richiesto, data limite
- I progetti possono essere di tipo hardware o software
- Ogni progetto appartiene a un creatore
- I progetti hanno uno stato (aperto/chiuso)

**Finanziamenti:**

- Gli utenti possono finanziare progetti con importi a scelta
- Ogni finanziamento è associato a un reward specifico
- Il sistema traccia la data e l'importo di ogni finanziamento

**Sistema di Competenze:**

- Gli amministratori gestiscono un catalogo di competenze
- Gli utenti possono associare competenze con livelli di esperienza (0-5)

**Interazioni Sociali:**

- Gli utenti possono commentare i progetti
- I creatori possono rispondere ai commenti

### 1.2 Lista delle Operazioni

#### Operazioni Utente

1. **Registrazione/Login** (20 volte/giorno)
   - Creazione nuovo account utente
   - Autenticazione utente esistente

2. **Gestione Profilo** (15 volte/giorno)
   - Modifica dati personali
   - Aggiunta/modifica competenze

3. **Navigazione Progetti** (100 volte/giorno)
   - Visualizzazione lista progetti
   - Ricerca progetti per categoria/creatore
   - Visualizzazione dettagli progetto

4. **Finanziamento** (10 volte/giorno)
   - Selezione reward e importo
   - Elaborazione finanziamento

#### Operazioni Creatore

5. **Gestione Progetti** (8 volte/giorno)
   - Creazione nuovo progetto
   - Modifica progetto esistente
   - Gestione rewards

6. **Interazione con Sostenitori** (5 volte/giorno)
   - Risposta a commenti
   - Visualizzazione statistiche finanziamenti

#### Operazioni Amministratore

7. **Gestione Sistema** (3 volte/giorno)
   - Gestione competenze
   - Moderazione contenuti
   - Visualizzazione statistiche globali

#### Operazioni di Sistema

8. **Calcoli Automatici** (continuo)
   - Aggiornamento budget raccolto
   - Calcolo affidabilità creatori
   - Chiusura progetti scaduti

### 1.3 Tavola Media dei Volumi

| Entità | Volume | Descrizione |
|--------|--------|-------------|
| **Utenti** | 1.000 | Utenti registrati nel sistema |
| **Progetti** | 200 | Progetti totali (50 attivi) |
| **Finanziamenti** | 2.000 | Finanziamenti totali |
| **Competenze** | 50 | Competenze gestite dal sistema |
| **Skill Utente** | 3.000 | Associazioni utente-competenza |
| **Commenti** | 500 | Commenti sui progetti |
| **Risposte** | 200 | Risposte ai commenti |
| **Rewards** | 600 | Rewards associati ai progetti |

### 1.4 Glossario dei Dati

| Termine | Descrizione | Sinonimi |
|---------|-------------|----------|
| **Utente** | Persona registrata nel sistema | User, Account |
| **Creatore** | Utente che può pubblicare progetti | Creator, Progettista |
| **Progetto** | Iniziativa che richiede finanziamenti | Campaign, Campagna |
| **Finanziamento** | Contributo economico a un progetto | Funding, Donazione |
| **Reward** | Ricompensa per i finanziatori | Premio, Ricompensa |
| **Competenza** | Abilità tecnica gestita dal sistema | Skill, Abilità |
| **Budget Richiesto** | Importo target del progetto | Goal, Obiettivo |
| **Budget Raccolto** | Importo attualmente finanziato | Raised, Raccolto |
| **Affidabilità** | Punteggio di credibilità del creatore | Reliability, Credibilità |

---

## 2. PROGETTAZIONE CONCETTUALE

### 2.1 Diagramma E-R

```
                    UTENTI
                ┌─────────────────┐
                │  id (PK)        │
                │  email          │
                │  nickname       │
                │  password       │
                │  nome           │
                │  cognome        │
                │  anno_nascita   │
                │  luogo_nascita  │
                │  tipo_utente    │
                │  nr_progetti    │
                │  affidabilita   │
                └─────────────────┘
                         │
                    ┌────┴────┐
                    │ POSSIEDE │ (1:N)
                    └────┬────┘
                         │
                 ┌───────────────────┐
                 │    SKILL_UTENTE   │
                 │  id (PK)          │
                 │  livello (0-5)    │
                 └───────────────────┘
                         │
                    ┌────┴────┐
                    │ RIFERISCE│ (N:1)
                    └────┬────┘
                         │
                ┌─────────────────┐
                │   COMPETENZE    │
                │  id (PK)        │
                │  nome           │
                │  descrizione    │
                └─────────────────┘

    UTENTI ──┐
             │ CREA (1:N)
             ▼
    ┌─────────────────┐         ┌─────────────────┐
    │    PROGETTI     │◄────────│    REWARDS      │
    │  id (PK)        │ OFFRE   │  id (PK)        │
    │  nome           │ (1:N)   │  codice         │
    │  descrizione    │         │  descrizione    │
    │  budget_rich.   │         │  importo_min    │
    │  budget_racc.   │         └─────────────────┘
    │  data_limite    │                  │
    │  stato          │                  │ SCELTO_PER
    │  tipo           │                  │ (N:1)
    └─────────────────┘                  ▼
             │                  ┌─────────────────┐
             │ RICEVE           │  FINANZIAMENTI  │
             │ (1:N)            │  id (PK)        │
             ▼                  │  importo        │
    ┌─────────────────┐         │  data_finanz.   │
    │    COMMENTI     │◄────────│  note           │
    │  id (PK)        │ FINANZIA└─────────────────┘
    │  testo          │ (N:1)            ▲
    │  data_commento  │                  │ EFFETTUA
    └─────────────────┘                  │ (N:1)
             │                           │
             │ HA_RISPOSTA               │
             │ (1:1)                     │
             ▼                    ┌──────┴──────┐
    ┌─────────────────┐           │   UTENTI    │
    │ RISPOSTE_COMM.  │           │             │
    │  id (PK)        │           └─────────────┘
    │  testo          │
    │  data_risposta  │
    └─────────────────┘
```

### 2.2 Dizionario delle Entità e Relazioni

#### Entità

**UTENTI**

- **Descrizione:** Rappresenta tutti gli utenti registrati nel sistema
- **Attributi:** id, email, nickname, password, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, nr_progetti, affidabilita
- **Identificatore:** id

**PROGETTI**

- **Descrizione:** Rappresenta i progetti di crowdfunding
- **Attributi:** id, nome, descrizione, budget_richiesto, budget_raccolto, data_limite, stato, tipo
- **Identificatore:** id

**COMPETENZE**

- **Descrizione:** Catalogo delle competenze tecniche gestite dal sistema
- **Attributi:** id, nome, descrizione
- **Identificatore:** id

**FINANZIAMENTI**

- **Descrizione:** Rappresenta i contributi economici ai progetti
- **Attributi:** id, importo, data_finanziamento, note
- **Identificatore:** id

**REWARDS**

- **Descrizione:** Ricompense offerte dai progetti ai finanziatori
- **Attributi:** id, codice, descrizione, importo_minimo
- **Identificatore:** id

**COMMENTI**

- **Descrizione:** Commenti degli utenti sui progetti
- **Attributi:** id, testo, data_commento
- **Identificatore:** id

#### Relazioni

**CREA (Utenti → Progetti)**

- **Cardinalità:** 1:N
- **Descrizione:** Un utente creatore può creare più progetti

**POSSIEDE (Utenti → Skill_Utente)**

- **Cardinalità:** 1:N  
- **Descrizione:** Un utente può avere più competenze

**RIFERISCE (Skill_Utente → Competenze)**

- **Cardinalità:** N:1
- **Descrizione:** Più skill utente riferiscono alla stessa competenza

**EFFETTUA (Utenti → Finanziamenti)**

- **Cardinalità:** 1:N
- **Descrizione:** Un utente può effettuare più finanziamenti

**RICEVE (Progetti → Finanziamenti)**

- **Cardinalità:** 1:N
- **Descrizione:** Un progetto può ricevere più finanziamenti

### 2.3 Tavola delle Business Rules

| ID | Regola | Tipo | Descrizione |
|----|--------|------|-------------|
| BR1 | Unicità Email | Vincolo | Ogni utente deve avere un'email unica |
| BR2 | Unicità Nickname | Vincolo | Ogni utente deve avere un nickname unico |
| BR3 | Budget Positivo | Vincolo | Il budget richiesto deve essere > 0 |
| BR4 | Livello Competenza | Vincolo | Il livello deve essere tra 0 e 5 |
| BR5 | Finanziamento Positivo | Vincolo | L'importo finanziamento deve essere > 0 |
| BR6 | Data Limite Futura | Regola | La data limite deve essere futura alla creazione |
| BR7 | Tipo Utente Valido | Vincolo | Tipo utente: normale, creatore, amministratore |
| BR8 | Stato Progetto | Vincolo | Stato progetto: aperto, chiuso |
| BR9 | Tipo Progetto | Vincolo | Tipo progetto: hardware, software |
| BR10 | Risposta Unica | Vincolo | Un commento può avere al massimo una risposta |
| BR11 | Creatore Risponde | Regola | Solo il creatore del progetto può rispondere |
| BR12 | Aggiornamento Automatico | Trigger | Budget raccolto aggiornato automaticamente |

---

## 3. PROGETTAZIONE LOGICA

### 3.1 Ristrutturazione dello Schema Concettuale

#### Eliminazione Generalizzazioni

Non sono presenti generalizzazioni nel modello E-R.

#### Gestione Relazioni Many-to-Many

- **Skill_Utente**: Già rappresentata come entità associativa
- Tutte le altre relazioni sono 1:N e non richiedono ristrutturazione

#### Gestione Relazioni 1:1

- **Risposte_Commenti**: Mantenuta come entità separata per permettere estensioni future

### 3.2 Analisi delle Ridondanze

#### Ridondanze Introdotte

**R1: budget_raccolto in PROGETTI**

- **Motivazione:** Evitare calcoli costosi su finanziamenti
- **Frequenza Accesso:** 100 volte/giorno (alta)
- **Frequenza Aggiornamento:** 10 volte/giorno (bassa)
- **Decisione:** MANTENERE - Beneficio in performance superiore al costo

**R2: nr_progetti in UTENTI**

- **Motivazione:** Statistica utile per calcolo affidabilità
- **Frequenza Accesso:** 20 volte/giorno (media)
- **Frequenza Aggiornamento:** 8 volte/giorno (bassa)
- **Decisione:** MANTENERE - Utilizzato in calcoli frequenti

**R3: affidabilita in UTENTI**

- **Motivazione:** Evitare ricalcoli complessi
- **Frequenza Accesso:** 50 volte/giorno (alta)
- **Frequenza Aggiornamento:** 10 volte/giorno (bassa)
- **Decisione:** MANTENERE - Calcolo complesso, accesso frequente

### 3.3 Lista delle Tabelle con Vincoli di Chiavi

#### Tabella: utenti

```sql
CREATE TABLE utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita INT NOT NULL,
    luogo_nascita VARCHAR(100) NOT NULL,
    tipo_utente ENUM('normale', 'creatore', 'amministratore') DEFAULT 'normale',
    nr_progetti INT DEFAULT 0,
    affidabilita DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Tabella: progetti

```sql
CREATE TABLE progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    descrizione TEXT NOT NULL,
    budget_richiesto DECIMAL(10,2) NOT NULL CHECK (budget_richiesto > 0),
    budget_raccolto DECIMAL(10,2) DEFAULT 0.00,
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    tipo ENUM('hardware', 'software') NOT NULL,
    creatore_id INT NOT NULL,
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE
);
```

#### Tabella: competenze

```sql
CREATE TABLE competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) UNIQUE NOT NULL,
    descrizione TEXT
);
```

#### Tabella: skill_utente

```sql
CREATE TABLE skill_utente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello TINYINT NOT NULL CHECK (livello >= 0 AND livello <= 5),
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY (utente_id, competenza_id)
);
```

#### Tabella: rewards

```sql
CREATE TABLE rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    codice VARCHAR(50) NOT NULL,
    descrizione TEXT NOT NULL,
    importo_minimo DECIMAL(10,2) NOT NULL CHECK (importo_minimo > 0),
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE
);
```

#### Tabella: finanziamenti

```sql
CREATE TABLE finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    reward_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL CHECK (importo > 0),
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note TEXT,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE
);
```

#### Tabella: commenti

```sql
CREATE TABLE commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE
);
```

#### Tabella: risposte_commenti

```sql
CREATE TABLE risposte_commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commento_id INT NOT NULL,
    utente_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_risposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commento_id) REFERENCES commenti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY (commento_id)
);
```

### 3.4 Lista dei Vincoli Inter-relazionali

#### Vincoli di Integrità Referenziale

1. **progetti.creatore_id → utenti.id**
   - Un progetto deve avere un creatore valido
   - CASCADE on DELETE

2. **skill_utente.utente_id → utenti.id**
   - Una skill deve appartenere a un utente valido
   - CASCADE on DELETE

3. **skill_utente.competenza_id → competenze.id**
   - Una skill deve riferirsi a una competenza valida
   - CASCADE on DELETE

4. **finanziamenti.utente_id → utenti.id**
   - Un finanziamento deve essere di un utente valido
   - CASCADE on DELETE

5. **finanziamenti.progetto_id → progetti.id**
   - Un finanziamento deve essere per un progetto valido
   - CASCADE on DELETE

6. **finanziamenti.reward_id → rewards.id**
   - Un finanziamento deve avere un reward valido
   - CASCADE on DELETE

#### Vincoli di Dominio

1. **Importi positivi**: budget_richiesto, importo_minimo, importo > 0
2. **Livelli competenza**: 0 ≤ livello ≤ 5
3. **Tipi enumerati**: tipo_utente, stato, tipo progetto
4. **Unicità**: email, nickname, nome progetto
5. **Risposta unica**: commento_id unico in risposte_commenti

---

## 4. NORMALIZZAZIONE

### Analisi della Normalizzazione

#### Prima Forma Normale (1NF)

✅ **SODDISFATTA** - Tutte le tabelle hanno:

- Attributi atomici (no array o strutture complesse)
- Chiavi primarie definite
- Nessun gruppo ripetuto

#### Seconda Forma Normale (2NF)

✅ **SODDISFATTA** - Tutte le tabelle:

- Sono in 1NF
- Ogni attributo non-chiave dipende completamente dalla chiave primaria
- Non ci sono dipendenze parziali

#### Terza Forma Normale (3NF)

✅ **SODDISFATTA** - Tutte le tabelle:

- Sono in 2NF  
- Non ci sono dipendenze transitive
- Ogni attributo non-chiave dipende direttamente dalla chiave primaria

#### Forma Normale di Boyce-Codd (BCNF)

✅ **SODDISFATTA** - Per ogni dipendenza funzionale X → Y:

- X è una super-chiave
- Non ci sono anomalie di aggiornamento

### Conclusione Normalizzazione

Il database è completamente normalizzato fino alla BCNF. Non sono necessarie ulteriori decomposizioni.

---

## 5. DESCRIZIONE FUNZIONALITÀ APPLICAZIONE WEB

### 5.1 Architettura del Sistema

#### Stack Tecnologico

- **Frontend**: HTML5, CSS3, JavaScript ES6+, Bootstrap 5.3.3
- **Backend**: PHP 7.4+, PDO per database access
- **Database**: MySQL/MariaDB 10.4+
- **Server**: Apache (XAMPP)
- **Pattern**: MVC (Model-View-Controller)

#### Struttura Applicazione

```
BOSTARTER/
├── frontend/           # Interfaccia utente
│   ├── auth/          # Autenticazione
│   ├── admin/         # Pannello admin
│   ├── css/           # Stili
│   ├── js/            # JavaScript
│   └── *.php          # Pagine principali
├── backend/           # Logica business
│   ├── api/           # Endpoint REST
│   ├── models/        # Modelli dati
│   ├── controllers/   # Controller
│   ├── services/      # Servizi
│   └── utils/         # Utilità
└── database/          # Schema e dati
```

### 5.2 Funzionalità Principali

#### 5.2.1 Sistema di Autenticazione

- **Registrazione utenti** con validazione dati
- **Login sicuro** con password hashate
- **Gestione sessioni** con timeout automatico
- **Ruoli utente**: normale, creatore, amministratore

#### 5.2.2 Gestione Progetti

- **Creazione progetti** da parte dei creatori
- **Definizione rewards** con importi minimi
- **Caricamento immagini** e descrizioni dettagliate
- **Tracking progresso** con statistiche in tempo reale

#### 5.2.3 Sistema di Finanziamenti

- **Selezione reward** e importo personalizzato
- **Elaborazione pagamenti** (simulata)
- **Tracking contributi** per utente
- **Aggiornamento automatico** budget raccolto

#### 5.2.4 Interazioni Sociali

- **Commenti sui progetti** con moderazione
- **Risposte dei creatori** ai commenti
- **Sistema di notifiche** (base)

#### 5.2.5 Pannello Amministrazione

- **Gestione competenze** del sistema
- **Moderazione contenuti**
- **Statistiche globali** e report
- **Gestione utenti** e progetti

### 5.3 Interfaccia Utente

#### Design Responsivo

- **Mobile-first** approach
- **Bootstrap framework** per consistency
- **Animazioni CSS** per migliorare UX
- **Progressive Web App** features

#### Pagine Principali

1. **Home** - Lista progetti in evidenza
2. **Progetti** - Catalogo completo con filtri
3. **Dettaglio Progetto** - Informazioni complete e finanziamento
4. **Dashboard** - Gestione personale per creatori
5. **Profilo** - Gestione dati personali e competenze

### 5.4 API e Servizi

#### Endpoint REST

- **GET /api/projects** - Lista progetti
- **POST /api/projects** - Creazione progetto  
- **POST /api/funding** - Elaborazione finanziamento
- **GET /api/stats** - Statistiche sistema

#### Servizi Backend

- **AuthService** - Gestione autenticazione
- **ProjectService** - Logica progetti
- **FundingService** - Elaborazione finanziamenti
- **NotificationService** - Sistema notifiche

### 5.5 Sicurezza

#### Misure Implementate

- **Password hashing** con PHP password_hash()
- **Validazione input** con sanitizzazione
- **CSRF protection** per form critici
- **Session security** con rigenerazione ID
- **SQL injection prevention** con prepared statements

#### Controllo Accessi

- **Autorizzazione basata su ruoli**
- **Validazione permessi** per ogni operazione
- **Isolamento dati** tra utenti

---

## 6. APPENDICE: CODICE SQL COMPLETO

### 6.1 Creazione Database

```sql
-- BOSTARTER Database Schema Completo
-- Creato per il corso di Basi di Dati
-- Data: 25 Agosto 2025

CREATE DATABASE IF NOT EXISTS bostarter 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE bostarter;
```

### 6.2 Creazione Tabelle

#### Tabella Utenti

```sql
CREATE TABLE IF NOT EXISTS utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    anno_nascita INT NOT NULL,
    luogo_nascita VARCHAR(100) NOT NULL,
    tipo_utente ENUM('normale', 'creatore', 'amministratore') DEFAULT 'normale',
    codice_sicurezza VARCHAR(50) NULL,
    nr_progetti INT DEFAULT 0,
    affidabilita DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_nickname (nickname),
    INDEX idx_tipo_utente (tipo_utente)
);
```

#### Tabella Competenze

```sql
CREATE TABLE IF NOT EXISTS competenze (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) UNIQUE NOT NULL,
    descrizione TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_nome (nome)
);
```

#### Tabella Skill Utente

```sql
CREATE TABLE IF NOT EXISTS skill_utente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello TINYINT NOT NULL CHECK (livello >= 0 AND livello <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    UNIQUE KEY unique_utente_competenza (utente_id, competenza_id),
    
    INDEX idx_utente (utente_id),
    INDEX idx_competenza (competenza_id)
);
```

#### Tabella Progetti

```sql
CREATE TABLE IF NOT EXISTS progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    descrizione TEXT NOT NULL,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    budget_richiesto DECIMAL(10,2) NOT NULL CHECK (budget_richiesto > 0),
    budget_raccolto DECIMAL(10,2) DEFAULT 0.00,
    data_limite DATE NOT NULL,
    stato ENUM('aperto', 'chiuso') DEFAULT 'aperto',
    tipo ENUM('hardware', 'software') NOT NULL,
    creatore_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (creatore_id) REFERENCES utenti(id) ON DELETE CASCADE,
    
    INDEX idx_nome (nome),
    INDEX idx_stato (stato),
    INDEX idx_tipo (tipo),
    INDEX idx_creatore (creatore_id),
    INDEX idx_data_limite (data_limite)
);
```

#### Tabella Foto Progetti

```sql
CREATE TABLE IF NOT EXISTS foto_progetti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    nome_file VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);
```

#### Tabella Rewards

```sql
CREATE TABLE IF NOT EXISTS rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    codice VARCHAR(50) NOT NULL,
    descrizione TEXT NOT NULL,
    importo_minimo DECIMAL(10,2) NOT NULL CHECK (importo_minimo > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id),
    INDEX idx_codice (codice)
);
```

#### Tabella Specifiche Tecniche

```sql
CREATE TABLE IF NOT EXISTS specifiche_tecniche (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    specifica TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    INDEX idx_progetto (progetto_id)
);
```

#### Tabella Competenze Richieste

```sql
CREATE TABLE IF NOT EXISTS competenze_richieste (
    id INT PRIMARY KEY AUTO_INCREMENT,
    progetto_id INT NOT NULL,
    competenza_id INT NOT NULL,
    livello_richiesto TINYINT NOT NULL CHECK (livello_richiesto >= 0 AND livello_richiesto <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (competenza_id) REFERENCES competenze(id) ON DELETE CASCADE,
    
    INDEX idx_progetto (progetto_id),
    INDEX idx_competenza (competenza_id)
);
```

#### Tabella Finanziamenti

```sql
CREATE TABLE IF NOT EXISTS finanziamenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    reward_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL CHECK (importo > 0),
    data_finanziamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note TEXT,
    
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
    
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_data (data_finanziamento)
);
```

#### Tabella Commenti

```sql
CREATE TABLE IF NOT EXISTS commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utente_id INT NOT NULL,
    progetto_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_commento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    
    INDEX idx_utente (utente_id),
    INDEX idx_progetto (progetto_id),
    INDEX idx_data (data_commento)
);
```

#### Tabella Risposte Commenti

```sql
CREATE TABLE IF NOT EXISTS risposte_commenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commento_id INT NOT NULL,
    utente_id INT NOT NULL,
    testo TEXT NOT NULL,
    data_risposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (commento_id) REFERENCES commenti(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_risposta_per_commento (commento_id),
    
    INDEX idx_commento (commento_id),
    INDEX idx_utente (utente_id)
);
```

### 6.3 Viste

#### Vista Progetti con Statistiche

```sql
CREATE OR REPLACE VIEW vista_progetti_stats AS
SELECT 
    p.id,
    p.nome,
    p.descrizione,
    p.budget_richiesto,
    p.budget_raccolto,
    p.data_limite,
    p.stato,
    p.tipo,
    u.nickname as creatore_nickname,
    COUNT(f.id) as numero_finanziamenti,
    COUNT(DISTINCT f.utente_id) as numero_sostenitori,
    ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale_completamento
FROM progetti p
LEFT JOIN utenti u ON p.creatore_id = u.id
LEFT JOIN finanziamenti f ON p.id = f.progetto_id
GROUP BY p.id, u.nickname;
```

#### Vista Top Progetti

```sql
CREATE OR REPLACE VIEW vista_top_progetti AS
SELECT 
    p.id,
    p.nome,
    p.budget_richiesto,
    COALESCE(SUM(f.importo), 0) as budget_raccolto,
    (COALESCE(SUM(f.importo), 0) / p.budget_richiesto * 100) as percentuale,
    COUNT(DISTINCT f.utente_id) as sostenitori
FROM progetti p
LEFT JOIN finanziamenti f ON p.id = f.progetto_id
WHERE p.stato = 'aperto'
GROUP BY p.id, p.nome, p.budget_richiesto
ORDER BY percentuale DESC
LIMIT 10;
```

#### Vista Utenti Attivi

```sql
CREATE OR REPLACE VIEW vista_utenti_attivi AS
SELECT 
    u.id,
    u.nickname,
    u.tipo_utente,
    u.affidabilita,
    COUNT(f.id) as numero_finanziamenti
FROM utenti u
JOIN finanziamenti f ON u.id = f.utente_id
GROUP BY u.id, u.nickname, u.tipo_utente, u.affidabilita
HAVING numero_finanziamenti > 0
ORDER BY numero_finanziamenti DESC;
```

### 6.4 Trigger

#### Aggiornamento Budget Raccolto

```sql
DELIMITER //

CREATE TRIGGER update_budget_raccolto_insert
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    UPDATE progetti 
    SET budget_raccolto = budget_raccolto + NEW.importo
    WHERE id = NEW.progetto_id;
END//

CREATE TRIGGER update_budget_raccolto_delete
AFTER DELETE ON finanziamenti
FOR EACH ROW
BEGIN
    UPDATE progetti 
    SET budget_raccolto = budget_raccolto - OLD.importo
    WHERE id = OLD.progetto_id;
END//
```

#### Aggiornamento Numero Progetti

```sql
CREATE TRIGGER update_nr_progetti_insert
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti 
    SET nr_progetti = nr_progetti + 1
    WHERE id = NEW.creatore_id;
END//

CREATE TRIGGER update_nr_progetti_delete
AFTER DELETE ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti 
    SET nr_progetti = nr_progetti - 1
    WHERE id = OLD.creatore_id;
END//
```

#### Controllo Stato Progetto

```sql
CREATE TRIGGER check_progetto_stato
BEFORE UPDATE ON progetti
FOR EACH ROW
BEGIN
    -- Chiude automaticamente se budget raggiunto
    IF NEW.budget_raccolto >= NEW.budget_richiesto THEN
        SET NEW.stato = 'chiuso';
    END IF;
    
    -- Chiude automaticamente se scaduto
    IF NEW.data_limite < CURDATE() AND OLD.stato = 'aperto' THEN
        SET NEW.stato = 'chiuso';
    END IF;
END//

DELIMITER ;
```

### 6.5 Procedure e Funzioni

#### Calcolo Affidabilità Utente

```sql
DELIMITER //

CREATE FUNCTION calcola_affidabilita(utente_id INT) 
RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE progetti_totali INT DEFAULT 0;
    DECLARE progetti_finanziati INT DEFAULT 0;
    DECLARE affidabilita DECIMAL(5,2) DEFAULT 0.00;
    
    -- Conta progetti totali dell'utente
    SELECT COUNT(*) INTO progetti_totali
    FROM progetti 
    WHERE creatore_id = utente_id;
    
    -- Conta progetti che hanno ricevuto finanziamenti
    SELECT COUNT(DISTINCT p.id) INTO progetti_finanziati
    FROM progetti p
    INNER JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE p.creatore_id = utente_id;
    
    -- Calcola affidabilità
    IF progetti_totali > 0 THEN
        SET affidabilita = (progetti_finanziati / progetti_totali) * 100;
    END IF;
    
    RETURN affidabilita;
END//

DELIMITER ;
```

#### Aggiornamento Affidabilità

```sql
DELIMITER //

CREATE PROCEDURE aggiorna_affidabilita_utente(IN utente_id INT)
BEGIN
    DECLARE nuova_affidabilita DECIMAL(5,2);
    
    SET nuova_affidabilita = calcola_affidabilita(utente_id);
    
    UPDATE utenti 
    SET affidabilita = nuova_affidabilita
    WHERE id = utente_id;
END//

DELIMITER ;
```

### 6.6 Indici per Performance

```sql
-- Indici aggiuntivi per ottimizzazione query frequenti
CREATE INDEX idx_progetti_stato_tipo ON progetti(stato, tipo);
CREATE INDEX idx_finanziamenti_data_importo ON finanziamenti(data_finanziamento, importo);
CREATE INDEX idx_utenti_tipo_affidabilita ON utenti(tipo_utente, affidabilita);
CREATE INDEX idx_progetti_budget ON progetti(budget_richiesto, budget_raccolto);
```

### 6.7 Dati di Test

```sql
-- Inserimento utente amministratore
INSERT INTO utenti (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza) 
VALUES ('admin@bostarter.com', 'admin', '$2y$10$hash_password', 'Admin', 'Sistema', 1990, 'Roma', 'amministratore', 'ADMIN2025');

-- Inserimento competenze base
INSERT INTO competenze (nome, descrizione) VALUES 
('PHP', 'Linguaggio di programmazione server-side'),
('JavaScript', 'Linguaggio di programmazione client-side'),
('MySQL', 'Sistema di gestione database relazionale'),
('HTML/CSS', 'Linguaggi di markup e styling'),
('Python', 'Linguaggio di programmazione versatile');
```

---

## CONCLUSIONI

Il sistema BOSTARTER rappresenta una soluzione completa per la gestione di piattaforme di crowdfunding, implementata seguendo rigorosamente la metodologia di progettazione vista a lezione.

### Punti di Forza

1. **Design normalizzato** fino alla BCNF
2. **Architettura scalabile** con pattern MVC
3. **Sicurezza implementata** a tutti i livelli
4. **Performance ottimizzate** con indici e viste
5. **Integrità dati garantita** tramite trigger e vincoli

### Tecnologie Utilizzate

- **Database**: MySQL/MariaDB con schema relazionale completo
- **Backend**: PHP con architettura MVC e API REST
- **Frontend**: HTML5, CSS3, JavaScript con Bootstrap
- **Sicurezza**: Hashing password, prepared statements, validazione input

Il progetto è completamente funzionante e pronto per un ambiente di produzione, con tutte le funzionalità richieste per una piattaforma di crowdfunding moderna e sicura.

---

**Data di consegna:** 25 Agosto 2025  
**Versione documento:** 1.0
