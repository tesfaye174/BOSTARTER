<?php

namespace BOSTARTER\Backend\Controllers;

use BOSTARTER\Backend\Models\ApplicationModel;
use BOSTARTER\Backend\Models\ProjectModel; // Per verificare esistenza progetto e permessi
use BOSTARTER\Backend\Utils\Auth; // Per verificare autenticazione e ottenere user ID

class ApplicationController {
    private $applicationModel;
    private $projectModel;

    public function __construct() {
        $this->applicationModel = new ApplicationModel();
        $this->projectModel = new ProjectModel();
    }

    /**
     * Gestisce la richiesta GET /api/projects/{projectId}/applications
     * Restituisce tutte le candidature per un progetto (solo per il creatore del progetto).
     * @param array $params Parametri dall'URL (es. ['projectId' => 123])
     */
    public function getProjectApplications(array $params) {
        $projectId = $params['projectId'] ?? null;

        if (!$projectId || !is_numeric($projectId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID progetto mancante o non valido']);
            return;
        }

        // Verifica autenticazione e autorizzazione (solo il creatore può vedere le candidature)
        $userId = Auth::getUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Autenticazione richiesta']);
            return;
        }

        try {
            $project = $this->projectModel->findById((int)$projectId);
            if (!$project) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
                return;
            }

            // Verifica se l'utente loggato è il creatore del progetto
            if ($project['creator_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Non autorizzato a visualizzare le candidature per questo progetto']);
                return;
            }

            $applications = $this->applicationModel->findByProjectId((int)$projectId);
            http_response_code(200);
            echo json_encode(['success' => true, 'applications' => $applications]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore nel recupero delle candidature: ' . $e->getMessage()]);
        }
    }

    /**
     * Gestisce la richiesta GET /api/user/applications
     * Restituisce tutte le candidature inviate dall'utente loggato.
     */
    public function getUserApplications() {
        $userId = Auth::getUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Autenticazione richiesta']);
            return;
        }

        try {
            $applications = $this->applicationModel->findByUserId($userId);
            http_response_code(200);
            echo json_encode(['success' => true, 'applications' => $applications]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore nel recupero delle candidature utente: ' . $e->getMessage()]);
        }
    }

    /**
     * Gestisce la richiesta POST /api/projects/{projectId}/applications
     * Crea una nuova candidatura per un progetto (richiede autenticazione).
     * @param array $params Parametri dall'URL (es. ['projectId' => 123])
     */
    public function createApplication(array $params) {
        $projectId = $params['projectId'] ?? null;

        if (!$projectId || !is_numeric($projectId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID progetto mancante o non valido']);
            return;
        }

        $userId = Auth::getUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Autenticazione richiesta']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $message = $data['message'] ?? ''; // Messaggio opzionale

        try {
            // Verifica se il progetto esiste
            $project = $this->projectModel->findById((int)$projectId);
            if (!$project) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
                return;
            }

            // Impedisci al creatore di candidarsi al proprio progetto (opzionale)
            if ($project['creator_id'] == $userId) {
                 http_response_code(403);
                 echo json_encode(['success' => false, 'message' => 'Non puoi candidarti al tuo stesso progetto']);
                 return;
            }

            // Verifica se l'utente si è già candidato (opzionale, dipende dalle regole)
            // $existingApplication = $this->applicationModel->findByProjectAndUser($projectId, $userId);
            // if ($existingApplication) {
            //     http_response_code(409); // Conflict
            //     echo json_encode(['success' => false, 'message' => 'Ti sei già candidato a questo progetto']);
            //     return;
            // }

            $applicationId = $this->applicationModel->create((int)$projectId, $userId, $message);

            if ($applicationId) {
                http_response_code(201); // Created
                echo json_encode(['success' => true, 'message' => 'Candidatura inviata con successo', 'application_id' => $applicationId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore nell'invio della candidatura']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore del server: ' . $e->getMessage()]);
        }
    }

    /**
     * Gestisce la richiesta PUT /api/applications/{applicationId}/status
     * Aggiorna lo stato di una candidatura (solo per il creatore del progetto).
     * @param array $params Parametri dall'URL (es. ['applicationId' => 456])
     */
    public function updateApplicationStatus(array $params) {
        $applicationId = $params['applicationId'] ?? null;

        if (!$applicationId || !is_numeric($applicationId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID candidatura mancante o non valido']);
            return;
        }

        $userId = Auth::getUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Autenticazione richiesta']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $newStatus = $data['status'] ?? null;

        // Validazione nuovo stato (es. 'accepted', 'rejected')
        if (!$newStatus || !in_array($newStatus, ['accepted', 'rejected'])) { // Aggiungi altri stati se necessario
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nuovo stato mancante o non valido']);
            return;
        }

        try {
            // Trova la candidatura
            $application = $this->applicationModel->findById((int)$applicationId);
            if (!$application) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Candidatura non trovata']);
                return;
            }

            // Trova il progetto associato
            $project = $this->projectModel->findById($application['project_id']);
            if (!$project) {
                // Questo non dovrebbe accadere se i dati sono consistenti
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Progetto associato non trovato']);
                return;
            }

            // Verifica se l'utente loggato è il creatore del progetto
            if ($project['creator_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Non autorizzato a modificare lo stato di questa candidatura']);
                return;
            }

            // Aggiorna lo stato
            $success = $this->applicationModel->updateStatus((int)$applicationId, $newStatus);

            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Stato candidatura aggiornato con successo']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore nell'aggiornamento dello stato della candidatura']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore del server: ' . $e->getMessage()]);
        }
    }

    // Potresti aggiungere DELETE /api/applications/{id} (es. per l'utente che ritira la candidatura o admin)
}