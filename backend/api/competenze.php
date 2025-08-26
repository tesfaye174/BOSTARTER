<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Competenza.php';
require_once '../models/User.php';
require_once '../utils/RoleManager.php';
require_once '../utils/ApiResponse.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();
$competenza = new Competenza();
$user = new User();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Recupera tutte le competenze (accessibile a tutti)
        $result = $competenza->getAll();
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result['competenze']);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'POST':
        // Verifica che sia amministratore
        if (!$roleManager->isAuthenticated() || $roleManager->getUserType() !== 'amministratore') {
            $apiResponse->sendError('Solo gli amministratori possono creare competenze', 403);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['action']) && $input['action'] === 'assign_skill') {
            // Assegna skill a utente
            if (!isset($input['utente_id']) || !isset($input['competenza_id'])) {
                $apiResponse->sendError('Utente ID e Competenza ID richiesti');
                exit();
            }
            
            $result = $user->addSkill($input['utente_id'], $input['competenza_id']);
        } else {
            // Crea nuova competenza
            if (!isset($input['nome']) || empty($input['nome'])) {
                $apiResponse->sendError('Nome competenza richiesto');
                exit();
            }
            
            $result = $competenza->create($input['nome'], $input['descrizione'] ?? null);
        }
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'DELETE':
        // Verifica che sia amministratore
        if (!$roleManager->isAuthenticated() || $roleManager->getUserType() !== 'amministratore') {
            $apiResponse->sendError('Solo gli amministratori possono eliminare competenze', 403);
            exit();
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'remove_skill') {
            // Rimuovi skill da utente
            $utente_id = $_GET['utente_id'] ?? null;
            $competenza_id = $_GET['competenza_id'] ?? null;
            
            if (!$utente_id || !$competenza_id) {
                $apiResponse->sendError('Utente ID e Competenza ID richiesti');
                exit();
            }
            
            $result = $user->removeSkill($utente_id, $competenza_id);
        } else {
            // Elimina competenza
            $competenza_id = $_GET['id'] ?? null;
            
            if (!$competenza_id) {
                $apiResponse->sendError('ID competenza richiesto');
                exit();
            }
            
            $result = $competenza->delete($competenza_id);
        }
        
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
