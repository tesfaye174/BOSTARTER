// Gestione progetti e filtri per BOSTARTER
import { API } from './api.js';
import { ErrorHandler } from './auth.js'; // Assuming ErrorHandler is exported from auth.js

document.addEventListener('DOMContentLoaded', () => {
    // Elementi DOM
    const projectsGrid = document.getElementById('projects-grid');
    const projectTemplate = document.getElementById('project-card-template');
    const categoryFilter = document.getElementById('category-filter');
    const sortFilter = document.getElementById('sort-filter');
    const loadingPlaceholder = document.getElementById('loading-placeholder'); // Use ID for direct access
    const errorContainerId = 'projects-error'; // ID for the error message container

    // Stato dell'applicazione
    let projects = [];
    let filteredProjects = [];
    let currentFilters = {
        category: 'all',
        sort: 'newest'
        // search: '' // Add search term if needed
    };

    // Funzione di inizializzazione
    async function init() {
        if (!projectsGrid || !projectTemplate || !categoryFilter || !sortFilter || !loadingPlaceholder) {
            console.error('Elementi DOM necessari per la pagina progetti non trovati.');
            // Display error in a dedicated container if available, otherwise fallback
            const errorContainer = document.getElementById(errorContainerId) || document.body;
            ErrorHandler.showError('Errore nell'inizializzazione della pagina. Elementi mancanti.', errorContainerId);
            return;
        }

        // Aggiungi event listeners ai filtri
        categoryFilter.addEventListener('change', handleFilterChange);
        sortFilter.addEventListener('change', handleFilterChange);

        // Carica i progetti
        await loadProjects();
    }

    // Carica i progetti dall'API
    async function loadProjects() {
        showLoading(true);
        ErrorHandler.hideError(errorContainerId); // Hide previous errors
        try {
            // Assuming API.getProjects returns an object like { projects: [...] }
            // Adjust page/limit as needed or implement pagination
            const response = await API.getProjects(1, 100);
            projects = response.projects || []; // Ensure projects is always an array
            applyFilters();
        } catch (error) {
            console.error('Errore nel caricamento dei progetti:', error);
            ErrorHandler.showError('Si è verificato un errore durante il caricamento dei progetti.', errorContainerId);
            renderErrorState(projectsGrid, 'Impossibile caricare i progetti.');
        } finally {
            showLoading(false);
        }
    }

    // Gestisce il cambio dei filtri
    function handleFilterChange(event) {
        const { id, value } = event.target;

        // Aggiorna lo stato dei filtri
        if (id === 'category-filter') {
            currentFilters.category = value;
        } else if (id === 'sort-filter') {
            currentFilters.sort = value;
        }

        // Applica i filtri
        applyFilters();
    }

    // Applica i filtri ai progetti
    function applyFilters() {
        let tempProjects = [...projects];

        // Filtra per categoria
        if (currentFilters.category !== 'all') {
            tempProjects = tempProjects.filter(project => project.category === currentFilters.category);
        }

        // Ordina i progetti
        switch (currentFilters.sort) {
            case 'newest':
                // Use creation_date, fallback to 0 if null/undefined
                tempProjects.sort((a, b) => new Date(b.creation_date || 0) - new Date(a.creation_date || 0));
                break;
            case 'popular':
                // Sort by total_funding, fallback to 0
                tempProjects.sort((a, b) => (b.total_funding || 0) - (a.total_funding || 0));
                break;
            case 'ending':
                // Sort by end_date, fallback to 0
                tempProjects.sort((a, b) => new Date(a.end_date || 0) - new Date(b.end_date || 0));
                break;
        }

        filteredProjects = tempProjects;

        // Visualizza i progetti filtrati
        renderProjects();
    }

    // Visualizza i progetti nella griglia
    function renderProjects() {
        // Pulisci la griglia prima di aggiungere nuovi elementi
        projectsGrid.innerHTML = '';

        if (filteredProjects.length === 0) {
            renderEmptyState(projectsGrid, 'Nessun progetto trovato con i filtri selezionati.', 'bi-search');
            return;
        }

        // Aggiungi i progetti alla griglia
        filteredProjects.forEach((project, index) => {
            const projectCard = createProjectCard(project);
            if (projectCard) {
                // Add animation classes (consider using CSS for animations)
                projectCard.classList.add('animate__animated', 'animate__fadeInUp');
                projectCard.style.animationDelay = `${index * 0.05}s`;
                projectsGrid.appendChild(projectCard);
            }
        });
    }

    // Crea una card di progetto
    function createProjectCard(project) {
        if (!projectTemplate) return null;
        const templateContent = projectTemplate.content.cloneNode(true);
        const cardElement = templateContent.querySelector('.project-card-wrapper'); // Use a wrapper class
        if (!cardElement) return null;

        // Imposta l'immagine del progetto
        const cardImage = cardElement.querySelector('.card-img-top');
        if (cardImage) {
            cardImage.src = project.photo_url || 'img/placeholder.png'; // Consistent placeholder
            cardImage.alt = project.name || 'Immagine progetto';
        }

        // Imposta il badge della categoria
        const categoryBadge = cardElement.querySelector('.category-badge');
        if (categoryBadge) {
            categoryBadge.textContent = project.category || 'N/D'; // Handle missing category
        }

        // Imposta il titolo e la descrizione
        const titleEl = cardElement.querySelector('.card-title');
        if (titleEl) titleEl.textContent = project.name || 'Senza titolo';

        const descriptionEl = cardElement.querySelector('.card-text');
        if (descriptionEl) {
            const description = project.description ? project.description.substring(0, 100) + (project.description.length > 100 ? '...' : '') : 'Nessuna descrizione disponibile.';
            descriptionEl.textContent = description;
        }

        // Calcola la percentuale di finanziamento
        const budget = parseFloat(project.budget) || 0;
        const totalFunding = parseFloat(project.total_funding) || 0;
        const fundingPercentage = budget > 0 ? Math.min(Math.round((totalFunding / budget) * 100), 100) : 0;

        // Imposta la barra di progresso
        const progressBar = cardElement.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = `${fundingPercentage}%`;
            progressBar.setAttribute('aria-valuenow', fundingPercentage);
            // Remove existing color classes and add the correct one
            progressBar.classList.remove('bg-success', 'bg-info', 'bg-primary', 'bg-warning', 'bg-danger');
            if (fundingPercentage >= 100) progressBar.classList.add('bg-success');
            else if (fundingPercentage >= 50) progressBar.classList.add('bg-primary');
            else if (fundingPercentage >= 25) progressBar.classList.add('bg-warning');
            else progressBar.classList.add('bg-danger');
        }

        // Imposta le informazioni di finanziamento
        const fundedTextEl = cardElement.querySelector('.funded-text');
        if (fundedTextEl) fundedTextEl.textContent = `€${totalFunding.toLocaleString('it-IT')} / €${budget.toLocaleString('it-IT')}`;

        // Calcola i giorni rimanenti
        const daysLeft = getDaysLeft(project.end_date);
        const daysLeftTextEl = cardElement.querySelector('.days-left-text');
        if (daysLeftTextEl) {
            if (daysLeft < 0) {
                 daysLeftTextEl.textContent = 'Terminato';
            } else if (daysLeft === 0) {
                 daysLeftTextEl.textContent = 'Ultimo giorno';
            } else {
                 daysLeftTextEl.textContent = `${daysLeft} ${daysLeft === 1 ? 'giorno rimasto' : 'giorni rimasti'}`;
            }
        }

        // Imposta le informazioni del creatore (assuming creator info is available)
        const creatorAvatar = cardElement.querySelector('.creator-avatar');
        if (creatorAvatar) creatorAvatar.src = project.creator?.avatar_url || 'img/avatar-placeholder.png';
        const creatorNameEl = cardElement.querySelector('.creator-name');
        if (creatorNameEl) creatorNameEl.textContent = project.creator?.nickname || 'Sconosciuto';

        // Aggiungi event listener per il click sulla card (link al dettaglio)
        const projectLink = cardElement.querySelector('a.project-link'); // Target the link directly
        if (projectLink) {
            projectLink.href = `project.html?id=${project.id}`; // Correct detail page name
        }

        return cardElement;
    }

    // Helper to show/hide loading indicator
    function showLoading(isLoading) {
        if (loadingPlaceholder) {
            loadingPlaceholder.style.display = isLoading ? 'block' : 'none';
        }
        // Disable filters while loading
        if (categoryFilter) categoryFilter.disabled = isLoading;
        if (sortFilter) sortFilter.disabled = isLoading;
    }

    // Helper to render empty state
    function renderEmptyState(container, message, iconClass = 'bi-info-circle') {
        if (container) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="empty-state">
                        <i class="bi ${iconClass} fs-1 text-muted mb-3"></i>
                        <h4>${message}</h4>
                    </div>
                </div>`;
        }
    }

    // Helper to render error state
    function renderErrorState(container, message) {
        if (container) {
            // Use the same structure as empty state for consistency
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle-fill fs-1 text-danger mb-3"></i>
                        <h4>Errore</h4>
                        <p class="text-muted">${message}</p>
                    </div>
                </div>`;
        }
    }

    // Helper to calculate days left
    function getDaysLeft(endDateString) {
        if (!endDateString) return -1; // Indicate invalid or missing date
        try {
            const endDate = new Date(endDateString);
            if (isNaN(endDate.getTime())) return -1; // Invalid date format
            const now = new Date();
            // Compare dates only, ignore time
            endDate.setHours(0, 0, 0, 0);
            now.setHours(0, 0, 0, 0);

            const diffTime = endDate - now;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays;
        } catch (e) {
            console.error("Error parsing date:", endDateString, e);
            return -1; // Return -1 on error
        }
    }

    // Inizializzazione
    init();
});