<?php
/**
 * BOSTARTER Asset Minifier
 * Sistema di minificazione per CSS e JavaScript
 * 
 * @version 2.0
 * @package BOSTARTER\Utils
 */

declare(strict_types=1);

namespace BOSTARTER\Utils;

class AssetMinifier {
    private const CACHE_DIR = __DIR__ . '/../cache/minified/';
    
    /**
     * Minifica CSS rimuovendo commenti, spazi extra e line breaks
     */
    public static function minifyCSS(string $css): string {
        // Rimuovi commenti CSS
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Rimuovi spazi extra
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Rimuovi spazi attorno ai caratteri speciali
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        // Rimuovi punti e virgola prima delle parentesi graffe
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Minifica JavaScript (versione semplice)
     */
    public static function minifyJS(string $js): string {
        // Rimuovi commenti single line
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Rimuovi commenti multi-line
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Rimuovi spazi extra preservando strings
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Rimuovi spazi attorno agli operatori
        $js = preg_replace('/\s*([{}:;,=+\-*\/])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Combina e minifica più file CSS
     */
    public static function combineCSS(array $files, string $outputName = 'combined.min.css'): string {
        $combined = '';
        $lastModified = 0;
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $combined .= file_get_contents($file) . "\n";
                $lastModified = max($lastModified, filemtime($file));
            }
        }
        
        $minified = self::minifyCSS($combined);
        $outputPath = self::CACHE_DIR . $outputName;
        
        // Crea directory se non esiste
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
        
        // Salva il file minificato
        file_put_contents($outputPath, $minified);
        
        // Imposta il timestamp
        touch($outputPath, $lastModified);
        
        return $outputPath;
    }
    
    /**
     * Combina e minifica più file JavaScript
     */
    public static function combineJS(array $files, string $outputName = 'combined.min.js'): string {
        $combined = '';
        $lastModified = 0;
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $combined .= file_get_contents($file) . ";\n";
                $lastModified = max($lastModified, filemtime($file));
            }
        }
        
        $minified = self::minifyJS($combined);
        $outputPath = self::CACHE_DIR . $outputName;
        
        // Crea directory se non esiste
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
        
        // Salva il file minificato
        file_put_contents($outputPath, $minified);
        
        // Imposta il timestamp
        touch($outputPath, $lastModified);
        
        return $outputPath;
    }
    
    /**
     * Genera hash per cache busting
     */
    public static function generateAssetHash(string $filePath): string {
        if (!file_exists($filePath)) {
            return '';
        }
        
        return substr(md5_file($filePath), 0, 8);
    }
    
    /**
     * Ottimizza immagini (richiede GD extension)
     */
    public static function optimizeImage(string $inputPath, string $outputPath, int $quality = 85): bool {
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $imageInfo = getimagesize($inputPath);
        if (!$imageInfo) {
            return false;
        }
        
        $imageType = $imageInfo[2];
        
        // Carica l'immagine in base al tipo
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($inputPath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($inputPath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($inputPath);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Crea directory di output se non esiste
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Salva l'immagine ottimizzata
        $result = false;
        if (strtolower(pathinfo($outputPath, PATHINFO_EXTENSION)) === 'webp') {
            $result = imagewebp($image, $outputPath, $quality);
        } else {
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $result = imagejpeg($image, $outputPath, $quality);
                    break;
                case IMAGETYPE_PNG:
                    // PNG quality range is 0-9
                    $pngQuality = (int) round((100 - $quality) / 10);
                    $result = imagepng($image, $outputPath, $pngQuality);
                    break;
                case IMAGETYPE_GIF:
                    $result = imagegif($image, $outputPath);
                    break;
            }
        }
        
        imagedestroy($image);
        return $result;
    }
    
    /**
     * Genera versioni responsive di un'immagine
     */
    public static function generateResponsiveImages(string $inputPath, array $widths = [320, 640, 960, 1280, 1920]): array {
        $generated = [];
        $pathInfo = pathinfo($inputPath);
        $basePath = $pathInfo['dirname'] . '/cache/';
        
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        
        foreach ($widths as $width) {
            $outputPath = $basePath . $pathInfo['filename'] . "-{$width}w.webp";
            
            if (self::resizeAndOptimizeImage($inputPath, $outputPath, $width)) {
                $generated[$width] = $outputPath;
            }
        }
        
        return $generated;
    }
    
    /**
     * Ridimensiona e ottimizza un'immagine
     */
    private static function resizeAndOptimizeImage(string $inputPath, string $outputPath, int $maxWidth): bool {
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $imageInfo = getimagesize($inputPath);
        if (!$imageInfo) {
            return false;
        }
        
        list($originalWidth, $originalHeight, $imageType) = $imageInfo;
        
        // Se l'immagine è già più piccola, non ridimensionare
        if ($originalWidth <= $maxWidth) {
            return self::optimizeImage($inputPath, $outputPath);
        }
        
        // Calcola nuove dimensioni mantenendo le proporzioni
        $ratio = $originalHeight / $originalWidth;
        $newWidth = $maxWidth;
        $newHeight = (int) round($maxWidth * $ratio);
        
        // Carica l'immagine originale
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($inputPath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($inputPath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($inputPath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        // Crea nuova immagine ridimensionata
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserva trasparenza per PNG e GIF
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Ridimensiona
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Salva come WebP
        $result = imagewebp($resized, $outputPath, 85);
        
        // Cleanup
        imagedestroy($source);
        imagedestroy($resized);
        
        return $result;
    }
}
