// Technology Page Manager
class TechnologyPageManager {
    constructor() {
        this.projects = [];
        this.filteredProjects = [];
        this.currentFilter = 'all';
        this.currentSort = 'newest';
        this.searchTerm = '';
        this.isLoading = false;
        this.projectsPerPage = 9;
        this.currentPage = 1;

        this.init();
    }

    init() {
        this.setupProgressiveEnhancement();
        this.setupThemeSystem();
        this.setupNavigation();
        this.setupFilters();
        this.setupSearch();
        this.setupLoadingSystem();
        this.loadProjects();
        this.animateCounters();
        this.setupAccessibility();
        this.setupPerformanceMonitoring();
    }

    setupProgressiveEnhancement() {
        // Remove no-js class and add js class for progressive enhancement
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    }

    setupThemeSystem() {
        const themeToggle = document.getElementById('themeToggle');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

        // Initialize theme
        const savedTheme = localStorage.getItem('theme');
        const systemTheme = prefersDark.matches ? 'dark' : 'light';
        const initialTheme = savedTheme || systemTheme;

        this.setTheme(initialTheme);

        // Theme toggle functionality
        themeToggle?.addEventListener('click', () => {
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            this.setTheme(newTheme);
            localStorage.setItem('theme', newTheme);

            this.announceToScreenReader(`Tema cambiato in modalità ${newTheme === 'dark' ? 'scura' : 'chiara'}`);
        });

        // Listen for system theme changes
        prefersDark.addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    setTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    setupNavigation() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenu = document.getElementById('mobileMenu');

        mobileMenuToggle?.addEventListener('click', () => {
            const isOpen = !mobileMenu.classList.contains('hidden');

            if (isOpen) {
                mobileMenu.classList.add('hidden');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                mobileMenuToggle.querySelector('i').className = 'ri-menu-line';
            } else {
                mobileMenu.classList.remove('hidden');
                mobileMenuToggle.setAttribute('aria-expanded', 'true');
                mobileMenuToggle.querySelector('i').className = 'ri-close-line';
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenuToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.add('hidden');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                mobileMenuToggle.querySelector('i').className = 'ri-menu-line';
            }
        });

        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                mobileMenuToggle.querySelector('i').className = 'ri-menu-line';
                mobileMenuToggle.focus();
            }
        });
    }

    setupFilters() {
        const filterChips = document.querySelectorAll('.filter-chip');

        filterChips.forEach(chip => {
            chip.addEventListener('click', () => {
                // Remove active class from all chips
                filterChips.forEach(c => c.classList.remove('active'));

                // Add active class to clicked chip
                chip.classList.add('active');

                // Update current filter
                this.currentFilter = chip.dataset.filter;

                // Apply filters
                this.applyFilters();

                this.announceToScreenReader(`Filtro attivo: ${chip.textContent}`);
            });
        });

        // Sort functionality
        const sortSelect = document.getElementById('sortProjects');
        sortSelect?.addEventListener('change', (e) => {
            this.currentSort = e.target.value;
            this.applyFilters();
            this.announceToScreenReader(`Ordinamento cambiato: ${e.target.selectedOptions[0].text}`);
        });
    }

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

    setupLoadingSystem() {
        const loadingOverlay = document.getElementById('loadingOverlay');

        // Hide loading overlay after page load
        window.addEventListener('load', () => {
            setTimeout(() => {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 500);
            }, 1000);
        });
    }

    async loadProjects() {
        const loadingProjects = document.getElementById('loadingProjects');
        const projectsGrid = document.getElementById('projectsGrid');

        // Show loading state
        loadingProjects.classList.remove('hidden');
        projectsGrid.innerHTML = '';

        try {
            // Simulate API call - replace with actual API endpoint
            await this.simulateApiDelay(1500);

            // Mock project data
            this.projects = this.generateMockProjects();
            this.filteredProjects = [...this.projects];

            // Hide loading state
            loadingProjects.classList.add('hidden');

            // Render projects
            this.renderProjects();

            // Show load more button if needed
            this.updateLoadMoreButton();

        } catch (error) {
            this.showNotification('Errore nel caricamento dei progetti', 'error');
            console.error('Error loading projects:', error);
        }
    }

    generateMockProjects() {
        const categories = ['hardware', 'software', 'ai', 'iot', 'blockchain', 'vr'];
        const projects = [];

        for (let i = 1; i <= 24; i++) {
            projects.push({
                id: i,
                title: `Progetto Tech ${i}`,
                description: `Descrizione innovativa per il progetto tecnologico numero ${i}. Un'idea rivoluzionaria che cambierà il futuro.`,
                category: categories[Math.floor(Math.random() * categories.length)],
                image: `../placeholder-tech${(i % 3) + 1}.jpg`,
                creator: `Creatore ${i}`,
                goal: Math.floor(Math.random() * 50000) + 10000,
                raised: 0,
                backers: Math.floor(Math.random() * 200) + 10,
                daysLeft: Math.floor(Math.random() * 30) + 1,
                featured: Math.random() > 0.7,
                createdAt: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000)
            });
        }

        // Calculate raised amount and progress
        projects.forEach(project => {
            project.raised = Math.floor(project.goal * (Math.random() * 0.8 + 0.1));
            project.progress = Math.round((project.raised / project.goal) * 100);
        });

        return projects;
    }

    applyFilters() {
        let filtered = [...this.projects];

        // Apply category filter
        if (this.currentFilter !== 'all') {
            filtered = filtered.filter(project => project.category === this.currentFilter);
        }

        // Apply search filter
        if (this.searchTerm) {
            filtered = filtered.filter(project =>
                project.title.toLowerCase().includes(this.searchTerm) ||
                project.description.toLowerCase().includes(this.searchTerm) ||
                project.creator.toLowerCase().includes(this.searchTerm)
            );
        }

        // Apply sorting
        filtered.sort((a, b) => {
            switch (this.currentSort) {
                case 'newest':
                    return new Date(b.createdAt) - new Date(a.createdAt);
                case 'ending':
                    return a.daysLeft - b.daysLeft;
                case 'funded':
                    return b.progress - a.progress;
                case 'popular':
                    return b.backers - a.backers;
                default:
                    return 0;
            }
        });

        this.filteredProjects = filtered;
        this.currentPage = 1;
        this.renderProjects();
        this.updateLoadMoreButton();
    }

    renderProjects() {
        const projectsGrid = document.getElementById('projectsGrid');
        const startIndex = 0;
        const endIndex = this.currentPage * this.projectsPerPage;
        const projectsToShow = this.filteredProjects.slice(startIndex, endIndex);

        if (projectsToShow.length === 0) {
            projectsGrid.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i class="ri-search-line text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Nessun progetto trovato</h3>
                    <p class="text-gray-600 dark:text-gray-400">Prova a modificare i filtri o il termine di ricerca.</p>
                </div>
            `;
            return;
        }

        projectsGrid.innerHTML = projectsToShow.map(project => this.createProjectCard(project)).join('');

        // Add animations to new cards
        const newCards = projectsGrid.querySelectorAll('.project-card');
        newCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    createProjectCard(project) {
        return `
            <article class="project-card group cursor-pointer" data-project-id="${project.id}" role="article" tabindex="0" aria-labelledby="project-title-${project.id}">
                <div class="relative overflow-hidden">
                    <img src="${project.image}" alt="Immagine del progetto ${project.title}" 
                         class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                         loading="lazy">
                    ${project.featured ? '<div class="absolute top-2 left-2 bg-yellow-400 text-yellow-900 text-xs font-semibold px-2 py-1 rounded-full">In Evidenza</div>' : ''}
                    <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded-full">
                        ${project.daysLeft} giorni
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wide">${this.getCategoryName(project.category)}</span>
                        <div class="flex items-center space-x-1 text-xs text-gray-500">
                            <i class="ri-heart-line"></i>
                            <span>${project.backers}</span>
                        </div>
                    </div>
                    <h3 id="project-title-${project.id}" class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-primary-600 transition-colors">
                        ${project.title}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-2">
                        ${project.description}
                    </p>
                    
                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Progresso</span>
                            <span class="font-medium text-gray-900 dark:text-white">${project.progress}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-primary-500 to-primary-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: ${project.progress}%"></div>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="flex justify-between items-center text-sm mb-4">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">€${project.raised.toLocaleString()}</div>
                            <div class="text-gray-500">di €${project.goal.toLocaleString()}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-gray-900 dark:text-white">${project.backers}</div>
                            <div class="text-gray-500">sostenitori</div>
                        </div>
                    </div>
                    
                    <!-- Creator -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                ${project.creator.charAt(0)}
                            </div>
                            <span class="text-sm text-gray-600 dark:text-gray-300">${project.creator}</span>
                        </div>
                        <button class="text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors">
                            Scopri di più →
                        </button>
                    </div>
                </div>
            </article>
        `;
    }

    getCategoryName(category) {
        const names = {
            hardware: 'Hardware',
            software: 'Software',
            ai: 'AI',
            iot: 'IoT',
            blockchain: 'Blockchain',
            vr: 'VR/AR'
        };
        return names[category] || category;
    }

    updateLoadMoreButton() {
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const totalShown = this.currentPage * this.projectsPerPage;

        if (totalShown < this.filteredProjects.length) {
            loadMoreBtn.classList.remove('hidden');
            loadMoreBtn.onclick = () => {
                this.currentPage++;
                this.renderProjects();
                this.updateLoadMoreButton();
            };
        } else {
            loadMoreBtn.classList.add('hidden');
        }
    }

    animateCounters() {
        const counters = document.querySelectorAll('[data-counter]');

        counters.forEach(counter => {
            const target = parseInt(counter.dataset.counter);
            const isEuro = counter.textContent.includes('€');
            let current = 0;
            const increment = target / 50;

            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    if (isEuro) {
                        counter.textContent = `€${Math.floor(current).toLocaleString()}`;
                    } else {
                        counter.textContent = Math.floor(current).toLocaleString();
                    }
                    requestAnimationFrame(updateCounter);
                } else {
                    if (isEuro) {
                        counter.textContent = `€${target.toLocaleString()}`;
                    } else {
                        counter.textContent = target.toLocaleString();
                    }
                }
            };

            // Start animation when element is in view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            });

            observer.observe(counter);
        });
    }

    setupAccessibility() {
        // Add click handlers for keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                const target = e.target;
                if (target.classList.contains('project-card')) {
                    e.preventDefault();
                    target.click();
                }
            }
        });

        // Add project card click handlers
        document.addEventListener('click', (e) => {
            const projectCard = e.target.closest('.project-card');
            if (projectCard && !e.target.closest('button')) {
                const projectId = projectCard.dataset.projectId;
                this.viewProject(projectId);
            }
        });
    }

    viewProject(projectId) {
        // Navigate to project detail page
        window.location.href = `/frontend/projects/project-${projectId}.html`;
    }

    setupPerformanceMonitoring() {
        // Monitor performance metrics
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'navigation') {
                        console.log('Page Load Time:', entry.loadEventEnd - entry.loadEventStart);
                    }
                });
            });
            observer.observe({ entryTypes: ['navigation'] });
        }
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');

        const icons = {
            success: 'ri-check-line',
            error: 'ri-error-warning-line',
            warning: 'ri-alert-line',
            info: 'ri-information-line'
        };

        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        notification.className = `${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform translate-x-full transition-transform duration-300`;
        notification.innerHTML = `
            <i class="${icons[type]}"></i>
            <span>${message}</span>
            <button class="ml-auto hover:bg-white hover:bg-opacity-20 rounded p-1" onclick="this.parentElement.remove()">
                <i class="ri-close-line"></i>
            </button>
        `;

        container.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;

        document.body.appendChild(announcement);
        setTimeout(() => document.body.removeChild(announcement), 1000);
    }

    async simulateApiDelay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Initialize the technology page manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new TechnologyPageManager();
});

// Global error handling
window.addEventListener('error', (e) => {
    console.error('Page Error:', e.error);
});

// Service Worker registration for PWA support
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/frontend/sw.js')
        .then(registration => console.log('SW registered'))
        .catch(error => console.log('SW registration failed'));
}
