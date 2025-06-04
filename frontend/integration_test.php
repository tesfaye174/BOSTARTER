<?php
/**
 * Test di verifica integrazione completa BOSTARTER
 * Verifica che tutti i componenti siano correttamente integrati
 */

header('Content-Type: application/json');

$results = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test 1: Verifica componenti condivisi
$sharedComponents = [
    '../frontend/assets/shared/css/common-styles.css',
    '../frontend/assets/shared/js/common-functions.js',
    '../frontend/assets/shared/js/category-config.js'
];

foreach ($sharedComponents as $component) {
    $exists = file_exists($component);
    $results['tests'][] = [
        'name' => "Componente condiviso: " . basename($component),
        'status' => $exists ? 'PASS' : 'FAIL',
        'file' => $component
    ];
}

// Test 2: Verifica API moderne
$modernApis = [
    '../backend/api/login.php',
    '../backend/api/register.php',
    '../backend/api/projects_modern.php'
];

foreach ($modernApis as $api) {
    $exists = file_exists($api);
    $results['tests'][] = [
        'name' => "API moderna: " . basename($api),
        'status' => $exists ? 'PASS' : 'FAIL',
        'file' => $api
    ];
}

// Test 3: Verifica rimozione file legacy
$legacyFiles = [
    '../backend/auth_api.php',
    '../backend/legacy/user.php'
];

foreach ($legacyFiles as $legacy) {
    $exists = file_exists($legacy);
    $results['tests'][] = [
        'name' => "File legacy rimosso: " . basename($legacy),
        'status' => !$exists ? 'PASS' : 'FAIL',
        'file' => $legacy,
        'note' => $exists ? 'File ancora presente - dovrebbe essere rimosso' : 'File correttamente rimosso'
    ];
}

// Test 4: Verifica categorie ottimizzate
$categories = [
    'arte', 'artigianato', 'cibo', 'danza', 'design', 'editoriale',
    'film', 'fotografia', 'fumetti', 'giochi', 'giornalismo', 
    'moda', 'musica', 'teatro', 'tecnologia'
];

$optimizedCount = 0;
foreach ($categories as $category) {
    $indexFile = "../frontend/assets/{$category}/index.html";
    if (file_exists($indexFile)) {
        $content = file_get_contents($indexFile);
        $hasSharedComponents = strpos($content, 'shared/css/common-styles.css') !== false;
        
        $results['tests'][] = [
            'name' => "Categoria ottimizzata: {$category}",
            'status' => $hasSharedComponents ? 'PASS' : 'FAIL',
            'file' => $indexFile,
            'note' => $hasSharedComponents ? 'Componenti condivisi integrati' : 'Componenti condivisi mancanti'
        ];
        
        if ($hasSharedComponents) $optimizedCount++;
    }
}

// Test 5: Statistiche finali
$results['summary'] = [
    'shared_components' => count(array_filter($sharedComponents, 'file_exists')),
    'modern_apis' => count(array_filter($modernApis, 'file_exists')),
    'legacy_files_removed' => count(array_filter($legacyFiles, function($f) { return !file_exists($f); })),
    'categories_optimized' => $optimizedCount,
    'total_categories' => count($categories),
    'optimization_percentage' => round(($optimizedCount / count($categories)) * 100, 2)
];

// Determina stato generale
$totalTests = count($results['tests']);
$passedTests = count(array_filter($results['tests'], function($test) {
    return $test['status'] === 'PASS';
}));

$results['overall_status'] = $passedTests === $totalTests ? 'ALL_TESTS_PASSED' : 'SOME_TESTS_FAILED';
$results['pass_rate'] = round(($passedTests / $totalTests) * 100, 2);

// Test database connection
try {
    require_once '../backend/config/database.php';
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $results['database_connection'] = 'CONNECTED';
} catch (Exception $e) {
    $results['database_connection'] = 'FAILED: ' . $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
