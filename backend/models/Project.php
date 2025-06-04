<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Project {
    private $db;
    private $conn;
      public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
      /**
     * Creates a new project
     */
    public function create($data) {
        try {
            $this->conn->beginTransaction();
              // Insert the project
            $stmt = $this->conn->prepare("
                INSERT INTO progetti (
                    nome, descrizione, budget_richiesto, data_lancio, data_scadenza,
                    categoria_id, immagine_principale, video_presentazione, creatore_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nome'],
                $data['descrizione'],
                $data['budget_richiesto'],
                $data['data_lancio'],
                $data['data_scadenza'],
                $data['categoria_id'],
                $data['immagine_principale'] ?? null,
                $data['video_presentazione'] ?? null,
                $data['creatore_id']
            ]);
            
            $projectId = $this->conn->lastInsertId();
              // Insert required skills
            if (!empty($data['competenze'])) {
                $stmt = $this->conn->prepare("
                    INSERT INTO progetti_competenze (progetto_id, competenza_id)
                    VALUES (?, ?)
                ");
                
                foreach ($data['competenze'] as $competenzaId) {
                    $stmt->execute([$projectId, $competenzaId]);
                }
            }
              // Insert rewards
            if (!empty($data['ricompense'])) {
                $stmt = $this->conn->prepare("
                    INSERT INTO ricompense (
                        progetto_id, titolo, descrizione, importo_minimo,
                        quantita_disponibile, data_consegna
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($data['ricompense'] as $ricompensa) {
                    $stmt->execute([
                        $projectId,
                        $ricompensa['titolo'],
                        $ricompensa['descrizione'],
                        $ricompensa['importo_minimo'],
                        $ricompensa['quantita_disponibile'],
                        $ricompensa['data_consegna']
                    ]);
                }
            }
            
            $this->conn->commit();            return [
                'success' => true,
                'progetto_id' => $projectId,
                'message' => 'Progetto creato con successo'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());            return [
                'success' => false,
                'message' => 'Errore durante la creazione del progetto'
            ];
        }
    }
    
    /**
     * Gets project details
     */    public function getDetails($projectId) {
        try {
            // Get project details
            $stmt = $this->conn->prepare("
                SELECT p.*, u.nickname as creatore_nickname, u.avatar as creatore_avatar,
                       c.nome as categoria_nome
                FROM progetti p
                JOIN utenti u ON p.creatore_id = u.id
                JOIN categorie_progetti c ON p.categoria_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();
            
            if (!$project) {
                return null;
            }
            
            // Get required skills
            $stmt = $this->conn->prepare("
                SELECT c.*
                FROM competenze c
                JOIN progetti_competenze pc ON c.id = pc.competenza_id
                WHERE pc.progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $project['competenze'] = $stmt->fetchAll();
            
            // Get rewards
            $stmt = $this->conn->prepare("
                SELECT *
                FROM ricompense
                WHERE progetto_id = ?
                ORDER BY importo_minimo ASC
            ");
            $stmt->execute([$projectId]);
            $project['ricompense'] = $stmt->fetchAll();
            
            // Get total fundings
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(importo), 0) as totale_finanziamenti
                FROM finanziamenti
                WHERE progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $project['totale_finanziamenti'] = $stmt->fetchColumn();
            
            // Calculate completion percentage
            $project['percentuale_completamento'] = ($project['totale_finanziamenti'] / $project['budget_richiesto']) * 100;
            
            // Calculate remaining days
            $project['giorni_rimanenti'] = max(0, (strtotime($project['data_scadenza']) - time()) / (60 * 60 * 24));
            
            return $project;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
      /**
     * Gets list of projects
     */    public function getList($filters = [], $page = 1, $perPage = 10) {
        try {
            $where = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['categoria'])) {
                $where[] = "p.categoria_id = ?";
                $params[] = $filters['categoria'];
            }
            
            if (!empty($filters['stato'])) {
                switch ($filters['stato']) {
                    case 'attivo':
                        $where[] = "p.data_scadenza > NOW() AND p.stato = 'aperto'";
                        break;
                    case 'completato':
                        $where[] = "p.data_scadenza <= NOW() AND p.stato = 'completato'";
                        break;
                    case 'finanziato':
                        $where[] = "p.budget_raccolto >= p.budget_richiesto";
                        break;
                }
            }
            
            if (!empty($filters['competenza'])) {
                $where[] = "EXISTS (
                    SELECT 1 FROM progetti_competenze pc
                    WHERE pc.progetto_id = p.id
                    AND pc.competenza_id = ?
                )";
                $params[] = $filters['competenza'];
            }
            
            // Build query
            $sql = "
                SELECT p.*, u.nickname as creatore_nickname,
                       c.nome as categoria_nome,
                       p.budget_raccolto as totale_finanziamenti
                FROM progetti p
                JOIN utenti u ON p.creatore_id = u.id
                JOIN categorie_progetti c ON p.categoria_id = c.id
            ";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            // Add ordering
            $sql .= " ORDER BY p.data_creazione DESC";
            
            // Add pagination
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            
            // Execute query
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $projects = $stmt->fetchAll();
            
            // Get total projects count
            $countSql = "
                SELECT COUNT(*)
                FROM progetti p
                JOIN utenti u ON p.creatore_id = u.id
                JOIN categorie_progetti c ON p.categoria_id = c.id
            ";
            
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(" AND ", $where);
            }
            
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute(array_slice($params, 0, -2));
            $total = $stmt->fetchColumn();
            
            return [
                'progetti' => $projects,
                'totale' => $total,
                'pagina' => $page,
                'per_pagina' => $perPage,
                'totale_pagine' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Updates a project
     */    public function update($projectId, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Update project details
            $allowedFields = [
                'nome', 'descrizione', 'budget_richiesto', 'data_scadenza',
                'categoria_id', 'immagine_principale', 'video_presentazione'
            ];
            
            $updates = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (!empty($updates)) {
                $params[] = $projectId;
                $sql = "UPDATE progetti SET " . implode(', ', $updates) . " WHERE id = ?";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
            }
            
            // Update skills
            if (isset($data['competenze'])) {
                // Remove existing skills
                $stmt = $this->conn->prepare("DELETE FROM progetti_competenze WHERE progetto_id = ?");
                $stmt->execute([$projectId]);
                
                // Insert new skills
                if (!empty($data['competenze'])) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO progetti_competenze (progetto_id, competenza_id)
                        VALUES (?, ?)
                    ");
                    
                    foreach ($data['competenze'] as $competenzaId) {
                        $stmt->execute([$projectId, $competenzaId]);
                    }
                }
            }
            
            // Update rewards
            if (isset($data['ricompense'])) {
                // Remove existing rewards
                $stmt = $this->conn->prepare("DELETE FROM ricompense WHERE progetto_id = ?");
                $stmt->execute([$projectId]);
                
                // Insert new rewards
                if (!empty($data['ricompense'])) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO ricompense (
                            progetto_id, titolo, descrizione, importo_minimo,
                            quantita_disponibile, data_consegna
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($data['ricompense'] as $ricompensa) {
                        $stmt->execute([
                            $projectId,
                            $ricompensa['titolo'],
                            $ricompensa['descrizione'],
                            $ricompensa['importo_minimo'],
                            $ricompensa['quantita_disponibile'],
                            $ricompensa['data_consegna']
                        ]);
                    }
                }
            }
            
            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Progetto aggiornato con successo'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento del progetto'
            ];
        }
    }
    
    /**
     * Deletes a project
     */    public function delete($projectId) {
        try {
            $this->conn->beginTransaction();
            
            // Delete rewards
            $stmt = $this->conn->prepare("DELETE FROM ricompense WHERE progetto_id = ?");
            $stmt->execute([$projectId]);
            
            // Delete skills
            $stmt = $this->conn->prepare("DELETE FROM progetti_competenze WHERE progetto_id = ?");
            $stmt->execute([$projectId]);
            
            // Delete fundings
            $stmt = $this->conn->prepare("DELETE FROM finanziamenti WHERE progetto_id = ?");
            $stmt->execute([$projectId]);
            
            // Delete project
            $stmt = $this->conn->prepare("DELETE FROM progetti WHERE id = ?");
            $stmt->execute([$projectId]);
            
            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Progetto eliminato con successo'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'eliminazione del progetto'
            ];
        }
    }
    
    /**
     * Makes a funding contribution
     */    public function donate($projectId, $userId, $amount, $rewardId = null) {
        try {
            $this->conn->beginTransaction();
            
            // Check if project exists and is active
            $stmt = $this->conn->prepare("
                SELECT id, budget_richiesto
                FROM progetti
                WHERE id = ? AND data_scadenza > NOW() AND stato = 'aperto'
            ");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();
            
            if (!$project) {
                throw new Exception('Progetto non trovato o non più attivo');
            }
            
            // Verify reward if specified
            if ($rewardId) {
                $stmt = $this->conn->prepare("
                    SELECT *
                    FROM ricompense
                    WHERE id = ? AND progetto_id = ? AND quantita_disponibile > 0
                ");
                $stmt->execute([$rewardId, $projectId]);
                $reward = $stmt->fetch();
                
                if (!$reward) {
                    throw new Exception('Ricompensa non valida o non disponibile');
                }
                
                if ($amount < $reward['importo_minimo']) {
                    throw new Exception('Importo insufficiente per la ricompensa selezionata');
                }
                
                // Update available quantity
                $stmt = $this->conn->prepare("
                    UPDATE ricompense
                    SET quantita_disponibile = quantita_disponibile - 1
                    WHERE id = ?
                ");
                $stmt->execute([$rewardId]);
            }
            
            // Insert funding
            $stmt = $this->conn->prepare("
                INSERT INTO finanziamenti (progetto_id, utente_id, ricompensa_id, importo)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$projectId, $userId, $rewardId, $amount]);
            
            // Update project total funding
            $stmt = $this->conn->prepare("
                UPDATE progetti 
                SET budget_raccolto = budget_raccolto + ?
                WHERE id = ?
            ");
            $stmt->execute([$amount, $projectId]);
            
            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Finanziamento completato con successo'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Gets project fundings
     */    public function getDonations($projectId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT f.*, u.nickname, u.avatar, r.titolo as ricompensa_titolo
                FROM finanziamenti f
                JOIN utenti u ON f.utente_id = u.id
                LEFT JOIN ricompense r ON f.ricompensa_id = r.id
                WHERE f.progetto_id = ?
                ORDER BY f.data_finanziamento DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$projectId, $perPage, $offset]);
            $fundings = $stmt->fetchAll();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM finanziamenti
                WHERE progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $total = $stmt->fetchColumn();
            
            return [
                'finanziamenti' => $fundings,
                'totale' => $total,
                'pagina' => $page,
                'per_pagina' => $perPage,
                'totale_pagine' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
}