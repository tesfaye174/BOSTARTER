<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../models/UserCompliant.php';
require_once '../services/MongoLogger.php';
require_once '../services/AuthService.php';
require_once '../utils/Validator.php';
require_once '../utils/ApiResponse.php';

// Inizializza i servizi sicuri
$database = Database::getInstance();
$db = $database->getConnection();
$userModel = new UserCompliant();
$mongoLogger = new MongoLogger();
$authService = new AuthService();

$method = $_SERVER['REQUEST_METHOD'];
$request = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        handleAuthRequest($authService, $mongoLogger, $request);
        break;
    case 'GET':
        handleAuthCheck($authService, $mongoLogger);
        break;
    case 'DELETE':
        handleLogout($authService, $mongoLogger);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleAuthRequest($authService, $mongoLogger, $request) {
    if (!isset($request['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Action required']);
        return;
    }

    switch ($request['action']) {
        case 'login':
            handleLogin($authService, $mongoLogger, $request);
            break;
        case 'register':
            handleRegister($userModel, $mongoLogger, $request);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handleLogin($authService, $mongoLogger, $request) {
    try {
        // Usa AuthService per login sicuro
        $result = $authService->login(
            $request['email'] ?? '',
            $request['password'] ?? '',
            $request['remember_me'] ?? false,
            $request['csrf_token'] ?? ''
        );

        if ($result['success']) {
            // Log successful login
            $mongoLogger->logEvent('user_login', [
                'user_id' => $result['user']['id'],
                'email' => $result['user']['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'login_method' => 'api_compliant'
            ]);

            ApiResponse::success([
                'user' => $result['user'],
                'session_id' => session_id(),
                'csrf_token' => $_SESSION['csrf_token'] ?? null
            ], $result['message']);
        } else {
            // Log failed login attempt
            $mongoLogger->logEvent('login_failed', [
                'email' => $request['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'reason' => $result['message'],
                'login_method' => 'api_compliant'
            ]);

            ApiResponse::unauthorized($result['message']);
        }
    } catch (Exception $e) {
        $mongoLogger->logEvent('login_error', [
            'email' => $request['email'] ?? 'unknown',
            'error' => $e->getMessage(),
            'login_method' => 'api_compliant'
        ]);        ApiResponse::serverError('Errore interno durante il login');
    }
}

function handleRegister($userModel, $mongoLogger, $request) {
    try {
        // Validazione con metodo static standardizzato
        $validationResult = Validator::validateRegistration($request);
        
        if ($validationResult !== true) {
            http_response_code(400);
            echo json_encode(['error' => $validationResult]);
            return;
        }

        // Validazione aggiuntiva su sesso
        if (!in_array($request['sesso'], ['M', 'F'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Sesso deve essere M o F']);
            return;
        }

        // Validazione aggiuntiva su data di nascita
        $date = DateTime::createFromFormat('Y-m-d', $request['data_nascita']);
        if (!$date || $date->format('Y-m-d') !== $request['data_nascita']) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato data nascita non valido (YYYY-MM-DD)']);
            return;
        }
        $today = new DateTime();
        $age = $today->diff($date)->y;
        if ($age < 18) {
            http_response_code(400);
            echo json_encode(['error' => 'Devi avere almeno 18 anni per registrarti']);
            return;
        }

        // Check if user already exists
        if ($userModel->userExists($request['email'], $request['nickname'])) {
            http_response_code(409);
            echo json_encode(['error' => 'Email o nickname già in uso']);
            return;
        }

        // Use stored procedure for registration
        $result = $userModel->register($request);

        if ($result['success']) {
            // Log successful registration
            $mongoLogger->logEvent('user_registered', [
                'nickname' => $request['nickname'],
                'email' => $request['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            echo json_encode([
                'success' => true,
                'message' => $result['message']
            ]);
        } else {
            // Log failed registration
            $mongoLogger->logEvent('registration_failed', [
                'nickname' => $request['nickname'],
                'email' => $request['email'],
                'reason' => $result['message']
            ]);

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
    } catch (Exception $e) {
        $mongoLogger->logEvent('registration_error', [
            'request_data' => $request,
            'error' => $e->getMessage()
        ]);

        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}

function handleAuthCheck($userModel, $mongoLogger) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'isValid' => false,
            'message' => 'Non autenticato'
        ]);
        return;
    }

    $user = $userModel->getUserById($_SESSION['user_id']);
    if ($user) {
        echo json_encode([
            'success' => true,
            'isValid' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'isValid' => false,
            'message' => 'Utente non trovato'
        ]);
    }
}

function handleLogout($mongoLogger) {
    session_start();

    if (isset($_SESSION['user_id'])) {
        // Log logout
        $mongoLogger->logEvent('user_logout', [
            'user_id' => $_SESSION['user_id'],
            'session_duration' => time() - ($_SESSION['login_time'] ?? time())
        ]);
    }

    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logout effettuato con successo']);
}
?>
