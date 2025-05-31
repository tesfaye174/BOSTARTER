<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../services/MongoLogger.php';

$database = new Database();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    handleSearch($db, $mongoLogger);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

function handleSearch($db, $mongoLogger) {
    try {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'all'; // all, hardware, software
        $status = $_GET['status'] ?? 'open'; // open, closed, all
        $sort = $_GET['sort'] ?? 'relevance'; // relevance, newest, oldest, funding, deadline
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $offset = max(0, intval($_GET['offset'] ?? 0));

        // Log search activity
        session_start();
        if (isset($_SESSION['user_id'])) {
            $mongoLogger->logActivity($_SESSION['user_id'], 'search', [
                'query' => $query,
                'type' => $type,
                'status' => $status,
                'sort' => $sort
            ]);
        } else {
            $mongoLogger->logSystem('anonymous_search', [
                'query' => $query,
                'type' => $type,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }

        // Build base query
        $baseQuery = "
            SELECT p.*, u.username as creator_name,
                   COALESCE(pf.total_funded, 0) as total_funded,
                   COALESCE(pf.funding_percentage, 0) as funding_percentage,
                   COALESCE(pf.backers_count, 0) as backers_count,
                   DATEDIFF(p.deadline, NOW()) as days_left,
                   MATCH(p.title, p.description) AGAINST (? IN NATURAL LANGUAGE MODE) as relevance_score
            FROM PROJECTS p
            JOIN USERS u ON p.creator_id = u.user_id
            LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
            WHERE 1=1
        ";

        $params = [];
        $whereConditions = [];

        // Add search query condition
        if (!empty($query)) {
            $whereConditions[] = "MATCH(p.title, p.description) AGAINST (? IN NATURAL LANGUAGE MODE)";
            $params[] = $query;
            $baseQuery = str_replace('?', '?', $baseQuery); // First ? is for relevance score
            array_unshift($params, $query); // Add query at beginning for relevance score
        } else {
            $baseQuery = str_replace('MATCH(p.title, p.description) AGAINST (? IN NATURAL LANGUAGE MODE) as relevance_score', '0 as relevance_score', $baseQuery);
        }

        // Add project type filter
        if ($type !== 'all') {
            $whereConditions[] = "p.project_type = ?";
            $params[] = $type;
        }

        // Add status filter
        if ($status !== 'all') {
            $whereConditions[] = "p.status = ?";
            $params[] = $status;
        }

        // Add WHERE conditions
        if (!empty($whereConditions)) {
            $baseQuery .= " AND " . implode(' AND ', $whereConditions);
        }

        // Add ORDER BY
        switch ($sort) {
            case 'newest':
                $baseQuery .= " ORDER BY p.created_at DESC";
                break;
            case 'oldest':
                $baseQuery .= " ORDER BY p.created_at ASC";
                break;
            case 'funding':
                $baseQuery .= " ORDER BY pf.funding_percentage DESC, p.created_at DESC";
                break;
            case 'deadline':
                $baseQuery .= " ORDER BY p.deadline ASC";
                break;
            case 'relevance':
            default:
                if (!empty($query)) {
                    $baseQuery .= " ORDER BY relevance_score DESC, pf.funding_percentage DESC";
                } else {
                    $baseQuery .= " ORDER BY p.created_at DESC";
                }
                break;
        }

        // Add LIMIT and OFFSET
        $baseQuery .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        // Execute query
        $stmt = $db->prepare($baseQuery);
        $stmt->execute($params);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countQuery = "
            SELECT COUNT(*) as total
            FROM PROJECTS p
            JOIN USERS u ON p.creator_id = u.user_id
            WHERE 1=1
        ";

        $countParams = [];
        if (!empty($query)) {
            $countQuery .= " AND MATCH(p.title, p.description) AGAINST (? IN NATURAL LANGUAGE MODE)";
            $countParams[] = $query;
        }

        if ($type !== 'all') {
            $countQuery .= " AND p.project_type = ?";
            $countParams[] = $type;
        }

        if ($status !== 'all') {
            $countQuery .= " AND p.status = ?";
            $countParams[] = $status;
        }

        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Format results
        $formattedProjects = array_map(function($project) {
            return [
                'project_id' => $project['project_id'],
                'title' => $project['title'],
                'description' => $project['description'],
                'project_type' => $project['project_type'],
                'status' => $project['status'],
                'funding_goal' => $project['funding_goal'],
                'deadline' => $project['deadline'],
                'created_at' => $project['created_at'],
                'creator_name' => $project['creator_name'],
                'total_funded' => floatval($project['total_funded']),
                'funding_percentage' => floatval($project['funding_percentage']),
                'backers_count' => intval($project['backers_count']),
                'days_left' => intval($project['days_left']),
                'relevance_score' => floatval($project['relevance_score'] ?? 0)
            ];
        }, $projects);

        echo json_encode([
            'success' => true,
            'projects' => $formattedProjects,
            'pagination' => [
                'total' => intval($totalCount),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'search_params' => [
                'query' => $query,
                'type' => $type,
                'status' => $status,
                'sort' => $sort
            ]
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('search_error', [
            'error' => $e->getMessage(),
            'query' => $_GET['q'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Search failed']);
    }
}

// Helper function for search suggestions
function getSearchSuggestions($db, $query, $limit = 5) {
    try {
        $stmt = $db->prepare("
            SELECT DISTINCT title
            FROM PROJECTS 
            WHERE title LIKE ? 
            AND status = 'open'
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute(['%' . $query . '%', $limit]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

// Handle search suggestions endpoint
if (isset($_GET['suggestions']) && !empty($_GET['q'])) {
    $suggestions = getSearchSuggestions($db, $_GET['q']);
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions
    ]);
    exit;
}
?>