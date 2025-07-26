<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        ApiResponse::error('Formato JSON non valido');
    }

    $authService = new AuthService();
    $user_data = $authService->login($input['email'] ?? '', $input['password'] ?? '');

    ApiResponse::success([
        'user' => [
            'id' => (int)$user_data['id'],
            'email' => $user_data['email'],
            'nickname' => $user_data['nickname'],
            'nome' => $user_data['nome'],
            'cognome' => $user_data['cognome'],
            'tipo_utente' => $user_data['tipo_utente'],
            'avatar' => null,  
            'bio' => null,     
            'data_registrazione' => $user_data['created_at']
        ],
        'session_id' => session_id()  
    ], 'Login effettuato con successo');

} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, '{') === 0) {
        ApiResponse::invalidInput(json_decode($errorMessage, true));
    } else if ($errorMessage === 'Email o password non validi') {
        ApiResponse::unauthorized($errorMessage);
    } else {
        error_log("Error in login.php: " . $errorMessage);
        ApiResponse::serverError('Errore interno del server');
    }
}