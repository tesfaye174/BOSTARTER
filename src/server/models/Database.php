<?php

namespace Models;

// Classe singleton per la gestione della connessione al database
class Database {
    // Istanza singleton
    private static $instance = null;
    // Connessione PDO
    private $connection = null;
    // Configurazione del database
    private $config = [
        'host' => 'localhost',
        'dbname' => 'bostarter',
        'username' => 'root',
        'password' => ''
    ];
    // Numero massimo di tentativi di connessione
    private $maxRetries = 3;
    // Ritardo tra i tentativi
    private $retryDelay = 1; // seconds

    // Costruttore privato: inizializza la connessione
    private function __construct() {
        $this->connect();
    }

    // Restituisce l'istanza singleton
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Connessione al database con tentativi multipli
    private function connect() {
        $retries = 0;
        while ($retries < $this->maxRetries) {
            try {
                $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset=utf8mb4";
                $this->connection = new \PDO(
                    $dsn,
                    $this->config['username'],
                    $this->config['password'],
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                return;
            } catch (\PDOException $e) {
                $retries++;
                if ($retries >= $this->maxRetries) {
                    throw new \Exception("Database connection failed after {$this->maxRetries} attempts: " . $e->getMessage());
                }
                sleep($this->retryDelay);
            }
        }
    }

    // Esegue una query parametrizzata
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception("Query execution failed: " . $e->getMessage());
        }
    }

    // Avvia una transazione
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    // Conferma una transazione
    public function commit() {
        return $this->connection->commit();
    }

    // Annulla una transazione
    public function rollback() {
        return $this->connection->rollback();
    }

    // Restituisce l'ultimo ID inserito
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    // Distruttore: chiude la connessione
    public function __destruct() {
        $this->connection = null;
    }
}