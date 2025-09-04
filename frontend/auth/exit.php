<?php
/**
 * BOSTARTER - Logout System
 * Gestisce il logout degli utenti con messaggio personalizzato
 */

session_start();

// Salva informazioni utente per messaggio personalizzato
$username = '';
if (isset($_SESSION['user_id']) && isset($_SESSION['nome'])) {
    $username = $_SESSION['nome'];
}

// Distruggi tutte le variabili di sessione
$_SESSION = array();

// Se desideri distruggere completamente la sessione, cancella anche il cookie di sessione
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distruggi la sessione
session_destroy();

// Reindirizza alla homepage con messaggio di conferma
if ($username) {
    header('Location: ../home.php?logout=success&user=' . urlencode($username));
} else {
    header('Location: ../home.php?logout=success');
}
exit();
?>