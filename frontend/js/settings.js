/**
 * BOSTARTER - Configuration File
 * Configurazione centralizzata per il progetto
 * @version 3.0.0
 */
(function (window) {
    'use strict';

    // Main configuration object
    const BOSTARTERConfig = {
        // Version information
        version: '3.0.0',
        framework: 'Bootstrap 5.3.3',
        buildDate: new Date().toISOString(),

        // Environment settings
        environment: {
            isDevelopment: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1',
            isProduction: window.location.protocol === 'https:' && !window.location.hostname.includes('localhost'),
            debug: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
        },

        // API configuration
        api: {
            baseUrl: window.location.origin,
            endpoints: {
                auth: '/backend/api/auth_compliant.php',
                login: '/backend/api/login.php',
                register: '/backend/api/signup.php',
                projects: '/backend/api/projects_compliant.php',
                users: '/backend/api/user.php',
                notifications: '/backend/api/notify.php',
                search: '/backend/api/find.php',
                stats: '/backend/api/stats_compliant.php',
                apply: '/backend/api/apply_view.php'
            },
            timeout: 30000,
            retries: 3,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        },

        // UI settings
        ui: {
            theme: {
                default: 'auto',
                storageKey: 'bostarter_theme',
                options: ['light', 'dark', 'auto']
            },
            animations: {
                enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                duration: {
                    fast: 200,
                    normal: 300,
                    slow: 500
                },
                easing: {
                    ease: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                    easeInOut: 'cubic-bezier(0.4, 0, 0.2, 1)',
                    bounce: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)'
                }
            },
            notifications: {
                position: 'top-right',
                duration: 5000,
                maxVisible: 3
            },
            modals: {
                backdrop: true,
                keyboard: true,
                focus: true
            }
        },

        // Performance settings
        performance: {
            lazyLoading: {
                enabled: true,
                rootMargin: '100px',
                threshold: 0.1
            },
            imageOptimization: {
                enabled: true,
                formats: ['webp', 'avif', 'jpg', 'png'],
                quality: 85
            },
            caching: {
                enabled: true,
                defaultTTL: 300000, // 5 minutes
                maxSize: 50 // Max items in cache
            },
            debounce: {
                scroll: 16,
                resize: 250,
                input: 300
            }
        },

        // Security settings
        security: {
            csrf: {
                enabled: true,
                tokenName: 'csrf_token'
            },
            xss: {
                enabled: true,
                sanitize: true
            },
            headers: {
                'X-Content-Type-Options': 'nosniff',
                'X-Frame-Options': 'DENY',
                'X-XSS-Protection': '1; mode=block'
            }
        },

        // Accessibility settings
        accessibility: {
            enabled: true,
            announcements: {
                enabled: true,
                polite: true,
                assertive: false
            },
            focusManagement: {
                enabled: true,
                trapFocus: true,
                restoreFocus: true
            },
            keyboardNavigation: {
                enabled: true,
                skipLinks: true,
                arrowKeys: true
            }
        },

        // Form settings
        forms: {
            validation: {
                realTime: true,
                showErrors: true,
                highlightFields: true
            },
            submission: {
                preventDefault: true,
                showLoading: true,
                disableOnSubmit: true
            }
        },

        // Media queries breakpoints (Bootstrap compatible)
        breakpoints: {
            xs: 0,
            sm: 576,
            md: 768,
            lg: 992,
            xl: 1200,
            xxl: 1400
        },

        // Color palette
        colors: {
            primary: '#6366f1',
            secondary: '#ec4899',
            success: '#22c55e',
            warning: '#eab308',
            danger: '#ef4444',
            info: '#06b6d4',
            light: '#f8fafc',
            dark: '#1e293b'
        },

        // Features flags
        features: {
            darkMode: true,
            offlineSupport: true,
            pushNotifications: false,
            analytics: false,
            serviceWorker: true,
            webShare: true,
            clipboard: true
        },

        // Storage configuration
        storage: {
            prefix: 'bostarter_',
            compression: false,
            encryption: false,
            quota: 5 * 1024 * 1024 // 5MB
        },

        // Logging configuration
        logging: {
            enabled: true,
            level: 'info', // debug, info, warn, error
            console: true,
            remote: false,
            maxEntries: 100
        },

        // PWA settings
        pwa: {
            enabled: true,
            name: 'BOSTARTER',
            shortName: 'BOSTARTER',
            description: 'Advanced Bootstrap Starter Template',
            themeColor: '#6366f1',
            backgroundColor: '#ffffff',
            display: 'standalone',
            orientation: 'portrait-primary'
        }
    };

    // Configuration validation
    const validateConfig = (config) => {
        const errors = [];

        // Check required fields
        if (!config.version) errors.push('Version is required');
        if (!config.api?.baseUrl) errors.push('API base URL is required');

        // Check breakpoints order
        const breakpoints = Object.values(config.breakpoints);
        for (let i = 1; i < breakpoints.length; i++) {
            if (breakpoints[i] <= breakpoints[i - 1]) {
                errors.push('Breakpoints must be in ascending order');
                break;
            }
        }

        // Check cache TTL
        if (config.performance?.caching?.defaultTTL < 1000) {
            errors.push('Cache TTL should be at least 1000ms');
        }

        return errors;
    };

    // Environment-specific overrides
    const applyEnvironmentOverrides = (config) => {
        if (config.environment.isDevelopment) {
            // Development overrides
            config.logging.level = 'debug';
            config.logging.console = true;
            config.api.timeout = 60000; // Longer timeout for development
            config.performance.caching.enabled = false; // Disable cache in dev
        }

        if (config.environment.isProduction) {
            // Production overrides
            config.logging.level = 'error';
            config.logging.console = false;
            config.security.csrf.enabled = true;
            config.features.analytics = true;
        }

        return config;
    };

    // Apply overrides and validate
    const finalConfig = applyEnvironmentOverrides(BOSTARTERConfig);
    const validationErrors = validateConfig(finalConfig);

    if (validationErrors.length > 0) {
        console.error('[BOSTARTER Config] Validation errors:', validationErrors);
    }

    // Utility functions for configuration
    const ConfigUtils = {
        // Get configuration value with dot notation
        get: (path, defaultValue = null) => {
            return path.split('.').reduce((obj, key) => {
                return (obj && obj[key] !== undefined) ? obj[key] : defaultValue;
            }, finalConfig);
        },

        // Set configuration value with dot notation
        set: (path, value) => {
            const keys = path.split('.');
            const lastKey = keys.pop();
            const target = keys.reduce((obj, key) => {
                if (!(key in obj)) obj[key] = {};
                return obj[key];
            }, finalConfig);
            target[lastKey] = value;
        },

        // Check if feature is enabled
        isFeatureEnabled: (feature) => {
            return ConfigUtils.get(`features.${feature}`, false);
        },

        // Get current breakpoint
        getCurrentBreakpoint: () => {
            const width = window.innerWidth;
            const breakpoints = finalConfig.breakpoints;

            if (width >= breakpoints.xxl) return 'xxl';
            if (width >= breakpoints.xl) return 'xl';
            if (width >= breakpoints.lg) return 'lg';
            if (width >= breakpoints.md) return 'md';
            if (width >= breakpoints.sm) return 'sm';
            return 'xs';
        },

        // Check if current viewport matches breakpoint
        matchesBreakpoint: (breakpoint) => {
            const current = ConfigUtils.getCurrentBreakpoint();
            const breakpoints = ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'];
            const currentIndex = breakpoints.indexOf(current);
            const targetIndex = breakpoints.indexOf(breakpoint);
            return currentIndex >= targetIndex;
        },

        // Get API endpoint URL
        getApiUrl: (endpoint) => {
            const baseUrl = finalConfig.api.baseUrl;
            const endpointPath = finalConfig.api.endpoints[endpoint];
            if (!endpointPath) {
                console.warn(`[Config] Unknown API endpoint: ${endpoint}`);
                return null;
            }
            return baseUrl + endpointPath;
        },

        // Get color value
        getColor: (colorName) => {
            return finalConfig.colors[colorName] || colorName;
        },

        // Debug information
        getDebugInfo: () => {
            return {
                version: finalConfig.version,
                environment: finalConfig.environment,
                features: Object.entries(finalConfig.features)
                    .filter(([, enabled]) => enabled)
                    .map(([feature]) => feature),
                breakpoint: ConfigUtils.getCurrentBreakpoint(),
                theme: localStorage.getItem(finalConfig.ui.theme.storageKey) || finalConfig.ui.theme.default
            };
        }
    };

    // Export configuration to global scope
    window.BOSTARTERConfig = finalConfig;
    window.BOSTARTERConfigUtils = ConfigUtils;

    // Log initialization in development
    if (finalConfig.environment.debug) {
        console.log('[BOSTARTER Config] Configuration loaded:', finalConfig);
        console.log('[BOSTARTER Config] Debug info:', ConfigUtils.getDebugInfo());
    }

    // Dispatch configuration ready event
    document.addEventListener('DOMContentLoaded', () => {
        document.dispatchEvent(new CustomEvent('bostarterConfigReady', {
            detail: { config: finalConfig, utils: ConfigUtils }
        }));
    });

})(window);

