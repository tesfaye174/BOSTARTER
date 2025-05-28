<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/NotificationController.php';

use BOSTARTER\Controllers\NotificationController;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestione delle richieste OPTIONS per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inizializza il controller
$database = new Database();
$db = $database->getConnection();
$controller = new NotificationController($db);

// Verifica l'autenticazione
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Utente non autenticato']);
    exit();
}

$userId = $_SESSION['user_id'];

// Gestione delle richieste
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'unread':
                $result = $controller->getUnread($userId);
                break;
            case 'count':
                $result = $controller->getUnreadCount($userId);
                break;
            default:
                http_response_code(400);
                $result = ['status' => 'error', 'message' => 'Azione non valida'];
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'mark_read':
                if (!isset($data['notification_id'])) {
                    http_response_code(400);
                    $result = ['status' => 'error', 'message' => 'ID notifica mancante'];
                    break;
                }
                $result = $controller->markAsRead($data['notification_id']);
                break;
                
            case 'mark_all_read':
                $result = $controller->markAllAsRead($userId);
                break;
                
            default:
                http_response_code(400);
                $result = ['status' => 'error', 'message' => 'Azione non valida'];
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            $result = ['status' => 'error', 'message' => 'ID notifica mancante'];
            break;
        }
        $result = $controller->delete($_GET['id']);
        break;

    default:
        http_response_code(405);
        $result = ['status' => 'error', 'message' => 'Metodo non supportato'];
}

// Invia la risposta
echo json_encode($result); 