/**
 * BOSTARTER Master JavaScript - Sistema Unificato Avanzato
 * Versione 3.0.0 - Ottimizzato per Performance e Modernità
 * @author BOSTARTER Team
 * @version 3.0.0
 * @framework Bootstrap 5.3.3
 */
(function (window, document) {
    "use strict";

    // Performance optimizations
    const raf = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame;
    const caf = window.cancelAnimationFrame || window.webkitCancelAnimationFrame || window.mozCancelAnimationFrame;

    /**
     * Main BOSTARTER Class with enhanced features
     */
    class BOSTARTERMaster {
        constructor() {
            this.version = "3.0.0";
            this.framework = "Bootstrap 5.3.3";
            this.cache = new Map();
            this.observers = new Map();
            this.listeners = new Map();
            this.rafIds = new Set();
            this.isInitialized = false;
            this.settings = {
                debug: false,
                prefersReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                theme: localStorage.getItem('bostarter_theme') || 'auto',
                animations: !window.matchMedia('(prefers-reduced-motion: reduce)').matches
            };

            // Bind methods
            this.init = this.init.bind(this);
            this.destroy = this.destroy.bind(this);
            this.handleVisibilityChange = this.handleVisibilityChange.bind(this);

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', this.init);
            } else {
                this.init();
            }
        }

        /**
         * Initialize all components
         */
        init() {
            if (this.isInitialized) return;

            try {
                this.log('Initializing BOSTARTER Master v3.0.0');

                // Core initialization
                this.setupPerformanceOptimizations();
                this.setupAccessibility();
                this.setupNavbar();
                this.setupAnimations();
                this.setupInteractions();
                this.setupForms();
                this.setupModals();
                this.setupTooltips();
                this.setupLazyLoading();
                this.setupIntersectionObserver();
                this.setupErrorHandling();
                this.setupServiceWorker();

                // Mark as initialized
                this.isInitialized = true;
                this.log('BOSTARTER Master initialized successfully');

                // Dispatch custom event
                document.dispatchEvent(new CustomEvent('bostarterReady', {
                    detail: { version: this.version, instance: this }
                }));

            } catch (error) {
                this.error('Failed to initialize BOSTARTER Master:', error);
            }
        }

        /**
         * Setup performance optimizations
         */
        setupPerformanceOptimizations() {
            // Passive event listeners
            this.addPassiveListener(window, 'scroll', this.throttle(this.handleScroll.bind(this), 16));
            this.addPassiveListener(window, 'resize', this.debounce(this.handleResize.bind(this), 250));

            // Visibility change handling
            document.addEventListener('visibilitychange', this.handleVisibilityChange);

            // Preload critical resources
            this.preloadCriticalResources();
        }

        /**
         * Enhanced navbar functionality
         */
        setupNavbar() {
            const navbar = document.querySelector(".navbar-bostarter, .navbar-modern");
            if (!navbar) return;

            let lastScroll = 0;
            let isScrolling = false;

            const updateNavbar = () => {
                const currentScroll = window.pageYOffset;
                const scrollDirection = currentScroll > lastScroll ? 'down' : 'up';

                // Add scrolled class
                navbar.classList.toggle("scrolled", currentScroll > 50);

                // Auto-hide on mobile
                if (window.innerWidth <= 768) {
                    if (scrollDirection === 'down' && currentScroll > 200) {
                        navbar.style.transform = "translateY(-100%)";
                        navbar.setAttribute('aria-hidden', 'true');
                    } else if (scrollDirection === 'up') {
                        navbar.style.transform = "translateY(0)";
                        navbar.removeAttribute('aria-hidden');
                    }
                }

                lastScroll = currentScroll;
                isScrolling = false;
            };

            this.addListener('scroll', () => {
                if (!isScrolling) {
                    const rafId = raf(updateNavbar);
                    this.rafIds.add(rafId);
                    isScrolling = true;
                }
            });

            // Enhanced mobile menu
            const toggleButton = navbar.querySelector('.navbar-toggler');
            const navbarCollapse = navbar.querySelector('.navbar-collapse');

            if (toggleButton && navbarCollapse) {
                toggleButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';

                    toggleButton.setAttribute('aria-expanded', !isExpanded);
                    navbarCollapse.classList.toggle('show');

                    // Animate icon
                    toggleButton.classList.toggle('active');

                    // Trap focus when menu is open
                    if (!isExpanded) {
                        this.trapFocus(navbarCollapse);
                    }
                });

                // Close menu when clicking outside
                document.addEventListener('click', (e) => {
                    if (!navbar.contains(e.target) && navbarCollapse.classList.contains('show')) {
                        toggleButton.click();
                    }
                });

                // Close menu on escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && navbarCollapse.classList.contains('show')) {
                        toggleButton.click();
                        toggleButton.focus();
                    }
                });
            }
        }

        /**
         * Enhanced animations setup
         */
        setupAnimations() {
            if (!this.settings.animations) return;

            // Scroll animations
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            if (animatedElements.length > 0) {
                this.setupScrollAnimations(animatedElements);
            }

            // Counter animations
            const counters = document.querySelectorAll('[data-counter]');
            counters.forEach(counter => this.setupCounter(counter));

            // Progress bar animations
            const progressBars = document.querySelectorAll('.progress-bar[data-animate]');
            progressBars.forEach(bar => this.setupProgressBar(bar));
        }

        /**
         * Setup scroll-based animations
         */
        setupScrollAnimations(elements) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const delay = entry.target.dataset.delay || 0;
                        setTimeout(() => {
                            entry.target.classList.add('animate');
                        }, delay);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });

            elements.forEach(el => observer.observe(el));
            this.observers.set('scroll-animations', observer);
        }

        /**
         * Setup counter animation
         */
        setupCounter(element) {
            const target = parseInt(element.dataset.counter);
            const duration = parseInt(element.dataset.duration) || 2000;
            const increment = target / (duration / 16);

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCounter(entry.target, target, increment);
                        observer.unobserve(entry.target);
                    }
                });
            });

            observer.observe(element);
        }

        /**
         * Animate counter
         */
        animateCounter(element, target, increment) {
            let current = 0;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 16);
        }

        /**
         * Setup progress bar animation
         */
        setupProgressBar(progressBar) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateProgressBar(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            });

            observer.observe(progressBar);
        }

        /**
         * Animate progress bar
         */
        animateProgressBar(progressBar) {
            const targetWidth = progressBar.getAttribute('aria-valuenow');
            progressBar.style.width = '0%';
            setTimeout(() => {
                progressBar.style.width = `${targetWidth}%`;
            }, 100);
        }

        /**
         * Enhanced interactions setup
         */
        setupInteractions() {
            // Ripple effect for buttons
            const rippleButtons = document.querySelectorAll('.btn-modern, .btn-ripple');
            rippleButtons.forEach(button => this.addRippleEffect(button));

            // Smooth scrolling for anchor links
            const anchorLinks = document.querySelectorAll('a[href^="#"]');
            anchorLinks.forEach(link => {
                link.addEventListener('click', this.handleSmoothScroll.bind(this));
            });

            // Copy to clipboard functionality
            const copyButtons = document.querySelectorAll('[data-copy]');
            copyButtons.forEach(button => this.setupCopyButton(button));
        }

        /**
         * Add ripple effect to button
         */
        addRippleEffect(button) {
            button.addEventListener('click', (e) => {
                const ripple = document.createElement('span');
                const rect = button.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    top: ${y}px;
                    left: ${x}px;
                    background: rgba(255, 255, 255, 0.5);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;

                button.style.position = 'relative';
                button.style.overflow = 'hidden';
                button.appendChild(ripple);

                setTimeout(() => ripple.remove(), 600);
            });
        }

        /**
         * Handle smooth scroll
         */
        handleSmoothScroll(e) {
            e.preventDefault();
            const target = document.querySelector(e.target.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        /**
         * Setup copy button
         */
        setupCopyButton(button) {
            button.addEventListener('click', async () => {
                const text = button.dataset.copy;
                try {
                    await navigator.clipboard.writeText(text);
                    this.showNotification('Copied to clipboard!', 'success', 2000);
                } catch (err) {
                    this.showNotification('Failed to copy to clipboard', 'error', 3000);
                }
            });
        }

        /**
         * Enhanced forms setup
         */
        setupForms() {
            const forms = document.querySelectorAll('form[data-validate]');
            forms.forEach(form => {
                this.setupFormValidation(form);
            });

            // Auto-resize textareas
            const textareas = document.querySelectorAll('textarea[data-auto-resize]');
            textareas.forEach(textarea => this.setupAutoResize(textarea));
        }

        /**
         * Setup form validation
         */
        setupFormValidation(form) {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });

            // Real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', () => this.clearFieldError(input));
            });
        }

        /**
         * Setup auto-resize textarea
         */
        setupAutoResize(textarea) {
            const resize = () => {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            };

            textarea.addEventListener('input', resize);
            resize(); // Initial resize
        }

        /**
         * Validate form
         */
        validateForm(form) {
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isValid = false;
                }
            });

            return isValid;
        }

        /**
         * Validate individual field
         */
        validateField(field) {
            const value = field.value.trim();
            const type = field.type;
            let isValid = true;
            let errorMessage = '';

            // Required validation
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = 'This field is required';
            }

            // Email validation
            if (type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }

            // Update field state
            field.classList.toggle('is-invalid', !isValid);
            field.classList.toggle('is-valid', isValid && value);

            // Update error message
            const errorElement = field.parentElement.querySelector('.invalid-feedback');
            if (errorElement) {
                errorElement.textContent = errorMessage;
            }

            return isValid;
        }

        /**
         * Clear field error
         */
        clearFieldError(field) {
            field.classList.remove('is-invalid');
            if (field.value.trim()) {
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
            }
        }

        /**
         * Enhanced modals setup
         */
        setupModals() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('shown.bs.modal', () => {
                    this.trapFocus(modal);
                });

                modal.addEventListener('hidden.bs.modal', () => {
                    this.restoreFocus();
                });
            });
        }

        /**
         * Enhanced tooltips setup
         */
        setupTooltips() {
            // Initialize Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover focus'
                });
            });

            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });

            this.cache.set('tooltips', tooltipList);
            this.cache.set('popovers', popoverList);
        }

        /**
         * Setup lazy loading
         */
        setupLazyLoading() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.add('loaded');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });

                this.observers.set('lazy-loading', imageObserver);
            }
        }

        /**
         * Setup intersection observer for animations
         */
        setupIntersectionObserver() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '50px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.observe-on-scroll').forEach(el => {
                observer.observe(el);
            });

            this.observers.set('intersection', observer);
        }

        /**
         * Setup error handling
         */
        setupErrorHandling() {
            window.addEventListener('error', (event) => {
                this.error('JavaScript Error:', event.error);
            });

            window.addEventListener('unhandledrejection', (event) => {
                this.error('Unhandled Promise Rejection:', event.reason);
            });
        }

        /**
         * Setup service worker
         */
        setupServiceWorker() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        this.log('ServiceWorker registered:', registration);
                    })
                    .catch(error => {
                        this.log('ServiceWorker registration failed:', error);
                    });
            }
        }

        /**
         * Setup accessibility features
         */
        setupAccessibility() {
            // Skip link
            const skipLink = document.querySelector('.skip-link');
            if (skipLink) {
                skipLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = document.querySelector(skipLink.getAttribute('href'));
                    if (target) {
                        target.focus();
                    }
                });
            }

            // Focus management
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    document.body.classList.add('user-is-tabbing');
                }
            });

            document.addEventListener('mousedown', () => {
                document.body.classList.remove('user-is-tabbing');
            });
        }

        /**
         * Utility methods
         */
        throttle(func, limit) {
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
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        addPassiveListener(element, event, handler) {
            element.addEventListener(event, handler, { passive: true });
        }

        addListener(event, handler) {
            const listener = this.throttle(handler, 16);
            window.addEventListener(event, listener, { passive: true });
            this.listeners.set(event, listener);
        }

        trapFocus(element) {
            const focusableElements = element.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            element.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstElement) {
                            lastElement.focus();
                            e.preventDefault();
                        }
                    } else {
                        if (document.activeElement === lastElement) {
                            firstElement.focus();
                            e.preventDefault();
                        }
                    }
                }
            });

            firstElement?.focus();
        }

        restoreFocus() {
            // Implementation for restoring focus after modal closes
            const lastFocusedElement = document.querySelector('[data-last-focused]');
            if (lastFocusedElement) {
                lastFocusedElement.focus();
                lastFocusedElement.removeAttribute('data-last-focused');
            }
        }

        handleScroll() {
            // Additional scroll handling logic can be added here
        }

        handleResize() {
            // Additional resize handling logic can be added here
        }

        handleVisibilityChange() {
            if (document.hidden) {
                // Pause animations and processes when tab is hidden
                this.rafIds.forEach(id => caf(id));
                this.rafIds.clear();
            }
        }

        preloadCriticalResources() {
            // Preload critical resources
            const criticalResources = document.querySelectorAll('[data-preload]');
            criticalResources.forEach(resource => {
                const link = document.createElement('link');
                link.rel = 'preload';
                link.href = resource.dataset.preload;
                link.as = resource.dataset.preloadAs || 'fetch';
                document.head.appendChild(link);
            });
        }

        /**
         * Public API methods
         */
        showNotification(message, type = "primary", duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="${this.getIconForType(type)} me-2"></i>
                    <div>${message}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto-remove
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, duration);

            return notification;
        }

        getIconForType(type) {
            const icons = {
                primary: 'fas fa-info-circle',
                success: 'fas fa-check-circle',
                warning: 'fas fa-exclamation-triangle',
                danger: 'fas fa-times-circle',
                info: 'fas fa-info-circle'
            };
            return icons[type] || icons.primary;
        }

        showLoading(element, text = "Loading...") {
            const loading = document.createElement('div');
            loading.className = 'text-center p-4';
            loading.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">${text}</span>
                </div>
                <div class="mt-2">${text}</div>
            `;
            element.appendChild(loading);
            return loading;
        }

        // Cache methods with TTL
        setCache(key, value, ttl = 300000) { // 5 minutes default
            const expiry = Date.now() + ttl;
            this.cache.set(key, { value, expiry });
        }

        getCache(key) {
            const cached = this.cache.get(key);
            if (cached && cached.expiry > Date.now()) {
                return cached.value;
            }
            this.cache.delete(key);
            return null;
        }

        // Logging methods
        log(...args) {
            if (this.settings.debug) {
                console.log('[BOSTARTER]', ...args);
            }
        }

        error(...args) {
            console.error('[BOSTARTER Error]', ...args);
        }

        /**
         * Cleanup method
         */
        destroy() {
            // Clean up observers
            this.observers.forEach(observer => observer.disconnect());
            this.observers.clear();

            // Clean up event listeners
            this.listeners.forEach((listener, event) => {
                window.removeEventListener(event, listener);
            });
            this.listeners.clear();

            // Cancel animation frames
            this.rafIds.forEach(id => caf(id));
            this.rafIds.clear();

            // Clear cache
            this.cache.clear();

            this.isInitialized = false;
        }
    }

    // Initialize BOSTARTER
    const bostarter = new BOSTARTERMaster();

    // Export to global scope
    window.BOSTARTER = {
        version: bostarter.version,
        framework: bostarter.framework,
        instance: bostarter,

        // Public API
        showNotification: bostarter.showNotification.bind(bostarter),
        showLoading: bostarter.showLoading.bind(bostarter),
        setCache: bostarter.setCache.bind(bostarter),
        getCache: bostarter.getCache.bind(bostarter)
    };

    // Development helpers
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        window.BOSTARTER.debug = true;
        window.BOSTARTER.instance.settings.debug = true;
    }

})(window, document);
