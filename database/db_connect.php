<?php
/**
 * Connessione al database BOSTARTER
 * File di configurazione per la connessione al database MySQL
 */

// Parametri di connessione
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Modificare in produzione
define('DB_PASS', ''); // Modificare in produzione
define('DB_NAME', 'bostarter');
define('DB_CHARSET', 'utf8mb4');

/**
 * Funzione per ottenere una connessione al database
 * @return mysqli Oggetto connessione al database
 */
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        // Crea una nuova connessione
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Verifica errori di connessione
        if ($conn->connect_error) {
            die('Errore di connessione al database: ' . $conn->connect_error);
        }
        
        // Imposta il charset
        $conn->set_charset(DB_CHARSET);
    }
    
    return $conn;
}

/**
 * Esegue una query SQL e restituisce il risultato
 * @param string $sql Query SQL da eseguire
 * @param array $params Parametri per prepared statement (opzionale)
 * @return mysqli_result|bool Risultato della query
 */
function executeQuery($sql, $params = []) {
    $conn = getDbConnection();
    
    if (empty($params)) {
        // Query semplice senza parametri
        return $conn->query($sql);
    } else {
        // Prepared statement
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            die('Errore nella preparazione della query: ' . $conn->error);
        }
        
        // Costruisci i tipi di parametri (s = string, i = integer, d = double, b = blob)
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        // Bind dei parametri
        $stmt->bind_param($types, ...$params);
        
        // Esegui la query
        $stmt->execute();
        
        // Restituisci il risultato
        return $stmt->get_result();
    }
}

/**
 * Ottiene una singola riga dal risultato di una query
 * @param string $sql Query SQL da eseguire
 * @param array $params Parametri per prepared statement (opzionale)
 * @return array|null Riga risultante o null se non trovata
 */
function fetchRow($sql, $params = []) {
    $result = executeQuery($sql, $params);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Ottiene tutte le righe dal risultato di una query
 * @param string $sql Query SQL da eseguire
 * @param array $params Parametri per prepared statement (opzionale)
 * @return array Array di righe risultanti
 */
function fetchAll($sql, $params = []) {
    $result = executeQuery($sql, $params);
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Inserisce dati in una tabella
 * @param string $table Nome della tabella
 * @param array $data Dati da inserire (chiave => valore)
 * @return int|bool ID dell'ultimo inserimento o false in caso di errore
 */
function insertData($table, $data) {
    $conn = getDbConnection();
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    // Costruisci i tipi di parametri
    $types = '';
    foreach ($data as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_string($value)) {
            $types .= 's';
        } else {
            $types .= 'b';
        }
    }
    
    // Bind dei parametri
    $stmt->bind_param($types, ...array_values($data));
    
    // Esegui la query
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

/**
 * Aggiorna dati in una tabella
 * @param string $table Nome della tabella
 * @param array $data Dati da aggiornare (chiave => valore)
 * @param string $where Condizione WHERE
 * @param array $whereParams Parametri per la condizione WHERE
 * @return bool True se l'aggiornamento è riuscito, false altrimenti
 */
function updateData($table, $data, $where, $whereParams = []) {
    $conn = getDbConnection();
    
    $setClauses = [];
    foreach (array_keys($data) as $column) {
        $setClauses[] = "$column = ?";
    }
    
    $sql = "UPDATE $table SET " . implode(', ', $setClauses) . " WHERE $where";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    // Unisci i parametri di data e where
    $allParams = array_merge(array_values($data), $whereParams);
    
    // Costruisci i tipi di parametri
    $types = '';
    foreach ($allParams as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_string($value)) {
            $types .= 's';
        } else {
            $types .= 'b';
        }
    }
    
    // Bind dei parametri
    $stmt->bind_param($types, ...$allParams);
    
    // Esegui la query
    return $stmt->execute();
}

/**
 * Elimina dati da una tabella
 * @param string $table Nome della tabella
 * @param string $where Condizione WHERE
 * @param array $params Parametri per la condizione WHERE
 * @return bool True se l'eliminazione è riuscita, false altrimenti
 */
function deleteData($table, $where, $params = []) {
    $conn = getDbConnection();
    
    $sql = "DELETE FROM $table WHERE $where";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    // Costruisci i tipi di parametri
    $types = '';
    foreach ($params as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_string($value)) {
            $types .= 's';
        } else {
            $types .= 'b';
        }
    }
    
    // Bind dei parametri
    $stmt->bind_param($types, ...$params);
    
    // Esegui la query
    return $stmt->execute();
}

/**
 * Chiude la connessione al database
 */
function closeDbConnection() {
    $conn = getDbConnection();
    $conn->close();
}

/**
 * Sanitizza l'input per prevenire SQL injection
 * @param string $input Input da sanitizzare
 * @return string Input sanitizzato
 */
function sanitizeInput($input) {
    $conn = getDbConnection();
    return $conn->real_escape_string($input);
}

/**
 * Verifica se una tabella esiste nel database
 * @param string $table Nome della tabella
 * @return bool True se la tabella esiste, false altrimenti
 */
function tableExists($table) {
    $result = executeQuery("SHOW TABLES LIKE '$table'");
    return ($result && $result->num_rows > 0);
}

/**
 * Inizializza il database se non esiste
 */
function initDatabase() {
    $conn = getDbConnection();
    
    // Verifica se il database è vuoto (nessuna tabella)
    $result = $conn->query("SHOW TABLES");
    
    if ($result->num_rows == 0) {
        // Il database è vuoto, esegui lo script di inizializzazione
        $sqlFile = __DIR__ . '/bostarter_schema.sql';
        
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Esegui le query multiple
            if ($conn->multi_query($sql)) {
                do {
                    // Consuma i risultati per poter eseguire la prossima query
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
                
                return true;
            } else {
                die('Errore nell\'inizializzazione del database: ' . $conn->error);
            }
        } else {
            die('File di schema del database non trovato: ' . $sqlFile);
        }
    }
    
    return false;
}

// Inizializza il database se necessario
// Commenta questa riga se non vuoi che il database venga inizializzato automaticamente
// initDatabase();
?>