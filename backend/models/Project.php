<?php
/**
 * Classe Project - Gestisce i progetti nella piattaforma BOSTARTER
 */
class Project {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Crea un nuovo progetto nel database
     * @param array $data Dati del progetto
     * @return int|false ID del progetto creato o false in caso di errore
     */
    public function create($data) {
        try {
            // Mappa le categorie del form ai valori ENUM del database
            $categoryMap = [
                'technology' => 'software',
                'games' => 'software',
                'art' => 'hardware',
                'music' => 'hardware',
                'film' => 'hardware',
                'publishing' => 'software',
                'food' => 'hardware',
                'fashion' => 'hardware',
                'health' => 'hardware',
                'education' => 'software',
                'community' => 'software',
                'environment' => 'hardware'
            ];
            
            $dbCategory = $categoryMap[$data['category']] ?? 'software';
            
            $sql = "INSERT INTO progetti (nome, descrizione, tipo_progetto, budget_richiesto, data_limite, creatore_id, stato) 
                     VALUES (?, ?, ?, ?, ?, ?, 'aperto')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['name'],
                $data['description'], 
                $dbCategory,
                $data['funding_goal'],
                $data['deadline'],
                $data['creator_id']
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("Errore nella creazione del progetto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Recupera un progetto tramite ID
     * @param int $id ID del progetto
     * @return array|false Dati del progetto o false se non trovato
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM progetti WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Recupera tutti i progetti ordinati per data di inserimento
     * @return array Lista di tutti i progetti
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM progetti ORDER BY data_inserimento DESC");
        return $stmt->fetchAll();
    }
    
    /**
     * Recupera i progetti di un creatore specifico
     * @param int $creatorId ID del creatore
     * @return array Lista dei progetti del creatore
     */
    public function getByCreator($creatorId) {
        $stmt = $this->db->prepare("SELECT * FROM progetti WHERE creatore_id = ? ORDER BY data_inserimento DESC");
        $stmt->execute([$creatorId]);
        return $stmt->fetchAll();
    }
}
?>