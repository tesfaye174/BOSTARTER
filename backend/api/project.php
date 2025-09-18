<?php
session_start();
// Carica l'autoloader personalizzato BOSTARTER
require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();
$project = new Project(Database::getInstance()->getConnection());

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
            // Supporta getAll() legacy che restituisce array di progetti,
            // e implementazioni più recenti che restituiscono ['success'=>..., 'data'=>...]
            if (is_array($result) && isset($result['success'])) {
                if ($result['success']) {
                // Se risultato strutturato, prova a inviare data.projects o data
                    if (isset($result['data']['projects'])) {
                        $apiResponse->sendSuccess($result['data']);
                    } else {
                        $apiResponse->sendSuccess($result['data']);
                    }
                } else {
                    $apiResponse->sendError($result['error']);
                }
            } else {
                // Presumi che $result sia un array semplice di progetti
                $apiResponse->sendSuccess($result);
            }
        }
        break;
        
    case 'POST':
    // Richiesta POST per creazione progetto
        
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Per favore, effettua il login per continuare', 401);
            exit();
        }
        
        // Verifica CSRF di base: accetta token in body JSON, header o POST
        $security = Security::getInstance();
        $csrfToken = null;
        $rawForCsrf = file_get_contents('php://input');
        $jsonForCsrf = json_decode($rawForCsrf, true);
        if (is_array($jsonForCsrf) && isset($jsonForCsrf['csrf_token'])) {
            $csrfToken = $jsonForCsrf['csrf_token'];
        } elseif (!empty($_POST['csrf_token'])) {
            $csrfToken = $_POST['csrf_token'];
        } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (!$security->verifyCSRFToken($csrfToken)) {
            $apiResponse->sendError('Token CSRF non valido o mancante', 403);
            exit();
        }

        // Supporta sia body JSON che multipart/form-data con upload file
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            // Prova a leggere $_POST standard (multipart/form-data)
            $input = $_POST;
        }
    // input parsato (json o post)

        $requiredFields = ['nome', 'descrizione', 'budget_richiesto', 'data_limite', 'tipo'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $apiResponse->sendError("Non dimenticare di inserire: $field");
                exit();
            }
        }
        
        $data = [
            'creatore_id' => $_SESSION['user_id'],
            'titolo' => $input['nome'],
            'descrizione' => $input['descrizione'],
            'budget_richiesto' => $input['budget_richiesto'],
            'data_fine' => $input['data_limite'],
            'tipo_progetto' => $input['tipo'],
            'categoria' => $input['categoria'] ?? null,
            'immagine' => $input['immagine'] ?? null
        ];

    // Upload non supportati in questa build semplificata (rimossi su richiesta).
    // Il frontend dovrebbe inviare JSON; nessun handling $_FILES è eseguito qui.

        $result = $project->create($data);
        
        if ($result['success']) {
            $apiResponse->sendSuccess(['project_id' => $result['project_id']], MessageManager::get('project_created'), 201);
        } else {
            error_log('Project creation failed: ' . ($result['error'] ?? 'unknown'));
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
        
        // Verifica CSRF per aggiornamento
        $security = Security::getInstance();
        $rawPut = file_get_contents('php://input');
        $jsonPut = json_decode($rawPut, true);
        $csrfToken = $jsonPut['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$security->verifyCSRFToken($csrfToken)) {
            $apiResponse->sendError('Token CSRF non valido o mancante', 403);
            exit();
        }

        $input = $jsonPut;
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
        
        // Verifica CSRF per eliminazione (token via header preferibile)
        $security = Security::getInstance();
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$security->verifyCSRFToken($csrfToken)) {
            $apiResponse->sendError('Token CSRF non valido o mancante', 403);
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