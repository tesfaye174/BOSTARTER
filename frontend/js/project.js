/**
 * BOSTARTER Projects Manager
 */
(function (window, document) {
    'use strict';

    const ProjectsManager = {
        config: {
            apiBaseUrl: '/BOSTARTER/backend/api/',
            itemsPerPage: 12,
            refreshInterval: 30000
        },

        state: {
            currentPage: 1,
            totalPages: 1,
            loading: false,
            filter: 'all',
            sortBy: 'created_at',
            sortOrder: 'desc'
        },

        elements: {
            container: null,
            grid: null,
            pagination: null,
            filters: null,
            searchInput: null,
            sortSelect: null,
            loadingIndicator: null
        },
        /**
         * Inizializza il projects manager
         */
        init() {
            this.findElements();
            this.setupEventListeners();
            this.loadProjects();
            this.setupAutoRefresh();
        },
        /**
         * Trova gli elementi nel DOM
         */
        findElements() {
            this.elements.container = document.querySelector('.projects-container, [data-projects-container]');
            this.elements.grid = document.querySelector('.projects-grid, [data-projects-grid]');
            this.elements.pagination = document.querySelector('.projects-pagination, [data-projects-pagination]');
            this.elements.filters = document.querySelectorAll('.project-filter, [data-project-filter]');
            this.elements.searchInput = document.querySelector('.projects-search, [data-projects-search]');
            this.elements.sortSelect = document.querySelector('.projects-sort, [data-projects-sort]');
            this.elements.loadingIndicator = document.querySelector('.projects-loading, [data-projects-loading]');
        },
        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Filtri
            this.elements.filters.forEach(filter => {
                filter.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleFilterChange(filter.dataset.filter || filter.getAttribute('data-filter'));
                });
            });
            // Ricerca
            if (this.elements.searchInput) {
                this.elements.searchInput.addEventListener('input', this.debounce((e) => {
                    this.handleSearch(e.target.value);
                }, 500));
            }
            // Ordinamento
            if (this.elements.sortSelect) {
                this.elements.sortSelect.addEventListener('change', (e) => {
                    this.handleSortChange(e.target.value);
                });
            }
            // Paginazione (delegated events)
            if (this.elements.pagination) {
                this.elements.pagination.addEventListener('click', (e) => {
                    const pageBtn = e.target.closest('[data-page]');
                    if (pageBtn) {
                        e.preventDefault();
                        const page = parseInt(pageBtn.dataset.page);
                        if (page && page !== this.state.currentPage) {
                            this.loadProjects(page);
                        }
                    }
                });
            }
        },
        /**
         * Carica i progetti
         */
        async loadProjects(page = 1) {
            if (this.state.loading) return;
            this.state.loading = true;
            this.state.currentPage = page;
            this.showLoading();
            try {
                const params = new URLSearchParams({
                    page: page,
                    limit: this.config.itemsPerPage,
                    filter: this.state.filter,
                    sort: this.state.sortBy,
                    order: this.state.sortOrder
                });
                if (this.elements.searchInput && this.elements.searchInput.value) {
                    params.append('search', this.elements.searchInput.value);
                }
                const response = await fetch(`${this.config.apiBaseUrl}projects_compliant.php?${params}`);
                const data = await response.json();
                if (data.success) {
                    this.renderProjects(data.projects || []);
                    this.renderPagination(data.pagination || {});
                } else {
                    this.showError(data.message || 'Errore nel caricamento dei progetti');
                }
            } catch (error) {
                console.error('Errore caricamento progetti:', error);
                this.showError('Errore di connessione');
            } finally {
                this.state.loading = false;
                this.hideLoading();
            }
        },
        /**
         * Renderizza i progetti
         */
        renderProjects(projects) {
            if (!this.elements.grid) return;
            if (projects.length === 0) {
                this.elements.grid.innerHTML = `
                    <div class="no-projects">
                        <div class="text-center py-12">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Nessun progetto trovato
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">
                                Prova a modificare i filtri di ricerca
                            </p>
                        </div>
                    </div>
                `;
                return;
            }
            const projectsHTML = projects.map(project => this.renderProjectCard(project)).join('');
            this.elements.grid.innerHTML = projectsHTML;
            // Aggiungi event listeners ai progetti
            this.setupProjectEvents();
        },
        /**
         * Renderizza una card progetto
         */
        renderProjectCard(project) {
            const progress = project.obiettivo_finanziario > 0
                ? Math.min(100, (project.totale_finanziamenti / project.obiettivo_finanziario) * 100)
                : 0;
            const daysLeft = this.calculateDaysLeft(project.data_scadenza);
            const statusClass = this.getStatusClass(project.stato);
            return `
                <div class="project-card ${statusClass}" data-project-id="${project.id}">
                    <div class="project-image">
                        <img src="${project.immagine_principale || './images/placeholder-project.jpg'}" 
                             alt="${project.titolo}" 
                             loading="lazy">
                        <div class="project-status">${this.getStatusLabel(project.stato)}</div>
                    </div>
                    <div class="project-content">
                        <h3 class="project-title">${project.titolo}</h3>
                        <p class="project-description">${this.truncateText(project.descrizione, 100)}</p>
                        <div class="project-stats">
                            <div class="stat">
                                <span class="stat-label">Raccolti</span>
                                <span class="stat-value">${this.formatCurrency(project.totale_finanziamenti)}</span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Obiettivo</span>
                                <span class="stat-value">${this.formatCurrency(project.obiettivo_finanziario)}</span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Giorni rimanenti</span>
                                <span class="stat-value">${daysLeft}</span>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progress}%"></div>
                        </div>
                        <div class="progress-text">${progress.toFixed(1)}% completato</div>
                        <div class="project-actions">
                            <button class="btn btn-primary view-project" data-project-id="${project.id}">
                                Visualizza
                            </button>
                            ${project.can_edit ? `
                                <button class="btn btn-secondary edit-project" data-project-id="${project.id}">
                                    Modifica
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        },
        /**
         * Setup eventi per i progetti
         */
        setupProjectEvents() {
            // View project
            document.querySelectorAll('.view-project').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const projectId = e.target.dataset.projectId;
                    this.viewProject(projectId);
                });
            });
            // Edit project
            document.querySelectorAll('.edit-project').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const projectId = e.target.dataset.projectId;
                    this.editProject(projectId);
                });
            });
        },
        /**
         * Renderizza la paginazione
         */
        renderPagination(pagination) {
            if (!this.elements.pagination || !pagination.total_pages) return;
            this.state.totalPages = pagination.total_pages;
            const currentPage = pagination.current_page || this.state.currentPage;
            let paginationHTML = '';
            // Previous button
            if (currentPage > 1) {
                paginationHTML += `
                    <button class="pagination-btn" data-page="${currentPage - 1}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15,18 9,12 15,6"></polyline>
                        </svg>
                        Precedente
                    </button>
                `;
            }
            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(pagination.total_pages, currentPage + 2);
            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === currentPage;
                paginationHTML += `
                    <button class="pagination-btn ${isActive ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `;
            }
            // Next button
            if (currentPage < pagination.total_pages) {
                paginationHTML += `
                    <button class="pagination-btn" data-page="${currentPage + 1}">
                        Successiva
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,18 15,12 9,6"></polyline>
                        </svg>
                    </button>
                `;
            }
            this.elements.pagination.innerHTML = paginationHTML;
        },
        /**
         * Gestisci cambio filtro
         */
        handleFilterChange(filter) {
            this.state.filter = filter;
            this.state.currentPage = 1;
            // Aggiorna UI filtri
            this.elements.filters.forEach(f => {
                f.classList.remove('active');
            });
            const activeFilter = document.querySelector(`[data-filter="${filter}"]`);
            if (activeFilter) {
                activeFilter.classList.add('active');
            }
            this.loadProjects(1);
        },
        /**
         * Gestisci ricerca
         */
        handleSearch(query) {
            this.state.currentPage = 1;
            this.loadProjects(1);
        },
        /**
         * Gestisci cambio ordinamento
         */
        handleSortChange(sortValue) {
            const [sortBy, sortOrder] = sortValue.split('-');
            this.state.sortBy = sortBy;
            this.state.sortOrder = sortOrder;
            this.state.currentPage = 1;
            this.loadProjects(1);
        },
        /**
         * Visualizza progetto
         */
        viewProject(projectId) {
            window.location.href = `./view.php?id=${projectId}`;
        },
        /**
         * Modifica progetto
         */
        editProject(projectId) {
            window.location.href = `./edit-view.php?id=${projectId}`;
        },
        /**
         * Mostra loading
         */
        showLoading() {
            if (this.elements.loadingIndicator) {
                this.elements.loadingIndicator.style.display = 'block';
            }
            if (this.elements.grid) {
                this.elements.grid.style.opacity = '0.5';
            }
        },
        /**
         * Nascondi loading
         */
        hideLoading() {
            if (this.elements.loadingIndicator) {
                this.elements.loadingIndicator.style.display = 'none';
            }
            if (this.elements.grid) {
                this.elements.grid.style.opacity = '1';
            }
        },
        /**
         * Mostra errore
         */
        showError(message) {
            if (this.elements.grid) {
                this.elements.grid.innerHTML = `
                    <div class="error-message">
                        <div class="text-center py-12">
                            <h3 class="text-lg font-semibold text-red-600 mb-2">Errore</h3>
                            <p class="text-gray-600">${message}</p>
                            <button class="btn btn-primary mt-4" onclick="location.reload()">
                                Riprova
                            </button>
                        </div>
                    </div>
                `;
            }
        },
        /**
         * Setup auto refresh
         */
        setupAutoRefresh() {
            setInterval(() => {
                if (!this.state.loading) {
                    this.loadProjects(this.state.currentPage);
                }
            }, this.config.refreshInterval);
        },
        /**
         * Utility functions
         */
        calculateDaysLeft(deadline) {
            const now = new Date();
            const end = new Date(deadline);
            const diffTime = end - now;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            if (diffDays < 0) return 'Scaduto';
            if (diffDays === 0) return 'Ultimo giorno';
            return `${diffDays} giorni`;
        },
        getStatusClass(status) {
            const statusClasses = {
                'draft': 'status-draft',
                'published': 'status-published',
                'funded': 'status-funded',
                'completed': 'status-completed',
                'cancelled': 'status-cancelled'
            };
            return statusClasses[status] || '';
        },
        getStatusLabel(status) {
            const statusLabels = {
                'draft': 'Bozza',
                'published': 'Pubblicato',
                'funded': 'Finanziato',
                'completed': 'Completato',
                'cancelled': 'Annullato'
            };
            return statusLabels[status] || status;
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('it-IT', {
                style: 'currency',
                currency: 'EUR'
            }).format(amount);
        },
        truncateText(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        },
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
        }
    };
    // Inizializzazione automatica
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ProjectsManager.init();
        });
    } else {
        ProjectsManager.init();
    }
    // Esporta il projects manager
    window.BOSTARTERProjects = ProjectsManager;
})(window, document);



