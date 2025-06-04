<?php
// Test the APIs via actual HTTP requests
echo "=== TESTING APIS VIA HTTP ===\n";

function makeHttpRequest($url, $data = null, $method = 'GET') {
    $curl = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ];
    
    if ($method === 'POST' && $data) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
        $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
    }
    
    curl_setopt_array($curl, $options);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    return [
        'code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

$baseUrl = 'http://localhost/BOSTARTER/backend/api';

// Test 1: Register a new user
echo "Test 1: Register new user\n";
$registerData = [
    'email' => 'httptest' . time() . '@example.com',
    'nickname' => 'httptest' . time(),
    'password' => 'testpassword123',
    'nome' => 'HTTP',
    'cognome' => 'Test',
    'anno_nascita' => 1990,
    'luogo_nascita' => 'Test City'
];

$result = makeHttpRequest($baseUrl . '/register.php', $registerData, 'POST');
echo "Status: " . $result['code'] . "\n";
echo "Response: " . $result['response'] . "\n";
if ($result['error']) {
    echo "Error: " . $result['error'] . "\n";
}
echo "\n";

// Test 2: Get projects
echo "Test 2: Get projects\n";
$result = makeHttpRequest($baseUrl . '/projects_modern.php?action=list');
echo "Status: " . $result['code'] . "\n";
echo "Response length: " . strlen($result['response']) . " characters\n";
$jsonResponse = json_decode($result['response'], true);
if ($jsonResponse) {
    echo "Projects found: " . (isset($jsonResponse['data']['progetti']) ? count($jsonResponse['data']['progetti']) : 'N/A') . "\n";
    if (isset($jsonResponse['data']['progetti']) && !empty($jsonResponse['data']['progetti'])) {
        echo "First project: " . $jsonResponse['data']['progetti'][0]['nome'] . "\n";
    }
} else {
    echo "Response preview: " . substr($result['response'], 0, 200) . "...\n";
}
echo "\n";

// Test 3: Frontend homepage
echo "Test 3: Frontend homepage\n";
$result = makeHttpRequest('http://localhost/BOSTARTER/frontend/index.php');
echo "Status: " . $result['code'] . "\n";
echo "Response length: " . strlen($result['response']) . " characters\n";
if (strpos($result['response'], 'BOSTARTER') !== false) {
    echo "✓ Homepage loads correctly\n";
} else {
    echo "✗ Homepage may have issues\n";
    echo "Preview: " . substr($result['response'], 0, 300) . "...\n";
}

echo "\n=== HTTP TESTS COMPLETED ===\n";
?>
