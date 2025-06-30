<?php
class ResourceOptimizer {
    private static array $loadedScripts = [];
    private static array $loadedStyles = [];
    private static array $deferredScripts = [];
    private static array $preloadedAssets = [];
    private static array $inlineScripts = [];
    private static array $deferredStyles = [];
    public static function optimizeImage(string $imagePath, array $options = []): string {
        $defaults = [
            'lazy' => true,
            'sizes' => '100vw',
            'quality' => 85,
            'formats' => ['webp', 'original'],
            'class' => '',
            'alt' => 'Immagine BOSTARTER',
            'width' => null,
            'height' => null
        ];
        $opts = array_merge($defaults, $options);
        $imageInfo = pathinfo($imagePath);
        $basePath = "/BOSTARTER/frontend/images/";
        $widths = [320, 640, 960, 1280, 1920];
        $srcset = [];
        foreach ($widths as $width) {
            $srcset[] = "{$basePath}cache/{$imageInfo['filename']}-{$width}w.webp {$width}w";
        }
        $srcsetAttr = implode(', ', $srcset);
        $placeholder = 'data:image/svg+xml,%3Csvg xmlns="http:
        $attrs = [
            'class' => 'optimized-image ' . $opts['class'],
            'alt' => $opts['alt'],
            'loading' => $opts['lazy'] ? 'lazy' : 'eager',
            'decoding' => 'async'
        ];
        if ($opts['width']) $attrs['width'] = $opts['width'];
        if ($opts['height']) $attrs['height'] = $opts['height'];
        if ($opts['lazy']) {
            $attrs['src'] = $placeholder;
            $attrs['data-src'] = $imagePath;
            $attrs['data-sizes'] = $opts['sizes'];
            if ($srcsetAttr) {
                $attrs['data-srcset'] = $srcsetAttr;
            }
        } else {
            $attrs['src'] = $imagePath;
            $attrs['sizes'] = $opts['sizes'];
            if ($srcsetAttr) {
                $attrs['srcset'] = $srcsetAttr;
            }
        }
        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= " {$key}=\"{$value}\"";
        }
        return "<img{$attrString}>";
    }
    public static function responsiveImage(string $imagePath, array $breakpoints = []): string {
        $defaults = [
            'mobile' => ['max-width' => '768px', 'width' => '400'],
            'tablet' => ['max-width' => '1024px', 'width' => '800'],
            'desktop' => ['width' => '1200']
        ];
        $breakpoints = array_merge($defaults, $breakpoints);
        $sources = '';
        foreach ($breakpoints as $device => $config) {
            $mediaQuery = isset($config['max-width']) ? "(max-width: {$config['max-width']})" : '';
            $sources .= "<source media=\"{$mediaQuery}\" srcset=\"{$imagePath}\" sizes=\"{$config['width']}px\">";
        }
        return "<picture>{$sources}" . self::optimizeImage($imagePath) . "</picture>";
    }
    public static function preloadAsset(string $href, string $type = 'script', array $options = []): string {
        if (in_array($href, self::$preloadedAssets)) {
            return ''; 
        }
        self::$preloadedAssets[] = $href;
        $defaults = [
            'as' => self::getAsType($type),
            'crossorigin' => 'anonymous',
            'importance' => 'high'
        ];
        $opts = array_merge($defaults, $options);
        $attrs = "rel=\"preload\" href=\"{$href}\"";
        foreach ($opts as $key => $value) {
            if ($value) $attrs .= " {$key}=\"{$value}\"";
        }
        return "<link {$attrs}>";
    }
    public static function loadScript(string $src, array $options = []): string {
        if (in_array($src, self::$loadedScripts)) {
            return ''; 
        }
        self::$loadedScripts[] = $src;
        $defaults = [
            'defer' => true,
            'async' => false,
            'module' => false,
            'integrity' => '',
            'crossorigin' => 'anonymous'
        ];
        $opts = array_merge($defaults, $options);
        $attrs = "src=\"{$src}\"";
        if ($opts['defer']) $attrs .= " defer";
        if ($opts['async']) $attrs .= " async";
        if ($opts['module']) $attrs .= " type=\"module\"";
        if ($opts['integrity']) $attrs .= " integrity=\"{$opts['integrity']}\"";
        if ($opts['crossorigin']) $attrs .= " crossorigin=\"{$opts['crossorigin']}\"";
        return "<script {$attrs}></script>";
    }
    public static function loadStyle(string $href, array $options = []): string {
        if (in_array($href, self::$loadedStyles)) {
            return ''; 
        }
        self::$loadedStyles[] = $href;
        $defaults = [
            'media' => 'all',
            'defer' => false,
            'critical' => false,
            'integrity' => '',
            'crossorigin' => 'anonymous'
        ];
        $opts = array_merge($defaults, $options);
        if ($opts['defer']) {
            return self::deferredStyle($href, $opts);
        }
        $attrs = "rel=\"stylesheet\" href=\"{$href}\" media=\"{$opts['media']}\"";
        if ($opts['integrity']) $attrs .= " integrity=\"{$opts['integrity']}\"";
        if ($opts['crossorigin']) $attrs .= " crossorigin=\"{$opts['crossorigin']}\"";
        return "<link {$attrs}>";
    }
    private static function deferredStyle(string $href, array $options): string {
        $script = "<script>
        (function() {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '{$href}';
            link.media = '{$options['media']}';
            link.onload = function() { this.media = 'all'; };
            document.head.appendChild(link);
        })();
        </script>";
        return $script;
    }
    public static function inlineScript(string $script, array $options = []): string {
        $hash = md5($script);
        if (in_array($hash, self::$inlineScripts)) {
            return ''; 
        }
        self::$inlineScripts[] = $hash;
        $defaults = [
            'defer' => false,
            'async' => false,
            'module' => false
        ];
        $opts = array_merge($defaults, $options);
        $attrs = '';
        if ($opts['defer']) $attrs .= ' defer';
        if ($opts['async']) $attrs .= ' async';
        if ($opts['module']) $attrs .= ' type="module"';
        return "<script{$attrs}>{$script}</script>";
    }
    public static function generateServiceWorker(array $assets = []): string {
        $version = date('Y-m-d-H-i-s');
        $cacheList = json_encode($assets);
        $sw = "const CACHE_NAME = 'bostarter-v{$version}';
const urlsToCache = {$cacheList};
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(urlsToCache))
    );
});
self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});";
        return $sw;
    }
    public static function resourceHints(): string {
        $hints = [
            '<link rel="dns-prefetch" href="
            '<link rel="dns-prefetch" href="
            '<link rel="preconnect" href="https:
            '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">',
            '<meta name="format-detection" content="telephone=no">',
            '<meta name="theme-color" content="#2563eb">',
        ];
        return implode("\n", $hints);
    }
    public static function lazyLoadScript(): string {
        return "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const lazyImages = document.querySelectorAll('img[data-src]');
            const lazyImageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                        }
                        if (img.dataset.sizes) {
                            img.sizes = img.dataset.sizes;
                        }
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            });
            lazyImages.forEach(img => {
                lazyImageObserver.observe(img);
            });
        });
        </script>";
    }
    private static function getAsType(string $type): string {
        $types = [
            'script' => 'script',
            'style' => 'style',
            'css' => 'style',
            'js' => 'script',
            'font' => 'font',
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio'
        ];
        return $types[$type] ?? 'script';
    }
    public static function coreWebVitals(): string {
        return "<script>
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.entryType === 'navigation') {
                        console.log('Navigation timing:', entry);
                    }
                    if (entry.entryType === 'largest-contentful-paint') {
                        console.log('LCP:', entry.startTime);
                    }
                }
            });
            observer.observe({entryTypes: ['navigation', 'largest-contentful-paint']});
        }
        </script>";
    }
    public static function preloadCriticalResources(): string {
        $criticalResources = [
            '/BOSTARTER/frontend/css/main-styles.css' => 'style',
            '/BOSTARTER/frontend/js/main.js' => 'script',
            '/BOSTARTER/frontend/images/logo.webp' => 'image'
        ];
        $output = '';
        foreach ($criticalResources as $href => $as) {
            $output .= "<link rel=\"preload\" href=\"{$href}\" as=\"{$as}\">\n";
        }
        return $output;
    }
    public static function deferResource(string $src, string $type = 'script'): void {
        self::$deferredScripts[] = [
            'src' => $src,
            'type' => $type
        ];
    }
    public static function loadDeferredResources(): string {
        $output = '<script>';
        $output .= 'document.addEventListener("DOMContentLoaded", function() {';
        foreach (self::$deferredScripts as $resource) {
            if ($resource['type'] === 'script') {
                $output .= "var script = document.createElement('script');";
                $output .= "script.src = '{$resource['src']}';";
                $output .= "document.head.appendChild(script);";
            } elseif ($resource['type'] === 'style') {
                $output .= "var link = document.createElement('link');";
                $output .= "link.rel = 'stylesheet';";
                $output .= "link.href = '{$resource['src']}';";
                $output .= "document.head.appendChild(link);";
            }
        }
        $output .= '});';
        $output .= '</script>';
        return $output;
    }
    public static function inlineCriticalCSS(string $css): string {
        return "<style type=\"text/css\">{$css}</style>";
    }
    public static function reset(): void {
        self::$loadedScripts = [];
        self::$loadedStyles = [];
        self::$deferredScripts = [];
        self::$preloadedAssets = [];
        self::$inlineScripts = [];
        self::$deferredStyles = [];
    }
}
?>
