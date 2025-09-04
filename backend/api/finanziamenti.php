<?php
session_start();
require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Lista finanziamenti per progetto o utente
        if (isset($_GET['progetto_id'])) {
            $progettoId = (int)$_GET['progetto_id'];
            $finanziamenti = getFinanziamentiProgetto($progettoId);
            $apiResponse->sendSuccess($finanziamenti);
        } elseif (isset($_GET['utente_id'])) {
            // Finanziamenti di un utente specifico
            $utenteId = (int)$_GET['utente_id'];
            if ($roleManager->getUserId() != $utenteId && !$roleManager->isAdmin()) {
                $apiResponse->sendError('Accesso negato', 403);
                exit();
            }
            $finanziamenti = getFinanziamentiUtente($utenteId);
            $apiResponse->sendSuccess($finanziamenti);
        } else {
            // Tutti i finanziamenti dell'utente corrente
            if (!$roleManager->isAuthenticated()) {
                $apiResponse->sendError('Autenticazione richiesta', 401);
                exit();
            }
            $finanziamenti = getFinanziamentiUtente($roleManager->getUserId());
            $apiResponse->sendSuccess($finanziamenti);
        }
        break;
        
    case 'POST':
        // Nuovo finanziamento
        if (!$roleManager->isAuthenticated()) {
            $apiResponse->sendError('Autenticazione richiesta', 401);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $apiResponse->sendError('Dati JSON non validi');
            exit();
        }
        
        $required = ['progetto_id', 'importo'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $apiResponse->sendError("Campo obbligatorio mancante: $field");
                exit();
            }
        }
        
        $result = creaFinanziamento($input, $roleManager->getUserId());
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], 'Finanziamento completato con successo');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    case 'PUT':
        // Aggiorna stato finanziamento (solo admin)
        if (!$roleManager->isAdmin()) {
            $apiResponse->sendError('Accesso negato', 403);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['finanziamento_id']) || !isset($input['stato_pagamento'])) {
            $apiResponse->sendError('ID finanziamento e stato richiesti');
            exit();
        }
        
        $result = aggiornaStatoFinanziamento($input['finanziamento_id'], $input['stato_pagamento']);
        if ($result['success']) {
            $apiResponse->sendSuccess($result['data'], 'Stato finanziamento aggiornato');
        } else {
            $apiResponse->sendError($result['error']);
        }
        break;
        
    default:
        $apiResponse->sendError('Metodo non supportato', 405);
        break;
}

// Funzioni helper
function getFinanziamentiProgetto($progettoId) {
    $db = Database::getInstance()->getConnection();
    
    // Verifica che il progetto esista
    $stmt = $db->prepare("SELECT id, nome FROM progetti WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$progettoId]);
    if (!$stmt->fetch()) {
        return ['error' => 'Progetto non trovato'];
    }
    
    // Recupera finanziamenti con dettagli utente e reward
    $stmt = $db->prepare(
        SELECT 
            f.id,
            f.importo,
            f.data_finanziamento,
            f.stato_pagamento,
            f.messaggio_supporto,
            u.nickname,
            u.nome,
            u.cognome,
            r.codice as reward_codice,
            r.nome as reward_nome,
            r.descrizione as reward_descrizione
        FROM finanziamenti f
        JOIN utenti u ON f.utente_id = u.id
        LEFT JOIN rewards r ON f.reward_id = r.id
        WHERE f.progetto_id = ? AND f.stato_pagamento = 'completed'
        ORDER BY f.data_finanziamento DESC
    );
    $stmt->execute([$progettoId]);
    
    return $stmt->fetchAll();
}

function getFinanziamentiUtente($utenteId) {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare(
        SELECT 
            f.id,
            f.importo,
            f.data_finanziamento,
            f.stato_pagamento,
            f.messaggio_supporto,
            p.nome as progetto_nome,
            p.tipo as progetto_tipo,
            r.codice as reward_codice,
            r.nome as reward_nome
        FROM finanziamenti f
        JOIN progetti p ON f.progetto_id = p.id
        LEFT JOIN rewards r ON f.reward_id = r.id
        WHERE f.utente_id = ?
        ORDER BY f.data_finanziamento DESC
    );
    $stmt->execute([$utenteId]);
    
    return $stmt->fetchAll();
}

function creaFinanziamento($input, $utenteId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Verifica che il progetto esista e sia aperto
        $stmt = $db->prepare("SELECT id, stato, budget_richiesto, budget_raccolto FROM progetti WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$input['progetto_id']]);
        $progetto = $stmt->fetch();
        
        if (!$progetto) {
            return ['success' => false, 'error' => 'Progetto non trovato'];
        }
        
        if ($progetto['stato'] !== 'aperto') {
            return ['success' => false, 'error' => 'Progetto non aperto ai finanziamenti'];
        }
        
        // Verifica importo
        $importo = (float)$input['importo'];
        if ($importo <= 0) {
            return ['success' => false, 'error' => 'Importo deve essere maggiore di zero'];
        }
        
        // Verifica reward se specificato
        $rewardId = $input['reward_id'] ?? null;
        if ($rewardId) {
            $stmt = $db->prepare(
                SELECT id, importo_minimo, quantita_disponibile, quantita_utilizzata 
                FROM rewards 
                WHERE id = ? AND progetto_id = ? AND is_active = TRUE
            );
            $stmt->execute([$rewardId, $input['progetto_id']]);
            $reward = $stmt->fetch();
            
            if (!$reward) {
                return ['success' => false, 'error' => 'Reward non trovato o non disponibile'];
            }
            
            if ($importo < $reward['importo_minimo']) {
                return ['success' => false, 'error' => "Importo insufficiente per questa reward (minimo: €{$reward['importo_minimo']})"];
            }
            
            if ($reward['quantita_disponibile'] !== null && $reward['quantita_utilizzata'] >= $reward['quantita_disponibile']) {
                return ['success' => false, 'error' => 'Reward esaurita'];
            }
        }
        
        // Usa stored procedure per inserimento
        $stmt = $db->prepare("CALL sp_finanzia_progetto(?, ?, ?, ?, @finanziamento_id)");
        $stmt->execute([$utenteId, $input['progetto_id'], $rewardId, $importo]);
        
        $result = $db->query("SELECT @finanziamento_id as finanziamento_id")->fetch();
        $finanziamentoId = $result['finanziamento_id'];
        
        // Aggiorna messaggio supporto se presente
        if (!empty($input['messaggio_supporto'])) {
            $stmt = $db->prepare("UPDATE finanziamenti SET messaggio_supporto = ? WHERE id = ?");
            $stmt->execute([$input['messaggio_supporto'], $finanziamentoId]);
        }
        
        return [
            'success' => true, 
            'data' => [
                'finanziamento_id' => $finanziamentoId,
                'importo' => $importo,
                'message' => 'Finanziamento completato con successo'
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function aggiornaStatoFinanziamento($finanziamentoId, $stato) {
    $db = Database::getInstance()->getConnection();
    
    if (!in_array($stato, ['pending', 'completed', 'failed', 'refunded'])) {
        return ['success' => false, 'error' => 'Stato non valido'];
    }
    
    try {
        // Aggiorna stato
        $stmt = $db->prepare("UPDATE finanziamenti SET stato_pagamento = ? WHERE id = ?");
        $stmt->execute([$stato, $finanziamentoId]);
        
        return ['success' => true, 'data' => ['stato' => $stato]];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>