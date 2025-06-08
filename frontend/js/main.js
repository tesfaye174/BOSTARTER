/**
 * BOSTARTER Main JavaScript
 * Core functionality loader and initializer
 */

// Core application state
window.BOSTARTER = {
    version: '1.0.0',
    initialized: false,
    modules: new Map(),
    config: {
        apiBaseUrl: '/backend/api/',
        debug: false,
        performance: true
    }
};

// Main initialization function
function initializeBOSTARTER() {
    if (window.BOSTARTER.initialized) {
        return Promise.resolve();
    }

    console.log('🚀 Initializing BOSTARTER v' + window.BOSTARTER.version);

    return Promise.all([
        loadCoreModules(),
        initializeServiceWorker(),
        setupGlobalErrorHandling(),
        initializeAccessibility(),
        setupPerformanceMonitoring()
    ]).then(() => {
        window.BOSTARTER.initialized = true;
        console.log('✅ BOSTARTER initialized successfully');

        // Dispatch ready event
        document.dispatchEvent(new CustomEvent('bostarter:ready', {
            detail: { version: window.BOSTARTER.version }
        }));
    }).catch(error => {
        console.error('❌ BOSTARTER initialization failed:', error);
        // Initialize fallback mode
        initializeFallbackMode();
    });
}

// Load core modules
async function loadCoreModules() {
    const coreModules = [
        { name: 'Utils', path: '/frontend/js/core/Utils.js' },
        { name: 'ErrorHandler', path: '/frontend/js/error-handler.js' },
        { name: 'NotificationSystem', path: '/frontend/js/core/NotificationSystem.js' },
        { name: 'CommonFunctions', path: '/frontend/js/utils/common-functions.js' }
    ];

    for (const module of coreModules) {
        try {
            await loadModule(module.name, module.path);
        } catch (error) {
            console.warn(`Failed to load ${module.name}:`, error);
        }
    }
}

// Dynamic module loader
function loadModule(name, path) {
    return new Promise((resolve, reject) => {
        if (window.BOSTARTER.modules.has(name)) {
            resolve(window.BOSTARTER.modules.get(name));
            return;
        }

        const script = document.createElement('script');
        script.src = path;
        script.async = true;

        script.onload = () => {
            console.log(`✅ Loaded module: ${name}`);
            window.BOSTARTER.modules.set(name, true);
            resolve(true);
        };

        script.onerror = () => {
            console.warn(`⚠️ Failed to load module: ${name}`);
            reject(new Error(`Failed to load ${name}`));
        };

        document.head.appendChild(script);
    });
}

// Initialize Service Worker
async function initializeServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        console.warn('Service Worker not supported');
        return;
    }

    try {
        // Load service worker registration manager
        await loadModule('ServiceWorkerManager', '/frontend/js/sw-register.js');

        // Initialize if available
        if (window.ServiceWorkerManager) {
            new window.ServiceWorkerManager();
        }
    } catch (error) {
        console.warn('Service Worker initialization failed:', error);
    }
}

// Setup global error handling
function setupGlobalErrorHandling() {
    // Global error handler
    window.addEventListener('error', (event) => {
        if (window.ErrorHandler) {
            window.ErrorHandler.handleGlobalError(event.error, event);
        } else {
            console.error('Global Error:', event.error);
        }
    });

    // Unhandled promise rejection handler
    window.addEventListener('unhandledrejection', (event) => {
        if (window.ErrorHandler) {
            window.ErrorHandler.handlePromiseRejection(event.reason, event);
        } else {
            console.error('Unhandled Promise Rejection:', event.reason);
        }
    });

    // Network error handler
    window.addEventListener('offline', () => {
        if (window.NotificationSystem) {
            window.NotificationSystem.show({
                type: 'warning',
                title: 'Connessione Persa',
                message: 'Stai navigando offline. Alcune funzionalità potrebbero non essere disponibili.',
                persistent: true
            });
        }
    });

    window.addEventListener('online', () => {
        if (window.NotificationSystem) {
            window.NotificationSystem.show({
                type: 'success',
                title: 'Connessione Ripristinata',
                message: 'Sei di nuovo online!',
                duration: 3000
            });
        }
    });
}

// Initialize accessibility features
function initializeAccessibility() {
    // Load accessibility manager if available
    if (window.AccessibilityManager) {
        new window.AccessibilityManager();
    }

    // Basic keyboard navigation
    document.addEventListener('keydown', (event) => {
        // Skip links activation
        if (event.key === 'Tab' && !event.shiftKey) {
            const skipLink = document.querySelector('.skip-link:focus');
            if (skipLink) {
                skipLink.style.position = 'static';
            }
        }

        // ESC key handling
        if (event.key === 'Escape') {
            // Close modals, dropdowns, etc.
            const activeModal = document.querySelector('.modal.show, .dropdown.show');
            if (activeModal) {
                activeModal.classList.remove('show');
            }
        }
    });

    // Focus management
    document.addEventListener('focusin', (event) => {
        // Add focus indicators for keyboard navigation
        if (event.target.matches('a, button, input, select, textarea, [tabindex]')) {
            event.target.classList.add('keyboard-focus');
        }
    });

    document.addEventListener('focusout', (event) => {
        event.target.classList.remove('keyboard-focus');
    });
}

// Performance monitoring
function setupPerformanceMonitoring() {
    if (!window.BOSTARTER.config.performance) return;

    // Monitor Core Web Vitals
    if ('web-vital' in window) {
        // This would be loaded from a separate performance module
        return;
    }

    // Basic performance logging
    window.addEventListener('load', () => {
        setTimeout(() => {
            const perfData = performance.getEntriesByType('navigation')[0];
            if (perfData) {
                console.log('📊 Page Load Performance:', {
                    domContentLoaded: Math.round(perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart),
                    load: Math.round(perfData.loadEventEnd - perfData.loadEventStart),
                    firstByte: Math.round(perfData.responseStart - perfData.requestStart)
                });
            }
        }, 0);
    });
}

// Fallback mode for when core modules fail
function initializeFallbackMode() {
    console.log('🔄 Initializing fallback mode');

    // Basic error handling
    window.addEventListener('error', (event) => {
        console.error('Error (fallback):', event.error);
    });

    // Basic notification system
    window.showNotification = function (message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 16px;
            background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#17a2b8'};
            color: white;
            border-radius: 4px;
            z-index: 10000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    };

    // Mark as initialized in fallback mode
    window.BOSTARTER.initialized = true;
    window.BOSTARTER.fallbackMode = true;
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeBOSTARTER);
} else {
    initializeBOSTARTER();
}

// Export for manual initialization
window.initializeBOSTARTER = initializeBOSTARTER;

// Utility functions for modules
window.BOSTARTER.utils = {
    // Safe module access
    getModule: (name) => window.BOSTARTER.modules.get(name),

    // Check if module is loaded
    hasModule: (name) => window.BOSTARTER.modules.has(name),

    // Wait for module
    waitForModule: (name, timeout = 5000) => {
        return new Promise((resolve, reject) => {
            if (window.BOSTARTER.modules.has(name)) {
                resolve(true);
                return;
            }

            const checkInterval = setInterval(() => {
                if (window.BOSTARTER.modules.has(name)) {
                    clearInterval(checkInterval);
                    clearTimeout(timeoutId);
                    resolve(true);
                }
            }, 100);

            const timeoutId = setTimeout(() => {
                clearInterval(checkInterval);
                reject(new Error(`Module ${name} failed to load within ${timeout}ms`));
            }, timeout);
        });
    },

    // API helper
    api: {
        baseUrl: '/backend/api/',

        async request(endpoint, options = {}) {
            const url = this.baseUrl + endpoint.replace(/^\//, '');
            const config = {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            };

            try {
                const response = await fetch(url, config);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                return data;
            } catch (error) {
                if (window.ErrorHandler) {
                    window.ErrorHandler.handleApiError(error, endpoint);
                }
                throw error;
            }
        },

        get(endpoint, params = {}) {
            const searchParams = new URLSearchParams(params);
            const url = endpoint + (searchParams.toString() ? '?' + searchParams.toString() : '');
            return this.request(url, { method: 'GET' });
        },

        post(endpoint, data = {}) {
            return this.request(endpoint, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }
    }
};

console.log('📄 BOSTARTER main.js loaded');
