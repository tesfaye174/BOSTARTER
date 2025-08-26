<?php
require_once __DIR__ . '/../config/database.php';

class Finanziamento {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $requiredFields = ['utente_id', 'progetto_id', 'importo', 'reward_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['success' => false, 'error' => "Campo $field obbligatorio"];
                }
            }
            
            // Verifica che il progetto sia aperto
            $stmt = $this->db->prepare("SELECT stato, budget_richiesto, budget_raccolto FROM progetti WHERE id = ?");
            $stmt->execute([$data['progetto_id']]);
            $progetto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$progetto) {
                return ['success' => false, 'error' => 'Progetto non trovato'];
            }
            
            if ($progetto['stato'] !== 'aperto') {
                return ['success' => false, 'error' => 'Il progetto è chiuso ai finanziamenti'];
            }
            
            // Verifica che la reward appartenga al progetto
            $stmt = $this->db->prepare("SELECT id FROM rewards WHERE id = ? AND progetto_id = ?");
            $stmt->execute([$data['reward_id'], $data['progetto_id']]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'error' => 'Reward non valida per questo progetto'];
            }
            
            $this->db->beginTransaction();
            
            // Inserisci finanziamento
            $stmt = $this->db->prepare("
                INSERT INTO finanziamenti (utente_id, progetto_id, importo, reward_id, data_finanziamento) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$data['utente_id'], $data['progetto_id'], $data['importo'], $data['reward_id']]);
            
            $finanziamentoId = $this->db->lastInsertId();
            
            // Aggiorna budget raccolto
            $stmt = $this->db->prepare("
                UPDATE progetti 
                SET budget_raccolto = budget_raccolto + ? 
                WHERE id = ?
            ");
            $stmt->execute([$data['importo'], $data['progetto_id']]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'finanziamento_id' => $finanziamentoId,
                'message' => 'Finanziamento effettuato con successo'
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Errore nel finanziamento: ' . $e->getMessage()];
        }
    }
    
    public function getByProject($progettoId) {
        try {
            $stmt = $this->db->prepare("
                SELECT f.*, u.nickname, r.descrizione as reward_descrizione
                FROM finanziamenti f
                JOIN utenti u ON f.utente_id = u.id
                JOIN rewards r ON f.reward_id = r.id
                WHERE f.progetto_id = ?
                ORDER BY f.data_finanziamento DESC
            ");
            $stmt->execute([$progettoId]);
            $finanziamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'finanziamenti' => $finanziamenti];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero finanziamenti: ' . $e->getMessage()];
        }
    }
    
    public function getByUser($utenteId) {
        try {
            $stmt = $this->db->prepare("
                SELECT f.*, p.nome as progetto_nome, r.descrizione as reward_descrizione
                FROM finanziamenti f
                JOIN progetti p ON f.progetto_id = p.id
                JOIN rewards r ON f.reward_id = r.id
                WHERE f.utente_id = ?
                ORDER BY f.data_finanziamento DESC
            ");
            $stmt->execute([$utenteId]);
            $finanziamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'finanziamenti' => $finanziamenti];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero finanziamenti: ' . $e->getMessage()];
        }
    }
    
    public function getTotalByUser($utenteId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(importo), 0) as totale_finanziato
                FROM finanziamenti 
                WHERE utente_id = ?
            ");
            $stmt->execute([$utenteId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'totale' => $result['totale_finanziato']];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel calcolo totale: ' . $e->getMessage()];
        }
    }
}
?>
