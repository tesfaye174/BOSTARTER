<?php
// Complete authentication flow test
echo "=== COMPLETE AUTHENTICATION FLOW TEST ===\n";

function makeHttpRequest($url, $data = null, $method = 'GET') {
    $curl = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR => sys_get_temp_dir() . '/cookies.txt',
        CURLOPT_COOKIEFILE => sys_get_temp_dir() . '/cookies.txt',
    ];
    
    if ($method === 'POST' && $data) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
        $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
    }
    
    curl_setopt_array($curl, $options);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

$baseUrl = 'http://localhost/BOSTARTER/backend/api';

// Step 1: Register a new user
echo "Step 1: Register new user\n";
$email = 'fulltest' . time() . '@example.com';
$password = 'testpassword123';

$registerData = [
    'email' => $email,
    'nickname' => 'fulltest' . time(),
    'password' => $password,
    'nome' => 'Full',
    'cognome' => 'Test',
    'anno_nascita' => 1990,
    'luogo_nascita' => 'Test City'
];

$result = makeHttpRequest($baseUrl . '/register.php', $registerData, 'POST');
echo "✓ Registration: " . ($result['response']['success'] ? 'SUCCESS' : 'FAILED') . "\n";

if ($result['response']['success']) {
    // Step 2: Login with the registered user
    echo "\nStep 2: Login with registered user\n";
    $loginData = [
        'email' => $email,
        'password' => $password
    ];
    
    $result = makeHttpRequest($baseUrl . '/login.php', $loginData, 'POST');
    echo "✓ Login: " . ($result['response']['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    
    if ($result['response']['success']) {
        echo "User ID: " . $result['response']['data']['user']['id'] . "\n";
        echo "Email: " . $result['response']['data']['user']['email'] . "\n";
        echo "Nickname: " . $result['response']['data']['user']['nickname'] . "\n";
        
        // Step 3: Get projects (should work with session)
        echo "\nStep 3: Get projects with authenticated session\n";
        $result = makeHttpRequest($baseUrl . '/projects_modern.php?action=list');
        echo "✓ Projects API: " . ($result['response']['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        echo "Projects count: " . count($result['response']['data']['progetti']) . "\n";
        
        // Step 4: Try to create a project (requires authentication)
        echo "\nStep 4: Create a project (authentication required)\n";
        $projectData = [
            'name' => 'Test Project ' . time(),
            'description' => 'This is a test project created via API',
            'budget' => 1000,
            'project_type' => 'software',
            'end_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'category' => 'Tecnologia'
        ];
        
        $result = makeHttpRequest($baseUrl . '/projects_modern.php?action=create', $projectData, 'POST');
        echo "✓ Create project: " . ($result['response']['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        
        if (!$result['response']['success']) {
            echo "Error: " . $result['response']['message'] . "\n";
        }
    }
}

echo "\n=== AUTHENTICATION FLOW TEST COMPLETED ===\n";
?>
