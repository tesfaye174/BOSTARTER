/**
 * Centralized Error Handling System for BOSTARTER
 * Consolidates all silent error handling patterns into a unified approach
 */

class ErrorHandler {
    constructor() {
        this.config = {
            enableLogging: false, // Disabled for production
            enableConsole: false, // All console output disabled
            enableNotifications: true,
            silentModes: {
                dashboard: true,
                auth: true,
                api: true,
                notifications: true,
                cache: true
            }
        };
    }

    /**
     * Handle dashboard-related errors silently
     */
    handleDashboardError(error, context = {}) {
        if (this.config.silentModes.dashboard) {
            // Silent handling for dashboard errors
            if (context.showFallback && context.fallbackContainer) {
                this.showFallbackContent(context.fallbackContainer, context.fallbackMessage);
            }
            return;
        }
        this.logError('dashboard', error, context);
    }

    /**
     * Handle authentication errors silently
     */
    handleAuthError(error, context = {}) {
        if (this.config.silentModes.auth) {
            // Silent handling for auth errors
            if (context.redirectOnUnauthorized && error.message.includes('401')) {
                window.location.href = '/frontend/auth/login.php';
            }
            return;
        }
        this.logError('auth', error, context);
    }

    /**
     * Handle API call errors silently
     */
    handleApiError(error, context = {}) {
        if (this.config.silentModes.api) {
            // Silent handling for API errors
            if (context.useCachedData && context.cacheKey) {
                this.loadCachedFallback(context.cacheKey, context.updateMethod);
            }
            return;
        }
        this.logError('api', error, context);
    }

    /**
     * Handle notification-related errors silently
     */
    handleNotificationError(error, context = {}) {
        if (this.config.silentModes.notifications) {
            // Silent handling for notification errors
            return;
        }
        this.logError('notifications', error, context);
    }

    /**
     * Handle cache operations errors silently
     */
    handleCacheError(error, context = {}) {
        if (this.config.silentModes.cache) {
            // Silent handling for cache errors
            return;
        }
        this.logError('cache', error, context);
    }

    /**
     * Show fallback content for failed operations
     */
    showFallbackContent(container, message = 'Content unavailable') {
        if (container && typeof container === 'string') {
            const element = document.getElementById(container);
            if (element) {
                element.innerHTML = `<p class="text-center text-gray-500">${message}</p>`;
            }
        } else if (container && container.innerHTML) {
            container.innerHTML = `<p class="text-center text-gray-500">${message}</p>`;
        }
    }

    /**
     * Load cached data as fallback
     */
    loadCachedFallback(cacheKey, updateMethod) {
        try {
            const cached = localStorage.getItem(`bostarter_cache_${cacheKey}`);
            if (cached && updateMethod && typeof updateMethod === 'function') {
                updateMethod(JSON.parse(cached));
            }
        } catch (error) {
            // Silent cache fallback error
        }
    }

    /**
     * Internal logging method (disabled in production)
     */
    logError(type, error, context) {
        if (!this.config.enableLogging) return;

        // This would typically send to a logging service
        // Currently disabled for production
    }

    /**
     * Configure error handling behavior
     */
    configure(options = {}) {
        this.config = { ...this.config, ...options };
    }
}

// Create global instance
window.ErrorHandler = new ErrorHandler();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ErrorHandler;
}
