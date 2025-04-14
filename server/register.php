<?php
namespace Server;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/mailer.php';

use Config\Database;
use Config\Security;
use Server\Mailer;

// Inizializza le classi di sicurezza
$security = Security::getInstance();
$csrf = CSRF::getInstance();
$rateLimiter = RateLimiter::getInstance();
$mailer = Mailer::getInstance();

// Imposta gli header di sicurezza
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header("Content-Security-Policy: default-src 'self'");
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Gestione CORS
$allowedOrigins = ['http://localhost', 'https://bostarter.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');
    header('Access-Control-Allow-Credentials: true');
}

// Gestione richieste OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

try {
    // Verifica il metodo della richiesta
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \Exception('Metodo non consentito', 405);
    }

    // Verifica rate limiting
    $clientIp = $_SERVER['REMOTE_ADDR'];
    if (!$rateLimiter->isAllowed("register:$clientIp", 3, 3600)) { // 3 tentativi per ora
        throw new \Exception('Troppi tentativi di registrazione. Riprova più tardi.', 429);
    }

    // Leggi e valida i dati della richiesta
    $input = file_get_contents('php://input');
    if (strlen($input) > 8192) { // Limita la dimensione dell'input
        throw new \Exception('Payload troppo grande', 413);
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Dati JSON non validi', 400);
    }

    // Sanifica input
    $data = $security->sanitizeInput($data);

    // Verifica CSRF
    if (!$csrf->verifyRequest()) {
        throw new \Exception('Token di sicurezza non valido', 403);
    }

    // Validazione campi obbligatori
    $requiredFields = ['email', 'password', 'nickname', 'name', 'surname'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new \Exception("Il campo $field è obbligatorio", 400);
        }
    }

    // Validazione email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new \Exception('Formato email non valido', 400);
    }

    // Validazione password
    if (!$security->validatePassword($data['password'])) {
        throw new \Exception('La password deve contenere almeno 12 caratteri, una lettera maiuscola, un numero e un carattere speciale', 400);
    }

    // Validazione nickname
    if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $data['nickname'])) {
        throw new \Exception('Il nickname deve contenere tra 3 e 30 caratteri alfanumerici, trattini o underscore', 400);
    }

    // Validazione nome e cognome
    $namePattern = '/^[a-zA-ZÀ-ÿ\s\'-]{2,50}$/u';
    if (!preg_match($namePattern, $data['name'])) {
        throw new \Exception('Nome non valido', 400);
    }
    if (!preg_match($namePattern, $data['surname'])) {
        throw new \Exception('Cognome non valido', 400);
    }

    // Ottieni la connessione al database
    $db = Database::getInstance()->getConnection();

    try {
        // Inizia la transazione
        $db->beginTransaction();

        // Verifica se l'email esiste già
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            throw new \Exception('Email già registrata', 409);
        }

        // Verifica se il nickname esiste già
        $stmt = $db->prepare("SELECT id FROM users WHERE nickname = ?");
        $stmt->execute([$data['nickname']]);
        if ($stmt->fetch()) {
            throw new \Exception('Nickname già in uso', 409);
        }

        // Hash della password
        $hashedPassword = password_hash($data['password'], PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        // Genera token di attivazione
        $activationToken = bin2hex(random_bytes(32));
        $activationExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Inserisci il nuovo utente
        $sql = "INSERT INTO users (email, password, nickname, name, surname, activation_token, 
                                 activation_expires, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['email'],
            $hashedPassword,
            $data['nickname'],
            $data['name'],
            $data['surname'],
            $activationToken,
            $activationExpiry
        ]);

        $userId = $db->lastInsertId();

        // Log della registrazione
        $logSql = "INSERT INTO user_logs (user_id, action, ip_address, created_at) 
                   VALUES (?, 'registration', ?, NOW())";
        $logStmt = $db->prepare($logSql);
        $logStmt->execute([$userId, $clientIp]);

        // Commit della transazione
        $db->commit();

        // Invia email di attivazione
        $activationUrl = "https://bostarter.com/activate.php?token=" . urlencode($activationToken);
        $emailResult = $mailer->sendActivationEmail(
            $data['email'],
            $data['name'],
            $activationUrl
        );

        if (!$emailResult) {
            error_log("Errore nell'invio dell'email di attivazione per l'utente $userId");
        }

        // Reset rate limiter dopo registrazione riuscita
        $rateLimiter->reset("register:$clientIp");

        // Risposta di successo
        echo json_encode([
            'success' => true,
            'message' => 'Registrazione completata con successo. Controlla la tua email per attivare l\'account.',
            'user_id' => $userId
        ]);

    } catch (\PDOException $e) {
        // Rollback in caso di errore
        $db->rollBack();
        throw $e;
    }

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
