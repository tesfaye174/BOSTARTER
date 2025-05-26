---
# 🚀 BOSTARTER

[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)]()
[![License](https://img.shields.io/badge/license-MIT-blue)]()

---

## 📋 Indice
- [📋 Indice](#-indice)
- [🔍 Panoramica](#-panoramica)
- [🔑 Funzionalità](#-funzionalità)
  - [Sostenitori](#sostenitori)
  - [Creatori](#creatori)
  - [Sviluppatori](#sviluppatori)
- [🏗️ Architettura](#️-architettura)
  - [Utenti](#utenti)
  - [Progetti](#progetti)
  - [Analisi](#analisi)
  - [Funzionalità avanzate](#funzionalità-avanzate)
- [⚙️ Setup](#️-setup)
  - [Prerequisiti](#prerequisiti)
  - [Avvio rapido](#avvio-rapido)
- [🤝 Contribuire](#-contribuire)
- [📄 Licenza](#-licenza)
- [🛠️ Supporto](#️-supporto)

---

## 🔍 Panoramica
BOSTARTER connette creatori, sostenitori e sviluppatori.

- Creatori avviano campagne.
- Sostenitori finanziano e commentano.
- Sviluppatori offrono competenze.

---

## 🔑 Funzionalità

### Sostenitori
- Finanziano progetti a livelli di ricompensa.
- Commentano e seguono aggiornamenti.
- Guadagnano riconoscimenti.

### Creatori
- Pubblicano progetti hardware e software.
- Definiscono obiettivi e ricompense.
- Gestiscono la reputazione.

### Sviluppatori
- Candidano profili a progetti.
- Mostrano competenze.
- Collaborano con i creatori.

---

## 🏗️ Architettura

### Utenti
- **Standard**: profilo, competenze, cronologia.
- **Creatori**: progetti, affidabilità.
- **Amministratori**: configurazione piattaforma.

### Progetti
- **Hardware**: componenti e specifiche.
- **Software**: profili richiesti.
- **Ricompense**: livelli di premio.
- **Commenti**: feedback e risposte.

### Analisi
- Monitoraggio finanziamenti.
- Classifica creatori.
- Top sostenitori.

---

### Funzionalità avanzate
- Stored procedure per operazioni rapide.
- Trigger per aggiornare affidabilità.
- Eventi schedulati per scadenze.
- Logging eventi in MongoDB.

---

## ⚙️ Setup

### Prerequisiti
- PHP ≥ 7.4
- MySQL
- MongoDB
- Apache o Nginx

### Avvio rapido
```bash
git clone https://github.com/yourusername/bostarter.git
cd bostarter
mysql -u user -p < database/setup.sql
cp config/config.example.php config/config.php
# modifica credenziali in config.php
php -S localhost:8000 -t .
```
Apri `http://localhost:8000`


## 🤝 Contribuire
1. Fai fork del progetto.
2. Crea un branch (`feat/nome-funzione`).
3. Commetti le modifiche. Mantieni i messaggi brevi.
4. Apri una pull request.

---

## 📄 Licenza
Questo progetto è distribuito sotto licenza **MIT**. Vedi `LICENSE`.

---

## 🛠️ Supporto
Per problemi o idee, apri un issue su GitHub.

---

*© 2025 BOSTARTER Team*

BOSTARTER/
│
├── backend/
│   ├── api/                # Endpoint REST PHP (es: progetti.php, utenti.php, auth.php)
│   ├── auth/               # Login/registrazione (es: login.php, register.php)
│   ├── controllers/        # Logica di business (es: ProgettoController.php)
│   ├── models/             # Classi PHP per entità (es: Progetto.php, Utente.php)
│   ├── utils/              # Funzioni di utilità (es: db.php, jwt.php)
│   ├── config/             # Configurazione DB (database.php, config.php)
│   ├── logs/               # Log MongoDB (eventi_log.php)
│   └── index.php           # Router principale API REST
│
├── database/
│   ├── bostarter_schema.sql      # Schema MySQL
│   ├── bostarter_procedures.sql  # Stored procedure
│   ├── bostarter_views.sql       # Viste statistiche
│   ├── bostarter_triggers.sql    # Trigger
│   └── bostarter_events.sql      # Eventi MySQL
│
├── frontend/
│   ├── assets/             # Immagini, SVG, font, icone
│   ├── css/                # Bootstrap + custom CSS
│   ├── js/                 # JS custom (moduli, servizi, store, utilità)
│   │   ├── api/            # Chiamate API REST
│   │   ├── components/     # Componenti riutilizzabili (modali, card, navbar)
│   │   ├── features/       # Logica specifica per feature (progetti, auth, dashboard, ecc.)
│   │   ├── store/          # Stato globale (es: authStore.js, projectStore.js)
│   │   ├── utils/          # Funzioni di utilità (es: validators.js, helpers.js)
│   │   └── main.js         # Entry point JS
│   ├── images/             # Immagini progetti/utenti
│   ├── index.html          # Homepage
│   ├── dashboard.html      # Dashboard utente
│   ├── project.html        # Dettaglio progetto
│   ├── admin/              # Pagine e JS per admin
│   └── ...                 # Altre pagine (login, register, ecc.)
│
└── README.md