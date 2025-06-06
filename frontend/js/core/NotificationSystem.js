// BOSTARTER Notification System
// Centralized notification management for consistent user alerts

class NotificationSystem {
    constructor() {
        this.config = {
            position: 'top-right',
            duration: 5000,
            animation: true
        };
        this.container = null;
        this.notifications = [];
        this.positionMap = {
            'top-right': { top: '20px', right: '20px', bottom: 'auto', left: 'auto' },
            'top-left': { top: '20px', right: 'auto', bottom: 'auto', left: '20px' },
            'bottom-right': { top: 'auto', right: '20px', bottom: '20px', left: 'auto' },
            'bottom-left': { top: 'auto', right: 'auto', bottom: '20px', left: '20px' },
            'top-center': { top: '20px', right: 'auto', bottom: 'auto', left: '50%', transform: 'translateX(-50%)' },
            'bottom-center': { top: 'auto', right: 'auto', bottom: '20px', left: '50%', transform: 'translateX(-50%)' }
        };
        this.init();
    }

    // Initialize notification container
    init() {
        this.container = document.getElementById('notificationContainer');

        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'notificationContainer';
            this.container.className = 'notification-container';
            this.container.setAttribute('role', 'region');
            this.container.setAttribute('aria-live', 'polite');
            this.container.setAttribute('aria-label', 'Notifiche');
            document.body.appendChild(this.container);
        }

        // Set position
        this.updatePosition(this.config.position);

        // Add styles if they don't exist
        if (!document.getElementById('notification-styles')) {
            this.addStyles();
        }
    }

    // Add notification system styles
    addStyles() {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification-container {
                position: fixed;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 400px;
                max-height: calc(100vh - 40px);
                overflow-y: auto;
                pointer-events: none;
            }

            .notification {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                border-left: 4px solid;
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                margin-bottom: 10px;
                width: 100%;
                max-width: 400px;
                transform: translateX(100%);
                transition: all 0.3s ease;
                pointer-events: auto;
                word-break: break-word;
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }

            .notification.notification-slide-in {
                transform: translateX(0);
            }

            .notification.notification-hide {
                transform: translateX(100%);
                opacity: 0;
            }

            .notification-content {
                flex: 1;
                margin-right: 10px;
            }

            .notification-close {
                background: none;
                border: none;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: background-color 0.2s ease;
                font-size: 18px;
                opacity: 0.7;
            }

            .notification-close:hover {
                opacity: 1;
                background-color: rgba(0, 0, 0, 0.1);
            }

            /* Success notification */
            .notification.success {
                background: rgba(240, 253, 244, 0.95);
                border-left-color: #10b981;
                color: #065f46;
            }

            /* Error notification */
            .notification.error {
                background: rgba(254, 242, 242, 0.95);
                border-left-color: #ef4444;
                color: #7f1d1d;
            }

            /* Warning notification */
            .notification.warning {
                background: rgba(255, 251, 235, 0.95);
                border-left-color: #f59e0b;
                color: #78350f;
            }

            /* Info notification */
            .notification.info {
                background: rgba(239, 246, 255, 0.95);
                border-left-color: #3b82f6;
                color: #1e3a8a;
            }

            /* Dark mode support */
            .dark .notification.success {
                background: rgba(6, 95, 70, 0.95);
                color: #86efac;
            }

            .dark .notification.error {
                background: rgba(127, 29, 29, 0.95);
                color: #fca5a5;
            }

            .dark .notification.warning {
                background: rgba(120, 53, 15, 0.95);
                color: #fcd34d;
            }

            .dark .notification.info {
                background: rgba(30, 58, 138, 0.95);
                color: #93c5fd;
            }

            /* Reduced motion support */
            @media (prefers-reduced-motion: reduce) {
                .notification {
                    transition: none !important;
                    transform: translateX(0) !important;
                }
            }
        `;
        document.head.appendChild(styles);
    }

    // Update container position
    updatePosition(position) {
        if (!this.positionMap[position]) {
            position = 'top-right';
        }

        const positionStyles = this.positionMap[position];

        Object.entries(positionStyles).forEach(([prop, value]) => {
            this.container.style[prop] = value;
        });
    }

    // Show a new notification
    show(message, type = 'info', options = {}) {
        if (!message) return null;

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.setAttribute('role', 'alert');

        const content = document.createElement('div');
        content.className = 'notification-content';
        content.textContent = message;

        const closeButton = document.createElement('button');
        closeButton.className = 'notification-close';
        closeButton.innerHTML = '&times;';
        closeButton.setAttribute('aria-label', 'Chiudi notifica');
        closeButton.setAttribute('type', 'button');

        notification.appendChild(content);
        notification.appendChild(closeButton);

        this.notifications.push(notification);
        this.container.appendChild(notification);

        // Animation with requestAnimationFrame for better performance
        requestAnimationFrame(() => {
            notification.classList.add('notification-slide-in');
        });

        // Setup close button event
        closeButton.addEventListener('click', () => this.close(notification));

        // Auto close after duration
        const duration = options.duration !== undefined ? options.duration : this.config.duration;
        if (duration > 0) {
            setTimeout(() => this.close(notification), duration);
        }

        return notification;
    }

    // Close a notification
    close(notification) {
        if (!notification) return;

        notification.classList.add('notification-hide');

        // Remove after animation completes
        setTimeout(() => {
            if (notification.parentNode === this.container) {
                this.container.removeChild(notification);
                this.notifications = this.notifications.filter(n => n !== notification);
            }
        }, 300);
    }

    // Close all notifications
    closeAll() {
        this.notifications.forEach(notification => this.close(notification));
    }

    // Helper methods for different notification types
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    error(message, options = {}) {
        return this.show(message, 'error', options);
    }

    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }

    info(message, options = {}) {
        return this.show(message, 'info', options);
    }

    // Configure the notification system
    configure(options = {}) {
        this.config = { ...this.config, ...options };

        if (options.position) {
            this.updatePosition(options.position);
        }

        return this;
    }
}

// Create a singleton instance
const notificationSystem = new NotificationSystem();

// Export for ES modules (both default and named)
export default notificationSystem;
export { NotificationSystem };

// Make it available globally
window.NotificationSystem = notificationSystem;

// Legacy compatibility - show notification function
window.showNotification = (message, type = 'info', duration) => {
    return notificationSystem.show(message, type, { duration });
};
