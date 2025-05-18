# Database BOSTARTER

Questo documento descrive la struttura e le funzionalità del database BOSTARTER, progettato per supportare la registrazione utenti, il login e la gestione dei creatori e dei loro progetti.

## Struttura del Database

Il database è organizzato nelle seguenti componenti principali:

- **Schema**: Definizione delle tabelle, indici e relazioni
- **Procedure**: Funzioni SQL per operazioni comuni
- **Trigger**: Automazioni per mantenere l'integrità dei dati
- **Viste**: Query predefinite per statistiche e report

## Tabelle Principali

- **Users**: Informazioni sugli utenti registrati
- **Creator_Users**: Estensione della tabella Users per i creatori
- **Projects**: Progetti creati dai creatori
- **Rewards**: Ricompense associate ai progetti
- **Funding**: Finanziamenti effettuati dagli utenti
- **Comments**: Commenti degli utenti sui progetti

## Procedure Stored

### Autenticazione e Gestione Utenti

- `register_user`: Registra un nuovo utente
- `login_user`: Autentica un utente esistente
- `check_creator_status`: Verifica se un utente è un creatore
- `register_creator`: Registra un utente come creatore

### Gestione Progetti

- `create_project`: Crea un nuovo progetto
- `add_project_reward`: Aggiunge una ricompensa a un progetto
- `publish_project`: Pubblica un progetto (cambia lo stato da bozza ad attivo)
- `get_creator_projects`: Ottiene tutti i progetti di un creatore
- `fund_project`: Finanzia un progetto

## Trigger

- `update_project_status_after_funding`: Aggiorna lo stato di un progetto quando raggiunge il budget
- `update_creator_reliability_after_project_completion`: Aggiorna l'affidabilità di un creatore quando un progetto viene completato
- `update_creator_total_funded_after_funding`: Aggiorna il totale finanziato di un creatore quando un progetto riceve un finanziamento
- `check_reward_availability_before_funding`: Verifica la disponibilità di una ricompensa prima di assegnarla

## Installazione

1. Assicurati che il database 'bostarter' esista:
   ```sql
   CREATE DATABASE IF NOT EXISTS bostarter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Esegui lo script di configurazione:
   ```bash
   mysql -u root -p < database/config.sql
   ```

3. Verifica che tutte le tabelle, procedure e trigger siano stati creati correttamente:
   ```sql
   SHOW TABLES;
   SHOW PROCEDURE STATUS WHERE Db = 'bostarter';
   SHOW TRIGGERS;
   ```

## Integrazione con il Frontend

Il database è progettato per integrarsi con il frontend attraverso le API del backend. Le procedure stored forniscono un'interfaccia coerente per le operazioni comuni, come la registrazione, il login e la gestione dei progetti.

### Esempio di Registrazione

```php
// Nel controller AuthController.php
public function register() {
    // ... validazione input ...
    
    // Chiamata alla procedura stored
    $stmt = $this->db->prepare("CALL register_user(?, ?, ?, ?, ?, ?, ?, @p_user_id, @p_success, @p_message)");
    $stmt->bindParam(1, $email);
    $stmt->bindParam(2, $nickname);
    $stmt->bindParam(3, $password_hash);
    $stmt->bindParam(4, $name);
    $stmt->bindParam(5, $surname);
    $stmt->bindParam(6, $birth_year, PDO::PARAM_INT);
    $stmt->bindParam(7, $birth_place);
    $stmt->execute();
    
    // Recupero dei risultati
    $result = $this->db->query("SELECT @p_user_id, @p_success, @p_message")->fetch(PDO::FETCH_ASSOC);
    
    // ... gestione della risposta ...
}
```

### Esempio di Creazione Progetto

```php
// Nel controller ProjectController.php
public function createProject() {
    // ... validazione input ...
    
    // Chiamata alla procedura stored
    $stmt = $this->db->prepare("CALL create_project(?, ?, ?, ?, ?, ?, @p_project_id, @p_success, @p_message)");
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $creator_id, PDO::PARAM_INT);
    $stmt->bindParam(3, $description);
    $stmt->bindParam(4, $budget);
    $stmt->bindParam(5, $project_type);
    $stmt->bindParam(6, $end_date);
    $stmt->execute();
    
    // Recupero dei risultati
    $result = $this->db->query("SELECT @p_project_id, @p_success, @p_message")->fetch(PDO::FETCH_ASSOC);
    
    // ... gestione della risposta ...
}
```

## Note sulla Sicurezza

- Tutte le password sono memorizzate come hash (utilizzando `password_hash()` in PHP)
- Le procedure stored validano i dati di input per prevenire SQL injection
- I trigger garantiscono l'integrità dei dati anche in caso di operazioni concorrenti

## Manutenzione

Per mantenere il database in buono stato:

1. Esegui backup regolari
2. Monitora le prestazioni delle query
3. Aggiorna gli indici in base ai pattern di accesso

---

Per ulteriori informazioni, consulta la documentazione completa del progetto BOSTARTER.