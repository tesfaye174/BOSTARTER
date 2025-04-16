import { API } from './api.js';
import { Auth, ErrorHandler } from './auth.js'; // Assuming ErrorHandler is here
import { CachedAPI } from './cache.js'; // Import CachedAPI

// Funzioni per la gestione della dashboard

// Carica e visualizza i top creator
async function loadTopCreators() {
    const container = document.getElementById('top-creators');
    if (!container) return; // Exit if container not found
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        const creators = await CachedAPI.getTopCreators();
        
        if (!creators || !creators.length) {
            container.innerHTML = '<p class="text-muted">Nessun creator trovato</p>';
            return;
        }

        const html = creators.map(creator => {
            // Assuming creator object structure from API.getTopCreators response
            const reliabilityClass = creator.reliability >= 90 ? 'text-success' :
                                   creator.reliability >= 70 ? 'text-primary' :
                                   'text-warning';
            const badge = creator.reliability >= 90 ? '<i class="bi bi-patch-check-fill text-success ms-2" title="Creator Verificato"></i>' : '';
            const avatarUrl = creator.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(creator.nickname)}&background=random`;
            
            return `
            <div class="creator-card p-3 mb-3 rounded-3 bg-white shadow-sm hover-lift">
                <div class="d-flex align-items-center mb-2">
                    <div class="creator-avatar me-3">
                        <img src="${avatarUrl}" 
                             alt="${creator.nickname}" 
                             class="rounded-circle" 
                             width="48" height="48">
                    </div>
                    <div class="creator-info flex-grow-1">
                        <h6 class="mb-0 d-flex align-items-center">
                            ${creator.nickname}
                            ${badge}
                        </h6>
                        <small class="text-muted">Progetti completati: ${creator.completed_projects || 0}</small>
                    </div>
                    <div class="creator-stats text-end">
                        <div class="reliability ${reliabilityClass} fw-bold">
                            ${creator.reliability || 0}%
                        </div>
                        <small class="text-muted">Affidabilità</small>
                    </div>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar ${reliabilityClass}" 
                         role="progressbar" 
                         style="width: ${creator.reliability || 0}%" 
                         aria-valuenow="${creator.reliability || 0}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
            `;
        }).join('');

        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading top creators:', error);
        // Use ErrorHandler
        ErrorHandler.showError('Errore nel caricamento dei top creator', 'top-creators-error'); // Use a dedicated error element ID if available
        // Optional: Retry logic removed for simplicity, can be added back if needed
        // setTimeout(() => loadTopCreators(), 5000); 
    }
}

// Carica e visualizza i progetti vicini al completamento
async function loadNearCompletionProjects() {
    const container = document.getElementById('near-completion');
    if (!container) return;
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        const projects = await CachedAPI.getNearCompletionProjects();

        if (!projects || !projects.length) {
            container.innerHTML = '<p class="text-muted">Nessun progetto vicino al completamento</p>';
            return;
        }

        const html = projects.map(project => {
            const progress = project.budget > 0 ? (project.total_funding / project.budget) * 100 : 0;
            return `
            <div class="mb-3">
                <h6 class="mb-1">${project.name}</h6>
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: ${progress.toFixed(0)}%" 
                         aria-valuenow="${progress.toFixed(0)}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
                <small class="text-muted">
                    €${(project.total_funding || 0).toLocaleString()} / €${(project.budget || 0).toLocaleString()}
                </small>
            </div>
        `}).join('');

        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading near completion projects:', error);
        ErrorHandler.showError('Errore nel caricamento dei progetti vicini al completamento', 'near-completion-error');
    }
}

// Carica e visualizza i top finanziatori
async function loadTopFunders() {
    const container = document.getElementById('top-funders');
    if (!container) return;
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        const funders = await CachedAPI.getTopFunders();

        if (!funders || !funders.length) {
            container.innerHTML = '<p class="text-muted">Nessun finanziatore trovato</p>';
            return;
        }

        const html = funders.map(funder => `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>${funder.nickname}</span>
                <span class="badge bg-success">€${(funder.total_funding || 0).toLocaleString()}</span>
            </div>
        `).join('');

        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading top funders:', error);
        ErrorHandler.showError('Errore nel caricamento dei top finanziatori', 'top-funders-error');
    }
}

// Carica e visualizza i progetti in evidenza
async function loadFeaturedProjects() {
    const container = document.getElementById('featured-projects');
    if (!container) return;
    container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        // Assuming CachedAPI.getFeaturedProjects returns an array of projects
        const projects = await CachedAPI.getFeaturedProjects(); 

        if (!projects || !projects.length) {
            container.innerHTML = '<div class="col-12"><p class="text-center text-muted">Nessun progetto in evidenza</p></div>';
            return;
        }

        const html = projects.map(project => {
            const progress = project.budget > 0 ? (project.total_funding / project.budget) * 100 : 0;
            const imageUrl = project.photo_url || 'img/placeholder.png'; // Use a placeholder image
            const description = project.description ? project.description.substring(0, 100) + (project.description.length > 100 ? '...' : '') : 'Nessuna descrizione.';

            return `
            <div class="col-md-4 mb-4">
                <div class="card h-100 hover-lift glass-card animate__animated animate__fadeIn">
                    <img src="${imageUrl}" class="card-img-top" alt="${project.name}" style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${project.name}</h5>
                        <p class="card-text flex-grow-1">${description}</p>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: ${progress.toFixed(0)}%" 
                                 aria-valuenow="${progress.toFixed(0)}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <small class="text-muted">
                                €${(project.total_funding || 0).toLocaleString()} / €${(project.budget || 0).toLocaleString()}
                            </small>
                            <a href="project.html?id=${project.id}" class="btn btn-primary btn-sm">Vedi Progetto</a>
                        </div>
                    </div>
                </div>
            </div>
        `}).join('');

        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading featured projects:', error);
        ErrorHandler.showError('Errore nel caricamento dei progetti in evidenza', 'featured-projects-error');
    }
}

// Rimosso showError helper, useremo ErrorHandler

// Inizializza la dashboard
document.addEventListener('DOMContentLoaded', async () => {
    // Check authentication status first
    if (!Auth.isAuthenticated()) {
        // Optionally redirect to login or show login elements
        // For now, assume public dashboard elements can load
        console.log('User not authenticated. Loading public dashboard elements.');
    } else {
        // User is authenticated, maybe load user-specific elements
        console.log('User authenticated.');
        // Example: Load user profile info
        // await loadUserProfile(); 
    }

    // Load dashboard components using CachedAPI or API
    // Use Promise.all for parallel loading
    try {
        await Promise.all([
            loadTopCreators(),
            loadNearCompletionProjects(),
            loadTopFunders(),
            loadFeaturedProjects(),
            loadRecentProjects() // Load recent projects as well
            // loadStats() // Removed as it seems redundant with individual calls
        ]);
    } catch (error) {
        // Although individual functions handle errors, catch potential top-level issues
        console.error('Error loading dashboard components:', error);
        ErrorHandler.showError('Errore generale nel caricamento della dashboard.', 'dashboard-error'); // Use a general error area
    }

    // Inizializza gli event listener dopo il caricamento del contenuto
    initEventListeners();
});

// Rimosso loadStats() - le sue parti sono caricate da funzioni specifiche con cache

async function loadRecentProjects() {
    const container = document.getElementById('recent-projects');
    if (!container) return;
    container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        // Fetch recent projects using API.getProjects (no cache needed for 'recent')
        const result = await API.getProjects(1, 6, { sortBy: 'creation_date', order: 'DESC' }); // Assuming API supports sorting
        const projects = result.projects || result; // Adjust based on API response structure

        if (!projects || !projects.length) {
            container.innerHTML = '<div class="col-12"><p class="text-center text-muted">Nessun progetto recente trovato</p></div>';
            return;
        }

        const html = projects.map(project => {
            const progress = project.budget > 0 ? (project.total_funding / project.budget) * 100 : 0;
            const imageUrl = project.photo_url || 'img/placeholder.png';
            const description = project.description ? project.description.substring(0, 100) + (project.description.length > 100 ? '...' : '') : 'Nessuna descrizione.';

            return `
                <div class="col-md-4 mb-4">
                    <div class="card h-100 hover-lift glass-card animate__animated animate__fadeIn">
                        <img src="${imageUrl}" class="card-img-top" alt="${project.name}" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">${project.name}</h5>
                            <p class="card-text flex-grow-1">${description}</p>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" style="width: ${progress.toFixed(0)}%" 
                                     aria-valuenow="${progress.toFixed(0)}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="text-muted">€${(project.total_funding || 0).toLocaleString()} raccolti</span>
                                <a href="project.html?id=${project.id}" class="btn btn-primary btn-sm">Scopri di più</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        container.innerHTML = html;
    } catch (error) {
        console.error('Errore nel caricamento dei progetti recenti:', error);
        ErrorHandler.showError('Errore nel caricamento dei progetti recenti.', 'recent-projects-error');
    }
}

function initEventListeners() {
    // Gestione del logout
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            Auth.logout(); // Auth.logout already handles errors and redirection
        });
    }

    // Gestione della ricerca
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = `projects.html?search=${encodeURIComponent(query)}`;
                }
            }
        });
    }
    
    // Add other event listeners as needed
}

// Rimuovi la funzione logout duplicata
// async function logout() { ... }

// Ensure necessary classes/functions are available globally or exported/imported correctly
// Example: Make sure Auth, API, CachedAPI, ErrorHandler are accessible
// window.Auth = Auth; // If needed globally