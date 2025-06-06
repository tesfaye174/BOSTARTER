/**
 * BOSTARTER UI Components - Centralized Loading & Notification System
 * Eliminates duplicate loading overlays and notification containers across all category pages
 */

class UIComponents {
    constructor() {
        this.loadingOverlay = null;
        this.notificationContainer = null;
        this.init();
    }

    init() {
        this.createLoadingOverlay();
        this.createNotificationContainer();
        this.setupGlobalStyles();
    }

    /**
     * Create centralized loading overlay
     */
    createLoadingOverlay() {
        // Remove existing loading overlay if present
        const existing = document.getElementById('loadingOverlay');
        if (existing) {
            existing.remove();
        }

        this.loadingOverlay = document.createElement('div');
        this.loadingOverlay.id = 'loadingOverlay';
        this.loadingOverlay.className = 'fixed inset-0 bg-white dark:bg-gray-900 z-50 flex items-center justify-center transition-opacity duration-500';
        this.loadingOverlay.setAttribute('aria-hidden', 'true');
        this.loadingOverlay.style.display = 'none';

        // Default loading content (can be customized per category)
        this.loadingOverlay.innerHTML = `
            <div class="text-center">
                <div class="relative mb-4">
                    <div class="w-16 h-16 border-4 border-primary-200 dark:border-gray-700 border-t-primary-500 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i id="loadingIcon" class="ri-loader-line text-2xl text-primary-500 animate-pulse"></i>
                    </div>
                </div>
                <p id="loadingText" class="text-lg font-medium text-gray-900 dark:text-white">Caricamento in corso...</p>
                <div class="mt-2 flex justify-center space-x-1">
                    <div class="w-2 h-2 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-2 h-2 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-2 h-2 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>
        `;

        document.body.appendChild(this.loadingOverlay);
    }

    /**
     * Create centralized notification container
     */
    createNotificationContainer() {
        // Remove existing notification container if present
        const existing = document.getElementById('notificationContainer');
        if (existing) {
            existing.remove();
        }

        this.notificationContainer = document.createElement('div');
        this.notificationContainer.id = 'notificationContainer';
        this.notificationContainer.className = 'fixed top-4 right-4 z-50 space-y-2';
        this.notificationContainer.setAttribute('aria-live', 'polite');
        this.notificationContainer.setAttribute('aria-label', 'Notifiche');

        document.body.appendChild(this.notificationContainer);
    }

    /**
     * Show loading overlay with optional customization
     */
    showLoading(options = {}) {
        const {
            icon = 'ri-loader-line',
            text = 'Caricamento in corso...',
            category = null
        } = options;

        // Customize loading text and icon based on category
        if (category) {
            const categoryConfig = this.getCategoryConfig(category);
            const iconElement = this.loadingOverlay.querySelector('#loadingIcon');
            const textElement = this.loadingOverlay.querySelector('#loadingText');

            if (iconElement) iconElement.className = `${categoryConfig.icon} text-2xl text-primary-500 animate-pulse`;
            if (textElement) textElement.textContent = categoryConfig.loadingText;
        } else {
            const iconElement = this.loadingOverlay.querySelector('#loadingIcon');
            const textElement = this.loadingOverlay.querySelector('#loadingText');

            if (iconElement) iconElement.className = `${icon} text-2xl text-primary-500 animate-pulse`;
            if (textElement) textElement.textContent = text;
        }

        this.loadingOverlay.style.display = 'flex';
        this.loadingOverlay.setAttribute('aria-hidden', 'false');

        // Fade in
        requestAnimationFrame(() => {
            this.loadingOverlay.style.opacity = '1';
        });
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        if (!this.loadingOverlay) return;

        this.loadingOverlay.style.opacity = '0';
        this.loadingOverlay.setAttribute('aria-hidden', 'true');

        setTimeout(() => {
            this.loadingOverlay.style.display = 'none';
        }, 500);
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');

        const icons = {
            success: 'ri-check-line',
            error: 'ri-error-warning-line',
            warning: 'ri-alert-line',
            info: 'ri-information-line'
        };

        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-primary-500'
        };

        notification.className = `${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform translate-x-full transition-transform duration-300`;
        notification.innerHTML = `
            <i class="${icons[type]}"></i>
            <span>${message}</span>
            <button class="ml-auto hover:bg-white hover:bg-opacity-20 rounded p-1" onclick="this.parentElement.remove()">
                <i class="ri-close-line"></i>
            </button>
        `;

        this.notificationContainer.appendChild(notification);

        // Slide in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, duration);

        return notification;
    }

    /**
     * Get category-specific configuration
     */
    getCategoryConfig(category) {
        const configs = {
            arte: {
                icon: 'ri-brush-line',
                loadingText: 'Caricando progetti arte...'
            },
            tecnologia: {
                icon: 'ri-computer-line',
                loadingText: 'Caricando progetti tecnologia...'
            },
            film: {
                icon: 'ri-film-line',
                loadingText: 'Caricando progetti film...'
            },
            musica: {
                icon: 'ri-music-line',
                loadingText: 'Caricando progetti musica...'
            },
            editoriale: {
                icon: 'ri-book-line',
                loadingText: 'Caricando progetti editoriale...'
            },
            fotografia: {
                icon: 'ri-camera-line',
                loadingText: 'Caricando progetti fotografia...'
            },
            cibo: {
                icon: 'ri-restaurant-line',
                loadingText: 'Caricando progetti cibo...'
            },
            danza: {
                icon: 'ri-music-2-line',
                loadingText: 'Caricando progetti danza...'
            },
            artigianato: {
                icon: 'ri-hammer-line',
                loadingText: 'Caricando progetti artigianato...'
            },
            design: {
                icon: 'ri-palette-line',
                loadingText: 'Caricando progetti design...'
            },
            teatro: {
                icon: 'ri-mask-line',
                loadingText: 'Caricando progetti teatro...'
            },
            moda: {
                icon: 'ri-shirt-line',
                loadingText: 'Caricando progetti moda...'
            },
            fumetti: {
                icon: 'ri-book-2-line',
                loadingText: 'Caricando progetti fumetti...'
            },
            giochi: {
                icon: 'ri-gamepad-line',
                loadingText: 'Caricando progetti giochi...'
            },
            giornalismo: {
                icon: 'ri-newspaper-line',
                loadingText: 'Caricando progetti giornalismo...'
            }
        };

        return configs[category] || {
            icon: 'ri-loader-line',
            loadingText: 'Caricamento in corso...'
        };
    }

    /**
     * Setup global styles for loading and notifications
     */
    setupGlobalStyles() {
        // Add skip link styles if not present
        if (!document.querySelector('style[data-ui-components]')) {
            const style = document.createElement('style');
            style.setAttribute('data-ui-components', 'true');
            style.textContent = `
                .skip-link {
                    position: absolute;
                    top: -40px;
                    left: 6px;
                    background: var(--primary, #3b82f6);
                    color: white;
                    padding: 8px;
                    text-decoration: none;
                    border-radius: 4px;
                    z-index: 1000;
                    transition: top 0.3s;
                }
                
                .skip-link:focus {
                    top: 6px;
                }
                
                /* Improved loading animation */
                @keyframes enhanced-bounce {
                    0%, 20%, 53%, 80%, 100% {
                        transform: translate3d(0,0,0);
                    }
                    40%, 43% {
                        transform: translate3d(0,-30px,0);
                    }
                    70% {
                        transform: translate3d(0,-15px,0);
                    }
                    90% {
                        transform: translate3d(0,-4px,0);
                    }
                }
                
                .animate-enhanced-bounce {
                    animation: enhanced-bounce 1.4s ease-in-out infinite;
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Auto-detect category from current page
     */
    detectCategory() {
        const path = window.location.pathname;
        const categoryMatch = path.match(/\/assets\/([^\/]+)\//);
        return categoryMatch ? categoryMatch[1] : null;
    }

    /**
     * Initialize loading for current category
     */
    initCategoryLoading() {
        const category = this.detectCategory();
        if (category) {
            this.showLoading({ category });

            // Auto-hide loading after page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => this.hideLoading(), 1000);
                });
            } else {
                setTimeout(() => this.hideLoading(), 1000);
            }
        }
    }

    /**
     * Create skip link if not present
     */
    ensureSkipLink() {
        if (!document.querySelector('.skip-link')) {
            const skipLink = document.createElement('a');
            skipLink.href = '#main-content';
            skipLink.className = 'skip-link';
            skipLink.textContent = 'Salta al contenuto principale';
            document.body.insertBefore(skipLink, document.body.firstChild);
        }
    }
}

// Global instance
window.UIComponents = new UIComponents();

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.UIComponents.ensureSkipLink();
    window.UIComponents.initCategoryLoading();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UIComponents;
}
