<?php
require_once __DIR__ . '/../../controllers/AuthController.php';

// Check if user is authenticated
$authController = new AuthController();
$authController->checkAuth();
?>