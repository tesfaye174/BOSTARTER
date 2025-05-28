<?php
namespace BOSTARTER\Controllers;

use BOSTARTER\Models\Notification;

class NotificationController {
    private $db;
    private $notification;

    public function __construct($db) {
        $this->db = $db;
        $this->notification = new Notification($db);
    }

    /**
     * Ottiene le notifiche non lette dell'utente
     * @param int $userId ID dell'utente
     * @return array Risultato dell'operazione
     */
    public function getUnread($userId) {
        try {
            $notifications = $this->notification->getUnread($userId);
            return [
                'status' => 'success',
                'data' => $notifications
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Errore nel recupero delle notifiche: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Marca una notifica come letta
     * @param int $notificationId ID della notifica
     * @return array Risultato dell'operazione
     */
    public function markAsRead($notificationId) {
        try {
            $result = $this->notification->markAsRead($notificationId);
            return $result;
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Errore nell\'aggiornamento della notifica: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Marca tutte le notifiche come lette
     * @param int $userId ID dell'utente
     * @return array Risultato dell'operazione
     */
    public function markAllAsRead($userId) {
        try {
            $result = $this->notification->markAllAsRead($userId);
            return $result;
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Errore nell\'aggiornamento delle notifiche: ' . $e->getMessage()
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
            $result = $this->notification->delete($notificationId);
            return $result;
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Errore nell\'eliminazione della notifica: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene il conteggio delle notifiche non lette
     * @param int $userId ID dell'utente
     * @return array Risultato dell'operazione
     */
    public function getUnreadCount($userId) {
        try {
            $count = $this->notification->getUnreadCount($userId);
            return [
                'status' => 'success',
                'count' => $count
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Errore nel conteggio delle notifiche: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crea una notifica per un evento specifico
     * @param array $data Dati della notifica
     * @return array Risultato dell'operazione
     */
    public function createNotification($data) {
        try {
            $result = $this->notification->create($data);
            return $result;
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Errore nella creazione della notifica: ' . $e->getMessage()
            ];
        }
    }
} 