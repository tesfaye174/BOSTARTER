<?php
/**
 * Wrapper per le stored procedures del database BOSTARTER
 * Questo file fornisce funzioni PHP per utilizzare facilmente le stored procedures relative ai progetti
 * Le funzioni di autenticazione sono state spostate nella classe User e nel file auth.php principale
 */

// Includi il file di connessione
require_once 'db_connect.php';


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
    $conn = getDbConnection();
    
    // Prepara la chiamata alla stored procedure
    $stmt = $conn->prepare("CALL sp_crea_progetto(?, ?, ?, ?, ?, ?, ?, @p_progetto_id, @p_success, @p_message)");
    $stmt->bind_param("ssissdss", $nome, $descrizione, $creatore_id, $tipo_progetto, $budget_richiesto, $data_scadenza, $categoria);
    
    // Esegui la stored procedure
    $stmt->execute();
    $stmt->close();
    
    // Ottieni i parametri di output
    $result = $conn->query("SELECT @p_progetto_id as progetto_id, @p_success as success, @p_message as message");
    $row = $result->fetch_assoc();
    
    return [
        'success' => (bool)$row['success'],
        'message' => $row['message'],
        'progetto_id' => $row['progetto_id']
    ];
}

/**
 * Ottiene i top creatori dalla vista v_top_creatori
 * 
 * @return array Array di creatori con le loro statistiche
 */
function getTopCreatori() {
    return fetchAll("SELECT * FROM v_top_creatori");
}

/**
 * Ottiene i progetti vicini al completamento dalla vista v_progetti_near_completion
 * 
 * @return array Array di progetti vicini al completamento
 */
function getProgettiNearCompletion() {
    return fetchAll("SELECT * FROM v_progetti_near_completion");
}

/**
 * Ottiene i top finanziatori dalla vista v_top_finanziatori
 * 
 * @return array Array di finanziatori con le loro statistiche
 */
function getTopFinanziatori() {
    return fetchAll("SELECT * FROM v_top_finanziatori");
}

/**
 * Esegue manualmente la procedura di chiusura dei progetti scaduti
 * Utile per test o per esecuzione manuale
 * 
 * @return bool True se l'operazione è riuscita, false altrimenti
 */
function chiudiProgettiScaduti() {
    $conn = getDbConnection();
    
    // Esegui la query dell'evento manualmente
    $updateResult = $conn->query("UPDATE progetti SET stato = 'chiuso' WHERE data_scadenza < NOW() AND stato = 'aperto'");
    
    if (!$updateResult) {
        return false;
    }
    
    // Registra l'operazione nel log
    $logResult = $conn->query("INSERT INTO log_attivita (tipo_attivita, descrizione) VALUES ('progetti_scaduti_chiusi', CONCAT('Progetti scaduti chiusi manualmente alle ', NOW()))");
    
    return ($logResult !== false);
}
?>