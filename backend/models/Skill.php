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
     * Gets list of skills
     */
    public function getList($filters = [], $page = 1, $perPage = 10) {
        try {
            $where = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['category'])) {
                $where[] = "category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['search'])) {
                $where[] = "(name LIKE ? OR description LIKE ?)";
                $params[] = "%{$filters['search']}%";
                $params[] = "%{$filters['search']}%";
            }
            
            // Build query
            $sql = "SELECT * FROM skills";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            // Add ordering
            $sql .= " ORDER BY name ASC";
            
            // Add pagination
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            
            // Execute query
            $stmt = $this->conn->prepare($sql);            $stmt->execute($params);
            $skills = $stmt->fetchAll();
            
            // Get total skills count
            $sql = "SELECT COUNT(*) FROM skills";
            
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
     * Gets skill details
     */
    public function getDetails($skillId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.*, COUNT(DISTINCT ps.project_id) as num_projects
                FROM skills s
                LEFT JOIN project_skills ps ON s.id = ps.skill_id
                WHERE s.id = ?
                GROUP BY s.id
            ");
            $stmt->execute([$skillId]);
            $skill = $stmt->fetch();
            
            if (!$skill) {
                return null;
            }
            
            // Get projects that require this skill
            $stmt = $this->conn->prepare("
                SELECT p.*, u.username as creator_username
                FROM projects p
                JOIN project_skills ps ON p.id = ps.project_id                JOIN users u ON p.creator_id = u.id
                WHERE ps.skill_id = ?
                AND p.end_date > NOW()
                ORDER BY p.start_date DESC
                LIMIT 5
            ");
            $stmt->execute([$skillId]);
            $skill['projects'] = $stmt->fetchAll();
            
            return $skill;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Creates a new skill
     */
    public function create($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO skills (name, description, category)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['category']
            ]);
            
            return [
                'success' => true,
                'skill_id' => $this->conn->lastInsertId(),
                'message' => 'Skill created successfully'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Error creating skill'
            ];
        }
    }
    
    /**
     * Updates a skill
     */    public function update($skillId, $data) {
        try {
            $allowedFields = ['name', 'description', 'category'];
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
                    'message' => 'No fields to update'
                ];
            }
            
            $params[] = $skillId;
            $sql = "UPDATE skills SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'message' => 'Skill updated successfully'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Error updating skill'
            ];
        }
    }
    
    /**
     * Deletes a skill
     */
    public function delete($skillId) {
        try {
            $this->conn->beginTransaction();
            
            // Delete associations with projects
            $stmt = $this->conn->prepare("DELETE FROM project_skills WHERE skill_id = ?");
            $stmt->execute([$skillId]);
            
            // Delete skill
            $stmt = $this->conn->prepare("DELETE FROM skills WHERE id = ?");
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
            $skillsByCategory = $stmt->fetchAll();            // Ottiene le competenze più richieste
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