<?php
// Includiamo il file di configurazione principale
require_once __DIR__ . '/config.php';

/**
 * Classe per gestire la connessione al database MySQL
 * Implementa il pattern Singleton per assicurare una sola connessione attiva
 */
class Database {
    private static $istanza = null;  // L'unica istanza della classe
    private $connessione;            // La connessione PDO al database
    
    /**
     * Costruttore privato per impedire la creazione diretta di istanze
     * Stabilisce la connessione al database con le configurazioni ottimali
     */
    private function __construct() {
        try {
            // Creiamo la connessione PDO con parametri sicuri
            $this->connessione = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    // Abilita la gestione delle eccezioni per gli errori SQL
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // Imposta il tipo di fetch predefinito come array associativo
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Disabilita l'emulazione dei prepared statement per maggiore sicurezza
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $errore) {
            // Se c'è un problema con la connessione, lanciamo un'eccezione comprensibile
            throw new Exception("Non riesco a connettermi al database: " . $errore->getMessage());
        }
    }
    
    /**
     * Ottiene l'istanza unica della classe Database
     * Se non esiste ancora, la crea
     * 
     * @return Database L'istanza della classe
     */
    public static function getInstance() {
        if (self::$istanza === null) {
            self::$istanza = new self();
        }
        return self::$istanza;
    }
    
    /**
     * Restituisce la connessione PDO per eseguire query
     * 
     * @return PDO L'oggetto connessione
     */
    public function getConnection() {
        return $this->connessione;
    }
    
    /**
     * Inizia una transazione nel database
     * Utile quando dobbiamo eseguire più operazioni che devono avere successo tutte insieme
     * 
     * @return bool True se la transazione è iniziata correttamente
     */
    public function iniziaTransazione() {
        return $this->connessione->beginTransaction();
    }
    
    /**
     * Conferma tutte le operazioni della transazione corrente
     * 
     * @return bool True se il commit è andato a buon fine
     */
    public function confermaTransazione() {
        return $this->connessione->commit();
    }
    
    /**
     * Annulla tutte le operazioni della transazione corrente
     * 
     * @return bool True se il rollback è andato a buon fine
     */
    public function annullaTransazione() {
        return $this->connessione->rollBack();
    }
    
    /**
     * Ottiene l'ID dell'ultimo record inserito
     * 
     * @return string L'ID dell'ultimo insert
     */
    public function ultimoIdInserito() {
        return $this->connessione->lastInsertId();
    }    
    /**
     * Esegue una query SQL semplice
     * 
     * @param string $sql La query da eseguire
     * @return PDOStatement Il risultato della query
     */
    public function eseguiQuery($sql) {
        return $this->connessione->query($sql);
    }
    
    /**
     * Prepara una query SQL per l'esecuzione sicura
     * 
     * @param string $sql La query da preparare
     * @return PDOStatement Lo statement preparato
     */
    public function preparaQuery($sql) {
        return $this->connessione->prepare($sql);
    }
    
    /**
     * Esegue una query preparata con parametri
     * 
     * @param string $sql La query da eseguire
     * @param array $parametri I parametri da associare alla query
     * @return bool True se l'esecuzione è riuscita
     */
    public function esegui($sql, $parametri = []) {
        $statement = $this->preparaQuery($sql);
        return $statement->execute($parametri);
    }
    
    /**
     * Esegue una query e restituisce un singolo risultato
     * 
     * @param string $sql La query da eseguire
     * @param array $parametri I parametri da associare alla query
     * @return array|false Il risultato o false se non trovato
     */
    public function ottieniRiga($sql, $parametri = []) {
        $statement = $this->preparaQuery($sql);
        $statement->execute($parametri);
        return $statement->fetch();
    }
    
    /**
     * Esegue una query e restituisce tutti i risultati
     * 
     * @param string $sql La query da eseguire
     * @param array $parametri I parametri da associare alla query
     * @return array L'array di tutti i risultati
     */
    public function ottieniTutteLeRighe($sql, $parametri = []) {
        $statement = $this->preparaQuery($sql);
        $statement->execute($parametri);
        return $statement->fetchAll();
    }
    
    /**
     * Esegue una query e restituisce un singolo valore
     * 
     * @param string $sql La query da eseguire
     * @param array $parametri I parametri da associare alla query
     * @return mixed Il valore della prima colonna del primo risultato
     */
    public function ottieniValore($sql, $parametri = []) {
        $statement = $this->preparaQuery($sql);
        $statement->execute($parametri);
        return $statement->fetchColumn();
    }
    
    /**
     * Conta il numero di righe interessate dall'ultima operazione
     * 
     * @param string $sql La query da eseguire
     * @param array $parametri I parametri da associare alla query
     * @return int Il numero di righe
     */
    public function contaRighe($sql, $parametri = []) {
        $statement = $this->preparaQuery($sql);
        $statement->execute($parametri);
        return $statement->rowCount();
    }
    
    /**
     * Inserisce un nuovo record in una tabella
     * 
     * @param string $tabella Il nome della tabella
     * @param array $dati I dati da inserire (chiave => valore)
     * @return string L'ID del record inserito
     */
    public function inserisci($tabella, $dati) {
        $campi = array_keys($dati);
        $segnaposto = array_fill(0, count($campi), '?');
        
        $sql = "INSERT INTO {$tabella} (" . implode(', ', $campi) . ") 
                VALUES (" . implode(', ', $segnaposto) . ")";
        
        $this->esegui($sql, array_values($dati));
        return $this->ultimoIdInserito();
    }
    
    /**
     * Aggiorna record esistenti in una tabella
     * 
     * @param string $tabella Il nome della tabella
     * @param array $dati I dati da aggiornare (chiave => valore)
     * @param string $condizione La condizione WHERE
     * @param array $parametriCondizione I parametri per la condizione WHERE
     * @return bool True se l'aggiornamento è riuscito
     */
    public function aggiorna($tabella, $dati, $condizione, $parametriCondizione = []) {
        $campi = array_map(function($campo) {
            return "{$campo} = ?";
        }, array_keys($dati));
        
        $sql = "UPDATE {$tabella} SET " . implode(', ', $campi) . " WHERE {$condizione}";
        
        $parametri = array_merge(array_values($dati), $parametriCondizione);
        return $this->esegui($sql, $parametri);
    }
    
    /**
     * Elimina record da una tabella
     * 
     * @param string $tabella Il nome della tabella
     * @param string $condizione La condizione WHERE
     * @param array $parametri I parametri per la condizione WHERE
     * @return bool True se l'eliminazione è riuscita
     */
    public function elimina($tabella, $condizione, $parametri = []) {
        $sql = "DELETE FROM {$tabella} WHERE {$condizione}";
        return $this->esegui($sql, $parametri);
    }
    
    /**
     * Impedisce la clonazione dell'istanza (pattern Singleton)
     */
    private function __clone() {}
    
    /**
     * Impedisce la deserializzazione dell'istanza (pattern Singleton)
     */
    public function __wakeup() {}
}
?>
