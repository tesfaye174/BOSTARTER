/**
 * BOSTARTER - Enhanced Utilities
 * Utilità avanzate per performance e funzionalità moderne
 * @version 3.0.0
 */
(function (window, document) {
    'use strict';

    // Main namespace
    window.BOSTARTEREnhanced = window.BOSTARTEREnhanced || {};

    /**
     * Performance Monitor
     * Monitora le performance dell'applicazione
     */
    const PerformanceMonitor = {
        metrics: new Map(),

        /**
         * Avvia il monitoraggio di una metrica
         */
        start(name) {
            this.metrics.set(name, performance.now());
        },

        /**
         * Termina il monitoraggio e restituisce il tempo
         */
        end(name) {
            const startTime = this.metrics.get(name);
            if (startTime) {
                const duration = performance.now() - startTime;
                this.metrics.delete(name);
                return duration;
            }
            return null;
        },

        /**
         * Misura le performance di una funzione
         */
        measure(name, fn) {
            this.start(name);
            const result = fn();
            const duration = this.end(name);
            console.log(`[Performance] ${name}: ${duration.toFixed(2)}ms`);
            return result;
        },

        /**
         * Monitora i Web Vitals
         */
        monitorWebVitals() {
            if ('web-vitals' in window) {
                import('https://unpkg.com/web-vitals@3/dist/web-vitals.js').then(({ getCLS, getFID, getFCP, getLCP, getTTFB }) => {
                    getCLS(console.log);
                    getFID(console.log);
                    getFCP(console.log);
                    getLCP(console.log);
                    getTTFB(console.log);
                });
            }
        }
    };

    /**
     * Advanced DOM Utilities
     * Utilità avanzate per la manipolazione del DOM
     */
    const DOMUtils = {
        /**
         * Debounced resize observer
         */
        createResizeObserver(callback, debounceMs = 100) {
            let timeoutId;
            return new ResizeObserver(entries => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => callback(entries), debounceMs);
            });
        },

        /**
         * Mutation observer with filters
         */
        createMutationObserver(callback, options = {}) {
            const defaultOptions = {
                childList: true,
                subtree: true,
                attributes: false,
                attributeOldValue: false,
                characterData: false,
                characterDataOldValue: false
            };

            return new MutationObserver(callback, { ...defaultOptions, ...options });
        },

        /**
         * Virtual scrolling implementation
         */
        createVirtualScroller(container, items, itemHeight, renderItem) {
            const totalHeight = items.length * itemHeight;
            const containerHeight = container.clientHeight;
            const visibleCount = Math.ceil(containerHeight / itemHeight) + 2;

            let scrollTop = 0;
            let startIndex = 0;

            const viewport = document.createElement('div');
            viewport.style.cssText = `
                height: ${totalHeight}px;
                position: relative;
            `;

            const content = document.createElement('div');
            content.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
            `;

            viewport.appendChild(content);
            container.appendChild(viewport);

            const update = () => {
                startIndex = Math.floor(scrollTop / itemHeight);
                const endIndex = Math.min(startIndex + visibleCount, items.length);

                content.style.transform = `translateY(${startIndex * itemHeight}px)`;
                content.innerHTML = '';

                for (let i = startIndex; i < endIndex; i++) {
                    content.appendChild(renderItem(items[i], i));
                }
            };

            container.addEventListener('scroll', () => {
                scrollTop = container.scrollTop;
                requestAnimationFrame(update);
            });

            update();
            return { update, destroy: () => container.removeChild(viewport) };
        },

        /**
         * Smooth element reveal
         */
        revealElement(element, options = {}) {
            const {
                duration = 600,
                delay = 0,
                easing = 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                direction = 'up'
            } = options;

            const transforms = {
                up: 'translateY(30px)',
                down: 'translateY(-30px)',
                left: 'translateX(30px)',
                right: 'translateX(-30px)',
                scale: 'scale(0.8)'
            };

            element.style.cssText = `
                opacity: 0;
                transform: ${transforms[direction] || transforms.up};
                transition: opacity ${duration}ms ${easing} ${delay}ms, transform ${duration}ms ${easing} ${delay}ms;
            `;

            // Force reflow
            element.offsetHeight;

            element.style.opacity = '1';
            element.style.transform = 'none';

            return new Promise(resolve => {
                setTimeout(resolve, duration + delay);
            });
        }
    };

    /**
     * Network Utilities
     * Utilità per la gestione della rete e delle richieste
     */
    const NetworkUtils = {
        /**
         * Fetch con retry automatico
         */
        async fetchWithRetry(url, options = {}, maxRetries = 3) {
            const { retryDelay = 1000, ...fetchOptions } = options;

            for (let i = 0; i <= maxRetries; i++) {
                try {
                    const response = await fetch(url, fetchOptions);
                    if (response.ok) {
                        return response;
                    }
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                } catch (error) {
                    if (i === maxRetries) {
                        throw error;
                    }
                    await this.delay(retryDelay * Math.pow(2, i)); // Exponential backoff
                }
            }
        },

        /**
         * Preload delle risorse
         */
        preloadResources(resources) {
            return Promise.all(resources.map(resource => {
                return new Promise((resolve, reject) => {
                    const link = document.createElement('link');
                    link.rel = 'preload';
                    link.href = resource.url;
                    link.as = resource.as || 'fetch';
                    if (resource.type) link.type = resource.type;

                    link.onload = resolve;
                    link.onerror = reject;

                    document.head.appendChild(link);
                });
            }));
        },

        /**
         * Network status monitor
         */
        createNetworkMonitor() {
            const callbacks = new Set();

            const updateStatus = () => {
                const status = {
                    online: navigator.onLine,
                    connection: navigator.connection || navigator.mozConnection || navigator.webkitConnection,
                    timestamp: Date.now()
                };

                callbacks.forEach(callback => callback(status));
            };

            window.addEventListener('online', updateStatus);
            window.addEventListener('offline', updateStatus);

            if (navigator.connection) {
                navigator.connection.addEventListener('change', updateStatus);
            }

            return {
                subscribe: (callback) => callbacks.add(callback),
                unsubscribe: (callback) => callbacks.delete(callback),
                getStatus: () => ({
                    online: navigator.onLine,
                    connection: navigator.connection || navigator.mozConnection || navigator.webkitConnection
                })
            };
        },

        /**
         * Simple delay utility
         */
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    };

    /**
     * Storage Utilities
     * Utilità avanzate per lo storage
     */
    const StorageUtils = {
        /**
         * Storage con TTL e compressione
         */
        setItem(key, value, ttl = null, compress = false) {
            const item = {
                value: compress ? this.compress(JSON.stringify(value)) : value,
                timestamp: Date.now(),
                ttl: ttl,
                compressed: compress
            };

            try {
                localStorage.setItem(key, JSON.stringify(item));
                return true;
            } catch (error) {
                console.warn('Storage quota exceeded, clearing old items');
                this.clearExpired();
                try {
                    localStorage.setItem(key, JSON.stringify(item));
                    return true;
                } catch (error) {
                    console.error('Failed to store item:', error);
                    return false;
                }
            }
        },

        /**
         * Recupera item con controllo TTL
         */
        getItem(key) {
            try {
                const stored = localStorage.getItem(key);
                if (!stored) return null;

                const item = JSON.parse(stored);

                // Check TTL
                if (item.ttl && (Date.now() - item.timestamp) > item.ttl) {
                    localStorage.removeItem(key);
                    return null;
                }

                return item.compressed ?
                    JSON.parse(this.decompress(item.value)) :
                    item.value;
            } catch (error) {
                console.error('Failed to retrieve item:', error);
                return null;
            }
        },

        /**
         * Pulisce gli item scaduti
         */
        clearExpired() {
            const keys = Object.keys(localStorage);
            keys.forEach(key => {
                try {
                    const item = JSON.parse(localStorage.getItem(key));
                    if (item.ttl && (Date.now() - item.timestamp) > item.ttl) {
                        localStorage.removeItem(key);
                    }
                } catch (error) {
                    // Invalid JSON, skip
                }
            });
        },

        /**
         * Simple compression
         */
        compress(str) {
            return btoa(encodeURIComponent(str));
        },

        /**
         * Simple decompression
         */
        decompress(str) {
            return decodeURIComponent(atob(str));
        },

        /**
         * Storage usage statistics
         */
        getUsageStats() {
            let totalSize = 0;
            const itemSizes = {};

            for (let key in localStorage) {
                if (localStorage.hasOwnProperty(key)) {
                    const size = localStorage[key].length;
                    itemSizes[key] = size;
                    totalSize += size;
                }
            }

            return {
                totalSize,
                itemSizes,
                remainingQuota: (5 * 1024 * 1024) - totalSize // Assuming 5MB quota
            };
        }
    };

    /**
     * Form Utilities
     * Utilità avanzate per i form
     */
    const FormUtils = {
        /**
         * Validazione in tempo reale
         */
        createRealTimeValidator(form) {
            const validators = new Map();

            const validate = (field) => {
                const validator = validators.get(field.name);
                if (validator) {
                    const result = validator(field.value, field);
                    this.updateFieldState(field, result);
                    return result.valid;
                }
                return true;
            };

            form.addEventListener('input', (e) => {
                if (e.target.matches('input, select, textarea')) {
                    validate(e.target);
                }
            });

            return {
                addValidator: (fieldName, validatorFn) => validators.set(fieldName, validatorFn),
                removeValidator: (fieldName) => validators.delete(fieldName),
                validateAll: () => {
                    const fields = form.querySelectorAll('input, select, textarea');
                    return Array.from(fields).every(field => validate(field));
                }
            };
        },

        /**
         * Aggiorna lo stato visuale del campo
         */
        updateFieldState(field, result) {
            const feedbackElement = field.parentElement.querySelector('.feedback');

            field.classList.toggle('is-valid', result.valid);
            field.classList.toggle('is-invalid', !result.valid);

            if (feedbackElement) {
                feedbackElement.textContent = result.message || '';
                feedbackElement.className = `feedback ${result.valid ? 'valid-feedback' : 'invalid-feedback'}`;
            }
        },

        /**
         * Serializza form in oggetto
         */
        serializeForm(form) {
            const formData = new FormData(form);
            const data = {};

            for (let [key, value] of formData.entries()) {
                if (data[key]) {
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            }

            return data;
        }
    };

    /**
     * Animation Utilities
     * Utilità per animazioni avanzate
     */
    const AnimationUtils = {
        /**
         * Tween engine semplice
         */
        tween(from, to, duration, easing = 'easeOutQuad', onUpdate, onComplete) {
            const startTime = performance.now();
            const delta = to - from;

            const easingFunctions = {
                linear: t => t,
                easeInQuad: t => t * t,
                easeOutQuad: t => t * (2 - t),
                easeInOutQuad: t => t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t,
                easeInCubic: t => t * t * t,
                easeOutCubic: t => (--t) * t * t + 1,
                easeInOutCubic: t => t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1
            };

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easedProgress = easingFunctions[easing](progress);
                const currentValue = from + delta * easedProgress;

                onUpdate(currentValue, progress);

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else if (onComplete) {
                    onComplete();
                }
            };

            requestAnimationFrame(animate);
        },

        /**
         * Parallax semplice
         */
        createParallax(element, speed = 0.5) {
            let ticking = false;

            const updateParallax = () => {
                const scrolled = window.pageYOffset;
                const rect = element.getBoundingClientRect();
                const elementTop = rect.top + scrolled;
                const rate = scrolled - elementTop;
                const yPos = -(rate * speed);

                element.style.transform = `translateY(${yPos}px)`;
                ticking = false;
            };

            const handleScroll = () => {
                if (!ticking) {
                    requestAnimationFrame(updateParallax);
                    ticking = true;
                }
            };

            window.addEventListener('scroll', handleScroll, { passive: true });

            return () => window.removeEventListener('scroll', handleScroll);
        },

        /**
         * Smooth counter animation
         */
        animateCounter(element, target, duration = 2000, startValue = 0) {
            this.tween(
                startValue,
                target,
                duration,
                'easeOutQuad',
                (value) => {
                    element.textContent = Math.floor(value);
                }
            );
        }
    };

    // Export all utilities
    Object.assign(window.BOSTARTEREnhanced, {
        PerformanceMonitor,
        DOMUtils,
        NetworkUtils,
        StorageUtils,
        FormUtils,
        AnimationUtils
    });

    // Auto-initialize performance monitoring in development
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        PerformanceMonitor.monitorWebVitals();
    }

})(window, document);
