<?php
namespace BOSTARTER\Services;

use BOSTARTER\Models\Notification;

class NotificationService {
    private $notificationModel;
    private $db;
    private $wsEndpoint;

    public function __construct($db) {
        $this->db = $db;
        $this->notificationModel = new Notification($db);
        $this->wsEndpoint = 'http://localhost:8080/api/send'; // WebSocket API endpoint
    }

    /**
     * Create and send a notification
     */
    public function createNotification($userId, $message, $type, $relatedId = null, $metadata = []) {
        try {
            // Create notification in database
            $notificationData = [
                'user_id' => $userId,
                'message' => $message,
                'type' => $type,
                'related_id' => $relatedId,
                'metadata' => json_encode($metadata),
                'is_read' => false,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $notification = $this->notificationModel->create($notificationData);

            if ($notification) {
                // Send real-time notification via WebSocket
                $this->sendRealTimeNotification($userId, $notification);
                
                // Log the notification
                $this->logNotification($userId, $type, $message);
            }

            return $notification;
        } catch (\Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send real-time notification via WebSocket
     */
    private function sendRealTimeNotification($userId, $notification) {
        try {
            // This would connect to the WebSocket server to send real-time notification
            // For now, we'll use a simple HTTP request to a webhook endpoint
            $data = [
                'type' => 'notification',
                'user_id' => $userId,
                'notification' => $notification
            ];

            // In a production environment, you'd use a proper message queue
            // or direct WebSocket connection here
            $this->sendWebSocketMessage($data);
            
        } catch (\Exception $e) {
            error_log("Error sending real-time notification: " . $e->getMessage());
        }
    }

    /**
     * Send message to WebSocket server
     */
    private function sendWebSocketMessage($data) {
        // This is a simplified implementation
        // In production, you'd use a proper WebSocket client or message queue
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'timeout' => 5
            ]
        ]);

        @file_get_contents($this->wsEndpoint, false, $context);
    }

    /**
     * Log notification for analytics
     */
    private function logNotification($userId, $type, $message) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_logs (user_id, type, message, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $type, $message]);
        } catch (\Exception $e) {
            error_log("Error logging notification: " . $e->getMessage());
        }
    }

    /**
     * Notify when a project receives backing
     */
    public function notifyProjectBacked($projectId, $backerId, $amount) {
        try {
            $project = $this->getProject($projectId);
            if (!$project) {
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