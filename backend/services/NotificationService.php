<?php
/**
 * SERVIZIO NOTIFICHE BOSTARTER
 * 
 * Questo servizio si occupa di tutto ciò che riguarda le notifiche:
 * - Creazione di nuove notifiche per gli utenti
 * - Invio di notifiche via email, SMS, push
 * - Recupero delle notifiche non lette
 * - Gestione delle preferenze di notifica
 * 
 * È come il servizio postale di casa: raccoglie i messaggi importanti
 * e li consegna nel modo giusto a ogni persona!
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Versione completamente riscritta per essere più umana
 */
namespace BOSTARTER\Services;

use BOSTARTER\Models\GestoreNotifiche;

class GestoreServizioNotifiche {
    // Gestore del modello notifiche e connessione database
    private $gestoreNotifiche;
    private $connessioneDatabase;

    public function __construct($database) {
        $this->connessioneDatabase = $database;
        $this->gestoreNotifiche = new GestoreNotifiche($database);
    }

    /**
     * Crea e invia una nuova notifica
     * 
     * È come quando devi mandare una lettera importante: scrivi il messaggio,
     * metti l'indirizzo giusto e la spedisci nel modo più appropriato
     * 
     * @param int $idUtente ID dell'utente che riceverà la notifica
     * @param string $messaggio Il contenuto della notifica
     * @param string $tipo Tipo di notifica (info, warning, success, error)
     * @param int|null $idCollegato ID collegato (es. progetto, utente, ecc.)
     * @param array $metadati Dati aggiuntivi per la notifica
     * @return array Risultato dell'operazione
     */
    public function creaNuovaNotifica($idUtente, $messaggio, $tipo, $idCollegato = null, $metadati = []) {
        try {            // Prepariamo i dati della notifica in formato comprensibile
            $datiNotifica = [
                'user_id' => $idUtente,
                'message' => $messaggio,
                'type' => $tipo,
                'related_id' => $idCollegato,
                'metadata' => json_encode($metadati),
                'is_read' => false,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Creiamo la notifica usando il nostro gestore
            $risultato = $this->gestoreNotifiche->creaNuovaNotifica(
                $idUtente, 
                $messaggio, 
                $tipo, 
                $idCollegato
            );
            
            if ($risultato['stato'] === 'successo') {
                // Registriamo l'attività per monitoraggio
                $this->registraNotifica($idUtente, $tipo, $messaggio);
                
                // Se è una notifica importante, potremmo inviarla anche via email
                if ($this->dovremmoInviareEmail($tipo)) {
                    $this->inviaNotificaEmail($idUtente, $messaggio, $tipo);
                }
            }
            
            return $risultato;        } catch (\Exception $errore) {
            error_log("Errore nella creazione della notifica: " . $errore->getMessage());
            return [
                'stato' => 'errore',
                'messaggio' => 'Non sono riuscito a creare la notifica. Riprova più tardi.'
            ];
        }
    }

    /**
     * Registra la notifica per statistiche e monitoraggio
     * 
     * È come tenere un registro di tutte le lettere spedite
     */
    private function registraNotifica($idUtente, $tipo, $messaggio) {
        try {
            $statement = $this->connessioneDatabase->prepare("
                INSERT INTO notification_logs (user_id, type, message, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $statement->execute([$idUtente, $tipo, $messaggio]);
        } catch (\Exception $errore) {
            error_log("Errore nel registrare notifica: " . $errore->getMessage());
        }
    }

    /**
     * Determina se dovremmo inviare questa notifica anche via email
     * 
     * @param string $tipo Il tipo di notifica
     * @return bool True se deve essere inviata via email
     */
    private function dovremmoInviareEmail($tipo) {
        // Inviamo email solo per notifiche importanti
        $tipiImportanti = ['project_funded', 'project_backed', 'system_alert', 'account_security'];
        return in_array($tipo, $tipiImportanti);
    }

    /**
     * Invia una notifica via email
     * 
     * @param int $idUtente ID dell'utente
     * @param string $messaggio Messaggio da inviare
     * @param string $tipo Tipo di notifica
     */
    private function inviaNotificaEmail($idUtente, $messaggio, $tipo) {
        // TODO: Implementare invio email
        // Per ora registriamo solo che dovremmo inviare l'email
        error_log("TODO: Inviare email a utente {$idUtente} - Tipo: {$tipo} - Messaggio: {$messaggio}");
    }

    /**
     * Notifica quando un progetto riceve un finanziamento
     * 
     * È come quando qualcuno fa una donazione: il creatore del progetto
     * deve essere avvisato che ha ricevuto supporto!
     */
    public function notificaProgettoFinanziato($idProgetto, $idSostenitore, $importo) {
        try {
            $progetto = $this->ottieniProgetto($idProgetto);
            if (!$progetto) {
                return false;
            }

            $backer = $this->getUser($backerId);
            $message = sprintf(
                "🎉 %s ha finanziato il tuo progetto '%s' con €%.2f!", 
                $backer['first_name'] ?? 'Un utente', 
                $project['title'], 
                $amount
            );

            return $this->createNotification(
                $project['creator_id'],
                $message,
                'project_backed',
                $projectId,
                [
                    'backer_id' => $backerId,
                    'amount' => $amount,
                    'project_title' => $project['title']
                ]
            );
        } catch (\Exception $e) {
            error_log("Error in project backed notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify when a project receives a comment
     */
    public function notifyProjectComment($projectId, $commenterId, $comment) {
        try {
            $project = $this->getProject($projectId);
            if (!$project) {
                return false;
            }

            $commenter = $this->getUser($commenterId);
            $message = sprintf(
                "💬 %s ha commentato il tuo progetto '%s'", 
                $commenter['first_name'] ?? 'Un utente', 
                $project['title']
            );

            return $this->createNotification(
                $project['creator_id'],
                $message,
                'project_comment',
                $projectId,
                [
                    'commenter_id' => $commenterId,
                    'comment_preview' => substr($comment, 0, 100),
                    'project_title' => $project['title']
                ]
            );
        } catch (\Exception $e) {
            error_log("Error in project comment notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify project backers of updates
     */
    public function notifyProjectUpdate($projectId, $updateTitle, $updateContent) {
        try {
            $backers = $this->getProjectBackers($projectId);
            $project = $this->getProject($projectId);
            
            if (!$backers || !$project) {
                return false;
            }

            $message = sprintf(
                "📢 Aggiornamento sul progetto '%s': %s", 
                $project['title'], 
                $updateTitle
            );

            $results = [];
            foreach ($backers as $backer) {
                $result = $this->createNotification(
                    $backer['user_id'],
                    $message,
                    'project_update',
                    $projectId,
                    [
                        'update_title' => $updateTitle,
                        'update_preview' => substr($updateContent, 0, 200),
                        'project_title' => $project['title']
                    ]
                );
                $results[] = $result;
            }

            return $results;
        } catch (\Exception $e) {
            error_log("Error in project update notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify when project reaches funding goal
     */
    public function notifyProjectGoalReached($projectId) {
        try {
            $project = $this->getProject($projectId);
            if (!$project) {
                return false;
            }

            // Notify creator
            $creatorMessage = sprintf(
                "🎯 Congratulazioni! Il tuo progetto '%s' ha raggiunto l'obiettivo di finanziamento!",
                $project['title']
            );

            $creatorNotification = $this->createNotification(
                $project['creator_id'],
                $creatorMessage,
                'goal_reached',
                $projectId,
                ['project_title' => $project['title']]
            );

            // Notify all backers
            $backers = $this->getProjectBackers($projectId);
            $backerMessage = sprintf(
                "🎉 Il progetto '%s' che hai sostenuto ha raggiunto l'obiettivo di finanziamento!",
                $project['title']
            );

            $backerNotifications = [];
            foreach ($backers as $backer) {
                $backerNotifications[] = $this->createNotification(
                    $backer['user_id'],
                    $backerMessage,
                    'project_goal_reached',
                    $projectId,
                    ['project_title' => $project['title']]
                );
            }

            return [
                'creator' => $creatorNotification,
                'backers' => $backerNotifications
            ];
        } catch (\Exception $e) {
            error_log("Error in goal reached notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify about application status changes
     */
    public function notifyApplicationStatusChange($applicationId, $newStatus) {
        try {
            $application = $this->getApplication($applicationId);
            if (!$application) {
                return false;
            }

            $statusMessages = [
                'approved' => '✅ La tua candidatura è stata approvata!',
                'rejected' => '❌ La tua candidatura non è stata accettata',
                'pending' => '⏳ La tua candidatura è in revisione'
            ];

            $message = $statusMessages[$newStatus] ?? 'Aggiornamento sulla tua candidatura';

            return $this->createNotification(
                $application['user_id'],
                $message,
                'application_status',
                $applicationId,
                [
                    'status' => $newStatus,
                    'project_id' => $application['project_id']
                ]
            );
        } catch (\Exception $e) {
            error_log("Error in application status notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        try {
            return $this->notificationModel->markAsRead($notificationId, $userId);
        } catch (\Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        try {
            return $this->notificationModel->markAllAsRead($userId);
        } catch (\Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's unread notifications
     */
    public function getUnreadNotifications($userId, $limit = 20) {
        try {
            return $this->notificationModel->getUnread($userId, $limit);
        } catch (\Exception $e) {
            error_log("Error getting unread notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount($userId) {
        try {
            return $this->notificationModel->getUnreadCount($userId);
        } catch (\Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }

    // Helper methods
    private function getProject($projectId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting project: " . $e->getMessage());
            return null;
        }
    }

    private function getUser($userId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting user: " . $e->getMessage());
            return null;
        }
    }

    private function getProjectBackers($projectId) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT user_id 
                FROM funding 
                WHERE project_id = ? AND status = 'confirmed'
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting project backers: " . $e->getMessage());
            return [];
        }
    }

    private function getApplication($applicationId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting application: " . $e->getMessage());
            return null;
        }
    }
}