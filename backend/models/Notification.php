<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Crea una nuova notifica
     */
    public function create($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifiche (
                    utente_id, tipo, titolo, messaggio,
                    link, data_creazione, letta
                ) VALUES (?, ?, ?, ?, ?, NOW(), false)
            ");
            
            $stmt->execute([
                $data['utente_id'],
                $data['tipo'],
                $data['titolo'],
                $data['messaggio'],
                $data['link'] ?? null
            ]);
            
            return [
                'success' => true,
                'notification_id' => $this->conn->lastInsertId(),
                'message' => 'Notifica creata con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante la creazione della notifica'
            ];
        }
    }
    
    /**
     * Ottiene le notifiche di un utente
     */
    public function getUserNotifications($userId, $page = 1, $perPage = 10, $unreadOnly = false) {
        try {
            $offset = ($page - 1) * $perPage;
            $where = $unreadOnly ? "WHERE utente_id = ? AND letta = false" : "WHERE utente_id = ?";
            
            $stmt = $this->conn->prepare("
                SELECT *
                FROM notifiche
                $where
                ORDER BY data_creazione DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $perPage, $offset]);
            $notifications = $stmt->fetchAll();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM notifiche
                $where
            ");
            $stmt->execute([$userId]);
            $total = $stmt->fetchColumn();
            
            return [
                'notifications' => $notifications,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Marca una notifica come letta
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifiche
                SET letta = true
                WHERE id = ? AND utente_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            return [
                'success' => true,
                'message' => 'Notifica marcata come letta'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento della notifica'
            ];
        }
    }
    
    /**
     * Marca tutte le notifiche di un utente come lette
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifiche
                SET letta = true
                WHERE utente_id = ? AND letta = false
            ");
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'message' => 'Tutte le notifiche sono state marcate come lette'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento delle notifiche'
            ];
        }
    }
    
    /**
     * Elimina una notifica
     */
    public function delete($notificationId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifiche
                WHERE id = ? AND utente_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            return [
                'success' => true,
                'message' => 'Notifica eliminata con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'eliminazione della notifica'
            ];
        }
    }
    
    /**
     * Elimina tutte le notifiche di un utente
     */
    public function deleteAll($userId) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifiche
                WHERE utente_id = ?
            ");
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'message' => 'Tutte le notifiche sono state eliminate'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'eliminazione delle notifiche'
            ];
        }
    }
    
    /**
     * Ottiene il numero di notifiche non lette
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM notifiche
                WHERE utente_id = ? AND letta = false
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return 0;
        }
    }
    
    /**
     * Crea notifiche per eventi specifici
     */
    public function createProjectNotification($projectId, $type, $data = []) {
        try {
            $this->conn->beginTransaction();
            
            // Ottiene i dettagli del progetto
            $stmt = $this->conn->prepare("
                SELECT titolo, creatore_id
                FROM progetti
                WHERE id = ?
            ");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();
            
            if (!$project) {
                throw new Exception('Progetto non trovato');
            }
            
            // Crea la notifica per il creatore
            $notification = [
                'utente_id' => $project['creatore_id'],
                'tipo' => $type,
                'titolo' => $this->getNotificationTitle($type, $project['titolo']),
                'messaggio' => $this->getNotificationMessage($type, $data),
                'link' => "/progetti/{$projectId}"
            ];
            
            $this->create($notification);
            
            // Se necessario, crea notifiche per i donatori
            if (in_array($type, ['aggiornamento', 'completato', 'fallito'])) {
                $stmt = $this->conn->prepare("
                    SELECT DISTINCT utente_id
                    FROM donazioni
                    WHERE progetto_id = ?
                ");
                $stmt->execute([$projectId]);
                $donors = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($donors as $donorId) {
                    if ($donorId != $project['creatore_id']) {
                        $notification['utente_id'] = $donorId;
                        $this->create($notification);
                    }
                }
            }
            
            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Notifiche create con successo'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Ottiene il titolo della notifica in base al tipo
     */
    private function getNotificationTitle($type, $projectTitle) {
        $titles = [
            'nuovo_progetto' => "Nuovo progetto: {$projectTitle}",
            'aggiornamento' => "Aggiornamento progetto: {$projectTitle}",
            'nuova_donazione' => "Nuova donazione al progetto: {$projectTitle}",
            'obiettivo_raggiunto' => "Obiettivo raggiunto: {$projectTitle}",
            'completato' => "Progetto completato: {$projectTitle}",
            'fallito' => "Progetto fallito: {$projectTitle}",
            'nuovo_commento' => "Nuovo commento al progetto: {$projectTitle}",
            'ricompensa_disponibile' => "Ricompensa disponibile: {$projectTitle}"
        ];
        
        return $titles[$type] ?? "Notifica: {$projectTitle}";
    }
    
    /**
     * Ottiene il messaggio della notifica in base al tipo
     */
    private function getNotificationMessage($type, $data) {
        $messages = [
            'nuovo_progetto' => "Il tuo progetto è stato creato con successo!",
            'aggiornamento' => "È stato pubblicato un nuovo aggiornamento per il tuo progetto.",
            'nuova_donazione' => "Hai ricevuto una nuova donazione di {$data['importo']}€.",
            'obiettivo_raggiunto' => "Congratulazioni! Il tuo progetto ha raggiunto l'obiettivo!",
            'completato' => "Il progetto è stato completato con successo!",
            'fallito' => "Il progetto non ha raggiunto l'obiettivo entro la scadenza.",
            'nuovo_commento' => "Hai ricevuto un nuovo commento sul tuo progetto.",
            'ricompensa_disponibile' => "Una nuova ricompensa è disponibile per il tuo progetto."
        ];
        
        return $messages[$type] ?? "Nuova notifica";
    }
} 