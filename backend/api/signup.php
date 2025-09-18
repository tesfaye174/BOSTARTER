<?php
session_start();
header('Content-Type: application/json');
// Gestione sicura CORS
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
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// Carica l'autoloader personalizzato BOSTARTER
require_once __DIR__ . '/../autoload.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        ApiResponse::error('I dati ricevuti non sono in un formato valido');
    }

    $authService = new AuthService();
    $result = $authService->signup($input);

    ApiResponse::success($result, MessageManager::get('signup_success'));

} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, '{') === 0) {
        ApiResponse::invalidInput(json_decode($errorMessage, true));
    } else {
        error_log("Error in signup.php: " . $errorMessage);
        ApiResponse::serverError('Errore interno del server');
    }
}