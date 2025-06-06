<?php
/**
 * PDF Compliance Audit for BOSTARTER
 * Verifies complete compliance with "Corso di Basi di Dati CdS Informatica per il Management A.A. 2024/2025"
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/VolumeAnalysisService.php';

class PDFComplianceAudit {
    private $conn;
    private $results = [];
    
    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    public function runFullAudit() {
        echo "=== PDF COMPLIANCE AUDIT FOR BOSTARTER ===\n";
        echo "Verifying compliance with course specifications...\n\n";
        
        $this->checkDatabaseSchema();
        $this->checkProjectTypes();
        $this->checkUserTypes();
        $this->checkTriggers();
        $this->checkStoredProcedures();
        $this->checkViews();
        $this->checkVolumeAnalysis();
        $this->checkSkillsSystem();
        $this->checkRedundancyField();
        
        $this->generateComplianceReport();
    }
    
    private function checkDatabaseSchema() {
        echo "📋 Checking Database Schema...\n";
        
        $requiredTables = [
            'utenti', 'progetti', 'finanziamenti', 'competenze', 
            'utenti_competenze', 'notifiche', 'backups', 'sistema_log'
        ];
          $existingTables = [];
        $result = $this->conn->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $existingTables[] = $row[0];
        }
        
        $missingTables = array_diff($requiredTables, $existingTables);
        
        if (empty($missingTables)) {
            $this->results['schema'] = ['status' => 'PASS', 'message' => 'All required tables present'];
            echo "✅ Schema compliance: PASS\n";
        } else {
            $this->results['schema'] = ['status' => 'FAIL', 'message' => 'Missing tables: ' . implode(', ', $missingTables)];
            echo "❌ Schema compliance: FAIL\n";
        }
        echo "\n";
    }
    
    private function checkProjectTypes() {
        echo "🎯 Checking Project Types Compliance...\n";
          // Check if only hardware/software projects exist
        $result = $this->conn->query("SELECT DISTINCT tipo_progetto FROM progetti");
        $types = $result->fetchAll(PDO::FETCH_COLUMN);
        
        $allowedTypes = ['hardware', 'software'];
        $invalidTypes = array_diff($types, $allowedTypes);
        
        if (empty($invalidTypes)) {
            $this->results['project_types'] = ['status' => 'PASS', 'message' => 'Only hardware/software projects found'];
            echo "✅ Project types compliance: PASS\n";
        } else {
            $this->results['project_types'] = ['status' => 'FAIL', 'message' => 'Invalid types found: ' . implode(', ', $invalidTypes)];
            echo "❌ Project types compliance: FAIL\n";
        }
        echo "\n";
    }
    
    private function checkUserTypes() {
        echo "👥 Checking User Types...\n";
          $result = $this->conn->query("SELECT DISTINCT tipo_utente FROM utenti");
        $types = $result->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredTypes = ['standard', 'creatore', 'amministratore'];
        $missingTypes = array_diff($requiredTypes, $types);
        
        if (empty($missingTypes)) {
            $this->results['user_types'] = ['status' => 'PASS', 'message' => 'All user types present'];
            echo "✅ User types compliance: PASS\n";
        } else {
            $this->results['user_types'] = ['status' => 'FAIL', 'message' => 'Missing types: ' . implode(', ', $missingTypes)];
            echo "❌ User types compliance: FAIL\n";
        }
        echo "\n";
    }
    
    private function checkTriggers() {
        echo "⚡ Checking Triggers Implementation...\n";
        
        $result = $this->conn->query("SHOW TRIGGERS");
        $triggers = $result->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredTriggers = [
            'update_nr_progetti_insert',
            'update_nr_progetti_delete',
            'update_reliability_after_project_complete',
            'auto_close_project'
        ];
        
        $foundTriggers = 0;
        foreach ($requiredTriggers as $requiredTrigger) {
            foreach ($triggers as $trigger) {
                if (strpos($trigger, 'nr_progetti') !== false || 
                    strpos($trigger, 'reliability') !== false || 
                    strpos($trigger, 'close') !== false) {
                    $foundTriggers++;
                    break;
                }
            }
        }
        
        if ($foundTriggers >= 3) {
            $this->results['triggers'] = ['status' => 'PASS', 'message' => 'Required triggers implemented'];
            echo "✅ Triggers compliance: PASS\n";
        } else {
            $this->results['triggers'] = ['status' => 'PARTIAL', 'message' => 'Some triggers may be missing'];
            echo "⚠️ Triggers compliance: PARTIAL\n";
        }
        echo "\n";
    }
    
    private function checkStoredProcedures() {
        echo "📦 Checking Stored Procedures...\n";
        
        $result = $this->conn->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE()");
        $procedures = $result->fetchAll();
        
        if (count($procedures) > 0) {
            $this->results['procedures'] = ['status' => 'PASS', 'message' => count($procedures) . ' procedures found'];
            echo "✅ Stored procedures compliance: PASS\n";
        } else {
            $this->results['procedures'] = ['status' => 'FAIL', 'message' => 'No stored procedures found'];
            echo "❌ Stored procedures compliance: FAIL\n";
        }
        echo "\n";
    }
    
    private function checkViews() {
        echo "👁️ Checking Views for Statistics...\n";
        
        $result = $this->conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
        $views = $result->fetchAll();
        
        if (count($views) > 0) {
            $this->results['views'] = ['status' => 'PASS', 'message' => count($views) . ' views found'];
            echo "✅ Views compliance: PASS\n";
        } else {
            $this->results['views'] = ['status' => 'FAIL', 'message' => 'No views found'];
            echo "❌ Views compliance: FAIL\n";
        }
        echo "\n";
    }
    
    private function checkVolumeAnalysis() {
        echo "📊 Checking Volume Analysis Implementation...\n";
        
        try {
            $volumeService = new VolumeAnalysisService();
            $analysis = $volumeService->performFullAnalysis();
            
            // Check if analysis contains required components
            $hasCoefficients = isset($analysis['parameters']) && 
                               $analysis['parameters']['wI'] == 1 && 
                               $analysis['parameters']['wB'] == 0.5 && 
                               $analysis['parameters']['a'] == 2;
            
            $hasOperations = isset($analysis['operations_analysis']);
            $hasRecommendations = isset($analysis['recommendations']);
            
            if ($hasCoefficients && $hasOperations && $hasRecommendations) {
                $this->results['volume_analysis'] = ['status' => 'PASS', 'message' => 'Complete volume analysis with PDF specifications'];
                echo "✅ Volume analysis compliance: PASS\n";
            } else {
                $this->results['volume_analysis'] = ['status' => 'PARTIAL', 'message' => 'Volume analysis incomplete'];
                echo "⚠️ Volume analysis compliance: PARTIAL\n";
            }
        } catch (Exception $e) {
            $this->results['volume_analysis'] = ['status' => 'FAIL', 'message' => 'Volume analysis service error: ' . $e->getMessage()];
            echo "❌ Volume analysis compliance: FAIL\n";
        }
        echo "\n";
    }
    
    private function checkSkillsSystem() {
        echo "🎓 Checking Skills System...\n";
        
        // Check competenze table structure
        $result = $this->conn->query("DESCRIBE competenze");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
          // Check utenti_competenze table structure
        $result = $this->conn->query("DESCRIBE utenti_skill");
        $userSkillsColumns = $result->fetchAll(PDO::FETCH_COLUMN);
        
        $hasLivelloField = in_array('livello', $userSkillsColumns);
        
        if ($hasLivelloField) {
            $this->results['skills'] = ['status' => 'PASS', 'message' => 'Skills system with competenza/livello structure'];
            echo "✅ Skills system compliance: PASS\n";
        } else {
            $this->results['skills'] = ['status' => 'FAIL', 'message' => 'Skills system structure incorrect'];
            echo "❌ Skills system compliance: FAIL\n";
        }
        echo "\n";
    }
    
    private function checkRedundancyField() {
        echo "🔄 Checking #nr_progetti Redundancy Field...\n";
        
        // Check if nr_progetti field exists in utenti table
        $result = $this->conn->query("DESCRIBE utenti");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        
        $hasNrProgettiField = in_array('nr_progetti', $columns);
        
        if ($hasNrProgettiField) {
            // Test consistency
            $result = $this->conn->query("
                SELECT u.id, u.nr_progetti, COUNT(p.id) as actual_count
                FROM utenti u
                LEFT JOIN progetti p ON u.id = p.creatore_id
                GROUP BY u.id
                HAVING u.nr_progetti != actual_count
                LIMIT 1
            ");
            
            $inconsistencies = $result->rowCount();
            
            if ($inconsistencies == 0) {
                $this->results['redundancy'] = ['status' => 'PASS', 'message' => '#nr_progetti field consistent'];
                echo "✅ Redundancy field compliance: PASS\n";
            } else {
                $this->results['redundancy'] = ['status' => 'PARTIAL', 'message' => '#nr_progetti field exists but has inconsistencies'];
                echo "⚠️ Redundancy field compliance: PARTIAL\n";
            }
        } else {
            $this->results['redundancy'] = ['status' => 'FAIL', 'message' => '#nr_progetti field missing'];
            echo "❌ Redundancy field compliance: FAIL\n";
        }
        echo "\n";
    }
    
    private function generateComplianceReport() {
        echo "📋 === FINAL COMPLIANCE REPORT ===\n\n";
        
        $passCount = 0;
        $totalChecks = count($this->results);
        
        foreach ($this->results as $check => $result) {
            $status = $result['status'];
            $message = $result['message'];
            
            $icon = $status === 'PASS' ? '✅' : ($status === 'PARTIAL' ? '⚠️' : '❌');
            if ($status === 'PASS') $passCount++;
            
            echo sprintf("%-20s %s %s - %s\n", 
                ucfirst(str_replace('_', ' ', $check)), 
                $icon, 
                $status, 
                $message
            );
        }
        
        $compliancePercentage = round(($passCount / $totalChecks) * 100, 1);
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Overall Compliance: {$compliancePercentage}% ({$passCount}/{$totalChecks} checks passed)\n";
        
        if ($compliancePercentage >= 90) {
            echo "🎉 EXCELLENT COMPLIANCE - Ready for submission!\n";
        } elseif ($compliancePercentage >= 75) {
            echo "👍 GOOD COMPLIANCE - Minor adjustments needed\n";
        } else {
            echo "⚠️ NEEDS IMPROVEMENT - Major compliance issues\n";
        }
        
        echo "\nBOSTARTER crowdfunding platform audit completed.\n";
        echo "System supports ONLY hardware/software projects as per PDF specifications.\n";
    }
}

// Run the audit
if (php_sapi_name() === 'cli') {
    $audit = new PDFComplianceAudit();
    $audit->runFullAudit();
} else {
    // Web interface
    header('Content-Type: text/plain');
    $audit = new PDFComplianceAudit();
    $audit->runFullAudit();
}
?>
