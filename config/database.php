<?php
/**
 * Configurazione del database per BOSTARTER
 * Questo file fornisce una classe per la connessione al database
 * utilizzando le impostazioni definite in config.php
 */

// Includi il file di configurazione principale
require_once __DIR__ . '/config.php';

/**
 * Classe per la gestione della connessione al database
 */
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $conn;
    
    /**
     * Costruttore che inizializza i parametri di connessione
     */
    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }
    
    /**
     * Ottiene una connessione al database
     * @return PDO Oggetto PDO per la connessione al database
     * @throws Exception Se la connessione fallisce
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch(PDOException $exception) {
            // Registra l'errore nel log
            log_error("Errore di connessione al database", [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
            
            // Lancia un'eccezione generica per non esporre dettagli sensibili
            throw new Exception("Connessione al database fallita");
        }
        
        return $this->conn;
    }
    
    /**
     * Chiude la connessione al database
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>