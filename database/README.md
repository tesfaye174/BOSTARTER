# 🚀 BOSTARTER - Database Setup v2.0

## 📋 Panoramica
Cartella database ottimizzata per la piattaforma di crowdfunding BOSTARTER.
**Tutti i file sono stati consolidati per una gestione più semplice e pulita.**

## 📁 Struttura Ottimizzata
```
database/
├── 📄 README.md              # Questa documentazione
├── 🔧 simple_install.php     # Installer web automatico
└── 📊 bostarter_install.sql  # Schema completo unificato
```

## ⚡ Installazione Rapida

### 🎯 Metodo Raccomandato: Installer Web
1. **Avvia XAMPP** e assicurati che MySQL sia attivo
2. **Apri nel browser:** `http://localhost/BOSTARTER/database/simple_install.php`
3. **Clicca "Installa"** e attendi il completamento automatico
4. **Usa le credenziali** mostrate per accedere

### 🛠️ Metodo Alternativo: SQL Manuale
```sql
-- 1. Crea database
CREATE DATABASE bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Importa schema unificato
USE bostarter;
source bostarter_install.sql;
```

## 🗄️ Schema Database

### 📊 Tabelle Principali
| Tabella | Descrizione | Record Tipo |
|---------|-------------|-------------|
| `utenti` | Account utenti e profili | ~1000 |
| `progetti` | Progetti di crowdfunding | ~50 |
| `finanziamenti` | Transazioni di finanziamento | ~200 |
| `competenze` | Catalogo competenze/skills | ~50 |
| `candidature` | Candidature ai progetti | ~100 |
| `ricompense` | Ricompense progetti | ~150 |
| `commenti` | Commenti sui progetti | ~300 |

### 🔐 Account Amministratore Predefinito
```
📧 Email:    admin@bostarter.com
🔒 Password: admin123
🎫 Codice:   ADMIN2025
👤 Ruolo:    Amministratore
```

### 🎯 Caratteristiche Schema
- ✅ **Encoding UTF-8** completo
- ✅ **Chiavi esterne** per integrità referenziale
- ✅ **Indici ottimizzati** per performance
- ✅ **Trigger automatici** per logica business
- ✅ **Viste pre-configurate** per query comuni
- ✅ **Competenze base** pre-caricate
- ✅ **Validazioni dati** integrate

## ⚙️ Configurazione Database

### 📝 File Configurazione
Aggiorna `../backend/config/app_config.php`:

```php
// Configurazione Database BOSTARTER
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Cambia se necessario
define('DB_PASS', '');               // Inserisci password MySQL
define('DB_NAME', 'bostarter');
```

### 🔧 Verifica Installazione
```sql
-- Test connessione e dati
USE bostarter;
SHOW TABLES;
SELECT COUNT(*) as 'Utenti' FROM utenti;
SELECT COUNT(*) as 'Competenze' FROM competenze;
SELECT nickname, email, tipo_utente FROM utenti WHERE tipo_utente = 'amministratore';
```

## 🚨 Risoluzione Problemi

### ❌ Errori Comuni

| Problema | Causa Possibile | Soluzione |
|----------|-----------------|----------|
| **Connessione Fallita** | MySQL non avviato | Avvia servizio MySQL in XAMPP |
| **Permessi Negati** | Utente senza privilegi | Usa root o crea utente con privilegi CREATE |
| **Database Esistente** | Conflitto nomi | Elimina DB esistente o cambia nome |
| **Encoding Errato** | Charset non UTF-8 | Assicurati di usare utf8mb4 |
| **File non Trovato** | Path errato | Verifica percorso file SQL |

### 🔍 Debug Avanzato
```bash
# Verifica servizi XAMPP
netstat -an | findstr :3306

# Test connessione MySQL
mysql -u root -p -h localhost

# Verifica privilegi utente
SHOW GRANTS FOR 'root'@'localhost';
```

### 💡 Suggerimenti Performance
- **RAM:** Minimo 4GB per sviluppo
- **Storage:** 500MB liberi per database
- **MySQL Version:** 5.7+ raccomandato
- **PHP Version:** 7.4+ raccomandato

## 🧪 Testing e Sviluppo

### 📊 Dati di Esempio Inclusi
- ✅ **5 competenze base** (PHP, JavaScript, MySQL, HTML/CSS, Arduino)
- ✅ **1 amministratore** completo
- ✅ **Struttura completa** per test immediati
- ✅ **Indici ottimizzati** per query veloci

### 🔄 Reset Database
```sql
-- Reset completo (attenzione: cancella tutto!)
DROP DATABASE IF EXISTS bostarter;
-- Poi rilancia installer
```

## 📞 Supporto

### 🆘 In Caso di Problemi
1. **Verifica XAMPP** - MySQL deve essere verde
2. **Controlla log errori** in XAMPP Control Panel
3. **Testa phpMyAdmin** - http://localhost/phpmyadmin
4. **Ripeti installazione** - usa installer web

### 📚 Risorse Utili
- [Documentazione XAMPP](https://www.apachefriends.org/docs/)
- [MySQL Reference](https://dev.mysql.com/doc/)
- [PHP PDO Guide](https://www.php.net/manual/en/book.pdo.php)

---

**🏆 BOSTARTER Database v2.0** | Ottimizzato per semplicità e performance

*© 2025 BOSTARTER Team - Piattaforma Crowdfunding Italiana*
