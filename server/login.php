<?php
namespace Server;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/csrf.php';

use Config\Database;
use Config\Security;

// Inizializza le classi di sicurezza
$security = Security::getInstance();
$csrf = CSRF::getInstance();
$rateLimiter = RateLimiter::getInstance();

// Gestione CORS e headers di sicurezza
$security->handleCORS();
$security->setSecurityHeaders();

try {
    // Verifica il metodo della richiesta
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \Exception('Metodo non consentito', 405);
    }

    // Verifica rate limiting
    $clientIp = $_SERVER['REMOTE_ADDR'];
    if (!$rateLimiter->isAllowed("login:$clientIp", 5, 300)) { // 5 tentativi in 5 minuti
        throw new \Exception('Troppi tentativi di accesso. Riprova più tardi.', 429);
    }

    // Leggi e valida i dati della richiesta
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Dati non validi', 400);
    }

    // Sanifica input
    $data = $security->sanitizeInput($data);

    // Verifica CSRF
    if (!$csrf->verifyRequest()) {
        throw new \Exception('Token di sicurezza non valido', 403);
    }

    // Validazione campi obbligatori
    if (empty($data['email']) || empty($data['password'])) {
        throw new \Exception('Email e password sono obbligatori', 400);
    }

    // Ottieni la connessione al database
    $db = Database::getInstance()->getConnection();

    // Cerca l'utente
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data['password'], $user['password'])) {
        throw new \Exception('Credenziali non valide', 401);
    }

    // Genera il token di accesso
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Salva il token nel database
    $tokenSql = "INSERT INTO access_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
    $tokenStmt = $db->prepare($tokenSql);
    $tokenStmt->execute([$user['id'], $token, $expiry]);

    // Aggiorna l'ultimo accesso
    $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->execute([$user['id']]);

    // Rimuovi dati sensibili
    unset($user['password']);
    unset($user['reset_token']);
    unset($user['activation_token']);

    // Reset rate limiter dopo login riuscito
    $rateLimiter->reset("login:$clientIp");

    // Risposta di successo
    echo json_encode([
        'success' => true,
        'message' => 'Login effettuato con successo',
        'user' => $user,
        'access_token' => $token,
        'expires_at' => $expiry
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Errore del server',
        'code' => 500
    ]);
} catch (\Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $code
    ]);
}
