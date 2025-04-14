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
    
    if (!$data) {
        throw new Exception('Dati non validi');
    }

    // Verifica i campi obbligatori
    $required_fields = ['email', 'password'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Il campo $field è obbligatorio");
        }
    }

    // Verifica se è un login da amministratore
    $isAdmin = isset($data['isAdmin']) && $data['isAdmin'] === true;
    if ($isAdmin && empty($data['securityCode'])) {
        throw new Exception('Il codice di sicurezza è obbligatorio per gli amministratori');
    }

    // Verifica il codice di sicurezza per gli amministratori
    if ($isAdmin) {
        $securityCode = Config::get('security.admin_code');
        if ($data['securityCode'] !== $securityCode) {
            throw new Exception('Codice di sicurezza non valido');
        }
    }

    // Connessione al database
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();

    // Prepara la query
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica l'utente
    if (!$user) {
        throw new Exception('Email o password non validi');
    }

    // Verifica la password
    if (!password_verify($data['password'], $user['password'])) {
        throw new Exception('Email o password non validi');
    }

    // Verifica se l'utente è attivo
    if ($user['status'] !== 'active') {
        throw new Exception('Account non attivo. Contatta l\'amministratore.');
    }

    // Genera il token JWT
    $token = JWT::encode([
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ],
        'exp' => time() + Config::get('security.token_expiry')
    ], Config::get('security.jwt_secret'));

    // Genera il token per il "ricordami" se richiesto
    $rememberToken = null;
    if (isset($data['rememberMe']) && $data['rememberMe'] === true) {
        $rememberToken = bin2hex(random_bytes(32));
        
        // Salva il token nel database
        $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $stmt->execute([$rememberToken, $user['id']]);
    }

    // Registra il login
    $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([
        $user['id'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);

    // Risposta di successo
    echo json_encode([
        'success' => true,
        'message' => 'Login effettuato con successo',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ],
        'token' => $token,
        'rememberToken' => $rememberToken
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 