<?php
require_once __DIR__ . '/../../backend/controllers/AuthController.php';

// Inizializza controller di autenticazione
$authController = new AuthController();

// Esegui logout
$authController->logout();