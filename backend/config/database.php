<?php
/**
 * BOSTARTER - Gestore Database Singleton
 *
 * Pattern Singleton per connessione MySQL PDO
 */

// Importa configurazioni
require_once __DIR__ . '/app_config.php';

/**
 * Classe Database - Pattern Singleton
 */
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $this->connection = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Could not connect to MySQL database');
        }
    }

    /**
     * Restituisce istanza singleton
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }

    private function __clone() {
        throw new Exception('Clonazione non permessa');
    }

    public function __wakeup() {
        throw new Exception('Deserializzazione non permessa');
    }

    public function testConnection(): bool {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log('Test connessione fallito: ' . $e->getMessage());
            return false;
        }
    }

    public function closeConnection(): void {
        $this->connection = null;
        self::$instance = null;
    }
}

/**
 * Helper per operazioni database comuni
 */
class DatabaseHelper {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
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
        $allParams = array_merge($inParams, array_fill(0, count($outParams), '@var'));

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
        $stmt = $this->db->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = ?)");
        $stmt->execute([$tableName]);
        return $stmt->fetchColumn() === '1';
    }

    /**
     * Ottiene schema di una tabella
     */
    public function getTableSchema($tableName) {
        $stmt = $this->db->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ?");
        $stmt->execute([$tableName]);
        return $stmt->fetchAll();
    }
}