// Configurazione
const config = {
    apiUrl: 'http://localhost:8080/api',
    theme: {
        colors: {
            primary: '#3176FF',
            secondary: '#FF6B35',
            success: '#10B981',
            warning: '#F59E0B',
            error: '#EF4444'
        }
    },
    searchDelay: 300,
    notificationDuration: 5000,
    scrollThreshold: 100,
    themeKey: 'bostarter-theme',
    languageKey: 'bostarter-language',
    currencyKey: 'bostarter-currency'
};

// Stato dell'applicazione
const appState = {
    user: null,
    isAuthenticated: false,
    notifications: [],
    currentPage: 1,
    isLoading: false
};

// Utility functions
const utils = {
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
    },

    formatCurrency(amount) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    },

    formatDate(date) {
        return new Intl.DateTimeFormat('it-IT', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    }
};

// UI Management
const UI = {
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} animate-fade-in`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="ri-${this.getNotificationIcon(type)}-line"></i>
                <p>${message}</p>
            </div>
            <button class="notification-close" aria-label="Chiudi notifica">
                <i class="ri-close-line"></i>
            </button>
        `;
        
        document.getElementById('notifications-container').appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('notification-hide');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    },

    getNotificationIcon(type) {
        const icons = {
            success: 'check-double',
            error: 'error-warning',
            warning: 'alert',
            info: 'information'
        };
        return icons[type] || 'information';
    },

    toggleModal(modalId, show = true) {
        const modal = document.getElementById(modalId);
        if (show) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    },

    updateLoadingState(isLoading) {
        appState.isLoading = isLoading;
        document.body.classList.toggle('loading', isLoading);
    }
};

// Authentication
const auth = {
    async login(email, password) {
        try {
            UI.updateLoadingState(true);
            const response = await fetch(`${config.apiUrl}/auth/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            if (!response.ok) throw new Error('Credenziali non valide');

            const data = await response.json();
            this.setUser(data.user);
            UI.showNotification('Login effettuato con successo', 'success');
            return true;
        } catch (error) {
            UI.showNotification(error.message, 'error');
            return false;
        } finally {
            UI.updateLoadingState(false);
        }
    },

    async register(userData) {
        try {
            UI.updateLoadingState(true);
            const response = await fetch(`${config.apiUrl}/auth/register`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(userData)
            });

            if (!response.ok) throw new Error('Errore durante la registrazione');

            const data = await response.json();
            this.setUser(data.user);
            UI.showNotification('Registrazione completata con successo', 'success');
            return true;
        } catch (error) {
            UI.showNotification(error.message, 'error');
            return false;
        } finally {
            UI.updateLoadingState(false);
        }
    },

    logout() {
        localStorage.removeItem('user');
        appState.user = null;
        appState.isAuthenticated = false;
        window.location.href = '/';
    },

    setUser(user) {
        appState.user = user;
        appState.isAuthenticated = true;
        localStorage.setItem('user', JSON.stringify(user));
    },

    checkAuth() {
        const user = localStorage.getItem('user');
        if (user) {
            this.setUser(JSON.parse(user));
        }
    }
};

// Project Management
const projects = {
    async getFeaturedProjects() {
        try {
            UI.updateLoadingState(true);
            const response = await fetch(`${config.apiUrl}/projects/featured`);
            if (!response.ok) throw new Error('Errore nel caricamento dei progetti');
            
            const data = await response.json();
            return data;
        } catch (error) {
            UI.showNotification(error.message, 'error');
            return [];
        } finally {
            UI.updateLoadingState(false);
        }
    },

    async searchProjects(query) {
        try {
            UI.updateLoadingState(true);
            const response = await fetch(`${config.apiUrl}/projects/search?q=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('Errore nella ricerca dei progetti');
            
            const data = await response.json();
            return data;
        } catch (error) {
            UI.showNotification(error.message, 'error');
            return [];
        } finally {
            UI.updateLoadingState(false);
        }
    }
};

// Gestione del tema
class ThemeManager {
    constructor() {
        this.themeToggle = document.getElementById('theme-toggle');
        this.init();
    }

    init() {
        this.loadTheme();
        this.themeToggle?.addEventListener('click', () => this.toggleTheme());
    }

    loadTheme() {
        const savedTheme = localStorage.getItem(config.themeKey);
        if (savedTheme) {
            document.documentElement.classList.toggle('dark', savedTheme === 'dark');
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    }

    toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem(config.themeKey, isDark ? 'dark' : 'light');
        this.showNotification('Tema cambiato', 'success');
    }
}

// Gestione delle animazioni
const animationManager = {
    init() {
        this.observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            },
            { threshold: 0.1 }
        );

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            this.observer.observe(el);
        });
    }
};

// Gestione dei modali
const modalManager = {
    init() {
        this.setupModals();
        this.setupCloseButtons();
    },

    setupModals() {
        const modals = document.querySelectorAll('[data-modal]');
        modals.forEach(modal => {
            const trigger = document.querySelector(`[data-modal-trigger="${modal.id}"]`);
            if (trigger) {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.openModal(modal.id);
                });
            }
        });
    },

    setupCloseButtons() {
        document.querySelectorAll('[data-modal-close]').forEach(button => {
            button.addEventListener('click', () => {
                const modalId = button.closest('[data-modal]').id;
                this.closeModal(modalId);
            });
        });
    },

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            this.setupModalFocus(modal);
        }
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    },

    setupModalFocus(modal) {
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            } else if (e.key === 'Escape') {
                this.closeModal(modal.id);
            }
        });

        firstFocusable.focus();
    }
};

// Gestione delle notifiche
const notificationManager = {
    init() {
        this.container = document.getElementById('notifications-container');
        if (!this.container) return;

        this.setupNotifications();
    },

    setupNotifications() {
        window.addEventListener('notification', (e) => {
            this.showNotification(e.detail);
        });
    },

    showNotification({ type = 'info', message, duration = 5000 }) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} animate-slide-in`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="ri-${this.getIcon(type)}-line"></i>
                <p>${message}</p>
            </div>
            <button class="notification-close" aria-label="Chiudi notifica">
                <i class="ri-close-line"></i>
            </button>
        `;

        this.container.appendChild(notification);

        const closeButton = notification.querySelector('.notification-close');
        closeButton.addEventListener('click', () => {
            this.removeNotification(notification);
        });

        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }
    },

    removeNotification(notification) {
        notification.classList.add('animate-fade-out');
        notification.addEventListener('animationend', () => {
            notification.remove();
        });
    },

    getIcon(type) {
        const icons = {
            success: 'check-double',
            error: 'error-warning',
            warning: 'alert',
            info: 'information'
        };
        return icons[type] || 'information';
    }
};

// Gestione della ricerca
class SearchManager {
    constructor() {
        this.searchInput = document.querySelector('.header-search input');
        this.searchResults = document.querySelector('.header-search-results');
        this.searchTimeout = null;
        this.init();
    }

    init() {
        this.searchInput?.addEventListener('input', (e) => this.handleSearch(e.target.value));
        document.addEventListener('click', (e) => this.handleClickOutside(e));
    }

    handleSearch(query) {
        clearTimeout(this.searchTimeout);
        if (query.length < 2) {
            this.hideResults();
            return;
        }

        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, config.searchDelay);
    }

    async performSearch(query) {
        try {
            // Simulazione di una chiamata API
            const results = await this.mockSearchAPI(query);
            this.displayResults(results);
        } catch (error) {
            console.error('Errore nella ricerca:', error);
            this.showNotification('Errore durante la ricerca', 'error');
        }
    }

    displayResults(results) {
        if (!this.searchResults) return;

        this.searchResults.innerHTML = results.map(result => `
            <a href="${result.url}" class="block p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <div class="flex items-center gap-2">
                    <i class="${result.icon} text-primary"></i>
                    <span>${result.title}</span>
                </div>
            </a>
        `).join('');

        this.searchResults.classList.add('active');
    }

    hideResults() {
        this.searchResults?.classList.remove('active');
    }

    handleClickOutside(event) {
        if (!this.searchInput?.contains(event.target) && !this.searchResults?.contains(event.target)) {
            this.hideResults();
        }
    }

    // Simulazione API
    async mockSearchAPI(query) {
        return new Promise(resolve => {
            setTimeout(() => {
                resolve([
                    { title: 'Progetto 1', url: '#', icon: 'ri-projector-line' },
                    { title: 'Progetto 2', url: '#', icon: 'ri-projector-line' },
                    { title: 'Progetto 3', url: '#', icon: 'ri-projector-line' }
                ]);
            }, 300);
        });
    }
}

// Sistema di notifiche
class NotificationSystem {
    constructor() {
        this.container = document.getElementById('notifications-container');
        this.init();
    }

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'notifications-container';
            this.container.className = 'fixed top-0 right-0 p-4 z-50 space-y-2';
            document.body.appendChild(this.container);
        }
    }

    show(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} animate-slide-in`;
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="${this.getIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;

        this.container.appendChild(notification);
        notification.classList.add('show');

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, config.notificationDuration);
    }

    getIcon(type) {
        const icons = {
            success: 'ri-checkbox-circle-line',
            error: 'ri-error-warning-line',
            warning: 'ri-alert-line',
            info: 'ri-information-line'
        };
        return icons[type] || icons.info;
    }
}

// Gestione della navigazione orizzontale
class HorizontalNav {
    constructor() {
        this.nav = document.querySelector('.header-nav');
        this.init();
    }

    init() {
        if (!this.nav) return;

        let isDown = false;
        let startX;
        let scrollLeft;

        this.nav.addEventListener('mousedown', (e) => {
            isDown = true;
            this.nav.classList.add('active');
            startX = e.pageX - this.nav.offsetLeft;
            scrollLeft = this.nav.scrollLeft;
        });

        this.nav.addEventListener('mouseleave', () => {
            isDown = false;
            this.nav.classList.remove('active');
        });

        this.nav.addEventListener('mouseup', () => {
            isDown = false;
            this.nav.classList.remove('active');
        });

        this.nav.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - this.nav.offsetLeft;
            const walk = (x - startX) * 2;
            this.nav.scrollLeft = scrollLeft - walk;
        });
    }
}

// Gestione della newsletter
class NewsletterManager {
    constructor() {
        this.form = document.querySelector('.footer-newsletter form');
        this.init();
    }

    init() {
        this.form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async handleSubmit(e) {
        e.preventDefault();
        const email = this.form.querySelector('input[type="email"]').value;
        const consent = this.form.querySelector('input[type="checkbox"]').checked;

        if (!consent) {
            notifications.show('Devi accettare le condizioni per la newsletter', 'warning');
            return;
        }

        try {
            // Simulazione invio
            await this.mockSubmit(email);
            notifications.show('Iscrizione alla newsletter completata!', 'success');
            this.form.reset();
        } catch (error) {
            notifications.show('Errore durante l\'iscrizione', 'error');
        }
    }

    async mockSubmit(email) {
        return new Promise(resolve => setTimeout(resolve, 1000));
    }
}

// Gestione delle lingue e valute
class LocalizationManager {
    constructor() {
        this.languageSelect = document.querySelector('select[aria-label="Seleziona lingua"]');
        this.currencySelect = document.querySelector('select[aria-label="Seleziona valuta"]');
        this.init();
    }

    init() {
        this.loadPreferences();
        this.languageSelect?.addEventListener('change', (e) => this.handleLanguageChange(e));
        this.currencySelect?.addEventListener('change', (e) => this.handleCurrencyChange(e));
    }

    loadPreferences() {
        const savedLanguage = localStorage.getItem(config.languageKey);
        const savedCurrency = localStorage.getItem(config.currencyKey);

        if (savedLanguage) this.languageSelect.value = savedLanguage;
        if (savedCurrency) this.currencySelect.value = savedCurrency;
    }

    handleLanguageChange(event) {
        const language = event.target.value;
        localStorage.setItem(config.languageKey, language);
        notifications.show(`Lingua cambiata in ${language}`, 'success');
    }

    handleCurrencyChange(event) {
        const currency = event.target.value;
        localStorage.setItem(config.currencyKey, currency);
        notifications.show(`Valuta cambiata in ${currency}`, 'success');
    }
}

// Gestione dello scroll progress
class ScrollProgress {
    constructor() {
        this.progressBar = document.createElement('div');
        this.init();
    }

    init() {
        this.progressBar.className = 'fixed top-0 left-0 h-1 bg-primary z-50 transition-all duration-200';
        document.body.appendChild(this.progressBar);

        window.addEventListener('scroll', () => this.updateProgress());
        this.updateProgress();
    }

    updateProgress() {
        const winScroll = document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        this.progressBar.style.width = `${scrolled}%`;
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    // Check authentication status
    auth.checkAuth();

    // Setup search functionality
    const searchInput = document.getElementById('search-projects');
    if (searchInput) {
        searchInput.addEventListener('input', utils.debounce(async (e) => {
            const query = e.target.value;
            if (query.length < 2) return;

            const results = await projects.searchProjects(query);
            // Update UI with results
        }, 300));
    }

    // Setup theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            themeToggle.classList.add('theme-toggle-spin');
            setTimeout(() => themeToggle.classList.remove('theme-toggle-spin'), 500);
            
            // Save preference
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
    }

    // Setup mobile menu
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            const nav = document.querySelector('nav');
            nav.classList.toggle('hidden');
        });
    }

    themeManager.init();
    animationManager.init();
    modalManager.init();
    notificationManager.init();
    searchManager.init();

    // Header Functionality
    // Search Functionality
    const searchInputHeader = document.querySelector('.header-search input');
    const searchResultsHeader = document.querySelector('.header-search-results');
    
    if (searchInputHeader && searchResultsHeader) {
        let searchTimeoutHeader;
        
        searchInputHeader.addEventListener('input', (e) => {
            clearTimeout(searchTimeoutHeader);
            const queryHeader = e.target.value.trim();
            
            if (queryHeader.length >= 2) {
                searchTimeoutHeader = setTimeout(() => {
                    // Simulate search results
                    searchResultsHeader.classList.add('active', 'search-expand');
                    // TODO: Implement actual search
                }, 300);
            } else {
                searchResultsHeader.classList.remove('active');
            }
        });

        // Close search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInputHeader.contains(e.target) && !searchResultsHeader.contains(e.target)) {
                searchResultsHeader.classList.remove('active');
            }
        });
    }

    // Navigation Scroll
    const navHeader = document.querySelector('.header-nav');
    if (navHeader) {
        let isScrollingHeader = false;
        let startXHeader;
        let scrollLeftHeader;

        navHeader.addEventListener('mousedown', (e) => {
            isScrollingHeader = true;
            startXHeader = e.pageX - navHeader.offsetLeft;
            scrollLeftHeader = navHeader.scrollLeft;
        });

        navHeader.addEventListener('mouseleave', () => {
            isScrollingHeader = false;
        });

        navHeader.addEventListener('mouseup', () => {
            isScrollingHeader = false;
        });

        navHeader.addEventListener('mousemove', (e) => {
            if (!isScrollingHeader) return;
            e.preventDefault();
            const xHeader = e.pageX - navHeader.offsetLeft;
            const walkHeader = (xHeader - startXHeader) * 2;
            navHeader.scrollLeft = scrollLeftHeader - walkHeader;
        });
    }

    // Newsletter Form
    const newsletterForm = document.querySelector('.footer-newsletter form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = newsletterForm.querySelector('input[type="email"]').value;
            const consent = newsletterForm.querySelector('#newsletter-consent').checked;

            if (!consent) {
                showNotification('Per favore accetta i termini per la newsletter', 'error');
                return;
            }

            try {
                // TODO: Implement actual newsletter subscription
                showNotification('Iscrizione alla newsletter completata!', 'success');
                newsletterForm.classList.add('newsletter-success');
                setTimeout(() => newsletterForm.classList.remove('newsletter-success'), 500);
                newsletterForm.reset();
            } catch (error) {
                showNotification('Errore durante l\'iscrizione', 'error');
            }
        });
    }

    // Language and Currency Selectors
    const languageSelect = document.querySelector('select[aria-label="Seleziona lingua"]');
    const currencySelect = document.querySelector('select[aria-label="Seleziona valuta"]');

    if (languageSelect) {
        languageSelect.addEventListener('change', (e) => {
            // TODO: Implement language change
            showNotification(`Lingua cambiata in ${e.target.value}`, 'info');
        });
    }

    if (currencySelect) {
        currencySelect.addEventListener('change', (e) => {
            // TODO: Implement currency change
            showNotification(`Valuta cambiata in ${e.target.value}`, 'info');
        });
    }

    const themeManager = new ThemeManager();
    const notifications = new NotificationSystem();
    const horizontalNav = new HorizontalNav();
    const newsletterManager = new NewsletterManager();
    const localizationManager = new LocalizationManager();
    const scrollProgress = new ScrollProgress();
});

// Notification System
function showNotification(message, type = 'info') {
    const container = document.getElementById('notifications-container');
    if (!container) return;

    const notification = document.createElement('div');
    notification.className = `notification ${type} p-4 rounded-lg shadow-lg transform transition-all duration-300`;
    
    const icon = {
        success: 'ri-checkbox-circle-fill',
        error: 'ri-error-warning-fill',
        warning: 'ri-alert-fill',
        info: 'ri-information-fill'
    }[type];

    notification.innerHTML = `
        <div class="flex items-center">
            <i class="${icon} mr-2 text-xl"></i>
            <p>${message}</p>
        </div>
    `;

    container.appendChild(notification);
    notification.classList.add('animate-slide-up');

    setTimeout(() => {
        notification.classList.add('opacity-0');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Scroll Progress
window.addEventListener('scroll', () => {
    const scrollProgress = document.querySelector('.scroll-progress');
    if (scrollProgress) {
        const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (window.scrollY / windowHeight) * 100;
        scrollProgress.style.width = `${scrolled}%`;
    }
});

// Initialize theme from localStorage
const savedTheme = localStorage.getItem('theme');
if (savedTheme === 'dark') {
    document.documentElement.classList.add('dark');
}

// Export modules
export { config, appState, utils, UI, auth, projects, themeManager, animationManager, modalManager, notificationManager, searchManager };

// Gestione del menu mobile
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('show');
        });
    }
    
    // Chiudi il menu mobile quando si clicca fuori
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.nav-container')) {
            navLinks.classList.remove('show');
        }
    });
});

// Animazione della barra di progresso
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
}

// Lazy loading delle immagini
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback per browser che non supportano IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
});

// Gestione dei form
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!validateForm(form)) {
                event.preventDefault();
            }
        });
    });
});

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            showError(input, 'Questo campo è obbligatorio');
        } else {
            clearError(input);
        }
    });
    
    return isValid;
}

function showError(input, message) {
    const formGroup = input.closest('.form-group');
    if (formGroup) {
        const errorElement = formGroup.querySelector('.error-message') || document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        
        if (!formGroup.querySelector('.error-message')) {
            formGroup.appendChild(errorElement);
        }
        
        input.classList.add('error');
    }
}

function clearError(input) {
    const formGroup = input.closest('.form-group');
    if (formGroup) {
        const errorElement = formGroup.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
        input.classList.remove('error');
    }
}

// Gestione del caricamento delle pagine
window.addEventListener('load', function() {
    document.body.classList.add('loaded');
});

// Gestione dello scroll
let lastScroll = 0;
const header = document.querySelector('header');

window.addEventListener('scroll', function() {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll <= 0) {
        header.classList.remove('scroll-up');
        return;
    }
    
    if (currentScroll > lastScroll && !header.classList.contains('scroll-down')) {
        // Scroll down
        header.classList.remove('scroll-up');
        header.classList.add('scroll-down');
    } else if (currentScroll < lastScroll && header.classList.contains('scroll-down')) {
        // Scroll up
        header.classList.remove('scroll-down');
        header.classList.add('scroll-up');
    }
    
    lastScroll = currentScroll;
});