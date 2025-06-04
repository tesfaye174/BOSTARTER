<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Auth.php';

try {
    $project = new Project();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            handleGetRequest($project, $action);
            break;
            
        case 'POST':
            handlePostRequest($project, $action);
            break;
            
        case 'PUT':
            handlePutRequest($project, $action);
            break;
            
        case 'DELETE':
            handleDeleteRequest($project, $action);
            break;
            
        default:
            ApiResponse::error('Metodo HTTP non supportato', 405);
    }
    
} catch (Exception $e) {
    error_log("Error in projects_api.php: " . $e->getMessage());
    ApiResponse::serverError('Errore interno del server');
}

function handleGetRequest($project, $action) {
    switch ($action) {
        case 'list':
            $filters = [];
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 10);
            
            // Apply filters if provided
            if (!empty($_GET['categoria'])) {
                $filters['categoria'] = $_GET['categoria'];
            }
            if (!empty($_GET['stato'])) {
                $filters['stato'] = $_GET['stato'];
            }
            if (!empty($_GET['competenza'])) {
                $filters['competenza'] = $_GET['competenza'];
            }
            
            $result = $project->getList($filters, $page, $perPage);
            if ($result !== null) {
                ApiResponse::success($result);
            } else {
                ApiResponse::serverError('Errore nel recupero dei progetti');
            }
            break;
            
        case 'details':
            $projectId = (int)($_GET['id'] ?? 0);
            if ($projectId <= 0) {
                ApiResponse::invalidInput(['id' => 'ID progetto richiesto']);
            }
            
            $result = $project->getDetails($projectId);
            if ($result !== null) {
                ApiResponse::success($result);
            } else {
                ApiResponse::error('Progetto non trovato', 404);
            }
            break;
            
        case 'donations':
            $projectId = (int)($_GET['id'] ?? 0);
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 10);
            
            if ($projectId <= 0) {
                ApiResponse::invalidInput(['id' => 'ID progetto richiesto']);
            }
            
            $result = $project->getDonations($projectId, $page, $perPage);
            if ($result !== null) {
                ApiResponse::success($result);
            } else {
                ApiResponse::serverError('Errore nel recupero dei finanziamenti');
            }
            break;
            
        default:
            ApiResponse::error('Azione non riconosciuta', 400);
    }
}

function handlePostRequest($project, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ApiResponse::error('Formato JSON non valido');
    }
    
    switch ($action) {
        case 'create':
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                ApiResponse::error('Accesso richiesto', 401);
            }
            
            // Add creator ID from session
            $input['creatore_id'] = $_SESSION['user_id'];
            
            // Validate required fields
            $validator = new Validator();
            $validator->required('nome', $input['nome'] ?? '')->minLength(3)->maxLength(200);
            $validator->required('descrizione', $input['descrizione'] ?? '')->minLength(10);
            $validator->required('budget_richiesto', $input['budget_richiesto'] ?? '')->numeric()->min(1);
            $validator->required('categoria_id', $input['categoria_id'] ?? '')->integer()->min(1);
            $validator->required('data_scadenza', $input['data_scadenza'] ?? '')->date();
            
            if (!$validator->isValid()) {
                ApiResponse::invalidInput($validator->getErrors());
            }
            
            $result = $project->create($input);
            if ($result['success']) {
                ApiResponse::success($result, 'Progetto creato con successo');
            } else {
                ApiResponse::serverError($result['message']);
            }
            break;
            
        case 'donate':
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                ApiResponse::error('Accesso richiesto', 401);
            }
            
            // Validate required fields
            $validator = new Validator();
            $validator->required('progetto_id', $input['progetto_id'] ?? '')->integer()->min(1);
            $validator->required('importo', $input['importo'] ?? '')->numeric()->min(1);
            
            if (!$validator->isValid()) {
                ApiResponse::invalidInput($validator->getErrors());
            }
            
            $result = $project->donate(
                $input['progetto_id'],
                $_SESSION['user_id'],
                $input['importo'],
                $input['ricompensa_id'] ?? null
            );
            
            if ($result['success']) {
                ApiResponse::success($result, 'Finanziamento completato con successo');
            } else {
                ApiResponse::error($result['message']);
            }
            break;
            
        default:
            ApiResponse::error('Azione non riconosciuta', 400);
    }
}

function handlePutRequest($project, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ApiResponse::error('Formato JSON non valido');
    }
    
    switch ($action) {
        case 'update':
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                ApiResponse::error('Accesso richiesto', 401);
            }
            
            $projectId = (int)($_GET['id'] ?? 0);
            if ($projectId <= 0) {
                ApiResponse::invalidInput(['id' => 'ID progetto richiesto']);
            }
            
            // TODO: Add check to ensure user owns the project
            
            $result = $project->update($projectId, $input);
            if ($result['success']) {
                ApiResponse::success($result, 'Progetto aggiornato con successo');
            } else {
                ApiResponse::serverError($result['message']);
            }
            break;
            
        default:
            ApiResponse::error('Azione non riconosciuta', 400);
    }
}

function handleDeleteRequest($project, $action) {
    switch ($action) {
        case 'delete':
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                ApiResponse::error('Accesso richiesto', 401);
            }
            
            $projectId = (int)($_GET['id'] ?? 0);
            if ($projectId <= 0) {
                ApiResponse::invalidInput(['id' => 'ID progetto richiesto']);
            }
            
            // TODO: Add check to ensure user owns the project or is admin
            
            $result = $project->delete($projectId);
            if ($result['success']) {
                ApiResponse::success($result, 'Progetto eliminato con successo');
            } else {
                ApiResponse::serverError($result['message']);
            }
            break;
            
        default:
            ApiResponse::error('Azione non riconosciuta', 400);
    }
}
?>
