<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Comment {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Crea un nuovo commento
     */
    public function create($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO commenti (
                    progetto_id, utente_id, contenuto,
                    data_creazione, modificato
                ) VALUES (?, ?, ?, NOW(), false)
            ");
            
            $stmt->execute([
                $data['progetto_id'],
                $data['utente_id'],
                $data['contenuto']
            ]);
            
            $commentId = $this->conn->lastInsertId();
            
            // Crea una notifica per il creatore del progetto
            $stmt = $this->conn->prepare("
                SELECT creatore_id
                FROM progetti
                WHERE id = ?
            ");
            $stmt->execute([$data['progetto_id']]);
            $creatorId = $stmt->fetchColumn();
            
            if ($creatorId != $data['utente_id']) {
                $notification = new Notification();
                $notification->createProjectNotification(
                    $data['progetto_id'],
                    'nuovo_commento'
                );
            }
            
            return [
                'success' => true,
                'comment_id' => $commentId,
                'message' => 'Commento creato con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante la creazione del commento'
            ];
        }
    }
    
    /**
     * Ottiene i commenti di un progetto
     */
    public function getProjectComments($projectId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT c.*, u.nickname, u.avatar
                FROM commenti c
                JOIN utenti u ON c.utente_id = u.id
                WHERE c.progetto_id = ?
                ORDER BY c.data_creazione DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$projectId, $perPage, $offset]);
            $comments = $stmt->fetchAll();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM commenti
                WHERE progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $total = $stmt->fetchColumn();
            
            return [
                'comments' => $comments,
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
     * Ottiene i commenti di un utente
     */
    public function getUserComments($userId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT c.*, p.titolo as progetto_titolo
                FROM commenti c
                JOIN progetti p ON c.progetto_id = p.id
                WHERE c.utente_id = ?
                ORDER BY c.data_creazione DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $perPage, $offset]);
            $comments = $stmt->fetchAll();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM commenti
                WHERE utente_id = ?
            ");
            $stmt->execute([$userId]);
            $total = $stmt->fetchColumn();
            
            return [
                'comments' => $comments,
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
     * Aggiorna un commento
     */
    public function update($commentId, $userId, $content) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE commenti
                SET contenuto = ?, modificato = true
                WHERE id = ? AND utente_id = ?
            ");
            $stmt->execute([$content, $commentId, $userId]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Commento non trovato o non autorizzato'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Commento aggiornato con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento del commento'
            ];
        }
    }
    
    /**
     * Elimina un commento
     */
    public function delete($commentId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM commenti
                WHERE id = ? AND utente_id = ?
            ");
            $stmt->execute([$commentId, $userId]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Commento non trovato o non autorizzato'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Commento eliminato con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'eliminazione del commento'
            ];
        }
    }
    
    /**
     * Ottiene le statistiche dei commenti
     */
    public function getStats($projectId = null) {
        try {
            $where = $projectId ? "WHERE progetto_id = ?" : "";
            $params = $projectId ? [$projectId] : [];
            
            // Ottiene il numero totale di commenti
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as num_commenti,
                COUNT(DISTINCT utente_id) as num_utenti
                FROM commenti
                $where
            ");
            $stmt->execute($params);
            $stats = $stmt->fetch();
            
            // Ottiene i commenti per mese
            $stmt = $this->conn->prepare("
                SELECT DATE_FORMAT(data_creazione, '%Y-%m') as mese,
                COUNT(*) as num_commenti
                FROM commenti
                $where
                GROUP BY DATE_FORMAT(data_creazione, '%Y-%m')
                ORDER BY mese DESC
                LIMIT 12
            ");
            $stmt->execute($params);
            $stats['commenti_per_mese'] = $stmt->fetchAll();
            
            // Ottiene gli utenti più attivi
            $stmt = $this->conn->prepare("
                SELECT u.id, u.nickname, u.avatar,
                COUNT(c.id) as num_commenti
                FROM utenti u
                JOIN commenti c ON u.id = c.utente_id
                " . ($projectId ? "WHERE c.progetto_id = ?" : "") . "
                GROUP BY u.id
                ORDER BY num_commenti DESC
                LIMIT 5
            ");
            $stmt->execute($projectId ? [$projectId] : []);
            $stats['utenti_attivi'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
} 