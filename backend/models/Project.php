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

            // Verifica che l'utente sia effettivamente un creatore
            $stmt = $this->db->prepare("SELECT tipo_utente FROM utenti WHERE id = ?");
            $stmt->execute([$data['creatore_id']]);
            $user = $stmt->fetch();
            
            if (!$user || $user['tipo_utente'] !== 'creatore') {
                return [
                    'success' => false,
                    'error' => 'Solo gli utenti creatori possono creare progetti'
                ];
            }

            if (!in_array($data['tipo'], ['hardware', 'software'])) {
                return [
                    'success' => false,
                    'error' => 'Tipo progetto deve essere hardware o software'
                ];
            }

            // Validazione data limite
            $dataLimite = strtotime($data['data_limite']);
            if (!$dataLimite || $dataLimite <= time()) {
                return [
                    'success' => false,
                    'error' => 'La data limite deve essere futura'
                ];
            }

            // Validazione budget
            if (!is_numeric($data['budget_richiesto']) || $data['budget_richiesto'] <= 0) {
                return [
                    'success' => false,
                    'error' => 'Il budget richiesto deve essere un numero positivo'
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

            // Verifica che la data limite sia futura
            if (strtotime($data['data_limite']) <= time()) {
                return [
                    'success' => false,
                    'error' => 'La data limite deve essere futura'
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
     * @return array Risultato operazione
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.nickname as creatore_nickname, u.nome as creatore_nome, u.cognome as creatore_cognome
                FROM progetti p 
                JOIN utenti u ON p.creatore_id = u.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $project = $stmt->fetch();
            
            if ($project) {
                return [
                    'success' => true,
                    'project' => $project
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Progetto non trovato'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Errore del server: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Lista progetti con paginazione e filtri
     * @param int $page Pagina corrente
     * @param int $limit Numero elementi per pagina
     * @param string $tipo Tipo progetto (hardware/software)
     * @param string $stato Stato progetto
     * @param string $search Termine di ricerca
     * @return array Risultato operazione
     */
    public function getList($page = 1, $limit = 10, $tipo = null, $stato = 'aperto', $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            if ($tipo) {
                $conditions[] = "p.tipo = ?";
                $params[] = $tipo;
            }
            
            if ($stato) {
                $conditions[] = "p.stato = ?";
                $params[] = $stato;
            }
            
            if ($search) {
                $conditions[] = "(p.nome LIKE ? OR p.descrizione LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Query per i dati
            $sql = "
                SELECT p.*, u.nickname as creatore_nickname
                FROM progetti p 
                JOIN utenti u ON p.creatore_id = u.id 
                $whereClause
                ORDER BY p.data_inserimento DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $projects = $stmt->fetchAll();
            
            // Query per il conteggio totale
            $countSql = "SELECT COUNT(*) FROM progetti p $whereClause";
            $countParams = array_slice($params, 0, -2); // Rimuovi LIMIT e OFFSET
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetchColumn();
            
            return [
                'success' => true,
                'data' => [
                    'projects' => $projects,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_items' => $total,
                        'items_per_page' => $limit
                    ]
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Errore del server: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Progetti per utente specifico
     * @param int $userId ID utente
     * @param int $page Pagina corrente
     * @param int $limit Numero elementi per pagina
     * @return array Risultato operazione
     */
    public function getByUserId($userId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "
                SELECT p.*, u.nickname as creatore_nickname
                FROM progetti p 
                JOIN utenti u ON p.creatore_id = u.id 
                WHERE p.creatore_id = ?
                ORDER BY p.data_inserimento DESC 
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            $projects = $stmt->fetchAll();
            
            // Conteggio totale
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM progetti WHERE creatore_id = ?");
            $countStmt->execute([$userId]);
            $total = $countStmt->fetchColumn();
            
            return [
                'success' => true,
                'data' => [
                    'projects' => $projects,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_items' => $total,
                        'items_per_page' => $limit
                    ]
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Errore del server: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Aggiorna un progetto
     * @param int $id ID progetto
     * @param array $data Dati da aggiornare
     * @return array Risultato operazione
     */
    public function update($id, $data) {
        try {
            // Campi aggiornabili
            $allowedFields = ['nome', 'descrizione', 'budget_richiesto', 'data_limite'];
            $updateFields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'error' => 'Nessun campo da aggiornare'
                ];
            }
            
            $params[] = $id;
            $sql = "UPDATE progetti SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Progetto aggiornato con successo'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Errore durante l\'aggiornamento'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Errore del server: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Elimina un progetto
     * @param int $id ID progetto
     * @return array Risultato operazione
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM progetti WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Progetto eliminato con successo'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Progetto non trovato o non eliminato'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Errore del server: ' . $e->getMessage()
            ];
        }
    }
    
    public function getDetails($id) {
        return $this->getById($id);
    }
    
    /**
     * Lista tutti i progetti (metodo legacy)
     * @return array
     */
    public function getAll() {
        $result = $this->getList(1, 1000);
        return $result['success'] ? $result['data']['projects'] : [];
    }
    
    /**
     * Progetti per creatore (metodo legacy)
     * @param int $creatorId
     * @return array
     */
    public function getByCreator($creatorId) {
        $result = $this->getByUserId($creatorId, 1, 1000);
        return $result['success'] ? $result['data']['projects'] : [];
    }
}
?>