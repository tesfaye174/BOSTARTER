/**
 * BOSTARTER Service Worker Registration
 * Enhanced registration with update handling and user notifications
 */

class ServiceWorkerManager {
    constructor() {
        this.registration = null;
        this.updateFound = false;
        this.init();
    }

    async init() {
        if (!('serviceWorker' in navigator)) {
            console.warn('Service Worker not supported');
            return;
        }

        try {
            await this.registerServiceWorker();
            this.setupUpdateHandling();
            this.setupMessageHandling();
        } catch (error) {
            console.error('Service Worker initialization failed:', error);
        }
    }

    async registerServiceWorker() {
        try {
            this.registration = await navigator.serviceWorker.register('/frontend/sw.js', {
                scope: '/frontend/'
            });

            // Check for updates immediately
            this.registration.update();

            // Set up update listeners
            this.registration.addEventListener('updatefound', () => {
                this.handleUpdateFound();
            });

            // Listen for controlling service worker changes
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                this.handleControllerChange();
            });

        } catch (error) {
            console.error('❌ Service Worker registration failed:', error);
            throw error;
        }
    } handleUpdateFound() {
        const newWorker = this.registration.installing;

        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed') {
                if (navigator.serviceWorker.controller) {
                    // New update available
                    this.showUpdateNotification();
                } else {
                    // First install
                    this.showInstallNotification();
                }
            }
        });
    } handleControllerChange() {
        // Optionally reload the page to use the new service worker
        if (this.updateFound) {
            window.location.reload();
        }
    }

    showUpdateNotification() {
        const notification = this.createNotification(
            'App Update Available',
            'A new version of BOSTARTER is available. Click to update.',
            () => this.applyUpdate()
        );
        this.displayNotification(notification);
    }

    showInstallNotification() {
        const notification = this.createNotification(
            'App Installed',
            'BOSTARTER is now available offline!',
            () => this.dismissNotification()
        );
        this.displayNotification(notification);
    }

    createNotification(title, message, action) {
        const notification = document.createElement('div');
        notification.className = 'sw-notification';
        notification.innerHTML = `
            <div class="sw-notification-content">
                <h4>${title}</h4>
                <p>${message}</p>
                <div class="sw-notification-actions">
                    <button class="btn btn-primary sw-action-btn">Update</button>
                    <button class="btn btn-secondary sw-dismiss-btn">Later</button>
                </div>
            </div>
        `;

        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 10000;
            max-width: 300px;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        `;

        // Add event listeners
        notification.querySelector('.sw-action-btn').addEventListener('click', action);
        notification.querySelector('.sw-dismiss-btn').addEventListener('click', () => {
            this.dismissNotification(notification);
        });

        return notification;
    }

    displayNotification(notification) {
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Auto-dismiss after 10 seconds
        setTimeout(() => {
            this.dismissNotification(notification);
        }, 10000);
    }

    dismissNotification(notification = null) {
        const notificationElement = notification || document.querySelector('.sw-notification');
        if (notificationElement) {
            notificationElement.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (notificationElement.parentNode) {
                    notificationElement.parentNode.removeChild(notificationElement);
                }
            }, 300);
        }
    }

    async applyUpdate() {
        if (this.registration && this.registration.waiting) {
            // Tell the waiting service worker to skip waiting
            this.registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            this.updateFound = true;
        }
    }

    setupMessageHandling() {
        navigator.serviceWorker.addEventListener('message', (event) => {
            const { type, data } = event.data;

            switch (type) {
                case 'SW_UPDATED':
                    break;
                case 'CACHE_UPDATED':
                    break;
                case 'OFFLINE_READY':
                    this.showOfflineReadyNotification();
                    break; default:
                // Unknown message type
            }
        });
    }

    showOfflineReadyNotification() {
        const notification = this.createNotification(
            'Offline Ready',
            'BOSTARTER is now available offline!',
            () => this.dismissNotification()
        );
        this.displayNotification(notification);
    }

    // Manual update check
    async checkForUpdates() {
        if (this.registration) {
            try {
                await this.registration.update();
                console.log('✅ Manual update check completed');
            } catch (error) {
                console.error('❌ Manual update check failed:', error);
            }
        }
    }

    // Get registration info
    getRegistrationInfo() {
        if (this.registration) {
            return {
                scope: this.registration.scope,
                active: !!this.registration.active,
                installing: !!this.registration.installing,
                waiting: !!this.registration.waiting,
                updateViaCache: this.registration.updateViaCache
            };
        }
        return null;
    }

    // Unregister service worker (for debugging)
    async unregister() {
        if (this.registration) {
            try {
                await this.registration.unregister();
                console.log('✅ Service Worker unregistered');
                this.registration = null;
            } catch (error) {
                console.error('❌ Service Worker unregistration failed:', error);
            }
        }
    }
}

// Initialize Service Worker Manager
document.addEventListener('DOMContentLoaded', () => {
    window.ServiceWorkerManager = new ServiceWorkerManager();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ServiceWorkerManager;
}