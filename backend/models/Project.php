<?php
/**
 * Modello Project - Gestione progetti BOSTARTER
 */
class Project {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Crea nuovo progetto
     * @param array $data Dati progetto
     * @return array Risultato operazione
     */
    public function create($data) {
        try {
            $requiredFields = ['nome', 'descrizione', 'tipo', 'budget_richiesto', 'data_limite', 'creatore_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return [
                        'success' => false,
                        'error' => "Campo obbligatorio mancante: $field"
                    ];
                }
            }

            if (!in_array($data['tipo'], ['hardware', 'software'])) {
                return [
                    'success' => false,
                    'error' => 'Tipo progetto deve essere hardware o software'
                ];
            }

            // Controllo nome duplicato
            $stmt = $this->db->prepare("SELECT id FROM progetti WHERE nome = ?");
            $stmt->execute([$data['nome']]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'error' => 'Esiste già un progetto con questo nome. Scegli un nome diverso.'
                ];
            }

            $dataLimite = date('Y-m-d', strtotime($data['data_limite']));
            
            $sql = "INSERT INTO progetti (nome, descrizione, tipo, budget_richiesto, data_limite, creatore_id, stato) 
                    VALUES (?, ?, ?, ?, ?, ?, 'aperto')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['nome'],
                $data['descrizione'], 
                $data['tipo'],
                $data['budget_richiesto'],
                $dataLimite,
                $data['creatore_id']
            ]);
            
            if ($result) {
                $projectId = $this->db->lastInsertId();
                return [
                    'success' => true,
                    'progetto_id' => $projectId,
                    'message' => 'Progetto creato con successo'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Errore durante la creazione del progetto'
                ];
            }
        } catch (Exception $e) {
            error_log("Errore nella creazione del progetto: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Errore del server: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Recupera progetto per ID
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM progetti WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getDetails($id) {
        return $this->getById($id);
    }
    
    /**
     * Lista tutti i progetti
     * @return array
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM progetti ORDER BY data_inserimento DESC");
        return $stmt->fetchAll();
    }
    
    /**
     * Progetti per creatore
     * @param int $creatorId
     * @return array
     */
    public function getByCreator($creatorId) {
        $stmt = $this->db->prepare("SELECT * FROM progetti WHERE creatore_id = ? ORDER BY data_inserimento DESC");
        $stmt->execute([$creatorId]);
        return $stmt->fetchAll();
    }
}
?>