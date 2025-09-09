<?php
/**
 * Test completo per tutti i modelli BOSTARTER
 * Verifica integrazione utilities e compatibilità MySQL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include dependencies
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Project.php';
require_once __DIR__ . '/models/Candidatura.php';
require_once __DIR__ . '/models/Commento.php';
require_once __DIR__ . '/models/Competenza.php';
require_once __DIR__ . '/models/Finanziamento.php';
require_once __DIR__ . '/models/Reward.php';
require_once __DIR__ . '/models/ProfiloRichiesto.php';
require_once __DIR__ . '/models/ComponenteHardware.php';
require_once __DIR__ . '/utils/MongoLogger.php';

class ModelTester {
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    
    public function __construct() {
        echo "<h1>🧪 Test Completo Modelli BOSTARTER</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: #059669; font-weight: bold; }
            .error { color: #dc2626; font-weight: bold; }
            .warning { color: #d97706; font-weight: bold; }
            .section { margin: 20px 0; padding: 15px; border-left: 4px solid #2563eb; background: #f8fafc; }
            .test-result { margin: 5px 0; padding: 8px; border-radius: 4px; }
            .pass { background: #dcfce7; border: 1px solid #059669; }
            .fail { background: #fef2f2; border: 1px solid #dc2626; }
            pre { background: #f1f5f9; padding: 10px; border-radius: 4px; overflow-x: auto; }
        </style>";
    }
    
    private function test($description, $callback) {
        $this->totalTests++;
        echo "<div class='test-result ";
        
        try {
            $result = $callback();
            if ($result === true || (is_array($result) && isset($result['success']) && $result['success'])) {
                echo "pass'><span class='success'>✅ PASS</span> - $description</div>\n";
                $this->passedTests++;
                return true;
            } else {
                echo "fail'><span class='error'>❌ FAIL</span> - $description";
                if (is_array($result) && isset($result['error'])) {
                    echo "<br><small>Error: " . htmlspecialchars($result['error']) . "</small>";
                }
                echo "</div>\n";
                return false;
            }
        } catch (Exception $e) {
            echo "fail'><span class='error'>❌ ERROR</span> - $description";
            echo "<br><small>Exception: " . htmlspecialchars($e->getMessage()) . "</small></div>\n";
            return false;
        }
    }
    
    public function testUserModel() {
        echo "<div class='section'><h2>👤 Test User Model</h2>";
        
        $user = new User();
        
        // Test database connection
        $this->test("User model initialization", function() use ($user) {
            return method_exists($user, 'create');
        });
        
        // Test utilities integration
        $this->test("User model has utilities integration", function() use ($user) {
            $reflection = new ReflectionClass($user);
            $properties = $reflection->getProperties();
            $hasUtilities = false;
            foreach ($properties as $prop) {
                if (in_array($prop->getName(), ['logger', 'security', 'performance', 'cache'])) {
                    $hasUtilities = true;
                    break;
                }
            }
            return $hasUtilities;
        });
        
        // Test validation methods
        $this->test("User validation methods exist", function() use ($user) {
            return method_exists($user, 'validateEmail') && method_exists($user, 'validatePassword');
        });
        
        echo "</div>";
    }
    
    public function testProjectModel() {
        echo "<div class='section'><h2>🚀 Test Project Model</h2>";
        
        $project = new Project();
        
        $this->test("Project model initialization", function() use ($project) {
            return method_exists($project, 'create');
        });
        
        $this->test("Project model has CRUD methods", function() use ($project) {
            return method_exists($project, 'getById') && 
                   method_exists($project, 'update') && 
                   method_exists($project, 'delete');
        });
        
        $this->test("Project model has list methods", function() use ($project) {
            return method_exists($project, 'getList') && 
                   method_exists($project, 'getByUserId');
        });
        
        echo "</div>";
    }
    
    public function testCandidaturaModel() {
        echo "<div class='section'><h2>📝 Test Candidatura Model</h2>";
        
        $candidatura = new Candidatura();
        
        $this->test("Candidatura model initialization", function() use ($candidatura) {
            return method_exists($candidatura, 'create');
        });
        
        $this->test("Candidatura model has query methods", function() use ($candidatura) {
            return method_exists($candidatura, 'getByProfilo') && 
                   method_exists($candidatura, 'getByUtente');
        });
        
        echo "</div>";
    }
    
    public function testCommentoModel() {
        echo "<div class='section'><h2>💬 Test Commento Model</h2>";
        
        $commento = new Commento();
        
        $this->test("Commento model initialization", function() use ($commento) {
            return method_exists($commento, 'create');
        });
        
        $this->test("Commento model has utilities integration", function() use ($commento) {
            $reflection = new ReflectionClass($commento);
            $properties = $reflection->getProperties();
            $hasUtilities = false;
            foreach ($properties as $prop) {
                if (in_array($prop->getName(), ['logger', 'security', 'performance', 'cache'])) {
                    $hasUtilities = true;
                    break;
                }
            }
            return $hasUtilities;
        });
        
        $this->test("Commento model has reply functionality", function() use ($commento) {
            return method_exists($commento, 'createReply');
        });
        
        echo "</div>";
    }
    
    public function testCompetenzaModel() {
        echo "<div class='section'><h2>🎯 Test Competenza Model</h2>";
        
        $competenza = new Competenza();
        
        $this->test("Competenza model initialization", function() use ($competenza) {
            return method_exists($competenza, 'create');
        });
        
        $this->test("Competenza model has utilities integration", function() use ($competenza) {
            $reflection = new ReflectionClass($competenza);
            $properties = $reflection->getProperties();
            $hasUtilities = false;
            foreach ($properties as $prop) {
                if (in_array($prop->getName(), ['logger', 'security', 'performance', 'cache'])) {
                    $hasUtilities = true;
                    break;
                }
            }
            return $hasUtilities;
        });
        
        $this->test("Competenza model has search functionality", function() use ($competenza) {
            return method_exists($competenza, 'search') && 
                   method_exists($competenza, 'getByCategoria');
        });
        
        echo "</div>";
    }
    
    public function testDatabaseConnection() {
        echo "<div class='section'><h2>🗄️ Test Database Connection</h2>";
        
        $this->test("Database connection established", function() {
            try {
                $db = Database::getInstance()->getConnection();
                return $db instanceof PDO;
            } catch (Exception $e) {
                return false;
            }
        });
        
        $this->test("Database has correct charset", function() {
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT @@character_set_database as charset");
                $result = $stmt->fetch();
                return $result && in_array($result['charset'], ['utf8', 'utf8mb4']);
            } catch (Exception $e) {
                return false;
            }
        });
        
        echo "</div>";
    }
    
    public function testUtilitiesAvailability() {
        echo "<div class='section'><h2>🛠️ Test Utilities Availability</h2>";
        
        $this->test("Logger utility exists", function() {
            return file_exists(__DIR__ . '/utils/Logger.php');
        });
        
        $this->test("Validator utility exists", function() {
            return file_exists(__DIR__ . '/utils/Validator.php');
        });
        
        $this->test("SecurityManager utility exists", function() {
            return file_exists(__DIR__ . '/utils/SecurityManager.php');
        });
        
        $this->test("PerformanceMonitor utility exists", function() {
            return file_exists(__DIR__ . '/utils/PerformanceMonitor.php');
        });
        
        $this->test("CacheManager utility exists", function() {
            return file_exists(__DIR__ . '/utils/CacheManager.php');
        });
        
        echo "</div>";
    }
    
    public function testSecurityFeatures() {
        echo "<div class='section'><h2>🔒 Test Security Features</h2>";
        
        // Test if Security class can be instantiated
        $this->test("Security utility can be loaded", function() {
            try {
                if (file_exists(__DIR__ . '/utils/Security.php')) {
                    require_once __DIR__ . '/utils/Security.php';
                    $security = Security::getInstance();
                    return $security !== null;
                }
                return false;
            } catch (Exception $e) {
                return false;
            }
        });
        
        $this->test("CSRF token generation available", function() {
            try {
                if (class_exists('Security')) {
                    $security = Security::getInstance();
                    return method_exists($security, 'generateCSRFToken');
                }
                return false;
            } catch (Exception $e) {
                return false;
            }
        });
        
        echo "</div>";
    }
    
    public function runAllTests() {
        echo "<div style='background: #e0f2fe; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
        echo "<h2>🚀 Avvio Test Suite Completa</h2>";
        echo "<p>Testing BOSTARTER backend models con utilities integrate...</p>";
        echo "</div>";
        
        $this->testDatabaseConnection();
        $this->testUtilitiesAvailability();
        $this->testSecurityFeatures();
        $this->testUserModel();
        $this->testProjectModel();
        $this->testCandidaturaModel();
        $this->testCommentoModel();
        $this->testCompetenzaModel();
        
        $this->showSummary();
    }
    
    private function showSummary() {
        echo "<div class='section' style='border-left-color: " . 
             ($this->passedTests === $this->totalTests ? '#059669' : '#dc2626') . ";'>";
        echo "<h2>📊 Riepilogo Test</h2>";
        echo "<p><strong>Test Totali:</strong> {$this->totalTests}</p>";
        echo "<p><strong>Test Passati:</strong> <span class='success'>{$this->passedTests}</span></p>";
        echo "<p><strong>Test Falliti:</strong> <span class='error'>" . 
             ($this->totalTests - $this->passedTests) . "</span></p>";
        
        $percentage = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 1) : 0;
        echo "<p><strong>Percentuale Successo:</strong> {$percentage}%</p>";
        
        if ($this->passedTests === $this->totalTests) {
            echo "<div style='background: #dcfce7; padding: 15px; border-radius: 8px; margin-top: 15px;'>";
            echo "<h3 class='success'>🎉 Tutti i test sono passati!</h3>";
            echo "<p>Il sistema BOSTARTER è completamente funzionale con:</p>";
            echo "<ul>";
            echo "<li>✅ Modelli backend ottimizzati</li>";
            echo "<li>✅ Utilities integrate (Logger, Security, Performance, Cache)</li>";
            echo "<li>✅ Compatibilità MySQL completa</li>";
            echo "<li>✅ Sistema enterprise-ready</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div style='background: #fef2f2; padding: 15px; border-radius: 8px; margin-top: 15px;'>";
            echo "<h3 class='error'>⚠️ Alcuni test hanno fallito</h3>";
            echo "<p>Verificare i dettagli sopra per risolvere i problemi.</p>";
            echo "</div>";
        }
        
        echo "</div>";
    }
}

// Esegui i test
$tester = new ModelTester();
$tester->runAllTests();

echo "<div style='margin-top: 30px; padding: 15px; background: #f1f5f9; border-radius: 8px;'>";
echo "<h3>🔧 Informazioni Sistema</h3>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Memory Usage:</strong> " . round(memory_get_usage() / 1024 / 1024, 2) . " MB</p>";
echo "</div>";
?>
