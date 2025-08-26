<?php
require_once __DIR__ . '/../config/database.php';

class Reward {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }    
    
    public function create($data) {
        try {
            $requiredFields = ['progetto_id', 'titolo', 'descrizione', 'importo_minimo'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['success' => false, 'error' => "Campo $field obbligatorio"];
                }
            }
            
            // Verifica che il progetto esista
            $stmt = $this->db->prepare("SELECT id FROM progetti WHERE id = ?");
            $stmt->execute([$data['progetto_id']]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'error' => 'Progetto non trovato'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO rewards (progetto_id, titolo, descrizione, importo_minimo, 
                                   quantita_limitata, quantita_max, data_consegna_prevista, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $data['progetto_id'],
                $data['titolo'],
                $data['descrizione'],
                $data['importo_minimo'],
                isset($data['quantita_limitata']) ? $data['quantita_limitata'] : 0,
                isset($data['quantita_max']) ? $data['quantita_max'] : null,
                isset($data['data_consegna_prevista']) ? $data['data_consegna_prevista'] : null
            ]);
            
            return [
                'success' => true,
                'reward_id' => $this->db->lastInsertId(),
                'message' => 'Reward creato con successo'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nella creazione reward: ' . $e->getMessage()];
        }
    }
    
    public function getByProject($progettoId) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, 
                       COUNT(f.id) as finanziamenti_count,
                       (r.quantita_max - COUNT(f.id)) as quantita_rimanente
                FROM rewards r
                LEFT JOIN finanziamenti f ON r.id = f.reward_id
                WHERE r.progetto_id = ?
                GROUP BY r.id
                ORDER BY r.importo_minimo ASC
            ");
            $stmt->execute([$progettoId]);
            $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'rewards' => $rewards];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero rewards: ' . $e->getMessage()];
        }
    }
    
    public function getById($rewardId) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, p.titolo as progetto_titolo, p.stato as progetto_stato,
                       COUNT(f.id) as finanziamenti_count
                FROM rewards r
                JOIN progetti p ON r.progetto_id = p.id
                LEFT JOIN finanziamenti f ON r.id = f.reward_id
                WHERE r.id = ?
                GROUP BY r.id
            ");
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reward) {
                return ['success' => false, 'error' => 'Reward non trovato'];
            }
            
            return ['success' => true, 'reward' => $reward];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero reward: ' . $e->getMessage()];
        }
    }
    
    public function update($rewardId, $data, $creatoreId) {
        try {
            // Verifica autorizzazione
            $stmt = $this->db->prepare("
                SELECT p.creatore_id 
                FROM rewards r 
                JOIN progetti p ON r.progetto_id = p.id 
                WHERE r.id = ?
            ");
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reward || $reward['creatore_id'] != $creatoreId) {
                return ['success' => false, 'error' => 'Non autorizzato a modificare questo reward'];
            }
            
            $fieldsToUpdate = [];
            $values = [];
            
            $allowedFields = ['titolo', 'descrizione', 'importo_minimo', 'quantita_limitata', 
                             'quantita_max', 'data_consegna_prevista'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fieldsToUpdate[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fieldsToUpdate)) {
                return ['success' => false, 'error' => 'Nessun campo da aggiornare'];
            }
            
            $values[] = $rewardId;
            
            $stmt = $this->db->prepare("
                UPDATE rewards 
                SET " . implode(', ', $fieldsToUpdate) . " 
                WHERE id = ?
            ");
            $stmt->execute($values);
            
            return ['success' => true, 'message' => 'Reward aggiornato con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'aggiornamento reward: ' . $e->getMessage()];
        }
    }
    
    public function delete($rewardId, $creatoreId) {
        try {
            // Verifica autorizzazione e che non ci siano finanziamenti
            $stmt = $this->db->prepare("
                SELECT p.creatore_id, COUNT(f.id) as finanziamenti_count
                FROM rewards r 
                JOIN progetti p ON r.progetto_id = p.id 
                LEFT JOIN finanziamenti f ON r.id = f.reward_id
                WHERE r.id = ?
                GROUP BY p.creatore_id
            ");
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reward || $reward['creatore_id'] != $creatoreId) {
                return ['success' => false, 'error' => 'Non autorizzato a eliminare questo reward'];
            }
            
            if ($reward['finanziamenti_count'] > 0) {
                return ['success' => false, 'error' => 'Impossibile eliminare reward con finanziamenti associati'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM rewards WHERE id = ?");
            $stmt->execute([$rewardId]);
            
            return ['success' => true, 'message' => 'Reward eliminato con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'eliminazione reward: ' . $e->getMessage()];
        }
    }
    
    public function checkAvailability($rewardId) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.quantita_limitata, r.quantita_max, COUNT(f.id) as finanziamenti_count
                FROM rewards r
                LEFT JOIN finanziamenti f ON r.id = f.reward_id
                WHERE r.id = ?
                GROUP BY r.id
            ");
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reward) {
                return ['success' => false, 'error' => 'Reward non trovato'];
            }
            
            $available = true;
            if ($reward['quantita_limitata'] && $reward['quantita_max']) {
                $available = $reward['finanziamenti_count'] < $reward['quantita_max'];
            }
            
            return [
                'success' => true, 
                'available' => $available,
                'remaining' => $reward['quantita_max'] ? $reward['quantita_max'] - $reward['finanziamenti_count'] : null
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nella verifica disponibilità: ' . $e->getMessage()];
        }
    }
    
    public function ottieniListaRicompense($idProgetto) {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM rewards
                WHERE progetto_id = ?
                ORDER BY created_at ASC
            ");
            $stmt->execute([$idProgetto]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Errore nel recupero ricompense: " . $e->getMessage());
            return [];
        }
    }
    public function ottieniDettagliRicompensa($idRicompensa) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, p.titolo as titolo_progetto, u.nickname as nickname_creatore
                FROM rewards r
                JOIN progetti p ON r.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$idRicompensa]);
            $ricompensa = $stmt->fetch();
            if (!$ricompensa) {
                return null;
            }
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as numero_donazioni
                FROM finanziamenti
                WHERE reward_id = ?            ");
            $stmt->execute([$idRicompensa]);
            $ricompensa['numero_donazioni'] = $stmt->fetchColumn();
            return $ricompensa;
        } catch (PDOException $e) {
            error_log("Errore nel recupero dettagli ricompensa: " . $e->getMessage());
            return null;
        }
    }
}
?>