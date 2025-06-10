# 🚀 BOSTARTER - Guida per Sviluppatori

> **La piattaforma di crowdfunding italiana più umana del web!**

Benvenuto nella guida completa per lavorare con il codice di BOSTARTER. Questa documentazione ti aiuterà a capire come il nostro codice è organizzato e come contribuire al progetto.

## 📚 Indice

- [🎯 Filosofia del Codice](#filosofia-del-codice)
- [🏗️ Architettura](#architettura)
- [🔧 Setup Ambiente di Sviluppo](#setup-ambiente)
- [📝 Convenzioni di Nomenclatura](#convenzioni-nomenclatura)
- [🎨 Frontend](#frontend)
- [⚙️ Backend](#backend)
- [🔒 Sicurezza](#sicurezza)
- [📊 Database](#database)
- [🧪 Testing](#testing)
- [🚀 Deploy](#deploy)

---

## 🎯 Filosofia del Codice

Il codice di BOSTARTER segue una filosofia semplice: **deve essere scritto come se lo leggesse un essere umano, non una macchina**.

### Principi Fondamentali

1. **🇮🇹 Italiano Prima di Tutto**: Nomi di variabili, metodi e commenti in italiano
2. **🧠 Leggibilità Umana**: Il codice si legge come una storia
3. **🔒 Sicurezza Built-in**: Ogni funzione pensa alla sicurezza
4. **⚡ Performance Smart**: Ottimizzazioni intelligenti, non premature
5. **🤝 Compatibilità**: Alias per il codice esistente durante la migrazione

### Esempio di Codice "Umano"

```php
// ❌ PRIMA (robotico)
public function getUserById($id) {
    $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ✅ DOPO (umano)
/**
 * Trova un utente specifico usando il suo ID
 * 
 * È come cercare una persona nell'elenco telefonico:
 * hai il numero (ID) e vuoi sapere chi è
 */
public function trovaUtentePerId($idUtente) {
    try {
        $ricercaUtente = $this->connessioneDatabase->prepare("
            SELECT * FROM utenti WHERE id = ?
        ");
        
        $ricercaUtente->execute([$idUtente]);
        $utenteFound = $ricercaUtente->fetch(\PDO::FETCH_ASSOC);
        
        if ($utenteFound) {
            return [
                'stato' => 'trovato',
                'utente' => $utenteFound,
                'messaggio' => 'Utente trovato con successo!'
            ];
        }
        
        return [
            'stato' => 'non_trovato',
            'messaggio' => 'Nessun utente con questo ID'
        ];
        
    } catch (\Exception $errore) {
        return [
            'stato' => 'errore',
            'messaggio' => 'Problemi nel cercare l\'utente: ' . $errore->getMessage()
        ];
    }
}
```

---

## 🏗️ Architettura

BOSTARTER utilizza un'architettura **MVC (Model-View-Controller)** moderna con elementi di **microservizi**.

```
BOSTARTER/
├── 🎨 frontend/          # Interfaccia utente (HTML, CSS, JS)
│   ├── css/             # Stili del sito
│   ├── js/              # JavaScript moderno (ES6+)
│   ├── assets/          # Immagini, font, categorie
│   └── auth/            # Pagine di login/registrazione
│
├── ⚙️ backend/           # Logica del server (PHP)
│   ├── controllers/     # Controller per le richieste
│   ├── models/          # Modelli per i dati
│   ├── services/        # Servizi business logic
│   ├── api/             # Endpoint REST API
│   ├── config/          # Configurazioni
│   ├── middleware/      # Middleware di sicurezza
│   └── utils/           # Utilità varie
│
├── 🗄️ database/         # Schema e migrazioni DB
└── 📊 logs/             # File di log
```

### 🔄 Flusso delle Richieste

1. **Frontend** → L'utente clicca qualcosa
2. **JavaScript** → Cattura l'evento e fa richiesta AJAX
3. **API Endpoint** → Riceve la richiesta e valida i dati
4. **Controller** → Gestisce la logica di business
5. **Service** → Operazioni complesse e comunicazione con DB
6. **Model** → Interazione diretta con il database
7. **Response** → Ritorna i dati in formato JSON

---

## 🔧 Setup Ambiente di Sviluppo

### Requisiti Sistema

- **PHP 8.0+** con estensioni: `pdo_mysql`, `json`, `openssl`, `mbstring`
- **MySQL 8.0+** o **MariaDB 10.4+**
- **Composer** per la gestione dipendenze PHP
- **Node.js 16+** per build tools frontend
- **XAMPP** o **WAMP** per sviluppo locale

### 🚀 Quick Start

```bash
# 1. Clone del progetto
git clone https://github.com/yourusername/bostarter.git
cd bostarter

# 2. Setup backend dependencies
cd backend
composer install

# 3. Configurazione database
cp config/config.example.php config/config.php
# Modifica le credenziali database in config.php

# 4. Importa il database
mysql -u root -p bostarter < ../database/bostarter_schema_compliant.sql

# 5. Avvia XAMPP e apri il progetto
# http://localhost/bostarter/frontend/
```

### 🔧 Configurazione IDE

Per **VS Code**, installa queste estensioni:

- **PHP Intelephense** - Autocompletamento PHP
- **Prettier** - Formattazione codice
- **Italian Language Pack** - Interfaccia in italiano
- **Thunder Client** - Test API
- **MySQL** - Gestione database

---

## 📝 Convenzioni di Nomenclatura

### 🇮🇹 Nomi in Italiano

| Tipo | Convenzione | Esempio |
|------|-------------|---------|
| **Classi** | PascalCase italiano | `GestoreUtenti`, `ServizioNotifiche` |
| **Metodi** | camelCase italiano | `registraNuovoUtente()`, `inviaEmail()` |
| **Variabili** | camelCase italiano | `$nomeUtente`, `$elencoProgetti` |
| **Costanti** | SNAKE_CASE | `NUMERO_MASSIMO_TENTATIVI` |
| **Database** | snake_case | `nome_utente`, `data_creazione` |

### 📁 Struttura File

```php
<?php
namespace BOSTARTER\Controllers;

/**
 * GESTORE ESEMPIO BOSTARTER
 * 
 * Breve descrizione di cosa fa questa classe.
 * Puoi usare metafore della vita reale per spiegare!
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Versione umana
 */

class GestoreEsempio 
{
    // === PROPRIETÀ DELLA CLASSE ===
    private $connessioneDatabase;
    private $configurazioneServizio;
    
    /**
     * Costruttore - Prepara il nostro gestore
     */
    public function __construct($database) {
        $this->connessioneDatabase = $database;
    }
    
    /**
     * Metodo principale che fa qualcosa di importante
     * 
     * Spiegazione umana di cosa fa questo metodo.
     * Usa metafore se aiuta a capire!
     * 
     * @param string $parametroImportante Cosa rappresenta questo parametro
     * @return array Cosa ritorna questo metodo
     */
    public function faQualcosaImportante($parametroImportante) {
        // Implementazione...
    }
}

// Alias per compatibilità con codice esistente
class_alias('BOSTARTER\Controllers\GestoreEsempio', 'ExampleController');
```

---

## 🎨 Frontend

Il nostro frontend è **mobile-first** e utilizza tecnologie moderne.

### 🛠️ Tecnologie Utilizzate

- **HTML5 Semantico** - Struttura accessibile
- **CSS3 + Tailwind** - Stili moderni e responsive
- **JavaScript ES6+** - Codice moderno e modulare
- **PWA Ready** - Funziona offline

### 🎯 Struttura JavaScript

```javascript
/**
 * GESTORE DASHBOARD BOSTARTER
 * 
 * Questa classe si occupa di gestire la dashboard dell'utente.
 * È come il cruscotto di un'auto: mostra tutte le informazioni
 * importanti e permette di controllare le funzioni principali.
 */

class GestoreDashboard {
    /**
     * Costruttore - Prepara la dashboard
     */
    constructor() {
        this.elementoDashboard = document.getElementById('dashboard');
        this.datiUtente = null;
        this.intervalloAggiornamento = null;
        
        // Bind dei metodi per evitare problemi di this
        this.aggiornaDati = this.aggiornaDati.bind(this);
        this.gestisciErrore = this.gestisciErrore.bind(this);
    }
    
    /**
     * Inizializza la dashboard
     * 
     * È come accendere l'auto: tutto si avvia e inizia a funzionare
     */
    async inizializza() {
        try {
            await this.caricaDatiUtente();
            this.impostaEventListeners();
            this.avviaAggiornamentoAutomatico();
            
            console.log('✅ Dashboard inizializzata con successo!');
        } catch (errore) {
            this.gestisciErrore('Errore nell\'inizializzazione dashboard', errore);
        }
    }
}

// Inizializzazione automatica quando la pagina è pronta
document.addEventListener('DOMContentLoaded', () => {
    const dashboard = new GestoreDashboard();
    dashboard.inizializza();
});
```

### 🎨 CSS Organizzato

```css
/**
 * STILI DASHBOARD BOSTARTER
 * 
 * Stili per la dashboard utente.
 * Organizzati in sezioni logiche per facilità di manutenzione.
 */

/* ====================================================================
   LAYOUT PRINCIPALE DASHBOARD
   ==================================================================== */

.dashboard-container {
    display: grid;
    grid-template-columns: 250px 1fr; /* Sidebar fissa + contenuto fluido */
    grid-template-rows: 60px 1fr; /* Header fisso + contenuto scrollabile */
    min-height: 100vh; /* Occupa tutta l'altezza schermo */
    background: var(--colore-sfondo-app); /* Colore di sfondo dell'app */
}

/* ====================================================================
   SIDEBAR NAVIGAZIONE
   ==================================================================== */

.dashboard-sidebar {
    grid-column: 1;
    grid-row: 1 / -1; /* Si estende per tutta l'altezza */
    background: var(--colore-sidebar);
    border-right: 1px solid var(--colore-bordo-sottile);
    padding: var(--spaziatura-media);
}
```

---

## ⚙️ Backend

Il backend è organizzato in **layer separati** per facilitare manutenzione e testing.

### 🏛️ Architettura Layer

```
Controller (Gestisce richieste HTTP)
    ↓
Service (Logica di business)
    ↓
Model (Accesso ai dati)
    ↓
Database (Persistenza)
```

### 📚 Esempio Completo di Flusso

```php
// 1. CONTROLLER - frontend/api/utenti.php
<?php
$controller = new GestoreUtentiController($database);
$risultato = $controller->registraNuovoUtente($_POST);
echo json_encode($risultato);

// 2. CONTROLLER - backend/controllers/UserController.php
class GestoreUtentiController {
    public function registraNuovoUtente($datiForm) {
        // Validazione e sanitizzazione
        $datiPuliti = $this->validaDatiRegistrazione($datiForm);
        
        // Chiama il service per la logica di business
        return $this->servizioUtenti->creaAccount($datiPuliti);
    }
}

// 3. SERVICE - backend/services/UserService.php
class ServizioUtenti {
    public function creaAccount($datiUtente) {
        // Logica di business: controlli avanzati, hash password, etc.
        $passwordHashata = password_hash($datiUtente['password'], PASSWORD_ARGON2ID);
        
        // Chiama il model per salvare
        return $this->modelloUtenti->salvaNuovoUtente($datiUtente);
    }
}

// 4. MODEL - backend/models/UserModel.php
class GestoreUtenti {
    public function salvaNuovoUtente($datiUtente) {
        // Accesso diretto al database
        $statement = $this->database->prepare("INSERT INTO utenti...");
        return $statement->execute($datiUtente);
    }
}
```

---

## 🔒 Sicurezza

La sicurezza è **integrata in ogni livello** dell'applicazione.

### 🛡️ Checklist Sicurezza

- ✅ **Validazione Input**: Tutti i dati vengono validati
- ✅ **Sanitizzazione**: HTML entities e escape SQL
- ✅ **Rate Limiting**: Prevenzione attacchi brute force
- ✅ **CSRF Protection**: Token per forms
- ✅ **SQL Injection**: Prepared statements sempre
- ✅ **XSS Protection**: Output escaping
- ✅ **Session Security**: Configurazione sicura
- ✅ **Password Hashing**: Argon2ID

### 🔐 Esempi Pratici

```php
// ✅ VALIDAZIONE SICURA
public function validaEmailUtente($email) {
    // 1. Rimuovi spazi
    $email = trim($email);
    
    // 2. Valida formato
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valido' => false, 'errore' => 'Email non valida'];
    }
    
    // 3. Controlla lunghezza
    if (strlen($email) > 254) {
        return ['valido' => false, 'errore' => 'Email troppo lunga'];
    }
    
    // 4. Sanitizza per output
    $emailSicura = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    
    return ['valido' => true, 'email' => $emailSicura];
}

// ✅ QUERY SICURA
public function trovaUtentePerEmail($email) {
    // MAI usare concatenazione stringhe!
    $statement = $this->database->prepare("
        SELECT id, nome, email FROM utenti 
        WHERE email = ? AND attivo = 1
    ");
    
    $statement->execute([$email]);
    return $statement->fetch(\PDO::FETCH_ASSOC);
}
```

---

## 📊 Database

Il database segue le specifiche del **progetto accademico** con ottimizzazioni per performance.

### 🗄️ Tabelle Principali

```sql
-- Utenti del sistema
CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    nickname VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Hash Argon2ID
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Progetti di crowdfunding
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    goal_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0,
    category ENUM('hardware', 'software') NOT NULL, -- Solo categorie conformi PDF
    status ENUM('draft', 'active', 'funded', 'failed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NOT NULL,
    FOREIGN KEY (creator_id) REFERENCES utenti(id)
);
```

### 🚀 Query Ottimizzate

```sql
-- ✅ BUONA: Con indici e limit
SELECT p.id, p.title, p.current_amount, u.nickname as creator
FROM projects p 
JOIN utenti u ON p.creator_id = u.id 
WHERE p.status = 'active' 
AND p.category = 'hardware'
ORDER BY p.created_at DESC 
LIMIT 10;

-- ❌ CATTIVA: Senza limiti e inefficiente
SELECT * FROM projects WHERE description LIKE '%tech%';
```

---

## 🧪 Testing

### 🎯 Strategia di Test

1. **Unit Tests** - Funzioni singole
2. **Integration Tests** - Moduli insieme
3. **API Tests** - Endpoint funzionanti
4. **UI Tests** - Interfaccia utente

### 🔬 Esempio Test

```php
// tests/UserServiceTest.php
class TestServizioUtenti extends PHPUnit\Framework\TestCase 
{
    /**
     * Testa che la registrazione di un nuovo utente funzioni correttamente
     */
    public function testRegistrazioneNuovoUtenteSuccesso() {
        // Arrange (Prepara)
        $datiUtente = [
            'email' => 'test@example.com',
            'nickname' => 'testuser',
            'password' => 'Password123!',
            'nome' => 'Mario',
            'cognome' => 'Rossi'
        ];
        
        // Act (Esegui)
        $risultato = $this->servizioUtenti->registraNuovoUtente($datiUtente);
        
        // Assert (Verifica)
        $this->assertTrue($risultato['successo']);
        $this->assertArrayHasKey('id_utente', $risultato);
        $this->assertEquals('Utente registrato con successo!', $risultato['messaggio']);
    }
}
```

---

## 🚀 Deploy

### 🌍 Ambiente di Produzione

```bash
# 1. Preparazione file
composer install --no-dev --optimize-autoloader
npm run build

# 2. Configurazione database
mysql -u user -p database < database/bostarter_schema_compliant.sql

# 3. Permessi file
chmod 755 frontend/
chmod 644 frontend/*.php
chmod 600 backend/config/config.php

# 4. SSL e sicurezza
# Configura HTTPS, headers di sicurezza, firewall
```

### 📋 Checklist Pre-Deploy

- ✅ Tutte le password cambiate
- ✅ Debug mode disabilitato
- ✅ Logs configurati
- ✅ Backup database
- ✅ SSL certificato installato
- ✅ Monitoraggio attivo

---

## 💡 Tips per Sviluppatori

### 🔥 Best Practices

1. **Commenta come se spiegassi a tua nonna** - Se tua nonna non capisce, riscrivi
2. **Usa metafore della vita reale** - "È come aprire una porta con la chiave"
3. **Un metodo = una responsabilità** - Fai una cosa e falla bene
4. **Nomi che spiegano** - `calcolaSconto()` è meglio di `calc()`
5. **Gestisci sempre gli errori** - Le cose possono andare storte

### 🎨 Metafore Utili

- **Database** = Biblioteca con schedari
- **API** = Cameriere che porta ordinazioni
- **Cache** = Memory del cervello umano
- **Session** = Documento d'identità digitale
- **Validation** = Controllo documenti ai confini
- **Encryption** = Lettera in codice segreto

### 🐛 Debug Tips

```php
// ✅ LOGGING UTILE
error_log("🔍 Debug: Cercando utente con email: {$email}");
error_log("📊 Query result: " . json_encode($risultato));
error_log("⚠️ Errore validazione: " . $errore->getMessage());

// ✅ EXCEPTION HANDLING
try {
    $risultato = $this->operazioneRischiosa();
} catch (DatabaseException $e) {
    error_log("💥 Errore database: " . $e->getMessage());
    return ['stato' => 'errore', 'messaggio' => 'Problema di connessione al database'];
} catch (ValidationException $e) {
    error_log("⚠️ Errore validazione: " . $e->getMessage());
    return ['stato' => 'errore', 'messaggio' => 'I dati inseriti non sono corretti'];
} catch (Exception $e) {
    error_log("❌ Errore generico: " . $e->getMessage());
    return ['stato' => 'errore', 'messaggio' => 'Si è verificato un errore imprevisto'];
}
```

---

## 🤝 Come Contribuire

1. **Fork** del repository
2. **Branch** per la tua feature: `git checkout -b feature/nuova-funzionalita`
3. **Commenta** tutto in italiano con metafore
4. **Testa** la tua modifica
5. **Pull Request** con descrizione dettagliata

### 📝 Template Commit

```
✨ Aggiungi: [cosa hai aggiunto]

🔧 Modifica: [cosa hai modificato]

🐛 Correggi: [cosa hai sistemato]

📝 Documenta: [cosa hai documentato]

♻️ Refactor: [cosa hai migliorato]
```

---

## 📞 Supporto

- **📧 Email**: <dev@bostarter.it>
- **💬 Discord**: #sviluppatori
- **📖 Wiki**: github.com/bostarter/wiki
- **🐛 Issues**: github.com/bostarter/issues

---

**Buono sviluppo! 🚀**

*Il team BOSTARTER*
