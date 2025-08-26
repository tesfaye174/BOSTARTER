<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Commento.php';
require_once '../utils/RoleManager.php';
require_once '../utils/ApiResponse.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();
$commento = new Commento();

// Verifica autenticazione
if (!$roleManager->isAuthenticated()) {
    $apiResponse->sendError('Devi essere autenticato', 401);
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $progetto_id = $_GET['progetto_id'] ?? null;
        
        if (!$progetto_id) {
            $apiResponse->sendError('ID progetto richiesto');
            exit();
        }
        
        $result = $commento->getByProject($progetto_id);
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result['commenti']);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['progetto_id']) || !isset($input['testo'])) {
            $apiResponse->sendError('Progetto ID e testo richiesti');
            exit();
        }
        
        $data = [
            'utente_id' => $_SESSION['user_id'],
            'progetto_id' => $input['progetto_id'],
            'testo' => $input['testo']
        ];
        
        $result = $commento->create($data);
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['commento_id']) || !isset($input['risposta'])) {
            $apiResponse->sendError('ID commento e risposta richiesti');
            exit();
        }
        
        $result = $commento->addRisposta(
            $input['commento_id'], 
            $_SESSION['user_id'], 
            $input['risposta']
        );
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'DELETE':
        $commento_id = $_GET['id'] ?? null;
        
        if (!$commento_id) {
            $apiResponse->sendError('ID commento richiesto');
            exit();
        }
        
        $isAdmin = $roleManager->getUserType() === 'amministratore';
        $result = $commento->delete($commento_id, $_SESSION['user_id'], $isAdmin);
        
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
try {
    $database = new Database();
    $pdo = $database->getConnection();
    $stmt = $pdo->prepare("SELECT id, nome, creatore_id FROM progetti WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    if (!$project) {
        http_response_code(404);
        echo json_encode(['error' => 'Progetto non trovato']);
        exit();
    }
    if ($parent_id) {
        $stmt = $pdo->prepare("SELECT id FROM commenti WHERE id = ? AND progetto_id = ?");
        $stmt->execute([$parent_id, $project_id]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Commento padre non trovato']);
            exit();
        }
    }
    $stmt = $pdo->prepare("
        INSERT INTO commenti (progetto_id, utente_id, testo, parent_id, data_commento) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$project_id, $user_id, $comment_text, $parent_id]);
    $comment_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT u.nome, u.cognome, u.avatar 
        FROM utenti u 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($project['creatore_id'] != $user_id) {
    }
    if ($parent_id) {
        $stmt = $pdo->prepare("
            SELECT utente_id FROM commenti WHERE id = ?
        ");
        $stmt->execute([$parent_id]);
        $parent_author = $stmt->fetch();
          if ($parent_author && $parent_author['utente_id'] != $user_id) {
        }
    }
    $response = [
        'success' => true,
        'comment' => [
            'id' => $comment_id,
            'text' => $comment_text,
            'author' => $user['nome'] . ' ' . $user['cognome'],
            'avatar' => $user['avatar'],
            'date' => date('d/m/Y H:i'),
            'parent_id' => $parent_id
        ]
    ];
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Comment posting error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante l\'invio del commento']);
}
?>
