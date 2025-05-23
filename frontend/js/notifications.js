// Sistema di notifiche centralizzato per BOSTARTER

const NotificationSystem = {
    types: {
        SUCCESS: 'success',
        ERROR: 'error',
        WARNING: 'warning',
        INFO: 'info'
    },

    // Configurazione predefinita
    defaultConfig: {
        duration: 5000,
        position: 'top-right',
        animationDuration: 300
    },

    // Crea il container per le notifiche se non esiste
    initialize() {
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
            document.body.appendChild(container);
        }
    },

    // Mostra una nuova notifica
    show(message, type = this.types.INFO, customConfig = {}) {
        this.initialize();
        const config = { ...this.defaultConfig, ...customConfig };
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.style.cssText = `
            padding: 1rem 1.5rem;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 280px;
            max-width: 480px;
            animation: slideIn ${config.animationDuration}ms ease-out;
            transition: all ${config.animationDuration}ms ease-out;
        `;

        // Aggiungi icona in base al tipo
        const icon = document.createElement('i');
        icon.className = this.getIconClass(type);
        notification.appendChild(icon);

        // Aggiungi il messaggio
        const messageElement = document.createElement('p');
        messageElement.style.margin = '0';
        messageElement.style.flex = '1';
        messageElement.textContent = message;
        notification.appendChild(messageElement);

        // Aggiungi pulsante di chiusura
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '×';
        closeButton.style.cssText = `
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            color: inherit;
            opacity: 0.7;
        `;
        closeButton.onclick = () => this.dismiss(notification);
        notification.appendChild(closeButton);

        // Applica stili specifici per il tipo
        this.applyTypeStyles(notification, type);

        // Aggiungi la notifica al container
        const container = document.getElementById('notification-container');
        container.appendChild(notification);

        // Rimuovi automaticamente dopo la durata specificata
        if (config.duration > 0) {
            setTimeout(() => this.dismiss(notification), config.duration);
        }

        return notification;
    },

    // Rimuovi una notifica con animazione
    dismiss(notification) {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            notification.remove();
        }, this.defaultConfig.animationDuration);
    },

    // Ottieni la classe dell'icona in base al tipo
    getIconClass(type) {
        const icons = {
            [this.types.SUCCESS]: 'ri-checkbox-circle-fill',
            [this.types.ERROR]: 'ri-error-warning-fill',
            [this.types.WARNING]: 'ri-alert-fill',
            [this.types.INFO]: 'ri-information-fill'
        };
        return icons[type] || icons[this.types.INFO];
    },

    // Applica stili specifici per il tipo di notifica
    applyTypeStyles(notification, type) {
        const styles = {
            [this.types.SUCCESS]: {
                background: '#f0fdf4',
                color: '#166534',
                border: '1px solid #bbf7d0'
            },
            [this.types.ERROR]: {
                background: '#fef2f2',
                color: '#991b1b',
                border: '1px solid #fecaca'
            },
            [this.types.WARNING]: {
                background: '#fffbeb',
                color: '#92400e',
                border: '1px solid #fef3c7'
            },
            [this.types.INFO]: {
                background: '#f0f9ff',
                color: '#075985',
                border: '1px solid #bae6fd'
            }
        };

        const typeStyle = styles[type] || styles[this.types.INFO];
        Object.assign(notification.style, typeStyle);
    },

    // Metodi di utilità per mostrare diversi tipi di notifiche
    success(message, config = {}) {
        return this.show(message, this.types.SUCCESS, config);
    },

    error(message, config = {}) {
        return this.show(message, this.types.ERROR, config);
    },

    warning(message, config = {}) {
        return this.show(message, this.types.WARNING, config);
    },

    info(message, config = {}) {
        return this.show(message, this.types.INFO, config);
    }
};

// Aggiungi stili CSS necessari
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .notification {
        position: relative;
        overflow: hidden;
    }

    .notification button:hover {
        opacity: 1;
    }
`;
document.head.appendChild(style);

export default NotificationSystem;