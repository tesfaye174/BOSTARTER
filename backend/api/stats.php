<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../services/MongoLogger.php';

$database = new Database();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

session_start();

// Get the requested stats type
$statsType = $_GET['type'] ?? 'overview';

try {
    switch ($statsType) {
        case 'overview':
            getOverviewStats($db, $mongoLogger);
            break;
        case 'projects':
            getProjectStats($db, $mongoLogger);
            break;
        case 'users':
            getUserStats($db, $mongoLogger);
            break;
        case 'funding':
            getFundingStats($db, $mongoLogger);
            break;
        case 'top_creators':
            getTopCreators($db, $mongoLogger);
            break;
        case 'close_to_goal':
            getProjectsCloseToGoal($db, $mongoLogger);
            break;
        case 'trending':
            getTrendingProjects($db, $mongoLogger);
            break;
        case 'monthly':
            getMonthlyStats($db, $mongoLogger);
            break;
        case 'user':
            getUserDashboardStats($db, $mongoLogger);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid stats type']);
            break;
    }
} catch (Exception $e) {
    $mongoLogger->logError('stats_error', [
        'error' => $e->getMessage(),
        'stats_type' => $statsType,
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch statistics']);
}

function getOverviewStats($db, $mongoLogger) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT p.project_id) as total_projects,
            COUNT(DISTINCT CASE WHEN p.status = 'open' THEN p.project_id END) as active_projects,
            COUNT(DISTINCT CASE WHEN p.status = 'funded' THEN p.project_id END) as funded_projects,
            COUNT(DISTINCT u.user_id) as total_users,
            COUNT(DISTINCT f.user_id) as total_backers,
            COALESCE(SUM(f.amount), 0) as total_funded,
            COUNT(DISTINCT f.funding_id) as total_fundings,
            AVG(p.funding_goal) as avg_funding_goal
        FROM PROJECTS p
        LEFT JOIN USERS u ON p.creator_id = u.user_id
        LEFT JOIN FUNDINGS f ON p.project_id = f.project_id
    ");
    $stmt->execute();
    $overview = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get project type breakdown
    $typeStmt = $db->prepare("
        SELECT 
            project_type,
            COUNT(*) as count,
            COUNT(CASE WHEN status = 'open' THEN 1 END) as active_count,
            AVG(funding_goal) as avg_goal
        FROM PROJECTS 
        GROUP BY project_type
    ");
    $typeStmt->execute();
    $projectTypes = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Log activity
    if (isset($_SESSION['user_id'])) {
        $mongoLogger->logActivity($_SESSION['user_id'], 'stats_overview_viewed', []);
    }

    echo json_encode([
        'success' => true,
        'overview' => $overview,
        'project_types' => $projectTypes,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function getProjectStats($db, $mongoLogger) {
    // Projects by status
    $statusStmt = $db->prepare("
        SELECT status, COUNT(*) as count 
        FROM PROJECTS 
        GROUP BY status
    ");
    $statusStmt->execute();
    $byStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent projects
    $recentStmt = $db->prepare("
        SELECT p.*, u.username as creator_name,
               COALESCE(pf.total_funded, 0) as total_funded,
               COALESCE(pf.funding_percentage, 0) as funding_percentage
        FROM PROJECTS p
        JOIN USERS u ON p.creator_id = u.user_id
        LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $recentStmt->execute();
    $recentProjects = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Most funded projects
    $fundedStmt = $db->prepare("
        SELECT p.*, u.username as creator_name,
               COALESCE(pf.total_funded, 0) as total_funded,
               COALESCE(pf.funding_percentage, 0) as funding_percentage
        FROM PROJECTS p
        JOIN USERS u ON p.creator_id = u.user_id
        LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
        ORDER BY pf.total_funded DESC
        LIMIT 10
    ");
    $fundedStmt->execute();
    $mostFunded = $fundedStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'by_status' => $byStatus,
        'recent_projects' => $recentProjects,
        'most_funded' => $mostFunded,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function getUserStats($db, $mongoLogger) {
    // User registration trends
    $registrationStmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as new_users
        FROM USERS 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $registrationStmt->execute();
    $registrationTrend = $registrationStmt->fetchAll(PDO::FETCH_ASSOC);

    // Most active users
    $activeStmt = $db->prepare("
        SELECT u.user_id, u.username, u.created_at,
               COUNT(DISTINCT p.project_id) as projects_created,
               COUNT(DISTINCT f.funding_id) as fundings_made,
               COALESCE(SUM(f.amount), 0) as total_funded_amount
        FROM USERS u
        LEFT JOIN PROJECTS p ON u.user_id = p.creator_id
        LEFT JOIN FUNDINGS f ON u.user_id = f.user_id
        WHERE u.status = 'active'
        GROUP BY u.user_id
        HAVING (projects_created > 0 OR fundings_made > 0)
        ORDER BY (projects_created + fundings_made) DESC
        LIMIT 10
    ");
    $activeStmt->execute();
    $activeUsers = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'registration_trend' => $registrationTrend,
        'most_active_users' => $activeUsers,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function getFundingStats($db, $mongoLogger) {
    // Funding trends
    $trendStmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as funding_count,
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount
        FROM FUNDINGS 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $trendStmt->execute();
    $fundingTrend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

    // Funding distribution
    $distributionStmt = $db->prepare("
        SELECT 
            CASE 
                WHEN amount < 10 THEN '<€10'
                WHEN amount < 50 THEN '€10-€50'
                WHEN amount < 100 THEN '€50-€100'
                WHEN amount < 500 THEN '€100-€500'
                ELSE '€500+'
            END as amount_range,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM FUNDINGS
        GROUP BY amount_range
        ORDER BY MIN(amount)
    ");
    $distributionStmt->execute();
    $fundingDistribution = $distributionStmt->fetchAll(PDO::FETCH_ASSOC);

    // Top backers
    $backersStmt = $db->prepare("
        SELECT u.username, 
               COUNT(f.funding_id) as backing_count,
               SUM(f.amount) as total_backed,
               AVG(f.amount) as avg_backing
        FROM FUNDINGS f
        JOIN USERS u ON f.user_id = u.user_id
        GROUP BY f.user_id
        ORDER BY total_backed DESC
        LIMIT 10
    ");
    $backersStmt->execute();
    $topBackers = $backersStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'funding_trend' => $fundingTrend,
        'funding_distribution' => $fundingDistribution,
        'top_backers' => $topBackers,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function getTopCreators($db, $mongoLogger) {
    $stmt = $db->prepare("
        SELECT u.user_id, u.username, u.full_name, u.created_at,
               COUNT(DISTINCT p.project_id) as total_projects,
               COUNT(DISTINCT CASE WHEN p.status = 'funded' THEN p.project_id END) as successful_projects,
               COALESCE(SUM(CASE WHEN p.status = 'funded' THEN p.funding_goal END), 0) as total_raised,
               AVG(pf.funding_percentage) as avg_funding_percentage,
               MAX(p.created_at) as last_project_date
        FROM USERS u
        JOIN PROJECTS p ON u.user_id = p.creator_id
        LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
        WHERE u.status = 'active'
        GROUP BY u.user_id
        HAVING total_projects > 0
        ORDER BY successful_projects DESC, total_raised DESC
        LIMIT 20
    ");
    $stmt->execute();
    $topCreators = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log activity
    if (isset($_SESSION['user_id'])) {
        $mongoLogger->logActivity($_SESSION['user_id'], 'top_creators_viewed', []);
    }

    echo json_encode([
        'success' => true,
        'top_creators' => $topCreators,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function getProjectsCloseToGoal($db, $mongoLogger) {
    $stmt = $db->prepare("
        SELECT p.*, u.username as creator_name,
               COALESCE(pf.total_funded, 0) as total_funded,
               COALESCE(pf.funding_percentage, 0) as funding_percentage,
               COALESCE(pf.backers_count, 0) as backers_count,
               DATEDIFF(p.deadline, NOW()) as days_left,
               (p.funding_goal - COALESCE(pf.total_funded, 0)) as amount_needed
        FROM PROJECTS p
        JOIN USERS u ON p.creator_id = u.user_id
        LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
        WHERE p.status = 'open' 
        AND pf.funding_percentage >= 75
        AND p.deadline > NOW()
        ORDER BY pf.funding_percentage DESC, p.deadline ASC
        LIMIT 20
    ");
    $stmt->execute();
    $closeToGoal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log activity
    if (isset($_SESSION['user_id'])) {
        $mongoLogger->logActivity($_SESSION['user_id'], 'close_to_goal_viewed', []);
    }

    echo json_encode([
        'success' => true,
        'projects_close_to_goal' => $closeToGoal,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function getTrendingProjects($db, $mongoLogger) {
    $stmt = $db->prepare("
        SELECT p.*, u.username as creator_name,
               COALESCE(pf.total_funded, 0) as total_funded,
               COALESCE(pf.funding_percentage, 0) as funding_percentage,
               COALESCE(pf.backers_count, 0) as backers_count,
               DATEDIFF(p.deadline, NOW()) as days_left,
               recent_funding.recent_amount,
               recent_funding.recent_backers
        FROM PROJECTS p
        JOIN USERS u ON p.creator_id = u.user_id
        LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
        LEFT JOIN (
            SELECT project_id,
                   SUM(amount) as recent_amount,
                   COUNT(*) as recent_backers
            FROM FUNDINGS
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY project_id
        ) recent_funding ON p.project_id = recent_funding.project_id
        WHERE p.status = 'open'
        AND p.deadline > NOW()
        AND recent_funding.recent_amount > 0
        ORDER BY recent_funding.recent_amount DESC, recent_funding.recent_backers DESC
        LIMIT 20
    ");
    $stmt->execute();
    $trending = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'trending_projects' => $trending,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function getMonthlyStats($db, $mongoLogger) {
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('n');

    // Monthly overview
    $overviewStmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT p.project_id) as projects_created,
            COUNT(DISTINCT u.user_id) as users_registered,
            COUNT(DISTINCT f.funding_id) as fundings_made,
            COALESCE(SUM(f.amount), 0) as total_funded
        FROM PROJECTS p
        FULL OUTER JOIN USERS u ON YEAR(u.created_at) = ? AND MONTH(u.created_at) = ?
        FULL OUTER JOIN FUNDINGS f ON YEAR(f.created_at) = ? AND MONTH(f.created_at) = ?
        WHERE (YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?)
        OR (YEAR(u.created_at) = ? AND MONTH(u.created_at) = ?)
        OR (YEAR(f.created_at) = ? AND MONTH(f.created_at) = ?)
    ");
    $overviewStmt->execute([$year, $month, $year, $month, $year, $month, $year, $month, $year, $month]);
    $monthlyOverview = $overviewStmt->fetch(PDO::FETCH_ASSOC);

    // Daily breakdown
    $dailyStmt = $db->prepare("
        SELECT 
            DAY(date) as day,
            projects_created,
            users_registered,
            fundings_made,
            total_funded
        FROM (
            SELECT 
                DATE(created_at) as date,
                COUNT(CASE WHEN table_type = 'project' THEN 1 END) as projects_created,
                COUNT(CASE WHEN table_type = 'user' THEN 1 END) as users_registered,
                COUNT(CASE WHEN table_type = 'funding' THEN 1 END) as fundings_made,
                SUM(CASE WHEN table_type = 'funding' THEN amount ELSE 0 END) as total_funded
            FROM (
                SELECT created_at, 'project' as table_type, 0 as amount FROM PROJECTS
                UNION ALL
                SELECT created_at, 'user' as table_type, 0 as amount FROM USERS
                UNION ALL
                SELECT created_at, 'funding' as table_type, amount FROM FUNDINGS
            ) combined
            WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
            GROUP BY DATE(created_at)
        ) daily_stats
        ORDER BY day
    ");
    $dailyStmt->execute([$year, $month]);
    $dailyStats = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'year' => intval($year),
        'month' => intval($month),
        'monthly_overview' => $monthlyOverview,
        'daily_breakdown' => $dailyStats,
        'generated_at' => date('Y-m-d H:i:s')
    ]);

    // Log analytics access
    $mongoLogger->logActivity($_SESSION['user_id'] ?? null, 'analytics_monthly_access', [
        'year' => $year,
        'month' => $month,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function getUserDashboardStats($db, $mongoLogger) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    $user_id = $_SESSION['user_id'];

    // Get user project stats
    $projectsQuery = "SELECT 
                        COUNT(*) as total_created,
                        COUNT(CASE WHEN status = 'funded' THEN 1 END) as successful_projects,
                        COUNT(CASE WHEN status = 'open' THEN 1 END) as active_projects
                      FROM PROJECTS 
                      WHERE creator_id = ?";
    $projectsStmt = $db->prepare($projectsQuery);
    $projectsStmt->execute([$user_id]);
    $projectStats = $projectsStmt->fetch(PDO::FETCH_ASSOC);

    // Get user funding stats
    $fundingQuery = "SELECT 
                        COALESCE(SUM(amount), 0) as total_funded,
                        COUNT(*) as total_contributions
                     FROM FUNDINGS 
                     WHERE user_id = ?";
    $fundingStmt = $db->prepare($fundingQuery);
    $fundingStmt->execute([$user_id]);
    $fundingStats = $fundingStmt->fetch(PDO::FETCH_ASSOC);

    // Get user application stats
    $applicationQuery = "SELECT 
                            COUNT(*) as total_applications,
                            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_applications,
                            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_applications
                         FROM CANDIDATURE 
                         WHERE user_id = ?";
    $applicationStmt = $db->prepare($applicationQuery);
    $applicationStmt->execute([$user_id]);
    $applicationStats = $applicationStmt->fetch(PDO::FETCH_ASSOC);

    // Get recent activity count
    $recentActivityQuery = "SELECT COUNT(*) as recent_activities
                           FROM (
                               SELECT created_at FROM PROJECTS WHERE creator_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                               UNION ALL
                               SELECT funding_date as created_at FROM FUNDINGS WHERE user_id = ? AND funding_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                               UNION ALL
                               SELECT application_date as created_at FROM CANDIDATURE WHERE user_id = ? AND application_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                           ) recent";
    $recentActivityStmt = $db->prepare($recentActivityQuery);
    $recentActivityStmt->execute([$user_id, $user_id, $user_id]);
    $recentActivity = $recentActivityStmt->fetch(PDO::FETCH_ASSOC);

    // Combine all stats
    $stats = [
        'projects_created' => intval($projectStats['total_created']),
        'successful_projects' => intval($projectStats['successful_projects']),
        'active_projects' => intval($projectStats['active_projects']),
        'total_funded' => floatval($fundingStats['total_funded']),
        'total_contributions' => intval($fundingStats['total_contributions']),
        'applications' => intval($applicationStats['total_applications']),
        'accepted_applications' => intval($applicationStats['accepted_applications']),
        'pending_applications' => intval($applicationStats['pending_applications']),
        'recent_activities' => intval($recentActivity['recent_activities']),
        'generated_at' => date('Y-m-d H:i:s')
    ];

    echo json_encode([
        'success' => true,
        'user_id' => $user_id,
        'stats' => $stats
    ]);

    // Log dashboard stats access
    $mongoLogger->logActivity($user_id, 'dashboard_stats_access', [
        'timestamp' => date('Y-m-d H:i:s'),
        'stats_requested' => array_keys($stats)
    ]);
}
?>