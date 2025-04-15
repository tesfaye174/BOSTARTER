<?php
require_once __DIR__ . '/../../controllers/AuthController.php';

// Handle logout requests
$authController = new AuthController();
$authController->logout();
?>