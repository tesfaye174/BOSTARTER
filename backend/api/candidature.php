<?php
session_start();
require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();

// Verifica autenticazione
if (!$roleManager->isAuthenticated()) {
    $apiResponse->sendError('Autenticazione richiesta', 401);
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Lista candidature per profilo o utente
        if (isset($_GET['profilo_id'])) {
            // Candidature per un profilo specifico (solo creatore/admin del progetto)
            $profiloId = (int)$_GET['profilo_id'];
            $candidature = getCandidaturePerProfilo($profiloId, $roleManager);
            $apiResponse->sendSuccess($candidature);
        } elseif (isset($_GET['utente_id'])) {
            // Candidature di un utente specifico
            $utenteId = (int)$_GET['utente_id'];
            if ($roleManager->getUserId() != $utenteId && !$roleManager->isAdmin()) {
                $apiResponse->sendError('Accesso negato', 403);
                exit();
            }
            $candidature = getCandidatureUtente($utenteId);
            $apiResponse->sendSuccess($candidature);
        } else {
            // Tutte le candidature dell'utente corrente
            $candidature = getCandidatureUtente($roleManager->getUserId());
            $apiResponse->sendSuccess($candidature);
        }
        break;
        
    case 'POST':
        // Nuova candidatura
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $apiResponse->sendError('Dati JSON non validi');
            exit();
        }
        
        $required = ['profilo_id', 'motivazione'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $apiResponse->sendError("Campo obbligatorio mancante: $field");
                exit();
            }
        }
        
        $result = creaCandidatura($input, $roleManager->getUserId());
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], 'Candidatura inviata con successo');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'PUT':
        // Aggiorna stato candidatura (solo creatore/admin)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['candidatura_id']) || !isset($input['stato'])) {
            $apiResponse->sendError('ID candidatura e stato richiesti');
            exit();
        }
        
        $result = aggiornaStatoCandidatura($input['candidatura_id'], $input['stato'], $roleManager);
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], 'Stato candidatura aggiornato');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'DELETE':
        // Cancella candidatura (solo proprietario o admin)
        $candidaturaId = (int)$_GET['id'];
        $result = cancellaCandidatura($candidaturaId, $roleManager);
        if ($result['success']) {
            $apiResponse->sendSuccess(null, 'Candidatura cancellata');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    default:
        $apiResponse->sendError('Metodo non supportato', 405);
        break;
}

// Funzioni helper
function getCandidaturePerProfilo($profiloId, $roleManager) {
    $db = Database::getInstance()->getConnection();
    
    // Verifica che l'utente sia creatore/admin del progetto
    $stmt = $db->prepare(
        SELECT p.creatore_id, p.nome as progetto_nome
        FROM profili_richiesti pr
        JOIN progetti p ON pr.progetto_id = p.id
        WHERE pr.id = ?
    );
    $stmt->execute([$profiloId]);
    $progetto = $stmt->fetch();
    
    if (!$progetto) {
        return ['error' => 'Profilo non trovato'];
    }
    
    if ($progetto['creatore_id'] != $roleManager->getUserId() && !$roleManager->isAdmin()) {
        return ['error' => 'Accesso negato'];
    }
    
    // Recupera candidature
    $stmt = $db->prepare(
        SELECT c.*, u.nickname, u.nome, u.cognome, pr.nome as profilo_nome
        FROM candidature c
        JOIN utenti u ON c.utente_id = u.id
        JOIN profili_richiesti pr ON c.profilo_id = pr.id
        WHERE c.profilo_id = ?
        ORDER BY c.data_candidatura DESC
    );
    $stmt->execute([$profiloId]);
    
    return $stmt->fetchAll();
}

function getCandidatureUtente($utenteId) {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare(
        SELECT c.*, pr.nome as profilo_nome, p.nome as progetto_nome, p.tipo as progetto_tipo
        FROM candidature c
        JOIN profili_richiesti pr ON c.profilo_id = pr.id
        JOIN progetti p ON pr.progetto_id = p.id
        WHERE c.utente_id = ?
        ORDER BY c.data_candidatura DESC
    );
    $stmt->execute([$utenteId]);
    
    return $stmt->fetchAll();
}

function creaCandidatura($input, $utenteId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Verifica che il profilo esista e sia per progetto software
        $stmt = $db->prepare(
            SELECT pr.*, p.tipo, p.stato
            FROM profili_richiesti pr
            JOIN progetti p ON pr.progetto_id = p.id
            WHERE pr.id = ? AND pr.is_active = TRUE
        );
        $stmt->execute([$input['profilo_id']]);
        $profilo = $stmt->fetch();
        
        if (!$profilo) {
            return ['success' => false, 'error' => 'Profilo non trovato o non attivo'];
        }
        
        if ($profilo['tipo'] !== 'software') {
            return ['success' => false, 'error' => 'Candidature solo per progetti software'];
        }
        
        if ($profilo['stato'] !== 'aperto') {
            return ['success' => false, 'error' => 'Progetto non aperto alle candidature'];
        }
        
        // Verifica che non ci sia già una candidatura
        $stmt = $db->prepare("SELECT id FROM candidature WHERE utente_id = ? AND profilo_id = ?");
        $stmt->execute([$utenteId, $input['profilo_id']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Candidatura già inviata per questo profilo'];
        }
        
        // Usa stored procedure per verifica skill e inserimento
        $stmt = $db->prepare("CALL sp_candidati_profilo(?, ?, ?)");
        $stmt->execute([$utenteId, $input['profilo_id'], $input['motivazione']]);
        
        return ['success' => true, 'data' => ['message' => 'Candidatura inviata con successo']];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function aggiornaStatoCandidatura($candidaturaId, $stato, $roleManager) {
    $db = Database::getInstance()->getConnection();
    
    if (!in_array($stato, ['accettata', 'rifiutata', 'in_valutazione'])) {
        return ['success' => false, 'error' => 'Stato non valido'];
    }
    
    try {
        // Verifica permessi
        $stmt = $db->prepare(
            SELECT c.*, p.creatore_id, pr.numero_posizioni, pr.posizioni_occupate
            FROM candidature c
            JOIN profili_richiesti pr ON c.profilo_id = pr.id
            JOIN progetti p ON pr.progetto_id = p.id
            WHERE c.id = ?
        );
        $stmt->execute([$candidaturaId]);
        $candidatura = $stmt->fetch();
        
        if (!$candidatura) {
            return ['success' => false, 'error' => 'Candidatura non trovata'];
        }
        
        if ($candidatura['creatore_id'] != $roleManager->getUserId() && !$roleManager->isAdmin()) {
            return ['success' => false, 'error' => 'Accesso negato'];
        }
        
        // Aggiorna stato
        $stmt = $db->prepare(
            UPDATE candidature 
            SET stato = ?, data_valutazione = NOW(), valutatore_id = ?
            WHERE id = ?
        );
        $stmt->execute([$stato, $roleManager->getUserId(), $candidaturaId]);
        
        // Se accettata, aggiorna posizioni occupate
        if ($stato === 'accettata') {
            $stmt = $db->prepare(
                UPDATE profili_richiesti 
                SET posizioni_occupate = posizioni_occupate + 1
                WHERE id = ?
            );
            $stmt->execute([$candidatura['profilo_id']]);
        }
        
        return ['success' => true, 'data' => ['stato' => $stato]];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function cancellaCandidatura($candidaturaId, $roleManager) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Verifica permessi
        $stmt = $db->prepare("SELECT utente_id FROM candidature WHERE id = ?");
        $stmt->execute([$candidaturaId]);
        $candidatura = $stmt->fetch();
        
        if (!$candidatura) {
            return ['success' => false, 'error' => 'Candidatura non trovata'];
        }
        
        if ($candidatura['utente_id'] != $roleManager->getUserId() && !$roleManager->isAdmin()) {
            return ['success' => false, 'error' => 'Accesso negato'];
        }
        
        $stmt = $db->prepare("DELETE FROM candidature WHERE id = ?");
        $stmt->execute([$candidaturaId]);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>