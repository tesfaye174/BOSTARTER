<?php
/**
 * BOSTARTER - Index Page
 * Gestisce redirect e messaggi di logout
 */

// Abilitare la visualizzazione degli errori per il debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

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