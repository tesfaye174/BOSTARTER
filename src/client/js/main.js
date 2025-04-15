// Funzioni per la gestione della dashboard

// Carica e visualizza i top creator
async function loadTopCreators() {
    const container = document.getElementById('top-creators');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        const creators = await CachedAPI.getTopCreators();
        
        if (!creators.length) {
            container.innerHTML = '<p class="text-muted">Nessun creator trovato</p>';
            return;
        }

        const html = creators.map(creator => {
            const reliabilityClass = creator.reliability >= 90 ? 'text-success' :
                                   creator.reliability >= 70 ? 'text-primary' :
                                   'text-warning';
            const badge = creator.reliability >= 90 ? '<i class="bi bi-patch-check-fill text-success ms-2" title="Creator Verificato"></i>' : '';
            
            return `
            <div class="creator-card p-3 mb-3 rounded-3 bg-white shadow-sm hover-lift">
                <div class="d-flex align-items-center mb-2">
                    <div class="creator-avatar me-3">
                        <img src="${creator.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(creator.nickname)}" 
                             alt="${creator.nickname}" 
                             class="rounded-circle" 
                             width="48" height="48">
                    </div>
                    <div class="creator-info flex-grow-1">
                        <h6 class="mb-0 d-flex align-items-center">
                            ${creator.nickname}
                            ${badge}
                        </h6>
                        <small class="text-muted">Progetti completati: ${creator.completed_projects}</small>
                    </div>
                    <div class="creator-stats text-end">
                        <div class="reliability ${reliabilityClass} fw-bold">
                            ${creator.reliability}%
                        </div>
                        <small class="text-muted">Affidabilità</small>
                    </div>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar ${reliabilityClass}" 
                         role="progressbar" 
                         style="width: ${creator.reliability}%" 
                         aria-valuenow="${creator.reliability}" 
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
        showError('top-creators', 'Errore nel caricamento dei top creator');
        setTimeout(() => loadTopCreators(), 5000); // Retry after 5 seconds
    }
}

// Carica e visualizza i progetti vicini al completamento
async function loadNearCompletionProjects() {
    const container = document.getElementById('near-completion');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        const projects = await CachedAPI.getNearCompletionProjects();

        if (!projects.length) {
            container.innerHTML = '<p class="text-muted">Nessun progetto trovato</p>';
            return;
        }

        const html = projects.map(project => `
            <div class="mb-3">
                <h6 class="mb-1">${project.name}</h6>
                <div class="progress mb-2">
                    <div class="progress-bar" role="progressbar" 
                         style="width: ${(project.total_funding / project.budget) * 100}%">
                    </div>
                </div>
                <small class="text-muted">
                    €${project.total_funding.toLocaleString()} / €${project.budget.toLocaleString()}
                </small>
            </div>
        `).join('');

        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading near completion projects:', error);
        showError('near-completion', 'Errore nel caricamento dei progetti');
    }
}

// Carica e visualizza i top finanziatori
async function loadTopFunders() {
    const container = document.getElementById('top-funders');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        const funders = await CachedAPI.getTopFunders();

        if (!funders.length) {
            container.innerHTML = '<p class="text-muted">Nessun finanziatore trovato</p>';
            return;
        }

        const html = funders.map(funder => `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>${funder.nickname}</span>
                <span class="badge bg-success">€${funder.total_funding.toLocaleString()}</span>
            </div>
        `).join('');

        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading top funders:', error);
        showError('top-funders', 'Errore nel caricamento dei finanziatori');
    }
}

// Carica e visualizza i progetti in evidenza
async function loadFeaturedProjects() {
    const container = document.getElementById('featured-projects');
    container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    try {
        const projects = await CachedAPI.getFeaturedProjects();

        if (!projects.length) {
            container.innerHTML = '<div class="col-12"><p class="text-center text-muted">Nessun progetto in evidenza</p></div>';
            return;
        }

        const html = projects.map(project => `
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    ${project.photo_url ? 
                        `<img src="${project.photo_url}" class="card-img-top" alt="${project.name}">` : 
                        '<div class="card-img-top bg-light text-center py-5">No image</div>'}
                    <div class="card-body">
                        <h5 class="card-title">${project.name}</h5>
                        <p class="card-text">${project.description.substring(0, 100)}...</p>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: ${(project.total_funding / project.budget) * 100}%">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                €${project.total_funding.toLocaleString()} / €${project.budget.toLocaleString()}
                            </small>
                            <a href="project.html?id=${project.id}" class="btn btn-primary btn-sm">Vedi Progetto</a>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading featured projects:', error);
        showError('featured-projects', 'Errore nel caricamento dei progetti in evidenza');
    }
}

// Funzione helper per mostrare errori
function showError(containerId, message) {
    const container = document.getElementById(containerId);
    container.innerHTML = `<div class="alert alert-danger">${message}</div>`;
}

// Inizializza la dashboard
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Carica le statistiche
        await loadStats();
        
        // Carica i progetti in evidenza
        await loadFeaturedProjects();
        
        // Carica i progetti recenti
        await loadRecentProjects();
        
        // Inizializza gli event listener
        initEventListeners();
    } catch (error) {
        console.error('Errore durante l\'inizializzazione:', error);
    }
});

async function loadStats() {
    try {
        const stats = await API.getStats();
        
        // Aggiorna i top creator
        const topCreatorsContainer = document.getElementById('top-creators');
        if (topCreatorsContainer) {
            topCreatorsContainer.innerHTML = stats.topCreators.map(creator => `
                <div class="d-flex align-items-center mb-2">
                    <img src="${creator.avatar}" alt="${creator.name}" class="rounded-circle me-2" width="32" height="32">
                    <div>
                        <div class="fw-bold">${creator.name}</div>
                        <small class="text-muted">${creator.projects} progetti</small>
                    </div>
                </div>
            `).join('');
        }

        // Aggiorna i progetti vicini al completamento
        const nearCompletionContainer = document.getElementById('near-completion');
        if (nearCompletionContainer) {
            nearCompletionContainer.innerHTML = stats.nearCompletion.map(project => `
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">${project.title}</span>
                        <span class="badge bg-success">${project.progress}%</span>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: ${project.progress}%"></div>
                    </div>
                </div>
            `).join('');
        }

        // Aggiorna i top finanziatori
        const topFundersContainer = document.getElementById('top-funders');
        if (topFundersContainer) {
            topFundersContainer.innerHTML = stats.topFunders.map(funder => `
                <div class="d-flex align-items-center mb-2">
                    <img src="${funder.avatar}" alt="${funder.name}" class="rounded-circle me-2" width="32" height="32">
                    <div>
                        <div class="fw-bold">${funder.name}</div>
                        <small class="text-muted">€${funder.totalDonations}</small>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Errore nel caricamento delle statistiche:', error);
    }
}

async function loadRecentProjects() {
    try {
        const projects = await API.getProjects(1, 6);
        const container = document.getElementById('recent-projects');
        
        if (container) {
            container.innerHTML = projects.map(project => `
                <div class="col-md-4">
                    <div class="card h-100 hover-lift glass-card animate__animated animate__fadeIn">
                        <img src="${project.image}" class="card-img-top" alt="${project.title}">
                        <div class="card-body">
                            <h5 class="card-title">${project.title}</h5>
                            <p class="card-text text-truncate">${project.description}</p>
                            <div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" style="width: ${project.progress}%">
                                    ${project.progress}%
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">€${project.raised} raccolti</span>
                                <a href="project.html?id=${project.id}" class="btn btn-primary btn-sm">Scopri di più</a>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Errore nel caricamento dei progetti recenti:', error);
    }
}

function initEventListeners() {
    // Gestione del logout
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await Auth.logout();
                window.location.href = 'index.html';
            } catch (error) {
                console.error('Errore durante il logout:', error);
            }
        });
    }

    // Gestione della ricerca
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const query = document.getElementById('search-input').value;
            window.location.href = `projects.html?search=${encodeURIComponent(query)}`;
        });
    }
}

// Funzione per il logout
async function logout() {
    try {
        await Auth.logout();
        window.location.href = 'index.html';
    } catch (error) {
        console.error('Errore durante il logout:', error);
    }
}