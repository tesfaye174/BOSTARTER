<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../utils/ApiResponse.php';

use BOSTARTER\Utils\AuthService;

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        ApiResponse::error('Formato JSON non valido');
    }

    $authService = new AuthService();
    $result = $authService->signup($input);

    ApiResponse::success($result, 'Registrazione completata con successo');

} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, '{') === 0) {
        ApiResponse::invalidInput(json_decode($errorMessage, true));
    } else {
        error_log("Error in signup.php: " . $errorMessage);
        ApiResponse::serverError('Errore interno del server');
    }
}