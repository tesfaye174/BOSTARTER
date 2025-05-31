/**
 * Dashboard JavaScript functionality for BOSTARTER
 * Handles dynamic content loading and user interactions
 */

class Dashboard {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDashboardData();
        this.initializeAnimations();
        this.setupNotifications();
    }

    setupEventListeners() {
        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', this.toggleTheme);
        }

        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // Project actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view-project"]')) {
                this.viewProject(e.target.dataset.projectId);
            }
            if (e.target.matches('[data-action="manage-project"]')) {
                this.manageProject(e.target.dataset.projectId);
            }
        });

        // Real-time updates
        this.setupWebSocket();
    }

    toggleTheme() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        html.classList.toggle('dark', !isDark);
        localStorage.setItem('theme', isDark ? 'light' : 'dark');

        // Update icon
        const sunIcon = document.querySelector('.ri-sun-line');
        const moonIcon = document.querySelector('.ri-moon-line');
        if (sunIcon && moonIcon) {
            sunIcon.classList.toggle('hidden', !isDark);
            moonIcon.classList.toggle('hidden', isDark);
        }
    }

    async loadDashboardData() {
        try {
            // Load user statistics
            const statsResponse = await fetch('/backend/api/stats.php?type=user');
            if (statsResponse.ok) {
                const stats = await statsResponse.json();
                this.updateStatistics(stats);
            }

            // Load recent activities
            const activitiesResponse = await fetch('/backend/api/users.php?action=activities');
            if (activitiesResponse.ok) {
                const activities = await activitiesResponse.json();
                this.updateActivities(activities);
            }

            // Load project updates
            this.loadProjectUpdates();
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.showNotification('Error loading dashboard data', 'error');
        }
    }

    updateStatistics(stats) {
        const statCards = document.querySelectorAll('.stat-card');
        if (stats && statCards.length > 0) {
            // Update each stat card with animation
            const values = [
                stats.projects_created || 0,
                stats.total_funded || 0,
                stats.applications || 0,
                stats.successful_projects || 0
            ];

            statCards.forEach((card, index) => {
                const valueElement = card.querySelector('h3');
                if (valueElement && values[index] !== undefined) {
                    this.animateValue(valueElement, 0, values[index], 1500, index === 1 ? '$' : '');
                }
            });
        }
    }

    animateValue(element, start, end, duration, prefix = '') {
        const startTime = performance.now();
        const startValue = parseInt(start);
        const endValue = parseInt(end);
        const difference = endValue - startValue;

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(startValue + (difference * this.easeOutQuart(progress)));

            element.textContent = prefix + (prefix === '$' ? this.formatCurrency(current) : current.toLocaleString());

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    easeOutQuart(t) {
        return 1 - (--t) * t * t * t;
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    updateActivities(activities) {
        const activityContainer = document.querySelector('.activity-container');
        if (!activityContainer || !activities || !activities.length) return;

        const activityHTML = activities.slice(0, 5).map(activity => `
            <div class="activity-item p-4 rounded-lg mb-3">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white mb-1">${this.escapeHtml(activity.title)}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${this.escapeHtml(activity.description)}</p>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">${this.formatDate(activity.date)}</span>
                </div>
            </div>
        `).join('');

        activityContainer.innerHTML = activityHTML;
    }

    async loadProjectUpdates() {
        try {
            const response = await fetch('/backend/api/projects.php?action=user_projects&limit=4');
            if (response.ok) {
                const projects = await response.json();
                this.updateProjectCards(projects);
            }
        } catch (error) {
            console.error('Error loading project updates:', error);
        }
    }

    updateProjectCards(projects) {
        const projectContainer = document.querySelector('.project-cards-container');
        if (!projectContainer || !projects || !projects.length) return;

        const projectHTML = projects.map(project => `
            <div class="project-card border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-medium text-gray-900 dark:text-white line-clamp-1">${this.escapeHtml(project.title)}</h3>
                    <span class="px-2 py-1 text-xs rounded-full ${this.getStatusClass(project.status)}">
                        ${this.capitalizeFirst(project.status)}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">${this.capitalizeFirst(project.project_type)} Project</p>
                
                <div class="mb-3">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span>$${this.formatCurrency(project.total_funded || 0)}</span>
                        <span>${Math.round(project.funding_percentage || 0)}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="progress-bar-custom h-2 rounded-full transition-all duration-500" 
                             style="width: ${Math.min(100, project.funding_percentage || 0)}%"></div>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button data-action="view-project" data-project-id="${project.project_id}"
                           class="flex-1 text-center px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        View Details
                    </button>
                    ${project.status === 'open' ? `
                        <button data-action="manage-project" data-project-id="${project.project_id}"
                               class="flex-1 text-center px-3 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                            Manage
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');

        projectContainer.innerHTML = projectHTML;
    }

    getStatusClass(status) {
        const classes = {
            'open': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'funded': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'closed': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
        };
        return classes[status] || classes['closed'];
    }

    initializeAnimations() {
        // Intersection Observer for fade-in animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        // Observe all elements with fade-in animation
        document.querySelectorAll('.animate-fade-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });

        // Progress bar animations
        this.animateProgressBars();
    }

    animateProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar-custom');
        progressBars.forEach((bar, index) => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 500 + (index * 100));
        });
    }

    setupNotifications() {
        // Check for notifications every 30 seconds
        setInterval(() => {
            this.checkNotifications();
        }, 30000);
    }

    async checkNotifications() {
        try {
            const response = await fetch('/backend/api/notifications.php?action=unread');
            if (response.ok) {
                const notifications = await response.json();
                if (notifications && notifications.length > 0) {
                    this.showNotificationBadge(notifications.length);
                }
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }

    showNotificationBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        }
    }

    setupWebSocket() {
        // WebSocket connection for real-time updates
        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const ws = new WebSocket(`${protocol}//${window.location.host}:8080`);

            ws.onopen = () => {
                console.log('WebSocket connected');
            };

            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleWebSocketMessage(data);
            };

            ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };

            ws.onclose = () => {
                console.log('WebSocket disconnected');
                // Attempt to reconnect after 5 seconds
                setTimeout(() => this.setupWebSocket(), 5000);
            };

            this.ws = ws;
        } catch (error) {
            console.error('WebSocket setup failed:', error);
        }
    }

    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'notification':
                this.showNotification(data.message, 'info');
                break;
            case 'project_update':
                this.loadProjectUpdates();
                break;
            case 'funding_update':
                this.loadDashboardData();
                break;
            default:
                console.log('Unknown WebSocket message:', data);
        }
    }

    viewProject(projectId) {
        window.location.href = `projects/detail.php?id=${projectId}`;
    }

    manageProject(projectId) {
        window.location.href = `projects/add_reward.php?id=${projectId}`;
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('notifications-container');
        if (!container) return;

        const notification = document.createElement('div');
        const bgColor = {
            'success': 'bg-green-500',
            'error': 'bg-red-500',
            'warning': 'bg-yellow-500',
            'info': 'bg-blue-500'
        }[type] || 'bg-blue-500';

        notification.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg transform transition-transform duration-300 translate-x-full`;
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${this.escapeHtml(message)}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 hover:bg-white/20 rounded p-1">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        `;

        container.appendChild(notification);

        // Slide in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays} days ago`;
        if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
        return date.toLocaleDateString();
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Dashboard();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Dashboard;
}
