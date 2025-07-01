# BOSTARTER - Piattaforma di Crowdfunding

**BOSTARTER** è una piattaforma di crowdfunding moderna e completa, sviluppata con backend PHP e frontend JavaScript vanilla. La piattaforma consente agli utenti di creare, gestire e finanziare progetti innovativi con focus su sviluppo hardware e software.

## 🚀 Caratteristiche

### Funzionalità Principali
- **Gestione Progetti**: Crea, modifica e pubblica progetti con validazione completa
- **Autenticazione Utenti**: Sistema sicuro di registrazione e login con validazione avanzata
- **Sistema di Finanziamento**: Supporta progetti con elaborazione pagamenti integrata
- **Notifiche in Tempo Reale**: Sistema avanzato di notifiche per aggiornamenti progetti
- **Candidature Progetti**: Candidati per entrare nei team di progetto con matching delle competenze
- **Dashboard Analytics**: Statistiche complete e analisi dei volumi

### Caratteristiche Tecniche
- **Design Responsive**: Bootstrap 5.3.3 con styling personalizzato
- **Progressive Enhancement**: JavaScript moderno con funzionalità di accessibilità
- **Sicurezza**: Rate limiting, gestione sicura password e conformità GDPR
- **Monitoraggio**: Sistema di logging MongoDB per tracciamento eventi completo
- **Performance**: Query database ottimizzate e meccanismi di caching

## 🏗️ Architettura

```
BOSTARTER/
├── backend/                    # Backend PHP
│   ├── api/                   # Endpoint REST API
│   ├── config/                # File di configurazione
│   ├── controllers/           # Controller logica business
│   ├── models/                # Modelli dati
│   ├── services/              # Layer di servizio
│   ├── middleware/            # Autenticazione e validazione
│   └── utils/                 # Funzioni utility
├── frontend/                  # Applicazione client-side
│   ├── css/                   # Fogli di stile
│   ├── js/                    # Moduli JavaScript
│   └── images/                # Asset statici
├── database/                  # Schema database e migrazioni
└── logs/                      # Log applicazione
```

## 🛠️ Stack Tecnologico

### Backend
- **PHP 8.0+** - Scripting server-side
- **MySQL** - Database primario
- **MongoDB** - Logging e analytics
- **PDO** - Layer di astrazione database
- **JWT** - Token di autenticazione

### Frontend
- **JavaScript Vanilla** - Nessuna dipendenza da framework
- **Bootstrap 5.3.3** - Framework UI
- **CSS3** - Styling personalizzato con animazioni
- **Progressive Web App** features

### Strumenti di Sviluppo
- **Composer** - Gestione dipendenze PHP
- **Visual Studio Code** - Ambiente di sviluppo
- **XAMPP** - Server di sviluppo locale

## 📋 Requisiti

- **PHP**: 8.0 o superiore
- **MySQL**: 5.7 o superiore
- **MongoDB**: 4.0 o superiore (per logging)
- **Web Server**: Apache o Nginx
- **Composer**: Ultima versione

## 🚀 Installazione

1. **Clona il repository**
   ```bash
   git clone https://github.com/tuousername/bostarter.git
   cd bostarter
   ```

2. **Installa dipendenze PHP**
   ```bash
   cd backend
   composer install
   ```

3. **Setup Database**
   ```bash
   # Importa lo schema principale
   mysql -u root -p < database/bostarter_schema_compliant.sql
   
   # Esegui migrazioni aggiuntive
   mysql -u root -p < database/security_tables.sql
   mysql -u root -p < database/notifications_enhancement.sql
   ```

4. **Configurazione**
   ```bash
   # Copia e configura le impostazioni environment
   cp backend/config/database.example.php backend/config/database.php
   # Modifica il file di configurazione con le tue credenziali database
   ```

5. **Crea Utente Admin**
   ```bash
   php database/create_admin_user.php
   ```

6. **Imposta Permessi**
   ```bash
   chmod 755 backend/logs/
   chmod 755 frontend/uploads/
   ```

## 🔧 Configurazione

### Configurazione Database
Modifica `backend/config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'bostarter');
define('DB_USER', 'tuo_username');
define('DB_PASS', 'tua_password');
```

### Configurazione MongoDB (Opzionale)
Per logging avanzato e analytics:

```php
define('MONGO_HOST', 'localhost');
define('MONGO_PORT', 27017);
define('MONGO_DB', 'bostarter_logs');
```

## 🎯 Utilizzo

### Per Creatori di Progetti
1. **Registrati** e completa il tuo profilo
2. **Crea Progetto** con informazioni dettagliate
3. **Aggiungi Competenze** richieste per il tuo progetto
4. **Pubblica** per revisione e finanziamento
5. **Gestisci** candidature e aggiornamenti

### Per Sostenitori
1. **Esplora Progetti** con filtri avanzati
2. **Supporta Progetti** con pagamenti sicuri
3. **Traccia Progressi** con notifiche in tempo reale
4. **Partecipa** con aggiornamenti e commenti progetti

### Per Collaboratori
1. **Cerca Progetti** per competenze e interessi
2. **Candidati** per entrare nei team di progetto
3. **Mostra** la tua esperienza e portfolio
4. **Collabora** su progetti innovativi

## 🔒 Caratteristiche di Sicurezza

- **Validazione Input**: Validazione completa server-side
- **Protezione SQL Injection**: Prepared statements e query parametrizzate
- **Prevenzione XSS**: Sanitizzazione output e header CSP
- **Protezione CSRF**: Validazione richieste basata su token
- **Rate Limiting**: Protezione endpoint API
- **Sessioni Sicure**: Flag cookie HTTPOnly e Secure
- **Sicurezza Password**: Hashing Bcrypt con salt

## 🎨 Architettura Frontend

Il frontend utilizza un'architettura JavaScript modulare:

- **[`BOSTARTERMaster`](frontend/js/bostarter-master.js)**: Framework core e animazioni
- **[`ProjectsManager`](frontend/js/projects.js)**: Listing e gestione progetti
- **[`NavigationManager`](frontend/js/navigation.js)**: Navigazione responsive
- **[`ModernRegistrationForm`](frontend/js/auth-register.js)**: UX registrazione potenziata
- **[`ModalAccessibility`](frontend/js/modal-accessibility.js)**: Funzionalità accessibilità

## 📊 Endpoint API

### Progetti
- `GET /api/projects_compliant.php` - Lista progetti con filtri
- `POST /api/projects_compliant.php` - Crea nuovo progetto
- `PUT /api/projects_compliant.php` - Aggiorna progetto
- `DELETE /api/projects_compliant.php` - Elimina progetto

### Statistiche
- `GET /api/stats_compliant.php?action=overview` - Statistiche piattaforma

### Candidature
- `POST /api/apply_project.php` - Candidati per entrare nel progetto

## 🧪 Testing

Esegui i test di validazione integrati:
```bash
php backend/utils/test_validator.php
```

## 📈 Monitoraggio

La piattaforma include logging completo attraverso:
- **[`MongoLogger`](backend/services/MongoLogger.php)**: Tracciamento eventi e analytics
- **[`NotificationService`](backend/services/NotificationService.php)**: Metriche engagement utenti
- **[`VolumeAnalysisService`](backend/services/VolumeAnalysisService.php)**: Analisi crescita piattaforma

## 🤝 Contribuire

1. Fai fork del repository
2. Crea un branch feature (`git checkout -b feature/funzionalita-fantastica`)
3. Commit delle tue modifiche (`git commit -m 'Aggiungi funzionalità fantastica'`)
4. Push al branch (`git push origin feature/funzionalita-fantastica`)
5. Apri una Pull Request

## 📄 Licenza

Questo progetto è rilasciato sotto Licenza MIT - vedi il file [LICENSE](LICENSE) per dettagli.

## 🙏 Riconoscimenti

- Team Bootstrap per l'eccellente framework CSS
- Community PHP per le robuste capacità server-side
- Contributori e tester che hanno aiutato a migliorare la piattaforma

## 📞 Supporto

Per supporto e domande:
- Crea un issue nel repository
- Consulta la documentazione nella cartella `docs/`
- Contatta il team di sviluppo

## 🌟 Caratteristiche Distintive

### Sistema di Validazione Avanzato
La piattaforma implementa un sistema di validazione multi-livello che garantisce:
- **Validazione Real-time**: Feedback immediato durante l'inserimento dati
- **Controlli Server-side**: Validazione completa backend per sicurezza
- **Messaggi Contestuali**: Guide utente intuitive per correzioni

### Gestione Competenze Intelligente
- **Matching Automatico**: Algoritmo di abbinamento skill-progetto
- **Suggerimenti Dinamici**: Raccomandazioni progetti basate su competenze
- **Portfolio Integrato**: Showcase lavori e certificazioni

### Analytics e Reporting
- **Dashboard Tempo Reale**: Metriche live performance progetti
- **Report Personalizzati**: Analisi dettagliate per creatori
- **Trend Analysis**: Identificazione pattern e opportunità mercato

---

**BOSTARTER** - Potenziamo l'Innovazione Attraverso il Crowdfunding 🚀

*Sviluppato con passione per supportare l'ecosistema dell'innovazione italiana*
