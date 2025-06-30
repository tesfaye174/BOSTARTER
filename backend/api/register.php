<?php
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
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MongoLogger.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/../utils/Validator.php';
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
try {
    $input = json_decode(file_get_contents('php:
    if (!$input) {
        ApiResponse::error('Formato JSON non valido');
    }
    $validator = new Validator();
    $validator->required('email', $input['email'] ?? '')->email();
    $validator->required('nickname', $input['nickname'] ?? '')->minLength(3)->maxLength(50);
    $validator->required('password', $input['password'] ?? '')->minLength(8);
    $validator->required('nome', $input['nome'] ?? '')->maxLength(100);
    $validator->required('cognome', $input['cognome'] ?? '')->maxLength(100);
    $validator->required('anno_nascita', $input['anno_nascita'] ?? '')->integer()->min(1900)->max(date('Y') - 13);
    $validator->required('luogo_nascita', $input['luogo_nascita'] ?? '')->maxLength(100);
    if (!$validator->isValid()) {
        ApiResponse::invalidInput($validator->getErrors());
    }
    $db = Database::getInstance()->getConnection();
    $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, ?, @user_id, @success, @message)");
    $stmt->execute([
        $input['email'],
        $input['nickname'],
        $password_hash,
        $input['nome'],
        $input['cognome'],
        $input['anno_nascita'],
        $input['luogo_nascita'],
        'standard' 
    ]);
    $result = $db->query("SELECT @user_id as user_id, @success as success, @message as message")->fetch();
      if (!$result['success']) {
        ApiResponse::error($result['message'], 400);
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = (int)$result['user_id'];
    $_SESSION['user'] = [
        'id' => (int)$result['user_id'],
        'email' => $input['email'],
        'username' => $input['nickname'],
        'tipo_utente' => 'standard',
        'nome' => $input['nome'],
        'cognome' => $input['cognome']
    ];
    $mongoLogger = new MongoLogger();
    $mongoLogger->logUserRegistration(
        $result['user_id'],
        $input['email'],
        [
            'nickname' => $input['nickname'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'auto_login' => true
        ]
    );
    ApiResponse::success([
        'user_id' => (int)$result['user_id'],
        'email' => $input['email'],
        'nickname' => $input['nickname'],
        'redirect_url' => '/BOSTARTER/frontend/dashboard.php'
    ], 'Registrazione completata con successo');
} catch (PDOException $e) {
    error_log("Database error in register.php: " . $e->getMessage());
    ApiResponse::serverError('Errore durante la registrazione: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Error in register.php: " . $e->getMessage());
    ApiResponse::serverError('Errore interno del server: ' . $e->getMessage());
}
