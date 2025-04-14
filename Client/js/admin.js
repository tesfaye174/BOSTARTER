// Funzioni per la gestione del pannello amministrativo

// Verifica l'autenticazione dell'amministratore
async function checkAdminAuth() {
    try {
        const user = await Auth.getCurrentUser();
        if (!user || user.role !== 'admin') {
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Error checking admin auth:', error);
        window.location.href = 'login.html';
    }
}

// Gestione della navigazione tra le sezioni
function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.admin-section');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetSection = e.target.getAttribute('data-section');

            navLinks.forEach(l => l.classList.remove('active'));
            e.target.classList.add('active');

            sections.forEach(section => {
                section.classList.add('d-none');
                if (section.id === `${targetSection}-section`) {
                    section.classList.remove('d-none');
                }
            });
        });
    });
}

// Carica le statistiche della dashboard
async function loadDashboardStats() {
    try {
        const stats = await API.getAdminStats();
        document.getElementById('total-users').textContent = stats.totalUsers;
        document.getElementById('active-projects').textContent = stats.activeProjects;
        document.getElementById('total-funding').textContent = `€${stats.totalFunding.toLocaleString()}`;
        document.getElementById('completed-projects').textContent = stats.completedProjects;
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

// Carica la lista degli utenti
async function loadUsers() {
    try {
        const users = await API.getUsers();
        const tbody = document.getElementById('users-table-body');
        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>${user.nickname}</td>
                <td>${user.email}</td>
                <td><span class="badge bg-${getRoleBadgeColor(user.role)}">${user.role}</span></td>
                <td><span class="badge bg-${getStatusBadgeColor(user.status)}">${user.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary me-1" onclick="editUser(${user.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

// Carica la lista dei progetti
async function loadProjects() {
    try {
        const projects = await API.getProjects();
        const tbody = document.getElementById('projects-table-body');
        tbody.innerHTML = projects.map(project => `
            <tr>
                <td>${project.id}</td>
                <td>${project.name}</td>
                <td>${project.creator_nickname}</td>
                <td>€${project.budget.toLocaleString()}</td>
                <td><span class="badge bg-${getProjectStatusBadgeColor(project.status)}">${project.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary me-1" onclick="editProject(${project.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteProject(${project.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading projects:', error);
    }
}

// Carica i progetti in evidenza
async function loadFeaturedProjects() {
    try {
        const projects = await API.getFeaturedProjects();
        const container = document.getElementById('featured-projects-container');
        container.innerHTML = projects.map(project => `
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
                            <button class="btn btn-danger btn-sm" onclick="removeFeatured(${project.id})">
                                Rimuovi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading featured projects:', error);
    }
}

// Funzioni helper per i badge
function getRoleBadgeColor(role) {
    const colors = {
        'admin': 'danger',
        'creator': 'success',
        'user': 'primary'
    };
    return colors[role] || 'secondary';
}

function getStatusBadgeColor(status) {
    const colors = {
        'active': 'success',
        'suspended': 'warning',
        'banned': 'danger'
    };
    return colors[status] || 'secondary';
}

function getProjectStatusBadgeColor(status) {
    const colors = {
        'draft': 'secondary',
        'active': 'primary',
        'completed': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}

// Gestione del form utente
async function saveUser(event) {
    event.preventDefault();
    const userId = document.getElementById('user-id').value;
    const userData = {
        nickname: document.getElementById('user-nickname').value,
        email: document.getElementById('user-email').value,
        role: document.getElementById('user-role').value,
        status: document.getElementById('user-status').value
    };

    try {
        if (userId) {
            await API.updateUser(userId, userData);
        } else {
            await API.createUser(userData);
        }
        bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
        loadUsers();
    } catch (error) {
        console.error('Error saving user:', error);
    }
}

// Gestione del form progetto
async function saveProject(event) {
    event.preventDefault();
    const projectId = document.getElementById('project-id').value;
    const projectData = {
        name: document.getElementById('project-name').value,
        description: document.getElementById('project-description').value,
        budget: parseFloat(document.getElementById('project-budget').value),
        creator_id: document.getElementById('project-creator').value,
        status: document.getElementById('project-status').value,
        featured: document.getElementById('project-featured').checked
    };

    try {
        if (projectId) {
            await API.updateProject(projectId, projectData);
        } else {
            await API.createProject(projectData);
        }
        bootstrap.Modal.getInstance(document.getElementById('projectModal')).hide();
        loadProjects();
        if (projectData.featured) {
            loadFeaturedProjects();
        }
    } catch (error) {
        console.error('Error saving project:', error);
    }
}

// Inizializzazione della pagina
document.addEventListener('DOMContentLoaded', async () => {
    await checkAdminAuth();
    setupNavigation();
    loadDashboardStats();
    loadUsers();
    loadProjects();
    loadFeaturedProjects();

    // Event listeners per i form
    document.getElementById('save-user-btn').addEventListener('click', saveUser);
    document.getElementById('save-project-btn').addEventListener('click', saveProject);
    document.getElementById('logout-btn').addEventListener('click', () => {
        Auth.logout();
        window.location.href = 'login.html';
    });
});

// Gestione della dashboard amministrativa
document.addEventListener('DOMContentLoaded', function() {
    // Inizializzazione
    initAdminDashboard();
    loadAdminStats();
    loadProjects();
    loadUsers();
    initModals();
});

// Funzione per inizializzare la dashboard
function initAdminDashboard() {
    // Verifica se l'utente è un amministratore
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user || user.role !== 'admin') {
        window.location.href = '/login.html';
        return;
    }

    // Inizializza il menu di navigazione
    initNavigation();
}

// Funzione per caricare le statistiche
async function loadAdminStats() {
    try {
        const response = await fetch('/api/admin/stats', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento delle statistiche');
        }

        const stats = await response.json();
        updateStatsCards(stats);
    } catch (error) {
        showError('Errore nel caricamento delle statistiche');
        console.error('Errore:', error);
    }
}

// Funzione per aggiornare le card delle statistiche
function updateStatsCards(stats) {
    document.getElementById('totalProjects').textContent = stats.totalProjects;
    document.getElementById('totalUsers').textContent = stats.totalUsers;
    document.getElementById('activeProjects').textContent = stats.activeProjects;
    document.getElementById('totalRevenue').textContent = formatCurrency(stats.totalRevenue);
}

// Funzione per caricare i progetti
async function loadProjects() {
    try {
        const response = await fetch('/api/admin/projects', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento dei progetti');
        }

        const projects = await response.json();
        updateProjectsTable(projects);
    } catch (error) {
        showError('Errore nel caricamento dei progetti');
        console.error('Errore:', error);
    }
}

// Funzione per aggiornare la tabella dei progetti
function updateProjectsTable(projects) {
    const tbody = document.querySelector('#projectsTable tbody');
    tbody.innerHTML = '';

    projects.forEach(project => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${project.id}</td>
            <td>${project.title}</td>
            <td>${project.owner}</td>
            <td>${formatCurrency(project.budget)}</td>
            <td>${project.status}</td>
            <td>
                <button class="btn btn-sm btn-outline-light" onclick="editProject(${project.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-light" onclick="deleteProject(${project.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Funzione per caricare gli utenti
async function loadUsers() {
    try {
        const response = await fetch('/api/admin/users', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento degli utenti');
        }

        const users = await response.json();
        updateUsersTable(users);
    } catch (error) {
        showError('Errore nel caricamento degli utenti');
        console.error('Errore:', error);
    }
}

// Funzione per aggiornare la tabella degli utenti
function updateUsersTable(users) {
    const tbody = document.querySelector('#usersTable tbody');
    tbody.innerHTML = '';

    users.forEach(user => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>${user.role}</td>
            <td>${user.status}</td>
            <td>
                <button class="btn btn-sm btn-outline-light" onclick="editUser(${user.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-light" onclick="deleteUser(${user.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Funzione per inizializzare i modali
function initModals() {
    // Inizializza i modali di Bootstrap
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        new bootstrap.Modal(modal);
    });
}

// Funzione per formattare la valuta
function formatCurrency(amount) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Funzione per mostrare gli errori
function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container').prepend(alertDiv);
}

// Funzione per inizializzare la navigazione
function initNavigation() {
    const user = JSON.parse(localStorage.getItem('user'));
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('data-role') && link.getAttribute('data-role') !== user.role) {
            link.style.display = 'none';
        }
    });
}

// Funzione per il logout
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/login.html';
}

// Funzioni di gestione utenti
async function editUser(userId) {
    try {
        const user = await API.getUser(userId);
        const form = document.getElementById('user-form');
        
        // Popola il form
        form.querySelector('#user-id').value = user.id;
        form.querySelector('#user-nickname').value = user.nickname;
        form.querySelector('#user-email').value = user.email;
        form.querySelector('#user-role').value = user.role;
        form.querySelector('#user-status').value = user.status;
        
        // Mostra il modal
        const modal = new bootstrap.Modal(document.getElementById('userModal'));
        modal.show();
    } catch (error) {
        showError('Errore nel caricamento dei dati utente');
    }
}

async function deleteUser(userId) {
    if (confirm('Sei sicuro di voler eliminare questo utente?')) {
        try {
            const response = await API.deleteUser(userId);
            if (response.success) {
                loadUsersTable();
            } else {
                showError('Errore nell\'eliminazione dell\'utente');
            }
        } catch (error) {
            showError('Errore di connessione');
        }
    }
}

// Funzioni di gestione progetti
async function editProject(projectId) {
    try {
        const project = await API.getProject(projectId);
        const form = document.getElementById('project-form');
        
        // Popola il form
        form.querySelector('#project-id').value = project.id;
        form.querySelector('#project-name').value = project.name;
        form.querySelector('#project-description').value = project.description;
        form.querySelector('#project-budget').value = project.budget;
        form.querySelector('#project-creator').value = project.creator;
        form.querySelector('#project-status').value = project.status;
        form.querySelector('#project-featured').checked = project.featured;
        
        // Mostra il modal
        const modal = new bootstrap.Modal(document.getElementById('projectModal'));
        modal.show();
    } catch (error) {
        showError('Errore nel caricamento dei dati progetto');
    }
}

async function deleteProject(projectId) {
    if (confirm('Sei sicuro di voler eliminare questo progetto?')) {
        try {
            const response = await API.deleteProject(projectId);
            if (response.success) {
                loadProjectsTable();
            } else {
                showError('Errore nell\'eliminazione del progetto');
            }
        } catch (error) {
            showError('Errore di connessione');
        }
    }
}

// Funzione di logout
document.getElementById('logout-btn').addEventListener('click', function() {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
    window.location.href = 'login.html';
});

// Funzione di utilità per mostrare errori
function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container').prepend(alert);
}