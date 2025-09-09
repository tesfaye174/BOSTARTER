# 🚀 BOSTARTER - Piattaforma di Crowdfunding

## 📖 Descrizione

Piattaforma web completa per crowdfunding di progetti hardware e software. Sistema robusto con database MySQL, trigger automatici, gestione utenti multi-ruolo e interfaccia moderna completamente ottimizzata.

## ✨ Caratteristiche Principali

- 👥 **Gestione Utenti**: Registrazione, login, profili (utente normale, creatore, amministratore)
- 💡 **Progetti**: Creazione e gestione progetti hardware/software con obiettivi di finanziamento
- 💰 **Finanziamenti**: Sistema di supporto progetti con ricompense
- 🎯 **Candidature**: Sistema di application per collaborazioni con matching competenze
- � **Statistiche**: Dashboard e analytics per utenti e amministratori
- 🔒 **Sicurezza**: Autenticazione sicura, validazione input, protezione CSRF

## 🛠️ Prerequisiti

- **XAMPP** con MySQL 8.0+
- **PHP** 7.4+ o 8.0+
- **Browser** moderno (Chrome, Firefox, Safari, Edge)

## 🚀 Installazione

### 📋 Setup Automatico (Raccomandato)

1. **Avvia XAMPP** e assicurati che Apache e MySQL siano in esecuzione

2. **Installa il Database**

   ```bash
   # Apri il browser e vai su:
   http://localhost/BOSTARTER/database/simple_install.php
   ```

3. **Verifica l'Installazione**

   ```bash
   # Controlla che tutto funzioni:
   http://localhost/BOSTARTER/test_mysql.php
   ```

### ⚡ Setup Manuale

Se preferisci l'installazione manuale:

```bash
# 1. Accedi a MySQL
mysql -u root -p

# 2. Importa lo schema del database
mysql -u root -p < database/schema_mysql.sql

# 3. Importa stored procedures
mysql -u root -p < database/procedures_mysql.sql

# 4. Importa trigger
mysql -u root -p < database/triggers_mysql.sql

# 5. Importa i dati di esempio
mysql -u root -p < database/data_demo_mysql.sql
```

## � Account di Test

| Ruolo | Email | Password | Note |
|-------|-------|----------|------|
| **Amministratore** | <admin@bostarter.local> | password | Accesso completo |
| **Creatore** | <mario.rossi@email.com> | password | Può creare progetti |
| **Utente Standard** | <giulia.bianchi@email.com> | password | Può supportare progetti |

## 🗂️ Struttura del Progetto

```
BOSTARTER/
├── 🌐 frontend/              # Interfaccia utente
│   ├── auth/                # Login, registrazione, logout
│   ├── admin/               # Pannello amministratore
│   ├── css/                 # Stili CSS moderni
│   ├── js/                  # JavaScript ES6+
│   ├── images/              # Assets grafici
│   └── includes/            # File condivisi
├── ⚙️ backend/               # Logica del server
│   ├── api/                 # Endpoint REST API
│   ├── models/              # Modelli dati MySQL
│   ├── services/            # Servizi business logic
│   ├── utils/               # Utility e helper
│   └── config/              # Configurazioni database
├── 🗄️ database/             # Schema e script MySQL
│   ├── schema_mysql.sql     # Struttura database MySQL
│   ├── procedures_mysql.sql # Stored procedures
│   ├── triggers_mysql.sql   # Trigger automazione
│   ├── data_demo_mysql.sql  # Dati di esempio
│   ├── simple_install.php   # Installer automatico
│   └── README_MYSQL.md      # Documentazione deploy
├── 📁 logs/                 # File di log
├── 📁 uploads/              # File caricati
└── test_mysql.php           # Test sistema completo
```

## 🔧 Funzionalità Implementate

### ✅ Autenticazione e Autorizzazione

- Registrazione utenti con validazione email
- Login sicuro con sessioni
- Sistema ruoli (normale, creatore, amministratore)
- Protezione CSRF e validazione input

### ✅ Gestione Progetti

- Creazione progetti hardware/software
- Upload immagini e descrizioni
- Obiettivi di finanziamento e deadline
- Stati automatici (aperto, chiuso, finanziato)

### ✅ Sistema Finanziamenti

- Supporto progetti con importi personalizzati
- Sistema ricompense per backers
- Tracciamento progressi finanziamento
- Chiusura automatica progetti

### ✅ Candidature e Competenze

- Gestione skill tecniche
- Candidature per collaborazioni
- Matching automatico competenze
- Pannello amministratore per gestione skill

### ✅ Dashboard e Statistiche

- Pannello utente personalizzato
- Statistiche progetti e finanziamenti
- Classifiche e analytics
- Report amministratore

## 🚀 Come Iniziare

1. **Avvia l'applicazione**

   ```
   http://localhost/BOSTARTER/frontend/home.php
   ```

2. **Fai Login come Amministratore**
   - Email: admin@bostarter.local
   - Password: password

3. **Esplora le Funzionalità**
   - Crea un nuovo progetto
   - Gestisci le competenze (Admin)
   - Testa il sistema di finanziamenti

## 🛠️ Tecnologie Utilizzate

- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap
- **Backend**: PHP 8.0+, PDO, MySQL
- **Database**: MySQL 8.0+ con trigger e stored procedures
- **Sicurezza**: CSRF protection, input validation, secure sessions
- **Tools**: Composer (gestione dipendenze), XAMPP (ambiente di sviluppo)

## 📞 Supporto

Per problemi o domande:

1. Controlla la documentazione in `/database/README_MYSQL.md`
2. Esegui i test automatici in `/test_mysql.php`
3. Verifica i log degli errori in `/logs/`
4. Controlla la compatibilità MySQL con lo script di installazione

## 📄 Licenza

Progetto sviluppato per scopi didattici e di apprendimento.

---

**BOSTARTER** - Trasforma le tue idee in realtà attraverso il crowdfunding! 🚀
