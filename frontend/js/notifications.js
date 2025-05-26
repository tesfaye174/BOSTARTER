// Sistema di notifiche centralizzato per BOSTARTER

// Configurazione delle notifiche
const notificationConfig = {
    position: 'top-right',
    duration: 5000,
    animation: true
};

// Classe per la gestione delle notifiche
class NotificationManager {
    constructor() {
        this.container = null;
        this.notifications = [];
        this.init();
    }

    // Inizializzazione del container delle notifiche
    init() {
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        document.body.appendChild(this.container);
    }

    // Creazione di una nuova notifica
    create(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const content = document.createElement('div');
        content.className = 'notification-content';
        content.textContent = message;
        
        const closeButton = document.createElement('button');
        closeButton.className = 'notification-close';
        closeButton.innerHTML = '&times;';
        closeButton.setAttribute('aria-label', 'Chiudi notifica');
        
        notification.appendChild(content);
        notification.appendChild(closeButton);
        
        this.notifications.push(notification);
        this.container.appendChild(notification);
        
        // Animazione di entrata
        requestAnimationFrame(() => {
            notification.classList.add('notification-slide-in');
        });
        
        // Gestione della chiusura
        closeButton.addEventListener('click', () => this.remove(notification));
        
        // Auto-rimozione dopo la durata specificata
        if (notificationConfig.duration > 0) {
            setTimeout(() => this.remove(notification), notificationConfig.duration);
        }
        
        return notification;
    }

    // Rimozione di una notifica
    remove(notification) {
        if (!notification) return;
        
        notification.classList.add('notification-hide');
        
        // Rimuovi dopo l'animazione
        setTimeout(() => {
            if (notification.parentNode === this.container) {
                this.container.removeChild(notification);
                this.notifications = this.notifications.filter(n => n !== notification);
            }
        }, 300);
    }

    // Rimozione di tutte le notifiche
    clear() {
        this.notifications.forEach(notification => this.remove(notification));
    }

    // Metodi di utilità per diversi tipi di notifiche
    success(message) {
        return this.create(message, 'success');
    }

    error(message) {
        return this.create(message, 'error');
    }

    warning(message) {
        return this.create(message, 'warning');
    }

    info(message) {
        return this.create(message, 'info');
    }
}

// Crea un'istanza globale del gestore notifiche
const notificationManager = new NotificationManager();

// Esporta l'istanza e la classe
export { notificationManager, NotificationManager };

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