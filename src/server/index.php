<?php
declare(strict_types=1);

// Autoload dependencies (assuming Composer is used or a similar autoloader exists)
// require_once __DIR__ . '/vendor/autoload.php'; 

// Load core components manually if no autoloader
require_once __DIR__ . '/config/config.php'; // Defines constants like ALLOWED_ORIGINS, JWT_SECRET etc.
require_once __DIR__ . '/config/database.php'; // Defines DB constants like DB_HOST etc.
require_once __DIR__ . '/config/DatabaseConfig.php';
require_once __DIR__ . '/config/ConnectionPool.php';
require_once __DIR__ . '/config/Logger.php';
require_once __DIR__ . '/utils/Response.php';
// require_once __DIR__ . '/utils/Validator.php'; // Assuming a Validator utility exists
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/config/routes.php'; // Contains route definitions

use Config\Logger;
use Config\DatabaseConfig;
use Server\Router;
use Utils\Response;
use Middleware\AuthMiddleware;

// --- Error Reporting (Development vs Production) ---
if (defined('APP_ENV') && APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
    // Consider setting a custom error handler for production logging
}

// --- Session Management ---
if (session_status() === PHP_SESSION_NONE) {
    // Configure session security settings before starting
    session_set_cookie_params([
        'lifetime' => 0, // Expires when browser closes
        'path' => '/',
        'domain' => '', // Set your domain in production
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Use secure cookies in production
        'httponly' => true,
        'samesite' => 'Lax' // Or 'Strict'
    ]);
    session_start();
}

// --- CSRF Token Generation ---
if (!isset($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Handle error during token generation (log and potentially halt)
        Logger::getInstance()->log('error', ['message' => 'Failed to generate CSRF token', 'exception' => $e->getMessage()]);
        Response::sendError('Internal Server Error', 500);
        exit;
    }
}

// --- Security Headers ---
// Use constants or configuration for allowed origins
header('Access-Control-Allow-Origin: ' . (defined('ALLOWED_ORIGINS') ? ALLOWED_ORIGINS : '*')); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
header('Access-Control-Allow-Credentials: true'); // If using cookies/sessions across origins
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
// Consider Content-Security-Policy header for added security
// header('Content-Security-Policy: default-src \'self\''); 
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// --- Handle Preflight OPTIONS Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // No further processing needed for OPTIONS
    exit(0);
}

// --- Request Processing ---
try {
    // Initialize Router
    $router = Router::getInstance();

    // --- Global Middleware ---
    // 1. Input Parsing & Sanitization Middleware
    $router->addGlobalMiddleware(function () {
        $contentType = trim($_SERVER['CONTENT_TYPE'] ?? '');
        $inputData = [];

        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $decoded = json_decode($input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException('Invalid JSON payload', 400);
                }
                $inputData = $decoded ?? [];
            }
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false || strpos($contentType, 'multipart/form-data') !== false) {
            $inputData = $_POST;
        }
        
        // Basic sanitization (consider a more robust library like htmlpurifier if needed)
        array_walk_recursive($inputData, function (&$value) {
            if (is_string($value)) {
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        });
        
        // Make parsed data available (e.g., through a request object or merge into $_REQUEST)
        // For simplicity, merging into $_REQUEST. Consider a dedicated Request class.
        $_REQUEST = array_merge($_REQUEST, $inputData);

        return true; // Continue processing
    });

    // 2. CSRF Validation Middleware (for relevant methods)
    $router->addGlobalMiddleware(function () {
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            $tokenSession = $_SESSION['csrf_token'] ?? null;

            if (!$tokenHeader || !$tokenSession || !hash_equals($tokenSession, $tokenHeader)) {
                 Logger::getInstance()->log('warning', ['message' => 'Invalid CSRF token attempt', 'ip' => $_SERVER['REMOTE_ADDR']]);
                 throw new \RuntimeException('Invalid CSRF token', 403);
            }
        }
        return true;
    });

    // --- Rate Limiting (Example - Implement using Redis/Memcached for production) ---
    // $router->setRateLimit('/api/', 100, 60); // Example: 100 requests per 60 seconds for all /api/ routes

    // --- Register Routes ---
    Config\Routes::register($router);

    // --- Handle Request ---
    $router->handleRequest();

} catch (\Throwable $e) {
    // --- Centralized Error Handling ---
    $statusCode = ($e instanceof \InvalidArgumentException || $e instanceof \RuntimeException) ? $e->getCode() : 500;
    if ($statusCode < 400 || $statusCode >= 600) {
        $statusCode = 500; // Default to 500 for unexpected codes
    }

    $errorMessage = $e->getMessage();

    // Log the detailed error
    Logger::getInstance()->log('error', [
        'message' => $errorMessage,
        'code' => $statusCode,
        'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        // 'trace' => $e->getTraceAsString() // Be cautious logging full traces in production
    ]);

    // Send a generic error response to the client
    $clientMessage = ($statusCode >= 500) ? 'Internal Server Error' : $errorMessage;
    Response::sendError($clientMessage, $statusCode);
}