<?php
// filepath: c:\xampp\htdocs\BOSTARTER\backend\api\login.php

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Gestione preflight
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
require_once __DIR__ . '/../utils/FluentValidator.php';
require_once __DIR__ . '/../utils/Auth.php';

try {
    // Leggi input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ApiResponse::error('Formato JSON non valido');
    }
    
    // Validazione input
    $validator = new FluentValidator();
    $validator->required('email', $input['email'] ?? '')->email();
    $validator->required('password', $input['password'] ?? '');
    
    if (!$validator->isValid()) {
        ApiResponse::invalidInput($validator->getErrors());
    }
    
    // Connessione database
    $db = Database::getInstance()->getConnection();
    
    // Chiama stored procedure per login
    $stmt = $db->prepare("CALL sp_login_utente(?, @user_id, @password_hash, @tipo_utente, @stato, @result)");
    $stmt->execute([$input['email']]);
    
    // Recupera risultato
    $result = $db->query("SELECT @user_id as user_id, @password_hash as password_hash, @tipo_utente as tipo_utente, @stato as stato, @result as result")->fetch();
    
    if ($result['result'] !== 'SUCCESS') {
        ApiResponse::unauthorized($result['result']);
    }
    
    // Verifica password
    if (!password_verify($input['password'], $result['password_hash'])) {
        ApiResponse::unauthorized('Email o password non validi');
    }
    
    // Recupera dati completi utente
    $stmt = $db->prepare("
        SELECT id, email, nickname, nome, cognome, tipo_utente, avatar, bio, data_registrazione
        FROM utenti 
        WHERE id = ?
    ");
    $stmt->execute([$result['user_id']]);
    $user_data = $stmt->fetch();
    
    // Effettua login
    $auth = Auth::getInstance();
    $auth->login($user_data);
      // Log su MongoDB
    $mongoLogger = new MongoLogger();
    $mongoLogger->logUserLogin(
        $user_data['id'],
        $user_data['email'],
        ['ip_address' => $_SERVER['REMOTE_ADDR'] ?? null]
    );
    
    // Risposta di successo
    ApiResponse::success([
        'user' => [
            'id' => (int)$user_data['id'],
            'email' => $user_data['email'],
            'nickname' => $user_data['nickname'],
            'nome' => $user_data['nome'],
            'cognome' => $user_data['cognome'],
            'tipo_utente' => $user_data['tipo_utente'],
            'avatar' => $user_data['avatar'],
            'bio' => $user_data['bio'],
            'data_registrazione' => $user_data['data_registrazione']
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
