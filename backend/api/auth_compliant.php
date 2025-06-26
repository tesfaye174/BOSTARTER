<?php
// Configuriamo gli header per l'API REST
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestiamo le richieste OPTIONS per il CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Includiamo tutte le dipendenze necessarie
require_once '../config/database.php';
require_once '../models/UserCompliant.php';
require_once '../services/MongoLogger.php';
require_once '../services/AuthService.php';
require_once '../utils/Validator.php';
require_once '../utils/ApiResponse.php';

// Inizializziamo tutti i servizi necessari
$database = Database::getInstance();
$connessione = $database->getConnection();
$modelloUtente = new UserCompliant();
$logger = new MongoLogger();
$servizioAuth = new AuthService();

// Otteniamo il metodo HTTP e i dati della richiesta
$metodo = $_SERVER['REQUEST_METHOD'];
$richiestaJson = json_decode(file_get_contents('php://input'), true);

// Gestiamo le diverse operazioni in base al metodo HTTP
switch ($metodo) {
    case 'POST':
        gestisciRichiestaAuth($servizioAuth, $logger, $richiestaJson);
        break;
    case 'GET':
        verificaStatoAuth($servizioAuth, $logger);
        break;
    case 'DELETE':
        gestisciLogout($servizioAuth, $logger);
        break;
    default:
        http_response_code(405);
        echo json_encode(['errore' => 'Metodo non consentito']);
        break;
}

/**
 * Gestisce le richieste di autenticazione (login e registrazione)
 */
function gestisciRichiestaAuth($servizioAuth, $logger, $richiesta) {
    if (!isset($richiesta['azione'])) {
        http_response_code(400);
        echo json_encode(['errore' => 'Azione richiesta mancante']);
        return;
    }

    switch ($richiesta['azione']) {
        case 'login':
            gestisciLogin($servizioAuth, $logger, $richiesta);
            break;
        case 'registrazione':
            gestisciRegistrazione($modelloUtente, $logger, $richiesta);
            break;
        default:
            http_response_code(400);
            echo json_encode(['errore' => 'Azione non valida']);
            break;
    }
}

/**
 * Gestisce il processo di login dell'utente
 */
function gestisciLogin($servizioAuth, $logger, $richiesta) {
    try {
        // Utilizziamo il servizio di autenticazione per un login sicuro
        $risultato = $servizioAuth->login(
            $richiesta['email'] ?? '',
            $richiesta['password'] ?? '',
            $richiesta['ricordami'] ?? false,
            $richiesta['csrf_token'] ?? ''
        );

        if ($risultato['success']) {
            // Registriamo il login riuscito
            $logger->registraEvento('login_utente', [
                'id_utente' => $risultato['user']['id'],
                'email' => $risultato['user']['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'sconosciuto',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'sconosciuto',
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

        // Validazione aggiuntiva su anno di nascita
        if (!empty($request['anno_nascita']) && ($request['anno_nascita'] < 1900 || $request['anno_nascita'] > date('Y'))) {
            http_response_code(400);
            echo json_encode(['error' => 'Anno di nascita non valido']);
            return;
        }
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
