<?php
require_once '../config/config.php';
require_once '../database/Database.php';
require_once 'Router.php';

header('Content-Type: application/json');

$router = new Router();

// Authentication routes
$router->addRoute('POST', '/api/auth/login', function() {
    // Login logic
});

$router->addRoute('POST', '/api/auth/register', function() {
    // Registration logic
});

// Project routes
$router->addRoute('GET', '/api/projects', function() {
    // Get projects logic
});

$router->addRoute('POST', '/api/projects', function() {
    // Create project logic
});

// Handle the request
$result = $router->handleRequest();
echo json_encode($result);
?>