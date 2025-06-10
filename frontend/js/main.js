/**
 * BOSTARTER - Applicazione JavaScript Principale
 * Implementazione moderna con ES6+ e funzionalità complete
 * 
 * Caratteristiche:
 * - Architettura JavaScript moderna con classi e moduli
 * - Intersection Observer per animazioni fluide
 * - Menu mobile e navigazione responsive
 * - Funzionalità di ricerca avanzata
 * - Caratteristiche di accessibilità
 * - Monitoraggio delle prestazioni
 * - Gestione errori e sistemi di fallback
 */

'use strict';

// Classe principale dell'applicazione BOSTARTER
class BOSTARTERApp {
    constructor() {
        this.versione = '2.0.0';
        this.inizializzata = false;
        this.moduli = new Map();
        this.osservatori = new Map();
        this.configurazione = {
            urlBaseApi: '/backend/api/',
            debug: false,
            prestazioni: true,
            animazioni: {
                durata: 300,           // Durata delle animazioni in millisecondi
                scaglionamento: 100,   // Ritardo tra animazioni multiple
                soglia: 0.1           // Soglia per triggerare le animazioni
            }
        };

        // Colleghiamo i metodi al contesto della classe
        this.inizializza = this.inizializza.bind(this);
        this.gestisciErrore = this.gestisciErrore.bind(this);
    }

    // Inizializzazione principale dell'applicazione
    async inizializza() {
        if (this.inizializzata) {
            return Promise.resolve();
        }

        console.log(`🚀 Inizializzazione BOSTARTER v${this.versione}`);

        try {
            await Promise.all([this.configuraOsservatoriIntersection(),
            this.inizializzaMenuMobile(),
            this.configuraRicerca(),
            this.inizializzaAnimazioni(),
            this.configuraMonitoraggioPrestazioni(),
            this.inizializzaGestoriForm(),
            this.configuraSistemaNotifiche()
            ]);

            this.inizializzata = true;
            console.log('✅ BOSTARTER inizializzata con successo');

            // Lanciamo un evento personalizzato per notificare l'inizializzazione
            document.dispatchEvent(new CustomEvent('bostarter:pronta', {
                detail: {
                    versione: this.versione,
                    timestamp: Date.now()
                }
            }));

            return true;
        } catch (errore) {
            console.error('❌ Inizializzazione BOSTARTER fallita:', errore);
            this.inizializzaModalitaFallback();
            return false;
        }
    }

    // Configurazione della gestione degli errori
    configuraGestioneErrori() {
        // Gestore globale degli errori
        window.addEventListener('error', this.gestisciErrore);
        window.addEventListener('unhandledrejection', (evento) => {
            this.gestisciErrore(evento.reason, 'promessa');
        });

        // Gestori dello stato della rete
        window.addEventListener('offline', () => {
            this.mostraNotifica('Connessione persa. Modalità offline attiva.', 'avviso', true);
        });

        window.addEventListener('online', () => {
            this.mostraNotifica('Connessione ripristinata!', 'successo', 3000);
        });

        return Promise.resolve();
    }

    // Gestore degli errori migliorato
    gestisciErrore(errore, contesto = 'generale') {
        const errorInfo = {
            message: error?.message || 'Unknown error',
            stack: error?.stack,
            context,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href
        };

        console.error('BOSTARTER Error:', errorInfo);

        // Don't show notifications for 404 errors or common resource loading issues
        if (errorInfo.message.includes('404') ||
            errorInfo.message.includes('Failed to fetch') ||
            errorInfo.message.includes('Loading chunk')) {
            return;
        }

        // Show user-friendly error notification
        this.showNotification(
            'Si è verificato un errore. Il team è stato notificato.',
            'error',
            5000
        );
    }

    // Initialize accessibility features
    initializeAccessibility() {
        // Skip links enhancement
        const skipLinks = document.querySelectorAll('.skip-link');
        skipLinks.forEach(link => {
            link.addEventListener('focus', () => {
                link.style.transform = 'translateY(0)';
                link.style.opacity = '1';
            });

            link.addEventListener('blur', () => {
                link.style.transform = 'translateY(-100%)';
                link.style.opacity = '0';
            });
        });

        // Enhanced keyboard navigation
        document.addEventListener('keydown', (event) => {
            switch (event.key) {
                case 'Escape':
                    this.closeAllOverlays();
                    break;
                case 'Tab':
                    this.handleTabNavigation(event);
                    break;
                case '/':
                    if (event.ctrlKey || event.metaKey) {
                        event.preventDefault();
                        this.focusSearch();
                    }
                    break;
            }
        });

        // Focus management
        document.addEventListener('focusin', (event) => {
            if (event.target.matches('a, button, input, select, textarea, [tabindex]')) {
                event.target.classList.add('keyboard-focus');
            }
        });

        document.addEventListener('focusout', (event) => {
            event.target.classList.remove('keyboard-focus');
        });

        // High contrast mode detection
        if (window.matchMedia('(prefers-contrast: high)').matches) {
            document.documentElement.classList.add('high-contrast');
        }

        // Reduced motion support
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('reduce-motion');
            this.config.animations.duration = 0;
        }

        return Promise.resolve();
    }

    // Setup intersection observers for animations
    setupIntersectionObservers() {
        // Create intersection observer for animations
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const animationType = element.dataset.animation || 'fadeIn';
                    const delay = element.dataset.delay || 0;

                    setTimeout(() => {
                        element.classList.add('animate', animationType);
                    }, parseInt(delay));

                    animationObserver.unobserve(element);
                }
            });
        }, {
            threshold: this.config.animations.threshold,
            rootMargin: '0px 0px -50px 0px'
        });

        // Observe all animation elements
        document.querySelectorAll('[data-animation]').forEach(el => {
            animationObserver.observe(el);
        });

        this.observers.set('animation', animationObserver);

        // Setup counter animation observer
        this.setupCounterObserver();

        // Setup progress bar observer
        this.setupProgressObserver();

        return Promise.resolve();
    }

    // Counter animation observer
    setupCounterObserver() {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.counter, [data-counter]').forEach(el => {
            counterObserver.observe(el);
        });

        this.observers.set('counter', counterObserver);
    }

    // Progress bar animation observer
    setupProgressObserver() {
        const progressObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateProgress(entry.target);
                    progressObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('.progress-bar, [data-progress]').forEach(el => {
            progressObserver.observe(el);
        });

        this.observers.set('progress', progressObserver);
    }

    // Animate counter elements
    animateCounter(element) {
        const target = parseInt(element.dataset.target || element.textContent.replace(/\D/g, ''));
        const duration = parseInt(element.dataset.duration || 2000);
        const start = 0;
        const startTime = performance.now();

        const updateCounter = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function for smooth animation
            const easeOutCubic = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(start + (target - start) * easeOutCubic);

            element.textContent = current.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        };

        requestAnimationFrame(updateCounter);
    }

    // Animate progress bars
    animateProgress(element) {
        const progress = element.dataset.progress ||
            element.getAttribute('aria-valuenow') ||
            parseFloat(element.style.width);

        if (!progress) return;

        element.style.width = '0%';
        element.style.transition = 'width 1.5s ease-out';

        requestAnimationFrame(() => {
            element.style.width = `${progress}%`;
        });
    }

    // Initialize mobile menu
    initializeMobileMenu() {
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle, .menu-toggle');
        const mobileMenu = document.querySelector('.mobile-menu, .nav-menu');
        const overlay = document.querySelector('.menu-overlay') || this.createMenuOverlay();

        if (!mobileMenuToggle || !mobileMenu) {
            return Promise.resolve();
        }

        // Toggle menu
        mobileMenuToggle.addEventListener('click', () => {
            const isOpen = mobileMenu.classList.contains('open');

            if (isOpen) {
                this.closeMobileMenu();
            } else {
                this.openMobileMenu();
            }
        });

        // Close on overlay click
        overlay.addEventListener('click', () => {
            this.closeMobileMenu();
        });

        // Close on escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && mobileMenu.classList.contains('open')) {
                this.closeMobileMenu();
            }
        });

        return Promise.resolve();
    }

    // Create menu overlay if it doesn't exist
    createMenuOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'menu-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        `;
        document.body.appendChild(overlay);
        return overlay;
    }

    // Open mobile menu
    openMobileMenu() {
        const mobileMenu = document.querySelector('.mobile-menu, .nav-menu');
        const overlay = document.querySelector('.menu-overlay');
        const toggle = document.querySelector('.mobile-menu-toggle, .menu-toggle');

        if (mobileMenu) {
            mobileMenu.classList.add('open');
            document.body.classList.add('menu-open');
        }

        if (overlay) {
            overlay.style.opacity = '1';
            overlay.style.visibility = 'visible';
        }

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.classList.add('active');
        }
    }

    // Close mobile menu
    closeMobileMenu() {
        const mobileMenu = document.querySelector('.mobile-menu, .nav-menu');
        const overlay = document.querySelector('.menu-overlay');
        const toggle = document.querySelector('.mobile-menu-toggle, .menu-toggle');

        if (mobileMenu) {
            mobileMenu.classList.remove('open');
            document.body.classList.remove('menu-open');
        }

        if (overlay) {
            overlay.style.opacity = '0';
            overlay.style.visibility = 'hidden';
        }

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.classList.remove('active');
        }
    }

    // Setup search functionality
    setupSearch() {
        const searchInput = document.querySelector('.search-input, input[type="search"]');

        if (!searchInput) {
            return Promise.resolve();
        }

        let searchTimeout;

        searchInput.addEventListener('input', (event) => {
            clearTimeout(searchTimeout);
            const query = event.target.value.trim();

            if (query.length < 2) {
                this.hideSearchResults();
                return;
            }

            searchTimeout = setTimeout(() => {
                this.performSearch(query);
            }, 300);
        });

        // Close search results on outside click
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.search-container')) {
                this.hideSearchResults();
            }
        });

        return Promise.resolve();
    }

    // Perform search
    async performSearch(query) {
        try {
            const response = await this.api.get('search', { q: query, limit: 10 });
            this.displaySearchResults(response.data || []);
        } catch (error) {
            console.error('Search error:', error);
            this.displaySearchResults([]);
        }
    }

    // Display search results
    displaySearchResults(results) {
        let searchResults = document.querySelector('.search-results');

        if (!searchResults) {
            searchResults = this.createSearchResults();
        }

        if (results.length === 0) {
            searchResults.innerHTML = '<div class="search-no-results">Nessun risultato trovato</div>';
        } else {
            searchResults.innerHTML = results.map(result => `
                <div class="search-result-item">
                    <a href="${result.url}" class="search-result-link">
                        <div class="search-result-title">${result.title}</div>
                        <div class="search-result-description">${result.description}</div>
                    </a>
                </div>
            `).join('');
        }

        searchResults.style.display = 'block';
    }

    // Create search results container
    createSearchResults() {
        const container = document.querySelector('.search-container') ||
            document.querySelector('.search-input')?.parentElement;

        if (!container) return null;

        const searchResults = document.createElement('div');
        searchResults.className = 'search-results';
        searchResults.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        `;

        container.style.position = 'relative';
        container.appendChild(searchResults);

        return searchResults;
    }

    // Hide search results
    hideSearchResults() {
        const searchResults = document.querySelector('.search-results');
        if (searchResults) {
            searchResults.style.display = 'none';
        }
    }

    // Focus search input
    focusSearch() {
        const searchInput = document.querySelector('.search-input, input[type="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }

    // Initialize animations
    initializeAnimations() {
        // Add animation classes to elements that need them
        document.querySelectorAll('.hero-content, .stat-item, .project-card, .category-card').forEach((el, index) => {
            if (!el.dataset.animation) {
                el.dataset.animation = 'fadeInUp';
                el.dataset.delay = index * this.config.animations.stagger;
            }
        });

        // Initialize scroll-triggered animations
        this.setupScrollAnimations();

        return Promise.resolve();
    }

    // Setup scroll-triggered animations
    setupScrollAnimations() {
        let ticking = false;

        const handleScroll = () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.updateScrollAnimations();
                    ticking = false;
                });
                ticking = true;
            }
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
    }

    // Update scroll animations
    updateScrollAnimations() {
        const scrolled = window.pageYOffset;

        // Parallax effect for hero section
        const hero = document.querySelector('.hero');
        if (hero) {
            hero.style.transform = `translateY(${scrolled * 0.5}px)`;
        }

        // Update header on scroll
        const header = document.querySelector('.header');
        if (header) {
            if (scrolled > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
    }

    // Setup performance monitoring
    setupPerformanceMonitoring() {
        if (!this.config.performance) {
            return Promise.resolve();
        }

        // Monitor Core Web Vitals
        this.monitorWebVitals();

        // Monitor resource loading
        this.monitorResourceLoading();

        return Promise.resolve();
    }

    // Monitor Core Web Vitals
    monitorWebVitals() {
        // First Contentful Paint
        const observer = new PerformanceObserver((list) => {
            list.getEntries().forEach((entry) => {
                if (entry.name === 'first-contentful-paint') {
                    console.log(`📊 FCP: ${entry.startTime.toFixed(2)}ms`);
                }
            });
        });

        try {
            observer.observe({ entryTypes: ['paint'] });
        } catch (error) {
            // PerformanceObserver not supported
        }

        // Largest Contentful Paint
        try {
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                console.log(`📊 LCP: ${lastEntry.startTime.toFixed(2)}ms`);
            });

            lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
        } catch (error) {
            // LCP not supported
        }
    }

    // Monitor resource loading
    monitorResourceLoading() {
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

    // Initialize form handlers
    initializeFormHandlers() {
        // Generic form handler
        document.querySelectorAll('form[data-ajax]').forEach(form => {
            form.addEventListener('submit', this.handleFormSubmit.bind(this));
        });

        // Newsletter form
        const newsletterForm = document.querySelector('.newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', this.handleNewsletterSubmit.bind(this));
        }

        // Contact forms
        document.querySelectorAll('.contact-form').forEach(form => {
            form.addEventListener('submit', this.handleContactSubmit.bind(this));
        });

        return Promise.resolve();
    }

    // Handle form submission
    async handleFormSubmit(event) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');

        // Disable submit button
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Invio in corso...';
        }

        try {
            const response = await this.api.post(form.action, Object.fromEntries(formData));

            this.showNotification('Modulo inviato con successo!', 'success');
            form.reset();

        } catch (error) {
            this.showNotification('Errore durante l\'invio del modulo.', 'error');
        } finally {
            // Re-enable submit button
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Invia';
            }
        }
    }

    // Handle newsletter subscription
    async handleNewsletterSubmit(event) {
        event.preventDefault();

        const form = event.target;
        const email = form.querySelector('input[type="email"]').value;

        try {
            await this.api.post('newsletter/subscribe', { email });
            this.showNotification('Iscrizione completata!', 'success');
            form.reset();
        } catch (error) {
            this.showNotification('Errore durante l\'iscrizione.', 'error');
        }
    }

    // Handle contact form
    async handleContactSubmit(event) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);

        try {
            await this.api.post('contact', Object.fromEntries(formData));
            this.showNotification('Messaggio inviato! Ti risponderemo presto.', 'success');
            form.reset();
        } catch (error) {
            this.showNotification('Errore durante l\'invio del messaggio.', 'error');
        }
    }

    // Setup notification system
    setupNotificationSystem() {
        // Create notification container if it doesn't exist
        if (!document.querySelector('.notification-container')) {
            const container = document.createElement('div');
            container.className = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }

        return Promise.resolve();
    }

    // Show notification
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.querySelector('.notification-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close" aria-label="Chiudi notifica">&times;</button>
            </div>
        `;

        // Styling
        notification.style.cssText = `
            background: ${this.getNotificationColor(type)};
            color: white;
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;

        // Add to container
        container.appendChild(notification);

        // Animate in
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });

        // Close button
        const closeButton = notification.querySelector('.notification-close');
        closeButton.addEventListener('click', () => {
            this.removeNotification(notification);
        });

        // Auto remove
        if (duration !== true && duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }
    }

    // Get notification color
    getNotificationColor(type) {
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        return colors[type] || colors.info;
    }

    // Remove notification
    removeNotification(notification) {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    // Utility methods
    closeAllOverlays() {
        // Close mobile menu
        this.closeMobileMenu();

        // Close modals
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.remove('show');
        });

        // Close dropdowns
        document.querySelectorAll('.dropdown.show').forEach(dropdown => {
            dropdown.classList.remove('show');
        });

        // Hide search results
        this.hideSearchResults();
    }

    handleTabNavigation(event) {
        // Enhanced tab navigation handling
        const focusableElements = document.querySelectorAll(
            'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (event.shiftKey) {
            if (document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            }
        } else {
            if (document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }
    }

    // Fallback mode initialization
    initializeFallbackMode() {
        console.log('🔄 Initializing fallback mode');

        // Basic error handling
        window.addEventListener('error', (event) => {
            console.error('Error (fallback):', event.error);
        });

        // Basic notification system
        this.showNotification = (message, type = 'info') => {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 16px;
                background: ${this.getNotificationColor(type)};
                color: white;
                border-radius: 4px;
                z-index: 10000;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        };

        this.initialized = true;
        this.fallbackMode = true;
    }

    // API utility methods
    get api() {
        return {
            baseUrl: this.config.apiBaseUrl,

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

                    return await response.json();
                } catch (error) {
                    console.error('API Error:', error);
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
            },

            put(endpoint, data = {}) {
                return this.request(endpoint, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
            },

            delete(endpoint) {
                return this.request(endpoint, { method: 'DELETE' });
            }
        };
    }
}

// Initialize the application
const app = new BOSTARTERApp();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => app.init());
} else {
    app.init();
}

// Make app globally available
window.BOSTARTER = app;

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BOSTARTERApp;
}

console.log('📄 BOSTARTER main.js v2.0.0 loaded');
