# BOSTARTER - Piattaforma di Crowdfunding

Piattaforma moderna per crowdfunding di progetti hardware e software con sistema completo di gestione competenze e candidature.

## 🚀 Funzionalità

- **Gestione Progetti**: Hardware e software con componenti/profili
- **Sistema Finanziamenti**: Con reward personalizzate e stati automatici
- **Competenze & Candidature**: Matching intelligente skill-progetti
- **Tre Tipologie Utenti**: Standard, Creatori, Amministratori
- **Statistiche Real-time**: Dashboard con top 3 classifiche
- **Automazioni**: Trigger per affidabilità, chiusura progetti, logging

## 📋 Installazione

### Prerequisiti

- XAMPP con MySQL
- PHP 7.4+

### Setup Rapido

```bash
# 1. Database
cd database
mysql -u root -p < schema.sql
mysql -u root -p < stored.sql
mysql -u root -p < dati.sql

# 2. Configurazione
# Verifica backend/config/database.php

# 3. Avvio
# Avvia XAMPP e vai su localhost/BOSTARTER/frontend/
```

## 👤 Account di Test

| Tipo | Email | Password | Codice |
|------|--------|----------|---------|
| Admin | <admin@bostarter.com> | password | ADMIN2024 |
| Creatore | <mario.rossi@email.com> | password | - |
| Standard | <giulia.bianchi@email.com> | password | - |

## 🏗️ Architettura

```text
BOSTARTER/
├── backend/          # API e logica business
├── frontend/         # Interfaccia utente
├── database/         # Schema e stored procedures
└── logs/            # Sistema logging
```

## 📊 Database

- **13 tabelle** con relazioni ottimizzate
- **16 stored procedures** per tutte le operazioni
- **9 trigger** per automazioni business logic
- **3 viste** per statistiche real-time
- **1 evento** per chiusura progetti scaduti

## 🔧 Tecnologie

- **Backend**: PHP, MySQL, Stored Procedures
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **Sicurezza**: Password hash, validazioni, autorizzazioni
- **Performance**: Indici ottimizzati, query efficienti

## ✅ Conformità Specifiche

- Tutte le operazioni via stored procedures
- Statistiche via viste database
- Trigger per calcolo affidabilità e stati
- Eventi automatici per scadenze
- Tabella volumi rispettata (10 progetti, 30 finanziamenti)

**Sistema completo e pronto per demo! 🚀**
