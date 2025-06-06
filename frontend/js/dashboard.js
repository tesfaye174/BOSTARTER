/**
 * Enhanced Dashboard JavaScript functionality for BOSTARTER
 * Handles dynamic content loading, real-time updates via HTTP polling, and user interactions
 */

class Dashboard {
    constructor() {
        this.retryCount = 0;
        this.maxRetries = 3;
        this.isOnline = navigator.onLine;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDashboardData();
        this.initializeAnimations();
        this.setupNotifications();
        this.setupOfflineSupport();
    }

    setupEventListeners() {
        // Theme toggle with improved handling
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                window.ThemeManager.toggle();
            });
        }

        // Mobile menu toggle with accessibility
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', () => {
                const isExpanded = mobileMenu.classList.contains('hidden');
                mobileMenu.classList.toggle('hidden');
                mobileMenuToggle.setAttribute('aria-expanded', !isExpanded);
            });
        }

        // Project actions with better error handling
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="view-project"]')) {
                e.preventDefault();
                this.viewProject(e.target.dataset.projectId);
            }
            if (e.target.matches('[data-action="manage-project"]')) {
                e.preventDefault();
                this.manageProject(e.target.dataset.projectId);
            }
            if (e.target.matches('[data-action="refresh-dashboard"]')) {
                e.preventDefault();
                this.refreshDashboard();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.refreshDashboard();
            }
        });        // Online/offline status
        window.addEventListener('online', () => {
            this.isOnline = true;
            window.NotificationSystem.success('Connection restored');
            this.loadDashboardData();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            window.NotificationSystem.warning('You are offline. Some features may be limited.');
        });
    }

    async loadDashboardData() {
        if (!this.isOnline) {
            this.loadCachedData();
            return;
        }

        try {
            this.showLoadingState(true);            // Load user statistics with retry logic
            const stats = await this.fetchWithRetry('/BOSTARTER/backend/api/stats_compliant.php?action=user_stats');
            if (stats && stats.success) {
                this.updateStatistics(stats.data);
                this.cacheData('stats', stats.data);
            }            // Load recent activities  
            const activities = await this.fetchWithRetry('/BOSTARTER/backend/api/stats_compliant.php?action=user_activities');
            if (activities && activities.success) {
                this.updateActivities(activities.data);
                this.cacheData('activities', activities.data);
            }

            // Load project updates
            await this.loadProjectUpdates();

            this.retryCount = 0; // Reset retry count on success        } catch (error) {
            // Silent error handling for dashboard data loading
            this.handleDataLoadError(error);
        } finally {
            this.showLoadingState(false);
        }
    }

    async fetchWithRetry(url, options = {}) {
        for (let i = 0; i <= this.maxRetries; i++) {
            try {
                const response = await fetch(url, {
                    ...options,
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        ...options.headers
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                return await response.json();
            } catch (error) {
                if (i === this.maxRetries) {
                    throw error;
                }

                // Exponential backoff
                await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
            }
        }
    } handleDataLoadError(error) {
        if (error.message.includes('401')) {
            // Unauthorized - redirect to login
            window.location.href = '/frontend/auth/login.php';
            return;
        }

        window.NotificationSystem.error('Failed to load dashboard data. Showing cached data.');
        this.loadCachedData();
    }

    updateStatistics(stats) {
        const statCards = document.querySelectorAll('.stat-card');
        if (!stats || !statCards.length) return;

        const values = [
            stats.projects_created || 0,
            stats.total_funded || 0,
            stats.applications || 0,
            stats.successful_projects || 0
        ];

        statCards.forEach((card, index) => {
            const valueElement = card.querySelector('h3, .stat-value');
            if (valueElement && values[index] !== undefined) {
                this.animateValue(valueElement, 0, values[index], 1500, index === 1 ? '€' : '');
            }
        });
    } animateValue(element, start, end, duration, prefix = '') {
        // Skip animation if user prefers reduced motion
        if (window.AnimationSystem.prefersReducedMotion) {
            element.textContent = prefix + window.Utils.formatCompactNumber(end);
            return;
        }

        const startTime = performance.now();
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Using the centralized easing function via AnimationSystem
            const currentValue = Math.floor(this.calculateEasing(progress) * (end - start) + start);
            element.textContent = prefix + window.Utils.formatCompactNumber(currentValue);

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    calculateEasing(t) {
        // Cubic-bezier easing similar to easeOutQuart
        return 1 - Math.pow(1 - t, 4);
    }

    updateActivities(activities) {
        const activityContainer = document.querySelector('.activity-container');
        if (!activityContainer || !activities || !activities.length) return;

        const activityHTML = activities.slice(0, 5).map(activity => `
            <div class="activity-item p-4 rounded-lg mb-3 bg-gray-50 dark:bg-gray-700 transition-colors">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-1">${this.escapeHtml(activity.title)}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${this.escapeHtml(activity.description)}</p>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-4">${this.formatDate(activity.date)}</span>
                </div>
            </div>
        `).join('');

        activityContainer.innerHTML = activityHTML;
    }

    async loadProjectUpdates() {
        try {
            const projects = await this.fetchWithRetry('/BOSTARTER/backend/api/projects_compliant.php?action=user_projects&limit=4');
            if (projects && projects.success) {
                this.updateProjectCards(projects.data);
                this.cacheData('projects', projects.data);
            }
        } catch (error) {
            // Silent error handling for project updates
        }
    }

    updateProjectCards(projects) {
        const projectContainer = document.querySelector('.project-grid, #projects-grid');
        if (!projectContainer || !projects) return;

        if (projects.length === 0) {
            projectContainer.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        <i class="fas fa-project-diagram text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">No projects yet</p>
                    <a href="/frontend/projects/create.php" class="btn btn-primary">Create Your First Project</a>
                </div>
            `;
            return;
        }

        const projectHTML = projects.map(project => `
            <div class="project-card bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all hover:shadow-md">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white truncate">${this.escapeHtml(project.title)}</h3>
                        <span class="px-2 py-1 text-xs rounded-full ${this.getStatusClass(project.status)}">${this.capitalizeFirst(project.status)}</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">${this.escapeHtml(project.description)}</p>
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Funded</span>
                            <span class="font-medium">€${(project.total_funded || 0).toLocaleString()}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full transition-all duration-500" style="width: ${Math.min(project.funding_percentage || 0, 100)}%"></div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button data-action="view-project" data-project-id="${project.project_id}" class="flex-1 text-center px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            View
                        </button>
                        ${project.status === 'open' ? `
                            <button data-action="manage-project" data-project-id="${project.project_id}" class="flex-1 text-center px-3 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                                Manage
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `).join('');

        projectContainer.innerHTML = projectHTML;
    }

    getStatusClass(status) {
        const classes = {
            'open': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
            'funded': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
            'closed': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'cancelled': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
        };
        return classes[status] || classes.closed;
    } initializeAnimations() {
        // Use the centralized AnimationSystem for animations
        const animatedElements = document.querySelectorAll('.animate-fade-in');
        if (animatedElements.length) {
            window.AnimationSystem.stagger(Array.from(animatedElements), 'fadeIn', {
                staggerDelay: 100,
                duration: 600,
                easing: window.AnimationSystem.config.easing.smooth
            });
        }

        this.animateProgressBars();
    }

    animateProgressBars() {
        // Use the centralized AnimationSystem for progress bar animations
        window.AnimationSystem.animateProgressBars('.progress-bar-custom, [class*="bg-primary"][style*="width"]', {
            duration: 800,
            staggerDelay: 100,
            baseDelay: 500
        });
    } setupNotifications() {
        // Check for notifications every 30 seconds using HTTP polling
        this.notificationInterval = setInterval(() => {
            if (this.isOnline) {
                this.checkNotifications();
            }
        }, 30000);

        // Initial check
        if (this.isOnline) {
            this.checkNotifications();
        }

        // Configure centralized notification system with dashboard preferences
        window.NotificationSystem.configure({
            position: 'top-right',
            duration: 5000
        });
    }

    async checkNotifications() {
        try {
            const response = await this.fetchWithRetry('/BOSTARTER/backend/api/stats_compliant.php?action=notifications');
            if (response && response.success && response.data.length > 0) {
                this.showNotificationBadge(response.data.length);

                // Populate notification dropdown if it exists
                this.populateNotificationDropdown(response.data);
            } else {
                // Hide badge if no unread notifications
                this.hideNotificationBadge();
            }
        } catch (error) {
            // Silent error handling for notifications check
        }
    }

    showNotificationBadge(count) {
        const badge = document.getElementById('notifications-badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        }
    }

    hideNotificationBadge() {
        const badge = document.getElementById('notifications-badge');
        if (badge) {
            badge.classList.add('hidden');
        }
    }

    /**
     * Populate notification dropdown
     */
    populateNotificationDropdown(notifications) {
        const dropdown = document.getElementById('notification-dropdown');
        if (dropdown) {
            dropdown.innerHTML = notifications.map(notification => `
                <div class="notification-item ${notification.is_read ? 'read' : 'unread'}" data-id="${notification.id}">
                    <div class="notification-content">
                        <div class="notification-message">${this.escapeHtml(notification.message)}</div>
                        <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                    </div>
                    ${!notification.is_read ? `
                        <button class="notification-mark-read" data-id="${notification.id}">
                            Mark as read
                        </button>
                    ` : ''}
                </div>
            `).join('');

            // Add click handlers for mark as read buttons
            dropdown.querySelectorAll('.notification-mark-read').forEach(btn => {
                btn.addEventListener('click', () => {
                    this.markNotificationAsRead(btn.dataset.id);
                });
            });
        }
    }

    /**
     * Mark notification as read
     */
    async markNotificationAsRead(notificationId) {
        try {
            const response = await fetch('/BOSTARTER/backend/api/stats_compliant.php?action=mark_notification_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            });

            const data = await response.json();
            if (data.success) {
                // Update UI
                const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.classList.add('read');
                    notificationElement.classList.remove('unread');

                    // Remove mark as read button
                    const markReadBtn = notificationElement.querySelector('.notification-mark-read');
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                }

                // Refresh notification count
                this.checkNotifications();
            }
        } catch (error) {
            // Silent error handling for notification marking
        }
    }

    /**
     * Load application updates
     */
    async loadApplications() {
        try {
            const response = await this.fetchWithRetry('/BOSTARTER/backend/api/stats_compliant.php?action=user_applications');
            if (response && response.success) {
                this.updateApplicationsSection(response.data);
            }
        } catch (error) {
            // Silent error handling for applications loading
        }
    }

    /**
     * Update applications section
     */
    updateApplicationsSection(applications) {
        const applicationsContainer = document.getElementById('applications-container');
        if (applicationsContainer && applications) {
            // Update applications display
            applicationsContainer.innerHTML = applications.map(app => `
                <div class="application-item status-${app.status}">
                    <h4>${this.escapeHtml(app.project_title)}</h4>
                    <p>Status: <span class="status-badge">${this.escapeHtml(app.status)}</span></p>
                    <p>Applied: ${this.formatDate(app.created_at)}</p>
                </div>
            `).join('');
        }
    }    /**
     * Format time for display - uses centralized Utils
     */
    formatTime(timestamp) {
        // Use centralized relative time formatter
        return window.Utils.formatRelativeTime(timestamp);
    }

    /**
     * Format date for display - uses centralized Utils
     */
    formatDate(timestamp) {
        // Use centralized date formatter with US locale for consistency
        return window.Utils.formatDate(timestamp, {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }, 'en-US');
    }    /**
     * Utility methods for UI handling - using centralized Utils
     */
    escapeHtml(text) {
        // If Utils is available, use it, otherwise fallback to the local implementation
        if (window.Utils && window.Utils.escapeHtml) {
            return window.Utils.escapeHtml(text);
        }

        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    capitalizeFirst(str) {
        // If Utils is available, use it, otherwise fallback to the local implementation
        if (window.Utils && window.Utils.capitalizeFirst) {
            return window.Utils.capitalizeFirst(str);
        }

        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    showLoadingState(show) {
        const loadingIndicators = document.querySelectorAll('.loading-indicator, .skeleton-loader');
        loadingIndicators.forEach(indicator => {
            indicator.style.display = show ? 'block' : 'none';
        });
    } refreshDashboard() {
        this.loadDashboardData();
        this.checkNotifications();
        window.NotificationSystem.success('Dashboard refreshed');
    }

    viewProject(projectId) {
        if (projectId) {
            window.location.href = `/frontend/projects/view.php?id=${projectId}`;
        }
    }

    manageProject(projectId) {
        if (projectId) {
            window.location.href = `/frontend/projects/manage.php?id=${projectId}`;
        }
    } setupOfflineSupport() {
        // Register service worker if available
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/frontend/sw.js')
                .then(registration => {
                    // Log via centralized system instead of console
                    if (window.BOSTARTER && window.BOSTARTER._emitEvent) {
                        window.BOSTARTER._emitEvent('serviceworker:registered');
                    }
                })
                .catch(error => {
                    // Log error via centralized system instead of console
                    if (window.NotificationSystem) {
                        window.NotificationSystem.error('Offline support setup failed');
                    }
                });
        }
    } cacheData(key, data) {
        try {
            localStorage.setItem(`dashboard_${key}`, JSON.stringify(data));
        } catch (error) {
            // Silent error handling for cache operations
        }
    } loadCachedData() {
        try {
            const cachedStats = localStorage.getItem('dashboard_stats');
            if (cachedStats) {
                this.updateStatistics(JSON.parse(cachedStats));
            }

            const cachedActivities = localStorage.getItem('dashboard_activities');
            if (cachedActivities) {
                this.updateActivities(JSON.parse(cachedActivities));
            }

            const cachedProjects = localStorage.getItem('dashboard_projects');
            if (cachedProjects) {
                this.updateProjectCards(JSON.parse(cachedProjects));
            } window.NotificationSystem.warning('Showing cached data. Check your internet connection.');
        } catch (error) {
            // Silent error handling for cached data loading
        }
    }

    // Cleanup method
    destroy() {
        if (this.notificationInterval) {
            clearInterval(this.notificationInterval);
        }
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardInstance = new Dashboard();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.dashboardInstance) {
        window.dashboardInstance.destroy();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Dashboard;
}
