// BOSTARTER Core Systems Loader
// Loads and initializes all core functionalities

// Import core systems
import Utils from './Utils.js';
import notificationSystem from './NotificationSystem.js';
import animationSystem from './AnimationSystem.js';

// Ensure the window object exists (browser environment)
if (typeof window !== 'undefined') {
    // Create a namespace for our application
    window.BOSTARTER = {
        // Core systems
        Utils,
        NotificationSystem: notificationSystem,
        AnimationSystem: animationSystem,

        // App state
        state: {
            isInitialized: false,
            isOnline: navigator.onLine,
            isDarkMode: window.matchMedia('(prefers-color-scheme: dark)').matches,
            prefersReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches
        },        // Initialize all systems
        init() {
            if (this.state.isInitialized) {
                return this;
            }

            // Setup network status monitoring
            this._setupNetworkMonitoring();

            // Remove 'no-js' class if present
            document.documentElement.classList.remove('no-js');

            // Mark as initialized
            this.state.isInitialized = true;

            // Emit initialization event
            this._emitEvent('core:initialized');

            return this;
        },

        // Setup network status monitoring
        _setupNetworkMonitoring() {
            const handleNetworkChange = () => {
                const wasOnline = this.state.isOnline;
                this.state.isOnline = navigator.onLine;

                if (wasOnline !== this.state.isOnline) {
                    this._emitEvent('network:change', { isOnline: this.state.isOnline });

                    if (!this.state.isOnline) {
                        notificationSystem.warning('Connessione persa. Alcune funzionalità potrebbero non essere disponibili.', { duration: 0 });
                    } else {
                        notificationSystem.success('Connessione ripristinata.', { duration: 3000 });
                    }
                }
            };

            window.addEventListener('online', handleNetworkChange);
            window.addEventListener('offline', handleNetworkChange);
        },

        // Emit custom event
        _emitEvent(eventName, detail = {}) {
            const event = new CustomEvent(`bostarter:${eventName}`, {
                bubbles: true,
                detail: {
                    ...detail,
                    timestamp: new Date().toISOString()
                }
            });
            document.dispatchEvent(event);
        }
    };

    // Initialize when document is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.BOSTARTER.init();
        });
    } else {
        window.BOSTARTER.init();
    }    // Legacy helper functions for backward compatibility
    window.showNotification = (message, type = 'info', duration) => {
        return notificationSystem.show(message, type, { duration });
    };
}

// Export for module systems
export default {
    Utils,
    NotificationSystem: notificationSystem,
    AnimationSystem: animationSystem
};
