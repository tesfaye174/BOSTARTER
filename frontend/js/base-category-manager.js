/**
 * Base Category Manager Class
 * Consolidates common functionality across all category page managers
 * Eliminates duplicate code patterns and provides unified interface
 */

class BaseCategoryManager {
    constructor(categoryName = 'generic') {
        this.categoryName = categoryName;
        this.projects = [];
        this.filteredProjects = [];
        this.currentFilter = 'all';
        this.currentSort = 'newest';
        this.searchTerm = '';
        this.isLoading = false;
        this.projectsPerPage = 9;
        this.currentPage = 1;

        // Category-specific data (can be overridden by subclasses)
        this.artists = [];
        this.technologies = [];
        this.categories = [];

        this.init();
    }

    /**
     * Initialize all common functionality
     */
    init() {
        this.setupProgressiveEnhancement();
        this.setupThemeSystem();
        this.setupNavigation();
        this.setupFilters();
        this.setupSearch();
        this.setupLoadingSystem();
        this.setupAccessibility();
        this.setupPerformanceMonitoring();

        // Load data and animate counters
        this.loadProjects();
        this.loadCategorySpecificData();
        this.animateCounters();
    }

    /**
     * Progressive Enhancement Setup
     */
    setupProgressiveEnhancement() {
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    }

    /**
     * Unified Theme System using centralized ThemeManager
     */
    setupThemeSystem() {
        // Check if ThemeManager exists globally
        if (typeof window.ThemeManager !== 'undefined') {
            window.ThemeManager.init();
            return;
        }

        // Fallback to local theme setup if ThemeManager not available
        const themeToggle = document.getElementById('themeToggle');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

        const savedTheme = localStorage.getItem('theme');
        const systemTheme = prefersDark.matches ? 'dark' : 'light';
        const initialTheme = savedTheme || systemTheme;

        this.setTheme(initialTheme);

        themeToggle?.addEventListener('click', () => {
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            this.setTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });

        // Listen for system theme changes
        prefersDark.addListener((e) => {
            if (!localStorage.getItem('theme')) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    /**
     * Set theme with smooth transition
     */
    setTheme(theme) {
        document.documentElement.classList.toggle('dark', theme === 'dark');

        // Update theme toggle icon if it exists
        const themeToggle = document.getElementById('themeToggle');
        const icon = themeToggle?.querySelector('i');
        if (icon) {
            icon.className = theme === 'dark' ? 'ri-sun-line' : 'ri-moon-line';
        }

        // Announce theme change to screen readers
        this.announceToScreenReader(`Tema cambiato in modalità ${theme === 'dark' ? 'scura' : 'chiara'}`);
    }

    /**
     * Navigation and Mobile Menu Setup
     */
    setupNavigation() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenu = document.getElementById('mobileMenu');

        mobileMenuToggle?.addEventListener('click', () => {
            const isExpanded = mobileMenuToggle.getAttribute('aria-expanded') === 'true';

            mobileMenu.classList.toggle('hidden');
            mobileMenuToggle.setAttribute('aria-expanded', !isExpanded);

            const icon = mobileMenuToggle.querySelector('i');
            if (icon) {
                icon.className = isExpanded ? 'ri-menu-line' : 'ri-close-line';
            }
        });

        // Close mobile menu on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !mobileMenu?.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
                mobileMenuToggle?.setAttribute('aria-expanded', 'false');
                const icon = mobileMenuToggle?.querySelector('i');
                if (icon) {
                    icon.className = 'ri-menu-line';
                }
                mobileMenuToggle?.focus();
            }
        });
    }

    /**
     * Filter System Setup
     */
    setupFilters() {
        const filterChips = document.querySelectorAll('.filter-chip');

        filterChips.forEach(chip => {
            chip.addEventListener('click', () => {
                filterChips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                this.currentFilter = chip.dataset.filter;
                this.applyFilters();
                this.announceToScreenReader(`Filtro attivo: ${chip.textContent}`);
            });
        });

        const sortSelect = document.getElementById('sortProjects');
        sortSelect?.addEventListener('change', (e) => {
            this.currentSort = e.target.value;
            this.applyFilters();
            this.announceToScreenReader(`Ordinamento cambiato: ${e.target.selectedOptions[0].text}`);
        });
    }

    /**
     * Search Functionality with Debouncing
     */
    setupSearch() {
        const searchInput = document.getElementById('projectSearch');
        let searchTimeout;

        searchInput?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.searchTerm = e.target.value.toLowerCase();
                this.applyFilters();
            }, 300);
        });
    }

    /**
     * Loading System Setup
     */
    setupLoadingSystem() {
        const loadingOverlay = document.getElementById('loadingOverlay');

        window.addEventListener('load', () => {
            setTimeout(() => {
                if (loadingOverlay) {
                    loadingOverlay.style.opacity = '0';
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                    }, 500);
                }
            }, 1000);
        });
    }

    /**
     * Unified Counter Animation System
     */
    animateCounters() {
        const counters = document.querySelectorAll('[data-counter], .counter[data-target]');

        counters.forEach(counter => {
            // Support both data-counter and data-target attributes
            const target = parseInt(counter.dataset.counter || counter.dataset.target);
            if (isNaN(target)) return;

            const isEuro = counter.textContent.includes('€');
            let current = 0;
            const increment = Math.max(1, target / 50);

            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    const displayValue = Math.floor(current);

                    if (isEuro) {
                        counter.textContent = `€${displayValue.toLocaleString()}`;
                    } else {
                        counter.textContent = displayValue.toLocaleString();
                    }

                    requestAnimationFrame(updateCounter);
                } else {
                    // Final value
                    if (isEuro) {
                        counter.textContent = `€${target.toLocaleString()}`;
                    } else {
                        counter.textContent = target.toLocaleString();
                    }
                }
            };

            // Use Intersection Observer for performance
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            observer.observe(counter);
        });
    }

    /**
     * Filter and Sort Projects
     */
    applyFilters() {
        this.isLoading = true;
        this.showLoading();

        setTimeout(() => {
            let filtered = [...this.projects];

            // Apply category filter
            if (this.currentFilter !== 'all') {
                filtered = filtered.filter(project =>
                    project.category === this.currentFilter ||
                    project.tags?.includes(this.currentFilter)
                );
            }

            // Apply search filter
            if (this.searchTerm) {
                filtered = filtered.filter(project =>
                    project.title.toLowerCase().includes(this.searchTerm) ||
                    project.description.toLowerCase().includes(this.searchTerm) ||
                    project.author?.toLowerCase().includes(this.searchTerm) ||
                    project.tags?.some(tag => tag.toLowerCase().includes(this.searchTerm))
                );
            }

            // Apply sorting
            this.sortProjects(filtered);

            this.filteredProjects = filtered;
            this.currentPage = 1;
            this.renderProjects();
            this.hideLoading();
            this.isLoading = false;
        }, 300);
    }

    /**
     * Sort projects based on current sort option
     */
    sortProjects(projects) {
        switch (this.currentSort) {
            case 'newest':
                projects.sort((a, b) => new Date(b.date) - new Date(a.date));
                break;
            case 'oldest':
                projects.sort((a, b) => new Date(a.date) - new Date(b.date));
                break;
            case 'popular':
                projects.sort((a, b) => (b.likes || 0) - (a.likes || 0));
                break;
            case 'funding':
                projects.sort((a, b) => (b.funded || 0) - (a.funded || 0));
                break;
            case 'alphabetical':
                projects.sort((a, b) => a.title.localeCompare(b.title));
                break;
        }
    }

    /**
     * Show/Hide Loading States
     */
    showLoading() {
        const loadingSpinner = document.querySelector('.loading-spinner');
        if (loadingSpinner) {
            loadingSpinner.style.display = 'block';
        }
    }

    hideLoading() {
        const loadingSpinner = document.querySelector('.loading-spinner');
        if (loadingSpinner) {
            loadingSpinner.style.display = 'none';
        }
    }

    /**
     * Accessibility Setup
     */
    setupAccessibility() {
        // Announce region for screen readers
        if (!document.getElementById('sr-announcements')) {
            const announceRegion = document.createElement('div');
            announceRegion.id = 'sr-announcements';
            announceRegion.setAttribute('aria-live', 'polite');
            announceRegion.setAttribute('aria-atomic', 'true');
            announceRegion.style.cssText = 'position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;';
            document.body.appendChild(announceRegion);
        }

        // Enhanced keyboard navigation
        this.setupKeyboardNavigation();
    }

    /**
     * Keyboard Navigation
     */
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    }

    /**
     * Screen Reader Announcements
     */
    announceToScreenReader(message) {
        const announceRegion = document.getElementById('sr-announcements');
        if (announceRegion) {
            announceRegion.textContent = message;
        }
    }

    /**
     * Performance Monitoring
     */
    setupPerformanceMonitoring() {
        // Monitor Core Web Vitals
        if ('performance' in window && 'PerformanceObserver' in window) {
            try {
                new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        if (entry.entryType === 'largest-contentful-paint') {
                            // LCP monitoring for performance optimization
                        }
                    });
                }).observe({ entryTypes: ['largest-contentful-paint'] });
            } catch (error) {
                // Performance monitoring not supported
            }
        }
    }

    /**
     * Methods to be overridden by subclasses
     */    loadProjects() {
        // To be implemented by subclasses
    }

    loadCategorySpecificData() {
        // To be implemented by subclasses if needed
        // For loading artists, technologies, etc.
    }

    renderProjects() {
        // To be implemented by subclasses
    }

    generateMockProjects() {
        // To be implemented by subclasses
    }
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BaseCategoryManager;
} else {
    window.BaseCategoryManager = BaseCategoryManager;
}
