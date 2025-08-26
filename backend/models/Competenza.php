<?php
require_once __DIR__ . '/../config/database.php';

class Competenza {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($nome, $descrizione = '') {
        try {
            // Verifica nome unico
            $stmt = $this->db->prepare("SELECT id FROM competenze WHERE nome = ?");
            $stmt->execute([$nome]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Competenza già esistente'];
            }
            
            $stmt = $this->db->prepare("INSERT INTO competenze (nome, descrizione) VALUES (?, ?)");
            $stmt->execute([$nome, $descrizione]);
            
            return [
                'success' => true,
                'competenza_id' => $this->db->lastInsertId(),
                'message' => 'Competenza creata con successo'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nella creazione competenza: ' . $e->getMessage()];
        }
    }
    
    public function getAll() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM competenze ORDER BY nome");
            $stmt->execute();
            $competenze = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'competenze' => $competenze];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero competenze: ' . $e->getMessage()];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM competenze WHERE id = ?");
            $stmt->execute([$id]);
            $competenza = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$competenza) {
                return ['success' => false, 'error' => 'Competenza non trovata'];
            }
            
            return ['success' => true, 'competenza' => $competenza];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero competenza: ' . $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM competenze WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Competenza non trovata'];
            }
            
            return ['success' => true, 'message' => 'Competenza eliminata con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'eliminazione competenza: ' . $e->getMessage()];
        }
    }
}
?>
