<?php
// Direct test of projects API
echo "=== TESTING PROJECTS API DIRECTLY ===\n";

// Simulate GET request
$_SERVER['REQUEST_METHOD'] = 'GET';

try {
    ob_start();
    include __DIR__ . '/backend/api/projects_modern.php';
    $output = ob_get_clean();
    
    echo "API Output:\n";
    echo $output . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
