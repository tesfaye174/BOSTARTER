/**
 * Header Management System for BOSTARTER
 * Handles search, language switching, authentication UI, and navigation
 */

class HeaderManager {
    constructor() {
        this.searchTimeout = null;
        this.isSearching = false;
        this.currentLanguage = 'it';
        this.searchCache = new Map();

        this.init();
    }

    init() {
        this.setupSearchFunctionality();
        this.setupLanguageSelector();
        this.setupAuthButton();
        this.setupThemeToggle();
        this.setupCategoryNavigation();
        this.setupMobileMenu();
        this.loadUserState();
    }

    // ===== SEARCH FUNCTIONALITY =====
    setupSearchFunctionality() {
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');
        const searchResults = document.getElementById('search-results');

        if (!searchInput || !searchBtn || !searchResults) return;

        // Search input events
        searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });

        searchInput.addEventListener('focus', () => {
            this.showSearchSuggestions();
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch(searchInput.value);
            } else if (e.key === 'Escape') {
                this.hideSearchResults();
                searchInput.blur();
            }
        });

        // Search button click
        searchBtn.addEventListener('click', () => {
            this.performSearch(searchInput.value);
        });

        // Close search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.closest('.relative').contains(e.target)) {
                this.hideSearchResults();
            }
        });
    }

    handleSearchInput(query) {
        clearTimeout(this.searchTimeout);

        if (query.length < 2) {
            this.hideSearchResults();
            return;
        }

        this.searchTimeout = setTimeout(() => {
            this.performAutoSearch(query);
        }, 300);
    }

    async performAutoSearch(query) {
        if (this.searchCache.has(query)) {
            this.displaySearchResults(this.searchCache.get(query));
            return;
        }

        this.setSearchLoading(true);

        try {
            const response = await fetch(`/backend/api/search.php?q=${encodeURIComponent(query)}&limit=5`);
            const data = await response.json();

            if (data.success) {
                this.searchCache.set(query, data.results);
                this.displaySearchResults(data.results);
            }
        } catch (error) {
            console.error('Search error:', error);
        } finally {
            this.setSearchLoading(false);
        }
    }

    displaySearchResults(results) {
        const searchResults = document.getElementById('search-results');
        const resultsList = document.getElementById('search-results-list');

        if (!results || results.length === 0) {
            resultsList.innerHTML = '<div class="text-gray-500 text-sm">Nessun risultato trovato</div>';
        } else {
            resultsList.innerHTML = results.map(result => `
                <div class="search-result-item p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer" 
                     onclick="window.location.href='${result.url}'">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                            <i class="ri-${this.getResultIcon(result.type)} text-primary text-sm"></i>
                        </div>
                        <div>
                            <div class="font-medium text-sm">${result.title}</div>
                            <div class="text-xs text-gray-500">${result.category}</div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        this.showSearchResults();
    }

    getResultIcon(type) {
        const icons = {
            project: 'lightbulb-line',
            user: 'user-line',
            category: 'folder-line'
        };
        return icons[type] || 'search-line';
    }

    showSearchResults() {
        const searchResults = document.getElementById('search-results');
        searchResults.classList.remove('hidden');
    }

    hideSearchResults() {
        const searchResults = document.getElementById('search-results');
        searchResults.classList.add('hidden');
    }

    setSearchLoading(loading) {
        const searchBtn = document.getElementById('search-btn');
        const icon = searchBtn.querySelector('i');

        if (loading) {
            icon.className = 'ri-loader-4-line text-xl animate-spin';
        } else {
            icon.className = 'ri-search-line text-xl';
        }
    }

    performSearch(query) {
        if (query.trim()) {
            window.location.href = `/frontend/search.html?q=${encodeURIComponent(query)}`;
        }
    }

    showSearchSuggestions() {
        // Show recent searches or popular searches
        const recentSearches = this.getRecentSearches();
        if (recentSearches.length > 0) {
            this.displaySearchResults(recentSearches);
        }
    }

    getRecentSearches() {
        try {
            return JSON.parse(localStorage.getItem('bostarter-recent-searches')) || [];
        } catch {
            return [];
        }
    }

    // ===== LANGUAGE SELECTOR =====
    setupLanguageSelector() {
        const languageToggle = document.getElementById('language-toggle');
        const languageDropdown = document.getElementById('language-dropdown');

        if (!languageToggle || !languageDropdown) return;

        languageToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown(languageDropdown, languageToggle);
        });

        // Language selection
        languageDropdown.addEventListener('click', (e) => {
            const langItem = e.target.closest('[data-lang]');
            if (langItem) {
                this.changeLanguage(langItem.dataset.lang);
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            this.closeDropdown(languageDropdown, languageToggle);
        });

        // Load saved language
        this.loadLanguage();
    } changeLanguage(lang) {
        this.currentLanguage = lang;
        localStorage.setItem('bostarter-language', lang);

        // Update UI
        const currentFlag = document.getElementById('current-flag');
        const flags = { it: '🇮🇹', en: '🇬🇧' };
        if (currentFlag) {
            currentFlag.textContent = flags[lang];
        }

        // Update active state and check marks
        document.querySelectorAll('[data-lang]').forEach(item => {
            const isActive = item.dataset.lang === lang;
            item.classList.toggle('active', isActive);

            // Update check mark visibility
            const checkMark = item.querySelector('.ri-check-line');
            if (checkMark) {
                checkMark.style.opacity = isActive ? '1' : '0';
            }
        });

        // Close dropdown
        const languageDropdown = document.getElementById('language-dropdown');
        const languageToggle = document.getElementById('language-toggle');
        this.closeDropdown(languageDropdown, languageToggle);

        // Apply language changes (if i18n system exists)
        if (window.i18n) {
            window.i18n.changeLanguage(lang);
        }
    }

    loadLanguage() {
        const savedLang = localStorage.getItem('bostarter-language') || 'it';
        this.changeLanguage(savedLang);
    }

    // ===== AUTH BUTTON =====
    setupAuthButton() {
        const authBtn = document.getElementById('auth-btn');
        const userDropdown = document.getElementById('user-dropdown');

        if (!authBtn) return;

        authBtn.addEventListener('click', (e) => {
            e.stopPropagation();

            // Check if user is logged in
            if (this.isUserLoggedIn()) {
                this.toggleDropdown(userDropdown, authBtn);
            } else {
                this.openLoginModal();
            }
        });

        // Close user dropdown when clicking outside
        document.addEventListener('click', () => {
            if (userDropdown) {
                this.closeDropdown(userDropdown, authBtn);
            }
        });
    }

    isUserLoggedIn() {
        return localStorage.getItem('bostarter-user') !== null ||
            sessionStorage.getItem('bostarter-user') !== null;
    }

    updateAuthUI(user = null) {
        const authBtn = document.getElementById('auth-btn');
        const authIcon = document.getElementById('auth-icon');
        const authText = document.getElementById('auth-text');
        const userDropdown = document.getElementById('user-dropdown');

        if (!authBtn) return;

        if (user) {
            // User is logged in
            authIcon.className = 'ri-user-line mr-2';
            authText.textContent = user.nickname || user.email;
            authBtn.setAttribute('aria-controls', 'user-dropdown');

            if (userDropdown) {
                userDropdown.classList.remove('hidden');
            }
        } else {
            // User is not logged in
            authIcon.className = 'ri-login-box-line mr-2';
            authText.textContent = 'Accedi';
            authBtn.removeAttribute('aria-controls');

            if (userDropdown) {
                userDropdown.classList.add('hidden');
            }
        }
    }

    openLoginModal() {
        const loginModal = document.getElementById('login-modal');
        if (loginModal) {
            this.showModal(loginModal);
        }
    } async handleLogout() {
        try {
            const response = await fetch('/backend/api/login.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Clear user data
                localStorage.removeItem('bostarter-user');
                sessionStorage.removeItem('bostarter-user');

                // Update UI
                this.updateAuthUI();

                // Show success message
                this.showNotification('Logout effettuato con successo', 'success');

                // Redirect to home if on protected page
                if (window.location.pathname.includes('dashboard') ||
                    window.location.pathname.includes('profile')) {
                    setTimeout(() => {
                        window.location.href = '/index.html';
                    }, 1000);
                }
            }
        } catch (error) {
            console.error('Logout error:', error);
            this.showNotification('Errore durante il logout', 'error');
        }
    }

    // ===== THEME TOGGLE =====
    setupThemeToggle() {
        const themeToggle = document.getElementById('theme-toggle');

        if (!themeToggle) return;

        themeToggle.addEventListener('click', () => {
            this.toggleTheme();
        });

        // Load saved theme
        this.loadTheme();
    }

    toggleTheme() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');

        if (isDark) {
            html.classList.remove('dark');
            localStorage.setItem('bostarter-theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('bostarter-theme', 'dark');
        }
    }

    loadTheme() {
        const savedTheme = localStorage.getItem('bostarter-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    }

    // ===== CATEGORY NAVIGATION =====
    setupCategoryNavigation() {
        const categoryLinks = document.querySelectorAll('nav[aria-label="Categorie principali"] a');

        categoryLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Track category click
                this.trackEvent('category_click', {
                    category: link.textContent.trim(),
                    url: link.href
                });
            });
        });
    }

    // ===== MOBILE MENU =====
    setupMobileMenu() {
        // Implementation for mobile hamburger menu if needed
        // This would handle responsive navigation for mobile devices
    }

    // ===== USER STATE =====
    async loadUserState() {
        try {
            const response = await fetch('/backend/api/login.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success && data.authenticated) {
                this.updateAuthUI(data.user);
                localStorage.setItem('bostarter-user', JSON.stringify(data.user));
            } else {
                this.updateAuthUI();
                localStorage.removeItem('bostarter-user');
            }
        } catch (error) {
            console.error('Auth check error:', error);
        }
    }

    // ===== UTILITY METHODS =====
    toggleDropdown(dropdown, toggle) {
        const isOpen = !dropdown.classList.contains('hidden');

        if (isOpen) {
            this.closeDropdown(dropdown, toggle);
        } else {
            this.openDropdown(dropdown, toggle);
        }
    } openDropdown(dropdown, toggle) {
        dropdown.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');

        // Trigger animation
        setTimeout(() => {
            dropdown.classList.remove('opacity-0', 'translate-y-2');
            dropdown.classList.add('opacity-100', 'translate-y-0');
        }, 10);
    }

    closeDropdown(dropdown, toggle) {
        dropdown.classList.remove('opacity-100', 'translate-y-0');
        dropdown.classList.add('opacity-0', 'translate-y-2');
        toggle.setAttribute('aria-expanded', 'false');

        // Hide after animation
        setTimeout(() => {
            dropdown.classList.add('hidden');
        }, 200);
    }

    showModal(modal) {
        modal.classList.remove('hidden');
        modal.classList.remove('opacity-0');
        modal.classList.add('opacity-100');

        const content = modal.querySelector('[id$="-modal-content"]');
        if (content) {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }

        document.body.style.overflow = 'hidden';
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('notifications-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification notification-${type} opacity-0 transform translate-x-full transition-all duration-300`;

        const icons = {
            success: 'ri-check-circle-line',
            error: 'ri-error-warning-line',
            warning: 'ri-alert-line',
            info: 'ri-information-line'
        };

        notification.innerHTML = `
            <div class="flex items-center gap-3 p-4 rounded-lg shadow-lg bg-white dark:bg-gray-800 border-l-4 border-${type === 'success' ? 'green' : type === 'error' ? 'red' : type === 'warning' ? 'yellow' : 'blue'}-500">
                <i class="${icons[type]} text-${type === 'success' ? 'green' : type === 'error' ? 'red' : type === 'warning' ? 'yellow' : 'blue'}-500"></i>
                <span class="text-gray-900 dark:text-white">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-500">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        `;

        container.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('opacity-0', 'translate-x-full');
        }, 100);

        // Auto remove
        setTimeout(() => {
            notification.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    }

    trackEvent(eventName, data = {}) {
        // Analytics tracking
        if (window.gtag) {
            window.gtag('event', eventName, data);
        }

        // Custom tracking
        console.log('Event tracked:', eventName, data);
    }
}

// Global logout function for dropdown onclick
window.handleLogout = function () {
    if (window.headerManager) {
        window.headerManager.handleLogout();
    }
};

// Initialize header manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.headerManager = new HeaderManager();
});

export default HeaderManager;
