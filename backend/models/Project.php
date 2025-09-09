<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/CacheManager.php';
require_once __DIR__ . '/../utils/MongoLogger.php';

/**
 * Gestione progetti piattaforma BOSTARTER
 * Ottimizzato con sistema avanzato di utilities
 */
class Project {
    private $db;
    private $logger;
    private $security;
    private $performance;
    private $cache;
    private $mongoLogger;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
        $this->security = SecurityManager::getInstance();
        $this->performance = PerformanceMonitor::getInstance();
        $this->cache = CacheManager::getInstance();
        $this->mongoLogger = MongoLogger::getInstance();
    }
    
    /**
     * Creazione nuovo progetto con validazione avanzata
     */
    public function create($data) {
        $operationId = $this->performance->startOperation('project_create');
        
        try {
            // Sanitizza dati input
            $validator = new Validator();
            $data = $validator->sanitize($data);
            
            // Validazione avanzata con regole specifiche
            $validationErrors = Validator::validateProjectData($data);
            if (!empty($validationErrors)) {
                $this->logger->warning('Project creation validation failed', ['errors' => $validationErrors]);
                return ['success' => false, 'errors' => $validationErrors];
            }
            
            // Verifica permessi utente con cache
            $userCacheKey = "user_permissions_{$data['creatore_id']}";
            $userPermissions = $this->cache->get($userCacheKey);
            
            if ($userPermissions === null) {
                $startTime = microtime(true);
                $stmt = $this->db->prepare("SELECT tipo_utente FROM utenti WHERE id = ?");
                $stmt->execute([$data['creatore_id']]);
                $user = $stmt->fetch();
                $this->performance->logQuery($stmt->queryString, [$data['creatore_id']], microtime(true) - $startTime, $operationId);
                
                $userPermissions = $user ? $user['tipo_utente'] : false;
                $this->cache->set($userCacheKey, $userPermissions, 1800); // Cache per 30 minuti
            }
            
            if (!$userPermissions || $userPermissions !== 'creatore') {
                $this->security->auditAction('failed_project_creation', [
                    'reason' => 'insufficient_permissions',
                    'user_id' => $data['creatore_id'],
                    'user_type' => $userPermissions
                ]);
                return [
                    'success' => false,
                    'error' => 'Per poter creare un progetto devi essere registrato come creatore'
                ];
            }
            
            // Controllo rate limiting per creazione progetti
            $rateLimitKey = "project_creation_{$data['creatore_id']}";
            if (!$this->security->checkRateLimit($rateLimitKey)) {
                return [
                    'success' => false,
                    'error' => 'Troppi progetti creati di recente. Riprova più tardi.'
                ];
            }
            
            // Inserimento progetto nel database
            $startTime = microtime(true);
            $stmt = $this->db->prepare("
                INSERT INTO progetti (titolo, descrizione, tipo_progetto, budget_richiesto, data_fine, creatore_id, stato)
                VALUES (?, ?, ?, ?, ?, ?, 'ATTIVO')
            ");
            
            $executeResult = $stmt->execute([
                $data['titolo'],
                $data['descrizione'], 
                strtoupper($data['tipo_progetto']),
                $data['budget_richiesto'],
                $data['data_fine'],
                $data['creatore_id']
            ]);
            
            $this->performance->logQuery($stmt->queryString, [], microtime(true) - $startTime, $operationId);
            
            if (!$executeResult) {
                throw new Exception('Errore durante l\'inserimento del progetto');
            }
            
            $projectId = $this->db->lastInsertId();
            
            // Invalida cache correlate
            $this->cache->delete($userCacheKey);
            $this->cache->delete('projects_active');
            $this->cache->delete("user_projects_{$data['creatore_id']}");
            
            // Audit trail
            $this->security->auditAction('project_created', [
                'project_id' => $projectId,
                'creator_id' => $data['creatore_id'],
                'tipo_progetto' => $data['tipo_progetto']
            ]);
            
            $this->performance->endOperation($operationId);
            $this->logger->info('Project created successfully', [
                'project_id' => $projectId,
                'creator_id' => $data['creatore_id']
            ]);
            
            return [
                'success' => true,
                'project_id' => $projectId,
                'message' => 'Progetto creato con successo'
            ];
        } catch (Exception $e) {
            $this->performance->endOperation($operationId);
            $this->logger->error('Project creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => 'Errore interno del server'
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
     */
    public function getList($page = 1, $limit = 10, $tipo = null, $stato = 'aperto', $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            if ($tipo) {
                $conditions[] = "UPPER(p.tipo_progetto) = UPPER(?)";
                $params[] = $tipo;
            }
            
            if ($stato) {
                // Mappa 'aperto'/'chiuso' ai valori di sistema
                $mappedStato = $stato;
                if (strtolower($stato) === 'aperto') { $mappedStato = 'ATTIVO'; }
                if (strtolower($stato) === 'chiuso') { $mappedStato = 'CHIUSO'; }
                $conditions[] = "p.stato = ?";
                $params[] = $mappedStato;
            }
            
            if ($search) {
                $conditions[] = "(p.titolo LIKE ? OR p.descrizione LIKE ?)";
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
            $allowedFields = ['titolo', 'descrizione', 'budget_richiesto', 'data_fine'];
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
    public function getAll($filters = []) {
        // Se sono forniti filtri, usa getList con mapping dei parametri
        if (!empty($filters)) {
            $page = isset($filters['page']) ? (int)$filters['page'] : 1;
            $limit = isset($filters['limit']) ? (int)$filters['limit'] : 100;
            $tipo = $filters['tipo'] ?? null;
            $stato = $filters['stato'] ?? null;
            $search = $filters['search'] ?? '';
            return $this->getList($page, $limit, $tipo, $stato, $search);
        }
        // Legacy comportamento: restituisce array semplice
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