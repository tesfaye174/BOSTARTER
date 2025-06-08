/**
 * BOSTARTER Resource Optimizer
 * Handles CSS/JS bundling, minification, and caching strategies
 */

class ResourceOptimizer {
    constructor() {
        this.cache = new Map();
        this.loadedResources = new Set();
        this.criticalResources = new Set();
        this.deferredResources = [];
        this.init();
    }

    init() {
        this.setupResourceHints();
        this.optimizeExistingResources();
        this.setupLazyLoading();
        this.monitorPerformance();
    }

    setupResourceHints() {
        // Add DNS prefetch for external domains
        const externalDomains = [
            'fonts.googleapis.com',
            'fonts.gstatic.com',
            'cdnjs.cloudflare.com'
        ];

        externalDomains.forEach(domain => {
            if (!document.querySelector(`link[rel="dns-prefetch"][href*="${domain}"]`)) {
                const link = document.createElement('link');
                link.rel = 'dns-prefetch';
                link.href = `//${domain}`;
                document.head.appendChild(link);
            }
        });

        // Add preconnect for critical external resources
        this.addPreconnect('https://fonts.googleapis.com');
        this.addPreconnect('https://fonts.gstatic.com');
    }

    addPreconnect(url) {
        if (!document.querySelector(`link[rel="preconnect"][href="${url}"]`)) {
            const link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = url;
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        }
    }

    optimizeExistingResources() {
        // Optimize CSS loading
        this.optimizeCSSLoading();

        // Optimize JavaScript loading
        this.optimizeJSLoading();

        // Optimize font loading
        this.optimizeFontLoading();
    }

    optimizeCSSLoading() {
        const cssLinks = document.querySelectorAll('link[rel="stylesheet"]');

        cssLinks.forEach((link, index) => {
            // Mark first CSS file as critical
            if (index === 0) {
                this.criticalResources.add(link.href);
                return;
            }

            // Defer non-critical CSS
            if (!link.hasAttribute('data-critical')) {
                this.deferCSS(link);
            }
        });
    }

    deferCSS(link) {
        // Use media hack to defer CSS loading
        const href = link.href;
        link.media = 'print';
        link.onload = function () {
            this.media = 'all';
            this.onload = null;
        };

        // Fallback for browsers that don't support onload on link
        setTimeout(() => {
            if (link.media === 'print') {
                link.media = 'all';
            }
        }, 3000);
    }

    optimizeJSLoading() {
        const scripts = document.querySelectorAll('script[src]');

        scripts.forEach(script => {
            // Add async/defer attributes if not present
            if (!script.hasAttribute('async') && !script.hasAttribute('defer')) {
                // Defer non-critical scripts
                if (!script.hasAttribute('data-critical')) {
                    script.defer = true;
                }
            }
        });
    }

    optimizeFontLoading() {
        // Add font-display: swap to all font faces
        const style = document.createElement('style');
        style.textContent = `
            @font-face {
                font-display: swap;
            }
        `;
        document.head.appendChild(style);

        // Preload critical fonts
        this.preloadFont('/path/to/critical-font.woff2', 'font/woff2');
    }

    preloadFont(href, type) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'font';
        link.type = type;
        link.href = href;
        link.crossOrigin = 'anonymous';
        document.head.appendChild(link);
    }

    setupLazyLoading() {
        // Lazy load non-critical JavaScript modules
        this.lazyLoadModule('/js/non-critical-module.js', () => {
            return document.querySelector('.component-that-needs-module');
        });
    }

    async lazyLoadModule(src, condition) {
        if (this.loadedResources.has(src)) {
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            // Check condition periodically
            const checkCondition = () => {
                if (condition && !condition()) {
                    setTimeout(checkCondition, 100);
                    return;
                }

                const script = document.createElement('script');
                script.src = src;
                script.async = true;

                script.onload = () => {
                    this.loadedResources.add(src);
                    resolve();
                };

                script.onerror = () => {
                    reject(new Error(`Failed to load ${src}`));
                };

                document.head.appendChild(script);
            };

            checkCondition();
        });
    }

    // Bundle and minify CSS
    async bundleCSS(files) {
        const bundledCSS = [];

        for (const file of files) {
            try {
                const response = await fetch(file);
                const css = await response.text();
                bundledCSS.push(this.minifyCSS(css));
            } catch (error) {
                console.warn(`Failed to load CSS file: ${file}`, error);
            }
        }

        return bundledCSS.join('\n');
    }

    minifyCSS(css) {
        return css
            // Remove comments
            .replace(/\/\*[\s\S]*?\*\//g, '')
            // Remove unnecessary whitespace
            .replace(/\s+/g, ' ')
            // Remove space around selectors and properties
            .replace(/\s*{\s*/g, '{')
            .replace(/;\s*/g, ';')
            .replace(/\s*}\s*/g, '}')
            // Remove trailing semicolons
            .replace(/;}/g, '}')
            .trim();
    }

    // Bundle and minify JavaScript
    async bundleJS(files) {
        const bundledJS = [];

        for (const file of files) {
            try {
                const response = await fetch(file);
                const js = await response.text();
                bundledJS.push(this.minifyJS(js));
            } catch (error) {
                console.warn(`Failed to load JS file: ${file}`, error);
            }
        }

        return bundledJS.join('\n');
    }

    minifyJS(js) {
        return js
            // Remove single-line comments (preserve URLs)
            .replace(/\/\/.*$/gm, '')
            // Remove multi-line comments
            .replace(/\/\*[\s\S]*?\*\//g, '')
            // Remove unnecessary whitespace
            .replace(/\s+/g, ' ')
            // Remove space around operators
            .replace(/\s*([{}();,:])\s*/g, '$1')
            .trim();
    }

    // Critical resource path optimization
    async optimizeCriticalPath() {
        // Inline critical CSS
        const criticalCSS = await this.extractCriticalCSS();
        this.inlineCSS(criticalCSS);

        // Defer non-critical resources
        this.deferNonCriticalResources();
    }

    async extractCriticalCSS() {
        // Extract CSS rules that apply to above-the-fold content
        const criticalRules = [];
        const stylesheets = Array.from(document.styleSheets);

        for (const stylesheet of stylesheets) {
            try {
                const rules = Array.from(stylesheet.cssRules || stylesheet.rules || []);

                for (const rule of rules) {
                    if (rule.type === CSSRule.STYLE_RULE) {
                        // Check if selector matches above-the-fold elements
                        if (this.isCriticalSelector(rule.selectorText)) {
                            criticalRules.push(rule.cssText);
                        }
                    }
                }
            } catch (error) {
                // Handle CORS or other access issues
                console.warn('Cannot access stylesheet rules:', error);
            }
        }

        return criticalRules.join('\n');
    }

    isCriticalSelector(selector) {
        // Define patterns that are likely to be above-the-fold
        const criticalPatterns = [
            /^body/,
            /^html/,
            /^\.header/,
            /^\.hero/,
            /^\.navigation/,
            /^\.logo/,
            /^h[1-6]/,
            /^\.btn/,
            /^\.container/
        ];

        return criticalPatterns.some(pattern => pattern.test(selector));
    }

    inlineCSS(css) {
        if (!css) return;

        const style = document.createElement('style');
        style.textContent = css;
        style.setAttribute('data-inline', 'critical');
        document.head.appendChild(style);
    }

    deferNonCriticalResources() {
        // Defer non-critical stylesheets
        const nonCriticalCSS = document.querySelectorAll('link[rel="stylesheet"]:not([data-critical])');
        nonCriticalCSS.forEach(link => this.deferCSS(link));

        // Defer non-critical scripts
        const nonCriticalJS = document.querySelectorAll('script[src]:not([data-critical])');
        nonCriticalJS.forEach(script => {
            if (!script.defer && !script.async) {
                script.defer = true;
            }
        });
    }

    // Resource compression
    enableCompression() {
        // Request compressed resources when available
        const supportsGzip = /gzip/.test(navigator.userAgent);
        const supportsBrotli = /br/.test(navigator.userAgent);

        if (supportsBrotli || supportsGzip) {
            // Add headers for compressed resource requests
            const originalFetch = window.fetch;
            window.fetch = function (url, options = {}) {
                options.headers = options.headers || {};
                if (supportsBrotli) {
                    options.headers['Accept-Encoding'] = 'br, gzip, deflate';
                } else if (supportsGzip) {
                    options.headers['Accept-Encoding'] = 'gzip, deflate';
                }
                return originalFetch(url, options);
            };
        }
    }

    // Performance monitoring
    monitorPerformance() {
        // Track resource loading times
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'resource') {
                        this.analyzeResourcePerformance(entry);
                    }
                });
            });
            observer.observe({ entryTypes: ['resource'] });
        }
    }

    analyzeResourcePerformance(entry) {
        const loadTime = entry.responseEnd - entry.startTime;
        const resourceType = this.getResourceType(entry.name);

        // Log slow resources
        if (loadTime > 1000) {
            console.warn(`Slow ${resourceType} resource:`, {
                url: entry.name,
                loadTime: Math.round(loadTime),
                size: entry.transferSize
            });
        }

        // Store metrics for reporting
        if (!this.cache.has('resourceMetrics')) {
            this.cache.set('resourceMetrics', []);
        }

        this.cache.get('resourceMetrics').push({
            url: entry.name,
            type: resourceType,
            loadTime: Math.round(loadTime),
            size: entry.transferSize,
            timestamp: Date.now()
        });
    }

    getResourceType(url) {
        if (url.match(/\.(css)$/)) return 'CSS';
        if (url.match(/\.(js)$/)) return 'JavaScript';
        if (url.match(/\.(jpg|jpeg|png|gif|svg|webp)$/)) return 'Image';
        if (url.match(/\.(woff|woff2|ttf|eot)$/)) return 'Font';
        return 'Other';
    }

    // Get performance report
    getPerformanceReport() {
        return {
            loadedResources: Array.from(this.loadedResources),
            criticalResources: Array.from(this.criticalResources),
            resourceMetrics: this.cache.get('resourceMetrics') || [],
            cacheSize: this.cache.size
        };
    }

    // Clear cache
    clearCache() {
        this.cache.clear();
        this.loadedResources.clear();
    }
}

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ResourceOptimizer = new ResourceOptimizer();
    });
} else {
    window.ResourceOptimizer = new ResourceOptimizer();
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ResourceOptimizer;
}
