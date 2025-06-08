# BOSTARTER - Piattaforma di Crowdfunding 🚀

BOSTARTER è una piattaforma di crowdfunding sviluppata conforme alle specifiche del corso di Basi di Dati A.A. 2024/2025. La piattaforma supporta esclusivamente progetti **hardware** e **software**, implementando un sistema completo di candidature, competenze utente e finanziamenti con una architettura moderna e sicura.

## ✨ Caratteristiche Principali

- **🔐 Sistema di Autenticazione Compliant**
  - API RESTful conformi al PDF delle specifiche
  - Registrazione utenti con campi obbligatori (nome, cognome, anno_nascita, luogo_nascita, sesso)
  - Validazione input centralizzata con Validator class
  - Sistema di sessioni PHP sicuro

- **💼 Gestione Progetti Conformi al PDF**
  - **SOLO** progetti Hardware e Software (conforme alle specifiche)
  - Sistema competenze utente con livelli 0-5
  - Candidature ai progetti software con matching competenze
  - Finanziamenti con reward system
  - Stati progetti: 'aperto' / 'chiuso'

- **🎯 Sistema Competenze Avanzato**
  - Associazione utenti-competenze con livelli di expertise
  - Validazione automatica requisiti per candidature
  - Profili software con competenze richieste
  - Gestione skill mismatch e feedback utente

- **📊 Database Compliant & Performance**
  - Schema conforme al 100% alle specifiche PDF
  - 15+ tabelle implementate con relazioni ottimizzate
  - Stored procedures per operazioni critiche
  - Views statistiche per reportistica
  - Trigger per mantenimento consistenza dati

- **🎨 Frontend Moderno & Accessibile**
  - Design responsive con Tailwind CSS
  - Dashboard utenti con gestione progetti
  - Sistema tema chiaro/scuro
  - API JavaScript per integrazione seamless
  - Lazy loading e ottimizzazioni performance

## 📋 Requisiti

- **PHP >= 8.0** con estensioni:
  - PDO (per database MySQL)
  - JSON
  - OpenSSL
  - mysqli
- **MySQL >= 5.7** o MariaDB >= 10.2
- **XAMPP/WAMP** o server web con mod_rewrite
- Browser moderno (per frontend JavaScript)

## 🛠️ Installazione

### 1. Setup Progetto

```bash
# Clona o scarica il progetto nella directory XAMPP
# Posiziona in: C:\xampp\htdocs\BOSTARTER\
```

### 2. Configurazione Database

```bash
# Avvia XAMPP (Apache + MySQL)
# Crea database 'bostarter_compliant' in phpMyAdmin

# Importa schema conforme:
mysql -u root -p bostarter_compliant < database/bostarter_schema_compliant.sql

# Importa stored procedures e dati di test:
mysql -u root -p bostarter_compliant < database/create_apply_and_sample.sql
```

### 3. Configurazione Backend

```php
// Verifica configurazione in backend/config/database.php
$host = 'localhost';
$dbname = 'bostarter_compliant';  
$username = 'root';
$password = '';  // O la tua password MySQL
```

### 4. Test Installazione

```
# Accedi a: http://localhost/BOSTARTER/
# Registra un nuovo utente con tutti i campi richiesti
# Testa login e accesso dashboard
```

## 🔧 Configurazione

### Variabili di Database

```php
// backend/config/database.php
private $host = "localhost";
private $db_name = "bostarter_compliant";
private $username = "root";  
private $password = "";  // Imposta la tua password MySQL
```

### Logging MongoDB (Opzionale)

```php
// backend/services/MongoLogger.php - Per logging eventi avanzato
// Configura connessione MongoDB se disponibile
```

## 📚 Documentazione API

### 🔐 Autenticazione

#### Registrazione Utente (Compliant)

```http
POST /BOSTARTER/backend/api/auth_compliant.php
Content-Type: application/json

{
    "action": "register",
    "email": "user@example.com",
    "password": "password123", 
    "nickname": "username",
    "nome": "Mario",
    "cognome": "Rossi",
    "anno_nascita": 1995,
    "luogo_nascita": "Roma",
    "sesso": "M"
}
```

#### Login

```http
POST /BOSTARTER/backend/api/auth_compliant.php
Content-Type: application/json

{
    "action": "login",
    "email": "user@example.com", 
    "password": "password123"
}
```

### 💼 Progetti (Solo Hardware/Software)

#### Lista Progetti

```http
GET /BOSTARTER/backend/api/projects_compliant.php?action=list
Optional Parameters:
- tipo=hardware|software
- stato=aperto|chiuso  
- page=1&per_page=10
```

#### Dettagli Progetto

```http
GET /BOSTARTER/backend/api/projects_compliant.php?action=get&id=1
```

#### Creazione Progetto (Auth Required)

```http
POST /BOSTARTER/backend/api/projects_compliant.php
Content-Type: application/json
Authorization: Session required

{
    "action": "create",
    "nome": "Robot Educativo",
    "descrizione": "Robot per apprendimento STEM",
    "budget_richiesto": 15000,
    "data_scadenza": "2024-12-31",
    "tipo": "hardware"
}
```

### 🎯 Candidature & Competenze

#### Candidatura a Progetto Software

```http
POST /BOSTARTER/backend/api/apply_project.php
Content-Type: application/json

{
    "project_id": 1,
    "profilo_id": 1  
}
```

#### Ricerca Progetti

```http
GET /BOSTARTER/backend/api/search.php?q=robot&tipo=hardware
```

### 📊 Statistiche

#### Top Creatori per Affidabilità  

```http
GET /BOSTARTER/backend/api/stats_compliant.php?action=top_creators
```

## 🧪 Testing

### Test Funzionali Base

```bash
# 1. Test Registrazione
# Accedi a: http://localhost/BOSTARTER/frontend/auth/register.php
# Compila tutti i campi richiesti incluso 'sesso'

# 2. Test Login  
# Accedi a: http://localhost/BOSTARTER/frontend/auth/login.php

# 3. Test Dashboard
# Dopo login: http://localhost/BOSTARTER/frontend/dashboard/

# 4. Test Ricerca Progetti
# Accedi a: http://localhost/BOSTARTER/frontend/projects/list_open.php
```

### Test API con cURL

```bash
# Test registrazione API
curl -X POST http://localhost/BOSTARTER/backend/api/auth_compliant.php \
  -H "Content-Type: application/json" \
  -d '{"action":"register","email":"test@test.it","password":"test123","nickname":"testuser","nome":"Test","cognome":"User","anno_nascita":1995,"luogo_nascita":"Roma","sesso":"M"}'

# Test lista progetti  
curl http://localhost/BOSTARTER/backend/api/projects_compliant.php?action=list
```

### Dati di Test Inclusi

Il database include dati di esempio:

- **Utenti**: <admin@test.it> / <user@test.it> (password: test123)
- **Progetti**: 5 progetti hardware/software di esempio
- **Competenze**: PHP, JavaScript, Python, MySQL, etc.

## 🔐 Sicurezza & Compliance

- **Password Hashing**: bcrypt per tutte le password utente
- **Validazione Input**: Validator class centralizzata con sanitizzazione  
- **SQL Injection Protection**: Prepared statements in tutti i query
- **Session Security**: Gestione sessioni PHP native sicure
- **Error Handling**: Logging errori senza esposizione dati sensibili
- **Database Compliance**: Schema 100% conforme alle specifiche PDF

## 📁 Struttura del Progetto

```
BOSTARTER/
├── backend/                    # Backend PHP
│   ├── api/                   # API endpoints RESTful
│   │   ├── auth_compliant.php # Autenticazione (login/register)
│   │   ├── projects_compliant.php # Gestione progetti hardware/software  
│   │   ├── apply_project.php  # Candidature progetti software
│   │   ├── search.php         # Ricerca progetti
│   │   ├── stats_compliant.php # Statistiche conformi
│   │   └── user_skills.php    # Gestione competenze utenti
│   ├── config/               # Configurazioni
│   │   └── database.php      # Configurazione database MySQL
│   ├── models/               # Modelli dati
│   │   ├── ProjectCompliant.php # Modello progetti conforme
│   │   ├── UserCompliant.php # Modello utenti conforme
│   │   └── Notification.php  # Gestione notifiche
│   ├── utils/                # Utility e helper
│   │   ├── Validator.php     # Validazione input centralizzata
│   │   ├── ApiResponse.php   # Gestione risposte API standardizzate
│   │   └── Auth.php          # Helper autenticazione
│   └── services/             # Servizi business logic
│       └── MongoLogger.php   # Logging eventi avanzato
├── database/                  # Database e setup
│   ├── bostarter_schema_compliant.sql # Schema conforme al PDF
│   ├── create_apply_and_sample.sql   # Stored procedures + dati test
│   └── SCHEMA_BOSTARTER.md           # Documentazione schema
├── frontend/                  # Frontend web  
│   ├── auth/                 # Pagine autenticazione
│   │   ├── login.php         # Form login
│   │   └── register.php      # Form registrazione (con campo sesso)
│   ├── projects/             # Gestione progetti
│   │   ├── list_open.php     # Lista progetti aperti
│   │   ├── detail.php        # Dettagli progetto
│   │   └── apply.php         # Candidatura progetto
│   ├── dashboard/            # Dashboard utenti
│   ├── js/                   # JavaScript frontend
│   │   ├── api.js           # Gestione chiamate API
│   │   ├── auth.js          # Validazione form autenticazione
│   │   └── dashboard-manager.js # Gestione dashboard
│   └── css/                  # Fogli di stile
│       └── tailwind.css      # Framework CSS moderno
└── README.md                  # Documentazione principale
```

### 🎯 Architettura Caratteristiche

- **API RESTful**: Endpoints organizzati per funzionalità
- **Separazione Backend/Frontend**: Architettura moderna scalabile  
- **Database Compliant**: Schema conforme al 100% alle specifiche
- **Stored Procedures**: Operazioni critiche gestite a livello DB
- **Validazione Centralizzata**: Input validation unificata
- **Logging Strutturato**: Tracciamento eventi con MongoDB

## 📈 Performance & Database

### Database Ottimizzazioni

- **Indici strategici** su campi di ricerca frequente (email, tipo_progetto, stato)
- **Foreign keys** per integrità referenziale
- **Trigger automatici** per aggiornamento affidabilità creatori
- **Views materializzate** per statistiche complesse
- **Event scheduler** per chiusura automatica progetti scaduti

### Stored Procedures Implementate

```sql
-- Registrazione utente compliant
CALL sp_registra_utente(email, password, nickname, nome, cognome, anno, luogo, sesso)

-- Candidatura a progetto (con alias)  
CALL apply_to_project(utente_id, progetto_id, profilo_id)
CALL candidati_progetto(utente_id, progetto_id, profilo_id)

-- Gestione competenze utente
CALL inserisci_skill_utente(utente_id, competenza_id, livello)
```

### Views Statistiche

- `vista_top_creatori_affidabilita` - Top 3 creatori per affidabilità
- `vista_progetti_per_stato` - Distribuzione progetti per stato  
- `vista_finanziamenti_mensili` - Trend finanziamenti nel tempo

## 🚀 Funzionalità Implementate

### ✅ Autenticazione & Utenti

- [x] Registrazione conforme con tutti i campi richiesti
- [x] Login sicuro con validazione
- [x] Gestione sessioni PHP
- [x] Dashboard utenti personalizzata
- [x] Sistema competenze con livelli 0-5

### ✅ Progetti (Hardware & Software)

- [x] Creazione progetti conformi al PDF
- [x] Lista progetti aperti con filtri
- [x] Dettagli progetto completi
- [x] Sistema finanziamenti con reward
- [x] Chiusura automatica progetti scaduti

### ✅ Candidature & Competenze  

- [x] Candidature progetti software
- [x] Validazione automatica skill requirements
- [x] Feedback dettagliato per skill mismatch
- [x] Gestione profili competenze richieste

### ✅ API & Integrazione

- [x] API RESTful per tutte le operazioni
- [x] Ricerca progetti unificata
- [x] Statistiche e reportistica
- [x] Logging eventi con MongoDB

## 🤝 Contribuire

### Setup Sviluppo

1. Clona il progetto in ambiente XAMPP
2. Importa schema compliant dal database
3. Configura credenziali database  
4. Testa API endpoints con Postman/cURL

### Guidelines

- Mantieni compliance 100% con specifiche PDF
- Usa prepared statements per tutte le query
- Documenta nuove API con esempi
- Testa su progetti SOLO hardware/software

## 📝 Licenza

Progetto sviluppato per il corso di **Basi di Dati A.A. 2024/2025**.
Conforme alle specifiche PDF del progetto.

## 👥 Team di Sviluppo

**Frontend Development**

- Sistema registrazione con validazione completa
- Dashboard responsive con gestione progetti  
- Integrazione API JavaScript

**Backend Development**  

- API RESTful conformi alle specifiche
- Database schema e stored procedures
- Sistema autenticazione e sicurezza

**Database Design**

- Schema conforme al 100% al PDF delle specifiche
- Ottimizzazioni performance e indici
- Trigger e views per funzionalità avanzate

## 📞 Supporto & Troubleshooting

### Problemi Comuni

**Errore Database Connection:**

```bash
# Verifica XAMPP sia avviato (Apache + MySQL)
# Controlla credenziali in backend/config/database.php
# Assicurati database 'bostarter_compliant' esista
```

**Errore API 404:**

```bash
# Verifica path: http://localhost/BOSTARTER/backend/api/...
# Controlla che mod_rewrite sia abilitato
# Verifica permessi cartelle XAMPP
```

**Problemi Registrazione:**

```bash
# Assicurati tutti i campi siano compilati (incluso 'sesso')
# Verifica email non sia già in uso
# Controlla log browser per errori JavaScript
```

### Log & Debug

- Errori PHP: `C:\xampp\apache\logs\error.log`
- Database errors: Controlla log MySQL in XAMPP Control Panel
- API testing: Usa browser developer tools o Postman

---

**BOSTARTER** - Una piattaforma di crowdfunding moderna e compliant 🚀
