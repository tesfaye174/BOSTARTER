/**
 * BOSTARTER - Enhanced Main JavaScript
 * Sistema principale ottimizzato con funzionalità avanzate
 * @version 3.0.0
 */
(function () {
    "use strict";

    // Configuration
    const CONFIG = {
        version: "3.0.0",
        framework: "Bootstrap 5.3.3",
        debug: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1',
        theme: {
            default: 'auto',
            storageKey: 'bostarter_theme'
        },
        performance: {
            lazyLoadOffset: 100,
            debounceDelay: 250,
            throttleDelay: 16
        }
    };

    // State management
    const state = {
        initialized: false,
        theme: localStorage.getItem(CONFIG.theme.storageKey) || CONFIG.theme.default,
        observers: new Map(),
        listeners: new Map(),
        cache: new Map()
    };

    // Utility functions
    const utils = {
        /**
         * Logging with debug check
         */
        log: (...args) => {
            if (CONFIG.debug) {
                console.log('[BOSTARTER Main]', ...args);
            }
        },

        /**
         * Error logging
         */
        error: (...args) => {
            console.error('[BOSTARTER Error]', ...args);
        },

        /**
         * Debounce function
         */
        debounce: (func, wait) => {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Throttle function
         */
        throttle: (func, limit) => {
            let inThrottle;
            return function () {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        /**
         * Check if element is in viewport
         */
        isInViewport: (element, offset = 0) => {
            const rect = element.getBoundingClientRect();
            return (
                rect.top >= -offset &&
                rect.left >= -offset &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) + offset &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth) + offset
            );
        },

        /**
         * Generate unique ID
         */
        generateId: () => {
            return 'bos_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
        }
    };

    // Main initialization
    const init = () => {
        if (state.initialized) return;

        try {
            utils.log('Initializing BOSTARTER Main v3.0.0');

            // Core setup
            setupTheme();
            setupMobileMenu();
            setupLazyLoading();
            setupScrollAnimations();
            setupBootstrapComponents();
            setupAccessibility();
            setupPerformanceOptimizations();
            setupErrorHandling();

            // Mark as initialized
            state.initialized = true;
            utils.log('BOSTARTER Main initialized successfully');

            // Dispatch ready event
            document.dispatchEvent(new CustomEvent('bostarterMainReady', {
                detail: { version: CONFIG.version, state }
            }));

        } catch (error) {
            utils.error('Failed to initialize BOSTARTER Main:', error);
        }
    };

    /**
     * Enhanced theme management
     */
    const setupTheme = () => {
        const savedTheme = localStorage.getItem(CONFIG.theme.storageKey) || CONFIG.theme.default;

        // Apply theme
        applyTheme(savedTheme);

        // Listen for theme changes
        document.addEventListener('themeChanged', (e) => {
            applyTheme(e.detail.theme);
        });

        // Listen for system theme changes
        if (savedTheme === 'auto') {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener((e) => {
                if (state.theme === 'auto') {
                    applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    };

    /**
     * Apply theme with smooth transition
     */
    const applyTheme = (theme) => {
        const html = document.documentElement;
        const body = document.body;

        // Add transition class
        html.classList.add('theme-transition');

        // Determine actual theme
        let actualTheme = theme;
        if (theme === 'auto') {
            actualTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        // Apply theme
        html.setAttribute('data-bs-theme', actualTheme);
        html.setAttribute('data-theme', theme);
        body.className = body.className.replace(/theme-\w+/g, '') + ` theme-${actualTheme}`;

        // Update state
        state.theme = theme;

        // Remove transition class
        setTimeout(() => {
            html.classList.remove('theme-transition');
        }, 300);

        utils.log(`Theme applied: ${theme} (actual: ${actualTheme})`);
    };

    /**
     * Enhanced mobile menu with better UX
     */
    const setupMobileMenu = () => {
        const navbar = document.querySelector('.navbar');
        const toggler = navbar?.querySelector('.navbar-toggler');
        const collapse = navbar?.querySelector('.navbar-collapse');

        if (!toggler || !collapse) return;

        let isAnimating = false;

        toggler.addEventListener('click', (e) => {
            e.preventDefault();

            if (isAnimating) return;
            isAnimating = true;

            const isExpanded = toggler.getAttribute('aria-expanded') === 'true';
            const newState = !isExpanded;

            // Update aria-expanded
            toggler.setAttribute('aria-expanded', newState);

            // Toggle classes with animation
            if (newState) {
                collapse.classList.add('collapsing');
                collapse.style.height = '0px';

                requestAnimationFrame(() => {
                    collapse.classList.remove('collapse');
                    collapse.classList.add('show');
                    collapse.style.height = collapse.scrollHeight + 'px';
                });

                // Trap focus
                setTimeout(() => {
                    trapFocus(collapse);
                }, 150);
            } else {
                collapse.style.height = collapse.scrollHeight + 'px';
                collapse.classList.add('collapsing');

                requestAnimationFrame(() => {
                    collapse.style.height = '0px';
                });
            }

            // Clean up animation
            setTimeout(() => {
                collapse.classList.remove('collapsing');
                if (newState) {
                    collapse.style.height = '';
                } else {
                    collapse.classList.remove('show');
                    collapse.classList.add('collapse');
                }
                isAnimating = false;
            }, 350);
        });

        // Close menu on outside click
        document.addEventListener('click', (e) => {
            if (!navbar.contains(e.target) && collapse.classList.contains('show')) {
                toggler.click();
            }
        });

        // Close menu on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && collapse.classList.contains('show')) {
                toggler.click();
                toggler.focus();
            }
        });
    };

    /**
     * Advanced lazy loading with intersection observer
     */
    const setupLazyLoading = () => {
        if (!('IntersectionObserver' in window)) {
            // Fallback for older browsers
            const images = document.querySelectorAll('img[loading="lazy"]');
            images.forEach(img => {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                }
                img.classList.add('loaded');
            });
            return;
        }

        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;

                    // Create a new image to preload
                    const newImg = new Image();
                    newImg.onload = () => {
                        img.src = img.dataset.src || img.src;
                        img.classList.add('loaded');
                        img.classList.add('animate-fadeIn');
                    };
                    newImg.onerror = () => {
                        img.classList.add('error');
                        utils.error('Failed to load image:', img.dataset.src || img.src);
                    };

                    newImg.src = img.dataset.src || img.src;
                    imageObserver.unobserve(img);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: `${CONFIG.performance.lazyLoadOffset}px`
        });

        // Observe all lazy images
        const lazyImages = document.querySelectorAll('img[loading="lazy"], img[data-src]');
        lazyImages.forEach(img => imageObserver.observe(img));

        state.observers.set('lazyLoading', imageObserver);
    };

    /**
     * Enhanced scroll animations
     */
    const setupScrollAnimations = () => {
        if (!('IntersectionObserver' in window)) return;

        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const delay = parseInt(element.dataset.delay) || 0;
                    const animationClass = element.dataset.animation || 'animate-fadeIn';

                    setTimeout(() => {
                        element.classList.add('animate', animationClass);

                        // Fire custom event
                        element.dispatchEvent(new CustomEvent('elementAnimated', {
                            detail: { element, animationClass }
                        }));
                    }, delay);

                    // Stop observing if animation should only happen once
                    if (!element.hasAttribute('data-repeat')) {
                        animationObserver.unobserve(element);
                    }
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });

        // Observe elements with scroll animations
        const animatedElements = document.querySelectorAll('.animate-on-scroll, [data-animation]');
        animatedElements.forEach(el => animationObserver.observe(el));

        state.observers.set('scrollAnimations', animationObserver);
    };

    /**
     * Enhanced Bootstrap components initialization
     */
    const setupBootstrapComponents = () => {
        // Tooltips with enhanced options
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover focus',
                delay: { show: 500, hide: 100 },
                boundary: 'viewport'
            });
        });

        // Popovers with enhanced options
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => {
            return new bootstrap.Popover(popoverTriggerEl, {
                trigger: 'click',
                boundary: 'viewport',
                sanitize: true
            });
        });

        // Modals with focus management
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', () => {
                const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                firstFocusable?.focus();
                trapFocus(modal);
            });

            modal.addEventListener('hidden.bs.modal', () => {
                const trigger = document.querySelector(`[data-bs-target="#${modal.id}"]`) ||
                    document.querySelector(`[href="#${modal.id}"]`);
                trigger?.focus();
            });
        });

        // Offcanvas with enhanced behavior
        const offcanvasElements = document.querySelectorAll('.offcanvas');
        offcanvasElements.forEach(offcanvas => {
            offcanvas.addEventListener('shown.bs.offcanvas', () => {
                trapFocus(offcanvas);
            });
        });

        // Store references
        state.cache.set('tooltips', tooltipList);
        state.cache.set('popovers', popoverList);
    };

    /**
     * Enhanced accessibility features
     */
    const setupAccessibility = () => {
        // Skip link functionality
        const skipLink = document.querySelector('.skip-link');
        if (skipLink) {
            skipLink.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(skipLink.getAttribute('href'));
                if (target) {
                    target.focus();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }

        // Keyboard navigation enhancement
        document.addEventListener('keydown', (e) => {
            // Tab navigation indicator
            if (e.key === 'Tab') {
                document.body.classList.add('user-is-tabbing');
            }

            // Arrow key navigation for custom components
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                handleArrowKeyNavigation(e);
            }
        });

        // Remove tab indicator on mouse use
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('user-is-tabbing');
        });

        // Announce dynamic content changes to screen readers
        setupAriaLiveRegions();
    };

    /**
     * Performance optimizations
     */
    const setupPerformanceOptimizations = () => {
        // Passive event listeners for better scroll performance
        const passiveEvents = ['scroll', 'touchstart', 'touchmove', 'wheel'];
        passiveEvents.forEach(event => {
            document.addEventListener(event, () => { }, { passive: true });
        });

        // Preload critical resources
        const criticalResources = document.querySelectorAll('[data-preload]');
        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource.dataset.preload;
            link.as = resource.dataset.preloadAs || 'fetch';
            if (resource.dataset.preloadType) {
                link.type = resource.dataset.preloadType;
            }
            document.head.appendChild(link);
        });

        // Resource hints for external resources
        const prefetchResources = document.querySelectorAll('[data-prefetch]');
        prefetchResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = resource.dataset.prefetch;
            document.head.appendChild(link);
        });
    };

    /**
     * Global error handling
     */
    const setupErrorHandling = () => {
        window.addEventListener('error', (event) => {
            utils.error('JavaScript Error:', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error
            });
        });

        window.addEventListener('unhandledrejection', (event) => {
            utils.error('Unhandled Promise Rejection:', event.reason);
        });
    };

    /**
     * Utility functions for components
     */

    /**
     * Focus trap for modals and other components
     */
    const trapFocus = (element) => {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        const trapFocusHandler = (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        lastElement?.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        firstElement?.focus();
                        e.preventDefault();
                    }
                }
            }
        };

        element.addEventListener('keydown', trapFocusHandler);

        // Return cleanup function
        return () => element.removeEventListener('keydown', trapFocusHandler);
    };

    /**
     * Handle arrow key navigation
     */
    const handleArrowKeyNavigation = (e) => {
        const activeElement = document.activeElement;

        // Custom dropdown navigation
        if (activeElement.matches('.dropdown-item')) {
            const dropdown = activeElement.closest('.dropdown-menu');
            const items = dropdown.querySelectorAll('.dropdown-item:not(.disabled)');
            const currentIndex = Array.from(items).indexOf(activeElement);

            let nextIndex;
            if (e.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % items.length;
                e.preventDefault();
            } else if (e.key === 'ArrowUp') {
                nextIndex = (currentIndex - 1 + items.length) % items.length;
                e.preventDefault();
            }

            if (nextIndex !== undefined) {
                items[nextIndex].focus();
            }
        }
    };

    /**
     * Setup ARIA live regions for dynamic announcements
     */
    const setupAriaLiveRegions = () => {
        // Create polite live region if it doesn't exist
        if (!document.getElementById('aria-live-polite')) {
            const liveRegion = document.createElement('div');
            liveRegion.id = 'aria-live-polite';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'visually-hidden';
            document.body.appendChild(liveRegion);
        }

        // Create assertive live region if it doesn't exist
        if (!document.getElementById('aria-live-assertive')) {
            const liveRegion = document.createElement('div');
            liveRegion.id = 'aria-live-assertive';
            liveRegion.setAttribute('aria-live', 'assertive');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'visually-hidden';
            document.body.appendChild(liveRegion);
        }
    };

    /**
     * Public API
     */
    const BOSTARTER_MAIN = {
        version: CONFIG.version,
        initialized: () => state.initialized,

        // Theme management
        setTheme: (theme) => {
            if (['light', 'dark', 'auto'].includes(theme)) {
                localStorage.setItem(CONFIG.theme.storageKey, theme);
                applyTheme(theme);
                return true;
            }
            return false;
        },

        getTheme: () => state.theme,

        // Announcements for screen readers
        announce: (message, priority = 'polite') => {
            const liveRegion = document.getElementById(`aria-live-${priority}`);
            if (liveRegion) {
                liveRegion.textContent = message;
                setTimeout(() => liveRegion.textContent = '', 1000);
            }
        },

        // Utility functions
        utils: {
            debounce: utils.debounce,
            throttle: utils.throttle,
            isInViewport: utils.isInViewport,
            generateId: utils.generateId
        },

        // State access
        getState: () => ({ ...state }),

        // Cleanup
        destroy: () => {
            state.observers.forEach(observer => observer.disconnect());
            state.observers.clear();
            state.listeners.forEach((listener, event) => {
                window.removeEventListener(event, listener);
            });
            state.listeners.clear();
            state.cache.clear();
            state.initialized = false;
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export to global scope
    window.BOSTARTER = {
        ...(window.BOSTARTER || {}),
        Main: BOSTARTER_MAIN
    };

})();

