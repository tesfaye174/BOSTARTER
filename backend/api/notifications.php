<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../services/PerformanceService.php';
require_once __DIR__ . '/../services/SecurityService.php';
require_once __DIR__ . '/../services/EmailNotificationService.php';
require_once __DIR__ . '/../utils/Validator.php';

use BOSTARTER\Controllers\NotificationController;
use BOSTARTER\Services\PerformanceService;
use BOSTARTER\Services\SecurityService;
use BOSTARTER\Services\EmailNotificationService;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize services
$database = Database::getInstance();
$db = $database->getConnection();
$performanceService = new PerformanceService($db);
$securityService = new SecurityService($db, $performanceService);
$emailService = new EmailNotificationService($db);
$controller = new NotificationController($db);

// Security checks
if ($securityService->isIPBlocked()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$rateLimitKey = 'api:' . $clientIP;
if (!$securityService->checkRateLimit($rateLimitKey, 100, 3600)) { // 100 requests per hour
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded']);
    exit();
}

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
    case 'GET':        switch ($action) {
            case 'unread':
                $limit = $_GET['limit'] ?? 20;
                // Use cached version for better performance
                $result = $performanceService->getCachedUserNotifications($userId, $limit, 0);
                if ($result === false) {
                    $result = $controller->getUnread($userId, $limit);
                } else {
                    // Filter for unread only
                    $result = array_filter($result, function($n) { return !$n['is_read']; });
                    $result = array_slice($result, 0, $limit);
                }
                break;
                
            case 'all':
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 20;
                $offset = ($page - 1) * $limit;
                
                // Use cached version
                $result = $performanceService->getCachedUserNotifications($userId, $limit, $offset);
                if ($result === false) {
                    $result = $controller->getAll($userId, $page, $limit);
                }
                break;
                  case 'count':
                $result = $controller->getUnreadCount($userId);
                break;
                
            case 'settings':
                $result = $controller->getNotificationSettings($userId);
                break;
                
            case 'stats':
                // Admin only
                if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'amministratore') {
                    http_response_code(403);
                    $result = ['status' => 'error', 'message' => 'Admin access required'];
                    break;
                }
                
                $days = $_GET['days'] ?? 7;
                $result = [
                    'security_stats' => $securityService->getSecurityStats($days),
                    'cache_stats' => $performanceService->getCacheStats(),
                    'email_queue_stats' => $emailService->getQueueStatistics()
                ];
                break;
                
            case 'performance':
                // Admin only - performance metrics
                if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'amministratore') {
                    http_response_code(403);
                    $result = ['status' => 'error', 'message' => 'Admin access required'];
                    break;
                }
                
                $result = [
                    'slow_queries' => $performanceService->getSlowQueries(10),
                    'cache_stats' => $performanceService->getCacheStats(),
                    'security_summary' => [
                        'attacking_ips' => $securityService->getTopAttackingIPs(5),
                        'recent_events' => $securityService->getSecurityStats(1)
                    ]
                ];
                break;
                
            case 'templates':
                // Get notification templates
                $stmt = $db->prepare("SELECT * FROM notification_templates WHERE is_active = TRUE ORDER BY type, name");
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'types':
                $result = $controller->getNotificationTypes();
                break;
                
            case 'settings':
                $result = $controller->getUserNotificationSettings($userId);
                break;
                
            default:
                // Get all notifications if no specific action
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 20;
                $result = $controller->getAll($userId, $page, $limit);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'bulk_action':
                if (!isset($data['notification_ids']) || !isset($data['action'])) {
                    http_response_code(400);
                    $result = ['status' => 'error', 'message' => 'Notification IDs and action required'];
                    break;
                }
                $result = $controller->bulkAction($data['notification_ids'], $data['action'], $userId);
                break;
                
            default:
                http_response_code(400);
                $result = ['status' => 'error', 'message' => 'Invalid action'];
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'mark_read':
                if (!isset($data['notification_id'])) {
                    http_response_code(400);
                    $result = ['status' => 'error', 'message' => 'Notification ID required'];
                    break;
                }
                $result = $controller->markAsRead($data['notification_id'], $userId);
                break;
                
                        case 'mark_all_read':
                $result = $controller->markAllAsRead($userId);
                // Invalidate user notifications cache
                $performanceService->invalidateUserNotifications($userId);
                break;
                
            case 'update_settings':
                if (!isset($data['settings'])) {
                    http_response_code(400);
                    $result = ['status' => 'error', 'message' => 'Settings data required'];
                    break;
                }
                $result = $controller->updateNotificationSettings($userId, $data['settings']);
                break;
                  case 'send_test_email':
                // Admin only feature for email notifications
                if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'amministratore') {
                    http_response_code(403);
                    $result = ['status' => 'error', 'message' => 'Admin access required'];
                    break;
                }
                
                $templateName = $data['template'] ?? 'project_backed';
                $variables = $data['variables'] ?? ['backer_name' => 'Test User', 'project_title' => 'Test Project', 'amount' => '$100'];
                $success = $emailService->sendImmediateEmail($userId, $templateName, $variables);
                $result = ['status' => $success ? 'success' : 'error', 'message' => $success ? 'Test email sent' : 'Failed to send test email'];
                break;
                
            case 'bulk_delete':
                if (!isset($data['notification_ids']) || !is_array($data['notification_ids'])) {
                    http_response_code(400);
                    $result = ['status' => 'error', 'message' => 'Notification IDs required'];
                    break;
                }
                
                $deleted = 0;
                foreach ($data['notification_ids'] as $notificationId) {
                    if ($controller->delete($notificationId, $userId)) {
                        $deleted++;
                    }
                }
                
                // Invalidate cache
                $performanceService->invalidateUserNotifications($userId);
                $result = ['status' => 'success', 'message' => "Deleted $deleted notifications"];
                break;
                
            case 'bulk_mark_read':
                if (!isset($data['notification_ids']) || !is_array($data['notification_ids'])) {
                    http_response_code(400);
                    $result = ['status' => 'error', 'message' => 'Notification IDs required'];
                    break;
                }
                
                $marked = 0;
                foreach ($data['notification_ids'] as $notificationId) {
                    if ($controller->markAsRead($notificationId, $userId)) {
                        $marked++;
                    }
                }
                
                // Invalidate cache
                $performanceService->invalidateUserNotifications($userId);
                $result = ['status' => 'success', 'message' => "Marked $marked notifications as read"];
                break;
                
            default:
                http_response_code(400);
                $result = ['status' => 'error', 'message' => 'Invalid action'];
        }
        break;

    case 'DELETE':
        switch ($action) {
            case 'single':
                if (!isset($_GET['id'])) {
                    http_response_code(400);
                    $result = ['status' => 'error', 'message' => 'Notification ID required'];
                    break;
                }
                $result = $controller->delete($_GET['id'], $userId);
                break;
                
            case 'all_read':
                $result = $controller->deleteAllRead($userId);
                break;
                
            case 'older_than':
                $days = $_GET['days'] ?? 30;
                $result = $controller->deleteOlderThan($userId, $days);
                break;
                
            default:
                if (!isset($_GET['id'])) {
                    http_response_code(400);
                    $result = ['status' => 'error', 'message' => 'Notification ID required'];
                    break;
                }
                $result = $controller->delete($_GET['id'], $userId);
        }
        break;

    default:
        http_response_code(405);
        $result = ['status' => 'error', 'message' => 'Method not supported'];
}

// Invia la risposta
echo json_encode($result);