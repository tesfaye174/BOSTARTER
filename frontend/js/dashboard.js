/**
 * Enhanced Dashboard JavaScript functionality for BOSTARTER
 * Handles dynamic content loading, real-time updates, and user interactions
 */

class Dashboard {
    constructor() {
        this.ws = null;
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
        this.initializeTheme();
    }

    setupEventListeners() {
        // Theme toggle with improved handling
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', this.toggleTheme.bind(this));
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
        });

        // Real-time updates
        this.setupWebSocket();

        // Online/offline status
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.showNotification('Connection restored', 'success');
            this.loadDashboardData();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.showNotification('You are offline. Some features may be limited.', 'warning');
        });
    }

    initializeTheme() {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
            document.documentElement.classList.add('dark');
        }

        // Update theme toggle button state
        this.updateThemeToggleIcon();
    }

    toggleTheme() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');

        html.classList.toggle('dark', !isDark);
        localStorage.setItem('theme', isDark ? 'light' : 'dark');

        this.updateThemeToggleIcon();
        this.showNotification(`Switched to ${isDark ? 'light' : 'dark'} mode`, 'info');
    }

    updateThemeToggleIcon() {
        const isDark = document.documentElement.classList.contains('dark');
        const sunIcons = document.querySelectorAll('.ri-sun-line');
        const moonIcons = document.querySelectorAll('.ri-moon-line');

        sunIcons.forEach(icon => icon.classList.toggle('hidden', isDark));
        moonIcons.forEach(icon => icon.classList.toggle('hidden', !isDark));
    }

    async loadDashboardData() {
        if (!this.isOnline) {
            this.loadCachedData();
            return;
        }

        try {
            this.showLoadingState(true);

            // Load user statistics with retry logic
            const stats = await this.fetchWithRetry('/backend/api/stats.php?type=user');
            if (stats && stats.success) {
                this.updateStatistics(stats.stats);
                this.cacheData('stats', stats.stats);
            }

            // Load recent activities
            const activities = await this.fetchWithRetry('/backend/api/users.php?action=activities');
            if (activities && activities.success) {
                this.updateActivities(activities.data);
                this.cacheData('activities', activities.data);
            }

            // Load project updates
            await this.loadProjectUpdates();

            this.retryCount = 0; // Reset retry count on success
        } catch (error) {
            console.error('Error loading dashboard data:', error);
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
    }

    handleDataLoadError(error) {
        if (error.message.includes('401')) {
            // Unauthorized - redirect to login
            window.location.href = '/frontend/auth/login.php';
            return;
        }

        this.showNotification('Failed to load dashboard data. Showing cached data.', 'error');
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
    }

    animateValue(element, start, end, duration, prefix = '') {
        const startTime = performance.now();
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const currentValue = Math.floor(this.easeOutQuart(progress) * (end - start) + start);
            element.textContent = prefix + this.formatNumber(currentValue);

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    easeOutQuart(t) {
        return 1 - Math.pow(1 - t, 4);
    }

    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toLocaleString();
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
            const projects = await this.fetchWithRetry('/backend/api/projects.php?action=user_projects&limit=4');
            if (projects && projects.success) {
                this.updateProjectCards(projects.data);
                this.cacheData('projects', projects.data);
            }
        } catch (error) {
            console.error('Error loading project updates:', error);
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
    }

    initializeAnimations() {
        // Animate elements on load
        const animatedElements = document.querySelectorAll('.animate-fade-in');
        animatedElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';

            setTimeout(() => {
                element.style.transition = 'all 0.6s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });

        this.animateProgressBars();
    }

    animateProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar-custom, [class*="bg-primary"][style*="width"]');
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
        this.notificationInterval = setInterval(() => {
            if (this.isOnline) {
                this.checkNotifications();
            }
        }, 30000);
    }

    async checkNotifications() {
        try {
            const response = await this.fetchWithRetry('/backend/api/notifications.php?action=unread');
            if (response && response.success && response.data.length > 0) {
                this.showNotificationBadge(response.data.length);
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }

    showNotificationBadge(count) {
        const badge = document.getElementById('notifications-badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        }
    } setupWebSocket() {
        if (!this.isOnline) return;

        // Use the enhanced WebSocket client
        if (typeof WebSocketClient !== 'undefined') {
            const userId = sessionStorage.getItem('user_id') || localStorage.getItem('user_id');
            const token = sessionStorage.getItem('token') || localStorage.getItem('token');

            if (userId && token) {
                // Set up authentication callback
                WebSocketClient.setAuthCallback((success) => {
                    if (success) {
                        console.log('WebSocket authenticated successfully');
                        this.showNotification('Real-time updates enabled', 'success');
                        WebSocketClient.subscribeToNotifications();
                    } else {
                        console.error('WebSocket authentication failed');
                        this.showNotification('Failed to connect to real-time updates', 'error');
                    }
                });

                // Set up notification listener
                WebSocketClient.addListener('notification', (notification) => {
                    this.handleRealtimeNotification(notification);
                });

                // Set up pending notifications listener
                WebSocketClient.addListener('pending_notifications', (data) => {
                    this.handlePendingNotifications(data);
                });

                // Set up connection failure listener
                WebSocketClient.addListener('connection_failed', (data) => {
                    this.showNotification('Real-time connection failed. Using fallback polling.', 'warning');
                    // Fall back to periodic polling
                    this.setupNotifications();
                });

                // Connect to WebSocket
                WebSocketClient.connect(userId, token);
            }
        } else {
            // Fallback to original WebSocket implementation
            try {
                const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                const wsUrl = `${protocol}//${window.location.host}:8080`;

                this.ws = new WebSocket(wsUrl);

                this.ws.onopen = () => {
                    console.log('WebSocket connected');
                    this.retryCount = 0;
                };

                this.ws.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        this.handleWebSocketMessage(data);
                    } catch (error) {
                        console.error('Error parsing WebSocket message:', error);
                    }
                };

                this.ws.onerror = (error) => {
                    console.error('WebSocket error:', error);
                };

                this.ws.onclose = () => {
                    console.log('WebSocket disconnected');
                    // Attempt to reconnect with exponential backoff
                    if (this.retryCount < this.maxRetries && this.isOnline) {
                        setTimeout(() => {
                            this.retryCount++;
                            this.setupWebSocket();
                        }, Math.pow(2, this.retryCount) * 1000);
                    }
                };
            } catch (error) {
                console.error('WebSocket setup failed:', error);
            }
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
            case 'stats_update':
                this.updateStatistics(data.stats);
                break;
            default:
                console.log('Unknown WebSocket message:', data);
        }
    }

    /**
     * Handle real-time notifications from WebSocket
     */
    handleRealtimeNotification(notification) {
        try {
            // Update notification badge
            this.updateNotificationBadge();

            // Show in-app notification
            this.showNotification(notification.message, this.getNotificationType(notification.type));

            // Update relevant dashboard sections based on notification type
            switch (notification.type) {
                case 'project_backed':
                    this.loadProjectUpdates();
                    this.loadDashboardData();
                    break;
                case 'project_comment':
                    this.loadProjectUpdates();
                    break;
                case 'project_update':
                    this.loadProjectUpdates();
                    break;
                case 'goal_reached':
                    this.loadDashboardData();
                    this.showCelebration();
                    break;
                case 'application_status':
                    this.loadApplications();
                    break;
                default:
                    console.log('Unhandled notification type:', notification.type);
            }

            // Add to notification list if visible
            this.addToNotificationList(notification);

        } catch (error) {
            console.error('Error handling real-time notification:', error);
        }
    }

    /**
     * Handle pending notifications received on connection
     */
    handlePendingNotifications(data) {
        try {
            if (data.notifications && data.notifications.length > 0) {
                // Update notification badge with count
                this.showNotificationBadge(data.count);

                // Show summary notification
                this.showNotification(
                    `You have ${data.count} unread notification${data.count > 1 ? 's' : ''}`,
                    'info'
                );

                // Populate notification dropdown if exists
                this.populateNotificationDropdown(data.notifications);
            }
        } catch (error) {
            console.error('Error handling pending notifications:', error);
        }
    }

    /**
     * Update notification badge
     */
    async updateNotificationBadge() {
        try {
            const response = await this.fetchWithRetry('/backend/api/notifications.php?action=count');
            if (response && response.success) {
                this.showNotificationBadge(response.count);
            }
        } catch (error) {
            console.error('Error updating notification badge:', error);
        }
    }

    /**
     * Get notification type for styling
     */
    getNotificationType(type) {
        const typeMap = {
            'project_backed': 'success',
            'project_comment': 'info',
            'project_update': 'info',
            'goal_reached': 'success',
            'application_status': 'warning',
            'error': 'error'
        };
        return typeMap[type] || 'info';
    }

    /**
     * Show celebration animation for goal reached
     */
    showCelebration() {
        // Create celebration overlay
        const celebration = document.createElement('div');
        celebration.className = 'celebration-overlay';
        celebration.innerHTML = `
            <div class="celebration-content">
                <div class="celebration-icon">🎉</div>
                <h2>Congratulations!</h2>
                <p>Your project has reached its funding goal!</p>
            </div>
        `;

        document.body.appendChild(celebration);

        // Remove after animation
        setTimeout(() => {
            celebration.remove();
        }, 5000);
    }

    /**
     * Add notification to the notification list
     */
    addToNotificationList(notification) {
        const notificationList = document.getElementById('notification-list');
        if (notificationList) {
            const notificationElement = this.createNotificationElement(notification);
            notificationList.insertBefore(notificationElement, notificationList.firstChild);

            // Limit the number of visible notifications
            const notifications = notificationList.children;
            if (notifications.length > 10) {
                notificationList.removeChild(notifications[notifications.length - 1]);
            }
        }
    }

    /**
     * Create notification element for the list
     */
    createNotificationElement(notification) {
        const element = document.createElement('div');
        element.className = `notification-item ${notification.is_read ? 'read' : 'unread'}`;
        element.innerHTML = `
            <div class="notification-content">
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${this.formatTime(notification.created_at)}</div>
            </div>
            <button class="notification-mark-read" data-id="${notification.id}">
                ${notification.is_read ? '✓' : 'Mark as read'}
            </button>
        `;

        // Add click handler for mark as read
        const markReadBtn = element.querySelector('.notification-mark-read');
        if (markReadBtn && !notification.is_read) {
            markReadBtn.addEventListener('click', () => {
                this.markNotificationAsRead(notification.id);
            });
        }

        return element;
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
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                    </div>
                </div>
            `).join('');
        }
    }

    /**
     * Mark notification as read
     */
    async markNotificationAsRead(notificationId) {
        try {
            const response = await fetch('/backend/api/notifications.php?action=mark_read', {
                method: 'PUT',
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
                }

                // Update badge count
                this.updateNotificationBadge();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    /**
     * Load application updates
     */
    async loadApplications() {
        try {
            const response = await this.fetchWithRetry('/backend/api/applications.php?action=user-applications');
            if (response && response.success) {
                this.updateApplicationsSection(response.data);
            }
        } catch (error) {
            console.error('Error loading applications:', error);
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
                    <h4>${app.project_title}</h4>
                    <p>Status: <span class="status-badge">${app.status}</span></p>
                    <p>Applied: ${this.formatDate(app.created_at)}</p>
                </div>
            `).join('');
        }
    }

    /**
     * Format time for display
     */
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) { // Less than 1 minute
            return 'Just now';
        } else if (diff < 3600000) { // Less than 1 hour
            return `${Math.floor(diff / 60000)} minutes ago`;
        } else if (diff < 86400000) { // Less than 1 day
            return `${Math.floor(diff / 3600000)} hours ago`;
        } else {
            return date.toLocaleDateString();
        }
    }

    /**
     * Format date for display
     */
    formatDate(timestamp) {
        return new Date(timestamp).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    // Cleanup method
    destroy() {
        if (this.ws) {
            this.ws.close();
        }
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
