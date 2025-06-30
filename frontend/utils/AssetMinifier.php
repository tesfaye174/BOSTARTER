<?php
declare(strict_types=1);
namespace BOSTARTER\Utils;
class AssetMinifier {
    private const CACHE_DIR = __DIR__ . '/../cache/minified/';
    public static function minifyCSS(string $css): string {
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        $css = str_replace(';}', '}', $css);
        return trim($css);
    }
    public static function minifyJS(string $js): string {
        $js = preg_replace('/\/\/.*$/m', '', $js);
        $js = preg_replace('/\/\*[\s\S]*?\*\
        $js = preg_replace('/\s+/', ' ', $js);
        $js = preg_replace('/\s*([{}:;,=+\-*\/])\s*/', '$1', $js);
        return trim($js);
    }
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
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
        file_put_contents($outputPath, $minified);
        touch($outputPath, $lastModified);
        return $outputPath;
    }
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
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
        file_put_contents($outputPath, $minified);
        touch($outputPath, $lastModified);
        return $outputPath;
    }
    public static function generateAssetHash(string $filePath): string {
        if (!file_exists($filePath)) {
            return '';
        }
        return substr(md5_file($filePath), 0, 8);
    }
    public static function optimizeImage(string $inputPath, string $outputPath, int $quality = 85): bool {
        if (!extension_loaded('gd')) {
            return false;
        }
        $imageInfo = getimagesize($inputPath);
        if (!$imageInfo) {
            return false;
        }
        $imageType = $imageInfo[2];
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
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $result = false;
        if (strtolower(pathinfo($outputPath, PATHINFO_EXTENSION)) === 'webp') {
            $result = imagewebp($image, $outputPath, $quality);
        } else {
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $result = imagejpeg($image, $outputPath, $quality);
                    break;
                case IMAGETYPE_PNG:
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
    private static function resizeAndOptimizeImage(string $inputPath, string $outputPath, int $maxWidth): bool {
        if (!extension_loaded('gd')) {
            return false;
        }
        $imageInfo = getimagesize($inputPath);
        if (!$imageInfo) {
            return false;
        }
        list($originalWidth, $originalHeight, $imageType) = $imageInfo;
        if ($originalWidth <= $maxWidth) {
            return self::optimizeImage($inputPath, $outputPath);
        }
        $ratio = $originalHeight / $originalWidth;
        $newWidth = $maxWidth;
        $newHeight = (int) round($maxWidth * $ratio);
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
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        $result = imagewebp($resized, $outputPath, 85);
        imagedestroy($source);
        imagedestroy($resized);
        return $result;
    }
}
