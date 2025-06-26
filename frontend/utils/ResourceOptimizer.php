// Classe moderna per l'ottimizzazione delle risorse
class ResourceOptimizer {
    private static array $loadedScripts = [];
    private static array $loadedStyles = [];
    private static array $deferredScripts = [];
    
    // Gestione lazy loading immagini
    public static function optimizeImage(string $imagePath, array $options = []): string {
        $defaults = [
            'lazy' => true,
            'sizes' => '100vw',
            'quality' => 85,
            'formats' => ['webp', 'original']
        ];
        
        $opts = array_merge($defaults, $options);
        $imageInfo = pathinfo($imagePath);
        $basePath = "/BOSTARTER/frontend/images/";
        
        // Genera srcset per responsive images
        $widths = [320, 640, 960, 1280, 1920];
        $srcset = [];
        
        foreach ($widths as $width) {
            $srcset[] = "{$basePath}cache/{$imageInfo['filename']}-{$width}w.webp {$width}w";
        }
        
        $srcsetAttr = implode(', ', $srcset);
        
        return sprintf(
            '<img src="%s" %s class="optimized-image %s" loading="%s" sizes="%s" alt="%s">',
            $imagePath,
            $srcsetAttr ? "srcset=\"{$srcsetAttr}\"" : '',
            $options['class'] ?? '',
            $opts['lazy'] ? 'lazy' : 'eager',
            $opts['sizes'],
            $options['alt'] ?? 'Immagine BOSTARTER'
        );
    }
    
    // Caricamento ottimizzato script
    public static function loadScript(string $src, array $options = []): void {
        if (in_array($src, self::$loadedScripts)) {
            return;
        }
        
        $defaults = [
            'defer' => true,
            'async' => false,
            'module' => false,
            'preload' => true
        ];
        
        $opts = array_merge($defaults, $options);
        
        if ($opts['preload']) {
            echo "<link rel=\"preload\" href=\"{$src}\" as=\"script\">";
        }
        
        $attributes = [];
        if ($opts['defer']) $attributes[] = 'defer';
        if ($opts['async']) $attributes[] = 'async';
        if ($opts['module']) $attributes[] = 'type="module"';
        
        echo sprintf(
            '<script src="%s" %s></script>',
            $src,
            implode(' ', $attributes)
        );
        
        self::$loadedScripts[] = $src;
    }
    
    // Caricamento ottimizzato CSS
    public static function loadStyle(string $href, array $options = []): void {
        if (in_array($href, self::$loadedStyles)) {
            return;
        }
        
        $defaults = [
            'preload' => true,
            'media' => 'all'
        ];
        
        $opts = array_merge($defaults, $options);
        
        if ($opts['preload']) {
            echo "<link rel=\"preload\" href=\"{$href}\" as=\"style\">";
        }
        
        echo sprintf(
            '<link rel="stylesheet" href="%s" media="%s">',
            $href,
            $opts['media']
        );
        
        self::$loadedStyles[] = $href;
    }
    
    // Ottimizzazione critica CSS
    public static function inlineCriticalCSS(string $css): void {
        echo "<style id=\"critical-css\">{$css}</style>";
    }
    
    // Differimento caricamento risorse non critiche
    public static function deferResource(string $src, string $type = 'script'): void {
        self::$deferredScripts[] = [
            'src' => $src,
            'type' => $type
        ];
    }
    
    // Caricamento risorse differite
    public static function loadDeferredResources(): void {
        echo "<script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    [" . json_encode(self::$deferredScripts) . "].forEach(function(resource) {
                        if (resource.type === 'script') {
                            var script = document.createElement('script');
                            script.src = resource.src;
                            document.body.appendChild(script);
                        } else if (resource.type === 'style') {
                            var link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = resource.src;
                            document.head.appendChild(link);
                        }
                    });
                }, 1000);
            });
        </script>";
    }
}

// Sistema di cache moderno
class CacheManager {
    private static array $cache = [];
    private const CACHE_DIR = __DIR__ . '/../cache/';
    
    public static function get(string $key) {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        $cacheFile = self::CACHE_DIR . md5($key) . '.cache';
        if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 3600) {
            $data = file_get_contents($cacheFile);
            self::$cache[$key] = unserialize($data);
            return self::$cache[$key];
        }
        
        return null;
    }
    
    public static function set(string $key, $value, int $ttl = 3600): void {
        self::$cache[$key] = $value;
        
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0777, true);
        }
        
        $cacheFile = self::CACHE_DIR . md5($key) . '.cache';
        file_put_contents($cacheFile, serialize($value));
    }
    
    public static function clear(string $key = null): void {
        if ($key === null) {
            self::$cache = [];
            array_map('unlink', glob(self::CACHE_DIR . '*.cache'));
        } else {
            unset(self::$cache[$key]);
            $cacheFile = self::CACHE_DIR . md5($key) . '.cache';
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
        }
    }
}

// Helper per la gestione delle prestazioni
class PerformanceHelper {
    private static array $metrics = [];
    private static float $pageStart;
    
    public static function startMeasurement(string $key): void {
        self::$metrics[$key] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage()
        ];
    }
    
    public static function endMeasurement(string $key): array {
        if (!isset(self::$metrics[$key])) {
            return [];
        }
        
        $end = microtime(true);
        $memoryEnd = memory_get_usage();
        
        $metrics = [
            'duration' => ($end - self::$metrics[$key]['start']) * 1000, // in ms
            'memory' => $memoryEnd - self::$metrics[$key]['memory_start']
        ];
        
        // Log metrics for analysis
        if (defined('PERFORMANCE_LOG') && PERFORMANCE_LOG) {
            error_log(sprintf(
                "Performance [%s]: %.2fms, Memory: %.2fKB",
                $key,
                $metrics['duration'],
                $metrics['memory'] / 1024
            ));
        }
        
        return $metrics;
    }
    
    public static function initPageMetrics(): void {
        self::$pageStart = microtime(true);
        
        // Register shutdown function to log overall metrics
        register_shutdown_function(function() {
            $totalTime = (microtime(true) - self::$pageStart) * 1000;
            $totalMemory = memory_get_peak_usage() / 1024 / 1024;
            
            if (defined('PERFORMANCE_LOG') && PERFORMANCE_LOG) {
                error_log(sprintf(
                    "Page Load Complete - Time: %.2fms, Peak Memory: %.2fMB",
                    $totalTime,
                    $totalMemory
                ));
            }
        });
    }
}
