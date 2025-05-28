<?php
namespace BOSTARTER\Services;

use BOSTARTER\Models\Notification;
use BOSTARTER\WebSocket\NotificationServer;

class NotificationService {
    private $notificationModel;
    private $notificationServer;

    public function __construct($db) {
        $this->notificationModel = new Notification($db);
        $this->notificationServer = new NotificationServer();
    }

    public function createNotification($userId, $message, $type, $relatedId = null) {
        try {
            // Crea la notifica nel database
            $notification = $this->notificationModel->create([
                'user_id' => $userId,
                'message' => $message,
                'type' => $type,
                'related_id' => $relatedId,
                'is_read' => false
            ]);

            if ($notification) {
                // Invia la notifica in tempo reale
                $this->notificationServer->sendNotification($userId, $notification);
            }

            return $notification;
        } catch (\Exception $e) {
            error_log("Errore nella creazione della notifica: " . $e->getMessage());
            return false;
        }
    }

    public function notifyProjectBacked($projectId, $backerId, $amount) {
        try {
            // Recupera il creatore del progetto
            $project = $this->getProject($projectId);
            if (!$project) {
                return false;
            }

            $message = "Hai ricevuto un nuovo finanziamento di €{$amount} per il progetto {$project['title']}";
            return $this->createNotification(
                $project['creator_id'],
                $message,
                'project_backed',
                $projectId
            );
        } catch (\Exception $e) {
            error_log("Errore nella notifica di finanziamento: " . $e->getMessage());
            return false;
        }
    }

    public function notifyProjectComment($projectId, $commenterId, $comment) {
        try {
            // Recupera il creatore del progetto
            $project = $this->getProject($projectId);
            if (!$project) {
                return false;
            }

            $message = "Nuovo commento sul tuo progetto {$project['title']}";
            return $this->createNotification(
                $project['creator_id'],
                $message,
                'project_comment',
                $projectId
            );
        } catch (\Exception $e) {
            error_log("Errore nella notifica di commento: " . $e->getMessage());
            return false;
        }
    }

    public function notifyProjectUpdate($projectId, $update) {
        try {
            // Recupera tutti i finanziatori del progetto
            $backers = $this->getProjectBackers($projectId);
            if (!$backers) {
                return false;
            }

            $project = $this->getProject($projectId);
            $message = "Aggiornamento sul progetto {$project['title']}: {$update}";

            $success = true;
            foreach ($backers as $backer) {
                $result = $this->createNotification(
                    $backer['user_id'],
                    $message,
                    'project_update',
                    $projectId
                );
                if (!$result) {
                    $success = false;
                }
            }

            return $success;
        } catch (\Exception $e) {
            error_log("Errore nella notifica di aggiornamento: " . $e->getMessage());
            return false;
        }
    }

    public function notifyProjectGoalReached($projectId) {
        try {
            $project = $this->getProject($projectId);
            if (!$project) {
                return false;
            }

            $message = "Congratulazioni! Il tuo progetto {$project['title']} ha raggiunto l'obiettivo di finanziamento!";
            return $this->createNotification(
                $project['creator_id'],
                $message,
                'goal_reached',
                $projectId
            );
        } catch (\Exception $e) {
            error_log("Errore nella notifica di obiettivo raggiunto: " . $e->getMessage());
            return false;
        }
    }

    private function getProject($projectId) {
        // Implementa la logica per recuperare i dettagli del progetto
        // Questo è un esempio, dovresti implementare la logica effettiva
        return [
            'id' => $projectId,
            'title' => 'Titolo Progetto',
            'creator_id' => 1
        ];
    }

    private function getProjectBackers($projectId) {
        // Implementa la logica per recuperare i finanziatori del progetto
        // Questo è un esempio, dovresti implementare la logica effettiva
        return [
            ['user_id' => 1],
            ['user_id' => 2]
        ];
    }
} 