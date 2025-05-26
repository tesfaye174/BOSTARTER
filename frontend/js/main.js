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
    }
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
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
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
});

// Export modules
export { config, appState, utils, UI, auth, projects };