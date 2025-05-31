<?php
/**
 * Utility per il database BOSTARTER
 * Questo file contiene funzioni di utilità per interagire con il database utilizzando PDO.
 * Le funzioni specifiche di mysqli e le definizioni delle costanti del database sono state rimosse.
 * Fare riferimento a config/config.php per le costanti del database e config/database.php per la classe Database.
 */

require_once __DIR__ . '/../config/database.php'; // Assicura che la classe Database sia caricata

/**
 * Verifica se una tabella esiste nel database
 * @param string $table Nome della tabella
 * @return bool True se la tabella esiste, false altrimenti
 */
function tableExists($table) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // La query varia leggermente a seconda del RDBMS, questa è per MySQL
        $stmt = $conn->prepare("SHOW TABLES LIKE :table");
        $stmt->bindParam(':table', $table, PDO::PARAM_STR);
        $stmt->execute();
        
        return ($stmt->rowCount() > 0);
    } catch (PDOException $e) {
        // Logga l'errore o gestiscilo come preferisci
        error_log("Errore in tableExists: " . $e->getMessage());
        return false;
    }
}

/**
 * Inizializza il database se non esiste o è vuoto.
 * Questa funzione è simile a quella in init_db.php ma può essere usata programmaticamente.
 * ATTENZIONE: Potenzialmente distruttiva se il database esiste e lo schema viene applicato.
 */
function initDatabase() {
    try {
        $db_host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $db_user = defined('DB_USER') ? DB_USER : 'root';
        $db_pass = defined('DB_PASS') ? DB_PASS : '';
        $db_name = defined('DB_NAME') ? DB_NAME : 'bostarter';
        $db_charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

        // Connessione al server MySQL per creare il database se non esiste
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET $db_charset COLLATE utf8mb4_unicode_ci;");
        
        // Connessione al database specifico
        $db = new Database(); // Usa la classe Database per la connessione effettiva al db bostarter
        $conn = $db->getConnection();
        
        // Verifica se il database è vuoto (nessuna tabella)
        $stmt = $conn->query("SHOW TABLES");
        if ($stmt->rowCount() == 0) {
            // Il database è vuoto, esegui lo script di inizializzazione
            $sqlFile = __DIR__ . '/bostarter_schema.sql';
            
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $conn->exec($sql); // Esegui le query multiple
                return true;
            } else {
                error_log('File di schema del database non trovato: ' . $sqlFile);
                return false;
            }
        }
        // Database già esistente e con tabelle, non fare nulla.
        return true; 
    } catch (PDOException $e) {
        error_log('Errore nell\'inizializzazione del database: ' . $e->getMessage());
        return false;
    }
}

// Esempio di chiamata (commentata di default)
// if (initDatabase()) {
//     echo "Database inizializzato o già esistente.\n";
// } else {
//     echo "Errore durante l'inizializzazione del database.\n";
// }

?>