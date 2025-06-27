<?php
/**
 * BOSTARTER Build System
 * Sistema di build per ottimizzazione assets
 * 
 * @version 2.0
 * @package BOSTARTER\Utils
 */

declare(strict_types=1);

require_once __DIR__ . '/ResourceOptimizer.php';
require_once __DIR__ . '/AssetMinifier.php';
require_once __DIR__ . '/CacheManager.php';

use BOSTARTER\Utils\AssetMinifier;
use BOSTARTER\Utils\CacheManager;

class BuildSystem {
    private const BUILD_DIR = __DIR__ . '/../build/';
    private const ASSETS_CONFIG = __DIR__ . '/../config/assets.json';
    
    private static array $buildManifest = [];
    
    /**
     * Esegue il build completo degli assets
     */
    public static function build(array $options = []): array {
        $startTime = microtime(true);
        
        echo "🚀 Avvio build sistema BOSTARTER...\n";
        
        // Crea directory di build
        self::createBuildDirectories();
        
        // Carica configurazione assets
        $config = self::loadAssetsConfig();
        
        // Build CSS
        $cssResults = self::buildCSS($config['css'] ?? []);
        
        // Build JavaScript
        $jsResults = self::buildJavaScript($config['js'] ?? []);
        
        // Ottimizza immagini
        $imageResults = self::optimizeImages($config['images'] ?? []);
        
        // Genera manifest
        self::generateManifest($cssResults, $jsResults, $imageResults);
        
        // Pulisci cache vecchia
        self::cleanOldCache();
        
        $buildTime = (microtime(true) - $startTime) * 1000;
        
        $results = [
            'success' => true,
            'build_time' => round($buildTime, 2),
            'css' => $cssResults,
            'js' => $jsResults,
            'images' => $imageResults
        ];
        
        echo "✅ Build completato in {$results['build_time']}ms\n";
        
        return $results;
    }
    
    /**
     * Crea le directory necessarie per il build
     */
    private static function createBuildDirectories(): void {
        $directories = [
            self::BUILD_DIR,
            self::BUILD_DIR . 'css/',
            self::BUILD_DIR . 'js/',
            self::BUILD_DIR . 'images/',
            __DIR__ . '/../cache/',
            __DIR__ . '/../cache/minified/',
            __DIR__ . '/../cache/images/'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Carica configurazione assets
     */
    private static function loadAssetsConfig(): array {
        if (file_exists(self::ASSETS_CONFIG)) {
            return json_decode(file_get_contents(self::ASSETS_CONFIG), true) ?? [];
        }
        
        // Configurazione default
        return [
            'css' => [
                'critical' => [
                    __DIR__ . '/../css/main-styles.css'
                ],
                'non_critical' => [
                    __DIR__ . '/../css/animations.css'
                ]
            ],
            'js' => [
                'critical' => [
                    __DIR__ . '/../js/main.js'
                ],
                'modules' => [
                    __DIR__ . '/../js/notifications.js'
                ]
            ],
            'images' => [
                'optimize' => true,
                'formats' => ['webp', 'original'],
                'sizes' => [320, 640, 960, 1280, 1920]
            ]
        ];
    }
    
    /**
     * Build CSS files
     */
    private static function buildCSS(array $cssConfig): array {
        $results = [];
        
        foreach ($cssConfig as $bundle => $files) {
            echo "📦 Building CSS bundle: {$bundle}\n";
            
            $outputFile = AssetMinifier::combineCSS($files, "{$bundle}.min.css");
            $hash = AssetMinifier::generateAssetHash($outputFile);
            $finalName = "{$bundle}.{$hash}.min.css";
            $finalPath = self::BUILD_DIR . "css/{$finalName}";
            
            // Copia con hash per cache busting
            copy($outputFile, $finalPath);
            
            $results[$bundle] = [
                'file' => $finalName,
                'path' => $finalPath,
                'hash' => $hash,
                'size' => filesize($finalPath)
            ];
            
            echo "   ✅ {$finalName} ({$results[$bundle]['size']} bytes)\n";
        }
        
        return $results;
    }
    
    /**
     * Build JavaScript files
     */
    private static function buildJavaScript(array $jsConfig): array {
        $results = [];
        
        foreach ($jsConfig as $bundle => $files) {
            echo "📦 Building JS bundle: {$bundle}\n";
            
            $outputFile = AssetMinifier::combineJS($files, "{$bundle}.min.js");
            $hash = AssetMinifier::generateAssetHash($outputFile);
            $finalName = "{$bundle}.{$hash}.min.js";
            $finalPath = self::BUILD_DIR . "js/{$finalName}";
            
            // Copia con hash per cache busting
            copy($outputFile, $finalPath);
            
            $results[$bundle] = [
                'file' => $finalName,
                'path' => $finalPath,
                'hash' => $hash,
                'size' => filesize($finalPath)
            ];
            
            echo "   ✅ {$finalName} ({$results[$bundle]['size']} bytes)\n";
        }
        
        return $results;
    }
    
    /**
     * Ottimizza le immagini
     */
    private static function optimizeImages(array $imageConfig): array {
        $results = [];
        
        if (!($imageConfig['optimize'] ?? false)) {
            return $results;
        }
        
        echo "🖼️  Ottimizzando immagini...\n";
        
        $imageDir = __DIR__ . '/../images/';
        $outputDir = self::BUILD_DIR . 'images/';
        
        if (!is_dir($imageDir)) {
            return $results;
        }
        
        $images = glob($imageDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        
        foreach ($images as $imagePath) {
            $filename = basename($imagePath);
            $pathInfo = pathinfo($imagePath);
            
            // Ottimizza immagine originale
            $optimizedPath = $outputDir . $pathInfo['filename'] . '.webp';
            if (AssetMinifier::optimizeImage($imagePath, $optimizedPath)) {
                $results[$filename] = [
                    'original' => $imagePath,
                    'optimized' => $optimizedPath,
                    'size' => filesize($optimizedPath)
                ];
                
                // Genera versioni responsive
                if (!empty($imageConfig['sizes'])) {
                    $responsiveImages = AssetMinifier::generateResponsiveImages($imagePath, $imageConfig['sizes']);
                    $results[$filename]['responsive'] = $responsiveImages;
                }
                
                echo "   ✅ {$filename} → {$pathInfo['filename']}.webp\n";
            }
        }
        
        return $results;
    }
    
    /**
     * Genera manifest file
     */
    private static function generateManifest(array $css, array $js, array $images): void {
        $manifest = [
            'timestamp' => time(),
            'version' => '2.0',
            'css' => $css,
            'js' => $js,
            'images' => $images
        ];
        
        file_put_contents(self::BUILD_DIR . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        
        // Salva anche in cache per accesso rapido
        CacheManager::set('build_manifest', $manifest, 86400); // 24 ore
        
        self::$buildManifest = $manifest;
    }
    
    /**
     * Pulisce cache vecchia
     */
    private static function cleanOldCache(): void {
        $cacheDir = __DIR__ . '/../cache/';
        $files = glob($cacheDir . '*.cache');
        $now = time();
        
        foreach ($files as $file) {
            if ($now - filemtime($file) > 86400) { // 24 ore
                unlink($file);
            }
        }
    }
    
    /**
     * Ottiene il manifest del build
     */
    public static function getManifest(): array {
        if (empty(self::$buildManifest)) {
            $manifest = CacheManager::get('build_manifest');
            if ($manifest) {
                self::$buildManifest = $manifest;
            } else {
                $manifestFile = self::BUILD_DIR . 'manifest.json';
                if (file_exists($manifestFile)) {
                    self::$buildManifest = json_decode(file_get_contents($manifestFile), true) ?? [];
                }
            }
        }
        
        return self::$buildManifest;
    }
    
    /**
     * Ottiene URL ottimizzato per un asset
     */
    public static function asset(string $type, string $bundle): string {
        $manifest = self::getManifest();
        
        if (isset($manifest[$type][$bundle]['file'])) {
            return "/BOSTARTER/frontend/build/{$type}/{$manifest[$type][$bundle]['file']}";
        }
        
        // Fallback to original
        return "/BOSTARTER/frontend/{$type}/{$bundle}";
    }
    
    /**
     * Controlla se è necessario un rebuild
     */
    public static function needsRebuild(): bool {
        $manifest = self::getManifest();
        
        if (empty($manifest)) {
            return true;
        }
        
        // Controlla se i file sorgente sono più recenti del manifest
        $manifestTime = $manifest['timestamp'] ?? 0;
        $sourceFiles = [
            __DIR__ . '/../css/main-styles.css',
            __DIR__ . '/../js/main.js',
            __DIR__ . '/../css/animations.css'
        ];
        
        foreach ($sourceFiles as $file) {
            if (file_exists($file) && filemtime($file) > $manifestTime) {
                return true;
            }
        }
        
        return false;
    }
}

// Se eseguito da CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $options = getopt('h', ['help', 'clean', 'watch']);
    
    if (isset($options['h']) || isset($options['help'])) {
        echo "BOSTARTER Build System\n\n";
        echo "Utilizzo: php build.php [opzioni]\n\n";
        echo "Opzioni:\n";
        echo "  -h, --help     Mostra questo aiuto\n";
        echo "  --clean        Pulisce cache e build precedenti\n";
        echo "  --watch        Modalità watch per rebuild automatico\n\n";
        exit(0);
    }
    
    if (isset($options['clean'])) {
        echo "🧹 Pulizia cache e build...\n";
        // Pulisci directory
        array_map('unlink', glob(__DIR__ . '/../cache/*.cache'));
        array_map('unlink', glob(__DIR__ . '/../build/css/*.css'));
        array_map('unlink', glob(__DIR__ . '/../build/js/*.js'));
        echo "✅ Pulizia completata\n";
    }
    
    if (isset($options['watch'])) {
        echo "👀 Modalità watch attivata. Premi Ctrl+C per uscire.\n";
        while (true) {
            if (BuildSystem::needsRebuild()) {
                echo "\n🔄 Rilevati cambiamenti, avvio rebuild...\n";
                BuildSystem::build();
            }
            sleep(2);
        }
    } else {
        BuildSystem::build();
    }
}
