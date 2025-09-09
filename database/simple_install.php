<?php
/**
 * BOSTARTER - Simple Database Installation Script
 * This script sets up the database with sample data for testing
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'bostarter';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>BOSTARTER Database Installation</h2>";
    echo "<p>Installing database schema and sample data...</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    $pdo->exec("USE $database");
    
    echo "<p>✓ Database created/selected</p>";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema_mysql.sql');
    if ($schema) {
        // Remove the CREATE DATABASE and USE statements since we already did that
        $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS bostarter.*;/', '', $schema);
        $schema = preg_replace('/USE bostarter;/', '', $schema);
        
        $pdo->exec($schema);
        echo "<p>✓ Database schema created</p>";
    }
    
    // Read and execute procedures
    $procedures = file_get_contents(__DIR__ . '/procedures_mysql.sql');
    if ($procedures) {
        $pdo->exec($procedures);
        echo "<p>✓ Stored procedures created</p>";
    }
    
    // Read and execute triggers
    $triggers = file_get_contents(__DIR__ . '/triggers_mysql.sql');
    if ($triggers) {
        $pdo->exec($triggers);
        echo "<p>✓ Triggers created</p>";
    }
    
    // Read and execute demo data
    $demoData = file_get_contents(__DIR__ . '/data_demo_mysql.sql');
    if ($demoData) {
        $pdo->exec($demoData);
        echo "<p>✓ Demo data inserted</p>";
    }
    
    echo "<div style='color: green; font-weight: bold; margin-top: 20px;'>";
    echo "<p>🎉 Installation completed successfully!</p>";
    echo "<p>You can now access BOSTARTER at: <a href='../frontend/'>http://localhost/BOSTARTER/frontend/</a></p>";
    echo "</div>";
    
    echo "<h3>Test Accounts:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@bostarter.local / password</li>";
    echo "<li><strong>Creator:</strong> mario.rossi@email.com / password</li>";
    echo "<li><strong>User:</strong> giulia.bianchi@email.com / password</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>";
    echo "<h3>Installation Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
