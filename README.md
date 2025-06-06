# BOSTARTER - Piattaforma di Crowdfunding 🚀

BOSTARTER è una piattaforma di crowdfunding moderna e ottimizzata per progetti creativi, sviluppata con architettura modulare e componenti condivisi.

## ✨ Caratteristiche Principali

- **🔐 Sistema di Autenticazione Sicuro**
  - API moderne RESTful (login.php, register.php)
  - Gestione sessioni JWT ottimizzata
  - Validazione input con FluentValidator
  - Protezione CSRF e XSS

- **📋 Gestione Progetti Avanzata**
  - 15 categorie specializzate (Arte, Design, Tecnologia, etc.)
  - Sistema di ricompense flessibile
  - Tracking finanziamenti real-time
  - Dashboard creatori responsive

- **🎨 Frontend Ottimizzato**
  - Componenti condivisi per prestazioni superiori
  - Design system unificato con Tailwind CSS
  - Tema chiaro/scuro automatico
  - PWA con service worker
  - Lazy loading e ottimizzazioni performance

- **🛡️ Sicurezza Enterprise**
  - Validazione input centralizzata
  - Hashing password bcrypt
  - Rate limiting per API
  - Logging eventi con MongoDB
  - Monitoraggio performance

## 📋 Requisiti

- PHP >= 8.0
- MySQL >= 5.7
- Composer
- Node.js >= 14.0 (per sviluppo frontend)
- Estensioni PHP:
  - PDO
  - JSON
  - OpenSSL

## 🛠️ Installazione

1. Clona il repository:
```bash
git clone https://github.com/tuousername/bostarter.git
cd bostarter
```

2. Installa le dipendenze PHP:
```bash
composer install
```

3. Configura il database:
- Crea un database MySQL
- Importa lo schema da `database/bostarter_schema_fixed.sql` o usa `database/complete_setup.sql` per un'installazione completa
- Importa le estensioni da `database/bostarter_extensions.sql`
- Configura le credenziali in `backend/config/database.php`

4. Configura il server web:
- Punto la root del server web alla cartella `public`
- Assicurati che mod_rewrite sia abilitato (Apache)
- Configura i permessi corretti per le cartelle

5. Configura le variabili d'ambiente:
```bash
cp .env.example .env
# Modifica .env con le tue configurazioni
```

## 🔧 Configurazione

### Backend
- `backend/config/config.php`: Configurazioni generali
- `backend/config/database.php`: Configurazione database
- `backend/config/routes.php`: Definizione routes API

### Frontend
- `frontend/js/config.js`: Configurazioni frontend
- `frontend/css/tailwind.config.js`: Configurazione Tailwind

## 📚 Documentazione API

### Autenticazione

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

#### Registrazione
```http
POST /api/auth/register
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123",
    "nickname": "username",
    "name": "Nome",
    "surname": "Cognome",
    "birth_year": 1990,
    "birth_place": "Città"
}
```

### Progetti

#### Creazione Progetto
```http
POST /api/projects/create
Content-Type: application/json
Authorization: Bearer <token>

{
    "name": "Nome Progetto",
    "description": "Descrizione",
    "budget": 1000,
    "project_type": "arte",
    "end_date": "2024-12-31"
}
```

## 🧪 Testing

```bash
# Esegui i test unitari
composer test

# Esegui i test di integrazione
composer test:integration
```

## 🔐 Sicurezza

- Tutte le password sono hashate con bcrypt
- Implementata protezione CSRF
- Validazione input lato server
- Rate limiting per le API
- Logging eventi di sicurezza

## 📁 Struttura del Progetto

```
BOSTARTER/
├── backend/                    # Backend PHP
│   ├── api/                   # API endpoints (RESTful)
│   │   ├── login.php         # Autenticazione utente
│   │   ├── register.php      # Registrazione utente
│   │   ├── projects_compliant.php # API progetti (compliant with PDF specs)
│   │   └── ...               # Altri endpoints
│   ├── config/               # Configurazioni
│   │   ├── database.php      # Configurazione database
│   │   └── config.php        # Configurazioni generali
│   ├── models/               # Modelli dati
│   │   ├── Project.php       # Modello progetti
│   │   └── Notification.php  # Modello notifiche
│   ├── utils/                # Utility e helper
│   │   ├── ApiResponse.php   # Gestione risposte API
│   │   ├── Auth.php          # Sistema autenticazione
│   │   └── FluentValidator.php # Validazione input
│   ├── services/             # Servizi business logic
│   └── legacy/               # File legacy (da migrare)
├── database/                  # Database e setup
│   ├── bostarter_schema.sql  # Schema principale
│   ├── setup_database.php    # Script setup
│   └── README.md             # Documentazione database
├── frontend/                  # Frontend web
│   ├── assets/               # Asset statici
│   ├── js/                   # JavaScript
│   ├── css/                  # Fogli di stile
│   └── components/           # Componenti riutilizzabili
├── tests/                     # Test e debug
│   ├── test_*.php            # File di test
│   └── README.md             # Documentazione test
├── docs/                      # Documentazione
├── logs/                      # File di log
└── README.md                  # Questo file
```

### Principi di Organizzazione

- **API RESTful**: Endpoints organizzati in `backend/api/`
- **Separazione di responsabilità**: Modelli, servizi e utility separati
- **Test isolati**: Tutti i test in directory dedicata
- **Database centralizzato**: Script e schema in `database/`
- **Documentazione**: README in ogni directory importante

## 📈 Performance

- Caching implementato per query frequenti
- Ottimizzazione immagini
- Lazy loading per componenti
- Minificazione assets

## 🤝 Contribuire

1. Fork il progetto
2. Crea un branch (`git checkout -b feature/AmazingFeature`)
3. Commit le modifiche (`git commit -m 'Add some AmazingFeature'`)
4. Push al branch (`git push origin feature/AmazingFeature`)
5. Apri una Pull Request

## 📝 Licenza

Questo progetto è sotto licenza MIT - vedi il file [LICENSE](LICENSE) per i dettagli.

## 👥 Team

- Nome Cognome - Lead Developer
- Nome Cognome - Frontend Developer
- Nome Cognome - Backend Developer

## 📞 Supporto

Per supporto, email support@bostarter.it o apri un issue su GitHub.

## 🏗️ Architettura Ottimizzata

### 📁 Componenti Condivisi
```
frontend/assets/shared/
├── css/
│   └── common-styles.css      # Stili unificati (card, filtri, animazioni)
├── js/
│   ├── common-functions.js    # Funzioni JavaScript centralizzate  
│   └── category-config.js     # Configurazioni per 15 categorie
```

### 🎯 Benefici Architettura
- **-70% codice duplicato** rimosso
- **Performance superiori** con componenti condivisi
- **Manutenzione semplificata** con single source of truth
- **Scalabilità migliorata** per nuove funzionalità
- **UX consistente** tra tutte le categorie

### 📋 Categorie Supportate
🎨 Arte | 🛠️ Artigianato | 🍽️ Cibo | 💃 Danza | 🎨 Design
📚 Editoriale | 🎬 Film | 📷 Fotografia | 📖 Fumetti | 🎮 Giochi  
📰 Giornalismo | 👗 Moda | 🎵 Musica | 🎭 Teatro | 💻 Tecnologia