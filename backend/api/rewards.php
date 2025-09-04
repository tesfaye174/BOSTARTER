<?php
session_start();
require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Lista rewards per progetto
        if (isset($_GET['progetto_id'])) {
            $progettoId = (int)$_GET['progetto_id'];
            $rewards = getRewardsProgetto($progettoId);
            $apiResponse->sendSuccess($rewards);
        } elseif (isset($_GET['id'])) {
            // Dettaglio singola reward
            $rewardId = (int)$_GET['id'];
            $reward = getRewardById($rewardId);
            if ($reward) {
                $apiResponse->sendSuccess($reward);
            } else {
                $apiResponse->sendError('Reward non trovata');
            }
        } else {
            $apiResponse->sendError('ID progetto o ID reward richiesto');
        }
        break;
        
    case 'POST':
        // Nuova reward (solo creatore/admin del progetto)
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Autenticazione richiesta', 401);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $apiResponse->sendError('Dati JSON non validi');
            exit();
        }
        
        $required = ['progetto_id', 'codice', 'nome', 'descrizione', 'importo_minimo'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $apiResponse->sendError("Campo obbligatorio mancante: $field");
                exit();
            }
        }
        
        $result = creaReward($input, $roleManager);
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], 'Reward creata con successo');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'PUT':
        // Modifica reward (solo creatore/admin del progetto)
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Autenticazione richiesta', 401);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || !isset($input['campo']) || !isset($input['valore'])) {
            $apiResponse->sendError('ID reward, campo e valore richiesti');
            exit();
        }
        
        $result = modificaReward($input['id'], $input['campo'], $input['valore'], $roleManager);
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], 'Reward aggiornata');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'DELETE':
        // Cancella reward (solo creatore/admin del progetto)
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Autenticazione richiesta', 401);
            exit();
        }
        
        $rewardId = (int)$_GET['id'];
        $result = cancellaReward($rewardId, $roleManager);
        if ($result['success']) {
            $apiResponse->sendSuccess(null, 'Reward cancellata');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    default:
        $apiResponse->sendError('Metodo non supportato', 405);
        break;
}

// Funzioni helper
function getRewardsProgetto($progettoId) {
    $db = Database::getInstance()->getConnection();
    
    // Verifica che il progetto esista
    $stmt = $db->prepare("SELECT id, nome FROM progetti WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$progettoId]);
    if (!$stmt->fetch()) {
        return ['error' => 'Progetto non trovato'];
    }
    
    // Recupera rewards
    $stmt = $db->prepare(
        SELECT 
            id,
            codice,
            nome,
            descrizione,
            importo_minimo,
            quantita_disponibile,
            quantita_utilizzata,
            is_active,
            created_at
        FROM rewards 
        WHERE progetto_id = ? AND is_active = TRUE
        ORDER BY importo_minimo ASC
    );
    $stmt->execute([$progettoId]);
    
    return $stmt->fetchAll();
}

function getRewardById($rewardId) {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare(
        SELECT r.*, p.nome as progetto_nome
        FROM rewards r
        JOIN progetti p ON r.progetto_id = p.id
        WHERE r.id = ?
    );
    $stmt->execute([$rewardId]);
    
    return $stmt->fetch();
}

function creaReward($input, $roleManager) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Verifica che l'utente sia creatore/admin del progetto
        $stmt = $db->prepare("SELECT creatore_id FROM progetti WHERE id = ?");
        $stmt->execute([$input['progetto_id']]);
        $progetto = $stmt->fetch();
        
        if (!$progetto) {
            return ['success' => false, 'error' => 'Progetto non trovato'];
        }
        
        if ($progetto['creatore_id'] != $roleManager->getUserId() && !$roleManager->isAdmin()) {
            return ['success' => false, 'error' => 'Solo il creatore può aggiungere rewards'];
        }
        
        // Verifica che il codice sia univoco per il progetto
        $stmt = $db->prepare("SELECT id FROM rewards WHERE codice = ? AND progetto_id = ?");
        $stmt->execute([$input['codice'], $input['progetto_id']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Codice reward già esistente per questo progetto'];
        }
        
        // Verifica importo minimo
        $importoMinimo = (float)$input['importo_minimo'];
        if ($importoMinimo <= 0) {
            return ['success' => false, 'error' => 'Importo minimo deve essere maggiore di zero'];
        }
        
        // Usa stored procedure per inserimento
        $stmt = $db->prepare("CALL sp_aggiungi_reward(?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $roleManager->getUserId(),
            $input['progetto_id'],
            $input['codice'],
            $input['nome'],
            $input['descrizione'],
            $importoMinimo,
            $input['quantita'] ?? null
        ]);
        
        return [
            'success' => true, 
            'data' => [
                'codice' => $input['codice'],
                'nome' => $input['nome'],
                'message' => 'Reward creata con successo'
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function modificaReward($rewardId, $campo, $valore, $roleManager) {
    $db = Database::getInstance()->getConnection();
    
    // Campi modificabili
    $campiPermessi = ['nome', 'descrizione', 'importo_minimo', 'quantita_disponibile', 'is_active'];
    if (!in_array($campo, $campiPermessi)) {
        return ['success' => false, 'error' => 'Campo non modificabile'];
    }
    
    try {
        // Verifica permessi
        $stmt = $db->prepare(
            SELECT r.id, p.creatore_id
            FROM rewards r
            JOIN progetti p ON r.progetto_id = p.id
            WHERE r.id = ?
        );
        $stmt->execute([$rewardId]);
        $reward = $stmt->fetch();
        
        if (!$reward) {
            return ['success' => false, 'error' => 'Reward non trovata'];
        }
        
        if ($reward['creatore_id'] != $roleManager->getUserId() && !$roleManager->isAdmin()) {
            return ['success' => false, 'error' => 'Accesso negato'];
        }
        
        // Validazioni specifiche per campo
        if ($campo === 'importo_minimo' && (float)$valore <= 0) {
            return ['success' => false, 'error' => 'Importo minimo deve essere maggiore di zero'];
        }
        
        if ($campo === 'quantita_disponibile' && $valore !== null && (int)$valore < 0) {
            return ['success' => false, 'error' => 'Quantità non può essere negativa'];
        }
        
        // Aggiorna campo
        $stmt = $db->prepare("UPDATE rewards SET $campo = ? WHERE id = ?");
        $stmt->execute([$valore, $rewardId]);
        
        return ['success' => true, 'data' => [$campo => $valore]];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function cancellaReward($rewardId, $roleManager) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Verifica permessi
        $stmt = $db->prepare(
            SELECT r.id, p.creatore_id, r.quantita_utilizzata
            FROM rewards r
            JOIN progetti p ON r.progetto_id = p.id
            WHERE r.id = ?
        );
        $stmt->execute([$rewardId]);
        $reward = $stmt->fetch();
        
        if (!$reward) {
            return ['success' => false, 'error' => 'Reward non trovata'];
        }
        
        if ($reward['creatore_id'] != $roleManager->getUserId() && !$roleManager->isAdmin()) {
            return ['success' => false, 'error' => 'Accesso negato'];
        }
        
        // Verifica che non sia stata utilizzata
        if ($reward['quantita_utilizzata'] > 0) {
            return ['success' => false, 'error' => 'Impossibile cancellare reward già utilizzata'];
        }
        
        // Disattiva invece di cancellare (soft delete)
        $stmt = $db->prepare("UPDATE rewards SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$rewardId]);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>