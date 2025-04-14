// Sistema di notifiche
class NotificationManager {
    constructor() {
        this.notificationsList = document.querySelector('.notifications-list');
        this.notificationsCount = document.querySelector('.notifications-count');
        this.notificationsMenu = document.getElementById('notifications-menu');
        this.updateInterval = 60000; // Aggiorna ogni minuto
        this.init();
    }

    init() {
        if (isLoggedIn) {
            this.notificationsMenu.style.display = 'block';
            this.loadNotifications();
            setInterval(() => this.loadNotifications(), this.updateInterval);
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('/Booster/server/notifications/get');
            const data = await response.json();

            if (data.success) {
                this.updateNotificationsList(data.notifications);
                this.updateUnreadCount(data.unread_count);
            }
        } catch (error) {
            console.error('Errore nel caricamento delle notifiche:', error);
        }
    }

    updateNotificationsList(notifications) {
        this.notificationsList.innerHTML = '';
        
        if (notifications.length === 0) {
            this.notificationsList.innerHTML = '<div class="text-center p-3">Nessuna notifica</div>';
            return;
        }

        notifications.forEach(notification => {
            const notificationElement = this.createNotificationElement(notification);
            this.notificationsList.appendChild(notificationElement);
        });
    }

    createNotificationElement(notification) {
        const div = document.createElement('div');
        div.className = `notification-item p-2 ${notification.is_read ? '' : 'bg-light'}`;
        
        const content = notification.link 
            ? `<a href="${notification.link}" class="text-decoration-none text-dark">${notification.message}</a>`
            : notification.message;

        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="notification-content">${content}</div>
                <button class="btn btn-sm text-danger" onclick="notificationManager.deleteNotification(${notification.id})">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <small class="text-muted">${this.formatDate(notification.created_at)}</small>
        `;

        if (!notification.is_read) {
            div.addEventListener('click', () => this.markAsRead(notification.id));
        }

        return div;
    }

    updateUnreadCount(count) {
        if (count > 0) {
            this.notificationsCount.style.display = 'block';
            this.notificationsCount.textContent = count;
        } else {
            this.notificationsCount.style.display = 'none';
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('/Booster/server/notifications/mark-read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            });

            if (response.ok) {
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Errore nel marcare la notifica come letta:', error);
        }
    }

    async deleteNotification(notificationId) {
        try {
            const response = await fetch('/Booster/server/notifications/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            });

            if (response.ok) {
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Errore nella cancellazione della notifica:', error);
        }
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return 'Proprio ora';
        if (diff < 3600000) return `${Math.floor(diff/60000)} minuti fa`;
        if (diff < 86400000) return `${Math.floor(diff/3600000)} ore fa`;
        if (diff < 604800000) return `${Math.floor(diff/86400000)} giorni fa`;

        return date.toLocaleDateString('it-IT');
    }
}

// Inizializza il gestore delle notifiche quando il DOM è caricato
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
});