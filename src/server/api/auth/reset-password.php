<?php
require_once __DIR__ . '/../../controllers/AuthController.php';

// Handle password reset requests
$authController = new AuthController();
$authController->resetPassword();
?>