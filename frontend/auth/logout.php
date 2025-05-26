<?php
session_start();

// Distruggi la sessione
session_destroy();

// Redirect alla home page
header('Location: /');
exit; 