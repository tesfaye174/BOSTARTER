<?php
// Test the modern projects API
echo "Testing Projects API...\n";
echo "======================\n";

// Test GET projects list
echo "\n1. Testing GET projects list...\n";
$url = 'http://localhost/BOSTARTER/backend/api/projects_modern.php?action=list';
$response = file_get_contents($url);
$data = json_decode($response, true);

if ($data && $data['success']) {
    echo "✅ GET projects list successful\n";
    echo "   Found " . $data['data']['totale'] . " projects\n";
    
    if (!empty($data['data']['progetti'])) {
        $firstProject = $data['data']['progetti'][0];
        $projectId = $firstProject['id'];
        
        // Test GET project details
        echo "\n2. Testing GET project details for ID: $projectId...\n";
        $url = "http://localhost/BOSTARTER/backend/api/projects_modern.php?action=details&id=$projectId";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && $data['success']) {
            echo "✅ GET project details successful\n";
            echo "   Project: " . $data['data']['nome'] . "\n";
            echo "   Creator: " . $data['data']['creatore_nickname'] . "\n";
            echo "   Category: " . $data['data']['categoria_nome'] . "\n";
        } else {
            echo "❌ GET project details failed\n";
            if (isset($data['message'])) {
                echo "   Error: " . $data['message'] . "\n";
            }
        }
        
        // Test GET project donations
        echo "\n3. Testing GET project donations for ID: $projectId...\n";
        $url = "http://localhost/BOSTARTER/backend/api/projects_modern.php?action=donations&id=$projectId";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && $data['success']) {
            echo "✅ GET project donations successful\n";
            echo "   Found " . $data['data']['totale'] . " donations\n";
        } else {
            echo "❌ GET project donations failed\n";
            if (isset($data['message'])) {
                echo "   Error: " . $data['message'] . "\n";
            }
        }
    }
} else {
    echo "❌ GET projects list failed\n";
    if (isset($data['message'])) {
        echo "   Error: " . $data['message'] . "\n";
    }
}

echo "\n🎉 API testing completed!\n";
?>
