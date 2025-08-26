<?php
/**
 * BOSTARTER - Index Page
 * Gestisce redirect e messaggi di logout
 */

// Controlla se c'è un messaggio di logout
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    // Reindirizza a home.php mantenendo il messaggio di logout
    header('Location: home.php?logout=success');
    exit;
}

// Redirect normale per compatibilità
header('Location: home.php');
exit;
?>
