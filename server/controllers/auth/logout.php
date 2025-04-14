<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Verifica che la richiesta sia POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit();
}

try {
    // Ottieni il token dalla richiesta
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        throw new Exception('Token non fornito');
    }

    // Connessione al database
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();

    // Aggiungi il token alla blacklist
    $stmt = $pdo->prepare("INSERT INTO token_blacklist (token, expires_at) VALUES (?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
    $stmt->execute([$token]);

    // Registra il logout
    $stmt = $pdo->prepare("INSERT INTO logout_logs (token, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([
        $token,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);

    // Risposta di successo
    echo json_encode([
        'success' => true,
        'message' => 'Logout effettuato con successo'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 