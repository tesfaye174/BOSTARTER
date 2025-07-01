<?php
session_start();

// Distruggi sessione
session_unset();
session_destroy();

// Reindirizza alla home con messaggio
header("Location: ../home.php?logout=success");
exit;
?>
