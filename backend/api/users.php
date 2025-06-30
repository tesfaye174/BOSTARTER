<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
require_once '../config/database.php';
require_once '../services/MongoLogger.php';
require_once '../utils/Validator.php';
$database = new Database();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();
session_start();
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}
function canAccessProfile($db, $targetUserId) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    if ($_SESSION['user_id'] == $targetUserId) {
        return true;
    }
    $stmt = $db->prepare("SELECT tipo_utente as role FROM utenti WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user && $user['role'] === 'admin';
}
$method = $_SERVER['REQUEST_METHOD'];
$request = json_decode(file_get_contents('php:
switch ($method) {
    case 'GET':
        if (isset($_GET['user_id'])) {
            getUserProfile($db, $mongoLogger, $_GET['user_id']);
        } else {
            getUserList($db, $mongoLogger);
        }
        break;
    case 'PUT':
        updateUserProfile($db, $mongoLogger, $request);
        break;
    case 'DELETE':
        deleteUser($db, $mongoLogger);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
function getUserProfile($db, $mongoLogger, $userId) {
    try {
        if (!canAccessProfile($db, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }        
        $userStmt = $db->prepare("
            SELECT id as user_id, nickname as username, email, CONCAT(nome, ' ', cognome) as full_name, 
                   '' as bio, luogo_nascita as location, '' as avatar_url, '' as website_url, 
                   'active' as status, tipo_utente as role, created_at, last_access as last_login
            FROM utenti 
            WHERE id = ?
        ");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }        
        $projectsStmt = $db->prepare("
            SELECT p.*, 
                   COALESCE(pf.totale_finanziato, 0) as total_funded,
                   COALESCE(pf.percentuale_finanziamento, 0) as funding_percentage,
                   COALESCE(pf.numero_sostenitori, 0) as backers_count,
                   DATEDIFF(p.data_limite, NOW()) as days_left
            FROM progetti p
            LEFT JOIN view_progetti pf ON p.id = pf.progetto_id
            WHERE p.creatore_id = ?
            ORDER BY p.data_inserimento DESC
        ");
        $projectsStmt->execute([$userId]);
        $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);
        $fundings = [];        if ($_SESSION['user_id'] == $userId || canAccessProfile($db, $userId)) {
            $fundingStmt = $db->prepare("
                SELECT f.*, p.nome as project_title, p.tipo_progetto as project_type,
                       u.nickname as creator_name
                FROM finanziamenti f
                JOIN progetti p ON f.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                WHERE f.utente_id = ?
                ORDER BY f.data_finanziamento DESC
                LIMIT 50
            ");
            $fundingStmt->execute([$userId]);
            $fundings = $fundingStmt->fetchAll(PDO::FETCH_ASSOC);
        }        
        $skillsStmt = $db->prepare("
            SELECT c.id as skill_id, c.nome as name, c.descrizione as description, 
                   su.livello as proficiency_level, 0 as years_experience
            FROM skill_utente su
            JOIN competenze c ON su.competenza_id = c.id
            WHERE su.utente_id = ?
            ORDER BY su.livello DESC, c.nome ASC
        ");
        $skillsStmt->execute([$userId]);
        $skills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);        
        $statsStmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT p.id) as projects_created,
                COUNT(DISTINCT CASE WHEN p.stato = 'chiuso' THEN p.id END) as successful_projects,
                COALESCE(SUM(CASE WHEN p.stato = 'chiuso' THEN p.budget_richiesto END), 0) as total_raised,
                COUNT(DISTINCT f.id) as projects_backed,
                COALESCE(SUM(f.importo), 0) as total_backed
            FROM utenti u
            LEFT JOIN progetti p ON u.id = p.creatore_id
            LEFT JOIN finanziamenti f ON u.id = f.utente_id
            WHERE u.id = ?
        ");
        $statsStmt->execute([$userId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        if (isset($_SESSION['user_id'])) {
            $mongoLogger->logActivity($_SESSION['user_id'], 'user_profile_viewed', [
                'viewed_user_id' => $userId,
                'viewed_username' => $user['username']
            ]);
        }
        $isOwnProfile = ($_SESSION['user_id'] ?? null) == $userId;
        $isAdmin = canAccessProfile($db, $userId) && ($_SESSION['user_id'] ?? null) != $userId;
        if (!$isOwnProfile && !$isAdmin) {
            unset($user['email']);
            $fundings = []; 
        }
        echo json_encode([
            'success' => true,
            'user' => $user,
            'projects' => $projects,
            'fundings' => $fundings,
            'skills' => $skills,
            'stats' => $stats,
            'is_own_profile' => $isOwnProfile
        ]);
    } catch (Exception $e) {
        $mongoLogger->logError('user_profile_error', [
            'error' => $e->getMessage(),
            'user_id' => $userId,
            'viewer_id' => $_SESSION['user_id'] ?? null
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch user profile']);
    }
}
function getUserList($db, $mongoLogger) {
    try {        
        requireAuth();
        $stmt = $db->prepare("SELECT tipo_utente as role FROM utenti WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$currentUser || $currentUser['role'] !== 'amministratore') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            return;
        }
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? 'all';
        $role = $_GET['role'] ?? 'all';
        $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
        $offset = max(0, intval($_GET['offset'] ?? 0));        $baseQuery = "
            SELECT u.id as user_id, u.nickname as username, u.email, CONCAT(u.nome, ' ', u.cognome) as full_name, 
                   'active' as status, u.tipo_utente as role, 
                   u.created_at, u.last_access as last_login,
                   COUNT(DISTINCT p.id) as projects_count,
                   COUNT(DISTINCT f.id) as fundings_count
            FROM utenti u
            LEFT JOIN progetti p ON u.id = p.creatore_id
            LEFT JOIN finanziamenti f ON u.id = f.utente_id
            WHERE 1=1
        ";
        $params = [];
        if (!empty($search)) {
            $baseQuery .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        if ($status !== 'all') {
            $baseQuery .= " AND u.status = ?";
            $params[] = $status;
        }
        if ($role !== 'all') {
            $baseQuery .= " AND u.role = ?";
            $params[] = $role;
        }
        $baseQuery .= " GROUP BY u.user_id ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $db->prepare($baseQuery);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $countQuery = "SELECT COUNT(*) as total FROM USERS u WHERE 1=1";
        $countParams = [];
        if (!empty($search)) {
            $countQuery .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $countParams[] = $searchTerm;
            $countParams[] = $searchTerm;
            $countParams[] = $searchTerm;
        }
        if ($status !== 'all') {
            $countQuery .= " AND u.status = ?";
            $countParams[] = $status;
        }
        if ($role !== 'all') {
            $countQuery .= " AND u.role = ?";
            $countParams[] = $role;
        }
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $mongoLogger->logActivity($_SESSION['user_id'], 'user_list_viewed', [
            'search' => $search,
            'status' => $status,
            'role' => $role,
            'result_count' => count($users)
        ]);
        echo json_encode([
            'success' => true,
            'users' => $users,
            'pagination' => [
                'total' => intval($totalCount),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
    } catch (Exception $e) {
        $mongoLogger->logError('user_list_error', [
            'error' => $e->getMessage(),
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch user list']);
    }
}
function updateUserProfile($db, $mongoLogger, $request) {
    requireAuth();
    try {
        $userId = $request['user_id'] ?? $_SESSION['user_id'];
        if (!canAccessProfile($db, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        $validator = new Validator();
        if (isset($request['password'])) {
            $validator->minLength(8);
        }
        if (isset($request['email'])) {
            $validator->email();
        }
        if (!$validator->isValid()) {
            http_response_code(400);
            echo json_encode(['error' => implode(', ', $validator->getErrors())]);
            return;
        }
        $checkStmt = $db->prepare("SELECT username FROM USERS WHERE user_id = ?");
        $checkStmt->execute([$userId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        $updateFields = [];
        $params = [];
        $allowedFields = ['full_name', 'bio', 'location', 'website_url'];
        if ($_SESSION['user_id'] != $userId) {
            $stmt = $db->prepare("SELECT role FROM USERS WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($currentUser && $currentUser['role'] === 'admin') {
                $allowedFields[] = 'role';
                $allowedFields[] = 'status';
            }
        }
        foreach ($allowedFields as $field) {
            if (isset($request[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $request[$field];
            }
        }
        if (isset($request['password']) && $_SESSION['user_id'] == $userId) {
            if (!isset($request['current_password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Current password required']);
                return;
            }
            $pwStmt = $db->prepare("SELECT password_hash FROM USERS WHERE user_id = ?");
            $pwStmt->execute([$userId]);
            $currentHash = $pwStmt->fetch(PDO::FETCH_ASSOC)['password_hash'];
            if (!password_verify($request['current_password'], $currentHash)) {
                http_response_code(400);
                echo json_encode(['error' => 'Current password is incorrect']);
                return;
            }
            if (strlen($request['password']) < 8) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 8 characters long']);
                return;
            }
            $updateFields[] = "password_hash = ?";
            $params[] = password_hash($request['password'], PASSWORD_DEFAULT);
        }
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update']);
            return;
        }
        $params[] = $userId;
        $updateStmt = $db->prepare("UPDATE USERS SET " . implode(', ', $updateFields) . " WHERE user_id = ?");
        $updateStmt->execute($params);
        $mongoLogger->logActivity($_SESSION['user_id'], 'user_profile_updated', [
            'updated_user_id' => $userId,
            'updated_fields' => array_keys($request),
            'is_self_update' => $_SESSION['user_id'] == $userId
        ]);
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } catch (Exception $e) {
        $mongoLogger->logError('user_update_error', [
            'error' => $e->getMessage(),
            'user_id' => $request['user_id'] ?? $_SESSION['user_id'],
            'updater_id' => $_SESSION['user_id']
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile']);
    }
}
function deleteUser($db, $mongoLogger) {
    requireAuth();
    try {
        $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
        if (!canAccessProfile($db, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        $projectStmt = $db->prepare("SELECT COUNT(*) as count FROM PROJECTS WHERE creator_id = ? AND status = 'open'");
        $projectStmt->execute([$userId]);
        $activeProjects = $projectStmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($activeProjects > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete user with active projects']);
            return;
        }
        $deleteStmt = $db->prepare("UPDATE USERS SET status = 'deleted', email = CONCAT(email, '_deleted_', UNIX_TIMESTAMP()) WHERE user_id = ?");
        $deleteStmt->execute([$userId]);
        $mongoLogger->logActivity($_SESSION['user_id'], 'user_deleted', [
            'deleted_user_id' => $userId,
            'is_self_delete' => $_SESSION['user_id'] == $userId
        ]);
        if ($_SESSION['user_id'] == $userId) {
            session_destroy();
        }
        echo json_encode([
            'success' => true,
            'message' => 'User account deleted successfully'
        ]);
    } catch (Exception $e) {
        $mongoLogger->logError('user_delete_error', [
            'error' => $e->getMessage(),
            'user_id' => $_GET['user_id'] ?? $_SESSION['user_id'],
            'deleter_id' => $_SESSION['user_id']
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user']);
    }
}
if (isset($_GET['skills']) && isset($_GET['user_id'])) {
    try {
        $userId = $_GET['user_id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireAuth();
            if ($_SESSION['user_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['error' => 'Can only add skills to your own profile']);
                exit;
            }
            $skillData = json_decode(file_get_contents('php:
            $insertStmt = $db->prepare("
                INSERT INTO USER_SKILLS (user_id, skill_id, proficiency_level, years_experience, created_at) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                proficiency_level = VALUES(proficiency_level), 
                years_experience = VALUES(years_experience)
            ");
            $insertStmt->execute([
                $userId,
                $skillData['skill_id'],
                $skillData['proficiency_level'] ?? 1,
                $skillData['years_experience'] ?? 0
            ]);
            echo json_encode(['success' => true, 'message' => 'Skill added successfully']);
        } else {
            $stmt = $db->prepare("
                SELECT s.skill_id, s.name, s.description, us.proficiency_level, us.years_experience
                FROM USER_SKILLS us
                JOIN SKILLS s ON us.skill_id = s.skill_id
                WHERE us.user_id = ? AND s.status = 'active'
                ORDER BY us.proficiency_level DESC, s.name ASC
            ");
            $stmt->execute([$userId]);
            $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'skills' => $skills]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to handle user skills']);
    }
    exit;
}
?>
