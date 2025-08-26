<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Candidatura.php';
require_once __DIR__ . '/../utils/RoleManager.php';
require_once __DIR__ . '/../utils/ApiResponse.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();
$candidatura = new Candidatura();

// Verifica autenticazione
if (!$roleManager->isAuthenticated()) {
    $apiResponse->sendError('Devi essere autenticato', 401);
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['progetto_id'])) {
            // Recupera candidature per progetto (solo per il creatore)
            $progetto_id = $_GET['progetto_id'];
            $result = $candidatura->getByProject($progetto_id, $_SESSION['user_id']);
        } else {
            // Recupera candidature dell'utente
            $result = $candidatura->getByUser($_SESSION['user_id']);
        }
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result['candidature']);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['progetto_id']) || !isset($input['messaggio'])) {
            $apiResponse->sendError('Progetto ID e messaggio richiesti');
            exit();
        }
        
        $data = [
            'utente_id' => $_SESSION['user_id'],
            'progetto_id' => $input['progetto_id'],
            'messaggio' => $input['messaggio']
        ];
        
        $result = $candidatura->create($data);
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['candidatura_id']) || !isset($input['stato'])) {
            $apiResponse->sendError('ID candidatura e stato richiesti');
            exit();
        }
        
        $result = $candidatura->updateStato(
            $input['candidatura_id'], 
            $_SESSION['user_id'], 
            $input['stato'],
            $input['motivazione'] ?? null
        );
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'DELETE':
        $candidatura_id = $_GET['id'] ?? null;
        
        if (!$candidatura_id) {
            $apiResponse->sendError('ID candidatura richiesto');
            exit();
        }
        
        $result = $candidatura->delete($candidatura_id, $_SESSION['user_id']);
        
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
