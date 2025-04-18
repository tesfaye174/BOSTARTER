<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/DatabaseManager.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Router.php';

use Config\Logger;
use Config\DatabaseManager;
use Server\Router;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define allowed origins
$allowedOrigins = ['http://localhost:3000', 'http://localhost:8080', 'http://127.0.0.1:3000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// CORS headers
header('Content-Type: application/json');
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // 24 hours cache
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

// Initialize router
$router = Router::getInstance();

// Add global rate limiting
$router->setRateLimit('/api/', 100, 60); // 100 requests per minute

// Add global error handlers
$router->addErrorHandler(404, function($e) {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found', 'message' => $e->getMessage()]);
});

$router->addErrorHandler(429, function($e) {
    http_response_code(429);
    echo json_encode(['error' => 'Too Many Requests', 'message' => 'Please try again later']);
});

try {
    // Load route configuration
    require_once __DIR__ . '/../config/routes.php';
    
    // Register routes
    Config\Routes::register($router);
    
    // Add request validation middleware
    $router->addGlobalMiddleware(function($request) {
        // Validate request data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON payload', 400);
                }
                $_POST = array_merge($_POST, $data);
            }
        }
        
        // Sanitize input data
        array_walk_recursive($_GET, 'filter_var', FILTER_SANITIZE_STRING);
        array_walk_recursive($_POST, 'filter_var', FILTER_SANITIZE_STRING);
        
        return true;
    });
    
    // Handle the request
    $router->handleRequest();
    
} catch (Exception $e) {
    http_response_code($e->getCode() && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Log error
    try {
        if (class_exists('Config\Logger')) {
            Logger::getInstance()->log('api_error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'uri' => $_SERVER['REQUEST_URI'],
                'method' => $_SERVER['REQUEST_METHOD'],
                'trace' => $e->getTraceAsString()
            ]);
        }
    } catch (Exception $logError) {
        error_log("Failed to log error: " . $logError->getMessage());
    }
}
?>