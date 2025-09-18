<?php
/**
 * BOSTARTER - API Initialization
 * Common initialization for all API endpoints
 */

// Start session with secure parameters
$sessionParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// Regenerate session ID to prevent session fixation
if (empty($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// CORS handling
$allowedOrigin = null;
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    $parsed = parse_url($origin);
    $host = $parsed['host'] ?? '';
    if (in_array($host, [$_SERVER['HTTP_HOST'] ?? '', 'localhost', '127.0.0.1'])) {
        $allowedOrigin = $origin;
    }
}
if ($allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: same-origin');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Load autoloader
require_once __DIR__ . '/../autoload.php';

// Set content type
header('Content-Type: application/json');

// Disable errors in production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
?>
