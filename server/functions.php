<?php
require_once 'config/config.php';

/**
 * Funzioni per la gestione degli utenti
 */

/**
 * Registra un nuovo utente nel sistema
 * 
 * @param string $email Email dell'utente
 * @param string $nickname Nome utente
 * @param string $password Password in chiaro (verrà hashata)
 * @param string $nome Nome reale
 * @param string $cognome Cognome
 * @param int $anno_nascita Anno di nascita
 * @param string $luogo_nascita Luogo di nascita
 * @param bool $is_admin Flag per i privilegi di amministratore
 * @param bool $is_creator Flag per i privilegi di creator
 * @param string|null $security_code Codice di sicurezza per admin
 * @return bool True se la registrazione ha successo, False altrimenti
 */
function registerUser($email, $nickname, $password, $nome, $cognome, $anno_nascita, $luogo_nascita, $is_admin = false, $is_creator = false, $security_code = null) {
    global $conn;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = mysqli_prepare($conn, "CALL register_user(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssssissis", $email, $nickname, $hashed_password, $nome, $cognome, $anno_nascita, $luogo_nascita, $is_admin, $is_creator, $security_code);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Nuovo utente registrato: $nickname");
        return true;
    }
    return false;
}

/**
 * Aggiunge una competenza al profilo di un utente
 * 
 * @param int $user_id ID dell'utente
 * @param string $competenza Nome della competenza
 * @param int $livello Livello di competenza (1-5)
 * @return bool True se l'aggiunta ha successo, False altrimenti
 */
function addUserSkill($user_id, $competenza, $livello) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "CALL add_user_skill(?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isi", $user_id, $competenza, $livello);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Aggiunta skill per utente ID: $user_id");
        return true;
    }
    return false;
}

/**
 * Funzioni per la gestione dei progetti
 */

/**
 * Crea un nuovo progetto nel sistema
 * 
 * @param string $nome Nome del progetto
 * @param string $descrizione Descrizione dettagliata del progetto
 * @param float $budget Budget richiesto per il progetto
 * @param string $data_limite Data di scadenza per il raggiungimento del budget
 * @param string $tipo Tipologia del progetto
 * @param int $creator_id ID dell'utente creator che crea il progetto
 * @return int|false ID del progetto creato o False in caso di errore
 */
function createProject($nome, $descrizione, $budget, $data_limite, $tipo, $creator_id) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "CALL create_project(?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssdssi", $nome, $descrizione, $budget, $data_limite, $tipo, $creator_id);
    
    $result = mysqli_stmt_execute($stmt);
    $project_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Nuovo progetto creato: $nome");
        return $project_id;
    }
    return false;
}

/**
 * Aggiunge una ricompensa a un progetto
 * 
 * @param int $progetto_id ID del progetto
 * @param string $codice Codice identificativo della ricompensa
 * @param string $descrizione Descrizione della ricompensa
 * @param string $url_foto URL dell'immagine della ricompensa
 * @return bool True se l'aggiunta ha successo, False altrimenti
 */
function addReward($progetto_id, $codice, $descrizione, $url_foto) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "CALL add_reward(?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $progetto_id, $codice, $descrizione, $url_foto);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Aggiunta reward per progetto ID: $progetto_id");
        return true;
    }
    return false;
}

/**
 * Registra un finanziamento per un progetto
 * 
 * @param int $progetto_id ID del progetto
 * @param int $user_id ID dell'utente che effettua il finanziamento
 * @param float $importo Importo del finanziamento
 * @param int $reward_id ID della ricompensa scelta
 * @return bool True se il finanziamento ha successo, False altrimenti
 */
function makeFunding($progetto_id, $user_id, $importo, $reward_id) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "CALL make_funding(?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iidi", $progetto_id, $user_id, $importo, $reward_id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Nuovo finanziamento per progetto ID: $progetto_id");
        return true;
    }
    return false;
}

/**
 * Funzioni per la gestione dei commenti
 */

/**
 * Aggiunge un commento a un progetto
 * 
 * @param int $progetto_id ID del progetto
 * @param int $user_id ID dell'utente che commenta
 * @param string $testo Contenuto del commento
 * @return bool True se l'aggiunta ha successo, False altrimenti
 */
function addComment($progetto_id, $user_id, $testo) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "CALL add_comment(?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iis", $progetto_id, $user_id, $testo);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Nuovo commento per progetto ID: $progetto_id");
        return true;
    }
    return false;
}

/**
 * Aggiunge una risposta a un commento esistente
 * 
 * @param int $commento_id ID del commento originale
 * @param string $risposta Testo della risposta
 * @return bool True se l'aggiunta ha successo, False altrimenti
 */
function replyToComment($commento_id, $risposta) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "CALL reply_to_comment(?, ?)");
    mysqli_stmt_bind_param($stmt, "is", $commento_id, $risposta);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Risposta aggiunta al commento ID: $commento_id");
        return true;
    }
    return false;
}

/**
 * Funzioni per la gestione delle candidature
 */

/**
 * Registra la candidatura di un utente per un profilo
 * 
 * @param int $user_id ID dell'utente che si candida
 * @param int $profilo_id ID del profilo per cui ci si candida
 * @return bool True se la candidatura ha successo, False altrimenti
 */
function applyForProfile($user_id, $profilo_id) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "CALL apply_for_profile(?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $profilo_id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Nuova candidatura per profilo ID: $profilo_id");
        return true;
    }
    return false;
}

/**
 * Gestisce lo stato di una candidatura
 * 
 * @param int $candidatura_id ID della candidatura
 * @param string $stato Nuovo stato della candidatura
 * @return bool True se l'aggiornamento ha successo, False altrimenti
 */
function handleApplication($candidatura_id, $stato) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "CALL handle_application(?, ?)");
    mysqli_stmt_bind_param($stmt, "is", $candidatura_id, $stato);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Candidatura ID: $candidatura_id aggiornata a $stato");
        return true;
    }
    return false;
}

/**
 * Funzioni per le statistiche
 */

/**
 * Recupera la lista dei creator più attivi
 * 
 * @return array Lista dei creator ordinata per numero di progetti e successo
 */
function getTopCreators() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM top_creators");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Recupera i progetti vicini al completamento del budget
 * 
 * @return array Lista dei progetti ordinata per percentuale di completamento
 */
function getNearCompletionProjects() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM near_completion_projects");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Recupera la lista dei finanziatori più generosi
 * 
 * @return array Lista degli utenti ordinata per importo totale finanziato
 */
function getTopFunders() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM top_funders");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Aggiunge una nuova competenza al sistema (riservato agli admin)
 * 
 * @param string $nome Nome della competenza
 * @param int $admin_id ID dell'amministratore che aggiunge la competenza
 * @return bool True se l'aggiunta ha successo, False altrimenti
 */
function addCompetenza($nome, $admin_id) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "CALL add_competenza(?, ?)");
    mysqli_stmt_bind_param($stmt, "si", $nome, $admin_id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        logEvent("Nuova competenza aggiunta: $nome");
        return true;
    }
    return false;
}