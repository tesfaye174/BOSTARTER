<?php
// filepath: c:\xampp\htdocs\BOSTARTER\backend\api\login.php

/**
 * API di Login per BOSTARTER
 * 
 * Questo file gestisce l'autenticazione degli utenti alla piattaforma.
 * Verifica le credenziali fornite (email e password) e, se corrette,
 * crea una sessione per l'utente e restituisce i dati necessari al frontend.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 */

// Avviamo la sessione per memorizzare i dati dell'utente
session_start();

// Impostiamo le intestazioni HTTP per l'API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Gestione delle richieste preflight OPTIONS (necessarie per CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verifichiamo che la richiesta sia di tipo POST (sicurezza)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Metodo non consentito
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// Includiamo le dipendenze necessarie
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MongoLogger.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/../utils/Validator.php';

try {
    // Leggiamo i dati inviati in formato JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Verifichiamo che il JSON sia valido
    if (!$input) {
        ApiResponse::error('Formato JSON non valido');
    }
    
    // Validiamo i campi obbligatori con il nostro validatore
    $validator = new Validator();
    $validator->required('email', $input['email'] ?? '')->email();
    $validator->required('password', $input['password'] ?? '');
    
    // Se la validazione fallisce, rispondiamo con gli errori
    if (!$validator->isValid()) {
        ApiResponse::invalidInput($validator->getErrors());
    }
    
    // Ci connettiamo al database
    $db = Database::getInstance()->getConnection();
      
    // Recuperiamo l'utente dal database usando l'email
    $stmt = $db->prepare("SELECT id, email, password_hash, nickname, nome, cognome, tipo_utente, created_at FROM utenti WHERE email = ?");
    $stmt->execute([$input['email']]);
    $user_data = $stmt->fetch();
    
    // Se l'utente non esiste, rispondiamo con errore generico (per sicurezza)
    if (!$user_data) {
        ApiResponse::unauthorized('Email o password non validi');
    }
    
    // Verifica della password dell'utente
    $password_valid = false;
    if (!empty($user_data['password_hash'])) {
        // Utilizziamo la funzione sicura password_verify per confrontare la password inserita con l'hash memorizzato
        if (password_verify($input['password'], $user_data['password_hash'])) {
            $password_valid = true;
        }
        // VULNERABILITÀ RIMOSSA: Non supportiamo più password in chiaro
    }
    
    // Se la password non corrisponde, rispondiamo con errore generico (per sicurezza)
    if (!$password_valid) {
        ApiResponse::unauthorized('Email o password non validi');
    }
    
    // Aggiorniamo la data e ora dell'ultimo accesso dell'utente
    $stmt = $db->prepare("UPDATE utenti SET last_access = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$user_data['id']]);
    
    // Impostiamo i dati della sessione per l'utente autenticato
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
    
    // Rigeneriamo l'ID di sessione per prevenire il session fixation attack
    session_regenerate_id(true);
    
    // Registriamo l'accesso nel sistema di logging MongoDB per analisi e sicurezza
    $mongoLogger = new MongoLogger();
    $mongoLogger->logUserLogin(
        $user_data['id'],
        $user_data['email'],
        ['ip_address' => $_SERVER['REMOTE_ADDR'] ?? null]
    );
    
    // Inviamo una risposta di successo con i dati dell'utente
    ApiResponse::success([
        'user' => [
            'id' => (int)$user_data['id'],
            'email' => $user_data['email'],
            'nickname' => $user_data['nickname'],
            'nome' => $user_data['nome'],
            'cognome' => $user_data['cognome'],
            'tipo_utente' => $user_data['tipo_utente'],
            'avatar' => null,  // Questi campi potrebbero essere popolati in futuro
            'bio' => null,     // da una query più completa
            'data_registrazione' => $user_data['created_at']
        ],
        'session_id' => session_id()  // Utile per il frontend per tracciare la sessione
    ], 'Login effettuato con successo');
    
} catch (PDOException $e) {
    // Gestiamo gli errori del database, registrando l'errore ma nascondendolo all'utente
    error_log("Database error in login.php: " . $e->getMessage());
    ApiResponse::serverError('Errore durante il login');
} catch (Exception $e) {
    // Gestiamo qualsiasi altro errore inaspettato
    error_log("Error in login.php: " . $e->getMessage());
    ApiResponse::serverError('Errore interno del server');
}
