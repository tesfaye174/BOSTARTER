<?php
session_start();
require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Lista competenze disponibili
        $competenze = getCompetenze();
        $apiResponse->sendSuccess($competenze);
        break;
        
    case 'POST':
        // Nuova competenza (solo admin con codice sicurezza)
        if (!$roleManager->isAdmin()) {
            $apiResponse->sendError('Accesso riservato agli amministratori', 403);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $apiResponse->sendError('Dati JSON non validi');
            exit();
        }
        
        $required = ['nome', 'codice_sicurezza'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $apiResponse->sendError("Campo obbligatorio mancante: $field");
                exit();
            }
        }
        
        $result = creaCompetenza($input, $roleManager->getUserId());
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], 'Competenza aggiunta con successo');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'PUT':
        // Modifica competenza (solo admin)
        if (!$roleManager->isAdmin()) {
            $apiResponse->sendError('Accesso riservato agli amministratori', 403);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || !isset($input['campo']) || !isset($input['valore'])) {
            $apiResponse->sendError('ID competenza, campo e valore richiesti');
            exit();
        }
        
        $result = modificaCompetenza($input['id'], $input['campo'], $input['valore']);
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], 'Competenza aggiornata');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'DELETE':
        // Disattiva competenza (solo admin)
        if (!$roleManager->isAdmin()) {
            $apiResponse->sendError('Accesso riservato agli amministratori', 403);
            exit();
        }
        
        $competenzaId = (int)$_GET['id'];
        $result = disattivaCompetenza($competenzaId);
        if ($result['success']) {
            $apiResponse->sendSuccess(null, 'Competenza disattivata');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    default:
        $apiResponse->sendError('Metodo non supportato', 405);
        break;
}

// Funzioni helper
function getCompetenze() {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare(
        SELECT 
            id,
            nome,
            descrizione,
            categoria,
            is_active,
            created_at
        FROM competenze 
        WHERE 1=1
        ORDER BY categoria, nome
    );
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function creaCompetenza($input, $adminId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Verifica codice sicurezza
        $stmt = $db->prepare(
            SELECT id, codice_sicurezza 
            FROM utenti 
            WHERE id = ? AND tipo_utente = 'amministratore'
        );
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        
        if (!$admin || $admin['codice_sicurezza'] !== $input['codice_sicurezza']) {
            return ['success' => false, 'error' => 'Codice di sicurezza non valido'];
        }
        
        // Verifica che il nome sia univoco
        $stmt = $db->prepare("SELECT id FROM competenze WHERE nome = ?");
        $stmt->execute([$input['nome']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Competenza già esistente'];
        }
        
        // Usa stored procedure per inserimento
        $stmt = $db->prepare("CALL sp_aggiungi_competenza(?, ?, ?, ?, ?, @success, @message)");
        $stmt->execute([
            $adminId,
            $input['codice_sicurezza'],
            $input['nome'],
            $input['descrizione'] ?? '',
            $input['categoria'] ?? 'generale'
        ]);
        
        $result = $db->query("SELECT @success as success, @message as message")->fetch();
        
        if ($result['success']) {
            return [
                'success' => true, 
                'data' => [
                    'nome' => $input['nome'],
                    'categoria' => $input['categoria'] ?? 'generale',
                    'message' => 'Competenza aggiunta con successo'
                ]
            ];
        } else {
            return ['success' => false, 'error' => $result['message']];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function modificaCompetenza($competenzaId, $campo, $valore) {
    $db = Database::getInstance()->getConnection();
    
    // Campi modificabili
    $campiPermessi = ['nome', 'descrizione', 'categoria'];
    if (!in_array($campo, $campiPermessi)) {
        return ['success' => false, 'error' => 'Campo non modificabile'];
    }
    
    try {
        // Verifica che la competenza esista
        $stmt = $db->prepare("SELECT id FROM competenze WHERE id = ? AND 1=1");
        $stmt->execute([$competenzaId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => 'Competenza non trovata'];
        }
        
        // Verifica univocità nome se modificato
        if ($campo === 'nome') {
            $stmt = $db->prepare("SELECT id FROM competenze WHERE nome = ? AND id != ?");
            $stmt->execute([$valore, $competenzaId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Nome competenza già esistente'];
            }
        }
        
        // Aggiorna campo
        $stmt = $db->prepare("UPDATE competenze SET $campo = ? WHERE id = ?");
        $stmt->execute([$valore, $competenzaId]);
        
        return ['success' => true, 'data' => [$campo => $valore]];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function disattivaCompetenza($competenzaId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Verifica che la competenza esista
        $stmt = $db->prepare("SELECT id FROM competenze WHERE id = ? AND 1=1");
        $stmt->execute([$competenzaId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => 'Competenza non trovata'];
        }
        
        // Verifica che non sia utilizzata da utenti
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM skill_utente WHERE competenza_id = ?");
        $stmt->execute([$competenzaId]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return ['success' => false, 'error' => 'Impossibile disattivare competenza utilizzata da utenti'];
        }
        
        // Verifica che non sia richiesta da profili
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM skill_profili WHERE competenza_id = ?");
        $stmt->execute([$competenzaId]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return ['success' => false, 'error' => 'Impossibile disattivare competenza richiesta da profili'];
        }
        
        // Disattiva competenza
        $stmt = $db->prepare("UPDATE competenze SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$competenzaId]);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>