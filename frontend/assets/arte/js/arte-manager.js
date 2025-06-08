// Arte Page Manager
class ArtePageManager extends BaseCategoryManager {
    constructor() {
        super('arte');
        this.PLACEHOLDER_ART_IMAGE = '/frontend/images/placeholder-art.jpg';
        this.DEFAULT_AVATAR_IMAGE = '/frontend/images/default-avatar.jpg';
    }

    /**
     * Override to load category-specific data
     */
    loadCategorySpecificData() {
        this.loadArtists();
    }

    async loadProjects() {
        const loadingProjects = document.getElementById('loadingProjects');
        const projectsGrid = document.getElementById('projectsGrid');

        loadingProjects?.classList.remove('hidden');
        if (projectsGrid) projectsGrid.innerHTML = '';

        try {
            await this.simulateApiDelay(1500);

            this.projects = this.generateMockProjects();
            this.filteredProjects = [...this.projects];

            loadingProjects?.classList.add('hidden');
            this.renderProjects();
            this.updateLoadMoreButton();
        } catch (error) {
            showNotification('Errore nel caricamento dei progetti artistici', 'error');
        }
    }

    async loadArtists() {
        try {
            await this.simulateApiDelay(800);
            this.artists = this.generateMockArtists();
            this.renderArtists();
        } catch (error) {
            showNotification('Errore nel caricamento degli artisti', 'error');
        }
    } generateMockProjects() {
        return window.MockDataGenerator.generateProjects('arte', 24);
    } generateMockArtists() {
        return window.MockDataGenerator.generateCreators('arte', 8);
    }

    renderProjects() {
        const projectsGrid = document.getElementById('projectsGrid');
        if (!projectsGrid) return;

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
        this.attachProjectCardListeners();

        const newCards = projectsGrid.querySelectorAll('.project-card');
        newCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    renderArtists() {
        const artistsGrid = document.getElementById('artistsGrid');
        if (!artistsGrid) return;

        artistsGrid.innerHTML = this.artists.map(artist => this.createArtistCard(artist)).join('');
        this.attachArtistCardListeners();
    }

    createProjectCard(project) {
        return `
            <article class="project-card group cursor-pointer" data-project-id="${project.id}" role="article" tabindex="0" aria-labelledby="project-title-${project.id}">
                <div class="relative overflow-hidden">
                    <img src="${project.image}" alt="Immagine del progetto ${project.title}" 
                         class="w-full h-56 object-cover group-hover:scale-110 transition-transform duration-500"
                         loading="lazy"
                         onerror="this.src='${this.PLACEHOLDER_ART_IMAGE}'">
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
                         onerror="this.src='${this.DEFAULT_AVATAR_IMAGE}'">
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
                <button class="btn-follow-artist mt-4 w-full bg-primary-600 text-white py-2 rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium">
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
        if (!loadMoreBtn) return;

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

    viewProject(projectId) {
        window.location.href = `/frontend/projects/art-project-${projectId}.html`;
    }

    viewArtist(artistId) {
        window.location.href = `/frontend/artists/artist-${artistId}.html`;
    }

    async simulateApiDelay(ms) {
        return window.MockDataGenerator.simulateApiDelay(ms);
    }

    attachProjectCardListeners() {
        const projectsGrid = document.getElementById('projectsGrid');
        if (!projectsGrid) return;

        projectsGrid.querySelectorAll('.project-card').forEach(card => {
            const projectId = card.dataset.projectId;
            if (!projectId) return;

            const handleInteraction = () => {
                this.viewProject(projectId);
            };

            card.addEventListener('click', handleInteraction);
            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault(); // Prevent default space scroll
                    handleInteraction();
                }
            });
        });
    }

    attachArtistCardListeners() {
        const artistsGrid = document.getElementById('artistsGrid');
        if (!artistsGrid) return;

        artistsGrid.querySelectorAll('.artist-card .btn-follow-artist').forEach(button => {
            button.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent triggering other listeners on the card if any
                const artistCard = button.closest('.artist-card');
                if (artistCard) {
                    const artistId = artistCard.dataset.artistId;
                    if (artistId) {
                        // For now, this button will also navigate to the artist's page.
                        // This could be changed to a specific "follow" action.
                        this.viewArtist(artistId);
                        // Example: showNotification(`Hai iniziato a seguire l'artista ${artistId}`, 'info');
                    }
                }
            });
        });
    }
}

// Initialize the arte page manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ArtePageManager();
});

// Global error handling
window.addEventListener('error', (e) => {
    if (window.ErrorHandler) {
        window.ErrorHandler.handleDashboardError(e.error, 'global-error');
    }
});

// Service Worker registration for PWA support
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/frontend/sw.js')
        .then(registration => {
            // SW registered
        })
        .catch(error => {
            if (window.ErrorHandler) {
                window.ErrorHandler.handleCacheError(error, 'sw-registration');
            }
        });
}
