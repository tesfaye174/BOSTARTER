<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require_once __DIR__ . '/../controllers/ProjectController.php';

$controller = new ProjectController();

$data = json_decode(file_get_contents('php://input'), true);
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    case 'create':
        if (!empty($data)) {
            echo json_encode($controller->createProject($data));
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Dati mancanti'
            ]);
        }
        break;

    case 'add-reward':
        if (!empty($data)) {
            echo json_encode($controller->addProjectReward($data));
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Dati mancanti'
            ]);
        }
        break;

    case 'publish':
        if (isset($data['project_id'])) {
            echo json_encode($controller->publishProject($data['project_id']));
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID progetto mancante'
            ]);
        }
        break;

    case 'creator-projects':
        if (isset($_GET['creator_id'])) {
            echo json_encode($controller->getCreatorProjects($_GET['creator_id']));
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID creatore mancante'
            ]);
        }
        break;

    case 'fund':
        if (!empty($data)) {
            echo json_encode($controller->fundProject($data));
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Dati mancanti'
            ]);
        }
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Azione non valida'
        ]);
        break;
}
?>