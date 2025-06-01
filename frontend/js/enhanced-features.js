/* ===== BOSTARTER ENHANCED JAVASCRIPT ===== */
/* Advanced features and utilities */

// ===== SERVICE WORKER REGISTRATION =====
if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/frontend/sw.js');
            console.log('✅ Service Worker registered successfully:', registration.scope);

            // Check for updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // Show update notification
                        if (window.bosstarterApp) {
                            window.bosstarterApp.showNotification(
                                'Nuova versione disponibile! Ricarica la pagina per aggiornare.',
                                'info',
                                10000
                            );
                        }
                    }
                });
            });
        } catch (error) {
            console.warn('❌ Service Worker registration failed:', error);
        }
    });
}

// ===== PERFORMANCE MONITORING =====
if ('performance' in window && 'getEntriesByType' in performance) {
    window.addEventListener('load', () => {
        setTimeout(() => {
            const perfData = performance.getEntriesByType('navigation')[0];
            const metrics = {
                loadTime: Math.round(perfData.loadEventEnd - perfData.fetchStart),
                domReady: Math.round(perfData.domContentLoadedEventEnd - perfData.fetchStart),
                firstPaint: null,
                firstContentfulPaint: null
            };

            // Get paint timings
            const paintEntries = performance.getEntriesByType('paint');
            paintEntries.forEach(entry => {
                if (entry.name === 'first-paint') {
                    metrics.firstPaint = Math.round(entry.startTime);
                } else if (entry.name === 'first-contentful-paint') {
                    metrics.firstContentfulPaint = Math.round(entry.startTime);
                }
            });

            console.log('📊 Performance Metrics:', metrics);

            // Send to analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'page_performance', {
                    load_time: metrics.loadTime,
                    dom_ready: metrics.domReady,
                    first_paint: metrics.firstPaint,
                    first_contentful_paint: metrics.firstContentfulPaint
                });
            }
        }, 0);
    });
}

// ===== NETWORK STATUS MONITORING =====
function updateNetworkStatus() {
    const isOnline = navigator.onLine;
    const statusElement = document.querySelector('.network-status');

    if (statusElement) {
        statusElement.className = `network-status ${isOnline ? 'online' : 'offline'}`;
        statusElement.textContent = isOnline ?
            '🟢 Connesso' :
            '🔴 Offline - Alcune funzionalità potrebbero non essere disponibili';
        statusElement.style.display = isOnline ? 'none' : 'block';
    }

    if (!isOnline && window.bosstarterApp) {
        window.bosstarterApp.showNotification(
            'Connessione persa. Verifica la tua connessione internet.',
            'warning',
            5000
        );
    }
}

window.addEventListener('online', updateNetworkStatus);
window.addEventListener('offline', updateNetworkStatus);

// ===== KEYBOARD NAVIGATION ENHANCEMENT =====
document.addEventListener('keydown', (e) => {
    // ESC key to close modals
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal-overlay:not(.hidden)');
        if (openModal && window.bosstarterApp) {
            window.bosstarterApp.closeLoginModal();
            window.bosstarterApp.closeRegisterModal();
        }

        // Close dropdowns
        const openDropdowns = document.querySelectorAll('.dropdown:not(.hidden)');
        openDropdowns.forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }

    // Tab trap for modals
    if (e.key === 'Tab') {
        const openModal = document.querySelector('.modal-overlay:not(.hidden)');
        if (openModal) {
            const focusableElements = openModal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );

            if (focusableElements.length > 0) {
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];

                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    }
});

// ===== MEMORY LEAK PREVENTION =====
window.addEventListener('beforeunload', () => {
    // Clean up any running intervals or timeouts
    if (window.bosstarterApp && typeof window.bosstarterApp.cleanup === 'function') {
        window.bosstarterApp.cleanup();
    }

    // Clear event listeners
    window.removeEventListener('online', updateNetworkStatus);
    window.removeEventListener('offline', updateNetworkStatus);
});

// ===== ANALYTICS SETUP =====
(function initAnalytics() {
    // Google Analytics 4 setup (replace with your tracking ID)
    const GA_TRACKING_ID = 'G-XXXXXXXXXX'; // Replace with actual ID

    if (GA_TRACKING_ID !== 'G-XXXXXXXXXX') {
        // Load Google Analytics
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_TRACKING_ID}`;
        document.head.appendChild(script);

        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', GA_TRACKING_ID, {
            page_title: document.title,
            page_location: window.location.href
        });

        // Track user interactions
        document.addEventListener('click', (e) => {
            const element = e.target.closest('[data-analytics-track]');
            if (element) {
                const action = element.dataset.analyticsTrack;
                const category = element.dataset.analyticsCategory || 'User Interaction';
                const label = element.dataset.analyticsLabel || element.textContent.trim();

                gtag('event', action, {
                    event_category: category,
                    event_label: label
                });
            }
        });

        // Make gtag globally available
        window.gtag = gtag;
    }
})();

// ===== THEME PERSISTENCE =====
(function initTheme() {
    const savedTheme = localStorage.getItem('bostarter-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    const theme = savedTheme || (prefersDark ? 'dark' : 'light');

    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    }

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('bostarter-theme')) {
            if (e.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    });
})();

// ===== LAZY LOADING IMAGES =====
(function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
})();

// ===== SCROLL TO TOP FUNCTIONALITY =====
(function initScrollToTop() {
    const scrollButton = document.createElement('button');
    scrollButton.innerHTML = '<i class="ri-arrow-up-line"></i>';
    scrollButton.className = 'fixed bottom-6 right-6 bg-primary text-white w-12 h-12 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 z-50 opacity-0 pointer-events-none';
    scrollButton.setAttribute('aria-label', 'Torna in cima');
    scrollButton.id = 'scroll-to-top';

    document.body.appendChild(scrollButton);

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            scrollButton.classList.remove('opacity-0', 'pointer-events-none');
        } else {
            scrollButton.classList.add('opacity-0', 'pointer-events-none');
        }
    });

    scrollButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
})();

// ===== COOKIE CONSENT =====
(function initCookieConsent() {
    const consentKey = 'bostarter-cookie-consent';

    if (!localStorage.getItem(consentKey)) {
        const banner = document.createElement('div');
        banner.className = 'fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 shadow-lg z-50';
        banner.innerHTML = `
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    🍪 Utilizziamo cookie per migliorare la tua esperienza. 
                    <a href="/privacy" class="text-primary hover:underline">Scopri di più</a>
                </div>
                <div class="flex gap-2">
                    <button id="accept-cookies" class="btn-primary btn-sm">
                        Accetta
                    </button>
                    <button id="decline-cookies" class="btn-outline btn-sm">
                        Rifiuta
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(banner);

        document.getElementById('accept-cookies').addEventListener('click', () => {
            localStorage.setItem(consentKey, 'accepted');
            banner.remove();
        });

        document.getElementById('decline-cookies').addEventListener('click', () => {
            localStorage.setItem(consentKey, 'declined');
            banner.remove();
        });
    }
})();

// ===== FORM VALIDATION HELPERS =====
window.BosstarterValidation = {
    email: (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    password: (password) => {
        return {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };
    },

    username: (username) => {
        return /^[a-zA-Z0-9_]{3,20}$/.test(username);
    },

    url: (url) => {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
};

// ===== UTILITY FUNCTIONS =====
window.BosstarterUtils = {
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

    formatCurrency: (amount, currency = 'EUR') => {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    formatDate: (date, options = {}) => {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        return new Intl.DateTimeFormat('it-IT', { ...defaultOptions, ...options }).format(date);
    },

    generateId: () => {
        return Math.random().toString(36).substr(2, 9);
    },

    copyToClipboard: async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            return successful;
        }
    }
};

// ===== BASIC PAGE INITIALIZATION =====
// Remove no-js class and add js class for progressive enhancement
document.documentElement.classList.remove('no-js');
document.documentElement.classList.add('js');

// Enhanced loading experience
window.addEventListener('load', () => {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.classList.add('fade-out');
        setTimeout(() => {
            loadingOverlay.remove();
        }, 300);
    }
});

// Enhanced error handling
window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    // Optional: Send error to analytics
    if (window.gtag) {
        gtag('event', 'exception', {
            description: event.error.message,
            fatal: false
        });
    }
});

// Enhanced accessibility: focus management for escape key
document.addEventListener('keydown', (event) => {
    // Escape key handling for modals and dropdowns
    if (event.key === 'Escape') {
        // Close any open dropdowns
        document.querySelectorAll('.dropdown:not(.hidden)').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });

        // Reset any expanded buttons
        document.querySelectorAll('[aria-expanded="true"]').forEach(button => {
            button.setAttribute('aria-expanded', 'false');
        });
    }
});

// Enhanced notification system
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notifications-container');
    if (!container) return;

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.setAttribute('role', 'alert');
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="ri-${getNotificationIcon(type)} mr-2" aria-hidden="true"></i>
                <span>${message}</span>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" 
                    class="ml-4 text-gray-400 hover:text-gray-600" 
                    aria-label="Chiudi notifica">
                <i class="ri-close-line" aria-hidden="true"></i>
            </button>
        </div>
    `;

    container.appendChild(notification);

    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'check-circle-line';
        case 'error': return 'error-warning-line';
        case 'warning': return 'alert-line';
        default: return 'information-line';
    }
}

// Enhanced form validation
function enhanceFormValidation() {
    document.querySelectorAll('input[required], textarea[required]').forEach(input => {
        input.addEventListener('blur', () => {
            validateField(input);
        });

        input.addEventListener('input', () => {
            if (input.classList.contains('error')) {
                validateField(input);
            }
        });
    });
}

function validateField(field) {
    const errorElement = field.parentElement.querySelector('.form-error');
    if (errorElement) {
        errorElement.remove();
    }

    if (!field.validity.valid) {
        field.classList.add('error');
        const error = document.createElement('span');
        error.className = 'form-error';
        error.textContent = getFieldErrorMessage(field);
        field.parentElement.appendChild(error);
        return false;
    } else {
        field.classList.remove('error');
        return true;
    }
}

function getFieldErrorMessage(field) {
    if (field.validity.valueMissing) {
        return 'Questo campo è obbligatorio';
    }
    if (field.validity.typeMismatch) {
        return field.type === 'email' ? 'Inserisci un indirizzo email valido' : 'Formato non valido';
    }
    if (field.validity.tooShort) {
        return `Minimo ${field.minLength} caratteri richiesti`;
    }
    if (field.validity.tooLong) {
        return `Massimo ${field.maxLength} caratteri consentiti`;
    }
    return 'Valore non valido';
}

// Enhanced accessibility: announce page changes
function announcePageChange(message) {
    const announcer = document.createElement('div');
    announcer.setAttribute('aria-live', 'polite');
    announcer.setAttribute('aria-atomic', 'true');
    announcer.className = 'sr-only';
    announcer.textContent = message;
    document.body.appendChild(announcer);

    setTimeout(() => {
        document.body.removeChild(announcer);
    }, 1000);
}

// Effetto ripple per i bottoni con classe .ripple
(function () {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.ripple');
        if (!btn) return;
        const rect = btn.getBoundingClientRect();
        const ripple = document.createElement('span');
        const size = Math.max(rect.width, rect.height);
        ripple.className = 'ripple-effect';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
        ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
        btn.appendChild(ripple);
        ripple.addEventListener('animationend', function () {
            ripple.remove();
        });
    }, false);
})();

// Initialize enhanced functionality
document.addEventListener('DOMContentLoaded', () => {
    enhanceFormValidation();

    // Enhanced intersection observer for animations
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-on-scroll');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
    }

    // Service Worker registration for PWA
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/frontend/sw.js')
            .then(registration => {
                console.log('SW registered:', registration);
            })
            .catch(error => {
                console.log('SW registration failed:', error);
            });
    }
});

// Export functions for global use
window.BOSTARTER = {
    showNotification,
    announcePageChange,
    validateField
};

console.log('🚀 BOSTARTER Enhanced features loaded successfully!');
