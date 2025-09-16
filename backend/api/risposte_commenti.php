<?php
/**
 * BOSTARTER - API Risposte Commenti
 *
 * Gestisce le risposte del creatore ai commenti sui progetti.
 * Permette al creatore di rispondere a ogni commento (max 1 risposta).
 */

session_start();
require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        // Aggiungi risposta a commento
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['commento_id']) || !isset($data['testo'])) {
            $apiResponse->sendError('Dati mancanti');
        }

        $commento_id = intval($data['commento_id']);
        $testo = trim($data['testo']);

        if (empty($testo)) {
            $apiResponse->sendError('Testo risposta obbligatorio');
        }

        if (strlen($testo) > 1000) {
            $apiResponse->sendError('Testo troppo lungo (max 1000 caratteri)');
        }

        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            // Verifica che l'utente sia autenticato
            if (!isset($_SESSION['user_id'])) {
                $apiResponse->sendError('Utente non autenticato');
            }

            $user_id = $_SESSION['user_id'];

            // Verifica che il commento esista e ottieni il progetto
            $stmt = $conn->prepare("
                SELECT c.progetto_id, p.creatore_id
                FROM commenti c
                JOIN progetti p ON c.progetto_id = p.id
                WHERE c.id = ?
            ");
            $stmt->execute([$commento_id]);
            $comment_info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment_info) {
                $apiResponse->sendError('Commento non trovato');
            }

            // Verifica che l'utente sia il creatore del progetto
            if ($comment_info['creatore_id'] != $user_id) {
                $apiResponse->sendError('Solo il creatore può rispondere ai commenti');
            }

            // Verifica che non esista già una risposta
            $stmt = $conn->prepare("SELECT id FROM risposte_commenti WHERE commento_id = ?");
            $stmt->execute([$commento_id]);
            if ($stmt->fetch()) {
                $apiResponse->sendError('Risposta già presente per questo commento');
            }

            // Inserisci risposta usando stored procedure
            $stmt = $conn->prepare("CALL inserisci_risposta_commento(?, ?)");
            $stmt->execute([$commento_id, $testo]);

            // Log dell'evento
            require_once __DIR__ . '/../services/MongoLogger.php';
            BOSTARTER_Audit::logEvent('COMMENT_RESPONSE', 'Risposta aggiunta a commento', [
                'commento_id' => $commento_id,
                'progetto_id' => $comment_info['progetto_id']
            ], $user_id);

            $apiResponse->sendSuccess(['message' => 'Risposta aggiunta con successo']);

        } catch (Exception $e) {
            error_log('Errore risposta commento: ' . $e->getMessage());
            $apiResponse->sendError('Errore interno del server');
        }
        break;

    case 'GET':
        // Recupera risposte per commenti
        $comment_ids = $_GET['comment_ids'] ?? '';

        if (empty($comment_ids)) {
            $apiResponse->sendError('ID commenti mancanti');
        }

        $ids = explode(',', $comment_ids);
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);

        if (empty($ids)) {
            $apiResponse->sendError('ID commenti non validi');
        }

        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            // Recupera risposte per i commenti specificati
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $conn->prepare("
                SELECT rc.*, u.nickname as creatore_nickname
                FROM risposte_commenti rc
                JOIN commenti c ON rc.commento_id = c.id
                JOIN progetti p ON c.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                WHERE rc.commento_id IN ($placeholders)
                ORDER BY rc.data_risposta DESC
            ");
            $stmt->execute($ids);
            $risposte = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $apiResponse->sendSuccess($risposte);

        } catch (Exception $e) {
            error_log('Errore recupero risposte: ' . $e->getMessage());
            $apiResponse->sendError('Errore interno del server');
        }
        break;

    case 'DELETE':
        // Rimuovi risposta (solo dal creatore)
        $commento_id = intval($_GET['commento_id'] ?? 0);

        if ($commento_id <= 0) {
            $apiResponse->sendError('ID commento non valido');
        }

        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            // Verifica autenticazione
            if (!isset($_SESSION['user_id'])) {
                $apiResponse->sendError('Utente non autenticato');
            }

            $user_id = $_SESSION['user_id'];

            // Verifica che l'utente sia il creatore del progetto
            $stmt = $conn->prepare("
                SELECT p.creatore_id
                FROM risposte_commenti rc
                JOIN commenti c ON rc.commento_id = c.id
                JOIN progetti p ON c.progetto_id = p.id
                WHERE rc.commento_id = ?
            ");
            $stmt->execute([$commento_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                $apiResponse->sendError('Risposta non trovata');
            }

            if ($result['creatore_id'] != $user_id) {
                $apiResponse->sendError('Solo il creatore può rimuovere la risposta');
            }

            // Rimuovi risposta
            $stmt = $conn->prepare("DELETE FROM risposte_commenti WHERE commento_id = ?");
            $stmt->execute([$commento_id]);

            // Log dell'evento
            require_once __DIR__ . '/../services/MongoLogger.php';
            BOSTARTER_Audit::logEvent('COMMENT_RESPONSE_REMOVED', 'Risposta rimossa da commento', [
                'commento_id' => $commento_id
            ], $user_id);

            $apiResponse->sendSuccess(['message' => 'Risposta rimossa con successo']);

        } catch (Exception $e) {
            error_log('Errore rimozione risposta: ' . $e->getMessage());
            $apiResponse->sendError('Errore interno del server');
        }
        break;

    default:
        $apiResponse->sendError('Metodo non supportato');
        break;
}
?>
