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

$database = new Database();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

session_start();

// Check if user is authenticated for write operations
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}

// Check if user is admin for admin operations
function requireAdmin($db) {
    requireAuth();
    
    $stmt = $db->prepare("SELECT role FROM USERS WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$request = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGetSkills($db, $mongoLogger);
        break;
    case 'POST':
        handleCreateSkill($db, $mongoLogger, $request);
        break;
    case 'PUT':
        handleUpdateSkill($db, $mongoLogger, $request);
        break;
    case 'DELETE':
        handleDeleteSkill($db, $mongoLogger);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGetSkills($db, $mongoLogger) {
    try {
        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';
        $includeStats = isset($_GET['stats']) && $_GET['stats'] === 'true';

        $baseQuery = "SELECT s.*, sc.name as category_name FROM SKILLS s 
                      LEFT JOIN SKILL_CATEGORIES sc ON s.category_id = sc.category_id
                      WHERE s.status = 'active'";
        $params = [];

        // Add category filter
        if (!empty($category)) {
            $baseQuery .= " AND sc.name = ?";
            $params[] = $category;
        }

        // Add search filter
        if (!empty($search)) {
            $baseQuery .= " AND (s.name LIKE ? OR s.description LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $baseQuery .= " ORDER BY s.name ASC";

        $stmt = $db->prepare($baseQuery);
        $stmt->execute($params);
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Include usage statistics if requested
        if ($includeStats) {
            foreach ($skills as &$skill) {
                // Get projects requiring this skill
                $projectStmt = $db->prepare("
                    SELECT COUNT(*) as project_count
                    FROM PROJECT_SKILLS ps
                    JOIN PROJECTS p ON ps.project_id = p.project_id
                    WHERE ps.skill_id = ? AND p.status = 'open'
                ");
                $projectStmt->execute([$skill['skill_id']]);
                $skill['active_projects'] = $projectStmt->fetch(PDO::FETCH_ASSOC)['project_count'];

                // Get users with this skill
                $userStmt = $db->prepare("
                    SELECT COUNT(*) as user_count
                    FROM USER_SKILLS us
                    JOIN USERS u ON us.user_id = u.user_id
                    WHERE us.skill_id = ? AND u.status = 'active'
                ");
                $userStmt->execute([$skill['skill_id']]);
                $skill['skilled_users'] = $userStmt->fetch(PDO::FETCH_ASSOC)['user_count'];
            }
        }

        // Log activity
        if (isset($_SESSION['user_id'])) {
            $mongoLogger->logActivity($_SESSION['user_id'], 'skills_viewed', [
                'category' => $category,
                'search' => $search,
                'include_stats' => $includeStats
            ]);
        }

        echo json_encode([
            'success' => true,
            'skills' => $skills
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('skills_get_error', [
            'error' => $e->getMessage(),
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch skills']);
    }
}

function handleCreateSkill($db, $mongoLogger, $request) {
    requireAdmin($db);
    
    try {
        // Validate required fields
        if (!isset($request['name']) || empty($request['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Skill name is required']);
            return;
        }

        // Check if skill already exists
        $checkStmt = $db->prepare("SELECT skill_id FROM SKILLS WHERE name = ?");
        $checkStmt->execute([$request['name']]);
        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Skill already exists']);
            return;
        }

        // Get or create category
        $categoryId = null;
        if (!empty($request['category'])) {
            $catStmt = $db->prepare("SELECT category_id FROM SKILL_CATEGORIES WHERE name = ?");
            $catStmt->execute([$request['category']]);
            $category = $catStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                // Create new category
                $createCatStmt = $db->prepare("INSERT INTO SKILL_CATEGORIES (name, description, created_at) VALUES (?, ?, NOW())");
                $createCatStmt->execute([$request['category'], $request['category_description'] ?? '']);
                $categoryId = $db->lastInsertId();
            } else {
                $categoryId = $category['category_id'];
            }
        }

        // Insert skill
        $insertStmt = $db->prepare("
            INSERT INTO SKILLS (name, description, category_id, status, created_at) 
            VALUES (?, ?, ?, 'active', NOW())
        ");
        $insertStmt->execute([
            $request['name'],
            $request['description'] ?? '',
            $categoryId
        ]);
        
        $skillId = $db->lastInsertId();

        $mongoLogger->logActivity($_SESSION['user_id'], 'skill_created', [
            'skill_id' => $skillId,
            'skill_name' => $request['name'],
            'category' => $request['category'] ?? null
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Skill created successfully',
            'skill_id' => $skillId
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('skill_create_error', [
            'error' => $e->getMessage(),
            'skill_name' => $request['name'] ?? 'unknown',
            'user_id' => $_SESSION['user_id']
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create skill']);
    }
}

function handleUpdateSkill($db, $mongoLogger, $request) {
    requireAdmin($db);
    
    try {
        if (!isset($request['skill_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Skill ID is required']);
            return;
        }

        // Check if skill exists
        $checkStmt = $db->prepare("SELECT skill_id, name FROM SKILLS WHERE skill_id = ?");
        $checkStmt->execute([$request['skill_id']]);
        $skill = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$skill) {
            http_response_code(404);
            echo json_encode(['error' => 'Skill not found']);
            return;
        }

        // Prepare update fields
        $updateFields = [];
        $params = [];

        if (isset($request['name']) && !empty($request['name'])) {
            $updateFields[] = "name = ?";
            $params[] = $request['name'];
        }

        if (isset($request['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $request['description'];
        }

        if (isset($request['status']) && in_array($request['status'], ['active', 'inactive'])) {
            $updateFields[] = "status = ?";
            $params[] = $request['status'];
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update']);
            return;
        }

        $params[] = $request['skill_id'];
        
        $updateStmt = $db->prepare("UPDATE SKILLS SET " . implode(', ', $updateFields) . " WHERE skill_id = ?");
        $updateStmt->execute($params);

        $mongoLogger->logActivity($_SESSION['user_id'], 'skill_updated', [
            'skill_id' => $request['skill_id'],
            'old_name' => $skill['name'],
            'new_name' => $request['name'] ?? $skill['name'],
            'updated_fields' => array_keys($request)
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Skill updated successfully'
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('skill_update_error', [
            'error' => $e->getMessage(),
            'skill_id' => $request['skill_id'] ?? 'unknown',
            'user_id' => $_SESSION['user_id']
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update skill']);
    }
}

function handleDeleteSkill($db, $mongoLogger) {
    requireAdmin($db);
    
    try {
        $skillId = $_GET['skill_id'] ?? null;
        
        if (!$skillId) {
            http_response_code(400);
            echo json_encode(['error' => 'Skill ID is required']);
            return;
        }

        // Check if skill exists
        $checkStmt = $db->prepare("SELECT name FROM SKILLS WHERE skill_id = ?");
        $checkStmt->execute([$skillId]);
        $skill = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$skill) {
            http_response_code(404);
            echo json_encode(['error' => 'Skill not found']);
            return;
        }

        // Check if skill is in use
        $usageStmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM PROJECT_SKILLS WHERE skill_id = ?) as project_count,
                (SELECT COUNT(*) FROM USER_SKILLS WHERE skill_id = ?) as user_count
        ");
        $usageStmt->execute([$skillId, $skillId]);
        $usage = $usageStmt->fetch(PDO::FETCH_ASSOC);

        if ($usage['project_count'] > 0 || $usage['user_count'] > 0) {
            // Soft delete - mark as inactive instead of deleting
            $updateStmt = $db->prepare("UPDATE SKILLS SET status = 'inactive' WHERE skill_id = ?");
            $updateStmt->execute([$skillId]);
            
            $mongoLogger->logActivity($_SESSION['user_id'], 'skill_deactivated', [
                'skill_id' => $skillId,
                'skill_name' => $skill['name'],
                'reason' => 'in_use',
                'project_count' => $usage['project_count'],
                'user_count' => $usage['user_count']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Skill deactivated (was in use)',
                'action' => 'deactivated'
            ]);
        } else {
            // Hard delete - skill not in use
            $deleteStmt = $db->prepare("DELETE FROM SKILLS WHERE skill_id = ?");
            $deleteStmt->execute([$skillId]);
            
            $mongoLogger->logActivity($_SESSION['user_id'], 'skill_deleted', [
                'skill_id' => $skillId,
                'skill_name' => $skill['name']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Skill deleted successfully',
                'action' => 'deleted'
            ]);
        }

    } catch (Exception $e) {
        $mongoLogger->logError('skill_delete_error', [
            'error' => $e->getMessage(),
            'skill_id' => $_GET['skill_id'] ?? 'unknown',
            'user_id' => $_SESSION['user_id']
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete skill']);
    }
}

// Handle skill categories endpoint
if (isset($_GET['categories'])) {
    try {
        $stmt = $db->prepare("
            SELECT sc.*, COUNT(s.skill_id) as skill_count
            FROM SKILL_CATEGORIES sc
            LEFT JOIN SKILLS s ON sc.category_id = s.category_id AND s.status = 'active'
            GROUP BY sc.category_id
            ORDER BY sc.name ASC
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch categories']);
    }
    exit;
}
?>