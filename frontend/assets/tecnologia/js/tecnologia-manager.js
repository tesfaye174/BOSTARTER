// filepath: c:\xampp\htdocs\BOSTARTER\frontend\assets\tecnologia\js\tecnologia-manager.js
// Technology Page Manager
class TechnologyPageManager extends BaseCategoryManager {
    constructor() {
        super('tecnologia');
    }

    /**
     * Override to load category-specific data
     */
    loadCategorySpecificData() {
        this.loadTechnologies();
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
            showNotification('Errore nel caricamento dei progetti tecnologici', 'error');
        }
    } async loadTechnologies() {
        try {
            await this.simulateApiDelay(800);
            this.technologies = this.generateMockTechnologies();
            this.renderTechnologies();
        } catch (error) {
            // Silent error handling for technology loading
        }
    } generateMockProjects() {
        // Use centralized mock data generator
        return window.MockDataGenerator.generateProjects('tecnologia', 24);
    } generateMockTechnologies() {
        // Use centralized mock data generator
        return window.MockDataGenerator.generateTechnologies(8);
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
                    <i class="ri-code-line text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Nessun progetto tecnologico trovato</h3>
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

    renderTechnologies() {
        const technologiesGrid = document.getElementById('technologiesGrid');
        if (!technologiesGrid) return;

        technologiesGrid.innerHTML = this.technologies.map(tech => this.createTechnologyCard(tech)).join('');
    }

    createProjectCard(project) {
        return `
            <article class="project-card group cursor-pointer" data-project-id="${project.id}" role="article" tabindex="0" aria-labelledby="project-title-${project.id}">
                <div class="relative overflow-hidden">
                    <img src="${project.image}" alt="Immagine del progetto ${project.title}" 
                         class="w-full h-56 object-cover group-hover:scale-110 transition-transform duration-500"
                         loading="lazy"
                         onerror="this.src='/frontend/images/placeholder-tech.jpg'">
                    ${project.featured ? '<div class="absolute top-3 left-3 tech-badge">In Evidenza</div>' : ''}
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
                            <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-500" 
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
                            <div class="font-bold text-blue-600">${project.innovation}%</div>
                            <div class="text-gray-500 text-xs">innovazione</div>
                        </div>
                    </div>
                    
                    <!-- Creator -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                ${project.creator.charAt(0)}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">${project.creator}</div>
                                <div class="text-xs text-gray-500">Sviluppatore</div>
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

    createTechnologyCard(tech) {
        return `
            <div class="technology-card group cursor-pointer" data-tech-id="${tech.id}">
                <div class="relative mb-4">
                    <img src="${tech.icon}" alt="Icona ${tech.name}" 
                         class="w-16 h-16 mx-auto object-contain group-hover:scale-110 transition-transform duration-300"
                         loading="lazy"
                         onerror="this.src='/frontend/images/default-tech.svg'">
                    ${tech.trending ? '<div class="absolute -top-1 -right-1 w-6 h-6 bg-green-400 rounded-full flex items-center justify-center"><i class="ri-fire-fill text-xs text-green-900"></i></div>' : ''}
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">${tech.name}</h3>
                <p class="text-blue-600 dark:text-blue-400 text-sm font-medium mb-3">${tech.description}</p>
                <div class="grid grid-cols-2 gap-4 text-xs text-gray-600 dark:text-gray-400">
                    <div class="text-center">
                        <div class="font-semibold text-gray-900 dark:text-white">${tech.projects}</div>
                        <div>Progetti</div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-gray-900 dark:text-white">${tech.popularity}%</div>
                        <div>Popolarità</div>
                    </div>
                </div>
                <button class="mt-4 w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                    Esplora Tech
                </button>
            </div>
        `;
    }

    getCategoryName(category) {
        const names = {
            'app-mobile': 'App Mobile',
            'web-app': 'Web App',
            'ai-ml': 'AI/ML',
            'iot': 'IoT',
            'blockchain': 'Blockchain',
            'gaming': 'Gaming'
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
        window.location.href = `/frontend/projects/tech-project-${projectId}.html`;
    }

    viewTechnology(techId) {
        window.location.href = `/frontend/technologies/tech-${techId}.html`;
    } async simulateApiDelay(ms) {
        // Use centralized API delay simulation
        return window.MockDataGenerator.simulateApiDelay(ms);
    }
}

// Initialize the technology page manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new TechnologyPageManager();
});

// Global error handling
window.addEventListener('error', (e) => {
    // Silent error handling for production
});

// Service Worker registration for PWA support
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/frontend/sw.js')
        .then(registration => {
            // Service worker registered successfully
        })
        .catch(error => {
            // Service worker registration failed
        });
}
