<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/CacheManager.php';

class ComponenteHardware {
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
     * Crea un nuovo componente hardware per progetto
     */
    public function create($progettoId, $nome, $descrizione, $prezzo, $quantita = 1) {
        $operationId = $this->performance->startOperation('componente_create');
        
        try {
            // Validazione input
            $validator = new Validator();
            $data = $validator->sanitize([
                'progetto_id' => $progettoId,
                'nome' => $nome,
                'descrizione' => $descrizione,
                'prezzo' => $prezzo,
                'quantita' => $quantita
            ]);
            
            $validator->required('progetto_id', $data['progetto_id'])->integer()
                     ->required('nome', $data['nome'])->minLength(3)->maxLength(200)
                     ->required('descrizione', $data['descrizione'])->minLength(10)
                     ->required('prezzo', $data['prezzo'])->numeric()->min(0.01)
                     ->required('quantita', $data['quantita'])->integer()->min(1);
            
            if (!$validator->isValid()) {
                $this->performance->endOperation($operationId);
                return ['success' => false, 'errors' => $validator->getErrors()];
            }
            
            // Verifica che il progetto sia di tipo HARDWARE
            $startTime = microtime(true);
            $stmt = $this->db->prepare("SELECT tipo_progetto FROM progetti WHERE id = ? AND tipo_progetto = 'HARDWARE'");
            $stmt->execute([$data['progetto_id']]);
            $progetto = $stmt->fetch();
            $this->performance->logQuery($stmt->queryString, [$data['progetto_id']], microtime(true) - $startTime, $operationId);
            
            if (!$progetto) {
                $this->performance->endOperation($operationId);
                return ['success' => false, 'error' => 'Progetto non trovato o non è di tipo HARDWARE'];
            }
            
            // Inserisci componente
            $startTime = microtime(true);
            $stmt = $this->db->prepare("
                INSERT INTO componenti_hardware (progetto_id, nome, descrizione, prezzo, quantita)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['progetto_id'], 
                $data['nome'], 
                $data['descrizione'], 
                $data['prezzo'], 
                $data['quantita']
            ]);
            $componenteId = $this->db->lastInsertId();
            $this->performance->logQuery($stmt->queryString, [$data['progetto_id'], $data['nome']], microtime(true) - $startTime, $operationId);
            
            // Invalida cache correlate
            $this->cache->delete("componenti_progetto_{$data['progetto_id']}");
            
            $this->security->auditAction('componente_created', [
                'componente_id' => $componenteId,
                'progetto_id' => $data['progetto_id'],
                'nome' => $data['nome'],
                'prezzo' => $data['prezzo']
            ]);
            
            $this->performance->endOperation($operationId);
            $this->logger->info('Componente hardware created successfully', [
                'componente_id' => $componenteId, 
                'progetto_id' => $data['progetto_id']
            ]);
            
            return [
                'success' => true, 
                'data' => [
                    'componente_id' => $componenteId,
                    'nome' => $data['nome'],
                    'prezzo' => $data['prezzo'],
                    'message' => 'Componente hardware creato con successo'
                ]
            ];
            
        } catch (Exception $e) {
            $this->performance->endOperation($operationId);
            $this->logger->error('Error creating componente hardware', [
                'error' => $e->getMessage(), 
                'progetto_id' => $progettoId
            ]);
            return ['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()];
        }
    }
    
    /**
     * Recupera componenti hardware per progetto
     */
    public function getByProgetto($progettoId) {
        $operationId = $this->performance->startOperation('componenti_get_by_progetto');
        
        try {
            $cacheKey = "componenti_progetto_{$progettoId}";
            $componenti = $this->cache->get($cacheKey);
            
            if ($componenti === null) {
                $startTime = microtime(true);
                $sql = "
                    SELECT 
                        ch.*,
                        (ch.prezzo * ch.quantita) as costo_totale
                    FROM componenti_hardware ch
                    WHERE ch.progetto_id = ?
                    ORDER BY ch.data_creazione DESC
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$progettoId]);
                $componenti = $stmt->fetchAll();
                $this->performance->logQuery($sql, [$progettoId], microtime(true) - $startTime, $operationId);
                
                $this->cache->set($cacheKey, $componenti, 600); // Cache per 10 minuti
            }
            
            $this->performance->endOperation($operationId);
            return ['success' => true, 'data' => $componenti];
            
        } catch (Exception $e) {
            $this->performance->endOperation($operationId);
            $this->logger->error('Error retrieving componenti', [
                'error' => $e->getMessage(), 
                'progetto_id' => $progettoId
            ]);
            return ['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()];
        }
    }
    
    /**
     * Recupera componente per ID
     */
    public function getById($componenteId) {
        try {
            $stmt = $this->db->prepare("
                SELECT ch.*, p.titolo as progetto_titolo
                FROM componenti_hardware ch
                JOIN progetti p ON ch.progetto_id = p.id
                WHERE ch.id = ?
            ");
            $stmt->execute([$componenteId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            $this->logger->error('Error retrieving componente', [
                'error' => $e->getMessage(), 
                'componente_id' => $componenteId
            ]);
            return null;
        }
    }
    
    /**
     * Aggiorna componente hardware
     */
    public function update($componenteId, $data) {
        try {
            $allowedFields = ['nome', 'descrizione', 'prezzo', 'quantita'];
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
            
            $params[] = $componenteId;
            $sql = "UPDATE componenti_hardware SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Invalida cache
                $stmt = $this->db->prepare("SELECT progetto_id FROM componenti_hardware WHERE id = ?");
                $stmt->execute([$componenteId]);
                $progetto = $stmt->fetch();
                if ($progetto) {
                    $this->cache->delete("componenti_progetto_{$progetto['progetto_id']}");
                }
                
                $this->security->auditAction('componente_updated', [
                    'componente_id' => $componenteId,
                    'fields_updated' => array_keys($data)
                ]);
                
                return ['success' => true, 'message' => 'Componente aggiornato con successo'];
            } else {
                return ['success' => false, 'error' => 'Errore durante l\'aggiornamento'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()];
        }
    }
    
    /**
     * Elimina componente hardware
     */
    public function delete($componenteId) {
        try {
            // Recupera info progetto per cache invalidation
            $stmt = $this->db->prepare("SELECT progetto_id FROM componenti_hardware WHERE id = ?");
            $stmt->execute([$componenteId]);
            $progetto = $stmt->fetch();
            
            $stmt = $this->db->prepare("DELETE FROM componenti_hardware WHERE id = ?");
            $result = $stmt->execute([$componenteId]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Invalida cache
                if ($progetto) {
                    $this->cache->delete("componenti_progetto_{$progetto['progetto_id']}");
                }
                
                $this->security->auditAction('componente_deleted', [
                    'componente_id' => $componenteId,
                    'progetto_id' => $progetto['progetto_id'] ?? null
                ]);
                
                return ['success' => true, 'message' => 'Componente eliminato con successo'];
            } else {
                return ['success' => false, 'error' => 'Componente non trovato o non eliminato'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()];
        }
    }
    
    /**
     * Calcola costo totale componenti per progetto
     */
    public function getCostoTotaleProgetto($progettoId) {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(prezzo * quantita) as costo_totale
                FROM componenti_hardware
                WHERE progetto_id = ?
            ");
            $stmt->execute([$progettoId]);
            $result = $stmt->fetch();
            
            return $result['costo_totale'] ?? 0;
            
        } catch (Exception $e) {
            $this->logger->error('Error calculating costo totale', [
                'error' => $e->getMessage(), 
                'progetto_id' => $progettoId
            ]);
            return 0;
        }
    }
    
    /**
     * Recupera statistiche componenti
     */
    public function getStatistiche() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as totale_componenti,
                    COUNT(DISTINCT progetto_id) as progetti_con_componenti,
                    AVG(prezzo) as prezzo_medio,
                    SUM(prezzo * quantita) as valore_totale_componenti
                FROM componenti_hardware
            ");
            $stmt->execute();
            return $stmt->fetch();
            
        } catch (Exception $e) {
            $this->logger->error('Error retrieving componenti statistics', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
?>
