<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// Verifica che la richiesta sia POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit();
}

try {
    // Ottieni i dati dalla richiesta
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['rememberToken'])) {
        throw new Exception('Token non fornito');
    }

    // Connessione al database
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();

    // Verifica il token remember
    $stmt = $pdo->prepare("
        SELECT u.* 
        FROM users u 
        WHERE u.remember_token = ? 
        AND u.status = 'active'
    ");
    
    $stmt->execute([$data['rememberToken']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Token non valido o utente non attivo');
    }

    // Genera un nuovo token JWT
    $token = JWT::encode([
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ],
        'exp' => time() + Config::get('security.token_expiry')
    ], Config::get('security.jwt_secret'));

    // Genera un nuovo token remember
    $newRememberToken = bin2hex(random_bytes(32));
    
    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $stmt->execute([$newRememberToken, $user['id']]);

    // Registra il refresh del token
    $stmt = $pdo->prepare("INSERT INTO token_refresh_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([
        $user['id'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);

    // Risposta di successo
    echo json_encode([
        'success' => true,
        'message' => 'Token aggiornato con successo',
        'token' => $token,
        'rememberToken' => $newRememberToken
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 