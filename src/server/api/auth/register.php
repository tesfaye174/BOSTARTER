<?php
require_once __DIR__ . '/../../controllers/AuthController.php';

// Handle registration requests
$authController = new AuthController();
$authController->register();
?>