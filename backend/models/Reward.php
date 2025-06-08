<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Reward {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }/**
     * Ottiene la lista delle ricompense di un progetto
     */
    public function getList($projectId) {        try {
            $stmt = $this->conn->prepare("
                SELECT *
                FROM reward
                WHERE progetto_id = ?
                ORDER BY created_at ASC
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    
    /**
     * Ottiene i dettagli di una ricompensa
     */
    public function getDetails($rewardId) {
        try {            $stmt = $this->conn->prepare("
                SELECT r.*, p.nome as progetto_titolo, u.nickname as creatore_nickname
                FROM reward r
                JOIN progetti p ON r.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch();
            
            if (!$reward) {
                return null;
            }            // Ottiene il numero di donazioni per questa ricompensa
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as num_donazioni
                FROM finanziamenti
                WHERE reward_id = ?
            ");
            $stmt->execute([$rewardId]);
            $reward['num_donazioni'] = $stmt->fetchColumn();
            
            return $reward;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Crea una nuova ricompensa
     */
    public function create($data) {        try {
            $stmt = $this->conn->prepare("
                INSERT INTO reward (
                    progetto_id, codice, descrizione
                ) VALUES (?, ?, ?)
            ");
              $stmt->execute([
                $data['progetto_id'],
                $data['codice'],
                $data['descrizione']
            ]);
            
            return [
                'success' => true,
                'reward_id' => $this->conn->lastInsertId(),
                'message' => 'Ricompensa creata con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante la creazione della ricompensa'
            ];
        }
    }    /**
     * Aggiorna una ricompensa
     */
    public function update($rewardId, $data) {
        try {
            $allowedFields = [
                'titolo', 'descrizione', 'importo_minimo',
                'quantita_disponibile', 'data_consegna'
            ];
            
            $updates = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($updates)) {
                return [
                    'success' => false,
                    'message' => 'Nessun campo da aggiornare'
                ];
            }
              $params[] = $rewardId;
            $sql = "UPDATE reward SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'message' => 'Ricompensa aggiornata con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento della ricompensa'
            ];
        }
    }
    
    /**
     * Elimina una ricompensa
     */
    public function delete($rewardId) {        try {
            // Verifica se ci sono donazioni associate
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM finanziamenti
                WHERE reward_id = ?
            ");
            $stmt->execute([$rewardId]);
            
            if ($stmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Impossibile eliminare la ricompensa: ci sono donazioni associate'
                ];
            }
              // Elimina la ricompensa
            $stmt = $this->conn->prepare("DELETE FROM reward WHERE id = ?");
            $stmt->execute([$rewardId]);
            
            return [
                'success' => true,
                'message' => 'Ricompensa eliminata con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'eliminazione della ricompensa'
            ];
        }
    }
    
    /**
     * Verifica la disponibilità di una ricompensa
     */
    public function checkAvailability($rewardId) {
        try {            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as available
                FROM reward
                WHERE id = ?
            ");
            $stmt->execute([$rewardId]);
            $result = $stmt->fetchColumn();
            
            return [
                'available' => $result > 0,
                'quantity' => 1 // Since reward table doesn't track quantity
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'available' => false,
                'quantity' => 0
            ];
        }
    }
    
    /**
     * Ottiene le donazioni per una ricompensa
     */
    public function getDonations($rewardId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
              $stmt = $this->conn->prepare("
                SELECT f.*, u.nickname, u.avatar
                FROM finanziamenti f
                JOIN utenti u ON f.utente_id = u.id
                WHERE f.reward_id = ?
                ORDER BY f.data_finanziamento DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$rewardId, $perPage, $offset]);            $donations = $stmt->fetchAll();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM finanziamenti
                WHERE reward_id = ?
            ");
            $stmt->execute([$rewardId]);
            $total = $stmt->fetchColumn();
            
            return [
                'donations' => $donations,
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
     * Ottiene le statistiche delle ricompense di un progetto
     */
    public function getProjectStats($projectId) {        try {
            // Ottiene il numero totale di ricompense
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as num_ricompense,
                       COUNT(*) as totale_disponibili
                FROM reward
                WHERE progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $stats = $stmt->fetch();
              // Ottiene il numero di donazioni per ricompensa
            $stmt = $this->conn->prepare("
                SELECT r.id, r.descrizione,
                COUNT(f.id) as num_donazioni,
                SUM(f.importo) as totale_donazioni
                FROM reward r
                LEFT JOIN finanziamenti f ON r.id = f.reward_id
                WHERE r.progetto_id = ?
                GROUP BY r.id
                ORDER BY r.created_at ASC
            ");
            $stmt->execute([$projectId]);
            $stats['ricompense'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
} 