<?php

namespace BOSTARTER\Backend\Utils;

use PDO;
use PDOException;

// Include il file di configurazione una sola volta
require_once __DIR__ . '/../../config/config.php';

class Database {
    private static $instance = null;
    private $conn;

    // Dettagli connessione DB (letti dalla configurazione)
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;

    private function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=' . $this->charset;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // In un'applicazione reale, loggare l'errore e mostrare un messaggio generico
            error_log('Errore connessione DB: ' . $e->getMessage());
            // Potresti voler terminare l'esecuzione o gestire l'errore in modo più robusto
            die('Errore di connessione al database. Si prega di riprovare più tardi.');
        }
    }

    /**
     * Ottiene l'istanza Singleton della classe Database.
     *
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Ottiene l'oggetto connessione PDO.
     *
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->conn;
    }

    // Impedisce la clonazione dell'istanza (Singleton)
    private function __clone() {}

    // Impedisce la deserializzazione dell'istanza (Singleton)
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
?>