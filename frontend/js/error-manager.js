// Enhanced Error Handling and Recovery System
class ErrorManager {
    constructor() {
        this.errorLog = [];
        this.retryAttempts = new Map();
        this.maxRetries = 3;
        this.fallbackStrategies = new Map();
        this.userNotifications = [];
        this.init();
    }

    init() {
        this.setupGlobalErrorHandling();
        this.setupUnhandledRejectionHandling();
        this.setupNetworkErrorHandling();
        this.setupFormErrorHandling();
        this.setupAPIErrorHandling();
        this.createErrorNotificationSystem();
        this.setupErrorRecovery();
    }

    // Global error handling
    setupGlobalErrorHandling() {
        window.addEventListener('error', (event) => {
            this.handleError({
                type: 'javascript',
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error,
                stack: event.error?.stack,
                timestamp: Date.now()
            });
        });

        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError({
                type: 'unhandled_promise',
                message: event.reason?.message || 'Unhandled promise rejection',
                reason: event.reason,
                stack: event.reason?.stack,
                timestamp: Date.now()
            });

            // Prevent the error from being logged to console
            event.preventDefault();
        });
    }

    setupUnhandledRejectionHandling() {
        window.addEventListener('unhandledrejection', (event) => {
            const error = {
                type: 'promise_rejection',
                message: event.reason?.message || 'Promise rejected',
                reason: event.reason,
                stack: event.reason?.stack,
                timestamp: Date.now()
            };

            this.handleError(error);
            this.offerRecoveryOptions(error);
        });
    }

    // Network error handling
    setupNetworkErrorHandling() {
        // Intercept fetch requests
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            try {
                const response = await originalFetch(...args);

                if (!response.ok) {
                    const error = {
                        type: 'network',
                        status: response.status,
                        statusText: response.statusText,
                        url: args[0],
                        method: args[1]?.method || 'GET',
                        timestamp: Date.now()
                    };

                    this.handleNetworkError(error, args);
                }

                return response;
            } catch (fetchError) {
                const error = {
                    type: 'network_failure',
                    message: fetchError.message,
                    url: args[0],
                    method: args[1]?.method || 'GET',
                    timestamp: Date.now()
                };

                return this.handleNetworkFailure(error, args);
            }
        };

        // Monitor online/offline status
        window.addEventListener('online', () => {
            this.handleOnlineStatusChange(true);
        });

        window.addEventListener('offline', () => {
            this.handleOnlineStatusChange(false);
        });
    }

    async handleNetworkError(error, originalArgs) {
        this.logError(error);

        // Implement retry logic for specific status codes
        if ([408, 429, 500, 502, 503, 504].includes(error.status)) {
            return this.retryRequest(originalArgs, error);
        }

        // Handle different status codes appropriately
        switch (error.status) {
            case 401:
                this.handleAuthenticationError();
                break;
            case 403:
                this.handleAuthorizationError();
                break;
            case 404:
                this.handleNotFoundError(error.url);
                break;
            case 422:
                this.handleValidationError();
                break;
            default:
                this.showUserFriendlyError(error);
        }

        throw error; // Re-throw for calling code to handle
    }

    async handleNetworkFailure(error, originalArgs) {
        this.logError(error);

        // Check if offline
        if (!navigator.onLine) {
            return this.handleOfflineRequest(originalArgs);
        }

        // Retry with exponential backoff
        return this.retryRequest(originalArgs, error);
    }

    async retryRequest(args, error) {
        const url = args[0];
        const attempts = this.retryAttempts.get(url) || 0;

        if (attempts >= this.maxRetries) {
            this.retryAttempts.delete(url);
            this.showRetryLimitError(error);
            throw error;
        }

        this.retryAttempts.set(url, attempts + 1);

        // Exponential backoff: 1s, 2s, 4s
        const delay = Math.pow(2, attempts) * 1000;

        this.showRetryNotification(attempts + 1, delay);

        await this.wait(delay);

        try {
            const response = await fetch(...args);
            this.retryAttempts.delete(url);
            this.hideRetryNotification();
            return response;
        } catch (retryError) {
            return this.retryRequest(args, retryError);
        }
    }

    // Form error handling
    setupFormErrorHandling() {
        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (form.tagName === 'FORM') {
                this.handleFormSubmission(form, event);
            }
        });

        // Real-time validation
        document.addEventListener('input', (event) => {
            if (event.target.form) {
                this.clearFieldError(event.target);
                this.validateField(event.target);
            }
        });
    }

    async handleFormSubmission(form, event) {
        try {
            this.clearFormErrors(form);
            this.showFormLoading(form);

            // Validate form before submission
            const validationResult = this.validateForm(form);
            if (!validationResult.isValid) {
                event.preventDefault();
                this.showFormErrors(form, validationResult.errors);
                this.hideFormLoading(form);
                return;
            }

        } catch (error) {
            event.preventDefault();
            this.hideFormLoading(form);

            const formError = {
                type: 'form_submission',
                form: form.id || form.className,
                message: error.message,
                timestamp: Date.now()
            };

            this.handleError(formError);
            this.showFormError(form, 'An error occurred while submitting the form. Please try again.');
        }
    }

    validateForm(form) {
        const errors = [];
        const formData = new FormData(form);

        // Required field validation
        form.querySelectorAll('[required]').forEach(field => {
            if (!formData.get(field.name) || formData.get(field.name).toString().trim() === '') {
                errors.push({
                    field: field.name,
                    message: `${this.getFieldLabel(field)} is required`
                });
            }
        });

        // Email validation
        form.querySelectorAll('input[type="email"]').forEach(field => {
            const value = formData.get(field.name);
            if (value && !this.isValidEmail(value)) {
                errors.push({
                    field: field.name,
                    message: 'Please enter a valid email address'
                });
            }
        });

        // Password validation
        form.querySelectorAll('input[type="password"]').forEach(field => {
            const value = formData.get(field.name);
            if (value && !this.isValidPassword(value)) {
                errors.push({
                    field: field.name,
                    message: 'Password must be at least 8 characters long and contain uppercase, lowercase, and numbers'
                });
            }
        });

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    // API error handling
    setupAPIErrorHandling() {
        // Enhance XMLHttpRequest
        const originalXHROpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function (method, url, ...args) {
            this.addEventListener('error', (event) => {
                const error = {
                    type: 'xhr_error',
                    method,
                    url,
                    status: this.status,
                    statusText: this.statusText,
                    timestamp: Date.now()
                };
                window.errorManager.handleError(error);
            });

            this.addEventListener('timeout', (event) => {
                const error = {
                    type: 'xhr_timeout',
                    method,
                    url,
                    timeout: this.timeout,
                    timestamp: Date.now()
                };
                window.errorManager.handleError(error);
            });

            return originalXHROpen.call(this, method, url, ...args);
        };
    }

    // Error notification system
    createErrorNotificationSystem() {
        // Create notification container
        const container = document.createElement('div');
        container.id = 'error-notifications';
        container.className = 'error-notifications';
        container.innerHTML = `
            <style>
                .error-notifications {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    max-width: 400px;
                }
                
                .error-notification {
                    background: #f44336;
                    color: white;
                    padding: 16px;
                    margin-bottom: 10px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    animation: slideIn 0.3s ease;
                    position: relative;
                }
                
                .warning-notification {
                    background: #ff9800;
                }
                
                .info-notification {
                    background: #2196f3;
                }
                
                .success-notification {
                    background: #4caf50;
                }
                
                .retry-notification {
                    background: #673ab7;
                }
                
                .error-notification .close-btn {
                    position: absolute;
                    top: 8px;
                    right: 12px;
                    background: none;
                    border: none;
                    color: white;
                    font-size: 18px;
                    cursor: pointer;
                    opacity: 0.7;
                    transition: opacity 0.2s;
                }
                
                .error-notification .close-btn:hover {
                    opacity: 1;
                }
                
                .error-notification .retry-btn {
                    background: rgba(255,255,255,0.2);
                    border: 1px solid rgba(255,255,255,0.3);
                    color: white;
                    padding: 6px 12px;
                    margin-top: 8px;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: background 0.2s;
                }
                
                .error-notification .retry-btn:hover {
                    background: rgba(255,255,255,0.3);
                }
                
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            </style>
        `;

        document.body.appendChild(container);
    }

    showNotification(message, type = 'error', options = {}) {
        const container = document.getElementById('error-notifications');
        const notification = document.createElement('div');
        notification.className = `error-notification ${type}-notification`;

        const id = 'notification-' + Date.now();
        notification.id = id;

        notification.innerHTML = `
            <div class="notification-content">
                <strong>${options.title || this.getTypeTitle(type)}</strong>
                <div>${message}</div>
                ${options.action ? `<button class="retry-btn" onclick="window.errorManager.handleNotificationAction('${id}', '${options.action}')">${options.actionText || 'Retry'}</button>` : ''}
            </div>
            <button class="close-btn" onclick="window.errorManager.hideNotification('${id}')">&times;</button>
        `;

        container.appendChild(notification);

        // Auto-hide after delay
        if (options.autoHide !== false) {
            setTimeout(() => {
                this.hideNotification(id);
            }, options.duration || 5000);
        }

        return id;
    }

    hideNotification(id) {
        const notification = document.getElementById(id);
        if (notification) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }

    // Error recovery strategies
    setupErrorRecovery() {
        this.fallbackStrategies.set('authentication', () => {
            this.redirectToLogin();
        });

        this.fallbackStrategies.set('network', () => {
            this.enableOfflineMode();
        });

        this.fallbackStrategies.set('validation', (error, context) => {
            this.highlightValidationErrors(context);
        });
    }

    handleError(error) {
        this.logError(error);

        // Determine appropriate recovery strategy
        const strategy = this.fallbackStrategies.get(error.type);
        if (strategy) {
            strategy(error);
        } else {
            this.showGenericErrorNotification(error);
        }

        // Report to analytics/logging service
        this.reportError(error);
    }

    logError(error) {
        this.errorLog.push({
            ...error,
            userAgent: navigator.userAgent,
            url: window.location.href,
            timestamp: Date.now()
        });

        // Keep only last 100 errors to prevent memory issues
        if (this.errorLog.length > 100) {
            this.errorLog = this.errorLog.slice(-100);
        }

        console.error('Error logged:', error);
    }

    reportError(error) {
        // Send error to logging service
        if (navigator.sendBeacon) {
            const payload = JSON.stringify({
                type: 'error',
                error: {
                    ...error,
                    userAgent: navigator.userAgent,
                    url: window.location.href
                }
            });

            navigator.sendBeacon('/api/errors/log', payload);
        }
    }

    // Specific error handlers
    handleAuthenticationError() {
        this.showNotification(
            'Your session has expired. Please log in again.',
            'warning',
            {
                title: 'Authentication Required',
                action: 'login',
                actionText: 'Log In',
                autoHide: false
            }
        );
    }

    handleAuthorizationError() {
        this.showNotification(
            'You do not have permission to access this resource.',
            'error',
            { title: 'Access Denied' }
        );
    }

    handleNotFoundError(url) {
        this.showNotification(
            'The requested resource was not found.',
            'error',
            {
                title: 'Not Found',
                action: 'home',
                actionText: 'Go Home'
            }
        );
    }

    handleValidationError() {
        this.showNotification(
            'Please check your input and try again.',
            'warning',
            { title: 'Validation Error' }
        );
    }

    handleOnlineStatusChange(isOnline) {
        if (isOnline) {
            this.showNotification(
                'Connection restored. Syncing data...',
                'success',
                { title: 'Back Online' }
            );
            this.syncOfflineData();
        } else {
            this.showNotification(
                'You are now offline. Some features may be limited.',
                'info',
                { title: 'Offline Mode' }
            );
        }
    }

    // Utility methods
    wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    getTypeTitle(type) {
        const titles = {
            error: 'Error',
            warning: 'Warning',
            info: 'Information',
            success: 'Success',
            retry: 'Retrying'
        };
        return titles[type] || 'Notification';
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    isValidPassword(password) {
        return password.length >= 8 &&
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password) &&
            /\d/.test(password);
    }

    getFieldLabel(field) {
        return field.getAttribute('data-label') ||
            field.previousElementSibling?.textContent ||
            field.placeholder ||
            field.name;
    }

    // Form helper methods
    clearFormErrors(form) {
        form.querySelectorAll('.field-error').forEach(error => error.remove());
        form.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
    }

    showFormErrors(form, errors) {
        errors.forEach(error => {
            const field = form.querySelector(`[name="${error.field}"]`);
            if (field) {
                this.showFieldError(field, error.message);
            }
        });
    }

    showFieldError(field, message) {
        field.classList.add('error');

        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;

        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }

    clearFieldError(field) {
        field.classList.remove('error');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    showFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = 'Please wait...';
        }
    }

    hideFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtn.dataset.originalText || 'Submit';
            delete submitBtn.dataset.originalText;
        }
    }

    // Public methods for manual error handling
    handleNotificationAction(notificationId, action) {
        this.hideNotification(notificationId);

        switch (action) {
            case 'login':
                this.redirectToLogin();
                break;
            case 'home':
                window.location.href = '/';
                break;
            case 'retry':
                window.location.reload();
                break;
        }
    }

    redirectToLogin() {
        window.location.href = '/frontend/login.php';
    }

    syncOfflineData() {
        // Trigger sync of offline data
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'SYNC_OFFLINE_DATA'
            });
        }
    }

    showRetryNotification(attempt, delay) {
        this.showNotification(
            `Connection failed. Retrying in ${delay / 1000} seconds... (Attempt ${attempt}/${this.maxRetries})`,
            'retry',
            {
                title: 'Retrying',
                autoHide: false
            }
        );
    }

    hideRetryNotification() {
        const retryNotifications = document.querySelectorAll('.retry-notification');
        retryNotifications.forEach(notification => {
            this.hideNotification(notification.id);
        });
    }

    showRetryLimitError(error) {
        this.showNotification(
            'Unable to complete the request after multiple attempts. Please check your connection and try again.',
            'error',
            {
                title: 'Request Failed',
                action: 'retry',
                actionText: 'Try Again',
                autoHide: false
            }
        );
    }

    showGenericErrorNotification(error) {
        this.showNotification(
            'An unexpected error occurred. Our team has been notified.',
            'error',
            {
                title: 'Something went wrong',
                action: 'retry',
                actionText: 'Reload Page'
            }
        );
    }

    // Public API for getting error statistics
    getErrorStats() {
        const now = Date.now();
        const lastHour = now - (60 * 60 * 1000);
        const last24Hours = now - (24 * 60 * 60 * 1000);

        const recentErrors = this.errorLog.filter(error => error.timestamp > lastHour);
        const dailyErrors = this.errorLog.filter(error => error.timestamp > last24Hours);

        return {
            total: this.errorLog.length,
            lastHour: recentErrors.length,
            last24Hours: dailyErrors.length,
            byType: this.groupErrorsByType(dailyErrors),
            mostRecent: this.errorLog[this.errorLog.length - 1]
        };
    }

    groupErrorsByType(errors) {
        return errors.reduce((acc, error) => {
            acc[error.type] = (acc[error.type] || 0) + 1;
            return acc;
        }, {});
    }
}

// Initialize error manager
window.errorManager = new ErrorManager();

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ErrorManager;
}
