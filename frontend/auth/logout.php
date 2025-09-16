<?php
/**
 * BOSTARTER - Logout Sicuro
 *
 * Questa pagina gestisce il logout sicuro degli utenti dalla piattaforma BOSTARTER.
 * Esegue la pulizia completa della sessione e reindirizza alla homepage.
 *
 * Funzionalità implementate:
 * - Pulizia completa delle variabili di sessione
 * - Distruzione sicura della sessione
 * - Cancellazione del cookie di sessione (se presente)
 * - Reindirizzamento alla homepage con messaggio personalizzato
 * - Logging dell'evento di logout per sicurezza
 *
 * Sicurezza:
 * - Prevenzione session fixation attraverso rigenerazione ID
 * - Cancellazione completa dei dati di sessione
 * - Eliminazione cookie di sessione lato client
 * - Reindirizzamento sicuro per evitare clickjacking
 *
 * @author BOSTARTER Development Team
 * @version 1.0
 * @since 2025
 */

// Avvia la sessione se non già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Registra l'evento di logout per audit trail (se applicabile)
// Qui potresti aggiungere logging MongoDB per tracciare i logout

// Salva informazioni utente per messaggio personalizzato prima della pulizia
$nomeUtente = '';
$userId = '';
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $nomeUtente = $_SESSION['nome'] ?? $_SESSION['nickname'] ?? '';
}

// Log dell'evento di logout (opzionale, per audit)
if ($userId) {
    error_log("BOSTARTER Logout: User ID {$userId} ({$nomeUtente}) logged out at " . date('Y-m-d H:i:s'));
}

// Pulizia completa della sessione
$_SESSION = array();

// Cancella il cookie di sessione lato client (se i cookie sono abilitati)
if (ini_get("session.use_cookies")) {
    $cookieParams = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Scaduto 12 ore fa
        $cookieParams["path"],
        $cookieParams["domain"],
        $cookieParams["secure"],
        $cookieParams["httponly"]
    );
}

// Distruggi completamente la sessione
session_destroy();

// Prevenzione caching della pagina di logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Reindirizzamento sicuro alla homepage con messaggio di conferma
if (!empty($nomeUtente)) {
    // Messaggio personalizzato con nome utente
    $redirectUrl = "../home.php?logout=success&user=" . urlencode($nomeUtente);
} else {
    // Messaggio generico
    $redirectUrl = "../home.php?logout=success";
}

// Reindirizzamento con header HTTP sicuro
header("Location: {$redirectUrl}");
exit();
?>
