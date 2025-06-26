<?php
// Includiamo il file di configurazione principale
require_once __DIR__ . '/config.php';

/**
 * Classe per gestire la connessione al database MySQL
 * 
 * Questa classe rappresenta il "ponte" tra la nostra applicazione e il database.
 * Implementa il pattern Singleton per assicurare una sola connessione attiva
 * evitando così sprechi di risorse e potenziali conflitti.
 * 
 * Offre metodi semplificati per eseguire query e gestire transazioni in modo sicuro.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 */
class Database {
    private static $istanza = null;  // L'unica istanza della classe (pattern Singleton)
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
     * @return PDO L'oggetto connessione attivo
     */
    public function getConnection() {
        return $this->connessione;
    }
    
    /**
     * Inizia una transazione nel database
     * 
     * Le transazioni sono come "operazioni protette": se una parte fallisce, 
     * nulla viene salvato. È come fare la spesa: o compri tutto o non compri niente.
     * Utile quando dobbiamo eseguire più operazioni che devono avere successo tutte insieme.
     * 
     * @return bool True se la transazione è iniziata correttamente
     */
    public function iniziaTransazione() {
        return $this->connessione->beginTransaction();
    }
    
    /**
     * Conferma tutte le operazioni della transazione corrente
     * 
     * È come il "salvataggio definitivo" di tutto ciò che abbiamo fatto
     * nella transazione. A questo punto i dati sono effettivamente nel database.
     * 
     * @return bool True se il commit è andato a buon fine
     */
    public function confermaTransazione() {
        return $this->connessione->commit();
    }
    
    /**
     * Annulla tutte le operazioni della transazione corrente
     * 
     * In caso di errori o problemi, possiamo annullare tutto e tornare
     * allo stato iniziale, come se niente fosse successo.
     * 
     * @return bool True se il rollback è andato a buon fine
     */
    public function annullaTransazione() {
        return $this->connessione->rollBack();
    }
    
    /**
     * Ottiene l'ID dell'ultimo record inserito
     * 
     * Utile dopo aver inserito un nuovo record per sapere quale ID gli è stato assegnato
     * 
     * @return string L'ID dell'ultimo insert
     */
    public function ultimoIdInserito() {
        return $this->connessione->lastInsertId();
    }    
    /**
     * Esegue una query SQL semplice
     * 
     * Metodo base per eseguire una query senza parametri
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
     * La preparazione della query è fondamentale per prevenire attacchi SQL injection
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
     * Metodo completo che prepara ed esegue una query con parametri in un solo passaggio
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
     * Ideale per query che devono restituire un solo record, come "trova utente per ID"
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
     * Perfetto per ottenere liste di elementi, come "tutti i progetti attivi"
     * o "tutti gli utenti registrati nell'ultimo mese"
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
     * Utile per operazioni come conteggi o calcoli aggregati: 
     * "quanti utenti abbiamo?" o "qual è la media delle donazioni?"
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
     * Utile per sapere quanti record sono stati modificati, inseriti o eliminati
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
     * Metodo semplificato per inserire dati in una tabella senza dover scrivere SQL
     * Costruisce automaticamente la query INSERT con i campi e valori forniti
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
     * Metodo semplificato per modificare dati esistenti senza scrivere SQL
     * Costruisce automaticamente la query UPDATE con i campi, valori e condizioni fornite
     * 
     * @param string $tabella Il nome della tabella
     * @param array $dati I dati da aggiornare (chiave => valore)
     * @param string $condizione La condizione WHERE (es. "id = ?")
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
     * Metodo semplificato per eliminare dati senza scrivere SQL
     * ATTENZIONE: usare con cautela e sempre con una condizione WHERE,
     * altrimenti si rischia di cancellare tutti i dati della tabella!
     * 
     * @param string $tabella Il nome della tabella
     * @param string $condizione La condizione WHERE (es. "id = ?")
     * @param array $parametri I parametri per la condizione WHERE
     * @return bool True se l'eliminazione è riuscita
     */
    public function elimina($tabella, $condizione, $parametri = []) {
        $sql = "DELETE FROM {$tabella} WHERE {$condizione}";
        return $this->esegui($sql, $parametri);
    }
    
    /**
     * Impedisce la clonazione dell'istanza (pattern Singleton)
     * 
     * Metodo protettivo che garantisce che non si possano creare copie
     * dell'oggetto Database attraverso il clone
     */
    private function __clone() {}
    
    /**
     * Impedisce la deserializzazione dell'istanza (pattern Singleton)
     * 
     * Ulteriore protezione per garantire l'unicità dell'istanza Database
     * anche quando si usa la serializzazione
     */
    public function __wakeup() {}
}
?>
