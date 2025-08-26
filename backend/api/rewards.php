<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reward.php';
require_once '../utils/RoleManager.php';
require_once '../utils/ApiResponse.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();
$reward = new Reward();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['progetto_id'])) {
            // Recupera rewards per progetto
            $result = $reward->getByProject($_GET['progetto_id']);
        } else if (isset($_GET['id'])) {
            // Recupera singolo reward
            $result = $reward->getById($_GET['id']);
        } else {
            $apiResponse->sendError('Parametro richiesto: progetto_id o id');
            exit();
        }
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'POST':
        // Verifica autenticazione per creazione
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Devi essere autenticato', 401);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $requiredFields = ['progetto_id', 'titolo', 'descrizione', 'importo_minimo'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $apiResponse->sendError("Campo $field richiesto");
                exit();
            }
        }
        
        $result = $reward->create($input);
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'PUT':
        // Verifica autenticazione per modifica
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Devi essere autenticato', 401);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $reward_id = $_GET['id'] ?? null;
        
        if (!$reward_id) {
            $apiResponse->sendError('ID reward richiesto');
            exit();
        }
        
        $result = $reward->update($reward_id, $input, $_SESSION['user_id']);
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'DELETE':
        // Verifica autenticazione per eliminazione
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Devi essere autenticato', 401);
            exit();
        }
        
        $reward_id = $_GET['id'] ?? null;
        
        if (!$reward_id) {
            $apiResponse->sendError('ID reward richiesto');
            exit();
        }
        
        $result = $reward->delete($reward_id, $_SESSION['user_id']);
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    default:
        $apiResponse->sendError('Metodo non supportato', 405);
}
?>
