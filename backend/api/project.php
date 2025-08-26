<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../utils/RoleManager.php';
require_once __DIR__ . '/../utils/ApiResponse.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();
$project = new Project();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $result = $project->getById($_GET['id']);
            if ($result['success']) {
                $apiResponse->sendSuccess($result['project']);
            } else {
                $apiResponse->sendError($result['error']);
            }
        } else {
            $filters = $_GET;
            $result = $project->getAll($filters);
            if ($result['success']) {
                $apiResponse->sendSuccess($result['projects']);
            } else {
                $apiResponse->sendError($result['error']);
            }
        }
        break;
        
    case 'POST':
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Devi essere autenticato', 401);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $requiredFields = ['nome', 'descrizione', 'budget_richiesto', 'data_limite', 'tipo'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $apiResponse->sendError("Campo $field obbligatorio");
                exit();
            }
        }
        
        $data = [
            'creatore_id' => $_SESSION['user_id'],
            'nome' => $input['nome'],
            'descrizione' => $input['descrizione'],
            'budget_richiesto' => $input['budget_richiesto'],
            'data_limite' => $input['data_limite'],
            'tipo' => $input['tipo'],
            'categoria' => $input['categoria'] ?? null,
            'immagine' => $input['immagine'] ?? null
        ];
        
        $result = $project->create($data);
        
        if ($result['success']) {
            $apiResponse->sendSuccess(['project_id' => $result['project_id']], 'Progetto creato con successo', 201);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'PUT':
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Devi essere autenticato', 401);
            exit();
        }
        
        if (!isset($_GET['id'])) {
            $apiResponse->sendError('ID progetto richiesto');
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $input['id'] = $_GET['id'];
        $input['user_id'] = $_SESSION['user_id'];
        
        $result = $project->update($input);
        
        if ($result['success']) {
            $apiResponse->sendSuccess([], 'Progetto aggiornato con successo');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'DELETE':
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Devi essere autenticato', 401);
            exit();
        }
        
        if (!isset($_GET['id'])) {
            $apiResponse->sendError('ID progetto richiesto');
            exit();
        }
        
        $result = $project->delete($_GET['id'], $_SESSION['user_id']);
        
        if ($result['success']) {
            $apiResponse->sendSuccess([], 'Progetto eliminato con successo');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    default:
        $apiResponse->sendError('Metodo non supportato', 405);
        break;
}
?>