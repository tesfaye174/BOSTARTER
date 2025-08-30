<?php
/**
 * =====================================================
 * BOSTARTER - GESTORE DATABASE SINGLETON
 * =====================================================
 * 
 * Implementa il pattern Singleton per la gestione
 * centralizzata delle connessioni al database MySQL.
 * 
 * @author BOSTARTER Team
 * @version 2.0
 * @description Singleton per connessioni database sicure
 */

// Importa le configurazioni dell'applicazione
require_once __DIR__ . '/app_config.php';

/**
 * Classe Database - Pattern Singleton
 * 
 * Gestisce le connessioni al database MySQL con PDO
 * garantendo una sola istanza attiva per applicazione.
 */
class Database {
    /** @var Database|null Istanza singleton della classe */
    private static $instance = null;
    
    /** @var PDO Connessione PDO al database */
    private $connection;
    
    /** @var string Charset utilizzato per la connessione */
    private $charset = 'utf8mb4';

    /**
     * Costruttore privato - Pattern Singleton
     * 
     * Stabilisce la connessione al database MySQL con
     * configurazioni ottimizzate per prestazioni e sicurezza.
     * 
     * @throws PDOException Se la connessione fallisce
     */
    private function __construct() {
        // Costruisce la stringa DSN per MySQL
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=$this->charset";
        
        // Opzioni PDO per prestazioni e sicurezza ottimali
        $options = [
            // Gestione errori con eccezioni
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Modalità fetch predefinita (array associativo)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Disabilita l'emulazione delle prepared statements
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Restituisce il numero di righe trovate invece di quelle modificate
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ];

        try {
            // Stabilisce la connessione PDO
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Log della connessione riuscita (solo in debug)
            if (isDebugMode()) {
                logMessage('DEBUG', 'Connessione database stabilita con successo');
            }
        } catch (PDOException $e) {
            // Log dell'errore di connessione
            logMessage('ERROR', 'Errore connessione database: ' . $e->getMessage());
            throw new PDOException('Impossibile connettersi al database: ' . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Restituisce l'istanza singleton della classe Database
     * 
     * Se l'istanza non esiste ancora, la crea automaticamente.
     * Garantisce che esista sempre una sola connessione al database.
     * 
     * @return Database L'istanza singleton
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Restituisce la connessione PDO attiva
     * 
     * @return PDO La connessione PDO al database
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Impedisce la clonazione dell'istanza singleton
     * 
     * @throws Exception Se si tenta di clonare l'istanza
     */
    private function __clone() {
        throw new Exception('Clonazione della classe Database non permessa - Pattern Singleton');
    }

    /**
     * Impedisce la deserializzazione dell'istanza singleton
     * 
     * @throws Exception Se si tenta di deserializzare l'istanza
     */
    public function __wakeup() {
        throw new Exception('Deserializzazione della classe Database non permessa - Pattern Singleton');
    }

    /**
     * Testa la connessione al database
     * 
     * @return bool True se la connessione è attiva, false altrimenti
     */
    public function testConnection(): bool {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            logMessage('WARNING', 'Test connessione database fallito: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Chiude la connessione al database
     * 
     * Utile per liberare risorse quando l'applicazione termina.
     */
    public function closeConnection(): void {
        $this->connection = null;
        self::$instance = null;
        
        if (isDebugMode()) {
            logMessage('DEBUG', 'Connessione database chiusa');
        }
    }
}

/**
 * Helper per operazioni database comuni
 */
class DatabaseHelper {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Esegue stored procedure
     */
    public function callStoredProcedure($procedureName, $params = []) {
        $placeholders = str_repeat('?,', count($params) - 1) . '?';
        $sql = "CALL $procedureName($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }

    /**
     * Esegue stored procedure con parametri OUT
     */
    public function callStoredProcedureWithOut($procedureName, $inParams, $outParams) {
        // Prepara placeholders
        $allParams = array_merge($inParams, array_fill(0, count($outParams), '@var'));
        $placeholders = implode(',', array_fill(0, count($allParams), '?'));
        
        // Sostituisce gli ultimi ? con @variabili per OUT
        $paramList = [];
        $outIndex = 0;
        for ($i = 0; $i < count($allParams); $i++) {
            if ($i < count($inParams)) {
                $paramList[] = '?';
            } else {
                $paramList[] = '@' . $outParams[$outIndex];
                $outIndex++;
            }
        }
        
        $sql = "CALL $procedureName(" . implode(',', $paramList) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($inParams);
        
        // Recupera valori OUT
        $outValues = [];
        foreach ($outParams as $param) {
            $result = $this->db->query("SELECT @$param as value")->fetch();
            $outValues[$param] = $result['value'];
        }
        
        return $outValues;
    }

    /**
     * Ottiene dati da vista
     */
    public function getViewData($viewName, $limit = null) {
        $sql = "SELECT * FROM $viewName";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Controlla se una tabella esiste
     */
    public function tableExists($tableName) {
        $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Ottiene schema di una tabella
     */
    public function getTableSchema($tableName) {
        $stmt = $this->db->prepare("DESCRIBE $tableName");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
