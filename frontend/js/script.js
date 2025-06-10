/**
 * BOSTARTER - Script Comune per tutte le pagine
 * Contiene funzionalità condivise e utilità globali
 * Include gestione notifiche, tema, prestazioni e accessibilità
 */

'use strict';

// Namespace globale per BOSTARTER
window.BOSTARTER = window.BOSTARTER || {};

// Utilità globali condivise
BOSTARTER.Utils = {
    /**
     * Formatta un importo in euro
     */
    formattaImporto: function (importo) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(importo);
    },

    /**
     * Formatta una data in italiano
     */
    formattaData: function (dataString, opzioni = {}) {
        const defaultOpzioni = {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        };
        const opzioniFinali = { ...defaultOpzioni, ...opzioni };

        const data = new Date(dataString);
        return data.toLocaleDateString('it-IT', opzioniFinali);
    },

    /**
     * Debounce per funzioni che vengono chiamate frequentemente
     */
    debounce: function (func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;

            const later = function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };

            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);

            if (callNow) func.apply(context, args);
        };
    },

    /**
     * Throttle per limitare la frequenza di esecuzione
     */
    throttle: function (func, limit) {
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
     * Escape HTML per prevenire XSS
     */
    escapeHtml: function (text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    },

    /**
     * Copia testo negli appunti
     */
    copiaNeglAppunti: async function (testo) {
        try {
            await navigator.clipboard.writeText(testo);
            return true;
        } catch (err) {
            // Fallback per browser più vecchi
            const textArea = document.createElement('textarea');
            textArea.value = testo;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                return true;
            } catch (err) {
                return false;
            } finally {
                document.body.removeChild(textArea);
            }
        }
    },

    /**
     * Genera un ID univoco
     */
    generaId: function () {
        return 'id_' + Math.random().toString(36).substr(2, 9);
    }
};

// Sistema di notifiche toast globale
BOSTARTER.Toast = {
    container: null,

    init: function () {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);

            // Aggiungiamo gli stili se non presenti
            this.aggiungiStili();
        }
    },

    mostra: function (messaggio, tipo = 'info', durata = 5000) {
        this.init();

        const toast = document.createElement('div');
        toast.className = `toast toast-${tipo}`;
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${this.ottieniIcona(tipo)}</span>
                <span class="toast-message">${BOSTARTER.Utils.escapeHtml(messaggio)}</span>
                <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        this.container.appendChild(toast);

        // Animazione di entrata
        setTimeout(() => toast.classList.add('toast-show'), 100);

        // Rimozione automatica
        if (durata > 0) {
            setTimeout(() => {
                toast.classList.remove('toast-show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, durata);
        }

        return toast;
    },

    successo: function (messaggio, durata = 5000) {
        return this.mostra(messaggio, 'success', durata);
    },

    errore: function (messaggio, durata = 7000) {
        return this.mostra(messaggio, 'error', durata);
    },

    avviso: function (messaggio, durata = 5000) {
        return this.mostra(messaggio, 'warning', durata);
    },

    info: function (messaggio, durata = 5000) {
        return this.mostra(messaggio, 'info', durata);
    },

    ottieniIcona: function (tipo) {
        const icone = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
        };
        return icone[tipo] || icone.info;
    },

    aggiungiStili: function () {
        if (document.getElementById('toast-styles')) return;

        const stili = document.createElement('style');
        stili.id = 'toast-styles';
        stili.textContent = `
            .toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                pointer-events: none;
            }
            
            .toast {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                margin-bottom: 10px;
                max-width: 400px;
                opacity: 0;
                pointer-events: auto;
                transform: translateX(100%);
                transition: all 0.3s ease;
            }
            
            .toast-show {
                opacity: 1;
                transform: translateX(0);
            }
            
            .toast-content {
                display: flex;
                align-items: center;
                padding: 12px 16px;
            }
            
            .toast-icon {
                margin-right: 10px;
                font-size: 16px;
            }
            
            .toast-message {
                flex: 1;
                font-size: 14px;
            }
            
            .toast-close {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                margin-left: 10px;
                opacity: 0.7;
            }
            
            .toast-close:hover {
                opacity: 1;
            }
            
            .toast-success {
                border-left: 4px solid #28a745;
            }
            
            .toast-error {
                border-left: 4px solid #dc3545;
            }
            
            .toast-warning {
                border-left: 4px solid #ffc107;
            }
            
            .toast-info {
                border-left: 4px solid #17a2b8;
            }
        `;
        document.head.appendChild(stili);
    }
};

// Sistema di gestione tema scuro/chiaro
BOSTARTER.Theme = {
    init: function () {
        const savedTheme = localStorage.getItem('bostarter-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        const theme = savedTheme || (prefersDark ? 'dark' : 'light');
        this.applica(theme);

        // Listener per cambi di tema del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('bostarter-theme')) {
                this.applica(e.matches ? 'dark' : 'light');
            }
        });
    },

    applica: function (theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('bostarter-theme', theme);

        // Aggiorniamo l'icona del toggle se presente
        const toggleIcon = document.querySelector('.theme-toggle-icon');
        if (toggleIcon) {
            toggleIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
    },

    toggle: function () {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.applica(newTheme);
    }
};

// Sistema di tracking prestazioni
BOSTARTER.Performance = {
    metriche: {},

    init: function () {
        // Tracciamo le Core Web Vitals se supportate
        if ('PerformanceObserver' in window) {
            this.trackWebVitals();
        }

        // Tracciamo il tempo di caricamento della pagina
        window.addEventListener('load', () => {
            this.trackPageLoad();
        });
    },

    trackWebVitals: function () {
        // Largest Contentful Paint
        new PerformanceObserver((entryList) => {
            const entries = entryList.getEntries();
            const lastEntry = entries[entries.length - 1];
            this.metriche.lcp = lastEntry.startTime;
        }).observe({ entryTypes: ['largest-contentful-paint'] });

        // First Input Delay
        new PerformanceObserver((entryList) => {
            const firstInput = entryList.getEntries()[0];
            this.metriche.fid = firstInput.processingStart - firstInput.startTime;
        }).observe({ entryTypes: ['first-input'], buffered: true });
    },

    trackPageLoad: function () {
        const navigation = performance.getEntriesByType('navigation')[0];
        this.metriche.pageLoad = navigation.loadEventEnd - navigation.fetchStart;

        // Log delle prestazioni in sviluppo
        if (window.location.hostname === 'localhost') {
            console.log('📊 Metriche prestazioni BOSTARTER:', this.metriche);
        }
    }
};

// Sistema di accessibilità
BOSTARTER.Accessibility = {
    init: function () {
        this.setupKeyboardNavigation();
        this.setupFocusManagement();
        this.setupSkipLinks();
    },

    setupKeyboardNavigation: function () {
        // Miglioriamo la navigazione da tastiera
        document.addEventListener('keydown', (e) => {
            // ESC per chiudere modal e dropdown
            if (e.key === 'Escape') {
                const modal = document.querySelector('.modal.show');
                if (modal) {
                    modal.querySelector('.btn-close, .close')?.click();
                }

                const dropdown = document.querySelector('.dropdown-menu.show');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            }
        });
    },

    setupFocusManagement: function () {
        // Gestiamo il focus per elementi interattivi
        const elementiInterattivi = 'a, button, input, textarea, select, details, [tabindex]:not([tabindex="-1"])';

        document.addEventListener('focusin', (e) => {
            if (e.target.matches(elementiInterattivi)) {
                e.target.classList.add('focus-visible');
            }
        });

        document.addEventListener('focusout', (e) => {
            e.target.classList.remove('focus-visible');
        });
    },

    setupSkipLinks: function () {
        // Aggiungiamo skip links se non presenti
        if (!document.querySelector('.skip-link')) {
            const skipLink = document.createElement('a');
            skipLink.href = '#main-content';
            skipLink.className = 'skip-link';
            skipLink.textContent = 'Salta al contenuto principale';
            skipLink.style.cssText = `
                position: absolute;
                top: -40px;
                left: 6px;
                background: #000;
                color: #fff;
                padding: 8px;
                text-decoration: none;
                z-index: 10000;
                border-radius: 4px;
            `;

            skipLink.addEventListener('focus', () => {
                skipLink.style.top = '6px';
            });

            skipLink.addEventListener('blur', () => {
                skipLink.style.top = '-40px';
            });

            document.body.insertBefore(skipLink, document.body.firstChild);
        }
    }
};

// Sistema di gestione errori globale
BOSTARTER.ErrorHandler = {
    init: function () {
        window.addEventListener('error', (e) => {
            this.logError(e.error, e.filename, e.lineno);
        });

        window.addEventListener('unhandledrejection', (e) => {
            this.logError(e.reason, 'Promise rejection');
            e.preventDefault();
        });
    },

    logError: function (error, source, line) {
        // In sviluppo, mostriamo l'errore
        if (window.location.hostname === 'localhost') {
            console.error('🚨 Errore BOSTARTER:', {
                error: error,
                source: source,
                line: line,
                timestamp: new Date().toISOString()
            });
        }

        // In produzione, potresti inviare l'errore a un servizio di logging
        // this.sendErrorToService(error, source, line);
    }
};

// Inizializzazione di tutti i sistemi comuni
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Inizializzazione sistemi comuni BOSTARTER');

    // Inizializziamo tutti i sistemi
    BOSTARTER.Theme.init();
    BOSTARTER.Performance.init();
    BOSTARTER.Accessibility.init();
    BOSTARTER.ErrorHandler.init();

    // Setup toggle tema se presente
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            BOSTARTER.Theme.toggle();
        });
    }

    // Setup conferme per azioni pericolose
    document.addEventListener('click', (e) => {
        if (e.target.matches('[data-confirm]')) {
            const messaggio = e.target.dataset.confirm;
            if (!confirm(messaggio)) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    });

    console.log('✅ Sistemi comuni BOSTARTER inizializzati');
});

// Esportiamo nel namespace globale per compatibilità
window.BOSTARTER = BOSTARTER;
