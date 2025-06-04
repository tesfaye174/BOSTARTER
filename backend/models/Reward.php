<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Reward {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }    /**
     * Ottiene la lista delle ricompense di un progetto
     */
    public function getList($projectId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT *
                FROM ricompense
                WHERE progetto_id = ?
                ORDER BY importo_minimo ASC
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
        try {
            $stmt = $this->conn->prepare("
                SELECT r.*, p.titolo as progetto_titolo, u.nickname as creatore_nickname
                FROM ricompense r
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
                FROM donazioni
                WHERE ricompensa_id = ?
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
    public function create($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO ricompense (
                    progetto_id, titolo, descrizione, importo_minimo,
                    quantita_disponibile, data_consegna
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['progetto_id'],
                $data['titolo'],
                $data['descrizione'],
                $data['importo_minimo'],
                $data['quantita_disponibile'],
                $data['data_consegna']
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
            $sql = "UPDATE ricompense SET " . implode(', ', $updates) . " WHERE id = ?";
            
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
    public function delete($rewardId) {
        try {
            // Verifica se ci sono donazioni associate
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM donazioni
                WHERE ricompensa_id = ?
            ");
            $stmt->execute([$rewardId]);
            
            if ($stmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Impossibile eliminare la ricompensa: ci sono donazioni associate'
                ];
            }
            
            // Elimina la ricompensa
            $stmt = $this->conn->prepare("DELETE FROM ricompense WHERE id = ?");
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
        try {
            $stmt = $this->conn->prepare("
                SELECT quantita_disponibile
                FROM ricompense
                WHERE id = ?
            ");
            $stmt->execute([$rewardId]);
            $quantity = $stmt->fetchColumn();
            
            return [
                'available' => $quantity > 0,
                'quantity' => $quantity
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
                SELECT d.*, u.nickname, u.avatar
                FROM donazioni d
                JOIN utenti u ON d.utente_id = u.id
                WHERE d.ricompensa_id = ?
                ORDER BY d.data_donazione DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$rewardId, $perPage, $offset]);
            $donations = $stmt->fetchAll();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM donazioni
                WHERE ricompensa_id = ?
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
    public function getProjectStats($projectId) {
        try {
            // Ottiene il numero totale di ricompense
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as num_ricompense,
                SUM(quantita_disponibile) as totale_disponibili,
                MIN(importo_minimo) as importo_minimo,
                MAX(importo_minimo) as importo_massimo
                FROM ricompense
                WHERE progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $stats = $stmt->fetch();
            
            // Ottiene il numero di donazioni per ricompensa
            $stmt = $this->conn->prepare("
                SELECT r.id, r.titolo, r.importo_minimo,
                COUNT(d.id) as num_donazioni,
                SUM(d.importo) as totale_donazioni
                FROM ricompense r
                LEFT JOIN donazioni d ON r.id = d.ricompensa_id
                WHERE r.progetto_id = ?
                GROUP BY r.id
                ORDER BY r.importo_minimo ASC
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