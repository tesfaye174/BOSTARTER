<?php
/**
 * BOSTARTER - Logout Page
 * Gestisce il logout dell'utente e reindirizza alla home
 */

session_start();

// Distruggi completamente la sessione
session_unset();
session_destroy();

// Elimina il cookie di sessione se esiste
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Reindirizza alla home con messaggio di successo
header("Location: ../home.php?logout=success");
exit();
?>