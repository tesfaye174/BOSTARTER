<?php
// Start secure session with strict parameters
$sessionParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// Regenerate session ID to prevent session fixation
if (empty($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Set security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS Configuration
$allowedOrigins = [
    'https://' . ($_SERVER['HTTP_HOST'] ?? ''),
    'http://localhost',
    'http://127.0.0.1'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');
header('Access-Control-Max-Age: 3600');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// Carica l'autoloader personalizzato BOSTARTER
require_once __DIR__ . '/../autoload.php';

// Inizializza il logger
$logger = new SimpleLogger(__DIR__ . '/../../logs/auth.log');

/**
 * Verifica se l'account è bloccato per troppi tentativi falliti
 */
function isAccountLocked($email) {
    global $logger;
    
    // Usa un file di lock temporaneo (sostituisci con Redis/Memcached in produzione)
    $lockFile = sys_get_temp_dir() . '/bostarter_auth_' . md5($email) . '.lock';
    
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        $lockDuration = 15 * 60; // 15 minuti di blocco
        
        if (time() - $lockTime < $lockDuration) {
            $logger->warning("Login bloccato per troppi tentativi falliti", ['email' => $email]);
            return true;
        } else {
            // Rimuovi il file di lock scaduto
            @unlink($lockFile);
        }
    }
    return false;
}

/**
 * Registra un tentativo di login fallito                   
 */
function recordFailedAttempt($email) {
    global $logger;
    
    $maxAttempts = 5;
    $lockFile = sys_get_temp_dir() . '/bostarter_auth_' . md5($email) . '.lock';
    $attemptsFile = sys_get_temp_dir() . '/bostarter_attempts_' . md5($email) . '.dat';
    
    // Incrementa il contatore dei tentativi
    $attempts = 0;
    if (file_exists($attemptsFile)) {
        $attempts = (int)file_get_contents($attemptsFile);
        if (time() - filemtime($attemptsFile) > 3600) { // Reset dopo 1 ora
            $attempts = 0;
        }
    }
    
    $attempts++;
    file_put_contents($attemptsFile, $attempts);
    
    if ($attempts >= $maxAttempts) {
        // Crea un file di lock
        file_put_contents($lockFile, time());
        $logger->warning("Account bloccato per troppi tentativi falliti", [
            'email' => $email,
            'attempts' => $attempts,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
}

// Verifica il token CSRF per richieste non-GET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$csrfToken || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $logger->warning("Tentativo di login con CSRF token non valido", [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        ApiResponse::error('Token di sicurezza non valido', 403);
    }
}

try {
    // Leggi e valida l'input JSON
    $json = file_get_contents('php://input');
    if (strlen($json) > 1024) { // Limita la dimensione dell'input
        ApiResponse::error('Payload troppo grande', 413);
    }
    
    $input = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        ApiResponse::error('Formato JSON non valido: ' . json_last_error_msg());
    }
    
    if (empty($input['email']) || empty($input['password'])) {
        ApiResponse::error('Email e password sono obbligatorie', 400);
    }
    
    // Sanitizza l'input
    $email = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);
    $password = $input['password']; // La password verrà gestita da AuthService
    
    // Verifica se l'account è bloccato
    if (isAccountLocked($email)) {
        ApiResponse::error('Account temporaneamente bloccato. Riprova tra 15 minuti.', 429);
    }

    $authService = new AuthService();
    
    try {
        $user_data = $authService->login($email, $password);
        
        // Login riuscito, resetta i tentativi falliti
        $attemptsFile = sys_get_temp_dir() . '/bostarter_attempts_' . md5($email) . '.dat';
        if (file_exists($attemptsFile)) {
            @unlink($attemptsFile);
        }
        
        // Rigenera l'ID di sessione dopo il login con successo
        session_regenerate_id(true);
        
        // Imposta il flag di autenticazione
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['last_activity'] = time();
        
        // Genera un nuovo token CSRF per la sessione
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        $logger->info("Login riuscito", [
            'user_id' => $user_data['id'],
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        // Registra il tentativo fallito
        recordFailedAttempt($email);
        
        // Logga l'errore ma non rivelare dettagli sensibili
        $logger->error("Tentativo di login fallito", [
            'email' => $email,
            'error' => $e->getMessage(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Delay per prevenire attacchi a forza bruta
        usleep(rand(100000, 2000000)); // 100ms-2s di ritardo casuale
        
        throw $e; // Rilancia l'eccezione per la gestione standard degli errori
    }

    $response = [
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
        'session_id' => session_id(),
        'csrf_token' => $_SESSION['csrf_token']
    ];
    
    // Imposta cookie HTTPOnly per il session ID
    setcookie(
        session_name(),
        session_id(),
        [
            'expires' => time() + 86400, // 24 ore
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
    
    ApiResponse::success($response, MessageManager::personalizedSuccess('login', $user_data['nome']));

} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    
    // Log dettagliato dell'errore
    $logger->error("Errore durante il login", [
        'error' => $errorMessage,
        'trace' => $e->getTraceAsString(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Mappa i messaggi di errore a risposte appropriate
    if (strpos($errorMessage, '{') === 0) {
        ApiResponse::invalidInput(json_decode($errorMessage, true));
    } else if ($errorMessage === 'Email o password non validi') {
        // Non rivelare se l'email esiste o meno
        ApiResponse::unauthorized('Credenziali non valide');
    } else if ($e->getCode() === 429) {
        // Troppi tentativi
        ApiResponse::error($errorMessage, 429);
    } else {
        // Errore generico - non rivelare dettagli interni
        ApiResponse::serverError('Si è verificato un errore durante l\'accesso. Riprova più tardi.');
    }
}