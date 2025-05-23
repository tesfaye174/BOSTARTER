<?php
/**
 * Sistema di autenticazione unificato per BOSTARTER
 * Questo file integra le funzionalità di autenticazione precedentemente divise tra backend/auth.php e database/auth.php
 * Utilizza la classe User per gestire tutte le operazioni di autenticazione
 */

// Includi la classe User
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/config/database.php';

// Avvia la sessione se non è già attiva (già gestito nella classe User)

// Verifica il login automatico all'inclusione del file
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->checkRememberMe();

/**
 * Funzione wrapper per registrare un nuovo utente
 * @param array $dati Dati dell'utente da registrare
 * @return array Risultato dell'operazione
 */
function registraUtente($dati) {
    global $db;
    $user = new User($db);
    
    $user->email = $dati['email'];
    $user->nickname = $dati['nickname'];
    $user->password_hash = $dati['password']; // Sarà hashata nel metodo register()
    $user->nome = $dati['nome'];
    $user->cognome = $dati['cognome'];
    $user->anno_nascita = $dati['anno_nascita'];
    $user->luogo_nascita = $dati['luogo_nascita'];
    $user->tipo_utente = $dati['tipo_utente'] ?? 'standard';
    
    $result = $user->register();
    
    // Converti il formato della risposta per compatibilità
    return [
        'success' => ($result['status'] === 'success'),
        'message' => $result['message'],
        'user_id' => $result['user_id']
    ];
}

/**
 * Funzione wrapper per effettuare il login
 * @param string $email Email dell'utente
 * @param string $password Password dell'utente
 * @param bool $ricordami Se true, imposta un cookie per il login automatico
 * @return array Risultato dell'operazione
 */
function loginUtente($email, $password, $ricordami = false) {
    global $db;
    $user = new User($db);
    
    $result = $user->login($email, $password, $ricordami);
    
    // Converti il formato della risposta per compatibilità
    return [
        'success' => ($result['status'] === 'success'),
        'message' => $result['message'],
        'user' => $result['user_data']
    ];
}

/**
 * Funzione wrapper per effettuare il logout
 */
function logoutUtente() {
    global $db;
    $user = new User($db);
    $user->logout();
}

/**
 * Funzione wrapper per verificare se l'utente è loggato
 * @return bool True se l'utente è loggato, false altrimenti
 */
function isLogged() {
    return User::isLogged();
}

/**
 * Funzione wrapper per ottenere l'ID dell'utente corrente
 * @return int|null ID dell'utente o null se non loggato
 */
function getCurrentUserId() {
    return User::getCurrentUserId();
}

/**
 * Funzione wrapper per ottenere i dati dell'utente corrente
 * @return array|null Dati dell'utente o null se non loggato
 */
function getCurrentUser() {
    global $db;
    $user = new User($db);
    return $user->getCurrentUser();
}

/**
 * Funzione wrapper per verificare se l'utente ha un determinato ruolo
 * @param string $role Ruolo da verificare (standard, creatore, amministratore)
 * @return bool True se l'utente ha il ruolo specificato, false altrimenti
 */
function hasRole($role) {
    return User::hasRole($role);
}

/**
 * Funzione wrapper per verificare se l'utente è un amministratore
 * @return bool True se l'utente è un amministratore, false altrimenti
 */
function isAdmin() {
    return User::isAdmin();
}

/**
 * Funzione wrapper per verificare se l'utente è un creatore
 * @return bool True se l'utente è un creatore, false altrimenti
 */
function isCreatore() {
    return User::isCreatore();
}

/**
 * Funzione wrapper per aggiornare il profilo dell'utente
 * @param int $utenteId ID dell'utente
 * @param array $dati Dati da aggiornare
 * @return array Risultato dell'operazione
 */
function aggiornaProfiloUtente($utenteId, $dati) {
    global $db;
    $user = new User($db);
    
    $user->id = $utenteId;
    $user->nickname = $dati['nickname'] ?? null;
    $user->nome = $dati['nome'] ?? null;
    $user->cognome = $dati['cognome'] ?? null;
    $user->anno_nascita = $dati['anno_nascita'] ?? null;
    $user->luogo_nascita = $dati['luogo_nascita'] ?? null;
    $user->bio = $dati['bio'] ?? null;
    $user->avatar = $dati['avatar'] ?? null;
    
    $result = $user->updateProfile();
    
    // Converti il formato della risposta per compatibilità
    return [
        'success' => ($result['status'] === 'success'),
        'message' => $result['message']
    ];
}

/**
 * Funzione wrapper per cambiare la password di un utente
 * @param int $utenteId ID dell'utente
 * @param string $passwordAttuale Password attuale
 * @param string $nuovaPassword Nuova password
 * @return array Risultato dell'operazione
 */
function cambiaPassword($utenteId, $passwordAttuale, $nuovaPassword) {
    global $db;
    $user = new User($db);
    
    $result = $user->changePassword($utenteId, $passwordAttuale, $nuovaPassword);
    
    // Converti il formato della risposta per compatibilità
    return [
        'success' => ($result['status'] === 'success'),
        'message' => $result['message']
    ];
}

/**
 * Funzione wrapper per verificare se un'email è già registrata
 * @param string $email Email da verificare
 * @return bool True se l'email esiste, false altrimenti
 */
function emailExists($email) {
    global $db;
    $user = new User($db);
    return $user->emailExists($email);
}

/**
 * Funzione wrapper per verificare se un nickname è già in uso
 * @param string $nickname Nickname da verificare
 * @return bool True se il nickname esiste, false altrimenti
 */
function nicknameExists($nickname) {
    global $db;
    $user = new User($db);
    return $user->nicknameExists($nickname);
}

/**
 * Funzione wrapper per promuovere un utente a creatore
 * @param int $utenteId ID dell'utente da promuovere
 * @return array Risultato dell'operazione
 */
function promuoviCreatore($utenteId) {
    global $db;
    $user = new User($db);
    
    // Verifica se l'utente esiste
    $userData = $user->getUserById($utenteId);
    
    if (!$userData) {
        return ['success' => false, 'message' => 'Utente non trovato'];
    }
    
    if ($userData['tipo_utente'] === 'creatore') {
        return ['success' => false, 'message' => "L'utente è già un creatore"];
    }
    
    // Aggiorna il tipo di utente
    $updateQuery = "UPDATE {$user->table_name} SET tipo_utente = ? WHERE id = ?";
    $stmt = $db->prepare($updateQuery);
    $result = $stmt->execute(['creatore', $utenteId]);
    
    if ($result) {
        // Registra l'attività
        $user->logActivity(User::getCurrentUserId(), 'promozione_creatore', "Promozione utente ID $utenteId a creatore");
        
        // Invia notifica all'utente
        $notificaQuery = "INSERT INTO notifiche (utente_id, tipo, messaggio, link) VALUES (?, ?, ?, ?)";
        $notificaStmt = $db->prepare($notificaQuery);
        $notificaStmt->execute([
            $utenteId,
            'promozione',
            'Sei stato promosso a creatore! Ora puoi pubblicare progetti sulla piattaforma.',
            '/frontend/creatori/creatori_dashboard.html'
        ]);
        
        return ['success' => true, 'message' => 'Utente promosso a creatore con successo'];
    } else {
        return ['success' => false, 'message' => "Errore durante la promozione dell'utente"];
    }
}

/**
 * Funzione wrapper per verificare se l'utente può modificare un progetto
 * @param int $progettoId ID del progetto
 * @return bool True se l'utente può modificare il progetto, false altrimenti
 */
function canEditProject($progettoId) {
    if (!User::isLogged()) {
        return false;
    }
    
    // Gli amministratori possono modificare qualsiasi progetto
    if (User::isAdmin()) {
        return true;
    }
    
    global $db;
    // Verifica se l'utente è il creatore del progetto
    $query = "SELECT creatore_id 
    FROM progetti WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$progettoId]);
    $progetto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $progetto && $progetto['creatore_id'] == User::getCurrentUserId();
}
?>