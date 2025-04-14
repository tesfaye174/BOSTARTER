<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/security.php';
require_once 'config/cache.php';
require_once 'config/mongodb.php';

// Gestione delle richieste OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ottieni il metodo della richiesta
$method = $_SERVER['REQUEST_METHOD'];

// Ottieni l'endpoint dalla URL
$request_uri = $_SERVER['REQUEST_URI'];
$endpoint = parse_url($request_uri, PHP_URL_PATH);
$endpoint = str_replace('/server/', '', $endpoint);

// Ottieni i parametri della query
$query_params = $_GET;

// Ottieni il corpo della richiesta
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// Gestione degli errori
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server',
        'error' => $errstr
    ]);
    exit();
});

// Gestione delle eccezioni
set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server',
        'error' => $exception->getMessage()
    ]);
    exit();
});

try {
    // Verifica l'autenticazione per le richieste protette
    $requires_auth = !in_array($endpoint, ['login.php', 'register.php', 'refresh-token.php']);
    
    if ($requires_auth) {
        $token = getBearerToken();
        if (!$token) {
            throw new Exception('Token non fornito');
        }
        
        $user = verifyToken($token);
        if (!$user) {
            throw new Exception('Token non valido');
        }
    }

    // Gestione delle richieste
    switch ($endpoint) {
        case 'login.php':
            require_once 'controllers/auth/login.php';
            break;
            
        case 'register.php':
            require_once 'controllers/auth/register.php';
            break;
            
        case 'logout.php':
            require_once 'controllers/auth/logout.php';
            break;
            
        case 'refresh-token.php':
            require_once 'controllers/auth/refresh-token.php';
            break;
            
        case 'projects.php':
            require_once 'controllers/projects.php';
            break;
            
        case 'profile.php':
            require_once 'controllers/profile.php';
            break;
            
        case 'stats.php':
            require_once 'controllers/stats.php';
            break;
            
        case 'admin/stats.php':
            require_once 'controllers/admin/stats.php';
            break;
            
        case 'admin/users.php':
            require_once 'controllers/admin/users.php';
            break;
            
        default:
            throw new Exception('Endpoint non trovato');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Funzioni di utilità
function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function verifyToken($token) {
    try {
        $decoded = JWT::decode($token, Config::get('security.jwt_secret'), ['HS256']);
        return $decoded->user;
    } catch (Exception $e) {
        return null;
    }
}