<?php
require_once '../config/config.php';
require_once '../utils/Response.php';
require_once '../middleware/AuthMiddleware.php';
require_once '../config/mongodb.php';

use Config\Logger;

// Security headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGINS);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Rate limiting check
    RateLimiter::check();
    
    // Parse URI
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', trim($uri, '/'));
    
    if (!isset($uri[1]) || $uri[1] !== 'api') {
        throw new ApiException('Invalid API endpoint', 404);
    }
    
    $endpoint = $uri[2] ?? '';
    $action = $uri[3] ?? '';
    $id = $uri[4] ?? null;
    
    // Validate endpoint
    if (!in_array($endpoint, ['auth', 'projects', 'users', 'admin'])) {
        throw new ApiException('Unknown endpoint', 404);
    }
    
    // Initialize controller
    $controllerClass = ucfirst($endpoint) . 'Controller';
    $controllerFile = __DIR__ . "/{$endpoint}/{$controllerClass}.php";
    
    if (!file_exists($controllerFile)) {
        throw new ApiException('Controller not found', 500);
    }
    
    require_once $controllerFile;
    $controller = new $controllerClass();
    
    // Log request
    Logger::getInstance()->log('api_request', [
        'endpoint' => $endpoint,
        'action' => $action,
        'method' => $_SERVER['REQUEST_METHOD'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
    
    // Handle request
    $response = $controller->handleRequest($_SERVER['REQUEST_METHOD'], $action, $id);
    
    // Log successful response
    Logger::getInstance()->log('api_response', [
        'endpoint' => $endpoint,
        'action' => $action,
        'status' => 'success'
    ]);
    
    Response::success($response);
    
} catch (ApiException $e) {
    // Log API-specific errors
    Logger::getInstance()->log('api_error', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
    Response::error($e->getMessage(), $e->getCode());
    
} catch (AuthException $e) {
    // Log authentication errors
    Logger::getInstance()->log('auth_error', [
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    Response::error($e->getMessage(), $e->getCode() ?: 401);
    
} catch (Exception $e) {
    // Log unexpected errors
    Logger::getInstance()->log('system_error', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
    Response::error('Internal Server Error', 500);
} finally {
    // Ensure all logs are flushed
    Logger::getInstance()->flush();
}
?>