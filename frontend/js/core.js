/**
 * BOSTARTER - Core JavaScript 
 * Sistema JavaScript moderno con funzionalità avanzate
 * @version 4.1.0
 */
(function () {
    'use strict';

    // Configuration
    const CONFIG = {
        version: '4.1.0',
        debug: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1',
        api: {
            base: '/BOSTARTER/backend/api/',
            timeout: 10000
        },
        theme: {
            default: 'light',
            storageKey: 'bostarter_theme'
        },
        animations: {
            duration: 300,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)'
        }
    };

    // State Management
    const state = {
        initialized: false,
        theme: localStorage.getItem(CONFIG.theme.storageKey) || CONFIG.theme.default,
        user: null,
        observers: new Map(),
        cache: new Map()
    };

    // Utility Functions
    const utils = {
        log: (...args) => CONFIG.debug && console.log('[BOSTARTER]', ...args),
        error: (...args) => console.error('[BOSTARTER Error]', ...args),
        warn: (...args) => CONFIG.debug && console.warn('[BOSTARTER Warning]', ...args),

        // DOM Utilities
        $(selector) {
            return typeof selector === 'string' ? document.querySelector(selector) : selector;
        },

        $$(selector) {
            return typeof selector === 'string' ? document.querySelectorAll(selector) : selector;
        },

        createElement(tag, options = {}) {
            const element = document.createElement(tag);

            if (options.className) element.className = options.className;
            if (options.id) element.id = options.id;
            if (options.textContent) element.textContent = options.textContent;
            if (options.innerHTML) element.innerHTML = options.innerHTML;

            if (options.attributes) {
                Object.entries(options.attributes).forEach(([key, value]) => {
                    element.setAttribute(key, value);
                });
            }

            if (options.styles) {
                Object.assign(element.style, options.styles);
            }

            return element;
        },

        // String Utilities
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // Date Utilities
        formatDate(date, format = 'it-IT') {
            const d = new Date(date);
            return d.toLocaleDateString(format, {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        formatCurrency(amount, currency = 'EUR') {
            return new Intl.NumberFormat('it-IT', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },

        // Performance Utilities
        debounce(func, delay = CONFIG.animations.duration) {
            let timeoutId;
            return function (...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        },

        throttle(func, delay = 16) {
            let lastCall = 0;
            return function (...args) {
                const now = Date.now();
                if (now - lastCall >= delay) {
                    lastCall = now;
                    return func.apply(this, args);
                }
            };
        },

        // Animation Utilities
        animate(element, keyframes, options = {}) {
            const defaultOptions = {
                duration: CONFIG.animations.duration,
                easing: CONFIG.animations.easing,
                fill: 'forwards'
            };

            return element.animate(keyframes, { ...defaultOptions, ...options });
        },

        fadeIn(element, duration = CONFIG.animations.duration) {
            element.style.opacity = '0';
            element.style.display = 'block';

            return this.animate(element, [
                { opacity: 0 },
                { opacity: 1 }
            ], { duration });
        },

        fadeOut(element, duration = CONFIG.animations.duration) {
            return this.animate(element, [
                { opacity: 1 },
                { opacity: 0 }
            ], { duration }).finished.then(() => {
                element.style.display = 'none';
            });
        },

        // Generate unique ID
        generateId() {
            return 'bos_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
        }
    };

    // API Handler
    const api = {
        async request(endpoint, options = {}) {
            const url = CONFIG.api.base + endpoint;
            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            };

            try {
                const response = await fetch(url, { ...defaultOptions, ...options });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                return data;
            } catch (error) {
                utils.error('API Request failed:', error);
                throw error;
            }
        },

        async get(endpoint, params = {}) {
            const searchParams = new URLSearchParams(params);
            const url = searchParams.toString() ? `${endpoint}?${searchParams}` : endpoint;
            return this.request(url);
        },

        async post(endpoint, data = {}) {
            return this.request(endpoint, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }
    };

    // Notification System
    const notifications = {
        container: null,

        init() {
            if (!this.container) {
                this.container = utils.createElement('div', {
                    className: 'notification-container position-fixed top-0 end-0 p-3',
                    styles: { zIndex: 9999 }
                });
                document.body.appendChild(this.container);
            }
        },

        show(message, type = 'info', duration = 5000) {
            this.init();

            const notificationId = utils.generateId();
            const notification = utils.createElement('div', {
                className: `alert alert-${type} alert-dismissible fade show shadow-sm`,
                id: notificationId,
                innerHTML: `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${this.getIcon(type)} me-2"></i>
                        <span>${utils.escapeHtml(message)}</span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                `
            });

            this.container.appendChild(notification);

            // Auto remove
            if (duration > 0) {
                setTimeout(() => {
                    this.remove(notificationId);
                }, duration);
            }

            return notificationId;
        },

        remove(notificationId) {
            const notification = utils.$(`#${notificationId}`);
            if (notification) {
                utils.fadeOut(notification).then(() => {
                    notification.remove();
                });
            }
        },

        success(message, duration) {
            return this.show(message, 'success', duration);
        },

        error(message, duration) {
            return this.show(message, 'danger', duration);
        },

        warning(message, duration) {
            return this.show(message, 'warning', duration);
        },

        info(message, duration) {
            return this.show(message, 'info', duration);
        },

        getIcon(type) {
            const icons = {
                success: 'check-circle',
                danger: 'exclamation-circle',
                warning: 'exclamation-triangle',
                info: 'info-circle'
            };
            return icons[type] || 'info-circle';
        }
    };

    // Form Utilities
    const forms = {
        validate(form) {
            const errors = [];
            const inputs = form.querySelectorAll('input, select, textarea');

            inputs.forEach(input => {
                if (input.hasAttribute('required') && !input.value.trim()) {
                    errors.push(`${this.getFieldLabel(input)} è obbligatorio`);
                    this.markInvalid(input);
                } else {
                    this.markValid(input);
                }

                // Email validation
                if (input.type === 'email' && input.value && !this.isValidEmail(input.value)) {
                    errors.push('Formato email non valido');
                    this.markInvalid(input);
                }
            });

            return errors;
        },

        markInvalid(input) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
        },

        markValid(input) {
            input.classList.add('is-valid');
            input.classList.remove('is-invalid');
        },

        getFieldLabel(input) {
            const label = utils.$(`label[for="${input.id}"]`);
            return label ? label.textContent.trim() : input.name || 'Campo';
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    };

    // Theme Management
    const theme = {
        init() {
            this.applyTheme(state.theme);
            this.setupToggle();
        },

        applyTheme(newTheme) {
            state.theme = newTheme;
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem(CONFIG.theme.storageKey, newTheme);
        },

        toggle() {
            const newTheme = state.theme === 'light' ? 'dark' : 'light';
            this.applyTheme(newTheme);
        },

        setupToggle() {
            const toggles = utils.$$('[data-theme-toggle]');
            toggles.forEach(toggle => {
                toggle.addEventListener('click', () => this.toggle());
            });
        }
    };

    // Component Handlers
    const components = {
        initSmoothScrolling() {
            utils.$$('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = utils.$(anchor.getAttribute('href'));

                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        },

        initBootstrapComponents() {
            // Initialize Bootstrap tooltips
            const tooltipTriggerList = utils.$$('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                if (typeof bootstrap !== 'undefined') {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                }
            });

            // Initialize Bootstrap popovers
            const popoverTriggerList = utils.$$('[data-bs-toggle="popover"]');
            popoverTriggerList.forEach(popoverTriggerEl => {
                if (typeof bootstrap !== 'undefined') {
                    new bootstrap.Popover(popoverTriggerEl);
                }
            });
        }
    };

    // Event Handlers
    const events = {
        init() {
            this.setupGlobalHandlers();
            this.setupFormHandlers();
        },

        setupGlobalHandlers() {
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // ESC key to close modals
                if (e.key === 'Escape') {
                    const openModal = utils.$('.modal.show');
                    if (openModal && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getInstance(openModal)?.hide();
                    }
                }
            });

            // Handle offline/online status
            window.addEventListener('online', () => {
                notifications.success('Connessione ripristinata');
            });

            window.addEventListener('offline', () => {
                notifications.warning('Connessione persa');
            });
        },

        setupFormHandlers() {
            // Auto-validation for forms
            utils.$$('form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    const errors = forms.validate(form);

                    if (errors.length > 0) {
                        e.preventDefault();
                        notifications.error(errors.join('<br>'));
                    }
                });
            });
        }
    };

    // Main Application
    const app = {
        init() {
            if (state.initialized) return;

            try {
                utils.log('Initializing BOSTARTER v4.1.0');

                // Initialize core systems
                theme.init();
                events.init();
                components.initSmoothScrolling();
                components.initBootstrapComponents();

                state.initialized = true;
                utils.log('BOSTARTER initialized successfully');

                // Dispatch ready event
                document.dispatchEvent(new CustomEvent('bostarterReady', {
                    detail: { version: CONFIG.version }
                }));

            } catch (error) {
                utils.error('Initialization failed:', error);
            }
        },

        // Public API
        getVersion() {
            return CONFIG.version;
        },

        showNotification(message, type, duration) {
            return notifications.show(message, type, duration);
        },

        validateForm(form) {
            return forms.validate(form);
        },

        apiRequest(endpoint, options) {
            return api.request(endpoint, options);
        },

        utils: utils,
        api: api,
        notifications: notifications,
        forms: forms,
        theme: theme,
        components: components
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', app.init);
    } else {
        app.init();
    }

    // Global API
    window.BOSTARTER = app;

    // Export for module systems
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = app;
    }

})();
