// BOSTARTER Utils
// Enhanced and expanded utility functions 

class Utils {
    /**
     * Date formatting with internationalization support
     * @param {Date|string} date - Date object or date string
     * @param {Object} options - Intl.DateTimeFormat options
     * @param {string} locale - Locale code (default: 'it-IT')
     * @returns {string} Formatted date string
     */
    static formatDate(date, options = {}, locale = 'it-IT') {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        return new Intl.DateTimeFormat(locale, { ...defaultOptions, ...options }).format(new Date(date));
    }

    /**
     * Format relative time (e.g., "2 days ago")
     * @param {Date|string} date - Date object or date string 
     * @param {string} locale - Locale code (default: 'it-IT')
     * @returns {string} Relative time string
     */
    static formatRelativeTime(date, locale = 'it-IT') {
        const now = new Date();
        const then = new Date(date);
        const diffSeconds = Math.floor((now - then) / 1000);

        // Define time units and their thresholds in seconds
        const units = [
            { name: 'year', seconds: 31536000, format: 'year' },
            { name: 'month', seconds: 2592000, format: 'month' },
            { name: 'week', seconds: 604800, format: 'week' },
            { name: 'day', seconds: 86400, format: 'day' },
            { name: 'hour', seconds: 3600, format: 'hour' },
            { name: 'minute', seconds: 60, format: 'minute' },
            { name: 'second', seconds: 1, format: 'second' }
        ];

        // Find the appropriate unit
        for (const unit of units) {
            const value = Math.floor(diffSeconds / unit.seconds);
            if (value >= 1) {
                try {
                    const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
                    return rtf.format(-value, unit.format);
                } catch (e) {
                    // Fallback if Intl.RelativeTimeFormat is not supported
                    return `${value} ${unit.name}${value > 1 ? 's' : ''} ago`;
                }
            }
        }

        return 'just now';
    }

    /**
     * Format a number as currency
     * @param {number} amount - Amount to format 
     * @param {string} currency - Currency code (default: 'EUR')
     * @param {string} locale - Locale code (default: 'it-IT')
     * @returns {string} Formatted currency string
     */
    static formatCurrency(amount, currency = 'EUR', locale = 'it-IT') {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }    /**
     * Format a number with thousands separators
     * @param {number} number - Number to format
     * @param {string} locale - Locale code (default: 'it-IT') 
     * @returns {string} Formatted number string
     */
    static formatNumber(number, locale = 'it-IT') {
        return new Intl.NumberFormat(locale).format(number);
    }

    /**
     * Format a number in compact form (e.g., 1.5K, 2.3M)
     * @param {number} number - Number to format 
     * @param {number} decimals - Number of decimal places (default: 1)
     * @returns {string} Formatted compact number
     */
    static formatCompactNumber(number, decimals = 1) {
        if (number >= 1000000) {
            return (number / 1000000).toFixed(decimals) + 'M';
        }
        if (number >= 1000) {
            return (number / 1000).toFixed(decimals) + 'K';
        }
        return number.toLocaleString();
    }

    /**
     * Calculate progress percentage
     * @param {number} current - Current value
     * @param {number} target - Target value
     * @returns {number} Progress percentage (0-100)
     */
    static calculateProgress(current, target) {
        return Math.min(Math.round((current / target) * 100), 100);
    }    /**
     * Safely escape HTML to prevent XSS attacks
     * @param {string} text - Text to escape
     * @returns {string} Escaped HTML
     */
    static escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Alias for backward compatibility
    static escapeHTML(text) {
        return this.escapeHtml(text);
    }

    /**
     * Capitalize first letter of a string
     * @param {string} str - Input string
     * @returns {string} String with first letter capitalized
     */
    static capitalizeFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * Truncate text to a specific length with ellipsis
     * @param {string} text - Text to truncate
     * @param {number} maxLength - Maximum length
     * @returns {string} Truncated text
     */
    static truncateText(text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    /**
     * Sanitize HTML to prevent XSS (legacy method - use escapeHtml instead)
     * @param {string} html - HTML to sanitize
     * @returns {string} Sanitized HTML
     */
    static sanitizeHTML(html) {
        return this.escapeHtml(html);
    }

    /**
     * Generate a slug from text
     * @param {string} text - Text to convert to slug
     * @returns {string} URL-friendly slug
     */
    static generateSlug(text) {
        if (!text) return '';
        return text
            .toLowerCase()
            .replace(/[^\w\s-]/g, '') // Remove special chars except spaces and dashes
            .replace(/\s+/g, '-')     // Replace spaces with dashes
            .replace(/-+/g, '-')      // Replace multiple dashes with single dash
            .replace(/^-+|-+$/g, ''); // Trim dashes from start and end
    }

    /**
     * Copy text to clipboard
     * @param {string} text - Text to copy
     * @returns {Promise} Promise that resolves when text is copied
     */
    static copyToClipboard(text) {
        // Try using the modern Clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        // Fallback for older browsers
        return new Promise((resolve, reject) => {
            try {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';  // Avoid scrolling to bottom
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);

                if (successful) {
                    resolve();
                } else {
                    reject(new Error('Could not copy text'));
                }
            } catch (err) {
                reject(err);
            }
        });
    }

    /**
     * Debounce a function call
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {Function} Debounced function
     */
    static debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle a function call
     * @param {Function} func - Function to throttle
     * @param {number} limit - Throttle time limit in milliseconds
     * @returns {Function} Throttled function
     */
    static throttle(func, limit = 300) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Format file size in human-readable format
     * @param {number} bytes - File size in bytes
     * @param {number} decimals - Number of decimal places
     * @returns {string} Formatted file size
     */
    static formatFileSize(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    /**
     * Get remaining time until a date
     * @param {Date|string} endDate - Target date
     * @returns {Object} Object with days, hours, minutes, seconds
     */
    static getTimeRemaining(endDate) {
        const total = new Date(endDate) - new Date();
        const seconds = Math.floor((total / 1000) % 60);
        const minutes = Math.floor((total / 1000 / 60) % 60);
        const hours = Math.floor((total / (1000 * 60 * 60)) % 24);
        const days = Math.floor(total / (1000 * 60 * 60 * 24));

        return {
            total,
            days,
            hours,
            minutes,
            seconds
        };
    }

    /**
     * Validate email
     * @param {string} email - Email to validate
     * @returns {boolean} Whether email is valid
     */
    static isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Check password strength
     * @param {string} password - Password to check
     * @returns {Object} Password strength metrics
     */
    static checkPasswordStrength(password) {
        if (!password) {
            return { score: 0, feedback: 'Password is required' };
        }

        let score = 0;
        let feedback = [];

        // Length check
        if (password.length < 8) {
            feedback.push('Password should be at least 8 characters');
        } else {
            score += password.length > 12 ? 2 : 1;
        }

        // Complexity checks
        if (/[A-Z]/.test(password)) score++;
        else feedback.push('Add uppercase letters');

        if (/[a-z]/.test(password)) score++;
        else feedback.push('Add lowercase letters');

        if (/\d/.test(password)) score++;
        else feedback.push('Add numbers');

        if (/[^A-Za-z0-9]/.test(password)) score++;
        else feedback.push('Add special characters');

        // Convert score to 0-5 scale
        score = Math.min(Math.max(score, 0), 5);

        // Generate feedback based on score
        let strengthText = '';
        if (score < 2) strengthText = 'Weak';
        else if (score < 4) strengthText = 'Moderate';
        else strengthText = 'Strong';

        return {
            score,
            strength: strengthText,
            feedback: feedback.join(', ') || 'Password is strong'
        };
    }

    /**
     * Generate a random ID
     * @param {number} length - ID length
     * @returns {string} Random ID
     */
    static generateId(length = 8) {
        return Array.from({ length })
            .map(() => (Math.random() * 36 | 0).toString(36))
            .join('');
    }

    /**
     * Get browser and device info
     * @returns {Object} Browser and device information
     */
    static getBrowserInfo() {
        const ua = navigator.userAgent;
        const browser = {
            name: 'Unknown',
            version: 'Unknown',
            mobile: /Mobile|Android|iPhone|iPad|iPod/i.test(ua),
            os: 'Unknown'
        };

        // Detect browser
        if (/Firefox\/\d+/i.test(ua)) {
            browser.name = 'Firefox';
            browser.version = ua.match(/Firefox\/(\d+)/i)[1];
        } else if (/Chrome\/\d+/i.test(ua) && !/Edg\/\d+/i.test(ua)) {
            browser.name = 'Chrome';
            browser.version = ua.match(/Chrome\/(\d+)/i)[1];
        } else if (/Safari\/\d+/i.test(ua) && !/Chrome\/\d+/i.test(ua)) {
            browser.name = 'Safari';
            browser.version = ua.match(/Version\/(\d+)/i)?.[1] || 'Unknown';
        } else if (/Edg\/\d+/i.test(ua)) {
            browser.name = 'Edge';
            browser.version = ua.match(/Edg\/(\d+)/i)[1];
        } else if (/Trident/i.test(ua)) {
            browser.name = 'Internet Explorer';
            browser.version = ua.match(/rv:(\d+)/i)?.[1] || 'Unknown';
        }

        // Detect OS
        if (/Windows/i.test(ua)) {
            browser.os = 'Windows';
        } else if (/Macintosh/i.test(ua)) {
            browser.os = 'macOS';
        } else if (/Linux/i.test(ua)) {
            browser.os = 'Linux';
        } else if (/Android/i.test(ua)) {
            browser.os = 'Android';
        } else if (/iPhone|iPad|iPod/i.test(ua)) {
            browser.os = 'iOS';
        }

        return browser;
    }

    /**
     * Get query parameters from URL
     * @param {string} [url=window.location.href] - URL to parse
     * @returns {Object} Query parameters
     */
    static getQueryParams(url = window.location.href) {
        const params = {};
        const queryString = url.split('?')[1];

        if (!queryString) return params;

        const pairs = queryString.split('&');
        pairs.forEach(pair => {
            const [key, value] = pair.split('=');
            params[decodeURIComponent(key)] = decodeURIComponent(value || '');
        });

        return params;
    }

    /**
     * Create URL with query parameters
     * @param {string} baseUrl - Base URL
     * @param {Object} params - Query parameters
     * @returns {string} URL with query parameters
     */
    static buildUrl(baseUrl, params = {}) {
        const url = new URL(baseUrl, window.location.origin);

        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.append(key, value);
            }
        });

        return url.toString();
    }

    /**
     * Check if element is fully in viewport
     * @param {HTMLElement} el - Element to check
     * @returns {boolean} Whether element is fully in viewport
     */
    static isInViewport(el) {
        if (!el) return false;

        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= window.innerHeight &&
            rect.right <= window.innerWidth
        );
    }

    /**
     * Check if element is partially in viewport
     * @param {HTMLElement} el - Element to check
     * @returns {boolean} Whether element is partially in viewport
     */
    static isPartiallyInViewport(el) {
        if (!el) return false;

        const rect = el.getBoundingClientRect();
        return (
            rect.top <= window.innerHeight &&
            rect.bottom >= 0 &&
            rect.left <= window.innerWidth &&
            rect.right >= 0
        );
    }

    /**
     * Detect dark mode preference
     * @returns {boolean} Whether dark mode is preferred
     */
    static prefersDarkMode() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    /**
     * JSON stringify with circular reference handling
     * @param {*} obj - Object to stringify
     * @returns {string} JSON string
     */
    static safeStringify(obj) {
        const seen = new WeakSet();
        return JSON.stringify(obj, (key, value) => {
            if (typeof value === 'object' && value !== null) {
                if (seen.has(value)) {
                    return '[Circular Reference]';
                }
                seen.add(value);
            }
            return value;
        });
    }
}

// Export for ES modules
export default Utils;

// Make it available globally
window.Utils = Utils;
