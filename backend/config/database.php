<?php
/**
 * Configurazione Database BOSTARTER
 * Singleton pattern per connessione MySQL
 */

require_once __DIR__ . '/app_config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $charset = 'utf8mb4';

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    // Prevenire clonazione
    private function __clone() {}

    // Prevenire unserialize
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Classe helper per operazioni database comuni
 */
class DatabaseHelper {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Esegue stored procedure con parametri
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
