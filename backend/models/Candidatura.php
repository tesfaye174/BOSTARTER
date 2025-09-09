<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/CacheManager.php';

class Candidatura {
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
     * Crea una nuova candidatura
     */
    public function create($utenteId, $profiloId, $motivazione) {
        $operationId = $this->performance->startOperation('candidatura_create');
        
        try {
            // Validazione input
            $validator = new Validator();
            $data = $validator->sanitize([
                'utente_id' => $utenteId,
                'profilo_id' => $profiloId,
                'motivazione' => $motivazione
            ]);
            
            $validator->required('utente_id', $data['utente_id'])->integer()
                     ->required('profilo_id', $data['profilo_id'])->integer()
                     ->required('motivazione', $data['motivazione'])->minLength(10)->maxLength(1000);
            
            if (!$validator->isValid()) {
                $this->logger->warning('Candidatura creation validation failed', ['errors' => $validator->getErrors()]);
                return ['success' => false, 'errors' => $validator->getErrors()];
            }
            
            // Verifica che il profilo esista e sia per progetto software con cache
            $profileCacheKey = "profile_details_{$data['profilo_id']}";
            $profilo = $this->cache->get($profileCacheKey);
            
            if ($profilo === null) {
                $startTime = microtime(true);
                $sql = "SELECT pr.*, p.tipo_progetto, p.stato FROM profili_richiesti pr JOIN progetti p ON pr.progetto_id = p.id WHERE pr.id = ? AND pr.is_active = TRUE";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data['profilo_id']]);
                $profilo = $stmt->fetch();
                $this->performance->logQuery($sql, [$data['profilo_id']], microtime(true) - $startTime, $operationId);
                $this->cache->set($profileCacheKey, $profilo ?: false, 600); // Cache per 10 minuti
            }
            
            if (!$profilo) {
                $this->security->auditAction('failed_candidatura_creation', ['reason' => 'profile_not_found', 'profilo_id' => $data['profilo_id']]);
                return ['success' => false, 'error' => 'Profilo non trovato o non attivo'];
            }
            
            if ($profilo['tipo_progetto'] !== 'SOFTWARE') {
                $this->security->auditAction('failed_candidatura_creation', ['reason' => 'wrong_project_type', 'tipo_progetto' => $profilo['tipo_progetto']]);
                return ['success' => false, 'error' => 'Candidature solo per progetti software'];
            }
            
            if ($profilo['stato'] !== 'ATTIVO') {
                return ['success' => false, 'error' => 'Progetto non aperto alle candidature'];
            }
            
            // Verifica che non ci sia già una candidatura con cache
            $candidaturaCacheKey = "candidatura_exists_{$data['utente_id']}_{$data['profilo_id']}";
            $candidaturaExists = $this->cache->get($candidaturaCacheKey);
            
            if ($candidaturaExists === null) {
                $startTime = microtime(true);
                $stmt = $this->db->prepare("SELECT id FROM candidature WHERE utente_id = ? AND profilo_id = ?");
                $stmt->execute([$data['utente_id'], $data['profilo_id']]);
                $candidaturaExists = $stmt->fetch() ? true : false;
                $this->performance->logQuery($stmt->queryString, [$data['utente_id'], $data['profilo_id']], microtime(true) - $startTime, $operationId);
                $this->cache->set($candidaturaCacheKey, $candidaturaExists, 300);
            }
            
            if ($candidaturaExists) {
                $this->security->auditAction('failed_candidatura_creation', ['reason' => 'duplicate_candidatura']);
                return ['success' => false, 'error' => 'Candidatura già inviata per questo profilo'];
            }
            
            // Usa stored procedure per verifica skill e inserimento
            $startTime = microtime(true);
            $stmt = $this->db->prepare("CALL sp_candidati_profilo(?, ?, ?)");
            $stmt->execute([$data['utente_id'], $data['profilo_id'], $data['motivazione']]);
            $this->performance->logQuery('CALL sp_candidati_profilo', [$data['utente_id'], $data['profilo_id']], microtime(true) - $startTime, $operationId);
            
            // Invalida cache correlate
            $this->cache->delete($candidaturaCacheKey);
            $this->cache->delete($profileCacheKey);
            
            $this->security->auditAction('candidatura_created', [
                'utente_id' => $data['utente_id'],
                'profilo_id' => $data['profilo_id']
            ]);
            
            $this->performance->endOperation($operationId);
            $this->logger->info('Candidatura created successfully', ['utente_id' => $data['utente_id'], 'profilo_id' => $data['profilo_id']]);
            
            return ['success' => true, 'message' => 'Candidatura inviata con successo'];
            
        } catch (Exception $e) {
            $this->performance->endOperation($operationId);
            $this->logger->error('Candidatura creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'error' => 'Errore interno del server'];
        }
    }
    
    /**
     * Recupera candidature per profilo
     */
    public function getByProfilo($profiloId, $creatoreId = null) {
        $operationId = $this->performance->startOperation('candidature_get_by_profilo');
        
        try {
            // Validazione input
            $validator = new Validator();
            $validator->required('profilo_id', $profiloId)->integer();
            
            if ($creatoreId !== null) {
                $validator->required('creatore_id', $creatoreId)->integer();
            }
            
            if (!$validator->isValid()) {
                return ['success' => false, 'errors' => $validator->getErrors()];
            }
            
            // Cache key basata sui parametri
            $cacheKey = "candidature_profilo_{$profiloId}" . ($creatoreId ? "_creator_{$creatoreId}" : '');
            $candidature = $this->cache->get($cacheKey);
            
            if ($candidature === null) {
                $sql = "
                    SELECT 
                        c.*,
                        u.nickname,
                        u.nome,
                        u.cognome,
                        u.email,
                        pr.nome as profilo_nome,
                        p.titolo as progetto_nome
                    FROM candidature c
                    JOIN utenti u ON c.utente_id = u.id
                    JOIN profili_richiesti pr ON c.profilo_id = pr.id
                    JOIN progetti p ON pr.progetto_id = p.id
                    WHERE c.profilo_id = ?
                ";
                
                $params = [$profiloId];
                
                // Se specificato creatore, filtra per progetto
                if ($creatoreId) {
                    $sql .= " AND p.creatore_id = ?";
                    $params[] = $creatoreId;
                }
                
                $sql .= " ORDER BY c.data_candidatura DESC";
                
                $startTime = microtime(true);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $candidature = $stmt->fetchAll();
                $this->performance->logQuery($sql, $params, microtime(true) - $startTime, $operationId);
                
                $this->cache->set($cacheKey, $candidature, 600); // Cache per 10 minuti
            }
            
            $this->performance->endOperation($operationId);
            $this->logger->info('Candidature retrieved successfully', ['profilo_id' => $profiloId, 'count' => count($candidature)]);
            
            return ['success' => true, 'data' => $candidature];
            
        } catch (Exception $e) {
            $this->performance->endOperation($operationId);
            $this->logger->error('Failed to retrieve candidature', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Errore interno del server'];
        }
    }
    
    /**
     * Recupera candidature di un utente
     */
    public function getByUtente($utenteId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.*,
                    pr.nome as profilo_nome,
                    p.titolo as progetto_nome,
                    p.tipo_progetto as progetto_tipo,
                    p.stato as progetto_stato
                FROM candidature c
                JOIN profili_richiesti pr ON c.profilo_id = pr.id
                JOIN progetti p ON pr.progetto_id = p.id
                WHERE c.utente_id = ?
                ORDER BY c.data_candidatura DESC
            ");
            $stmt->execute([$utenteId]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Aggiorna stato candidatura
     */
    public function updateStatus($candidaturaId, $stato, $valutatoreId) {
        try {
            if (!in_array($stato, ['accettata', 'rifiutata', 'in_valutazione'])) {
                return ['success' => false, 'error' => 'Stato non valido'];
            }
            
            // Verifica permessi
            $stmt = $this->db->prepare("
                SELECT c.*, p.creatore_id
                FROM candidature c
                JOIN profili_richiesti pr ON c.profilo_id = pr.id
                JOIN progetti p ON pr.progetto_id = p.id
                WHERE c.id = ?
            ");
            $stmt->execute([$candidaturaId]);
            $candidatura = $stmt->fetch();
            
            if (!$candidatura) {
                return ['success' => false, 'error' => 'Candidatura non trovata'];
            }
            
            // Aggiorna stato
            $stmt = $this->db->prepare("
                UPDATE candidature 
                SET stato = ?, data_valutazione = NOW(), valutatore_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$stato, $valutatoreId, $candidaturaId]);
            
            // Se accettata, aggiorna posizioni occupate
            if ($stato === 'accettata') {
                $stmt = $this->db->prepare("
                    UPDATE profili_richiesti 
                    SET posizioni_occupate = posizioni_occupate + 1
                    WHERE id = ?
                ");
                $stmt->execute([$candidatura['profilo_id']]);
            }
            
            return ['success' => true, 'stato' => $stato];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cancella candidatura
     */
    public function delete($candidaturaId, $utenteId, $isAdmin = false) {
        try {
            // Verifica permessi
            $stmt = $this->db->prepare("SELECT utente_id FROM candidature WHERE id = ?");
            $stmt->execute([$candidaturaId]);
            $candidatura = $stmt->fetch();
            
            if (!$candidatura) {
                return ['success' => false, 'error' => 'Candidatura non trovata'];
            }
            
            if ($candidatura['utente_id'] != $utenteId && !$isAdmin) {
                return ['success' => false, 'error' => 'Accesso negato'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM candidature WHERE id = ?");
            $stmt->execute([$candidaturaId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Verifica se utente può candidarsi a profilo
     */
    public function canApply($utenteId, $profiloId) {
        try {
            // Recupera skill richieste dal profilo
            $stmt = $this->db->prepare("
                SELECT sp.competenza_id, sp.livello_richiesto
                FROM skill_profili sp
                WHERE sp.profilo_id = ?
            ");
            $stmt->execute([$profiloId]);
            $skillRichiesti = $stmt->fetchAll();
            
            if (empty($skillRichiesti)) {
                return ['success' => false, 'error' => 'Profilo senza skill richieste'];
            }
            
            // Verifica skill utente
            foreach ($skillRichiesti as $skill) {
                $stmt = $this->db->prepare(
                    SELECT livello FROM utenti_competenze 
                    WHERE utente_id = ? AND competenza_id = ?
                );
                $stmt->execute([$utenteId, $skill['competenza_id']]);
                $skillUtente = $stmt->fetch();
                
                if (!$skillUtente || $skillUtente['livello'] < $skill['livello_richiesto']) {
                    return [
                        'success' => false, 
                        'error' => 'Skill insufficienti per questo profilo'
                    ];
                }
            }
            
            return ['success' => true, 'can_apply' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiche candidature per progetto
     */
    public function getStatsByProgetto($progettoId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    pr.nome as profilo_nome,
                    COUNT(c.id) as totale_candidature,
                    COUNT(CASE WHEN c.stato = 'accettata' THEN 1 END) as accettate,
                    COUNT(CASE WHEN c.stato = 'rifiutata' THEN 1 END) as rifiutate,
                    COUNT(CASE WHEN c.stato = 'in_valutazione' THEN 1 END) as in_valutazione,
                    pr.numero_posizioni,
                    pr.posizioni_occupate
                FROM profili_richiesti pr
                LEFT JOIN candidature c ON pr.id = c.profilo_id
                WHERE pr.progetto_id = ?
                GROUP BY pr.id, pr.nome, pr.numero_posizioni, pr.posizioni_occupate
            ");
            $stmt->execute([$progettoId]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>