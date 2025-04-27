<?php

namespace BOSTARTER\Backend\Controllers;

use BOSTARTER\Backend\Models\RewardModel;
use BOSTARTER\Backend\Models\ProjectModel; // Potrebbe servire per verificare l'esistenza del progetto

class RewardController {
    private $rewardModel;
    private $projectModel; // Aggiunto per controlli

    public function __construct() {
        $this->rewardModel = new RewardModel();
        $this->projectModel = new ProjectModel(); // Istanzia ProjectModel
    }

    /**
     * Gestisce la richiesta GET /api/projects/{projectId}/rewards
     * Restituisce tutte le ricompense per un progetto specifico.
     * @param array $params Parametri dall'URL (es. ['projectId' => 123])
     */
    public function getProjectRewards(array $params) {
        $projectId = $params['projectId'] ?? null;

        if (!$projectId || !is_numeric($projectId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID progetto mancante o non valido']);
            return;
        }

        try {
            // Verifica se il progetto esiste (opzionale ma consigliato)
            // $project = $this->projectModel->findById((int)$projectId);
            // if (!$project) {
            //     http_response_code(404);
            //     echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
            //     return;
            // }

            $rewards = $this->rewardModel->findByProjectId((int)$projectId);
            http_response_code(200);
            echo json_encode(['success' => true, 'rewards' => $rewards]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore nel recupero delle ricompense: ' . $e->getMessage()]);
        }
    }

    /**
     * Gestisce la richiesta POST /api/projects/{projectId}/rewards
     * Crea una nuova ricompensa per un progetto (richiede autenticazione e autorizzazione, es. creatore del progetto).
     * @param array $params Parametri dall'URL (es. ['projectId' => 123])
     */
    public function createReward(array $params) {
        $projectId = $params['projectId'] ?? null;

        if (!$projectId || !is_numeric($projectId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID progetto mancante o non valido']);
            return;
        }

        // Verifica autenticazione e autorizzazione (es. l'utente loggato è il creatore del progetto $projectId)
        // ... logica di controllo permessi ...
        // if (!Auth::isProjectCreator($projectId)) { // Esempio di funzione helper
        //     http_response_code(403);
        //     echo json_encode(['success' => false, 'message' => 'Non autorizzato a creare ricompense per questo progetto']);
        //     return;
        // }

        $data = json_decode(file_get_contents('php://input'), true);

        // Validazione input
        if (empty($data['title']) || empty($data['description']) || !isset($data['min_amount']) || !is_numeric($data['min_amount'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dati ricompensa mancanti o non validi (title, description, min_amount)']);
            return;
        }
        $quantity_limit = isset($data['quantity_limit']) && is_numeric($data['quantity_limit']) ? (int)$data['quantity_limit'] : null;

        try {
            // Verifica se il progetto esiste
            $project = $this->projectModel->findById((int)$projectId);
            if (!$project) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
                return;
            }

            $rewardId = $this->rewardModel->create(
                (int)$projectId,
                $data['title'],
                $data['description'],
                (float)$data['min_amount'],
                $quantity_limit
            );

            if ($rewardId) {
                http_response_code(201); // Created
                echo json_encode(['success' => true, 'message' => 'Ricompensa creata con successo', 'reward_id' => $rewardId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore nella creazione della ricompensa']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore del server: ' . $e->getMessage()]);
        }
    }

    // Potresti aggiungere altri metodi per GET /api/rewards/{id}, PUT /api/rewards/{id}, DELETE /api/rewards/{id}
}