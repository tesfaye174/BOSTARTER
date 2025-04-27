<?php

namespace BOSTARTER\Backend\Controllers;

use BOSTARTER\Backend\Models\CommentModel;
use BOSTARTER\Backend\Models\ProjectModel; // Per verificare esistenza progetto
use BOSTARTER\Backend\Utils\Auth; // Per verificare autenticazione

class CommentController {
    private $commentModel;
    private $projectModel;

    public function __construct() {
        $this->commentModel = new CommentModel();
        $this->projectModel = new ProjectModel();
    }

    /**
     * Gestisce la richiesta GET /api/projects/{projectId}/comments
     * Restituisce tutti i commenti per un progetto specifico.
     * @param array $params Parametri dall'URL (es. ['projectId' => 123])
     */
    public function getProjectComments(array $params) {
        $projectId = $params['projectId'] ?? null;

        if (!$projectId || !is_numeric($projectId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID progetto mancante o non valido']);
            return;
        }

        try {
            // Verifica se il progetto esiste (opzionale ma consigliato)
            $project = $this->projectModel->findById((int)$projectId);
            if (!$project) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
                return;
            }

            $comments = $this->commentModel->findByProjectId((int)$projectId);
            http_response_code(200);
            // Qui potresti voler strutturare i commenti in un albero prima di inviarli
            echo json_encode(['success' => true, 'comments' => $comments]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore nel recupero dei commenti: ' . $e->getMessage()]);
        }
    }

    /**
     * Gestisce la richiesta POST /api/projects/{projectId}/comments
     * Crea un nuovo commento per un progetto (richiede autenticazione).
     * @param array $params Parametri dall'URL (es. ['projectId' => 123])
     */
    public function createComment(array $params) {
        $projectId = $params['projectId'] ?? null;

        if (!$projectId || !is_numeric($projectId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID progetto mancante o non valido']);
            return;
        }

        // Verifica autenticazione
        $userId = Auth::getUserId(); // Esempio: Ottieni l'ID utente dalla sessione o token
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Autenticazione richiesta']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Validazione input
        if (empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Contenuto del commento mancante']);
            return;
        }
        $parentCommentId = isset($data['parent_comment_id']) && is_numeric($data['parent_comment_id']) ? (int)$data['parent_comment_id'] : null;

        try {
            // Verifica se il progetto esiste
            $project = $this->projectModel->findById((int)$projectId);
            if (!$project) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
                return;
            }

            // Se è una risposta, verifica che il commento padre esista e appartenga allo stesso progetto (opzionale)
            if ($parentCommentId) {
                $parentComment = $this->commentModel->findById($parentCommentId);
                if (!$parentComment || $parentComment['project_id'] != $projectId) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Commento padre non valido o non trovato']);
                    return;
                }
            }

            $commentId = $this->commentModel->create(
                (int)$projectId,
                $userId,
                $data['content'],
                $parentCommentId
            );

            if ($commentId) {
                http_response_code(201); // Created
                // Potresti voler restituire il commento appena creato
                $newComment = $this->commentModel->findById($commentId); // Recupera il commento con username
                echo json_encode(['success' => true, 'message' => 'Commento creato con successo', 'comment' => $newComment]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore nella creazione del commento']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore del server: ' . $e->getMessage()]);
        }
    }

    // Potresti aggiungere metodi per PUT /api/comments/{id}, DELETE /api/comments/{id}
    // Ricorda di implementare controlli sui permessi (es. solo l'autore può modificare/eliminare)
}