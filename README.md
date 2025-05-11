---
# 🚀 BOSTARTER

> **Bringing innovative ideas to life through community support and collaboration.**

[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)]()
[![License](https://img.shields.io/badge/license-MIT-blue)]()

**BOSTARTER** è una piattaforma crowdfunding per progetti hardware e software.

---

## 📋 Indice
1. [Panoramica](#panoramica)
2. [Funzionalità](#funzionalità)
3. [Architettura](#architettura)
4. [Implementazione](#implementazione)
5. [Setup](#setup)
6. [Struttura del progetto](#struttura-del-progetto)
7. [Contribuire](#contribuire)
8. [Licenza](#licenza)
9. [Supporto](#supporto)

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

## 💾 Implementazione

### Database (MySQL)
```sql
Users(
  id,
  email,
  nickname,
  password,
  name,
  surname,
  birth_year,
  birth_place
)
Projects(
  id,
  name,
  description,
  insert_date,
  photos,
  budget,
  deadline,
  status
)
Rewards(id, code, description, photo, project_id)
Skills(id, competency, level)
HardwareProjects(project_id, components)
SoftwareProjects(project_id, required_profiles)
CreatorUsers(user_id, project_count, reliability)
AdminUsers(user_id, security_code)
Funding(id, user_id, project_id, amount, date, reward_id)
Comments(id, user_id, project_id, date, text, response)
Candidatures(user_id, profile_id, project_id, status)
```

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

---

## 🗂️ Struttura del progetto

```
BOSTARTER/
├─ database/
│  ├─ schema/
│  ├─ procedures/
│  ├─ triggers/
│  ├─ views/
│  ├─ events/
│  └─ seed/
├─ backend/
│  ├─ controllers/
│  ├─ models/
│  ├─ services/
│  ├─ middleware/
│  └─ api/
├─ frontend/
│  ├─ css/
│  ├─ js/
│  ├─ templates/
│  └─ assets/
├─ config/
├─ public/
└─ logs/
```

---

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

