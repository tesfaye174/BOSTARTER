// Sistema di notifiche moderno per BOSTARTER
class BONotifications {
    constructor() {
        this.container = this.createContainer();
        document.body.appendChild(this.container);
    }

    createContainer() {
        const container = document.createElement('div');
        container.className = 'notifications-container';
        container.style.cssText = `
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        `;
        return container;
    }

    show(message, options = {}) {
        const {
            type = 'info',
            duration = 5000,
            icon = null,
            action = null
        } = options;

        const notification = document.createElement('div');
        notification.className = `notification notification-${type} animate-in`;

        const iconClass = icon || this.getDefaultIcon(type);

        notification.innerHTML = `
            <div class="notification-content">
                <i class="${iconClass}"></i>
                <p>${message}</p>
                ${action ? `
                    <button class="notification-action">
                        ${action.text}
                    </button>
                ` : ''}
            </div>
            <div class="notification-progress"></div>
        `;

        this.container.appendChild(notification);

        if (action && action.callback) {
            notification.querySelector('.notification-action')
                .addEventListener('click', () => action.callback());
        }

        // Animazione di progress
        const progress = notification.querySelector('.notification-progress');
        progress.style.animation = `progress ${duration}ms linear`;

        // Rimozione automatica
        setTimeout(() => {
            notification.classList.add('animate-out');
            setTimeout(() => notification.remove(), 300);
        }, duration);

        return notification;
    }

    getDefaultIcon(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    // Metodi helper per diversi tipi di notifiche
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
