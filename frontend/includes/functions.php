<?php
/**
 * BOSTARTER - Frontend Utility Functions
 *
 * File di utility comuni per ridurre ridondanze nel frontend
 * Contiene funzioni frequentemente utilizzate in più pagine
 */

session_start();

/**
 * Verifica se l'utente è autenticato
 * @return bool True se loggato, false altrimenti
 */
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

/**
 * Genera token CSRF sicuro
 * @return string Token CSRF
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Sanitizza output HTML per prevenire XSS
 * @param mixed $data Dati da sanitizzare
 * @return string Stringa sanitizzata
 */
function sanitize_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formatta data in formato italiano
 * @param string $date Data da formattare
 * @return string Data formattata o messaggio di errore
 */
function format_date($date) {
    if (!$date) return 'Non specificata';
    return date('d/m/Y', strtotime($date));
}

/**
 * Calcola giorni rimanenti fino alla scadenza
 * @param string $deadline Data limite
 * @return int Giorni rimanenti (0 se scaduto)
 */
function calculate_days_left($deadline) {
    if (!$deadline) return 0;
    $now = time();
    $deadline_time = strtotime($deadline);
    $diff = $deadline_time - $now;
    return max(0, ceil($diff / (60 * 60 * 24)));
}

/**
 * Connessione sicura al database con gestione errori
 * @return PDO|null Connessione database o null se errore
 */
function getDatabaseConnection() {
    static $db = null;

    if ($db === null) {
        try {
            require_once __DIR__ . '/../backend/config/database.php';
            $db = Database::getInstance();
        } catch(Exception $e) {
            error_log('Errore connessione database: ' . $e->getMessage());
            return null;
        }
    }

    return $db;
}

/**
 * Reindirizza con messaggio di errore
 * @param string $message Messaggio di errore
 * @param string $location URL di destinazione (default: home)
 */
function redirectWithError($message, $location = 'home.php') {
    $_SESSION['error'] = $message;
    header("Location: $location");
    exit;
}

/**
 * Reindirizza con messaggio di successo
 * @param string $message Messaggio di successo
 * @param string $location URL di destinazione (default: home)
 */
function redirectWithSuccess($message, $location = 'home.php') {
    $_SESSION['success'] = $message;
    header("Location: $location");
    exit;
}

/**
 * Verifica autorizzazioni utente per azione specifica
 * @param string $required_role Ruolo richiesto
 * @return bool True se autorizzato
 */
function hasPermission($required_role) {
    $user_role = $_SESSION['tipo_utente'] ?? '';
    return $user_role === $required_role || $user_role === 'amministratore';
}

/**
 * Genera URL sicuro per redirect
 * @param string $path Percorso relativo
 * @return string URL completo e sicuro
 */
function safeUrl($path) {
    return htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida forza password (almeno 8 caratteri, maiuscola, minuscola, numero)
 * @param string $password Password da validare
 * @return bool True se password valida
 */
function isValidPassword($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

/**
 * Verifica token CSRF dalla richiesta
 * @param string $token Token da verificare
 * @return bool True se valido
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
