<?php

class Reward {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Crea una nuova reward
     */
    public function create($creatoreId, $progettoId, $codice, $nome, $descrizione, $importoMinimo, $quantita = null) {
        try {
            // Verifica che l'utente sia creatore/admin del progetto
            $stmt = $this->db->prepare("SELECT creatore_id FROM progetti WHERE id = ?");
            $stmt->execute([$progettoId]);
            $progetto = $stmt->fetch();
            
            if (!$progetto) {
                return ['success' => false, 'error' => 'Progetto non trovato'];
            }
            
            if ($progetto['creatore_id'] != $creatoreId) {
                return ['success' => false, 'error' => 'Solo il creatore può aggiungere rewards'];
            }
            
            // Verifica che il codice sia univoco per il progetto
            $stmt = $this->db->prepare("SELECT id FROM rewards WHERE codice = ? AND progetto_id = ?");
            $stmt->execute([$codice, $progettoId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Codice reward già esistente per questo progetto'];
            }
            
            // Verifica importo minimo
            if ($importoMinimo <= 0) {
                return ['success' => false, 'error' => 'Importo minimo deve essere maggiore di zero'];
            }
            
            // Usa stored procedure per inserimento
            $stmt = $this->db->prepare("CALL sp_aggiungi_reward(?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $creatoreId,
                $progettoId,
                $codice,
                $nome,
                $descrizione,
                $importoMinimo,
                $quantita
            ]);
            
            return [
                'success' => true, 
                'data' => [
                    'codice' => $codice,
                    'nome' => $nome,
                    'message' => 'Reward creata con successo'
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera rewards per progetto
     */
    public function getByProgetto($progettoId, $onlyActive = true) {
        try {
            $sql = 
                SELECT 
                    id,
                    codice,
                    nome,
                    descrizione,
                    importo_minimo,
                    quantita_disponibile,
                    quantita_utilizzata,
                    is_active,
                    created_at
                FROM rewards 
                WHERE progetto_id = ?
            ;
            
            $params = [$progettoId];
            
            if ($onlyActive) {
                $sql .= " AND is_active = TRUE";
            }
            
            $sql .= " ORDER BY importo_minimo ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera singola reward
     */
    public function getById($rewardId) {
        try {
            $stmt = $this->db->prepare(
                SELECT r.*, p.nome as progetto_nome, p.creatore_id
                FROM rewards r
                JOIN progetti p ON r.progetto_id = p.id
                WHERE r.id = ?
            );
            $stmt->execute([$rewardId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Modifica reward
     */
    public function update($rewardId, $campo, $valore, $creatoreId) {
        // Campi modificabili
        $campiPermessi = ['nome', 'descrizione', 'importo_minimo', 'quantita_disponibile', 'is_active'];
        if (!in_array($campo, $campiPermessi)) {
            return ['success' => false, 'error' => 'Campo non modificabile'];
        }
        
        try {
            // Verifica permessi
            $stmt = $this->db->prepare(
                SELECT r.id, p.creatore_id
                FROM rewards r
                JOIN progetti p ON r.progetto_id = p.id
                WHERE r.id = ?
            );
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch();
            
            if (!$reward) {
                return ['success' => false, 'error' => 'Reward non trovata'];
            }
            
            if ($reward['creatore_id'] != $creatoreId) {
                return ['success' => false, 'error' => 'Accesso negato'];
            }
            
            // Validazioni specifiche per campo
            if ($campo === 'importo_minimo' && (float)$valore <= 0) {
                return ['success' => false, 'error' => 'Importo minimo deve essere maggiore di zero'];
            }
            
            if ($campo === 'quantita_disponibile' && $valore !== null && (int)$valore < 0) {
                return ['success' => false, 'error' => 'Quantità non può essere negativa'];
            }
            
            // Aggiorna campo
            $stmt = $this->db->prepare("UPDATE rewards SET $campo = ? WHERE id = ?");
            $stmt->execute([$valore, $rewardId]);
            
            return ['success' => true, 'data' => [$campo => $valore]];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Disattiva reward
     */
    public function deactivate($rewardId, $creatoreId) {
        try {
            // Verifica permessi
            $stmt = $this->db->prepare(
                SELECT r.id, p.creatore_id, r.quantita_utilizzata
                FROM rewards r
                JOIN progetti p ON r.progetto_id = p.id
                WHERE r.id = ?
            );
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch();
            
            if (!$reward) {
                return ['success' => false, 'error' => 'Reward non trovata'];
            }
            
            if ($reward['creatore_id'] != $creatoreId) {
                return ['success' => false, 'error' => 'Accesso negato'];
            }
            
            // Verifica che non sia stata utilizzata
            if ($reward['quantita_utilizzata'] > 0) {
                return ['success' => false, 'error' => 'Impossibile disattivare reward già utilizzata'];
            }
            
            // Disattiva invece di cancellare (soft delete)
            $stmt = $this->db->prepare("UPDATE rewards SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$rewardId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Verifica disponibilità reward
     */
    public function checkAvailability($rewardId) {
        try {
            $stmt = $this->db->prepare(
                SELECT 
                    quantita_disponibile,
                    quantita_utilizzata,
                    is_active
                FROM rewards 
                WHERE id = ?
        );
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch();
            
            if (!$reward || !$reward['is_active']) {
                return ['available' => false, 'reason' => 'Reward non disponibile'];
            }
            
            if ($reward['quantita_disponibile'] === null) {
                return ['available' => true, 'unlimited' => true];
            }
            
            $available = $reward['quantita_disponibile'] - $reward['quantita_utilizzata'];
            
            return [
                'available' => $available > 0,
                'unlimited' => false,
                'remaining' => $available,
                'reason' => $available > 0 ? null : 'Quantità esaurita'
            ];
            
        } catch (Exception $e) {
            return ['available' => false, 'reason' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiche rewards per progetto
     */
    public function getStatsByProgetto($progettoId) {
        try {
            $stmt = $this->db->prepare(
                SELECT 
                    COUNT(r.id) as totale_rewards,
                    COUNT(CASE WHEN r.is_active = TRUE THEN 1 END) as rewards_attive,
                    AVG(r.importo_minimo) as importo_medio_minimo,
                    MIN(r.importo_minimo) as importo_minimo_minimo,
                    MAX(r.importo_minimo) as importo_minimo_massimo,
                    SUM(CASE WHEN r.quantita_disponibile IS NOT NULL THEN r.quantita_disponibile ELSE 0 END) as totale_quantita_disponibile,
                    SUM(r.quantita_utilizzata) as totale_quantita_utilizzata
                FROM rewards r
                WHERE r.progetto_id = ?
            );
            $stmt->execute([$progettoId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Rewards più popolari
     */
    public function getMostPopular($limit = 10) {
        try {
            $stmt = $this->db->prepare(
                SELECT 
                    r.nome,
                    r.descrizione,
                    r.importo_minimo,
                    r.quantita_utilizzata,
                    p.nome as progetto_nome,
                    p.tipo as progetto_tipo
                FROM rewards r
                JOIN progetti p ON r.progetto_id = p.id
                WHERE r.is_active = TRUE AND r.quantita_utilizzata > 0
                ORDER BY r.quantita_utilizzata DESC
                LIMIT ?
            );
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>