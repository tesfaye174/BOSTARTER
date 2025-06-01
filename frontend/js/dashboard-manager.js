// ===== DASHBOARD MANAGEMENT FUNCTIONALITY =====

class DashboardManager {
    constructor() {
        this.currentSection = 'overview';
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupTheme();
        this.loadUserData();
        this.hideLoadingOverlay();

        // Handle hash navigation
        const hash = window.location.hash.substring(1) || 'overview';
        this.showSection(hash);
    }

    setupEventListeners() {
        // Navigation links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.currentTarget.getAttribute('href').substring(1);
                this.showSection(section);
                window.location.hash = section;
            });
        });

        // Theme toggles
        document.getElementById('theme-toggle')?.addEventListener('click', () => {
            this.toggleTheme();
        });

        document.getElementById('theme-toggle-mobile')?.addEventListener('click', () => {
            this.toggleTheme();
        });

        // Mobile menu
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', this.toggleMobileMenu.bind(this));
        }

        // User menu
        document.getElementById('user-menu-btn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const userMenu = document.getElementById('user-menu');
            if (userMenu) {
                userMenu.classList.toggle('hidden');
            }
        });

        // Logout buttons
        document.getElementById('logout-btn')?.addEventListener('click', () => {
            this.handleLogout();
        });

        document.getElementById('logout-menu')?.addEventListener('click', () => {
            this.handleLogout();
        });

        // Close dropdowns on outside click
        document.addEventListener('click', () => {
            document.getElementById('user-menu')?.classList.add('hidden');
        });

        // Escape key handling
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.getElementById('user-menu')?.classList.add('hidden');
                const sidebar = document.querySelector('.dashboard-sidebar');
                if (sidebar) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Hash change
        window.addEventListener('hashchange', () => {
            const hash = window.location.hash.substring(1) || 'overview';
            this.showSection(hash);
        });
    }

    setupTheme() {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
            document.documentElement.classList.add('dark');
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                document.documentElement.classList.toggle('dark', e.matches);
            }
        });
    }

    showSection(sectionName) {
        // Hide all sections
        document.querySelectorAll('.dashboard-section').forEach(section => {
            section.classList.add('hidden');
        });

        // Show target section
        const targetSection = document.getElementById(`${sectionName}-section`);
        if (targetSection) {
            targetSection.classList.remove('hidden');
        }

        // Update navigation state
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('text-primary', 'bg-primary/5');
            link.classList.add('text-gray-700', 'dark:text-gray-300');
        });

        const activeNavLink = document.getElementById(`nav-${sectionName}`);
        if (activeNavLink) {
            activeNavLink.classList.remove('text-gray-700', 'dark:text-gray-300');
            activeNavLink.classList.add('text-primary', 'bg-primary/5');
        }

        this.currentSection = sectionName;
        this.loadSectionData(sectionName);
    }

    toggleMobileMenu() {
        const sidebar = document.querySelector('.dashboard-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('open');
        }
    }

    toggleTheme() {
        const isDark = document.documentElement.classList.contains('dark');
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', !isDark ? 'dark' : 'light');
    }

    async loadUserData() {
        try {
            // Check if user is authenticated
            if (!this.isAuthenticated()) {
                window.location.href = '/frontend/auth/login.php';
                return;
            }

            // Simulate API call - replace with actual API endpoints
            const userData = {
                name: 'Mario Rossi',
                avatar: '/frontend/images/avatar-placeholder.svg',
                stats: {
                    projects: 3,
                    funds: '€12,450',
                    backed: 8,
                    backers: 156
                }
            };

            this.updateUserInterface(userData);
            this.loadDashboardStats(userData.stats);
            this.loadRecentActivity();

        } catch (error) {
            console.error('Error loading user data:', error);
            this.showNotification('Errore nel caricamento dei dati utente', 'error');
        }
    }

    updateUserInterface(userData) {
        // Update user name displays
        const userNameElements = document.querySelectorAll('#user-name');
        userNameElements.forEach(el => {
            if (el) el.textContent = userData.name;
        });

        // Update avatar
        const avatar = document.getElementById('user-avatar');
        if (avatar && userData.avatar) {
            avatar.src = userData.avatar;
        }
    }

    loadDashboardStats(stats) {
        // Update stats cards
        document.getElementById('stats-projects')?.textContent = stats.projects || 0;
        document.getElementById('stats-funds')?.textContent = stats.funds || '€0';
        document.getElementById('stats-backed')?.textContent = stats.backed || 0;
        document.getElementById('stats-backers')?.textContent = stats.backers || 0;
    }

    async loadSectionData(section) {
        switch (section) {
            case 'projects':
                await this.loadUserProjects();
                break;
            case 'backed':
                await this.loadBackedProjects();
                break;
            case 'overview':
                await this.loadRecentActivity();
                break;
        }
    }

    async loadUserProjects() {
        try {
            const projectsGrid = document.getElementById('projects-grid');
            if (!projectsGrid) return;

            // Simulate API call
            const projects = [
                {
                    id: 1,
                    title: "Innovazione Verde",
                    description: "Progetto per lo sviluppo di tecnologie eco-sostenibili",
                    image: "/frontend/assets/placeholder-tech.jpg",
                    funded: 8500,
                    goal: 15000,
                    backers: 78,
                    daysLeft: 15
                },
                {
                    id: 2,
                    title: "App per la Mobilità",
                    description: "Applicazione innovativa per il trasporto urbano",
                    image: "/frontend/assets/placeholder-tech2.jpg",
                    funded: 12450,
                    goal: 10000,
                    backers: 124,
                    daysLeft: 0
                }
            ];

            projectsGrid.innerHTML = projects.map(project => this.createProjectCard(project)).join('');

        } catch (error) {
            console.error('Error loading projects:', error);
            const projectsGrid = document.getElementById('projects-grid');
            if (projectsGrid) {
                projectsGrid.innerHTML = '<p class="text-center text-gray-500">Errore nel caricamento dei progetti</p>';
            }
        }
    }

    createProjectCard(project) {
        const progressPercentage = Math.min((project.funded / project.goal) * 100, 100);
        const status = project.daysLeft === 0 ? 'Completato' : `${project.daysLeft} giorni rimanenti`;
        const statusClass = project.daysLeft === 0 ? 'text-green-600' : 'text-blue-600';

        return `
            <div class="dashboard-card">
                <img src="${project.image}" alt="${project.title}" class="w-full h-48 object-cover rounded-lg mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">${project.title}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">${project.description}</p>
                
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Raccolto</span>
                        <span class="font-semibold">€${project.funded.toLocaleString()} / €${project.goal.toLocaleString()}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full" style="width: ${progressPercentage}%"></div>
                    </div>
                </div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600 dark:text-gray-400">${project.backers} sostenitori</span>
                    <span class="${statusClass} font-medium">${status}</span>
                </div>
                
                <div class="mt-4 flex space-x-2">
                    <button class="btn btn-primary flex-1 text-sm py-2">
                        <i class="ri-edit-line mr-1"></i>Modifica
                    </button>
                    <button class="btn bg-gray-600 hover:bg-gray-700 text-white text-sm py-2">
                        <i class="ri-eye-line"></i>
                    </button>
                </div>
            </div>
        `;
    }

    async loadBackedProjects() {
        try {
            const backedContainer = document.getElementById('backed-projects');
            if (!backedContainer) return;

            // Simulate API call
            const backedProjects = [
                {
                    id: 1,
                    title: "Robot Educativo",
                    creator: "TechEdu",
                    amount: 75,
                    reward: "Kit Completo + Manuale",
                    status: 'in_progress'
                },
                {
                    id: 2,
                    title: "Gioco da Tavolo Innovativo",
                    creator: "BoardGame Studio",
                    amount: 50,
                    reward: "Copia del Gioco",
                    status: 'funded'
                }
            ];

            backedContainer.innerHTML = backedProjects.map(project => `
                <div class="dashboard-card">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 dark:text-white">${project.title}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">di ${project.creator}</p>
                            <p class="text-sm text-gray-500 mt-1">Contributo: €${project.amount} - ${project.reward}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${project.status === 'funded' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">
                                ${project.status === 'funded' ? 'Finanziato' : 'In Corso'}
                            </span>
                        </div>
                    </div>
                </div>
            `).join('');

        } catch (error) {
            console.error('Error loading backed projects:', error);
        }
    }

    async loadRecentActivity() {
        try {
            const activityContainer = document.getElementById('recent-activity');
            if (!activityContainer) return;

            // Sample recent activity data
            const activities = [
                {
                    type: 'support',
                    message: 'Nuovo supporto ricevuto',
                    details: 'Per il progetto "Innovazione Verde" - €50',
                    time: '2h fa',
                    icon: 'ri-heart-line'
                },
                {
                    type: 'update',
                    message: 'Progetto aggiornato',
                    details: '"App per la Mobilità" - Nuova milestone raggiunta',
                    time: '1d fa',
                    icon: 'ri-arrow-up-line'
                },
                {
                    type: 'reward',
                    message: 'Ricompensa inviata',
                    details: 'T-shirt personalizzata per il supporto ricevuto',
                    time: '3d fa',
                    icon: 'ri-gift-line'
                }
            ];

            activityContainer.innerHTML = activities.map(activity => `
                <div class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
                    <div class="flex-shrink-0 mr-3">
                        <i class="${activity.icon} ${activity.type === 'support' ? 'text-green-500' : activity.type === 'update' ? 'text-blue-500' : 'text-purple-500'}"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${activity.message}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">${activity.details}</p>
                    </div>
                    <div class="text-xs text-gray-400">${activity.time}</div>
                </div>
            `).join('');

        } catch (error) {
            console.error('Error loading recent activity:', error);
        }
    }

    isAuthenticated() {
        // Check for authentication token or session
        return localStorage.getItem('authToken') || document.cookie.includes('auth_session');
    }

    async handleLogout() {
        try {
            // Clear local storage
            localStorage.removeItem('authToken');
            localStorage.removeItem('userData');

            // Redirect to login
            window.location.href = '/frontend/auth/login.php';

        } catch (error) {
            console.error('Error during logout:', error);
        }
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('notifications-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="ri-information-line mr-2"></i>
                <span>${message}</span>
                <button class="ml-auto text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        `;

        container.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    hideLoadingOverlay() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
        }
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardManager = new DashboardManager();

    // Update current year in footer if present
    const currentYearElement = document.getElementById('current-year');
    if (currentYearElement) {
        currentYearElement.textContent = new Date().getFullYear();
    }
});

// Global error handling
window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    if (window.dashboardManager) {
        window.dashboardManager.showNotification('Si è verificato un errore. Ricarica la pagina.', 'error');
    }
});

// Handle page visibility changes
document.addEventListener('visibilitychange', () => {
    if (!document.hidden && window.dashboardManager) {
        // Refresh data when page becomes visible
        window.dashboardManager.loadUserData();
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { DashboardManager };
} else if (typeof window !== 'undefined') {
    window.DashboardManager = DashboardManager;
}
