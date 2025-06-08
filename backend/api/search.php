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
require_once '../utils/Validator.php';

$database = Database::getInstance();
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
        // Get and validate search params
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'all'; // all, hardware, software
        $status = $_GET['status'] ?? 'open'; // open, closed, all
        $sort = $_GET['sort'] ?? 'newest';
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $offset = max(0, intval($_GET['offset'] ?? 0));

        // Log search activity
        session_start();
        if (isset($_SESSION['user_id'])) {
            $mongoLogger->logActivity($_SESSION['user_id'], 'search', ['query'=>$query]);
        }

        // Build simplified search query
        $baseQuery = "SELECT p.id AS project_id, p.nome AS title, p.descrizione AS description, p.tipo_progetto AS project_type, p.stato AS status, p.budget_richiesto AS funding_goal, p.data_limite AS deadline, p.data_inserimento AS created_at, u.nickname AS creator_name, COALESCE((SELECT SUM(f.importo) FROM finanziamenti f WHERE f.progetto_id = p.id AND f.stato_pagamento='completato'),0) AS total_funded, ROUND(COALESCE((SELECT SUM(f.importo) FROM finanziamenti f WHERE f.progetto_id = p.id AND f.stato_pagamento='completato'),0)/p.budget_richiesto*100,1) AS funding_percentage, (SELECT COUNT(DISTINCT f.utente_id) FROM finanziamenti f WHERE f.progetto_id = p.id AND f.stato_pagamento='completato') AS backers_count, DATEDIFF(p.data_limite, NOW()) AS days_left FROM progetti p JOIN utenti u ON p.creatore_id = u.id WHERE 1=1";
        $params = [];
        if (!empty($query)) {
            $baseQuery .= " AND (p.nome LIKE ? OR p.descrizione LIKE ?)";
            $params[] = "%{$query}%";
            $params[] = "%{$query}%";
        }
        if ($type !== 'all') {
            $baseQuery .= " AND p.tipo_progetto = ?";
            $params[] = $type;
        }
        if ($status !== 'all') {
            $st = $status === 'open' ? 'aperto' : 'chiuso';
            $baseQuery .= " AND p.stato = ?";
            $params[] = $st;
        }
        // Add ORDER BY
        switch ($sort) {
            case 'oldest': $baseQuery .= " ORDER BY p.data_inserimento ASC"; break;
            case 'funding': $baseQuery .= " ORDER BY total_funded DESC"; break;
            case 'deadline': $baseQuery .= " ORDER BY p.data_limite ASC"; break;
            case 'newest':
            default: $baseQuery .= " ORDER BY p.data_inserimento DESC"; break;
        }
        // Add LIMIT OFFSET
        $baseQuery .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($baseQuery);
        $stmt->execute($params);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countQuery = "SELECT COUNT(*) AS total FROM progetti p WHERE 1=1";
        $countParams = [];
        if (!empty($query)) {
            $countQuery .= " AND (p.nome LIKE ? OR p.descrizione LIKE ?)";
            $countParams[] = "%{$query}%";
            $countParams[] = "%{$query}%";
        }
        if ($type !== 'all') {
            $countQuery .= " AND p.tipo_progetto = ?";
            $countParams[] = $type;
        }
        if ($status !== 'all') {
            $st = $status === 'open' ? 'aperto' : 'chiuso';
            $countQuery .= " AND p.stato = ?";
            $countParams[] = $st;
        }
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalCount = intval($countStmt->fetch(PDO::FETCH_ASSOC)['total']);

        echo json_encode([
            'success'=>true,
            'projects'=>$projects,
            'pagination'=>['total'=>$totalCount,'limit'=>$limit,'offset'=>$offset,'has_more'=>($offset+$limit)<$totalCount],
            'search_params'=>['query'=>$query,'type'=>$type,'status'=>$status,'sort'=>$sort]
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('search_error',['error'=>$e->getMessage(),'query'=>$_GET['q']??'']);
        http_response_code(500);
        echo json_encode(['error'=>'Search failed']);
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