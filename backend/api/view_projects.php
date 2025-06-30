<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MongoLogger.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/../utils/Validator.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        ApiResponse::error('Only GET method allowed', 405);
    }
    $db = Database::getInstance()->getConnection();
    $mongoLogger = new MongoLogger();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(50, max(10, (int)($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $per_page;
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'recent'; 
    $min_funding = (float)($_GET['min_funding'] ?? 0);
    $max_funding = (float)($_GET['max_funding'] ?? 0);
    $validator = new Validator();
    if ($page < 1) {
        $validator->min(1);
    }
    if ($per_page < 10 || $per_page > 50) {
        $validator->min(10)->max(50);
    }
    if (!$validator->isValid()) {
        ApiResponse::error(implode(', ', $validator->getErrors()), 400);
    }
    $where_conditions = ["p.stato = 'aperto'", "p.data_limite > NOW()"];
    $params = [];
    if (!empty($category) && $category !== 'all') {
        $where_conditions[] = "p.tipo_progetto = ?";
        $params[] = $category;
    }
    if (!empty($search)) {
        $where_conditions[] = "(p.nome LIKE ? OR p.descrizione LIKE ? OR u.nickname LIKE ?)";
        $search_term = '%' . $search . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    if ($min_funding > 0) {
        $where_conditions[] = "p.budget_richiesto >= ?";
        $params[] = $min_funding;
    }
    if ($max_funding > 0) {
        $where_conditions[] = "p.budget_richiesto <= ?";
        $params[] = $max_funding;
    }
    $where_clause = implode(' AND ', $where_conditions);
    switch ($sort) {
        case 'funded':
            $order_clause = "current_funding DESC";
            break;
        case 'ending_soon':
            $order_clause = "p.data_limite ASC";
            break;
        case 'title':
            $order_clause = "p.nome ASC";
            break;
        case 'popular':
            $order_clause = "backers_count DESC";
            break;        default:
            $order_clause = "p.created_at DESC";
    }
    $count_query = "
        SELECT COUNT(*) as total
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        WHERE $where_clause
    ";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_projects = $count_stmt->fetch()['total'];
    $projects_query = "
        SELECT 
            p.id,
            p.nome as title,
            p.descrizione as description,
            p.budget_richiesto as funding_goal,
            p.foto as image,
            p.tipo_progetto as category,
            p.data_limite as deadline,
            p.created_at as created_at,
            u.nickname as creator_name,
            u.id as creator_id,
            COALESCE(SUM(f.importo), 0) as current_funding,
            COUNT(DISTINCT f.id) as backers_count,
            ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) as funding_percentage,
            DATEDIFF(p.data_limite, NOW()) as days_left
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato'
        WHERE $where_clause
        GROUP BY p.id, p.nome, p.descrizione, p.budget_richiesto, p.foto, p.tipo_progetto, p.data_limite, p.created_at, u.nickname, u.id
        ORDER BY $order_clause
        LIMIT ? OFFSET ?
    ";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt = $db->prepare($projects_query);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
    if (!empty($projects)) {
        $project_ids = array_column($projects, 'id');
        $placeholders = str_repeat('?,', count($project_ids) - 1) . '?';
          $skills_query = "
            SELECT 
                srp.profilo_id as project_id,
                c.nome as skill_name,
                c.categoria as skill_category
            FROM skill_richieste_profilo srp
            JOIN competenze c ON srp.competenza_id = c.id
            JOIN profili_software ps ON srp.profilo_id = ps.id
            WHERE ps.progetto_id IN ($placeholders)
        ";
        $skills_stmt = $db->prepare($skills_query);
        $skills_stmt->execute($project_ids);
        $project_skills = $skills_stmt->fetchAll();
        $skills_by_project = [];
        foreach ($project_skills as $skill) {
            $skills_by_project[$skill['project_id']][] = [
                'name' => $skill['skill_name'],
                'category' => $skill['skill_category']
            ];
        }
        foreach ($projects as &$project) {
            $project['required_skills'] = $skills_by_project[$project['id']] ?? [];
        }
    }
    $total_pages = ceil($total_projects / $per_page);
    $has_next = $page < $total_pages;
    $has_prev = $page > 1;
    $categories_query = "
        SELECT DISTINCT tipo_progetto as category, COUNT(*) as count
        FROM progetti p
        WHERE p.stato = 'aperto' AND p.data_limite > NOW()
        GROUP BY tipo_progetto
        ORDER BY tipo_progetto
    ";
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll();
    $user_id = $_SESSION['user_id'] ?? null;
    $mongoLogger->logActivity($user_id, 'projects_viewed', [
        'filters' => [
            'category' => $category,
            'search' => $search,
            'sort' => $sort,
            'page' => $page
        ],
        'results_count' => count($projects),
        'total_available' => $total_projects
    ]);
    ApiResponse::success([
        'projects' => $projects,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
            'total_projects' => $total_projects,
            'has_next' => $has_next,
            'has_prev' => $has_prev
        ],
        'filters' => [
            'categories' => $categories,
            'applied_filters' => [
                'category' => $category,
                'search' => $search,
                'sort' => $sort,
                'min_funding' => $min_funding,
                'max_funding' => $max_funding
            ]
        ]
    ]);
} catch (PDOException $e) {
    error_log("Database error in view_projects.php: " . $e->getMessage());
    ApiResponse::serverError('Database error occurred');
} catch (Exception $e) {
    error_log("Error in view_projects.php: " . $e->getMessage());
    ApiResponse::serverError('An unexpected error occurred');
}
