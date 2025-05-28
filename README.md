# BOSTARTER - Piattaforma di Crowdfunding

BOSTARTER è una piattaforma di crowdfunding moderna e sicura per progetti creativi, sviluppata con PHP e tecnologie web moderne.

## 🚀 Caratteristiche Principali

- **Sistema di Autenticazione Sicuro**
  - Login/Registrazione con validazione
  - Gestione sessioni JWT
  - Protezione CSRF
  - Remember me functionality

- **Gestione Progetti**
  - Creazione e pubblicazione progetti
  - Sistema di ricompense
  - Tracking finanziamenti
  - Dashboard creatori

- **Frontend Moderno**
  - Design responsive con Tailwind CSS
  - Tema chiaro/scuro
  - PWA support
  - Animazioni fluide
  - Supporto multilingua

- **Sicurezza**
  - Validazione input
  - Hashing password
  - Protezione XSS
  - Rate limiting
  - Logging eventi

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
- Importa lo schema da `database/bostarter_schema.sql`
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