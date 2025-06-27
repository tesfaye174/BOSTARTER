/**
 * BOSTARTER - Sistema JavaScript Moderno e Completo
 * Versione: 2.0 - Giugno 2025
 */

(function () {
    'use strict';

    // State management
    const state = {
        isLoading: true,
        theme: localStorage.getItem('theme') || 'light',
        userMenuOpen: false,
        notifications: []
    };

    // DOM Elements cache
    const elements = {};

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('🚀 BOSTARTER: Inizializzazione JavaScript v2.0');

        // Cache DOM elements
        cacheElements();

        // Initialize all modules
        loadingManager.init();
        animations.init();
        navigation.init();
        forms.init();
        userInterface.init();
        advancedUI.init();
        performanceMonitor.init();
        accessibility.init();
        serviceWorker.init();

        // Initialize page-specific features
        if (document.body.classList.contains('homepage')) {
            homepage.init();
        }

        // Set initial theme
        themeManager.setTheme(state.theme);

        console.log('✅ BOSTARTER: Inizializzazione completata');
    }

    function cacheElements() {
        elements.loadingOverlay = document.getElementById('loading-overlay');
        elements.userMenuButton = document.getElementById('user-menu-button');
        elements.userMenu = document.getElementById('user-menu');
        elements.themeToggle = document.getElementById('theme-toggle');
        elements.notificationContainer = document.getElementById('notifications-container');
        elements.mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
        elements.mobileMenu = document.querySelector('[data-mobile-menu]');
    }

    // Loading Manager
    const loadingManager = {
        init() {
            this.hideLoadingOverlay();
        },

        hideLoadingOverlay() {
            if (elements.loadingOverlay) {
                setTimeout(() => {
                    elements.loadingOverlay.style.opacity = '0';
                    setTimeout(() => {
                        elements.loadingOverlay.style.display = 'none';
                        state.isLoading = false;
                        this.triggerLoadComplete();
                    }, 500);
                }, 100);
            }
        },

        triggerLoadComplete() {
            // Trigger custom event for load completion
            document.dispatchEvent(new CustomEvent('bostarterLoaded'));
        }
    };

    // Enhanced Animation System
    const animations = {
        observers: new Map(),

        init() {
            console.log('🎨 Inizializzazione sistema animazioni');
            this.setupScrollAnimations();
            this.setupLoadAnimations();
        },

        setupScrollAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');

                        // Add staggered animation delay for grouped elements
                        const siblings = Array.from(entry.target.parentNode.children);
                        const index = siblings.indexOf(entry.target);
                        entry.target.style.animationDelay = `${index * 0.1}s`;
                    }
                });
            }, observerOptions);

            // Observe elements
            document.querySelectorAll('.animate-on-scroll, .stats-card, [data-animate]').forEach(el => {
                observer.observe(el);
            });

            this.observers.set('scroll', observer);
        },

        setupLoadAnimations() {
            // Animate elements on page load
            const animateElements = document.querySelectorAll('[data-animate-load]');
            animateElements.forEach((el, index) => {
                setTimeout(() => {
                    el.classList.add('animate-in');
                }, index * 100);
            });
        },

        animateElement(element, animationClass, duration = 300) {
            return new Promise(resolve => {
                element.classList.add(animationClass);
                setTimeout(() => {
                    element.classList.remove(animationClass);
                    resolve();
                }, duration);
            });
        }
    };

    // Enhanced Navigation
    const navigation = {
        init() {
            console.log('🧭 Inizializzazione navigazione');
            this.setupSmoothScroll();
            this.setupUserMenu();
            this.setupMobileMenu();
            this.setupNavHighlight();
        },

        setupSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = document.querySelector(anchor.getAttribute('href'));
                    if (target) {
                        const headerOffset = 80;
                        const elementPosition = target.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        },

        setupUserMenu() {
            if (elements.userMenuButton && elements.userMenu) {
                elements.userMenuButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleUserMenu();
                });

                // Close menu when clicking outside
                document.addEventListener('click', (e) => {
                    if (!elements.userMenu.contains(e.target) && !elements.userMenuButton.contains(e.target)) {
                        this.closeUserMenu();
                    }
                });

                // Close menu on escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && state.userMenuOpen) {
                        this.closeUserMenu();
                    }
                });
            }
        },

        toggleUserMenu() {
            state.userMenuOpen = !state.userMenuOpen;
            elements.userMenu.classList.toggle('show', state.userMenuOpen);
            elements.userMenuButton.setAttribute('aria-expanded', state.userMenuOpen);
        },

        closeUserMenu() {
            state.userMenuOpen = false;
            elements.userMenu.classList.remove('show');
            elements.userMenuButton.setAttribute('aria-expanded', 'false');
        },

        setupMobileMenu() {
            if (elements.mobileMenuToggle && elements.mobileMenu) {
                elements.mobileMenuToggle.addEventListener('click', () => {
                    elements.mobileMenu.classList.toggle('hidden');
                });
            }
        },

        setupNavHighlight() {
            // Highlight current nav item based on scroll position
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('nav a[href^="#"]');

            if (sections.length === 0 || navLinks.length === 0) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        navLinks.forEach(link => {
                            link.classList.remove('active');
                            if (link.getAttribute('href') === `#${entry.target.id}`) {
                                link.classList.add('active');
                            }
                        });
                    }
                });
            }, { threshold: 0.5 });

            sections.forEach(section => observer.observe(section));
        }
    };

    // Enhanced Form Handling
    const forms = {
        init() {
            console.log('📝 Inizializzazione gestione form');
            this.setupValidation();
            this.setupEnhancements();
        },

        setupValidation() {
            document.querySelectorAll('form[data-validate]').forEach(form => {
                form.addEventListener('submit', (e) => this.validateForm(e));

                // Real-time validation
                form.querySelectorAll('input, textarea, select').forEach(field => {
                    field.addEventListener('blur', () => this.validateField(field));
                    field.addEventListener('input', () => this.clearFieldError(field));
                });
            });
        },

        setupEnhancements() {
            // Auto-resize textareas
            document.querySelectorAll('textarea[data-auto-resize]').forEach(textarea => {
                textarea.addEventListener('input', () => {
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                });
            });

            // Character counters
            document.querySelectorAll('[data-max-length]').forEach(field => {
                const maxLength = parseInt(field.getAttribute('data-max-length'));
                const counter = document.createElement('div');
                counter.className = 'text-sm text-gray-500 mt-1';
                field.parentNode.appendChild(counter);

                const updateCounter = () => {
                    const remaining = maxLength - field.value.length;
                    counter.textContent = `${remaining} caratteri rimanenti`;
                    counter.classList.toggle('text-red-500', remaining < 10);
                };

                field.addEventListener('input', updateCounter);
                updateCounter();
            });
        },

        validateForm(e) {
            const form = e.currentTarget;
            let isValid = true;

            // Clear previous errors
            this.clearFormErrors(form);

            // Validate all fields
            form.querySelectorAll('[required], [data-validate]').forEach(field => {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Focus first invalid field
                const firstError = form.querySelector('.border-red-500');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            return isValid;
        },

        validateField(field) {
            const value = field.value.trim();
            const type = field.type;
            const required = field.hasAttribute('required');
            let isValid = true;
            let errorMessage = '';

            // Required validation
            if (required && !value) {
                errorMessage = 'Questo campo è obbligatorio';
                isValid = false;
            }
            // Email validation
            else if (type === 'email' && value && !this.isValidEmail(value)) {
                errorMessage = 'Inserisci un\'email valida';
                isValid = false;
            }
            // Password validation
            else if (type === 'password' && value && value.length < 8) {
                errorMessage = 'La password deve contenere almeno 8 caratteri';
                isValid = false;
            }
            // Custom validation
            else if (field.hasAttribute('data-pattern')) {
                const pattern = new RegExp(field.getAttribute('data-pattern'));
                if (value && !pattern.test(value)) {
                    errorMessage = field.getAttribute('data-error-message') || 'Formato non valido';
                    isValid = false;
                }
            }

            if (!isValid) {
                this.showFieldError(field, errorMessage);
            } else {
                this.clearFieldError(field);
            }

            return isValid;
        },

        showFieldError(field, message) {
            field.classList.add('border-red-500');

            let errorEl = field.parentNode.querySelector('.error-message');
            if (!errorEl) {
                errorEl = document.createElement('div');
                errorEl.className = 'error-message text-red-500 text-sm mt-1';
                field.parentNode.appendChild(errorEl);
            }
            errorEl.textContent = message;
        },

        clearFieldError(field) {
            field.classList.remove('border-red-500');
            const errorEl = field.parentNode.querySelector('.error-message');
            if (errorEl) {
                errorEl.remove();
            }
        },

        clearFormErrors(form) {
            form.querySelectorAll('.border-red-500').forEach(field => {
                field.classList.remove('border-red-500');
            });
            form.querySelectorAll('.error-message').forEach(error => {
                error.remove();
            });
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    };

    // Theme Manager
    const themeManager = {
        setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            state.theme = theme;

            if (elements.themeToggle) {
                const icon = elements.themeToggle.querySelector('i');
                if (icon) {
                    icon.className = theme === 'dark' ? 'ri-sun-line text-xl' : 'ri-moon-line text-xl';
                }
            }
        },

        toggleTheme() {
            const newTheme = state.theme === 'light' ? 'dark' : 'light';
            this.setTheme(newTheme);
        }
    };

    // User Interface Enhancements
    const userInterface = {
        init() {
            console.log('🎨 Inizializzazione interfaccia utente');
            this.setupThemeToggle();
            this.setupTooltips();
            this.setupKeyboardNavigation();
        },

        setupThemeToggle() {
            if (elements.themeToggle) {
                elements.themeToggle.addEventListener('click', () => {
                    themeManager.toggleTheme();
                });
            }
        },

        setupTooltips() {
            document.querySelectorAll('[data-tooltip]').forEach(element => {
                element.addEventListener('mouseenter', (e) => {
                    this.showTooltip(e.target, e.target.getAttribute('data-tooltip'));
                });
                element.addEventListener('mouseleave', this.hideTooltip);
            });
        },

        showTooltip(element, text) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip absolute bg-gray-900 text-white text-sm rounded px-2 py-1 z-50';
            tooltip.textContent = text;
            document.body.appendChild(tooltip);

            const rect = element.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        },

        hideTooltip() {
            document.querySelectorAll('.tooltip').forEach(tooltip => tooltip.remove());
        },

        setupKeyboardNavigation() {
            // Enhanced keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    document.body.classList.add('keyboard-navigation');
                }
            });

            document.addEventListener('mousedown', () => {
                document.body.classList.remove('keyboard-navigation');
            });
        }
    };

    // Homepage Specific Features
    const homepage = {
        init() {
            console.log('🏠 Inizializzazione homepage');
            this.setupStatsCounter();
            this.setupProjectCards();
            this.setupNewsletterForm();
        },

        setupStatsCounter() {
            const statsCards = document.querySelectorAll('.stats-card');

            statsCards.forEach((card, index) => {
                const countElement = card.querySelector('[data-count]');
                if (countElement) {
                    const targetValue = parseInt(countElement.getAttribute('data-count'));
                    setTimeout(() => {
                        this.animateCounter(countElement, targetValue);
                    }, index * 200);
                }
            });
        },

        animateCounter(element, target, duration = 2000) {
            const start = 0;
            const startTime = performance.now();

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const current = Math.floor(start + (target - start) * easeOutQuart);

                element.textContent = current.toLocaleString('it-IT');

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };

            requestAnimationFrame(animate);
        },

        setupProjectCards() {
            document.querySelectorAll('.project-card').forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-8px)';
                });

                card.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                });

                // Add click tracking
                card.addEventListener('click', (e) => {
                    if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                        const link = card.querySelector('a');
                        if (link) {
                            link.click();
                        }
                    }
                });
            });
        },

        setupNewsletterForm() {
            const newsletterForm = document.querySelector('#newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const email = newsletterForm.querySelector('input[type="email"]').value;
                    const submitButton = newsletterForm.querySelector('button[type="submit"]');

                    submitButton.disabled = true;
                    submitButton.textContent = 'Iscrizione in corso...';

                    try {
                        // Simulate API call
                        await new Promise(resolve => setTimeout(resolve, 1000));

                        // Show success message
                        utils.showNotification('Iscrizione completata con successo!', 'success');
                        newsletterForm.reset();

                    } catch (error) {
                        utils.showNotification('Errore durante l\'iscrizione. Riprova.', 'error');
                    } finally {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Iscriviti';
                    }
                });
            }
        }
    };

    // Advanced UI Interactions
    const advancedUI = {
        init() {
            console.log('🎯 Inizializzazione interazioni avanzate');
            this.setupRippleEffects();
            this.setupLazyLoading();
            this.setupScrollReveal();
            this.setupParallaxEffects();
            this.setupTypingAnimations();
        },

        setupRippleEffects() {
            document.querySelectorAll('.btn-ripple').forEach(button => {
                button.addEventListener('click', function (e) {
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    const ripple = document.createElement('div');
                    ripple.className = 'ripple-effect';
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.4);
                        border-radius: 50%;
                        pointer-events: none;
                        transform: scale(0);
                        animation: ripple-expand 0.6s ease-out;
                    `;

                    this.appendChild(ripple);

                    setTimeout(() => ripple.remove(), 600);
                });
            });
        },

        setupLazyLoading() {
            const images = document.querySelectorAll('img[loading="lazy"]');
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        },

        setupScrollReveal() {
            const elements = document.querySelectorAll('.scroll-reveal');
            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        revealObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            elements.forEach(el => revealObserver.observe(el));
        },

        setupParallaxEffects() {
            const parallaxElements = document.querySelectorAll('[data-parallax]');

            if (parallaxElements.length > 0) {
                const handleParallax = utils.throttle(() => {
                    const scrolled = window.pageYOffset;

                    parallaxElements.forEach(element => {
                        const rate = parseFloat(element.dataset.parallax) || 0.5;
                        const yPos = -(scrolled * rate);
                        element.style.transform = `translateY(${yPos}px)`;
                    });
                }, 16);

                window.addEventListener('scroll', handleParallax);
            }
        },

        setupTypingAnimations() {
            const typingElements = document.querySelectorAll('.typing-animation');

            typingElements.forEach(element => {
                const text = element.textContent;
                element.textContent = '';
                element.style.maxWidth = '100%';

                let index = 0;
                const typeInterval = setInterval(() => {
                    element.textContent = text.slice(0, index);
                    index++;

                    if (index > text.length) {
                        clearInterval(typeInterval);
                        element.style.borderRight = 'none';
                    }
                }, 100);
            });
        }
    };

    // Performance Monitor
    const performanceMonitor = {
        metrics: {
            loadTime: 0,
            renderTime: 0,
            interactionTime: 0
        },

        init() {
            console.log('📊 Inizializzazione monitoraggio performance');
            this.measureLoadTime();
            this.measureRenderTime();
            this.setupInteractionTracking();
        },

        measureLoadTime() {
            window.addEventListener('load', () => {
                this.metrics.loadTime = performance.now();
                console.log(`⚡ Tempo di caricamento: ${this.metrics.loadTime.toFixed(2)}ms`);
            });
        },

        measureRenderTime() {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.entryType === 'measure') {
                        this.metrics.renderTime = entry.duration;
                        console.log(`🎨 Tempo di rendering: ${entry.duration.toFixed(2)}ms`);
                    }
                }
            });
            observer.observe({ entryTypes: ['measure'] });
        },

        setupInteractionTracking() {
            ['click', 'touchstart', 'keydown'].forEach(eventType => {
                document.addEventListener(eventType, (e) => {
                    const start = performance.now();
                    requestAnimationFrame(() => {
                        const end = performance.now();
                        this.metrics.interactionTime = end - start;
                    });
                }, { passive: true });
            });
        },

        getMetrics() {
            return this.metrics;
        }
    };

    // Accessibility Enhancements
    const accessibility = {
        init() {
            console.log('♿ Inizializzazione accessibilità');
            this.setupKeyboardNavigation();
            this.setupScreenReaderSupport();
            this.setupHighContrastMode();
            this.setupReducedMotion();
        },

        setupKeyboardNavigation() {
            // Trap focus in modals
            document.querySelectorAll('.modal').forEach(modal => {
                const focusableElements = modal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );

                if (focusableElements.length > 0) {
                    const firstElement = focusableElements[0];
                    const lastElement = focusableElements[focusableElements.length - 1];

                    modal.addEventListener('keydown', (e) => {
                        if (e.key === 'Tab') {
                            if (e.shiftKey && document.activeElement === firstElement) {
                                e.preventDefault();
                                lastElement.focus();
                            } else if (!e.shiftKey && document.activeElement === lastElement) {
                                e.preventDefault();
                                firstElement.focus();
                            }
                        }
                    });
                }
            });

            // Skip links
            const skipLink = document.querySelector('.skip-link');
            if (skipLink) {
                skipLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = document.querySelector(skipLink.getAttribute('href'));
                    if (target) {
                        target.focus();
                        target.scrollIntoView();
                    }
                });
            }
        },

        setupScreenReaderSupport() {
            // Announce page changes
            const announcer = document.createElement('div');
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.className = 'sr-only';
            document.body.appendChild(announcer);

            // Announce navigation changes
            const navLinks = document.querySelectorAll('nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    setTimeout(() => {
                        announcer.textContent = `Navigazione a ${link.textContent}`;
                    }, 100);
                });
            });
        },

        setupHighContrastMode() {
            const contrastToggle = document.getElementById('high-contrast-toggle');
            if (contrastToggle) {
                contrastToggle.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        document.documentElement.classList.add('high-contrast');
                    } else {
                        document.documentElement.classList.remove('high-contrast');
                    }
                });
            }
        },

        setupReducedMotion() {
            const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

            const handleMotionChange = (e) => {
                if (e.matches) {
                    document.documentElement.classList.add('reduce-motion');
                } else {
                    document.documentElement.classList.remove('reduce-motion');
                }
            };

            motionQuery.addListener(handleMotionChange);
            handleMotionChange(motionQuery);
        }
    };

    // Service Worker Management
    const serviceWorker = {
        init() {
            if ('serviceWorker' in navigator) {
                this.register();
                this.setupUpdateNotification();
            }
        },

        async register() {
            try {
                const registration = await navigator.serviceWorker.register('/BOSTARTER/frontend/sw.js');
                console.log('✅ Service Worker registrato:', registration.scope);

                registration.addEventListener('updatefound', () => {
                    console.log('🔄 Aggiornamento Service Worker disponibile');
                });
            } catch (error) {
                console.error('❌ Errore registrazione Service Worker:', error);
            }
        },

        setupUpdateNotification() {
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                utils.showNotification(
                    'Nuova versione disponibile! Ricarica la pagina per aggiornare.',
                    'info',
                    10000
                );
            });
        }
    };

    // Export to global scope
    window.BOSTARTER = {
        state,
        elements,
        loadingManager,
        animations,
        navigation,
        forms,
        themeManager,
        userInterface,
        homepage,
        utils,
        advancedUI,
        performanceMonitor,
        accessibility,
        serviceWorker
    };

    // Custom events
    document.addEventListener('bostarterLoaded', () => {
        console.log('🎉 BOSTARTER: Caricamento completato');
    });

})();
