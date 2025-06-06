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
    }    // Connessione database
    $db = Database::getInstance()->getConnection();
      // Prima recuperiamo l'utente dal database
    $stmt = $db->prepare("SELECT id, email, password_hash, nickname, nome, cognome, tipo_utente, created_at FROM utenti WHERE email = ?");
    $stmt->execute([$input['email']]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        ApiResponse::unauthorized('Email o password non validi');
    }
    
    // Verifica password (supporta sia hash che plain text per compatibilità)
    $password_valid = false;
    if (password_verify($input['password'], $user_data['password_hash'])) {
        // Password hashata verificata
        $password_valid = true;
    } elseif ($input['password'] === $user_data['password_hash']) {
        // Password in plain text (per compatibilità con dati esistenti)
        $password_valid = true;
    }
    
    if (!$password_valid) {
        ApiResponse::unauthorized('Email o password non validi');
    }
    
    // Aggiorna ultimo accesso
    $stmt = $db->prepare("UPDATE utenti SET last_access = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$user_data['id']]);
    
    // Effettua login
    $auth = Auth::getInstance();
    $auth->login($user_data);// Log su MongoDB
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
