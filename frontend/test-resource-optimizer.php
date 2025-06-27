<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test ResourceOptimizer - BOSTARTER</title>
    
    <?php
    // Includi ResourceOptimizer
    require_once __DIR__ . '/utils/ResourceOptimizer.php';
    require_once __DIR__ . '/utils/CacheManager.php';
    
    // Inizializza sistemi
    PerformanceHelper::initPageMetrics();
    PerformanceHelper::startMeasurement('test_page');
    CacheManager::init();
    
    // Test resource hints
    echo ResourceOptimizer::resourceHints();
    
    // Test caricamento CSS
    echo ResourceOptimizer::loadStyle('/BOSTARTER/frontend/css/main-styles.css', ['critical' => true]);
    echo ResourceOptimizer::loadStyle('/BOSTARTER/frontend/css/animations.css', ['defer' => true]);
    
    // Test preload
    echo ResourceOptimizer::preloadCriticalResources();
    
    // Test CSS critico inline
    echo ResourceOptimizer::inlineCriticalCSS('
        body { font-family: Inter, sans-serif; }
        .test-container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .test-card { background: white; border-radius: 8px; padding: 1.5rem; margin: 1rem 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #10b981; }
        .error { color: #ef4444; }
    ');
    ?>
</head>
<body>
    <div class="test-container">
        <h1>Test ResourceOptimizer - BOSTARTER</h1>
        
        <div class="test-card">
            <h2>Test Caricamento Immagini</h2>
            <p>Immagine con lazy loading:</p>
            <?php echo ResourceOptimizer::optimizeImage('/BOSTARTER/frontend/images/logo.png', [
                'class' => 'test-image',
                'alt' => 'Logo BOSTARTER Test',
                'width' => 200,
                'height' => 100
            ]); ?>
            
            <p>Immagine responsive:</p>
            <?php echo ResourceOptimizer::responsiveImage('/BOSTARTER/frontend/images/hero.jpg', [
                'mobile' => ['max-width' => '480px', 'width' => '300'],
                'tablet' => ['max-width' => '768px', 'width' => '600'],
                'desktop' => ['width' => '1000']
            ]); ?>
        </div>
        
        <div class="test-card">
            <h2>Test Cache</h2>
            <?php
            // Test cache
            $cacheKey = 'test_data_' . date('Y-m-d-H');
            $cachedData = CacheManager::get($cacheKey);
            
            if ($cachedData === null) {
                $testData = [
                    'timestamp' => time(),
                    'message' => 'Dati generati e memorizzati in cache',
                    'random' => rand(1000, 9999)
                ];
                CacheManager::set($cacheKey, $testData, 3600);
                echo '<p class="success">✓ Dati memorizzati in cache</p>';
                echo '<pre>' . json_encode($testData, JSON_PRETTY_PRINT) . '</pre>';
            } else {
                echo '<p class="success">✓ Dati recuperati dalla cache</p>';
                echo '<pre>' . json_encode($cachedData, JSON_PRETTY_PRINT) . '</pre>';
            }
            
            // Statistiche cache
            $stats = CacheManager::getStatistics();
            echo '<h3>Statistiche Cache:</h3>';
            echo '<pre>' . json_encode($stats, JSON_PRETTY_PRINT) . '</pre>';
            ?>
        </div>
        
        <div class="test-card">
            <h2>Test Performance</h2>
            <?php
            // Test performance
            PerformanceHelper::startMeasurement('test_operation');
            
            // Simula operazione
            for ($i = 0; $i < 10000; $i++) {
                $temp = md5(uniqid());
            }
            
            $metrics = PerformanceHelper::endMeasurement('test_operation');
            echo '<p class="success">✓ Operazione completata</p>';
            echo '<p>Tempo: ' . round($metrics['duration'], 2) . 'ms</p>';
            echo '<p>Memoria: ' . round($metrics['memory']/1024, 2) . 'KB</p>';
            ?>
        </div>
        
        <div class="test-card">
            <h2>Test Service Worker</h2>
            <p>Service Worker generato:</p>
            <textarea readonly rows="10" cols="80" style="width: 100%; font-family: monospace;">
<?php echo ResourceOptimizer::generateServiceWorker([
    '/BOSTARTER/frontend/',
    '/BOSTARTER/frontend/css/main-styles.css',
    '/BOSTARTER/frontend/js/main.js'
]); ?>
            </textarea>
        </div>
    </div>
    
    <?php
    // Carica script con ResourceOptimizer
    echo ResourceOptimizer::loadScript('/BOSTARTER/frontend/js/main.js', ['defer' => true]);
    echo ResourceOptimizer::lazyLoadScript();
    
    // Performance finale
    $pageMetrics = PerformanceHelper::endMeasurement('test_page');
    echo ResourceOptimizer::inlineScript('
        console.log("Page load completed in ' . round($pageMetrics['duration'], 2) . 'ms");
        console.log("Memory used: ' . round($pageMetrics['memory']/1024, 2) . 'KB");
    ');
    
    // Reset per test successivi
    ResourceOptimizer::reset();
    ?>
</body>
</html>
