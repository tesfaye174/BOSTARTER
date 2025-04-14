// Gestione progetti e filtri per BOSTARTER
document.addEventListener('DOMContentLoaded', () => {
    // Elementi DOM
    const projectsGrid = document.getElementById('projects-grid');
    const projectTemplate = document.getElementById('project-card-template');
    const categoryFilter = document.getElementById('category-filter');
    const sortFilter = document.getElementById('sort-filter');
    
    // Stato dell'applicazione
    let projects = [];
    let filteredProjects = [];
    let currentFilters = {
        category: 'all',
        sort: 'newest'
    };
    
    // Inizializzazione
    init();
    
    // Funzione di inizializzazione
    async function init() {
        // Aggiungi event listeners ai filtri
        categoryFilter.addEventListener('change', handleFilterChange);
        sortFilter.addEventListener('change', handleFilterChange);
        
        // Carica i progetti
        await loadProjects();
    }
    
    // Carica i progetti dall'API
    async function loadProjects() {
        try {
            const response = await API.getProjects();
            if (response.success) {
                projects = response.data;
                applyFilters();
            } else {
                showError('Impossibile caricare i progetti');
            }
        } catch (error) {
            console.error('Errore nel caricamento dei progetti:', error);
            showError('Si è verificato un errore durante il caricamento dei progetti');
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
        // Filtra per categoria
        if (currentFilters.category === 'all') {
            filteredProjects = [...projects];
        } else {
            filteredProjects = projects.filter(project => project.category === currentFilters.category);
        }
        
        // Ordina i progetti
        switch (currentFilters.sort) {
            case 'newest':
                filteredProjects.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                break;
            case 'popular':
                filteredProjects.sort((a, b) => b.backers - a.backers);
                break;
            case 'ending':
                filteredProjects.sort((a, b) => new Date(a.end_date) - new Date(b.end_date));
                break;
        }
        
        // Visualizza i progetti filtrati
        renderProjects();
    }
    
    // Visualizza i progetti nella griglia
    function renderProjects() {
        // Rimuovi il placeholder di caricamento
        projectsGrid.innerHTML = '';
        
        if (filteredProjects.length === 0) {
            showNoProjectsMessage();
            return;
        }
        
        // Aggiungi i progetti alla griglia con animazione
        filteredProjects.forEach((project, index) => {
            const projectCard = createProjectCard(project);
            
            // Aggiungi animazione di entrata
            projectCard.style.opacity = '0';
            projectCard.style.transform = 'translateY(20px)';
            
            projectsGrid.appendChild(projectCard);
            
            // Animazione con ritardo progressivo
            setTimeout(() => {
                projectCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                projectCard.style.opacity = '1';
                projectCard.style.transform = 'translateY(0)';
            }, 50 * index);
        });
    }
    
    // Crea una card di progetto
    function createProjectCard(project) {
        const card = projectTemplate.content.cloneNode(true);
        
        // Imposta l'immagine del progetto
        const cardImage = card.querySelector('.card-img-top');
        cardImage.src = project.image_url || 'img/placeholder-project.jpg';
        cardImage.alt = project.title;
        
        // Imposta il badge della categoria
        const categoryBadge = card.querySelector('.category-badge');
        categoryBadge.textContent = getCategoryLabel(project.category);
        categoryBadge.className = `category-badge ${project.category}`;
        
        // Imposta il titolo e la descrizione
        card.querySelector('.card-title').textContent = project.title;
        card.querySelector('.card-text').textContent = project.description;
        
        // Calcola la percentuale di finanziamento
        const fundingPercentage = Math.min(Math.round((project.current_amount / project.goal_amount) * 100), 100);
        
        // Imposta la barra di progresso
        const progressBar = card.querySelector('.progress-bar');
        progressBar.style.width = `${fundingPercentage}%`;
        progressBar.setAttribute('aria-valuenow', fundingPercentage);
        
        // Classe per il colore della barra di progresso
        if (fundingPercentage >= 100) {
            progressBar.classList.add('bg-success');
        } else if (fundingPercentage >= 75) {
            progressBar.classList.add('bg-info');
        } else if (fundingPercentage >= 50) {
            progressBar.classList.add('bg-primary');
        } else if (fundingPercentage >= 25) {
            progressBar.classList.add('bg-warning');
        } else {
            progressBar.classList.add('bg-danger');
        }
        
        // Imposta le informazioni di finanziamento
        card.querySelector('.funded').textContent = `${fundingPercentage}% finanziato`;
        
        // Calcola i giorni rimanenti
        const daysLeft = getDaysLeft(project.end_date);
        card.querySelector('.days-left').textContent = `${daysLeft} giorni rimanenti`;
        
        // Imposta le informazioni del creatore
        const creatorAvatar = card.querySelector('.creator-avatar');
        creatorAvatar.src = project.creator_avatar || 'img/placeholder-avatar.jpg';
        creatorAvatar.alt = project.creator_name;
        
        card.querySelector('.creator-name').textContent = project.creator_name;
        
        // Aggiungi event listener per il click sulla card
        const projectCard = card.querySelector('.project-card');
        projectCard.addEventListener('click', () => {
            window.location.href = `project-details.html?id=${project.id}`;
        });
        
        return card.firstElementChild;
    }
    
    // Mostra un messaggio quando non ci sono progetti
    function showNoProjectsMessage() {
        const messageEl = document.createElement('div');
        messageEl.className = 'col-12 text-center py-5';
        messageEl.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-search fs-1 text-muted mb-3"></i>
                <h4>Nessun progetto trovato</h4>
                <p class="text-muted">Prova a modificare i filtri di ricerca</p>
            </div>
        `;
        projectsGrid.appendChild(messageEl);
    }
    
    // Mostra un messaggio di errore
    function showError(message) {
        const errorEl = document.createElement('div');
        errorEl.className = 'col-12 text-center py-5';
        errorEl.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3"></i>
                <h4>Errore</h4>
                <p class="text-muted">${message}</p>
                <button class="btn btn-primary mt-3" id="retry-button">
                    <i class="bi bi-arrow-clockwise me-2"></i>Riprova
                </button>
            </div>
        `;
        projectsGrid.innerHTML = '';
        projectsGrid.appendChild(errorEl);
        
        document.getElementById('retry-button').addEventListener('click', loadProjects);
    }
    
    // Funzioni di utilità
    function getCategoryLabel(category) {
        const categories = {
            'tech': 'Tecnologia',
            'art': 'Arte',
            'games': 'Giochi'
        };
        return categories[category] || 'Altro';
    }
    
    function getDaysLeft(endDate) {
        const end = new Date(endDate);
        const now = new Date();
        const diffTime = end - now;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays > 0 ? diffDays : 0;
    }
});

// Gestione dei progetti
class ProjectsManager {
    static async loadProjects(filters = {}) {
        try {
            const queryParams = new URLSearchParams(filters);
            const response = await fetch(`/api/projects?${queryParams}`, {
                headers: {
                    'Authorization': `Bearer ${Auth.getToken()}`
                }
            });

            if (!response.ok) {
                throw new Error('Errore nel caricamento dei progetti');
            }

            return await response.json();
        } catch (error) {
            console.error('Errore:', error);
            throw error;
        }
    }

    static async loadProjectDetails(projectId) {
        try {
            const response = await fetch(`/api/projects/${projectId}`, {
                headers: {
                    'Authorization': `Bearer ${Auth.getToken()}`
                }
            });

            if (!response.ok) {
                throw new Error('Errore nel caricamento dei dettagli del progetto');
            }

            return await response.json();
        } catch (error) {
            console.error('Errore:', error);
            throw error;
        }
    }

    static async createProject(projectData) {
        try {
            const response = await fetch('/api/projects', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${Auth.getToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(projectData)
            });

            if (!response.ok) {
                throw new Error('Errore nella creazione del progetto');
            }

            return await response.json();
        } catch (error) {
            console.error('Errore:', error);
            throw error;
        }
    }

    static async updateProject(projectId, projectData) {
        try {
            const response = await fetch(`/api/projects/${projectId}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${Auth.getToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(projectData)
            });

            if (!response.ok) {
                throw new Error('Errore nell\'aggiornamento del progetto');
            }

            return await response.json();
        } catch (error) {
            console.error('Errore:', error);
            throw error;
        }
    }

    static async deleteProject(projectId) {
        try {
            const response = await fetch(`/api/projects/${projectId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${Auth.getToken()}`
                }
            });

            if (!response.ok) {
                throw new Error('Errore nell\'eliminazione del progetto');
            }

            return await response.json();
        } catch (error) {
            console.error('Errore:', error);
            throw error;
        }
    }
}

// Gestione dell'interfaccia utente
class ProjectsUI {
    static async initialize() {
        // Carica i progetti iniziali
        await this.loadProjects();

        // Inizializza i filtri
        this.initializeFilters();

        // Inizializza la paginazione
        this.initializePagination();
    }

    static async loadProjects(filters = {}) {
        try {
            const projects = await ProjectsManager.loadProjects(filters);
            this.updateProjectsGrid(projects);
        } catch (error) {
            ErrorHandler.showError('Errore nel caricamento dei progetti');
        }
    }

    static updateProjectsGrid(projects) {
        const grid = document.getElementById('projects-grid');
        grid.innerHTML = '';

        projects.forEach(project => {
            const card = this.createProjectCard(project);
            grid.appendChild(card);
        });
    }

    static createProjectCard(project) {
        const template = document.getElementById('project-card-template');
        const card = template.content.cloneNode(true);

        // Popola la card con i dati del progetto
        card.querySelector('.card-title').textContent = project.title;
        card.querySelector('.card-text').textContent = project.description;
        card.querySelector('.progress-bar').style.width = `${project.progress}%`;
        card.querySelector('.funded-text').textContent = `${project.funded}€`;
        card.querySelector('.days-left-text').textContent = `${project.daysLeft} giorni`;
        card.querySelector('.creator-name').textContent = project.creator;
        card.querySelector('.category-badge').textContent = project.category;

        return card;
    }

    static initializeFilters() {
        const categoryFilter = document.getElementById('category-filter');
        const sortFilter = document.getElementById('sort-filter');

        categoryFilter.addEventListener('change', () => this.applyFilters());
        sortFilter.addEventListener('change', () => this.applyFilters());
    }

    static applyFilters() {
        const filters = {
            category: document.getElementById('category-filter').value,
            sort: document.getElementById('sort-filter').value
        };

        this.loadProjects(filters);
    }

    static initializePagination() {
        // Implementa la logica di paginazione
    }
}

// Inizializzazione
document.addEventListener('DOMContentLoaded', () => {
    ProjectsUI.initialize();
});