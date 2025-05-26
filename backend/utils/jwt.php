<?php
// Funzioni per generare e validare JWT
require_once __DIR__ . '/../vendor/autoload.php'; // Se usi firebase/php-jwt

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function generate_jwt($user) {
    $key = 'TUO_SECRET_KEY';
    $payload = [
        'id' => $user['id'],
        'ruolo' => $user['ruolo'],
        'exp' => time() + 3600
    ];
    return JWT::encode($payload, $key, 'HS256');
}

function check_jwt_token() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Token mancante']);
        exit;
    }
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    try {
        $decoded = JWT::decode($token, new Key('TUO_SECRET_KEY', 'HS256'));
        return (array)$decoded;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token non valido']);
        exit;
    }
}
?>
