// ===== ARTE PAGE MANAGEMENT SYSTEM =====
class ArtePageManager {
    constructor() {
        this.projects = [];
        this.artists = [];
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
        this.loadArtists();
        this.animateCounters();
        this.setupAccessibility();
        this.setupPerformanceMonitoring();
    }

    setupProgressiveEnhancement() {
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    }

    setupThemeSystem() {
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

            this.announceToScreenReader(`Tema cambiato in modalità ${newTheme === 'dark' ? 'scura' : 'chiara'}`);
        });

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

        document.addEventListener('click', (e) => {
            if (!mobileMenuToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.add('hidden');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                mobileMenuToggle.querySelector('i').className = 'ri-menu-line';
            }
        });

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

        loadingProjects.classList.remove('hidden');
        projectsGrid.innerHTML = '';

        try {
            await this.simulateApiDelay(1500);

            this.projects = this.generateMockProjects();
            this.filteredProjects = [...this.projects];

            loadingProjects.classList.add('hidden');
            this.renderProjects();
            this.updateLoadMoreButton();

        } catch (error) {
            this.showNotification('Errore nel caricamento dei progetti artistici', 'error');
            console.error('Error loading projects:', error);
        }
    }

    async loadArtists() {
        try {
            await this.simulateApiDelay(800);
            this.artists = this.generateMockArtists();
            this.renderArtists();
        } catch (error) {
            console.error('Error loading artists:', error);
        }
    }

    generateMockProjects() {
        const categories = ['pittura', 'scultura', 'installazioni', 'fotografia', 'digitale', 'street'];
        const artTypes = ['Pittura ad Olio', 'Scultura Contemporanea', 'Installazione Multimediale', 'Fotografia Concettuale', 'Arte Digitale', 'Murales Urbano'];
        const projects = [];

        for (let i = 1; i <= 24; i++) {
            const category = categories[Math.floor(Math.random() * categories.length)];
            projects.push({
                id: i,
                title: `Opera Artistica ${i}`,
                description: `Un'opera innovativa di ${artTypes[categories.indexOf(category)]} che esplora temi contemporanei attraverso tecniche tradizionali e moderne.`,
                category: category,
                image: `/frontend/images/art-project-${(i % 6) + 1}.jpg`,
                creator: `Artista ${i}`,
                goal: Math.floor(Math.random() * 30000) + 5000,
                raised: 0,
                backers: Math.floor(Math.random() * 150) + 10,
                daysLeft: Math.floor(Math.random() * 45) + 1,
                featured: Math.random() > 0.8,
                creativity: Math.floor(Math.random() * 100) + 1,
                createdAt: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000)
            });
        }

        projects.forEach(project => {
            project.raised = Math.floor(project.goal * (Math.random() * 0.8 + 0.1));
            project.progress = Math.round((project.raised / project.goal) * 100);
        });

        return projects;
    }

    generateMockArtists() {
        const artistNames = ['Marco Rossi', 'Giulia Bianchi', 'Alessandro Verdi', 'Francesca Neri', 'Luca Ferrari', 'Sofia Romano', 'Andrea Ricci', 'Elena Greco'];
        const specialties = ['Pittore', 'Scultrice', 'Artista Digitale', 'Fotografa', 'Installatrice', 'Street Artist'];
        const artists = [];

        for (let i = 0; i < 8; i++) {
            artists.push({
                id: i + 1,
                name: artistNames[i],
                specialty: specialties[Math.floor(Math.random() * specialties.length)],
                projects: Math.floor(Math.random() * 8) + 1,
                followers: Math.floor(Math.random() * 2000) + 100,
                avatar: `/frontend/images/artist-${i + 1}.jpg`,
                featured: Math.random() > 0.5
            });
        }

        return artists;
    }

    applyFilters() {
        let filtered = [...this.projects];

        if (this.currentFilter !== 'all') {
            filtered = filtered.filter(project => project.category === this.currentFilter);
        }

        if (this.searchTerm) {
            filtered = filtered.filter(project =>
                project.title.toLowerCase().includes(this.searchTerm) ||
                project.description.toLowerCase().includes(this.searchTerm) ||
                project.creator.toLowerCase().includes(this.searchTerm)
            );
        }

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
                case 'creative':
                    return b.creativity - a.creativity;
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
                    <i class="ri-palette-line text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Nessun progetto artistico trovato</h3>
                    <p class="text-gray-600 dark:text-gray-400">Prova a modificare i filtri o il termine di ricerca.</p>
                </div>
            `;
            return;
        }

        projectsGrid.innerHTML = projectsToShow.map(project => this.createProjectCard(project)).join('');

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

    renderArtists() {
        const artistsGrid = document.getElementById('artistsGrid');

        artistsGrid.innerHTML = this.artists.map(artist => this.createArtistCard(artist)).join('');
    }

    createProjectCard(project) {
        return `
            <article class="project-card group cursor-pointer" data-project-id="${project.id}" role="article" tabindex="0" aria-labelledby="project-title-${project.id}">
                <div class="relative overflow-hidden">
                    <img src="${project.image}" alt="Immagine del progetto ${project.title}" 
                         class="w-full h-56 object-cover group-hover:scale-110 transition-transform duration-500"
                         loading="lazy"
                         onerror="this.src='/frontend/images/placeholder-art.jpg'">
                    ${project.featured ? '<div class="absolute top-3 left-3 artistic-badge">In Evidenza</div>' : ''}
                    <div class="absolute top-3 right-3 bg-black bg-opacity-60 text-white text-xs px-3 py-1 rounded-full">
                        ${project.daysLeft} giorni
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400 uppercase tracking-wider">${this.getCategoryName(project.category)}</span>
                        <div class="flex items-center space-x-1 text-xs text-gray-500">
                            <i class="ri-heart-line"></i>
                            <span>${project.backers}</span>
                        </div>
                    </div>
                    <h3 id="project-title-${project.id}" class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-primary-600 transition-colors font-serif">
                        ${project.title}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-2 leading-relaxed">
                        ${project.description}
                    </p>
                    
                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Progresso</span>
                            <span class="font-semibold text-gray-900 dark:text-white">${project.progress}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-primary-500 to-secondary-500 h-2 rounded-full transition-all duration-500" 
                                 style="width: ${project.progress}%"></div>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="flex justify-between items-center text-sm mb-5">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">€${project.raised.toLocaleString()}</div>
                            <div class="text-gray-500 text-xs">di €${project.goal.toLocaleString()}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-gray-900 dark:text-white">${project.backers}</div>
                            <div class="text-gray-500 text-xs">sostenitori</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-primary-600">${project.creativity}%</div>
                            <div class="text-gray-500 text-xs">creatività</div>
                        </div>
                    </div>
                    
                    <!-- Creator -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-secondary-500 rounded-full flex items-center justify-center text-white font-bold">
                                ${project.creator.charAt(0)}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">${project.creator}</div>
                                <div class="text-xs text-gray-500">Artista</div>
                            </div>
                        </div>
                        <button class="text-primary-600 hover:text-primary-700 font-semibold text-sm transition-colors flex items-center space-x-1">
                            <span>Scopri</span>
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </div>
                </div>
            </article>
        `;
    }

    createArtistCard(artist) {
        return `
            <div class="artist-card group cursor-pointer" data-artist-id="${artist.id}">
                <div class="relative mb-4">
                    <img src="${artist.avatar}" alt="Avatar di ${artist.name}" 
                         class="w-20 h-20 rounded-full mx-auto object-cover ring-4 ring-primary-100 group-hover:ring-primary-300 transition-all duration-300"
                         loading="lazy"
                         onerror="this.src='/frontend/images/default-avatar.jpg'">
                    ${artist.featured ? '<div class="absolute -top-2 -right-2 w-6 h-6 bg-yellow-400 rounded-full flex items-center justify-center"><i class="ri-star-fill text-xs text-yellow-900"></i></div>' : ''}
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">${artist.name}</h3>
                <p class="text-primary-600 dark:text-primary-400 text-sm font-medium mb-3">${artist.specialty}</p>
                <div class="grid grid-cols-2 gap-4 text-xs text-gray-600 dark:text-gray-400">
                    <div class="text-center">
                        <div class="font-semibold text-gray-900 dark:text-white">${artist.projects}</div>
                        <div>Progetti</div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-gray-900 dark:text-white">${artist.followers}</div>
                        <div>Follower</div>
                    </div>
                </div>
                <button class="mt-4 w-full bg-primary-600 text-white py-2 rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium">
                    Segui Artista
                </button>
            </div>
        `;
    }

    getCategoryName(category) {
        const names = {
            pittura: 'Pittura',
            scultura: 'Scultura',
            installazioni: 'Installazioni',
            fotografia: 'Fotografia',
            digitale: 'Arte Digitale',
            street: 'Street Art'
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
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                const target = e.target;
                if (target.classList.contains('project-card') || target.classList.contains('artist-card')) {
                    e.preventDefault();
                    target.click();
                }
            }
        });

        document.addEventListener('click', (e) => {
            const projectCard = e.target.closest('.project-card');
            const artistCard = e.target.closest('.artist-card');

            if (projectCard && !e.target.closest('button')) {
                const projectId = projectCard.dataset.projectId;
                this.viewProject(projectId);
            }

            if (artistCard && !e.target.closest('button')) {
                const artistId = artistCard.dataset.artistId;
                this.viewArtist(artistId);
            }
        });
    }

    viewProject(projectId) {
        window.location.href = `/frontend/projects/art-project-${projectId}.html`;
    }

    viewArtist(artistId) {
        window.location.href = `/frontend/artists/artist-${artistId}.html`;
    }

    setupPerformanceMonitoring() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'navigation') {
                        console.log('Arte Page Load Time:', entry.loadEventEnd - entry.loadEventStart);
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
            info: 'bg-primary-500'
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

        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

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

// Initialize the arte page manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ArtePageManager();
});

// Global error handling
window.addEventListener('error', (e) => {
    console.error('Arte Page Error:', e.error);
});

// Service Worker registration for PWA support
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/frontend/sw.js')
        .then(registration => console.log('SW registered'))
        .catch(error => console.log('SW registration failed'));
}
