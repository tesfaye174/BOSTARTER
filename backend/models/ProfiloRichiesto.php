<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/CacheManager.php';

class ProfiloRichiesto {
    private $db;
    private $logger;
    private $security;
    private $performance;
    private $cache;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
        $this->security = SecurityManager::getInstance();
        $this->performance = PerformanceMonitor::getInstance();
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Crea un nuovo profilo richiesto per progetto software
     */
    public function create($progettoId, $nome, $descrizione, $numeroPostazioni = 1, $skillRichieste = []) {
        $operationId = $this->performance->startOperation('profilo_create');
        
        try {
            // Validazione input
            $validator = new Validator();
            $data = $validator->sanitize([
                'progetto_id' => $progettoId,
                'nome' => $nome,
                'descrizione' => $descrizione,
                'numero_posizioni' => $numeroPostazioni
            ]);
            
            $validator->required('progetto_id', $data['progetto_id'])->integer()
                     ->required('nome', $data['nome'])->minLength(3)->maxLength(200)
                     ->required('descrizione', $data['descrizione'])->minLength(10)
                     ->required('numero_posizioni', $data['numero_posizioni'])->integer()->min(1);
            
            if (!$validator->isValid()) {
                $this->performance->endOperation($operationId);
                return ['success' => false, 'errors' => $validator->getErrors()];
            }
            
            // Verifica che il progetto sia di tipo SOFTWARE
            $startTime = microtime(true);
            $stmt = $this->db->prepare("SELECT tipo_progetto FROM progetti WHERE id = ? AND tipo_progetto = 'SOFTWARE'");
            $stmt->execute([$data['progetto_id']]);
            $progetto = $stmt->fetch();
            $this->performance->logQuery($stmt->queryString, [$data['progetto_id']], microtime(true) - $startTime, $operationId);
            
            if (!$progetto) {
                $this->performance->endOperation($operationId);
                return ['success' => false, 'error' => 'Progetto non trovato o non è di tipo SOFTWARE'];
            }
            
            $this->db->beginTransaction();
            
            // Inserisci profilo richiesto
            $startTime = microtime(true);
            $stmt = $this->db->prepare("
                INSERT INTO profili_richiesti (progetto_id, nome, descrizione, numero_posizioni)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$data['progetto_id'], $data['nome'], $data['descrizione'], $data['numero_posizioni']]);
            $profiloId = $this->db->lastInsertId();
            $this->performance->logQuery($stmt->queryString, [$data['progetto_id'], $data['nome']], microtime(true) - $startTime, $operationId);
            
            // Inserisci skill richieste se presenti
            if (!empty($skillRichieste)) {
                foreach ($skillRichieste as $skill) {
                    if (isset($skill['competenza_id']) && isset($skill['livello_richiesto'])) {
                        $startTime = microtime(true);
                        $stmt = $this->db->prepare("
                            INSERT INTO skill_profili (profilo_id, competenza_id, livello_richiesto, obbligatoria)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $profiloId, 
                            $skill['competenza_id'], 
                            $skill['livello_richiesto'],
                            $skill['obbligatoria'] ?? true
                        ]);
                        $this->performance->logQuery($stmt->queryString, [$profiloId, $skill['competenza_id']], microtime(true) - $startTime, $operationId);
                    }
                }
            }
            
            $this->db->commit();
            
            // Invalida cache correlate
            $this->cache->delete("profili_progetto_{$data['progetto_id']}");
            
            $this->security->auditAction('profilo_created', [
                'profilo_id' => $profiloId,
                'progetto_id' => $data['progetto_id'],
                'nome' => $data['nome']
            ]);
            
            $this->performance->endOperation($operationId);
            $this->logger->info('Profilo richiesto created successfully', ['profilo_id' => $profiloId, 'progetto_id' => $data['progetto_id']]);
            
            return [
                'success' => true, 
                'data' => [
                    'profilo_id' => $profiloId,
                    'nome' => $data['nome'],
                    'message' => 'Profilo richiesto creato con successo'
                ]
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->performance->endOperation($operationId);
            $this->logger->error('Error creating profilo richiesto', ['error' => $e->getMessage(), 'progetto_id' => $progettoId]);
            return ['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()];
        }
    }
    
    /**
     * Recupera profili richiesti per progetto
     */
    public function getByProgetto($progettoId) {
        $operationId = $this->performance->startOperation('profili_get_by_progetto');
        
        try {
            $cacheKey = "profili_progetto_{$progettoId}";
            $profili = $this->cache->get($cacheKey);
            
            if ($profili === null) {
                $startTime = microtime(true);
                $sql = "
                    SELECT 
                        pr.*,
                        COUNT(c.id) as candidature_ricevute,
                        COUNT(CASE WHEN c.stato = 'ACCETTATA' THEN 1 END) as candidature_accettate
                    FROM profili_richiesti pr
                    LEFT JOIN candidature c ON pr.id = c.profilo_id
                    WHERE pr.progetto_id = ?
                    GROUP BY pr.id, pr.nome, pr.numero_posizioni, pr.posizioni_occupate
                    ORDER BY pr.data_creazione DESC
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$progettoId]);
                $profili = $stmt->fetchAll();
                $this->performance->logQuery($sql, [$progettoId], microtime(true) - $startTime, $operationId);
                
                // Recupera skill per ogni profilo
                foreach ($profili as &$profilo) {
                    $startTime = microtime(true);
                    $stmt = $this->db->prepare("
                        SELECT sp.*, c.nome as competenza_nome
                        FROM skill_profili sp
                        JOIN competenze c ON sp.competenza_id = c.id
                        WHERE sp.profilo_id = ?
                        ORDER BY sp.obbligatoria DESC, c.nome
                    ");
                    $stmt->execute([$profilo['id']]);
                    $profilo['skill_richieste'] = $stmt->fetchAll();
                    $this->performance->logQuery($stmt->queryString, [$profilo['id']], microtime(true) - $startTime, $operationId);
                }
                
                $this->cache->set($cacheKey, $profili, 600); // Cache per 10 minuti
            }
            
            $this->performance->endOperation($operationId);
            return ['success' => true, 'data' => $profili];
            
        } catch (Exception $e) {
            $this->performance->endOperation($operationId);
            $this->logger->error('Error retrieving profili', ['error' => $e->getMessage(), 'progetto_id' => $progettoId]);
            return ['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()];
        }
    }
    
    /**
     * Recupera profilo per ID con skill
     */
    public function getById($profiloId) {
        try {
            $stmt = $this->db->prepare("
                SELECT pr.*, p.titolo as progetto_titolo
                FROM profili_richiesti pr
                JOIN progetti p ON pr.progetto_id = p.id
                WHERE pr.id = ?
            ");
            $stmt->execute([$profiloId]);
            $profilo = $stmt->fetch();
            
            if ($profilo) {
                // Recupera skill richieste
                $stmt = $this->db->prepare("
                    SELECT sp.*, c.nome as competenza_nome
                    FROM skill_profili sp
                    JOIN competenze c ON sp.competenza_id = c.id
                    WHERE sp.profilo_id = ?
                    ORDER BY sp.obbligatoria DESC, c.nome
                ");
                $stmt->execute([$profiloId]);
                $profilo['skill_richieste'] = $stmt->fetchAll();
            }
            
            return $profilo;
            
        } catch (Exception $e) {
            $this->logger->error('Error retrieving profilo', ['error' => $e->getMessage(), 'profilo_id' => $profiloId]);
            return null;
        }
    }
    
    /**
     * Aggiorna profilo richiesto
     */
    public function update($profiloId, $data) {
        try {
            $allowedFields = ['nome', 'descrizione', 'numero_posizioni'];
            $updateFields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                return ['success' => false, 'error' => 'Nessun campo da aggiornare'];
            }
            
            $params[] = $profiloId;
            $sql = "UPDATE profili_richiesti SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Invalida cache
                $stmt = $this->db->prepare("SELECT progetto_id FROM profili_richiesti WHERE id = ?");
                $stmt->execute([$profiloId]);
                $progetto = $stmt->fetch();
                if ($progetto) {
                    $this->cache->delete("profili_progetto_{$progetto['progetto_id']}");
                }
                
                return ['success' => true, 'message' => 'Profilo aggiornato con successo'];
            } else {
                return ['success' => false, 'error' => 'Errore durante l\'aggiornamento'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()];
        }
    }
    
    /**
     * Elimina profilo richiesto
     */
    public function delete($profiloId) {
        try {
            // Verifica se ci sono candidature associate
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM candidature WHERE profilo_id = ?");
            $stmt->execute([$profiloId]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return ['success' => false, 'error' => 'Impossibile eliminare profilo con candidature associate'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM profili_richiesti WHERE id = ?");
            $result = $stmt->execute([$profiloId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Profilo eliminato con successo'];
            } else {
                return ['success' => false, 'error' => 'Profilo non trovato o non eliminato'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()];
        }
    }
}
?>
