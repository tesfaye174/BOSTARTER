<?php
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/models/Project.php';

echo "Testing Project model connection...\n";
echo "====================================\n";

try {
    // Test database connection
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful\n";
    
    // Test Project model instantiation
    $project = new Project();
    echo "✅ Project model instantiated successfully\n";
    
    // Test getting project list
    echo "\nTesting getList method...\n";
    $result = $project->getList();
    
    if ($result !== null) {
        echo "✅ getList method works\n";
        echo "   Found " . $result['totale'] . " projects\n";
        echo "   Current page: " . $result['pagina'] . "\n";
        echo "   Total pages: " . $result['totale_pagine'] . "\n";
    } else {
        echo "❌ getList method failed\n";
    }
    
    // Test getting project details (if any projects exist)
    if ($result && !empty($result['progetti'])) {
        $firstProject = $result['progetti'][0];
        echo "\nTesting getDetails method with project ID: " . $firstProject['id'] . "...\n";
        
        $details = $project->getDetails($firstProject['id']);
        if ($details !== null) {
            echo "✅ getDetails method works\n";
            echo "   Project: " . $details['nome'] . "\n";
            echo "   Creator: " . $details['creatore_nickname'] . "\n";
            echo "   Category: " . $details['categoria_nome'] . "\n";
        } else {
            echo "❌ getDetails method failed\n";
        }
    } else {
        echo "\nℹ️ No projects found to test getDetails method\n";
    }
    
    echo "\n🎉 Project model testing completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
?>
