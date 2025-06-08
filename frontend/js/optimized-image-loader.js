/**
 * BOSTARTER Optimized Image Loader
 * Implements lazy loading, WebP support, and responsive images
 */

class OptimizedImageLoader {
    constructor() {
        this.intersectionObserver = null;
        this.imageCache = new Map();
        this.supportedFormats = this.checkFormatSupport();
        this.init();
    }

    init() {
        this.setupIntersectionObserver();
        this.processImages();
        this.setupPreloadHints();
    }

    checkFormatSupport() {
        const formats = {
            webp: false,
            avif: false
        };

        // Check WebP support
        const webpCanvas = document.createElement('canvas');
        webpCanvas.width = 1;
        webpCanvas.height = 1;
        formats.webp = webpCanvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;

        // Check AVIF support
        const avifImg = new Image();
        avifImg.onload = () => formats.avif = true;
        avifImg.src = 'data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQAMAAAAABNjb2xybmNseAABAA0ABoAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgABogQEAwgMg8f8D///8WfhwB8+ErK42A=';

        return formats;
    }

    setupIntersectionObserver() {
        if (!('IntersectionObserver' in window)) {
            // Fallback for older browsers
            this.loadAllImages();
            return;
        }

        this.intersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.intersectionObserver.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
    }

    processImages() {
        // Find all images with data-src (lazy loading)
        const lazyImages = document.querySelectorAll('img[data-src], picture[data-src]');
        lazyImages.forEach(img => {
            this.intersectionObserver.observe(img);
        });

        // Process existing images for optimization
        const existingImages = document.querySelectorAll('img:not([data-src])');
        existingImages.forEach(img => this.optimizeExistingImage(img));
    }

    async loadImage(element) {
        const src = element.dataset.src;
        if (!src) return;

        try {
            // Show loading state
            element.classList.add('loading');

            // Determine best format and size
            const optimizedSrc = await this.getOptimizedImageSrc(src, element);

            if (element.tagName.toLowerCase() === 'img') {
                // Preload the image
                const img = new Image();
                img.onload = () => {
                    element.src = optimizedSrc;
                    element.classList.remove('loading');
                    element.classList.add('loaded');
                    this.imageCache.set(src, optimizedSrc);
                };
                img.onerror = () => {
                    element.src = src; // Fallback to original
                    element.classList.remove('loading');
                    element.classList.add('error');
                };
                img.src = optimizedSrc;
            } else if (element.tagName.toLowerCase() === 'picture') {
                this.loadPictureElement(element, src);
            }

        } catch (error) {
            console.warn('Error loading image:', error);
            element.src = src; // Fallback
            element.classList.remove('loading');
            element.classList.add('error');
        }
    }

    async getOptimizedImageSrc(originalSrc, element) {
        // Check cache first
        if (this.imageCache.has(originalSrc)) {
            return this.imageCache.get(originalSrc);
        }

        // Determine optimal size based on element size and device pixel ratio
        const rect = element.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        const targetWidth = Math.ceil(rect.width * dpr);
        const targetHeight = Math.ceil(rect.height * dpr);

        // Build optimized URL (assuming you have an image optimization service)
        let optimizedSrc = originalSrc;

        // Try to use WebP format if supported
        if (this.supportedFormats.webp && !originalSrc.includes('.svg')) {
            optimizedSrc = this.convertToWebP(originalSrc);
        }

        // Add responsive sizing parameters
        if (targetWidth > 0 && targetHeight > 0) {
            optimizedSrc = this.addSizeParameters(optimizedSrc, targetWidth, targetHeight);
        }

        return optimizedSrc;
    }

    convertToWebP(src) {
        // Convert image extension to WebP
        return src.replace(/\.(jpg|jpeg|png)$/i, '.webp');
    }

    addSizeParameters(src, width, height) {
        // Add size parameters (adjust based on your image service)
        const separator = src.includes('?') ? '&' : '?';
        return `${src}${separator}w=${width}&h=${height}&fit=cover&auto=compress`;
    }

    loadPictureElement(picture, baseSrc) {
        const sources = picture.querySelectorAll('source');
        const img = picture.querySelector('img');

        // Update source elements with optimized versions
        sources.forEach(source => {
            const media = source.getAttribute('media');
            const srcset = source.getAttribute('data-srcset') || source.getAttribute('srcset');
            if (srcset) {
                source.srcset = this.optimizeSrcset(srcset);
            }
        });

        // Update main image
        if (img && baseSrc) {
            this.loadImage(img);
        }

        picture.classList.remove('loading');
        picture.classList.add('loaded');
    }

    optimizeSrcset(srcset) {
        // Optimize each image in the srcset
        return srcset.split(',').map(src => {
            const [url, descriptor] = src.trim().split(' ');
            const optimizedUrl = this.supportedFormats.webp ? this.convertToWebP(url) : url;
            return `${optimizedUrl} ${descriptor || ''}`.trim();
        }).join(', ');
    }

    optimizeExistingImage(img) {
        // Add progressive enhancement to existing images
        if (!img.dataset.optimized) {
            const currentSrc = img.src;
            if (currentSrc && this.supportedFormats.webp) {
                const webpSrc = this.convertToWebP(currentSrc);

                // Test if WebP version exists
                const testImg = new Image();
                testImg.onload = () => {
                    img.src = webpSrc;
                    img.dataset.optimized = 'true';
                };
                testImg.onerror = () => {
                    img.dataset.optimized = 'true'; // Mark as processed
                };
                testImg.src = webpSrc;
            }
        }
    }

    setupPreloadHints() {
        // Add preload hints for critical images
        const criticalImages = document.querySelectorAll('img[data-critical], picture[data-critical]');
        criticalImages.forEach(img => {
            const src = img.dataset.src || img.src;
            if (src) {
                this.preloadImage(src);
            }
        });
    }

    preloadImage(src) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'image';
        link.href = this.supportedFormats.webp ? this.convertToWebP(src) : src;
        document.head.appendChild(link);
    }

    loadAllImages() {
        // Fallback for browsers without IntersectionObserver
        const lazyImages = document.querySelectorAll('img[data-src], picture[data-src]');
        lazyImages.forEach(img => this.loadImage(img));
    }

    // Public method to manually trigger image loading
    loadImageNow(selector) {
        const element = document.querySelector(selector);
        if (element) {
            this.loadImage(element);
        }
    }

    // Performance monitoring
    getPerformanceMetrics() {
        return {
            cacheSize: this.imageCache.size,
            supportedFormats: this.supportedFormats,
            observedImages: this.intersectionObserver ?
                this.intersectionObserver.takeRecords().length : 0
        };
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.OptimizedImageLoader = new OptimizedImageLoader();
    });
} else {
    window.OptimizedImageLoader = new OptimizedImageLoader();
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OptimizedImageLoader;
}
