<?php

require_once __DIR__ . '/../utils/MongoLogger.php';

class Finanziamento {
    private $db;
    private $mongoLogger;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->mongoLogger = MongoLogger::getInstance();
    }
    
    /**
     * Crea un nuovo finanziamento
     */
    public function create($utenteId, $progettoId, $rewardId, $importo, $messaggioSupporto = null) {
        try {
            // Verifica che il progetto esista e sia aperto
            $stmt = $this->db->prepare("SELECT id, stato, budget_richiesto, budget_raccolto, creatore_id FROM progetti WHERE id = ? AND is_active = TRUE");
            $stmt->execute([$progettoId]);
            $progetto = $stmt->fetch();
            
            if (!$progetto) {
                $this->mongoLogger->logFinancing('failed', $progettoId, $utenteId, [
                    'reason' => 'project_not_found',
                    'amount' => $importo
                ]);
                return ['success' => false, 'error' => 'Progetto non trovato'];
            }
            
            if ($progetto['stato'] !== 'aperto') {
                $this->mongoLogger->logFinancing('failed', $progettoId, $utenteId, [
                    'reason' => 'project_not_open',
                    'project_status' => $progetto['stato'],
                    'amount' => $importo
                ]);
                return ['success' => false, 'error' => 'Progetto non aperto ai finanziamenti'];
            }
            
            // Verifica importo
            if ($importo <= 0) {
                $this->mongoLogger->logFinancing('failed', $progettoId, $utenteId, [
                    'reason' => 'invalid_amount',
                    'amount' => $importo
                ]);
                return ['success' => false, 'error' => 'Importo deve essere maggiore di zero'];
            }
            
            // Verifica reward se specificato
            $rewardData = null;
            if ($rewardId) {
                $stmt = $this->db->prepare(
                    "SELECT id, importo_minimo, quantita_disponibile, quantita_utilizzata 
                    FROM rewards 
                    WHERE id = ? AND progetto_id = ? AND is_active = TRUE"
                );
                $stmt->execute([$rewardId, $progettoId]);
                $reward = $stmt->fetch();
                
                if (!$reward) {
                    $this->mongoLogger->logFinancing('failed', $progettoId, $utenteId, [
                        'reason' => 'reward_not_found',
                        'reward_id' => $rewardId,
                        'amount' => $importo
                    ]);
                    return ['success' => false, 'error' => 'Reward non trovato o non disponibile'];
                }
                
                if ($importo < $reward['importo_minimo']) {
                    $this->mongoLogger->logFinancing('failed', $progettoId, $utenteId, [
                        'reason' => 'insufficient_amount_for_reward',
                        'amount' => $importo,
                        'required_amount' => $reward['importo_minimo'],
                        'reward_id' => $rewardId
                    ]);
                    return ['success' => false, 'error' => "Importo insufficiente per questa reward (minimo: €{$reward['importo_minimo']})"];
                }
                
                if ($reward['quantita_disponibile'] !== null && $reward['quantita_utilizzata'] >= $reward['quantita_disponibile']) {
                    $this->mongoLogger->logFinancing('failed', $progettoId, $utenteId, [
                        'reason' => 'reward_unavailable',
                        'reward_id' => $rewardId,
                        'amount' => $importo
                    ]);
                    return ['success' => false, 'error' => 'Reward esaurita'];
                }
                
                $rewardData = [
                    'id' => $reward['id'],
                    'min_amount' => $reward['importo_minimo'],
                    'available' => $reward['quantita_disponibile'] - $reward['quantita_utilizzata']
                ];
            }
            
            // Usa stored procedure per inserimento
            $stmt = $this->db->prepare("CALL sp_finanzia_progetto(?, ?, ?, ?, @finanziamento_id)");
            $stmt->execute([$utenteId, $progettoId, $rewardId, $importo]);
            
            $result = $this->db->query("SELECT @finanziamento_id as finanziamento_id")->fetch();
            $finanziamentoId = $result['finanziamento_id'];
            
            // Aggiorna messaggio supporto se presente
            if ($messaggioSupporto) {
                $stmt = $this->db->prepare("UPDATE finanziamenti SET messaggio_supporto = ? WHERE id = ?");
                $stmt->execute([$messaggioSupporto, $finanziamentoId]);
            }
            
            // Log successful funding
            $this->mongoLogger->logFinancing('create', $progettoId, $utenteId, [
                'financing_id' => $finanziamentoId,
                'amount' => $importo,
                'reward' => $rewardData,
                'message' => $messaggioSupporto,
                'project_creator_id' => $progetto['creatore_id']
            ]);
            
            return [
                'success' => true, 
                'data' => [
                    'finanziamento_id' => $finanziamentoId,
                    'importo' => $importo,
                    'message' => 'Finanziamento completato con successo'
                ]
            ];
            
        } catch (Exception $e) {
            $this->mongoLogger->logFinancing('failed', $progettoId, $utenteId, [
                'reason' => 'exception',
                'error' => $e->getMessage(),
                'amount' => $importo
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera finanziamenti per progetto
     */
    public function getByProgetto($progettoId, $statoPagamento = 'completed') {
        try {
            $sql = 
                "SELECT 
                    f.id,
                    f.importo,
                    f.data_finanziamento,
                    f.stato_pagamento,
                    f.messaggio_supporto,
                    u.nickname,
                    u.nome,
                    u.cognome,
                    u.avatar,
                    r.codice as reward_codice,
                    r.nome as reward_nome,
                    r.descrizione as reward_descrizione
                FROM finanziamenti f
                JOIN utenti u ON f.utente_id = u.id
                LEFT JOIN rewards r ON f.reward_id = r.id
                WHERE f.progetto_id = ?
            ";
            
            $params = [$progettoId];
            
            if ($statoPagamento) {
                $sql .= " AND f.stato_pagamento = ?";
                $params[] = $statoPagamento;
            }
            
            $sql .= " ORDER BY f.data_finanziamento DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera finanziamenti di un utente
     */
    public function getByUtente($utenteId, $limit = null) {
        try {
            $sql = 
                "SELECT 
                    f.id,
                    f.importo,
                    f.data_finanziamento,
                    f.stato_pagamento,
                    f.messaggio_supporto,
                    p.nome as progetto_nome,
                    p.tipo as progetto_tipo,
                    p.stato as progetto_stato,
                    r.codice as reward_codice,
                    r.nome as reward_nome
                FROM finanziamenti f
                JOIN progetti p ON f.progetto_id = p.id
                LEFT JOIN rewards r ON f.reward_id = r.id
                WHERE f.utente_id = ?
                ORDER BY f.data_finanziamento DESC
            ";
            
            if ($limit) {
                $sql .= " LIMIT ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$utenteId, $limit]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$utenteId]);
            }
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera singolo finanziamento
     */
    public function getById($finanziamentoId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    f.*,
                    u.nickname,
                    u.nome,
                    u.cognome,
                    p.nome as progetto_nome,
                    p.tipo as progetto_tipo,
                    r.codice as reward_codice,
                    r.nome as reward_nome,
                    r.descrizione as reward_descrizione
                FROM finanziamenti f
                JOIN utenti u ON f.utente_id = u.id
                JOIN progetti p ON f.progetto_id = p.id
                LEFT JOIN rewards r ON f.reward_id = r.id
                WHERE f.id = ?
            ");
            $stmt->execute([$finanziamentoId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Aggiorna stato finanziamento
     */
    public function updateStatus($finanziamentoId, $stato) {
        try {
            if (!in_array($stato, ['pending', 'completed', 'failed', 'refunded'])) {
                return ['success' => false, 'error' => 'Stato non valido'];
            }
            
            // Get financing details first
            $financing = $this->getById($finanziamentoId);
            
            if (!$financing) {
                $this->mongoLogger->logFinancing('status_update_failed', null, null, [
                    'reason' => 'financing_not_found',
                    'financing_id' => $finanziamentoId
                ]);
                return ['success' => false, 'error' => 'Finanziamento non trovato'];
            }
            
            // Aggiorna stato
            $stmt = $this->db->prepare("UPDATE finanziamenti SET stato_pagamento = ? WHERE id = ?");
            $stmt->execute([$stato, $finanziamentoId]);
            
            // Log status change
            $this->mongoLogger->logFinancing('status_update', $financing['progetto_id'], $financing['utente_id'], [
                'financing_id' => $finanziamentoId,
                'old_status' => $financing['stato_pagamento'],
                'new_status' => $stato,
                'amount' => $financing['importo'],
                'reward_id' => $financing['reward_id']
            ]);
            
            return ['success' => true, 'stato' => $stato];
            
        } catch (Exception $e) {
            $this->mongoLogger->logFinancing('status_update_failed', null, null, [
                'reason' => 'exception',
                'error' => $e->getMessage(),
                'financing_id' => $finanziamentoId
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiche finanziamenti per progetto
     */
    public function getStatsByProgetto($progettoId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    COUNT(f.id) as totale_finanziamenti,
                    SUM(f.importo) as totale_raccolto,
                    AVG(f.importo) as importo_medio,
                    COUNT(DISTINCT f.utente_id) as finanziatori_unici,
                    COUNT(CASE WHEN f.stato_pagamento = 'completed' THEN 1 END) as finanziamenti_completati,
                    SUM(CASE WHEN f.stato_pagamento = 'completed' THEN f.importo ELSE 0 END) as importo_completato,
                    MAX(f.data_finanziamento) as ultimo_finanziamento
                FROM finanziamenti f
                WHERE f.progetto_id = ?
            ");
            $stmt->execute([$progettoId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiche finanziamenti per utente
     */
    public function getStatsByUtente($utenteId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    COUNT(f.id) as totale_finanziamenti,
                    SUM(f.importo) as totale_speso,
                    AVG(f.importo) as importo_medio,
                    COUNT(DISTINCT f.progetto_id) as progetti_supportati,
                    MAX(f.data_finanziamento) as ultimo_finanziamento,
                    MIN(f.data_finanziamento) as primo_finanziamento
                FROM finanziamenti f
                WHERE f.utente_id = ? AND f.stato_pagamento = 'completed'
            ");
            $stmt->execute([$utenteId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Trend finanziamenti per periodo
     */
    public function getTrendByPeriodo($progettoId, $periodo = '30') {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    DATE(f.data_finanziamento) as data,
                    COUNT(f.id) as numero_finanziamenti,
                    SUM(f.importo) as totale_giornaliero
                FROM finanziamenti f
                WHERE f.progetto_id = ? 
                AND f.stato_pagamento = 'completed'
                AND f.data_finanziamento >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(f.data_finanziamento)
                ORDER BY data DESC
            ");
            $stmt->execute([$progettoId, $periodo]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Verifica se utente può finanziare progetto
     */
    public function canFinance($utenteId, $progettoId) {
        try {
            // Verifica che il progetto esista e sia aperto
            $stmt = $this->db->prepare("SELECT id, stato FROM progetti WHERE id = ? AND is_active = TRUE");
            $stmt->execute([$progettoId]);
            $progetto = $stmt->fetch();
            
            if (!$progetto) {
                return ['success' => false, 'error' => 'Progetto non trovato'];
            }
            
            if ($progetto['stato'] !== 'aperto') {
                return ['success' => false, 'error' => 'Progetto non aperto ai finanziamenti'];
            }
            
            return ['success' => true, 'can_finance' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>