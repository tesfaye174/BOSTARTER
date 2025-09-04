<?php
session_start();
require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Lista commenti per progetto
        if (isset($_GET['progetto_id'])) {
            $progettoId = (int)$_GET['progetto_id'];
            $commenti = getCommentiProgetto($progettoId);
            $apiResponse->sendSuccess($commenti);
        } else {
            $apiResponse->sendError('ID progetto richiesto');
        }
        break;
        
    case 'POST':
        // Nuovo commento o risposta
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Autenticazione richiesta', 401);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $apiResponse->sendError('Dati JSON non validi');
            exit();
        }
        
        if (isset($input['commento_id'])) {
            // Risposta a commento (solo creatore/admin del progetto)
            $result = creaRisposta($input, $roleManager);
        } else {
            // Nuovo commento
            $result = creaCommento($input, $roleManager->getUserId());
        }
        
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], $result['message']);
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'PUT':
        // Modifica commento (solo proprietario)
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Autenticazione richiesta', 401);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['commento_id']) || !isset($input['testo'])) {
            $apiResponse->sendError('ID commento e testo richiesti');
            exit();
        }
        
        $result = modificaCommento($input['commento_id'], $input['testo'], $roleManager);
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], 'Commento aggiornato');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'DELETE':
        // Cancella commento (solo proprietario o admin)
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Autenticazione richiesta', 401);
            exit();
        }
        
        $commentoId = (int)$_GET['id'];
        $result = cancellaCommento($commentoId, $roleManager);
        if ($result['success']) {
            $apiResponse->sendSuccess(null, 'Commento cancellato');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    default:
        $apiResponse->sendError('Metodo non supportato', 405);
        break;
}

// Funzioni helper
function getCommentiProgetto($progettoId) {
    $db = Database::getInstance()->getConnection();
    
    // Verifica che il progetto esista
    $stmt = $db->prepare("SELECT id, nome FROM progetti WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$progettoId]);
    if (!$stmt->fetch()) {
        return ['error' => 'Progetto non trovato'];
    }
    
    // Recupera commenti con risposte
    $stmt = $db->prepare(
        SELECT 
            c.id,
            c.testo,
            c.data_commento,
            c.utente_id,
            u.nickname,
            u.nome,
            u.cognome,
            r.testo as risposta_testo,
            r.data_risposta,
            r.creatore_id as risposta_creatore_id,
            ur.nickname as risposta_creatore_nickname
        FROM commenti c
        JOIN utenti u ON c.utente_id = u.id
        LEFT JOIN risposte_commenti r ON c.id = r.commento_id
        LEFT JOIN utenti ur ON r.creatore_id = ur.id
        WHERE c.progetto_id = ?
        ORDER BY c.data_commento DESC
    );
    $stmt->execute([$progettoId]);
    
    return $stmt->fetchAll();
}

function creaCommento($input, $utenteId) {
    $db = Database::getInstance()->getConnection();
    
    $required = ['progetto_id', 'testo'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            return ['success' => false, 'error' => "Campo obbligatorio mancante: $field"];
        }
    }
    
    try {
        // Verifica che il progetto esista e sia attivo
        $stmt = $db->prepare("SELECT id, stato FROM progetti WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$input['progetto_id']]);
        $progetto = $stmt->fetch();
        
        if (!$progetto) {
            return ['success' => false, 'error' => 'Progetto non trovato'];
        }
        
        // Usa stored procedure per inserimento
        $stmt = $db->prepare("CALL sp_inserisci_commento(?, ?, ?)");
        $stmt->execute([$utenteId, $input['progetto_id'], $input['testo']]);
        
        return [
            'success' => true, 
            'data' => ['message' => 'Commento inserito con successo'],
            'message' => 'Commento inserito con successo'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function creaRisposta($input, $roleManager) {
    $db = Database::getInstance()->getConnection();
    
    $required = ['commento_id', 'testo'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            return ['success' => false, 'error' => "Campo obbligatorio mancante: $field"];
        }
    }
    
    try {
        // Verifica che l'utente sia creatore/admin del progetto
        $stmt = $db->prepare(
            SELECT c.progetto_id, p.creatore_id
            FROM commenti c
            JOIN progetti p ON c.progetto_id = p.id
            WHERE c.id = ?
        );
        $stmt->execute([$input['commento_id']]);
        $commento = $stmt->fetch();
        
        if (!$commento) {
            return ['success' => false, 'error' => 'Commento non trovato'];
        }
        
        if ($commento['creatore_id'] != $roleManager->getUserId() && !$roleManager->isAdmin()) {
            return ['success' => false, 'error' => 'Solo il creatore può rispondere ai commenti'];
        }
        
        // Usa stored procedure per inserimento risposta
        $stmt = $db->prepare("CALL sp_rispondi_commento(?, ?, ?)");
        $stmt->execute([$roleManager->getUserId(), $input['commento_id'], $input['testo']]);
        
        return [
            'success' => true, 
            'data' => ['message' => 'Risposta inserita con successo'],
            'message' => 'Risposta inserita con successo'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function modificaCommento($commentoId, $testo, $roleManager) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Verifica permessi
        $stmt = $db->prepare("SELECT utente_id FROM commenti WHERE id = ?");
        $stmt->execute([$commentoId]);
        $commento = $stmt->fetch();
        
        if (!$commento) {
            return ['success' => false, 'error' => 'Commento non trovato'];
        }
        
        if ($commento['utente_id'] != $roleManager->getUserId() && !$roleManager->isAdmin()) {
            return ['success' => false, 'error' => 'Accesso negato'];
        }
        
        // Aggiorna commento
        $stmt = $db->prepare("UPDATE commenti SET testo = ? WHERE id = ?");
        $stmt->execute([$testo, $commentoId]);
        
        return ['success' => true, 'data' => ['testo' => $testo]];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function cancellaCommento($commentoId, $roleManager) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Verifica permessi
        $stmt = $db->prepare("SELECT utente_id FROM commenti WHERE id = ?");
        $stmt->execute([$commentoId]);
        $commento = $stmt->fetch();
        
        if (!$commento) {
            return ['success' => false, 'error' => 'Commento non trovato'];
        }
        
        if ($commento['utente_id'] != $roleManager->getUserId() && !$roleManager->isAdmin()) {
            return ['success' => false, 'error' => 'Accesso negato'];
        }
        
        // Cancella commento (le risposte vengono cancellate in cascata)
        $stmt = $db->prepare("DELETE FROM commenti WHERE id = ?");
        $stmt->execute([$commentoId]);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>