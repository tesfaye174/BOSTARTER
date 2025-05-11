<?php

namespace BOSTARTER\Backend\Controllers;

use BOSTARTER\Backend\Models\RewardModel;
use BOSTARTER\Backend\Models\ProjectModel; // Potrebbe servire per verificare l'esistenza del progetto
use BOSTARTER\Backend\Router; // Per usare jsonResponse
use BOSTARTER\Backend\Utils\Auth; // Per controlli autenticazione/permessi
use BOSTARTER\Backend\Utils\Logger; // Per logging

class RewardController {
    private $rewardModel;
    private $projectModel; // Aggiunto per controlli

    public function __construct() {
        $this->rewardModel = new RewardModel();
        $this->projectModel = new ProjectModel(); // Istanzia ProjectModel
        $this->logger = new Logger('RewardController');
    }

    /**
     * Gestisce la richiesta GET /api/projects/{projectId}/rewards
     * Restituisce tutte le ricompense per un progetto specifico.
     * @param array $params Parametri dall'URL (es. ['projectId' => 123])
     */
    public function getProjectRewards(array $params) {
        $projectId = isset($params['projectId']) ? filter_var($params['projectId'], FILTER_VALIDATE_INT) : null;
        if (!$projectId) {
            $this->logger->warning('ID progetto mancante o non valido', ['projectId' => $projectId]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'ID progetto mancante o non valido',
                'code' => 'INVALID_PROJECT_ID'
            ], 400);
            return;
        }
        try {
            $project = $this->projectModel->findById($projectId);
            if (!$project) {
                $this->logger->warning('Progetto non trovato', ['projectId' => $projectId]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Progetto non trovato',
                    'code' => 'PROJECT_NOT_FOUND'
                ], 404);
                return;
            }
            $rewards = $this->rewardModel->findByProjectId($projectId);
            $this->logger->info('Ricompense recuperate', ['projectId' => $projectId, 'count' => count($rewards)]);
            Router::jsonResponse([
                'success' => true,
                'rewards' => $rewards
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Errore nel recupero delle ricompense', ['error' => $e->getMessage()]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Errore nel recupero delle ricompense.',
                'code' => 'REWARD_FETCH_ERROR',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestisce la richiesta POST /api/projects/{projectId}/rewards
     * Crea una nuova ricompensa per un progetto (richiede autenticazione e autorizzazione, es. creatore del progetto).
     * @param array $params Parametri dall'URL (es. ['projectId' => 123])
     */
    public function createReward(array $params) {
        $projectId = isset($params['projectId']) ? filter_var($params['projectId'], FILTER_VALIDATE_INT) : null;
        if (!$projectId) {
            $this->logger->warning('ID progetto mancante o non valido', ['projectId' => $projectId]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'ID progetto mancante o non valido',
                'code' => 'INVALID_PROJECT_ID'
            ], 400);
            return;
        }
        if (!Auth::isLoggedIn()) {
            $this->logger->warning('Tentativo di creazione ricompensa senza autenticazione', ['projectId' => $projectId]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Autenticazione richiesta.',
                'code' => 'AUTH_REQUIRED'
            ], 401);
            return;
        }
        if (!Auth::isProjectCreator($projectId)) {
            $this->logger->warning('Tentativo non autorizzato di creazione ricompensa', ['projectId' => $projectId, 'userId' => Auth::getUserId()]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Non autorizzato a creare ricompense per questo progetto',
                'code' => 'NOT_AUTHORIZED'
            ], 403);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $data = is_array($data) ? array_map('trim', $data) : [];
        if (empty($data['title']) || empty($data['description']) || !isset($data['min_amount']) || !is_numeric($data['min_amount'])) {
            $this->logger->warning('Dati ricompensa mancanti o non validi', ['data' => $data]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Dati ricompensa mancanti o non validi (title, description, min_amount)',
                'code' => 'INVALID_REWARD_DATA'
            ], 400);
            return;
        }
        $quantity_limit = isset($data['quantity_limit']) && is_numeric($data['quantity_limit']) ? (int)$data['quantity_limit'] : null;
        try {
            $project = $this->projectModel->findById($projectId);
            if (!$project) {
                $this->logger->warning('Progetto non trovato', ['projectId' => $projectId]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Progetto non trovato',
                    'code' => 'PROJECT_NOT_FOUND'
                ], 404);
                return;
            }
            $rewardId = $this->rewardModel->create(
                $projectId,
                htmlspecialchars($data['title']),
                htmlspecialchars($data['description']),
                (float)$data['min_amount'],
                $quantity_limit
            );
            if ($rewardId) {
                $this->logger->info('Ricompensa creata con successo', ['rewardId' => $rewardId, 'projectId' => $projectId]);
                Router::jsonResponse([
                    'success' => true,
                    'message' => 'Ricompensa creata con successo',
                    'reward_id' => $rewardId
                ], 201);
            } else {
                $this->logger->error('Errore nella creazione della ricompensa', ['projectId' => $projectId]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Errore nella creazione della ricompensa',
                    'code' => 'REWARD_CREATE_ERROR'
                ], 500);
            }
        } catch (\Exception $e) {
            $this->logger->error('Errore del server nella creazione ricompensa', ['error' => $e->getMessage()]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Errore del server.',
                'code' => 'SERVER_ERROR',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Potresti aggiungere altri metodi per GET /api/rewards/{id}, PUT /api/rewards/{id}, DELETE /api/rewards/{id}
    /**
     * Gestisce la richiesta GET /api/rewards/{id}
     * Restituisce una singola ricompensa.
     * @param array $params Parametri dall'URL (es. ['id' => 123])
     */
    public function getReward(array $params) {
        $rewardId = $params['id'] ?? null;
        if (!$rewardId || !is_numeric($rewardId)) {
            $this->logger->warning('ID ricompensa mancante o non valido', ['rewardId' => $rewardId]);
            Router::jsonResponse(['error' => 'ID ricompensa mancante o non valido'], 400);
            return;
        }
        try {
            $reward = $this->rewardModel->findById((int)$rewardId);
            if (!$reward) {
                $this->logger->info('Ricompensa non trovata', ['rewardId' => $rewardId]);
                Router::jsonResponse(['error' => 'Ricompensa non trovata'], 404);
                return;
            }
            $this->logger->info('Ricompensa recuperata', ['rewardId' => $rewardId]);
            Router::jsonResponse(['success' => true, 'reward' => $reward]);
        } catch (\Exception $e) {
            $this->logger->error('Errore nel recupero della ricompensa', ['error' => $e->getMessage()]);
            Router::jsonResponse(['error' => 'Errore nel recupero della ricompensa.'], 500);
        }
    }

    /**
     * Gestisce la richiesta PUT /api/rewards/{id}
     * Modifica una ricompensa (solo creatore progetto).
     * @param array $params Parametri dall'URL (es. ['id' => 123])
     */
    public function updateReward(array $params) {
        $rewardId = isset($params['id']) ? filter_var($params['id'], FILTER_VALIDATE_INT) : null;
        if (!$rewardId) {
            $this->logger->warning('ID ricompensa mancante o non valido', ['rewardId' => $rewardId]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'ID ricompensa mancante o non valido',
                'code' => 'INVALID_REWARD_ID'
            ], 400);
            return;
        }
        if (!Auth::isLoggedIn()) {
            $this->logger->warning('Tentativo di modifica ricompensa senza autenticazione', ['rewardId' => $rewardId]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Autenticazione richiesta.',
                'code' => 'AUTH_REQUIRED'
            ], 401);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $data = is_array($data) ? array_map('trim', $data) : [];
        if (empty($data['title']) || empty($data['description']) || !isset($data['min_amount']) || !is_numeric($data['min_amount'])) {
            $this->logger->warning('Dati ricompensa mancanti o non validi', ['data' => $data]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Dati ricompensa mancanti o non validi (title, description, min_amount)',
                'code' => 'INVALID_REWARD_DATA'
            ], 400);
            return;
        }
        $quantity_limit = isset($data['quantity_limit']) && is_numeric($data['quantity_limit']) ? (int)$data['quantity_limit'] : null;
        try {
            $reward = $this->rewardModel->findById($rewardId);
            if (!$reward) {
                $this->logger->info('Ricompensa non trovata', ['rewardId' => $rewardId]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Ricompensa non trovata',
                    'code' => 'REWARD_NOT_FOUND'
                ], 404);
                return;
            }
            $project = $this->projectModel->findById($reward['project_id']);
            if (!$project) {
                $this->logger->warning('Progetto associato non trovato', ['projectId' => $reward['project_id']]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Progetto associato non trovato',
                    'code' => 'PROJECT_NOT_FOUND'
                ], 404);
                return;
            }
            if (!Auth::isProjectCreator($project['id'])) {
                $this->logger->warning('Tentativo non autorizzato di modifica ricompensa', ['projectId' => $project['id'], 'userId' => Auth::getUserId()]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Non autorizzato a modificare questa ricompensa',
                    'code' => 'NOT_AUTHORIZED'
                ], 403);
                return;
            }
            $success = $this->rewardModel->update($rewardId, htmlspecialchars($data['title']), htmlspecialchars($data['description']), (float)$data['min_amount'], $quantity_limit);
            if ($success) {
                $this->logger->info('Ricompensa aggiornata con successo', ['rewardId' => $rewardId]);
                Router::jsonResponse([
                    'success' => true,
                    'message' => 'Ricompensa aggiornata con successo'
                ]);
            } else {
                $this->logger->error('Errore nell\'aggiornamento della ricompensa', ['rewardId' => $rewardId]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Errore nell\'aggiornamento della ricompensa',
                    'code' => 'REWARD_UPDATE_ERROR'
                ], 500);
            }
        } catch (\Exception $e) {
            $this->logger->error('Errore del server nell\'aggiornamento ricompensa', ['error' => $e->getMessage()]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Errore del server.',
                'code' => 'SERVER_ERROR',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestisce la richiesta DELETE /api/rewards/{id}
     * Elimina una ricompensa (solo creatore progetto).
     * @param array $params Parametri dall'URL (es. ['id' => 123])
     */
    public function deleteReward(array $params) {
        $rewardId = isset($params['id']) ? filter_var($params['id'], FILTER_VALIDATE_INT) : null;
        if (!$rewardId) {
            $this->logger->warning('ID ricompensa mancante o non valido', ['rewardId' => $rewardId]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'ID ricompensa mancante o non valido',
                'code' => 'INVALID_REWARD_ID'
            ], 400);
            return;
        }
        if (!Auth::isLoggedIn()) {
            $this->logger->warning('Tentativo di eliminazione ricompensa senza autenticazione', ['rewardId' => $rewardId]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Autenticazione richiesta.',
                'code' => 'AUTH_REQUIRED'
            ], 401);
            return;
        }
        try {
            $reward = $this->rewardModel->findById($rewardId);
            if (!$reward) {
                $this->logger->info('Ricompensa non trovata', ['rewardId' => $rewardId]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Ricompensa non trovata',
                    'code' => 'REWARD_NOT_FOUND'
                ], 404);
                return;
            }
            $project = $this->projectModel->findById($reward['project_id']);
            if (!$project) {
                $this->logger->warning('Progetto associato non trovato', ['projectId' => $reward['project_id']]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Progetto associato non trovato',
                    'code' => 'PROJECT_NOT_FOUND'
                ], 404);
                return;
            }
            if (!Auth::isProjectCreator($project['id'])) {
                $this->logger->warning('Tentativo non autorizzato di eliminazione ricompensa', ['projectId' => $project['id'], 'userId' => Auth::getUserId()]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Non autorizzato a eliminare questa ricompensa',
                    'code' => 'NOT_AUTHORIZED'
                ], 403);
                return;
            }
            $success = $this->rewardModel->delete($rewardId);
            if ($success) {
                $this->logger->info('Ricompensa eliminata con successo', ['rewardId' => $rewardId]);
                Router::jsonResponse([
                    'success' => true,
                    'message' => 'Ricompensa eliminata con successo'
                ]);
            } else {
                $this->logger->error('Errore nell\'eliminazione della ricompensa', ['rewardId' => $rewardId]);
                Router::jsonResponse([
                    'success' => false,
                    'error' => 'Errore nell\'eliminazione della ricompensa',
                    'code' => 'REWARD_DELETE_ERROR'
                ], 500);
            }
        } catch (\Exception $e) {
            $this->logger->error('Errore del server nell\'eliminazione ricompensa', ['error' => $e->getMessage()]);
            Router::jsonResponse([
                'success' => false,
                'error' => 'Errore del server.',
                'code' => 'SERVER_ERROR',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}