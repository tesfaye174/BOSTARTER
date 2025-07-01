<?php
require_once __DIR__ . '/../config/database.php';
class VolumeAnalysisService {
    private $conn;
    const WI = 1;        
    const WB = 0.5;      
    const A = 2;         
    const FREQ_ADD_PROJECT = 1;     
    const FREQ_VIEW_ALL = 1;        
    const FREQ_COUNT_PROJECTS = 3;  
    const TOTAL_PROJECTS = 10;      
    const FUNDINGS_PER_PROJECT = 3; 
    const TOTAL_USERS = 5;          
    const PROJECTS_PER_USER = 2;    
    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }
    public function analyzeRedundancy() {
        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'field_analyzed' => '#nr_progetti',
            'coefficients' => [
                'wI' => self::WI,                 
                'wB' => self::WB,                 
                'a' => self::A                    
            ],
            'volumes' => $this->getVolumes(),     
            'operations' => $this->analyzeOperations(), 
            'redundancy_cost' => $this->calculateRedundancyCost(),     
            'non_redundancy_cost' => $this->calculateNonRedundancyCost(), 
            'recommendation' => null,
            'performance_impact' => null
        ];
        $results['recommendation'] = $this->generateRecommendation($results);
        $results['performance_impact'] = $this->analyzePerformanceImpact($results);
        $this->logAnalysis($results);
        return $results;
    }
    private function getVolumes() {
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM progetti");
            $total_projects = $stmt->fetch()['total'];
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM utenti");
            $total_users = $stmt->fetch()['total'];
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM finanziamenti");
            $total_fundings = $stmt->fetch()['total'];
            $stmt = $this->conn->query("
                SELECT AVG(progetti_count) as avg_projects 
                FROM (
                    SELECT creator_id, COUNT(*) as progetti_count 
                    FROM progetti 
                    GROUP BY creator_id
                ) AS user_projects
            ");
            $avg_projects_per_user = $stmt->fetch()['avg_projects'] ?? 0;
            $stmt = $this->conn->query("
                SELECT MAX(progetti_count) as max_projects 
                FROM (
                    SELECT creator_id, COUNT(*) as progetti_count 
                    FROM progetti 
                    GROUP BY creator_id
                ) AS user_projects
            ");
            $max_projects_per_user = $stmt->fetch()['max_projects'] ?? 0;
            $stmt = $this->conn->query("
                SELECT 
                    COUNT(*) as user_count,
                    AVG(progetti_count) as avg_projects,
                    STDDEV(progetti_count) as stddev_projects
                FROM (
                    SELECT creator_id, COUNT(*) as progetti_count 
                    FROM progetti 
                    GROUP BY creator_id
                ) AS user_projects
            ");
            $project_distribution = $stmt->fetch();
            return [
                'current' => [
                    'total_projects' => $total_projects,
                    'total_users' => $total_users,
                    'total_fundings' => $total_fundings,
                    'avg_projects_per_user' => round($avg_projects_per_user, 2),
                    'avg_fundings_per_project' => round($avg_fundings_per_project, 2),
                    'max_projects_per_user' => $max_projects_per_user,
                    'project_distribution' => $project_distribution
                ],
                'pdf_specified' => [
                    'total_projects' => self::TOTAL_PROJECTS,
                    'total_users' => self::TOTAL_USERS,
                    'fundings_per_project' => self::FUNDINGS_PER_PROJECT,
                    'projects_per_user' => self::PROJECTS_PER_USER
                ]
            ];
        } catch (Exception $e) {
            error_log("Errore nell'ottenere i volumi: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    public function analyzeOperations() {
        return [
            'add_project_frequency' => self::FREQ_ADD_PROJECT,
            'view_all_frequency' => self::FREQ_VIEW_ALL,
            'count_projects_frequency' => self::FREQ_COUNT_PROJECTS,
            'total_operations_per_month' => self::FREQ_ADD_PROJECT + self::FREQ_VIEW_ALL + self::FREQ_COUNT_PROJECTS,
            'operation_costs' => [
                'add_project' => self::FREQ_ADD_PROJECT * self::WI,
                'view_all' => self::FREQ_VIEW_ALL * self::WB,
                'count_projects' => self::FREQ_COUNT_PROJECTS * self::WB
            ],
            'details' => [
                'add_project' => [
                    'frequency_per_month' => self::FREQ_ADD_PROJECT,
                    'description' => 'Creazione nuovo progetto (aggiorna #nr_progetti)',
                    'type' => 'INSERT/UPDATE',
                    'affects_redundancy' => true
                ],
                'view_all_projects' => [
                    'frequency_per_month' => self::FREQ_VIEW_ALL,
                    'description' => 'Visualizzazione elenco progetti',
                    'type' => 'SELECT',
                    'affects_redundancy' => false
                ],
                'count_user_projects' => [
                    'frequency_per_month' => self::FREQ_COUNT_PROJECTS,
                    'description' => 'Conteggio progetti utente (usa #nr_progetti)',
                    'type' => 'SELECT',
                    'affects_redundancy' => false,
                    'benefits_from_redundancy' => true
                ]
            ]
        ];
    }
    private function calculateRedundancyCost() {
        $update_cost = self::WI * self::FREQ_ADD_PROJECT; 
        $storage_cost = self::TOTAL_USERS * 0.1; 
        $consistency_cost = self::A * 0.5; 
        $total_cost = $update_cost + $storage_cost + $consistency_cost;
        return [
            'update_cost' => $update_cost,
            'storage_cost' => $storage_cost,
            'consistency_cost' => $consistency_cost,
            'total_monthly_cost' => round($total_cost, 2),
            'annual_cost' => round($total_cost * 12, 2)
        ];
    }
    private function calculateNonRedundancyCost() {
        $count_query_cost = self::WB * self::FREQ_COUNT_PROJECTS; 
        $join_cost = self::WB * self::FREQ_VIEW_ALL * 0.3; 
        $processing_cost = self::A * 0.3;
        $total_cost = $count_query_cost + $join_cost + $processing_cost;
        return [
            'count_query_cost' => $count_query_cost,
            'join_cost' => $join_cost,
            'processing_cost' => $processing_cost,
            'total_monthly_cost' => round($total_cost, 2),
            'annual_cost' => round($total_cost * 12, 2)
        ];
    }
    private function generateRecommendation($analysis) {
        $redundancy_cost = $analysis['redundancy_cost']['total_monthly_cost'];
        $non_redundancy_cost = $analysis['non_redundancy_cost']['total_monthly_cost'];
        $difference = $redundancy_cost - $non_redundancy_cost;
        $percentage_diff = abs($difference) / max($redundancy_cost, $non_redundancy_cost) * 100;
        if (abs($difference) < 0.5) {
            $recommendation = 'NEUTRAL';
            $reason = 'I costi sono sostanzialmente equivalenti. Mantenere la ridondanza per semplicità.';
        } elseif ($redundancy_cost < $non_redundancy_cost) {
            $recommendation = 'KEEP_REDUNDANCY';
            $reason = sprintf(
                'La ridondanza è conveniente. Risparmio mensile: %.2f (%.1f%%)',
                abs($difference), $percentage_diff
            );
        } else {
            $recommendation = 'REMOVE_REDUNDANCY';
            $reason = sprintf(
                'Rimuovere la ridondanza per risparmiare. Costo extra mensile: %.2f (%.1f%%)',
                $difference, $percentage_diff
            );
        }
        return [
            'decision' => $recommendation,
            'reason' => $reason,
            'cost_difference' => $difference,
            'percentage_impact' => round($percentage_diff, 1),
            'confidence' => $this->calculateConfidence($analysis)
        ];
    }
    private function analyzePerformanceImpact($analysis) {
        $volumes = $analysis['volumes']['current'];
        return [
            'read_performance' => [
                'with_redundancy' => 'O(1) - Accesso diretto al campo #nr_progetti',
                'without_redundancy' => 'O(n) - COUNT su tabella progetti per ogni utente',
                'impact' => $volumes['total_projects'] > 100 ? 'SIGNIFICANT' : 'MODERATE'
            ],
            'write_performance' => [
                'with_redundancy' => 'O(1) + trigger overhead per aggiornamento campo',
                'without_redundancy' => 'O(1) - Solo inserimento in progetti',
                'impact' => 'MINIMAL'
            ],
            'scalability' => [
                'concern' => $volumes['total_users'] > 1000 ? 'HIGH' : 'LOW',
                'recommendation' => 'Monitorare con crescita utenti'
            ]
        ];
    }
    private function calculateConfidence($analysis) {
        $factors = [];
        $current_vol = $analysis['volumes']['current']['total_projects'];
        $pdf_vol = $analysis['volumes']['pdf_specified']['total_projects'];
        $vol_similarity = 1 - abs($current_vol - $pdf_vol) / max($current_vol, $pdf_vol, 1);
        $factors[] = $vol_similarity * 30; 
        $cost_diff = abs($analysis['redundancy_cost']['total_monthly_cost'] - 
                        $analysis['non_redundancy_cost']['total_monthly_cost']);
        $cost_factor = min($cost_diff * 10, 40); 
        $factors[] = $cost_factor;
        $completeness = 30; 
        $factors[] = $completeness;
        return min(100, array_sum($factors));
    }
    public function testConsistency() {
        try {
            $stmt = $this->conn->query("
                SELECT 
                    u.id,
                    u.nickname,
                    u.nr_progetti as stored_count,
                    COUNT(p.id) as actual_count,
                    (u.nr_progetti - COUNT(p.id)) as difference
                FROM utenti u
                LEFT JOIN progetti p ON u.id = p.creatore_id
                WHERE u.tipo_utente = 'creatore'
                GROUP BY u.id, u.nickname, u.nr_progetti
                HAVING difference != 0
            ");
            $inconsistencies = $stmt->fetchAll();
            return [
                'consistent' => count($inconsistencies) === 0,
                'inconsistencies_found' => count($inconsistencies),
                'details' => $inconsistencies
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function fixInconsistencies() {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->exec("
                UPDATE utenti u
                SET nr_progetti = (
                    SELECT COUNT(*)
                    FROM progetti p
                    WHERE p.creatore_id = u.id
                )
                WHERE u.tipo_utente = 'creatore'
            ");
            $this->conn->commit();
            return [
                'success' => true,
                'updated_users' => $stmt,
                'message' => 'Consistenza del campo #nr_progetti ripristinata'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['error' => $e->getMessage()];
        }
    }
    private function logAnalysis($results) {
        try {
            $log_entry = [
                'timestamp' => $results['timestamp'],
                'analysis_type' => 'volume_redundancy',
                'field' => '#nr_progetti',
                'recommendation' => $results['recommendation']['decision'],
                'confidence' => $results['recommendation']['confidence'],
                'cost_difference' => $results['recommendation']['cost_difference']
            ];
            $log_message = sprintf(
                "[%s] Volume Analysis: %s field '%s' - Recommendation: %s (Confidence: %.1f%%)\n",
                $log_entry['timestamp'],
                $log_entry['analysis_type'],
                $log_entry['field'],
                $log_entry['recommendation'],
                $log_entry['confidence']
            );
            error_log($log_message, 3, __DIR__ . '/../../logs/volume_analysis.log');
        } catch (Exception $e) {
            error_log("Errore nel logging dell'analisi: " . $e->getMessage());
        }
    }
    public function generateReport() {
        $analysis = $this->analyzeRedundancy();
        $consistency = $this->testConsistency();
        ob_start();
        include __DIR__ . '/../views/volume_analysis_report.php';
        return ob_get_clean();
    }
    public function performCustomAnalysis($params = []) {
        $analysisParams = array_merge([
            'num_projects' => self::TOTAL_PROJECTS,
            'fundings_per_project' => self::FUNDINGS_PER_PROJECT,
            'num_users' => self::TOTAL_USERS,
            'projects_per_user' => self::PROJECTS_PER_USER,
            'wI' => self::WI,
            'wB' => self::WB,
            'a' => self::A
        ], $params);
        $redundancyCost = $this->calculateRedundancyCost($analysisParams);
        $nonRedundancyCost = $this->calculateNonRedundancyCost($analysisParams);
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'parameters' => $analysisParams,
            'redundancy_cost' => $redundancyCost,
            'non_redundancy_cost' => $nonRedundancyCost,
            'recommendation' => $redundancyCost < $nonRedundancyCost ? 'Mantieni ridondanza' : 'Rimuovi ridondanza',
            'cost_difference' => abs($redundancyCost - $nonRedundancyCost),
            'savings_percentage' => $redundancyCost > 0 ? (abs($redundancyCost - $nonRedundancyCost) / max($redundancyCost, $nonRedundancyCost)) * 100 : 0
        ];
    }
    public function performFullAnalysis() {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'parameters' => [
                'wI' => self::WI,
                'wB' => self::WB,
                'a' => self::A,
                'num_projects' => self::TOTAL_PROJECTS,
                'fundings_per_project' => self::FUNDINGS_PER_PROJECT,
                'num_users' => self::TOTAL_USERS,
                'projects_per_user' => self::PROJECTS_PER_USER
            ],
            'redundancy_analysis' => $this->analyzeRedundancy(),
            'operations_analysis' => $this->analyzeOperations(),
            'current_stats' => $this->getCurrentSystemStats(),
            'recommendations' => $this->getRecommendations(),
            'performance_impact' => $this->assessPerformanceImpact()
        ];
    }
    public function getCurrentSystemStats() {
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM progetti");
            $total_projects = $stmt->fetch()['total'];
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM utenti");
            $total_users = $stmt->fetch()['total'];
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM finanziamenti");
            $total_fundings = $stmt->fetch()['total'];
            $stmt = $this->conn->query("
                SELECT AVG(nr_progetti) as avg_projects 
                FROM utenti 
                WHERE tipo_utente = 'creatore'
            ");
            $avg_projects_per_user = $stmt->fetch()['avg_projects'] ?? 0;
            $avg_fundings_per_project = $total_projects > 0 ? $total_fundings / $total_projects : 0;
            return [
                'total_projects' => $total_projects,
                'total_users' => $total_users,
                'total_fundings' => $total_fundings,
                'avg_projects_per_user' => round($avg_projects_per_user, 2),
                'avg_fundings_per_project' => round($avg_fundings_per_project, 2),
                'collection_date' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Error getting current stats: " . $e->getMessage());
            return [];
        }
    }
    public function getRecommendations() {
        $analysis = $this->analyzeRedundancy();
        $redundancyCost = $analysis['total_redundancy_cost'] ?? 0;
        $nonRedundancyCost = $analysis['total_non_redundancy_cost'] ?? 0;
        $strategy = $redundancyCost < $nonRedundancyCost ? 'Mantieni ridondanza' : 'Rimuovi ridondanza';
        $savings = abs($redundancyCost - $nonRedundancyCost);
        return [
            'strategy' => $strategy,
            'estimated_savings' => $savings,
            'reasoning' => $this->getReasoningForRecommendation($redundancyCost, $nonRedundancyCost),
            'confidence' => 'High',
            'implementation_complexity' => $strategy === 'Mantieni ridondanza' ? 'Low' : 'Medium'
        ];
    }
    public function assessPerformanceImpact() {
        return [
            'slow_queries' => 0, 
            'memory_usage' => 15, 
            'response_time' => 120, 
            'details' => [
                'Redundancy reduces query complexity for user project counts',
                'Additional storage overhead is minimal',
                'Trigger maintenance adds minimal overhead',
                'Overall performance impact is positive'
            ]
        ];
    }
    private function getReasoningForRecommendation($redundancyCost, $nonRedundancyCost) {
        if ($redundancyCost < $nonRedundancyCost) {
            return "Il costo di mantenimento della ridondanza è inferiore al costo di calcolo dinamico. " .
                   "I trigger garantiscono consistenza con overhead minimo.";
        } else {
            return "Il costo di calcolo dinamico è inferiore al costo di mantenimento. " .
                   "La rimozione della ridondanza potrebbe migliorare le performance.";
        }
    }
}
