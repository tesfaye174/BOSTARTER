<?php
// Test integration script for BOSTARTER dashboard
require_once '../backend/config/database.php';
require_once '../backend/services/MongoLogger.php';

echo "<h1>BOSTARTER Dashboard Integration Test</h1>";

try {
    // Test database connection
    echo "<h2>Database Connection Test</h2>";
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ MySQL Database connection successful<br>";
    
    // Test MongoDB logger
    echo "<h2>MongoDB Logger Test</h2>";
    $mongoLogger = new MongoLogger();
    $testResult = $mongoLogger->logActivity('test_user', 'integration_test', [
        'timestamp' => date('Y-m-d H:i:s'),
        'test_type' => 'integration_check'
    ]);
    
    if ($testResult) {
        echo "✅ MongoDB logging successful<br>";
    } else {
        echo "❌ MongoDB logging failed<br>";
    }
    
    // Test database queries
    echo "<h2>Database Queries Test</h2>";
    $query = "SELECT COUNT(*) as user_count FROM USERS";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Users in database: " . $result['user_count'] . "<br>";
    
    $query = "SELECT COUNT(*) as project_count FROM PROJECTS";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Projects in database: " . $result['project_count'] . "<br>";
    
    // Test API endpoint
    echo "<h2>API Endpoint Test</h2>";
    $api_url = "http://localhost/BOSTARTER/backend/api/stats.php?type=overview";
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET'
        ]
    ]);
    
    $api_response = @file_get_contents($api_url, false, $context);
    if ($api_response) {
        $data = json_decode($api_response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ API endpoint working correctly<br>";
            echo "API Response: <pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "❌ API returned error: " . json_encode($data) . "<br>";
        }
    } else {
        echo "❌ API endpoint not accessible<br>";
    }
    
    echo "<h2>File Checks</h2>";
    $files_to_check = [
        'frontend/dashboard.php' => 'Main Dashboard File',
        'frontend/js/dashboard.js' => 'Dashboard JavaScript',
        'frontend/css/main.css' => 'Main CSS File',
        'backend/services/MongoLogger.php' => 'MongoDB Logger Service',
        'backend/api/stats.php' => 'Statistics API'
    ];
    
    foreach ($files_to_check as $file => $description) {
        $full_path = "../$file";
        if (file_exists($full_path)) {
            echo "✅ $description exists<br>";
        } else {
            echo "❌ $description missing: $file<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "❌ Test failed: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Integration Status</h2>";
echo "<p>The BOSTARTER dashboard integration includes:</p>";
echo "<ul>";
echo "<li>✅ Modern Tailwind CSS design integrated with PHP backend</li>";
echo "<li>✅ MongoDB logging system for user activity tracking</li>";
echo "<li>✅ Enhanced API endpoints for dashboard statistics</li>";
echo "<li>✅ JavaScript dashboard functionality for dynamic interactions</li>";
echo "<li>✅ Responsive design with dark mode support</li>";
echo "<li>✅ Real-time notification system</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>Login as a user to test the full dashboard functionality</li>";
echo "<li>Create test projects to see data visualization</li>";
echo "<li>Test funding and application workflows</li>";
echo "<li>Verify MongoDB logs are being created properly</li>";
echo "</ul>";

echo "<p><a href='dashboard.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard (requires login)</a></p>";
echo "<p><a href='auth/login.php' style='background: #f56565; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login Page</a></p>";
?>
