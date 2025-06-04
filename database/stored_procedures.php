<?php
/**
 * Wrapper per le stored procedures del database BOSTARTER
 * Questo file fornisce funzioni PHP per utilizzare facilmente le stored procedures relative ai progetti
 * Le funzioni di autenticazione sono state spostate nella classe User e nel file auth.php principale
 */

// Includi il file di connessione
require_once __DIR__ . '/../backend/config/database.php';


/**
 * Crea un nuovo progetto utilizzando la stored procedure sp_crea_progetto
 * 
 * @param string $nome Nome del progetto
 * @param string $descrizione Descrizione del progetto
 * @param int $creatore_id ID dell'utente creatore
 * @param string $tipo_progetto Tipo di progetto (hardware, software)
 * @param float $budget_richiesto Budget richiesto per il progetto
 * @param string $data_scadenza Data di scadenza del progetto (formato YYYY-MM-DD HH:MM:SS)
 * @param string $categoria Categoria del progetto
 * @return array Risultato dell'operazione con chiavi 'success', 'message' e 'progetto_id' se successo
 */
function creaProgetto($nome, $descrizione, $creatore_id, $tipo_progetto, $budget_richiesto, $data_scadenza, $categoria) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Prepara la chiamata alla stored procedure
    $stmt = $conn->prepare("CALL sp_crea_progetto(:nome, :descrizione, :creatore_id, :tipo_progetto, :budget_richiesto, :data_scadenza, :categoria, @p_progetto_id, @p_success, @p_message)");
    
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':descrizione', $descrizione, PDO::PARAM_STR);
    $stmt->bindParam(':creatore_id', $creatore_id, PDO::PARAM_INT);
    $stmt->bindParam(':tipo_progetto', $tipo_progetto, PDO::PARAM_STR);
    $stmt->bindParam(':budget_richiesto', $budget_richiesto, PDO::PARAM_STR); // PDO uses STR for decimals
    $stmt->bindParam(':data_scadenza', $data_scadenza, PDO::PARAM_STR);
    $stmt->bindParam(':categoria', $categoria, PDO::PARAM_STR);
    
    // Esegui la stored procedure
    $stmt->execute();
    $stmt->closeCursor(); // Chiudi il cursore per permettere altre query
    
    // Ottieni i parametri di output
    $output = $conn->query("SELECT @p_progetto_id as progetto_id, @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
    
    return [
        'success' => (bool)$output['success'],
        'message' => $output['message'],
        'progetto_id' => $output['progetto_id']
    ];
}

/**
 * Ottiene i top creatori dalla vista v_top_creatori
 * 
 * @return array Array di creatori con le loro statistiche
 */
function getTopCreatori() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM v_top_creatori");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ottiene i progetti vicini al completamento dalla vista v_progetti_near_completion
 * 
 * @return array Array di progetti vicini al completamento
 */
function getProgettiNearCompletion() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM v_progetti_near_completion");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ottiene i top finanziatori dalla vista v_top_finanziatori
 * 
 * @return array Array di finanziatori con le loro statistiche
 */
function getTopFinanziatori() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM v_top_finanziatori");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Esegue manualmente la procedura di chiusura dei progetti scaduti
 * Utile per test o per esecuzione manuale
 * 
 * @return bool True se l'operazione è riuscita, false altrimenti
 */
function chiudiProgettiScaduti() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Esegui la query dell'evento manualmente
        $conn->exec("UPDATE progetti SET stato = 'chiuso' WHERE data_scadenza < NOW() AND stato = 'aperto'");
        
        // Registra l'operazione nel log
        $conn->exec("INSERT INTO log_attivita (tipo_attivita, descrizione) VALUES ('progetti_scaduti_chiusi', CONCAT('Progetti scaduti chiusi manualmente alle ', NOW()))");
        
        return true;
    } catch (PDOException $e) {
        // Logga l'errore o gestiscilo come preferisci
        error_log("Errore in chiudiProgettiScaduti: " . $e->getMessage());
        return false;
    }
}
?>