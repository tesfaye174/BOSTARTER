<?php
require_once __DIR__ . '/../config/database.php';

class Commento {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $requiredFields = ['utente_id', 'progetto_id', 'testo'];
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
                INSERT INTO commenti (utente_id, progetto_id, testo, data_commento) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$data['utente_id'], $data['progetto_id'], $data['testo']]);
            
            return [
                'success' => true,
                'commento_id' => $this->db->lastInsertId(),
                'message' => 'Commento inserito con successo'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'inserimento commento: ' . $e->getMessage()];
        }
    }
    
    public function addRisposta($commentoId, $creatoreId, $testo) {
        try {
            // Verifica che il commento esista e che il creatore sia autorizzato
            $stmt = $this->db->prepare("
                SELECT c.id, p.creatore_id 
                FROM commenti c 
                JOIN progetti p ON c.progetto_id = p.id 
                WHERE c.id = ?
            ");
            $stmt->execute([$commentoId]);
            $commento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$commento) {
                return ['success' => false, 'error' => 'Commento non trovato'];
            }
            
            if ($commento['creatore_id'] != $creatoreId) {
                return ['success' => false, 'error' => 'Solo il creatore del progetto può rispondere'];
            }
            
            // Verifica che non ci sia già una risposta
            $stmt = $this->db->prepare("SELECT id FROM commenti WHERE risposta_a = ?");
            $stmt->execute([$commentoId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Risposta già presente per questo commento'];
            }
            
            $stmt = $this->db->prepare("
                UPDATE commenti 
                SET risposta = ?, data_risposta = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$testo, $commentoId]);
            
            return ['success' => true, 'message' => 'Risposta aggiunta con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'aggiunta risposta: ' . $e->getMessage()];
        }
    }
    
    public function getByProject($progettoId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.nickname 
                FROM commenti c
                JOIN utenti u ON c.utente_id = u.id
                WHERE c.progetto_id = ?
                ORDER BY c.data_commento DESC
            ");
            $stmt->execute([$progettoId]);
            $commenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'commenti' => $commenti];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero commenti: ' . $e->getMessage()];
        }
    }
    
    public function delete($commentoId, $utenteId, $isAdmin = false) {
        try {
            // Verifica autorizzazione
            if (!$isAdmin) {
                $stmt = $this->db->prepare("SELECT utente_id FROM commenti WHERE id = ?");
                $stmt->execute([$commentoId]);
                $commento = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$commento || $commento['utente_id'] != $utenteId) {
                    return ['success' => false, 'error' => 'Non autorizzato a eliminare questo commento'];
                }
            }
            
            $stmt = $this->db->prepare("DELETE FROM commenti WHERE id = ?");
            $stmt->execute([$commentoId]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Commento non trovato'];
            }
            
            return ['success' => true, 'message' => 'Commento eliminato con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'eliminazione commento: ' . $e->getMessage()];
        }
    }
}
?>
