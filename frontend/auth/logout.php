<?php
require_once __DIR__ . '/../../backend/controllers/AuthController.php';

// Inizializza controller di autenticazione
$authController = new \BOSTARTER\Controllers\GestoreAutenticazione();

// Esegui logout
$authController->logout();

session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;