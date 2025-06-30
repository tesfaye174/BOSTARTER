<?php
ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
ob_clean();
try {
    session_start();
    require_once '../config/database.php';
    require_once '../models/ProjectCompliant.php';
    require_once '../services/MongoLogger.php';
    require_once '../utils/Validator.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    $projectModel = new ProjectCompliant();
    $mongoLogger = new MongoLogger();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';
    $request = json_decode(file_get_contents('php:
    $validProjectTypes = ['hardware', 'software'];
    $mongoLogger->logAction('projects_compliant_request', [
        'endpoint' => 'projects_compliant',
        'method' => $method,
        'action' => $action,
        'timestamp' => time(),
        'pdf_compliant' => true,
        'supported_types' => $validProjectTypes
    ]);
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'list';
            switch ($action) {
                case 'list':
                    $result = $projectModel->getList();
                    echo json_encode([
                        'success' => true,
                        'progetti' => array_values($result['progetti'] ?? [])
                    ]);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Invalid action'
                    ]);
            }
            break;
        case 'POST':
            if ($action === 'create') {
                handleCreateProjectCompliant($projectModel, $mongoLogger, $request, $validProjectTypes);
            } else {
                sendError('Azione POST non supportata', 400);
            }
            break;
        case 'PUT':
            if ($action === 'update') {
                $projectId = (int)($_GET['id'] ?? 0);
                handleUpdateProjectCompliant($projectModel, $mongoLogger, $projectId, $request, $validProjectTypes);
            } else {
                sendError('Azione PUT non supportata', 400);
            }
            break;
        case 'DELETE':
            if ($action === 'delete') {
                $projectId = (int)($_GET['id'] ?? 0);
                handleDeleteProjectCompliant($projectModel, $mongoLogger, $projectId, $validProjectTypes);
            } else {
                sendError('Azione DELETE non supportata', 400);
            }
            break;
        default:
            sendError('Metodo HTTP non supportato', 405);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
ob_end_flush();
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    return $_SESSION['user_id'];
}
function handleGetProject($projectModel, $mongoLogger, $projectId) {
    try {
        $project = $projectModel->getDetails($projectId);
        if ($project) {
            $mongoLogger->logEvent('project_viewed', [
                'project_id' => $projectId,
                'viewer_id' => $_SESSION['user_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            echo json_encode([
                'success' => true,
                'project' => $project
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found']);
        }
    } catch (Exception $e) {
        $mongoLogger->logEvent('project_get_error', [
            'project_id' => $projectId,
            'error' => $e->getMessage()
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}
function handleGetProjects($projectModel, $mongoLogger) {
    try {
        $filters = [];
        $page = intval($_GET['page'] ?? 1);
        $perPage = min(intval($_GET['per_page'] ?? 10), 50); 
        if (isset($_GET['tipo']) && in_array($_GET['tipo'], ['hardware', 'software'])) {
            $filters['tipo'] = $_GET['tipo'];
        }
        if (isset($_GET['stato'])) {
            $filters['stato'] = $_GET['stato'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        $result = $projectModel->getList($filters, $page, $perPage);
        if ($result) {
            $mongoLogger->logEvent('projects_list_viewed', [
                'filters' => $filters,
                'page' => $page,
                'results_count' => count($result['progetti']),
                'viewer_id' => $_SESSION['user_id'] ?? null
            ]);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve projects']);
        }
    } catch (Exception $e) {
        $mongoLogger->logEvent('projects_list_error', [
            'error' => $e->getMessage()
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}
function handleCreateProject($projectModel, $mongoLogger, $request) {
    $userId = requireAuth();
    try {
        $validationResult = Validator::validateProjectData([
            'name' => $request['nome'] ?? '',
            'creator_id' => $userId,
            'description' => $request['descrizione'] ?? '',
            'budget' => $request['budget_richiesto'] ?? '',
            'project_type' => $request['tipo'] ?? '',
            'end_date' => $request['data_scadenza'] ?? ''
        ]);
        if ($validationResult !== true) {
            http_response_code(400);
            echo json_encode(['error' => implode(', ', $validationResult)]);
            return;
        }
        if (!in_array($request['tipo'], ['hardware', 'software'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo progetto deve essere hardware o software']);
            return;
        }
        if ($request['tipo'] === 'hardware') {
            if (empty($request['componenti'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Progetti hardware devono avere almeno un componente']);
                return;
            }
        } elseif ($request['tipo'] === 'software') {
            if (empty($request['profili'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Progetti software devono avere almeno un profilo']);
                return;
            }
        }
        $request['creatore_id'] = $userId;
        $result = $projectModel->create($request);
        if ($result['success']) {
            $mongoLogger->logEvent('project_created', [
                'project_id' => $result['progetto_id'],
                'creator_id' => $userId,
                'tipo' => $request['tipo'],
                'budget_richiesto' => $request['budget_richiesto']
            ]);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    } catch (Exception $e) {
        $mongoLogger->logEvent('project_creation_error', [
            'creator_id' => $userId,
            'request_data' => $request,
            'error' => $e->getMessage()
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}
function handleUpdateProject($projectModel, $mongoLogger, $projectId, $request) {
    $userId = requireAuth();
    try {
        $project = $projectModel->getDetails($projectId);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found']);
            return;
        }
        if ($project['creatore_id'] != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authorized to update this project']);
            return;
        }
        if ($project['stato'] !== 'aperto' || strtotime($project['data_scadenza']) <= time()) {
            http_response_code(400);
            echo json_encode(['error' => 'Project cannot be updated']);
            return;
        }
        if (isset($request['budget_richiesto'])) {
            $validator = new Validator();
            $validator->required('budget_richiesto', $request['budget_richiesto']);
            if (!$validator->isValid() || !is_numeric($request['budget_richiesto']) || $request['budget_richiesto'] <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Budget deve essere un numero positivo']);
                return;
            }
        }
        if (isset($request['data_scadenza'])) {
            $deadline = DateTime::createFromFormat('Y-m-d H:i:s', $request['data_scadenza']);
            if (!$deadline || $deadline <= new DateTime()) {
                http_response_code(400);
                echo json_encode(['error' => 'Data scadenza deve essere futura']);
                return;
            }
        }
        $mongoLogger->logEvent('project_update_attempted', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'update_data' => $request
        ]);
        echo json_encode([
            'success' => false,
            'message' => 'Project updates not implemented in compliant version'
        ]);
    } catch (Exception $e) {
        $mongoLogger->logEvent('project_update_error', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}
function handleDeleteProject($projectModel, $mongoLogger, $projectId) {
    $userId = requireAuth();
    try {
        $project = $projectModel->getDetails($projectId);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found']);
            return;
        }
        if ($project['creatore_id'] != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authorized to delete this project']);
            return;
        }
        if ($project['totale_finanziamenti'] > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete project with existing funding']);
            return;
        }
        $mongoLogger->logEvent('project_deletion_attempted', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'project_data' => $project
        ]);
        echo json_encode([
            'success' => false,
            'message' => 'Project deletion not implemented in compliant version'
        ]);
    } catch (Exception $e) {
        $mongoLogger->logEvent('project_deletion_error', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}
?>
