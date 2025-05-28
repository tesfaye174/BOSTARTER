<?php
namespace BOSTARTER\Models;

class Notification {
    private $db;
    private $table = 'notifications';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Crea una nuova notifica
     * @param array $data Dati della notifica
     * @return array Risultato dell'operazione
     */
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (
                    user_id, 
                    type, 
                    title, 
                    message, 
                    link, 
                    is_read, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");

            $stmt->execute([
                $data['user_id'],
                $data['type'],
                $data['title'],
                $data['message'],
                $data['link'] ?? null
            ]);

            return [
                'status' => 'success',
                'message' => 'Notifica creata con successo',
                'notification_id' => $this->db->lastInsertId()
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Errore durante la creazione della notifica: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene le notifiche non lette di un utente
     * @param int $userId ID dell'utente
     * @return array Lista delle notifiche
     */
    public function getUnread($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Marca una notifica come letta
     * @param int $notificationId ID della notifica
     * @return array Risultato dell'operazione
     */
    public function markAsRead($notificationId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$notificationId]);
            
            return [
                'status' => 'success',
                'message' => 'Notifica marcata come letta'
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Errore durante l\'aggiornamento della notifica: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Marca tutte le notifiche di un utente come lette
     * @param int $userId ID dell'utente
     * @return array Risultato dell'operazione
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $stmt->execute([$userId]);
            
            return [
                'status' => 'success',
                'message' => 'Tutte le notifiche sono state marcate come lette'
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Errore durante l\'aggiornamento delle notifiche: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina una notifica
     * @param int $notificationId ID della notifica
     * @return array Risultato dell'operazione
     */
    public function delete($notificationId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$notificationId]);
            
            return [
                'status' => 'success',
                'message' => 'Notifica eliminata con successo'
            ];
        } catch (\PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Errore durante l\'eliminazione della notifica: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene il conteggio delle notifiche non lette
     * @param int $userId ID dell'utente
     * @return int Numero di notifiche non lette
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result['count'] ?? 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }
} 