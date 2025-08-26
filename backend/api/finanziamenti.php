<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Finanziamento.php';
require_once '../utils/RoleManager.php';
require_once '../utils/ApiResponse.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();
$finanziamento = new Finanziamento();

// Verifica autenticazione
if (!$roleManager->isAuthenticated()) {
    $apiResponse->sendError('Devi essere autenticato', 401);
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['progetto_id'])) {
            // Recupera finanziamenti per progetto
            $result = $finanziamento->getByProject($_GET['progetto_id']);
        } else if (isset($_GET['utente_id'])) {
            // Recupera finanziamenti per utente (solo se è l'utente stesso o admin)
            $utente_id = $_GET['utente_id'];
            if ($utente_id != $_SESSION['user_id'] && $roleManager->getUserType() !== 'amministratore') {
                $apiResponse->sendError('Non autorizzato', 403);
                exit();
            }
            $result = $finanziamento->getByUser($utente_id);
        } else {
            $apiResponse->sendError('Parametro richiesto: progetto_id o utente_id');
            exit();
        }
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result['finanziamenti']);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        $requiredFields = ['progetto_id', 'importo'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                $apiResponse->sendError("Campo $field richiesto");
                exit();
            }
        }
        
        $data = [
            'utente_id' => $_SESSION['user_id'],
            'progetto_id' => $input['progetto_id'],
            'importo' => $input['importo'],
            'reward_id' => $input['reward_id'] ?? null,
            'messaggio' => $input['messaggio'] ?? null,
            'anonimo' => $input['anonimo'] ?? 0
        ];
        
        $result = $finanziamento->create($data);
        
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
