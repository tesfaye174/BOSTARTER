/**
 * Generic Category Manager
 * Simple manager for categories that don't need specific functionality
 * Extends BaseCategoryManager with minimal configuration
 */

class GenericCategoryManager extends BaseCategoryManager {
    constructor(categoryName) {
        super(categoryName);
        this.PLACEHOLDER_IMAGE = '/frontend/images/placeholder-generic.jpg';
        this.DEFAULT_AVATAR_IMAGE = '/frontend/images/default-avatar.jpg';
    }

    /**
     * Override to load basic project data
     */
    async loadProjects() {
        const loadingProjects = document.getElementById('loadingProjects');
        const projectsGrid = document.getElementById('projectsGrid');

        loadingProjects?.classList.remove('hidden');
        if (projectsGrid) projectsGrid.innerHTML = '';

        try {
            // Simulate API delay
            await new Promise(resolve => setTimeout(resolve, 1000));

            this.projects = this.generateMockProjects();
            this.filteredProjects = [...this.projects];

            loadingProjects?.classList.add('hidden');
            this.renderProjects();
            this.updateLoadMoreButton();
        } catch (error) {
            console.error(`Error loading ${this.categoryName} projects:`, error);
            this.showError('Errore nel caricamento dei progetti');
        }
    }

    /**
     * Generate mock projects for the category
     */
    generateMockProjects() {
        if (window.MockDataGenerator) {
            return window.MockDataGenerator.generateProjects(this.categoryName, 12);
        }

        // Fallback if MockDataGenerator is not available
        return this.createFallbackProjects();
    }

    /**
     * Create fallback projects if MockDataGenerator is not available
     */
    createFallbackProjects() {
        const projects = [];
        const categories = this.getCategoryFilters();
        const titles = this.getCategoryTitles();

        for (let i = 1; i <= 12; i++) {
            projects.push({
                id: i,
                title: titles[i % titles.length] || `Progetto ${this.categoryName} ${i}`,
                description: `Descrizione del progetto ${this.categoryName} numero ${i}`,
                image: this.PLACEHOLDER_IMAGE,
                category: categories[i % categories.length] || 'generale',
                creator: `Creatore ${i}`,
                goal: Math.floor(Math.random() * 50000) + 5000,
                raised: Math.floor(Math.random() * 40000) + 1000,
                backers: Math.floor(Math.random() * 200) + 10,
                daysLeft: Math.floor(Math.random() * 30) + 1,
                progress: Math.floor(Math.random() * 90) + 10,
                featured: i <= 3,
                creativity: Math.floor(Math.random() * 30) + 70
            });
        }

        return projects;
    }

    /**
     * Get category-specific filters
     */
    getCategoryFilters() {
        const filterMap = {
            musica: ['tutti', 'album', 'strumenti', 'concerti', 'educazione'],
            design: ['tutti', 'grafico', 'industriale', 'ux-ui', 'interni'],
            film: ['tutti', 'cortometraggi', 'documentari', 'animazione', 'feature'],
            fotografia: ['tutti', 'ritratti', 'paesaggi', 'eventi', 'street'],
            moda: ['tutti', 'abbigliamento', 'accessori', 'sostenibile', 'avant-garde'],
            giochi: ['tutti', 'tavolo', 'carte', 'digitali', 'educativi'],
            fumetti: ['tutti', 'graphic-novel', 'webcomics', 'manga', 'indie'],
            teatro: ['tutti', 'dramma', 'musical', 'sperimentale', 'comunitario'],
            danza: ['tutti', 'contemporanea', 'classica', 'urbana', 'folk'],
            editoriale: ['tutti', 'libri', 'riviste', 'ebook', 'poesia'],
            cibo: ['tutti', 'ristoranti', 'prodotti', 'cookbook', 'bevande'],
            artigianato: ['tutti', 'ceramica', 'tessile', 'legno', 'metallo'],
            giornalismo: ['tutti', 'investigativo', 'locale', 'podcast', 'video']
        };

        return filterMap[this.categoryName] || ['tutti', 'generale', 'creativo', 'innovativo'];
    }

    /**
     * Get category-specific project titles
     */
    getCategoryTitles() {
        const titleMap = {
            musica: ['Album Debut', 'Nuovo Strumento', 'Tour Musicale', 'Studio Musicale'],
            design: ['Design Sostenibile', 'Prodotto Innovativo', 'Identità Visiva', 'Spazio Moderno'],
            film: ['Cortometraggio Indie', 'Documentario Sociale', 'Film Animato', 'Web Series'],
            fotografia: ['Mostra Fotografica', 'Libro di Foto', 'Progetto Documentario', 'Workshop'],
            moda: ['Collezione Sostenibile', 'Brand Emergente', 'Accessori Artigianali', 'Fashion Week'],
            giochi: ['Gioco da Tavolo', 'Card Game', 'Puzzle Innovativo', 'Gioco Educativo'],
            fumetti: ['Graphic Novel', 'Serie a Fumetti', 'Webcomic', 'Illustrazioni'],
            teatro: ['Spettacolo Teatrale', 'Musical Originale', 'Teatro Sperimentale', 'Performance'],
            danza: ['Spettacolo di Danza', 'Workshop di Danza', 'Video Danza', 'Festival'],
            editoriale: ['Libro Indipendente', 'Rivista Culturale', 'Poesia Contemporanea', 'Antologia'],
            cibo: ['Ristorante Sostenibile', 'Prodotto Biologico', 'Cookbook Artistico', 'Food Truck'],
            artigianato: ['Ceramiche Artistiche', 'Tessili Tradizionali', 'Mobili Artigianali', 'Gioielli'],
            giornalismo: ['Inchiesta Giornalistica', 'Podcast Indipendente', 'Rivista Locale', 'Documentario']
        };

        return titleMap[this.categoryName] || ['Progetto Creativo', 'Idea Innovativa', 'Iniziativa Artistica'];
    }

    /**
     * Render projects with basic card layout
     */
    renderProjects() {
        const projectsGrid = document.getElementById('projectsGrid');
        if (!projectsGrid) return;

        const startIndex = 0;
        const endIndex = this.currentPage * this.projectsPerPage;
        const projectsToShow = this.filteredProjects.slice(startIndex, endIndex);

        if (projectsToShow.length === 0) {
            projectsGrid.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i class="ri-folder-line text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Nessun progetto trovato</h3>
                    <p class="text-gray-600 dark:text-gray-400">Prova a modificare i filtri o il termine di ricerca.</p>
                </div>
            `;
            return;
        }

        projectsGrid.innerHTML = projectsToShow.map(project => this.createProjectCard(project)).join('');

        // Add animation to new cards
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

    /**
     * Create a basic project card
     */
    createProjectCard(project) {
        return `
            <article class="project-card group cursor-pointer" data-project-id="${project.id}" role="article" tabindex="0" aria-labelledby="project-title-${project.id}">
                <div class="relative overflow-hidden">
                    <img src="${project.image}" alt="Immagine del progetto ${project.title}" 
                         class="w-full h-56 object-cover group-hover:scale-110 transition-transform duration-500"
                         loading="lazy"
                         onerror="this.src='${this.PLACEHOLDER_IMAGE}'">
                    ${project.featured ? '<div class="absolute top-3 left-3 bg-primary-600 text-white text-xs px-3 py-1 rounded-full">In Evidenza</div>' : ''}
                    <div class="absolute top-3 right-3 bg-black bg-opacity-60 text-white text-xs px-3 py-1 rounded-full">
                        ${project.daysLeft} giorni
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400 uppercase tracking-wider">${this.getCategoryDisplayName(project.category)}</span>
                        <div class="flex items-center space-x-1 text-xs text-gray-500">
                            <i class="ri-heart-line"></i>
                            <span>${project.backers}</span>
                        </div>
                    </div>
                    <h3 id="project-title-${project.id}" class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-primary-600 transition-colors">
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
                            <div class="text-gray-500 text-xs">qualità</div>
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
                                <div class="text-xs text-gray-500">Creatore</div>
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

    /**
     * Get display name for category
     */
    getCategoryDisplayName(category) {
        const displayNames = {
            album: 'Album',
            strumenti: 'Strumenti',
            concerti: 'Concerti',
            educazione: 'Educazione',
            grafico: 'Grafico',
            industriale: 'Industriale',
            'ux-ui': 'UX/UI',
            interni: 'Interni',
            cortometraggi: 'Cortometraggi',
            documentari: 'Documentari',
            animazione: 'Animazione',
            feature: 'Lungometraggi'
        };

        return displayNames[category] || category.charAt(0).toUpperCase() + category.slice(1);
    }

    /**
     * Update load more button functionality
     */
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

    /**
     * Handle project view
     */
    viewProject(projectId) {
        window.location.href = `/frontend/projects/${this.categoryName}-project-${projectId}.html`;
    }

    /**
     * Show error message
     */
    showError(message) {
        const projectsGrid = document.getElementById('projectsGrid');
        if (projectsGrid) {
            projectsGrid.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i class="ri-error-warning-line text-6xl text-red-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Errore</h3>
                    <p class="text-gray-600 dark:text-gray-400">${message}</p>
                </div>
            `;
        }
    }
}

// Export for use in other modules
window.GenericCategoryManager = GenericCategoryManager;
