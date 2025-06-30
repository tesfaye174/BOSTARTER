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
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MongoLogger.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/../utils/Validator.php';
try {
    $input = json_decode(file_get_contents('php:
    if (!$input) {
        ApiResponse::error('Formato JSON non valido');
    }
    $validator = new Validator();
    $validator->required('email', $input['email'] ?? '')->email();
    $validator->required('password', $input['password'] ?? '');
    if (!$validator->isValid()) {
        ApiResponse::invalidInput($validator->getErrors());
    }
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, email, password_hash, nickname, nome, cognome, tipo_utente, created_at FROM utenti WHERE email = ?");
    $stmt->execute([$input['email']]);
    $user_data = $stmt->fetch();
    if (!$user_data) {
        ApiResponse::unauthorized('Email o password non validi');
    }
    $password_valid = false;
    if (!empty($user_data['password_hash'])) {
        if (password_verify($input['password'], $user_data['password_hash'])) {
            $password_valid = true;
        }
    }
    if (!$password_valid) {
        ApiResponse::unauthorized('Email o password non validi');
    }
    $stmt = $db->prepare("UPDATE utenti SET last_access = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$user_data['id']]);
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['user_email'] = $user_data['email'];
    $_SESSION['user_tipo'] = $user_data['tipo_utente'];
    $_SESSION['login_time'] = time();
    $_SESSION['user'] = [
        'id' => $user_data['id'],
        'email' => $user_data['email'],
        'username' => $user_data['nickname'],
        'tipo_utente' => $user_data['tipo_utente'],
        'nome' => $user_data['nome'],
        'cognome' => $user_data['cognome']
    ];
    session_regenerate_id(true);
    $mongoLogger = new MongoLogger();
    $mongoLogger->logUserLogin(
        $user_data['id'],
        $user_data['email'],
        ['ip_address' => $_SERVER['REMOTE_ADDR'] ?? null]
    );
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
} catch (PDOException $e) {
    error_log("Database error in login.php: " . $e->getMessage());
    ApiResponse::serverError('Errore durante il login');
} catch (Exception $e) {
    error_log("Error in login.php: " . $e->getMessage());
    ApiResponse::serverError('Errore interno del server');
}
