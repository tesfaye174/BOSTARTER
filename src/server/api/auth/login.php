<?php
require_once __DIR__ . '/../../controllers/AuthController.php';

// Handle login requests
$authController = new AuthController();
$authController->login();
?>