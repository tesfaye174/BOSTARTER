<?php

require_once __DIR__ . '/../controllers/DonationController.php';
require_once __DIR__ . '/../utils/security.php';

// Abilita CORS per lo sviluppo locale
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verifica autenticazione
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit();
}

$donationController = new DonationController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        // Crea una nuova donazione
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['project_id']) || !isset($data['amount'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati mancanti']);
            exit();
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Importo non valido']);
            exit();
        }

        try {
            $data['user_id'] = $_SESSION['user_id'];
            $donation = $donationController->createDonation($data);
            echo json_encode($donation->toArray());
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore durante la creazione della donazione']);
        }
        break;

    case 'GET':
        // Recupera donazioni per progetto o utente
        if (isset($_GET['project_id'])) {
            $donations = $donationController->getDonationsByProject((int)$_GET['project_id']);
            echo json_encode(array_map(fn($d) => $d->toArray(), $donations));
        } elseif (isset($_GET['user_id'])) {
            // Verifica che l'utente stia accedendo solo alle proprie donazioni
            if ($_GET['user_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Non autorizzato']);
                exit();
            }
            $donations = $donationController->getDonationsByUser((int)$_GET['user_id']);
            echo json_encode(array_map(fn($d) => $d->toArray(), $donations));
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Parametri mancanti']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Metodo non permesso']);
        break;
} 