<?php
// Quick test to verify MongoLogger and database integration
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== BOSTARTER System Test ===\n";

// Test 1: Database connection
try {
    require_once __DIR__ . '/../backend/config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    if ($db) {
        echo "✓ Database connection: SUCCESS\n";
    } else {
        echo "✗ Database connection: FAILED\n";
    }
} catch (Exception $e) {
    echo "✗ Database connection: ERROR - " . $e->getMessage() . "\n";
}

// Test 2: MongoLogger instantiation
try {
    require_once __DIR__ . '/../backend/services/MongoLogger.php';
    $mongoLogger = new MongoLogger();
    echo "✓ MongoLogger instantiation: SUCCESS\n";
} catch (Exception $e) {
    echo "✗ MongoLogger instantiation: ERROR - " . $e->getMessage() . "\n";
}

// Test 3: Basic queries
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM progetti");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Projects query: SUCCESS - Found {$count['count']} projects\n";
} catch (Exception $e) {
    echo "✗ Projects query: ERROR - " . $e->getMessage() . "\n";
}

// Test 4: MongoLogger functionality
try {
    $mongoLogger->logSystem('system_test', [
        'timestamp' => date('Y-m-d H:i:s'),
        'test_id' => 'frontend_integration_test'
    ]);
    echo "✓ MongoLogger logging: SUCCESS\n";
} catch (Exception $e) {
    echo "✗ MongoLogger logging: ERROR - " . $e->getMessage() . "\n";
}

// Test 5: Frontend page generation
try {
    ob_start();
    include 'index.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    if (strpos($output, '<title>') !== false && strpos($output, '</html>') !== false) {
        echo "✓ Frontend page generation: SUCCESS\n";
    } else {
        echo "✗ Frontend page generation: INCOMPLETE\n";
    }
} catch (Exception $e) {
    echo "✗ Frontend page generation: ERROR - " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
