<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Gestione delle richieste OPTIONS per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verifica del token CSRF
function validateCSRFToken() {
    $headers = getallheaders();
    if (!isset($headers['X-CSRF-Token']) || !verify_csrf_token($headers['X-CSRF-Token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF non valido']);
        exit();
    }
    return true;
}

// Ottieni il percorso della richiesta
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/BOSTARTER/backend/', '', $path);

// Routing delle richieste
// Routing delle richieste
switch ($path) {
    case 'auth/login.php':
        // Il login non richiede CSRF per permettere l'accesso iniziale
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($controller->login($data));
        break;

    case 'auth/register.php':
        // La registrazione non richiede CSRF per permettere l'accesso iniziale
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($controller->register($data));
        break;

    case 'projects/create.php':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validateCSRFToken();
        }
        require_once __DIR__ . '/controllers/ProjectController.php';
        $controller = new ProjectController();
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($controller->createProject($data));
        break;

    case 'projects/get_by_creator.php':
        // Le richieste GET non richiedono CSRF
        require_once __DIR__ . '/controllers/ProjectController.php';
        $controller = new ProjectController();
        $creator_id = $_GET['creator_id'] ?? null;
        if ($creator_id) {
            echo json_encode($controller->getCreatorProjects($creator_id));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID creatore mancante']);
        }
        break;

    case 'projects/fund.php':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validateCSRFToken();
        }
        require_once __DIR__ . '/controllers/ProjectController.php';
        $controller = new ProjectController();
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($controller->fundProject($data));
        break;

    case 'projects/add_reward.php':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validateCSRFToken();
        }
        require_once __DIR__ . '/controllers/ProjectController.php';
        $controller = new ProjectController();
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($controller->addProjectReward($data));
        break;

    case 'projects/publish.php':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validateCSRFToken();
        }
        require_once __DIR__ . '/controllers/ProjectController.php';
        $controller = new ProjectController();
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($controller->publishProject($data['project_id']));
        break;

    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Endpoint non trovato']);
        break;
}
?>