<?php
// Prevent any output before headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Clear any previous output
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
    $action = $_GET['action'] ?? '';

    if ($method !== 'GET') {
        throw new Exception('Method not allowed');
    }

    switch ($action) {
        case 'overview':
            $stats = [
                'total_progetti' => $projectModel->getTotalProjects(),
                'progetti_hardware' => $projectModel->getProjectsByType('hardware'),
                'progetti_software' => $projectModel->getProjectsByType('software'),
                'finanziamenti_totali' => $projectModel->getTotalFunding(),
                'media_finanziamento' => $projectModel->getAverageFunding(),
                'progetti_completati' => $projectModel->getCompletedProjects()
            ];

            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code($e->getMessage() === 'Method not allowed' ? 405 : 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// In questo endpoint non sono presenti input utente da validare, ma la Validator è pronta per eventuali estensioni future.

ob_end_flush();
