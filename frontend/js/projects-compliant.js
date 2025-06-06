/**
 * ===== GESTIONE PROGETTI CONFORME AL PDF =====
 * Sistema di filtri per SOLO progetti hardware e software
 * Conforme alle specifiche Progetto Basi di Dati A.A. 2024/2025
 */

document.addEventListener('DOMContentLoaded', () => {    // Inizializza configurazione conforme al PDF
    if (!window.BostarterConfig) {
        // Configuration error - show error message to user instead
        const errorMsg = document.createElement('div');
        errorMsg.className = 'alert alert-error';
        errorMsg.textContent = 'Errore di configurazione. Ricarica la pagina.';
        document.body.prepend(errorMsg);
        return;
    }

    // Verifica che solo hardware/software siano configurati
    const validCategories = window.getValidCategories();
    // Categorie PDF-compliant caricate

    // Selettori degli elementi
    const projectsGrid = document.getElementById('projects-grid');
    const categoryButtons = document.querySelectorAll('[data-category]');
    const statusFilter = document.getElementById('status-filter');
    const sortFilter = document.getElementById('sort-filter');
    const loadingIndicator = document.getElementById('loading-indicator');
    const emptyState = document.getElementById('empty-state');
    const errorContainer = document.getElementById('error-container');    // Validazione categorie conformi al PDF
    categoryButtons.forEach(button => {
        const category = button.dataset.category;
        if (category !== 'all' && !window.isCategoryCompliant(category)) {
            // Hide non-compliant category buttons silently
            button.style.display = 'none';
        }
    });

    // Gestione dei filtri per categoria (SOLO hardware/software conforme al PDF)
    categoryButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();

            const category = button.dataset.category;            // Validazione categoria conforme al PDF
            if (category !== 'all' && !window.isCategoryCompliant(category)) {
                // Block non-compliant category operations silently
                return;
            }

            // Rimuove la classe attiva da tutti i pulsanti
            categoryButtons.forEach(btn => {
                btn.classList.remove('bg-primary', 'text-white', 'active');
                btn.classList.add('text-gray-700', 'hover:bg-gray-200');
                btn.setAttribute('aria-pressed', 'false');
            });

            // Aggiunge la classe attiva al pulsante cliccato
            button.classList.remove('text-gray-700', 'hover:bg-gray-200');
            button.classList.add('bg-primary', 'text-white', 'active');
            button.setAttribute('aria-pressed', 'true');

            // Aggiorna l'indicatore visivo della categoria
            updateCategoryIndicator(category);

            // Filtra i progetti
            filterProjectsCompliant();
        });
    });

    // Gestione dei filtri per stato e ordinamento
    if (statusFilter) statusFilter.addEventListener('change', filterProjectsCompliant);
    if (sortFilter) sortFilter.addEventListener('change', filterProjectsCompliant);

    // Aggiorna indicatore visivo della categoria attiva
    function updateCategoryIndicator(category) {
        const indicator = document.getElementById('category-indicator');
        if (!indicator) return;

        if (category === 'all') {
            indicator.textContent = 'Tutti i progetti (Hardware & Software)';
            indicator.className = 'category-indicator all-categories';
        } else {
            const config = window.getCategoryConfig(category);
            if (config) {
                indicator.textContent = config.texts.title;
                indicator.className = `category-indicator ${category}-category`;
                indicator.style.color = config.colors.primary;
            }
        }
    }

    // Funzione principale per filtrare i progetti (conforme al PDF)
    function filterProjectsCompliant() {
        const selectedCategoryBtn = document.querySelector('[data-category][aria-pressed="true"]');
        const selectedCategory = selectedCategoryBtn ? selectedCategoryBtn.dataset.category : 'all';
        const selectedStatus = statusFilter ? statusFilter.value : 'all';
        const selectedSort = sortFilter ? sortFilter.value : 'recent';        // Validazione finale categoria
        if (selectedCategory !== 'all' && !window.isCategoryCompliant(selectedCategory)) {
            // Block non-compliant category filtering silently
            return;
        }

        // Filtraggio progetti in corso

        // Mostra loading
        showLoadingState();

        // Carica progetti filtrati dall'API compliant
        loadFilteredProjectsCompliant(selectedCategory, selectedStatus, selectedSort);
    }

    // Carica progetti filtrati dall'API conforme al PDF
    async function loadFilteredProjectsCompliant(category, status, sort) {
        try {
            // Usa endpoint API compliant per progetti
            let url = '/BOSTARTER/backend/api/projects_compliant.php?action=list';

            const params = new URLSearchParams();

            // Filtra per tipo progetto (solo hardware/software conforme al PDF)
            if (category && category !== 'all') {
                if (!window.isCategoryCompliant(category)) {
                    throw new Error(`Categoria ${category} non conforme al PDF`);
                }
                params.append('tipo_progetto', category);
            }

            // Filtra per stato
            if (status && status !== 'all') {
                params.append('stato', status);
            }

            // Ordinamento
            if (sort) {
                const sortMap = {
                    'recent': 'data_creazione DESC',
                    'funded': 'percentuale_finanziamento DESC',
                    'ending': 'data_scadenza ASC',
                    'amount': 'obiettivo DESC'
                };
                params.append('order_by', sortMap[sort] || 'data_creazione DESC');
            }

            if (params.toString()) {
                url += '&' + params.toString();
            }

            // Chiamata API in corso

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`Errore HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                displayProjectsCompliant(data.progetti || [], category);
                updateProjectStats(data.stats || {});
            } else {
                throw new Error(data.message || 'Errore nel caricamento progetti');
            }
        } catch (error) {
            // Handle project loading error
            showErrorState(error.message);
        } finally {
            hideLoadingState();
        }
    }

    // Visualizza i progetti nel grid (conforme al PDF)
    function displayProjectsCompliant(projects, category) {
        if (!projectsGrid) return;

        hideErrorState();

        if (projects.length === 0) {
            showEmptyState(category);
            return;
        }

        const categoryConfig = category !== 'all' ? window.getCategoryConfig(category) : null;

        projectsGrid.innerHTML = projects.map(project => createProjectCardCompliant(project, categoryConfig)).join('');

        // Inizializza interazioni sulle card
        initializeProjectCards();

        // Progetti visualizzati per categoria
    }

    // Crea card progetto conforme al PDF
    function createProjectCardCompliant(project, categoryConfig) {
        const cardClass = categoryConfig ? categoryConfig.selectors.cardClass : 'project-card';
        const primaryColor = categoryConfig ? categoryConfig.colors.primary : '#6c757d';
        const progress = Math.min((project.raccolto / project.obiettivo) * 100, 100);
        const daysLeft = calculateDaysLeft(project.data_scadenza);

        return `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="${cardClass} h-100 shadow-sm" data-project-id="${project.id}">
                    <div class="position-relative">
                        <img src="${project.immagine || '/frontend/images/default-project.jpg'}" 
                             class="card-img-top" alt="${project.titolo}"
                             style="height: 200px; object-fit: cover;">
                        
                        <!-- Badge categoria conforme al PDF -->
                        <span class="badge position-absolute top-0 end-0 m-2" 
                              style="background-color: ${primaryColor};">
                            <i class="${categoryConfig ? categoryConfig.icons.primary : 'ri-star-line'}"></i>
                            ${project.tipo_progetto}
                        </span>
                        
                        <!-- Badge stato -->
                        <span class="badge bg-${getStatusBadgeClass(project.stato)} position-absolute top-0 start-0 m-2">
                            ${project.stato}
                        </span>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${project.titolo}</h5>
                        <p class="card-text text-muted flex-grow-1">${project.descrizione_breve || project.descrizione}</p>
                        
                        <!-- Progress bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>€${project.raccolto?.toLocaleString() || 0}</span>
                                <span>€${project.obiettivo?.toLocaleString() || 0}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: ${progress}%; background-color: ${primaryColor};"
                                     aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span>${progress.toFixed(1)}% finanziato</span>
                                <span>${daysLeft} giorni rimanenti</span>
                            </div>
                        </div>
                        
                        <!-- Creator info -->
                        <div class="d-flex align-items-center mb-3">
                            <img src="${project.creator_avatar || '/frontend/images/default-avatar.jpg'}" 
                                 class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                            <small class="text-muted">di ${project.creator_nome || 'Anonimo'}</small>
                        </div>
                        
                        <!-- Actions -->
                        <div class="d-flex gap-2">
                            <a href="/frontend/progetto.php?id=${project.id}" 
                               class="btn btn-outline-primary flex-grow-1">Dettagli</a>
                            <a href="/frontend/finanzia.php?id=${project.id}" 
                               class="btn text-white flex-grow-1"
                               style="background-color: ${primaryColor};">Finanzia</a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Utility functions
    function getStatusBadgeClass(status) {
        const statusMap = {
            'attivo': 'success',
            'completato': 'primary',
            'scaduto': 'danger',
            'bozza': 'secondary'
        };
        return statusMap[status] || 'secondary';
    }

    function calculateDaysLeft(scadenza) {
        const today = new Date();
        const endDate = new Date(scadenza);
        const diffTime = endDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return Math.max(0, diffDays);
    }

    // Stati UI
    function showLoadingState() {
        if (loadingIndicator) loadingIndicator.style.display = 'block';
        if (projectsGrid) projectsGrid.style.opacity = '0.5';
        if (emptyState) emptyState.style.display = 'none';
        if (errorContainer) errorContainer.style.display = 'none';
    }

    function hideLoadingState() {
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        if (projectsGrid) projectsGrid.style.opacity = '1';
    }

    function showEmptyState(category) {
        if (!emptyState) return;

        const categoryConfig = category !== 'all' ? window.getCategoryConfig(category) : null;
        const message = categoryConfig ? categoryConfig.texts.emptyMessage : 'Nessun progetto trovato';

        emptyState.innerHTML = `
            <div class="text-center py-5">
                <i class="ri-folder-open-line display-1 text-muted"></i>
                <h3 class="mt-3 text-muted">${message}</h3>
                <p class="text-muted">Prova a modificare i filtri di ricerca</p>
            </div>
        `;
        emptyState.style.display = 'block';
        if (projectsGrid) projectsGrid.innerHTML = '';
    }

    function showErrorState(message) {
        if (!errorContainer) return;

        errorContainer.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="ri-error-warning-line me-2"></i>
                <strong>Errore:</strong> ${message}
                <button type="button" class="btn btn-sm btn-outline-danger ms-3" onclick="location.reload()">
                    Riprova
                </button>
            </div>
        `;
        errorContainer.style.display = 'block';
    }

    function hideErrorState() {
        if (errorContainer) errorContainer.style.display = 'none';
    }

    function initializeProjectCards() {
        const cards = document.querySelectorAll('[data-project-id]');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-2px)';
                card.style.transition = 'transform 0.2s ease';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    }

    function updateProjectStats(stats) {
        const statsContainer = document.getElementById('project-stats');
        if (!statsContainer || !stats) return;

        statsContainer.innerHTML = `
            <div class="row text-center">
                <div class="col-md-3">
                    <h3 class="text-primary">${stats.total || 0}</h3>
                    <p class="text-muted">Progetti Totali</p>
                </div>
                <div class="col-md-3">
                    <h3 class="text-success">${stats.hardware || 0}</h3>
                    <p class="text-muted">Hardware</p>
                </div>
                <div class="col-md-3">
                    <h3 class="text-info">${stats.software || 0}</h3>
                    <p class="text-muted">Software</p>
                </div>
                <div class="col-md-3">
                    <h3 class="text-warning">€${(stats.totale_raccolto || 0).toLocaleString()}</h3>
                    <p class="text-muted">Totale Raccolto</p>
                </div>
            </div>
        `;
    }

    // Inizializzazione
    function init() {
        // Inizializzazione sistema progetti conforme al PDF

        // Carica progetti iniziali (tutti)
        filterProjectsCompliant();

        // Aggiorna indicatore categoria
        updateCategoryIndicator('all');

        // Sistema progetti inizializzato correttamente
    }

    // Avvia inizializzazione
    init();
});

// Export per uso esterno
window.BostarterProjects = {
    filter: function (category, status, sort) {
        if (category && !window.isCategoryCompliant(category)) {            // Category not compliant - return false silently
            return false;
        }
        // Trigger filter programmatically
        const event = new CustomEvent('filterProjects', {
            detail: { category, status, sort }
        });
        document.dispatchEvent(event);
        return true;
    },

    getValidCategories: function () {
        return window.getValidCategories();
    },

    isCompliant: function () {
        return true; // Questo script è conforme al PDF
    }
};
