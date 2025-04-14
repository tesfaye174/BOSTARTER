<?php

require_once 'config/database.php';
require_once 'models/Donation.php';
require_once 'controllers/DonationController.php';
require_once 'security.php';

// Abilita CORS per lo sviluppo locale
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Gestisci richieste OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Headers di sicurezza
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

try {
    $donationController = new DonationController();
    $security = new Security();

    // Verifica il metodo della richiesta
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verifica il token CSRF
        if (!$security->validateCSRFToken()) {
            throw new Exception('Token CSRF non valido');
        }

        // Ottieni e valida i dati della donazione
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['user_id']) || !isset($data['project_id']) || !isset($data['amount'])) {
            throw new Exception('Dati mancanti per la donazione');
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception('Importo donazione non valido');
        }

        // Crea la donazione
        $result = $donationController->createDonation($data);
        echo json_encode(['success' => true, 'data' => $result]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Ottieni parametri dalla query string
        $projectId = $_GET['project_id'] ?? null;
        $userId = $_GET['user_id'] ?? null;

        if ($projectId) {
            $donations = $donationController->getDonationsByProject($projectId);
            echo json_encode(['success' => true, 'data' => $donations]);
        } elseif ($userId) {
            $donations = $donationController->getDonationsByUser($userId);
            echo json_encode(['success' => true, 'data' => $donations]);
        } else {
            throw new Exception('Parametro project_id o user_id richiesto');
        }
    } else {
        throw new Exception('Metodo non supportato');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 