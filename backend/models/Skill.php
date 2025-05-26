<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Skill {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Ottiene la lista delle competenze
     */
    public function getList($filters = [], $page = 1, $perPage = 10) {
        try {
            $where = [];
            $params = [];
            
            // Applica i filtri
            if (!empty($filters['categoria'])) {
                $where[] = "categoria = ?";
                $params[] = $filters['categoria'];
            }
            
            if (!empty($filters['search'])) {
                $where[] = "(nome LIKE ? OR descrizione LIKE ?)";
                $params[] = "%{$filters['search']}%";
                $params[] = "%{$filters['search']}%";
            }
            
            // Costruisce la query
            $sql = "SELECT * FROM competenze";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            // Aggiunge l'ordinamento
            $sql .= " ORDER BY nome ASC";
            
            // Aggiunge la paginazione
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            
            // Esegue la query
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $skills = $stmt->fetchAll();
            
            // Ottiene il totale delle competenze
            $sql = "SELECT COUNT(*) FROM competenze";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_slice($params, 0, -2));
            $total = $stmt->fetchColumn();
            
            return [
                'skills' => $skills,
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
     * Ottiene i dettagli di una competenza
     */
    public function getDetails($skillId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.*, COUNT(DISTINCT cp.progetto_id) as num_progetti
                FROM competenze c
                LEFT JOIN competenze_progetti cp ON c.id = cp.competenza_id
                WHERE c.id = ?
                GROUP BY c.id
            ");
            $stmt->execute([$skillId]);
            $skill = $stmt->fetch();
            
            if (!$skill) {
                return null;
            }
            
            // Ottiene i progetti che richiedono questa competenza
            $stmt = $this->conn->prepare("
                SELECT p.*, u.nickname as creatore_nickname
                FROM progetti p
                JOIN competenze_progetti cp ON p.id = cp.progetto_id
                JOIN utenti u ON p.creatore_id = u.id
                WHERE cp.competenza_id = ?
                AND p.data_fine > NOW()
                ORDER BY p.data_inizio DESC
                LIMIT 5
            ");
            $stmt->execute([$skillId]);
            $skill['progetti'] = $stmt->fetchAll();
            
            return $skill;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Crea una nuova competenza
     */
    public function create($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO competenze (nome, descrizione, categoria)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nome'],
                $data['descrizione'],
                $data['categoria']
            ]);
            
            return [
                'success' => true,
                'skill_id' => $this->conn->lastInsertId(),
                'message' => 'Competenza creata con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante la creazione della competenza'
            ];
        }
    }
    
    /**
     * Aggiorna una competenza
     */
    public function update($skillId, $data) {
        try {
            $allowedFields = ['nome', 'descrizione', 'categoria'];
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
            
            $params[] = $skillId;
            $sql = "UPDATE competenze SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'message' => 'Competenza aggiornata con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento della competenza'
            ];
        }
    }
    
    /**
     * Elimina una competenza
     */
    public function delete($skillId) {
        try {
            $this->conn->beginTransaction();
            
            // Elimina le associazioni con i progetti
            $stmt = $this->conn->prepare("DELETE FROM competenze_progetti WHERE competenza_id = ?");
            $stmt->execute([$skillId]);
            
            // Elimina la competenza
            $stmt = $this->conn->prepare("DELETE FROM competenze WHERE id = ?");
            $stmt->execute([$skillId]);
            
            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Competenza eliminata con successo'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'eliminazione della competenza'
            ];
        }
    }
    
    /**
     * Ottiene le competenze di un utente
     */
    public function getUserSkills($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.*
                FROM competenze c
                JOIN competenze_utenti cu ON c.id = cu.competenza_id
                WHERE cu.utente_id = ?
                ORDER BY c.nome ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    
    /**
     * Aggiunge una competenza a un utente
     */
    public function addUserSkill($userId, $skillId) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO competenze_utenti (utente_id, competenza_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $skillId]);
            
            return [
                'success' => true,
                'message' => 'Competenza aggiunta con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiunta della competenza'
            ];
        }
    }
    
    /**
     * Rimuove una competenza da un utente
     */
    public function removeUserSkill($userId, $skillId) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM competenze_utenti
                WHERE utente_id = ? AND competenza_id = ?
            ");
            $stmt->execute([$userId, $skillId]);
            
            return [
                'success' => true,
                'message' => 'Competenza rimossa con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante la rimozione della competenza'
            ];
        }
    }
    
    /**
     * Ottiene le competenze richieste per un progetto
     */
    public function getProjectSkills($projectId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.*
                FROM competenze c
                JOIN competenze_progetti cp ON c.id = cp.competenza_id
                WHERE cp.progetto_id = ?
                ORDER BY c.nome ASC
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    
    /**
     * Ottiene le statistiche delle competenze
     */
    public function getStats() {
        try {
            // Ottiene il numero totale di competenze
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM competenze");
            $stmt->execute();
            $totalSkills = $stmt->fetchColumn();
            
            // Ottiene il numero di competenze per categoria
            $stmt = $this->conn->prepare("
                SELECT categoria, COUNT(*) as num_competenze
                FROM competenze
                GROUP BY categoria
                ORDER BY num_competenze DESC
            ");
            $stmt->execute();
            $skillsByCategory = $stmt->fetchAll();
            
            // Ottiene le competenze più richieste
            $stmt = $this->conn->prepare("
                SELECT c.*, COUNT(cp.progetto_id) as num_progetti
                FROM competenze c
                JOIN competenze_progetti cp ON c.id = cp.competenza_id
                GROUP BY c.id
                ORDER BY num_progetti DESC
                LIMIT 10
            ");
            $stmt->execute();
            $topSkills = $stmt->fetchAll();
            
            return [
                'total_skills' => $totalSkills,
                'skills_by_category' => $skillsByCategory,
                'top_skills' => $topSkills
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
} 