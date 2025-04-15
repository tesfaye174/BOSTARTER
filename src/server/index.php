<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/DatabaseManager.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/Router.php';

use Config\Logger;
use Config\DatabaseManager;
use Server\Router;

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGINS);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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
    require_once __DIR__ . '/config/routes.php';
    
    // Register routes
    Config\Routes::register($router);
    
    // Add request validation middleware
    $router->addGlobalMiddleware(function($request) {
        // Validate CSRF token for non-GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                throw new \Exception('Invalid CSRF token', 403);
            }
        }

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
    $statusCode = $e->getCode();
    if (!is_int($statusCode) || $statusCode < 400 || $statusCode >= 600) {
        $statusCode = 500;
    }

    http_response_code($statusCode);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $statusCode
    ]);
    
    // Log error
    try {
        if (class_exists('Config\Logger')) {
            Logger::getInstance()->log('api_error', [
                'message' => $e->getMessage(),
                'code' => $statusCode,
                'uri' => $_SERVER['REQUEST_URI'],
                'method' => $_SERVER['REQUEST_METHOD'],
                'trace' => $e->getTraceAsString(),
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
        }
    } catch (Exception $logError) {
        error_log("Failed to log error: " . $logError->getMessage());
    }
}