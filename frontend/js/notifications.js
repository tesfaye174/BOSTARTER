// Modern Notification System for BOSTARTER
class BONotifications {
    constructor() {
        this.container = this.createContainer();
        this.notifications = new Map();
        this.maxNotifications = 5;
        document.body.appendChild(this.container);

        // Handle focus management for accessibility
        this.handleFocusManagement();
    }

    createContainer() {
        const container = document.createElement('div');
        container.className = 'notifications-container';
        container.setAttribute('role', 'log');
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-relevant', 'additions');
        container.style.cssText = `
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            max-width: 400px;
            pointer-events: none;
        `;
        return container;
    }

    handleFocusManagement() {
        // Track focus for accessibility
        this.container.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.dismissAll();
            }
        });
    }

    show(message, options = {}) {
        const {
            type = 'info',
            duration = 5000,
            icon = this.getDefaultIcon(type),
            action = null,
            id = Date.now().toString()
        } = options;

        // Manage maximum notifications
        if (this.notifications.size >= this.maxNotifications) {
            const oldestId = this.notifications.keys().next().value;
            this.dismiss(oldestId);
        }

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.setAttribute('role', 'alert');
        notification.style.cssText = `
            background: var(--notification-${type}-bg, white);
            border-left: 4px solid var(--notification-${type}-border);
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            pointer-events: auto;
        `;

        // Add icon
        if (icon) {
            const iconElement = document.createElement('span');
            iconElement.className = 'notification-icon';
            iconElement.innerHTML = icon;
            notification.appendChild(iconElement);
        }

        // Add message
        const messageElement = document.createElement('span');
        messageElement.className = 'notification-message';
        messageElement.textContent = message;
        notification.appendChild(messageElement);

        // Add action button if provided
        if (action) {
            const actionButton = document.createElement('button');
            actionButton.className = 'notification-action';
            actionButton.textContent = action.text;
            actionButton.onclick = action.callback;
            notification.appendChild(actionButton);
        }

        // Add dismiss button
        const dismissButton = document.createElement('button');
        dismissButton.className = 'notification-dismiss';
        dismissButton.setAttribute('aria-label', 'Dismiss notification');
        dismissButton.innerHTML = '×';
        dismissButton.onclick = () => this.dismiss(id);
        notification.appendChild(dismissButton);

        // Store notification reference
        this.notifications.set(id, {
            element: notification,
            timeout: duration ? setTimeout(() => this.dismiss(id), duration) : null
        });

        // Add to container and animate
        this.container.appendChild(notification);
        requestAnimationFrame(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        });

        return id;
    }

    dismiss(id) {
        const notification = this.notifications.get(id);
        if (!notification) return;

        const { element, timeout } = notification;
        if (timeout) clearTimeout(timeout);

        element.style.opacity = '0';
        element.style.transform = 'translateX(100%)';

        element.addEventListener('transitionend', () => {
            element.remove();
            this.notifications.delete(id);
        });
    }

    dismissAll() {
        this.notifications.forEach((_, id) => this.dismiss(id));
    }

    getDefaultIcon(type) {
        const icons = {
            success: '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            error: '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            warning: '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            info: '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };
        return icons[type] || icons.info;
    }

    // Helper methods for different notification types
    success(message, options = {}) {
        return this.show(message, { ...options, type: 'success' });
    }

    error(message, options = {}) {
        return this.show(message, { ...options, type: 'error' });
    }

    warning(message, options = {}) {
        return this.show(message, { ...options, type: 'warning' });
    }

    info(message, options = {}) {
        return this.show(message, { ...options, type: 'info' });
    }
}

// Inizializzazione del sistema di notifiche
window.boNotifications = new BONotifications();
