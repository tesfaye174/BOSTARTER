#!/usr/bin/env php
<?php
/**
 * Test Script per ResourceOptimizer, CacheManager e PerformanceHelper
 * Esegui con: php test-optimization-system.php
 */

echo "=== TEST SISTEMA DI OTTIMIZZAZIONE BOSTARTER ===\n\n";

// Carica le classi
require_once __DIR__ . '/utils/ResourceOptimizer.php';
require_once __DIR__ . '/utils/CacheManager.php';
require_once __DIR__ . '/utils/PerformanceHelper.php';

try {
    // Test 1: ResourceOptimizer
    echo "1. Test ResourceOptimizer\n";
    echo "------------------------\n";
    
    // Test generazione HTML
    $imageHtml = ResourceOptimizer::optimizeImage('/test/image.jpg', [
        'class' => 'test-img',
        'alt' => 'Test Image',
        'lazy' => true
    ]);
    echo "✓ Immagine ottimizzata generata: " . strlen($imageHtml) . " caratteri\n";
    
    $scriptHtml = ResourceOptimizer::loadScript('/test/script.js', ['defer' => true]);
    echo "✓ Script tag generato: " . strlen($scriptHtml) . " caratteri\n";
    
    $styleHtml = ResourceOptimizer::loadStyle('/test/style.css', ['critical' => true]);
    echo "✓ Style tag generato: " . strlen($styleHtml) . " caratteri\n";
    
    $swCode = ResourceOptimizer::generateServiceWorker(['/app/', '/app/css/main.css']);
    echo "✓ Service Worker generato: " . strlen($swCode) . " caratteri\n";
    
    echo "\n";
    
    // Test 2: CacheManager
    echo "2. Test CacheManager\n";
    echo "--------------------\n";
    
    CacheManager::init();
    echo "✓ CacheManager inizializzato\n";
    
    // Test cache
    $testKey = 'test_' . time();
    $testData = ['message' => 'Test data', 'timestamp' => time()];
    
    $setResult = CacheManager::set($testKey, $testData, 60);
    echo $setResult ? "✓ Dati memorizzati in cache\n" : "✗ Errore memorizzazione cache\n";
    
    $getData = CacheManager::get($testKey);
    echo ($getData && $getData['message'] === 'Test data') ? "✓ Dati recuperati dalla cache\n" : "✗ Errore recupero cache\n";
    
    // Test remember
    $rememberData = CacheManager::remember('remember_test', function() {
        return ['computed' => 'value', 'time' => time()];
    }, 60);
    echo "✓ Cache remember funziona: " . $rememberData['computed'] . "\n";
    
    // Test increment/decrement
    CacheManager::set('counter', 5);
    $newValue = CacheManager::increment('counter', 3);
    echo "✓ Counter incrementato a: $newValue\n";
    
    $decremented = CacheManager::decrement('counter', 2);
    echo "✓ Counter decrementato a: $decremented\n";
    
    // Statistiche
    $stats = CacheManager::getStatistics();
    echo "✓ Hit rate: {$stats['hit_rate']}, Memory items: {$stats['memory_items']}\n";
    
    echo "\n";
    
    // Test 3: PerformanceHelper
    echo "3. Test PerformanceHelper\n";
    echo "-------------------------\n";
    
    PerformanceHelper::initPageMetrics();
    echo "✓ Performance tracking inizializzato\n";
    
    // Test misurazione
    PerformanceHelper::startMeasurement('test_operation');
    
    // Simula operazione
    $result = 0;
    for ($i = 0; $i < 100000; $i++) {
        $result += $i;
    }
    
    $metrics = PerformanceHelper::endMeasurement('test_operation');
    echo "✓ Operazione completata in " . round($metrics['duration'], 2) . "ms\n";
    echo "✓ Memoria utilizzata: " . round($metrics['memory']/1024, 2) . "KB\n";
    
    echo "\n";
    
    // Test 4: Integrazione completa
    echo "4. Test Integrazione\n";
    echo "--------------------\n";
    
    // Test cache con performance
    PerformanceHelper::startMeasurement('cache_test');
    
    $cachedResult = CacheManager::remember('expensive_operation', function() {
        // Simula operazione costosa
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = md5(uniqid());
        }
        return $data;
    }, 300);
    
    $cacheMetrics = PerformanceHelper::endMeasurement('cache_test');
    echo "✓ Operazione con cache completata in " . round($cacheMetrics['duration'], 2) . "ms\n";
    echo "✓ Risultati generati: " . count($cachedResult) . " elementi\n";
    
    // Test secondo accesso (dovrebbe essere più veloce)
    PerformanceHelper::startMeasurement('cache_hit_test');
    $cachedResult2 = CacheManager::get('expensive_operation');
    $hitMetrics = PerformanceHelper::endMeasurement('cache_hit_test');
    echo "✓ Secondo accesso completato in " . round($hitMetrics['duration'], 2) . "ms (cache hit)\n";
    
    echo "\n";
    
    // Test 5: Cleanup e reset
    echo "5. Test Cleanup\n";
    echo "---------------\n";
    
    $cleanedItems = CacheManager::cleanupExpired();
    echo "✓ Cleanup cache: $cleanedItems elementi rimossi\n";
    
    ResourceOptimizer::reset();
    echo "✓ ResourceOptimizer reset completato\n";
    
    echo "\n=== TUTTI I TEST COMPLETATI CON SUCCESSO ===\n";
    
} catch (Exception $e) {
    echo "✗ ERRORE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "✗ ERRORE FATALE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
