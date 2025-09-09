# BOSTARTER - Database MySQL

## 📋 Panoramica

Sistema di database MySQL completo per la piattaforma di crowdfunding BOSTARTER. Include schema, stored procedures, trigger, viste statistiche e dati demo.

## 🗂️ Struttura File

```
database/
├── schema_mysql.sql          # Schema completo (tabelle, viste, indici)
├── procedures_mysql.sql      # Stored procedures per business logic
├── triggers_mysql.sql        # Trigger per automazione
├── data_demo_mysql.sql       # Dati demo per test
├── simple_install.php        # Script installazione automatica
├── ER_MODEL.md              # Documentazione modello dati
└── README_MYSQL.md          # Questa documentazione
```

## 🚀 Deploy Rapido

### Opzione 1: Script Automatico
```bash
# Vai su: http://localhost/BOSTARTER/database/simple_install.php
```

### Opzione 2: phpMyAdmin
1. Apri `http://localhost/phpmyadmin`
2. Esegui i file nell'ordine:
   - `schema_mysql.sql`
   - `procedures_mysql.sql` 
   - `triggers_mysql.sql`
   - `data_demo_mysql.sql`

### Opzione 3: Linea di Comando
```bash
# Crea database e schema
mysql -u root -p < schema_mysql.sql

# Aggiungi procedures
mysql -u root -p bostarter < procedures_mysql.sql

# Aggiungi trigger
mysql -u root -p bostarter < triggers_mysql.sql

# Inserisci dati demo
mysql -u root -p bostarter < data_demo_mysql.sql
```

## 📊 Schema Database

### Tabelle Principali (15)
- **utenti** - Gestione utenti e creatori
- **progetti** - Progetti di crowdfunding
- **finanziamenti** - Transazioni di finanziamento
- **categorie** - Categorie progetti
- **competenze** - Competenze richieste/possedute
- **ricompense** - Ricompense per sostenitori
- **commenti** - Sistema commenti progetti
- **candidature** - Candidature per partecipare ai progetti
- **componenti** - Membri team progetti
- **profili** - Profili team descrittivi
- **foto_progetti** - Galleria immagini progetti
- **system_log** - Log attività sistema
- **utenti_competenze** - Relazione utenti-competenze
- **progetti_competenze** - Relazione progetti-competenze
- **profili_competenze** - Relazione profili-competenze

### Viste Statistiche (3)
- **top_creatori** - Classifica creatori per affidabilità
- **progetti_quasi_completati** - Progetti vicini all'obiettivo
- **top_finanziatori** - Maggiori sostenitori

## ⚙️ Stored Procedures

### Gestione Utenti
- `registra_utente()` - Registrazione nuovo utente
- `aggiorna_affidabilita()` - Calcolo affidabilità creatori

### Gestione Progetti  
- `crea_progetto()` - Creazione nuovo progetto
- `finanzia_progetto()` - Processo finanziamento
- `chiudi_progetti_scaduti()` - Chiusura automatica progetti

### Competenze
- `assegna_competenza_utente()` - Assegnazione competenze

### Statistiche
- `get_statistiche_generali()` - Statistiche piattaforma

## 🔄 Trigger Automatici

### Business Logic
- Aggiornamento contatori progetti (sostenitori, commenti, candidature)
- Calcolo affidabilità creatori automatico
- Gestione quantità ricompense
- Chiusura automatica progetti completati/falliti

### Logging
- Log automatico modifiche utenti
- Log cambio stato progetti
- Tracciamento attività sistema

## 📈 Event Scheduler

- **chiusura_progetti_automatica** - Esecuzione giornaliera per chiudere progetti scaduti

## 🧪 Dati Demo

### Account Test
- **Admin:** admin@bostarter.local / password
- **Creatore:** mario.rossi@email.com / password  
- **Utente:** giulia.bianchi@email.com / password

### Contenuti Demo
- 8 categorie progetti
- 10 competenze diverse
- 7 utenti con profili completi
- 5 progetti in vari stati
- 10 finanziamenti completati
- 9 ricompense disponibili
- Sistema commenti attivo
- Candidature e team members

## 🔧 Test Sistema

### Test Automatico
```bash
# Vai su: http://localhost/BOSTARTER/test_mysql.php
```

### Test API
```bash
# Test endpoint progetti
curl http://localhost/BOSTARTER/backend/api/progetti.php

# Test endpoint utenti  
curl http://localhost/BOSTARTER/backend/api/utenti.php
```

### Test Frontend
```bash
# Interfaccia utente
http://localhost/BOSTARTER/frontend/
```

## 📋 Requisiti Sistema

### Software Richiesto
- **MySQL 5.7+** o **MariaDB 10.3+**
- **PHP 7.4+** con estensioni:
  - `pdo_mysql`
  - `json`
  - `openssl`
  - `curl`

### Configurazione MySQL
```sql
-- Abilita event scheduler
SET GLOBAL event_scheduler = ON;

-- Verifica configurazione
SHOW VARIABLES LIKE 'event_scheduler';
```

## 🔍 Verifica Installazione

### Controllo Tabelle
```sql
USE bostarter;
SHOW TABLES;
-- Dovrebbe mostrare 15 tabelle
```

### Controllo Procedures
```sql
SHOW PROCEDURE STATUS WHERE Db = 'bostarter';
-- Dovrebbe mostrare 6 procedure
```

### Controllo Trigger
```sql
SHOW TRIGGERS;
-- Dovrebbe mostrare 10 trigger
```

### Controllo Dati
```sql
SELECT 
    (SELECT COUNT(*) FROM utenti) as utenti,
    (SELECT COUNT(*) FROM progetti) as progetti,
    (SELECT COUNT(*) FROM finanziamenti) as finanziamenti;
-- Dovrebbe mostrare: 7 utenti, 5 progetti, 10 finanziamenti
```

## 🚨 Troubleshooting

### Errore "Event Scheduler OFF"
```sql
SET GLOBAL event_scheduler = ON;
```

### Errore Foreign Key
```sql
SET FOREIGN_KEY_CHECKS = 0;
-- Esegui operazione
SET FOREIGN_KEY_CHECKS = 1;
```

### Reset Completo Database
```sql
DROP DATABASE IF EXISTS bostarter;
-- Poi riesegui schema_mysql.sql
```

## 📞 Supporto

Per problemi o domande:
1. Verifica log MySQL: `/var/log/mysql/error.log`
2. Controlla log PHP: `error_log`
3. Usa test automatico: `test_mysql.php`

---

**BOSTARTER MySQL Database v2.0**  
*Sistema completo e pronto per produzione*
