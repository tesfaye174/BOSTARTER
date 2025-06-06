// Enhanced Theme Configuration for BOSTARTER
const themeConfig = {
    light: {
        name: 'light',
        colors: {
            // Primary colors remain the same for consistency
            '--primary': '#3176FF',
            '--primary-dark': '#1E4FCC',
            '--primary-light': '#60A5FA',

            // Background colors
            '--bg-primary': '#F9FAFB',
            '--bg-secondary': '#FFFFFF',
            '--bg-tertiary': '#F3F4F6',

            // Text colors
            '--text-primary': '#111827',
            '--text-secondary': '#374151',
            '--text-tertiary': '#6B7280',

            // Border colors
            '--border-primary': '#E5E7EB',
            '--border-secondary': '#D1D5DB',

            // Glass effect
            '--glass-bg': 'rgba(255, 255, 255, 0.8)',
            '--glass-border': 'rgba(255, 255, 255, 0.2)',

            // Shadows
            '--shadow-sm': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            '--shadow-md': '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
            '--shadow-lg': '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
            '--shadow-xl': '0 20px 25px -5px rgba(0, 0, 0, 0.1)'
        }
    },
    dark: {
        name: 'dark',
        colors: {
            // Adjusted primary colors for better dark mode visibility
            '--primary': '#60A5FA',
            '--primary-dark': '#3B82F6',
            '--primary-light': '#93C5FD',

            // Dark background colors
            '--bg-primary': '#111827',
            '--bg-secondary': '#1F2937',
            '--bg-tertiary': '#374151',

            // Dark text colors
            '--text-primary': '#F9FAFB',
            '--text-secondary': '#D1D5DB',
            '--text-tertiary': '#9CA3AF',

            // Dark border colors
            '--border-primary': '#374151',
            '--border-secondary': '#4B5563',

            // Dark glass effect
            '--glass-bg': 'rgba(31, 41, 55, 0.8)',
            '--glass-border': 'rgba(75, 85, 99, 0.3)',

            // Enhanced shadows for dark mode
            '--shadow-sm': '0 1px 2px 0 rgba(0, 0, 0, 0.3)',
            '--shadow-md': '0 4px 6px -1px rgba(0, 0, 0, 0.4)',
            '--shadow-lg': '0 10px 15px -3px rgba(0, 0, 0, 0.5)',
            '--shadow-xl': '0 20px 25px -5px rgba(0, 0, 0, 0.6)'
        }
    }
};

// Enhanced Theme Manager - CONSOLIDATED VERSION
class ThemeManager {
    constructor() {
        this.theme = this.getStoredTheme() || this.getSystemTheme();
        this.observers = [];
        this.init();
    }

    // Initialize theme manager
    init() {
        this.applyTheme(this.theme);
        this.setupThemeToggle();
        this.setupSystemThemeListener();
        this.addTransitionClasses();
        this.notifyObservers();
    }

    // Add smooth transitions for theme switching
    addTransitionClasses() {
        if (document.getElementById('theme-transitions')) return;

        const style = document.createElement('style');
        style.id = 'theme-transitions';
        style.textContent = `
            *, *::before, *::after {
                transition: background-color 0.3s ease, 
                           border-color 0.3s ease, 
                           color 0.3s ease, 
                           box-shadow 0.3s ease !important;
            }
        `;
        document.head.appendChild(style);
    }

    // Get stored theme from localStorage
    getStoredTheme() {
        return localStorage.getItem('theme') || localStorage.getItem('bostarter-theme');
    }

    // Get system theme preference
    getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    // Apply theme to document
    applyTheme(theme) {
        const root = document.documentElement;
        const config = themeConfig[theme]; if (!config) {
            // Theme not found - fallback to light theme silently
            theme = 'light';
        }

        // Apply CSS custom properties
        Object.entries(themeConfig[theme].colors).forEach(([property, value]) => {
            root.style.setProperty(property, value);
        });

        // Update HTML attributes
        root.setAttribute('data-theme', theme);

        // Update body and document classes for compatibility
        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.body.classList.toggle('dark', theme === 'dark');

        // Update theme meta tag
        this.updateThemeMetaTag(theme);

        // Store theme preference
        localStorage.setItem('theme', theme);
        localStorage.setItem('bostarter-theme', theme);
        this.theme = theme;

        // Update all toggle icons
        this.updateAllToggleIcons();

        // Announce theme change for accessibility
        this.announceThemeChange();

        // Notify observers
        this.notifyObservers();
    }

    // Update theme meta tag for PWA support
    updateThemeMetaTag(theme) {
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            const primaryColor = themeConfig[theme].colors['--primary'];
            metaThemeColor.setAttribute('content', primaryColor);
        }
    }

    // Setup theme toggle functionality - CONSOLIDATED FOR ALL SELECTORS
    setupThemeToggle() {
        // Support multiple theme toggle selectors used across the app
        const toggleSelectors = [
            '#themeToggle',
            '#theme-toggle',
            '.theme-toggle',
            '[data-theme-toggle]'
        ];

        toggleSelectors.forEach(selector => {
            const toggles = document.querySelectorAll(selector);
            toggles.forEach(toggle => {
                if (toggle && !toggle.hasAttribute('data-theme-initialized')) {
                    toggle.setAttribute('data-theme-initialized', 'true');

                    toggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        const newTheme = this.theme === 'light' ? 'dark' : 'light';
                        this.applyTheme(newTheme);
                    });

                    this.updateToggleIcon(toggle);
                }
            });
        });
    }

    // Update all theme toggle icons across the app
    updateAllToggleIcons() {
        const toggleSelectors = [
            '#themeToggle',
            '#theme-toggle',
            '.theme-toggle',
            '[data-theme-toggle]'
        ];

        toggleSelectors.forEach(selector => {
            const toggles = document.querySelectorAll(selector);
            toggles.forEach(toggle => this.updateToggleIcon(toggle));
        });
    }

    // Update theme toggle icon - supports multiple icon libraries
    updateToggleIcon(toggle) {
        if (!toggle) return;

        const icon = toggle.querySelector('i');
        if (icon) {
            // Support both Font Awesome and Remix icons
            if (icon.className.includes('fa-')) {
                icon.className = this.theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            } else if (icon.className.includes('ri-')) {
                icon.className = this.theme === 'light' ? 'ri-moon-line' : 'ri-sun-line';
            }
        }

        toggle.setAttribute('aria-label', `Switch to ${this.theme === 'light' ? 'dark' : 'light'} theme`);
        toggle.setAttribute('title', `Switch to ${this.theme === 'light' ? 'dark' : 'light'} mode`);
    }

    // Announce theme change for accessibility
    announceThemeChange() {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.style.cssText = 'position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;';
        announcement.textContent = `Tema cambiato in modalità ${this.theme === 'dark' ? 'scura' : 'chiara'}`;

        document.body.appendChild(announcement);
        setTimeout(() => {
            if (document.body.contains(announcement)) {
                document.body.removeChild(announcement);
            }
        }, 1000);
    }

    // Setup system theme preference listener
    setupSystemThemeListener() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        const handleSystemThemeChange = (e) => {
            if (!this.getStoredTheme()) {
                this.applyTheme(e.matches ? 'dark' : 'light');
            }
        };

        // Use both addEventListener and addListener for compatibility
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', handleSystemThemeChange);
        } else if (mediaQuery.addListener) {
            mediaQuery.addListener(handleSystemThemeChange);
        }
    }

    // Observer pattern for theme changes
    addObserver(callback) {
        this.observers.push(callback);
    }

    removeObserver(callback) {
        this.observers = this.observers.filter(obs => obs !== callback);
    }

    notifyObservers() {
        this.observers.forEach(callback => {
            try {
                callback(this.theme, themeConfig[this.theme]);
            } catch (error) {
                // Silent error handling for theme observer
            }
        });
    }

    // Public API methods
    toggle() {
        const newTheme = this.theme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);
    }

    setTheme(theme) {
        if (themeConfig[theme]) {
            this.applyTheme(theme);
        } else {
            // Invalid theme - handle silently
        }
    }

    getTheme() {
        return this.theme;
    }

    // Static method to get theme config
    static getConfig() {
        return themeConfig;
    }
}

// Create global instance
window.ThemeManager = new ThemeManager();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ThemeManager, themeConfig };
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ThemeManager.setupThemeToggle();
    });
} else {
    window.ThemeManager.setupThemeToggle();
}
