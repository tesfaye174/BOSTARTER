<?php
/**
 * Volume Analysis API Endpoint
 * Provides access to the volume analysis service for redundancy evaluation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../services/VolumeAnalysisService.php';
require_once '../utils/ApiResponse.php';
require_once '../utils/Auth.php';

try {
    // Initialize database connection
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Initialize volume analysis service
    $volumeService = new VolumeAnalysisService($db);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest($volumeService);
            break;
            
        case 'POST':
            handlePostRequest($volumeService);
            break;
            
        default:
            ApiResponse::error('Method not allowed', 405);
            break;
    }
    
} catch (Exception $e) {
    error_log("Volume Analysis API Error: " . $e->getMessage());
    ApiResponse::error('Internal server error', 500, [
        'details' => $e->getMessage()
    ]);
}

function handleGetRequest($volumeService) {
    $action = $_GET['action'] ?? 'full_analysis';
    
    switch ($action) {
        case 'full_analysis':
            $analysis = $volumeService->performFullAnalysis();
            ApiResponse::success($analysis);
            break;
            
        case 'redundancy_analysis':
            $analysis = $volumeService->analyzeRedundancy();
            ApiResponse::success($analysis);
            break;
            
        case 'operations_analysis':
            $analysis = $volumeService->analyzeOperations();
            ApiResponse::success($analysis);
            break;
            
        case 'recommendations':
            $recommendations = $volumeService->getRecommendations();
            ApiResponse::success($recommendations);
            break;
            
        case 'performance_impact':
            $impact = $volumeService->assessPerformanceImpact();
            ApiResponse::success($impact);
            break;
            
        case 'current_stats':
            $stats = $volumeService->getCurrentSystemStats();
            ApiResponse::success($stats);
            break;
            
        default:
            ApiResponse::error('Invalid action parameter', 400);
            break;
    }
}

function handlePostRequest($volumeService) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ApiResponse::error('Invalid JSON input', 400);
        return;
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'custom_analysis':
            $params = $input['parameters'] ?? [];
            
            // Validate custom parameters
            $validParams = [
                'num_projects' => $params['num_projects'] ?? 10,
                'fundings_per_project' => $params['fundings_per_project'] ?? 3,
                'num_users' => $params['num_users'] ?? 5,
                'projects_per_user' => $params['projects_per_user'] ?? 2,
                'wI' => $params['wI'] ?? 1,
                'wB' => $params['wB'] ?? 0.5,
                'a' => $params['a'] ?? 2
            ];
            
            $analysis = $volumeService->performCustomAnalysis($validParams);
            ApiResponse::success($analysis);
            break;
            
        case 'test_consistency':
            $testResults = $volumeService->testConsistency();
            ApiResponse::success($testResults);
            break;
            
        case 'fix_inconsistencies':
            $fixResults = $volumeService->fixInconsistencies();
            ApiResponse::success($fixResults);
            break;
            
        default:
            ApiResponse::error('Invalid action parameter', 400);
            break;
    }
}
?>
