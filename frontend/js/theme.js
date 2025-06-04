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

// Enhanced Theme Manager
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
        const style = document.createElement('style');
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
        return localStorage.getItem('bostarter-theme');
    }

    // Get system theme preference
    getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }    // Apply theme to document
    applyTheme(theme) {
        const root = document.documentElement;
        const config = themeConfig[theme];

        if (!config) {
            console.warn(`Theme "${theme}" not found. Falling back to light theme.`);
            theme = 'light';
        }

        // Apply CSS custom properties
        Object.entries(themeConfig[theme].colors).forEach(([property, value]) => {
            root.style.setProperty(property, value);
        });

        // Update HTML attributes
        root.setAttribute('data-theme', theme);

        // Update body class for Tailwind compatibility
        document.body.classList.toggle('dark', theme === 'dark');

        // Update theme meta tag
        this.updateThemeMetaTag(theme);

        // Store theme preference
        localStorage.setItem('bostarter-theme', theme);
        this.theme = theme;

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
                console.warn('Theme observer error:', error);
            }
        });
    }

    // Setup theme toggle functionality
    setupThemeToggle() {
        const toggle = document.querySelector('.theme-toggle');
        if (!toggle) return;

        toggle.addEventListener('click', () => {
            const newTheme = this.theme === 'light' ? 'dark' : 'light';
            this.applyTheme(newTheme);
            this.updateToggleIcon(toggle);
        });

        this.updateToggleIcon(toggle);
    }

    // Update theme toggle icon
    updateToggleIcon(toggle) {
        const icon = toggle.querySelector('i');
        if (!icon) return;

        icon.className = this.theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        toggle.setAttribute('aria-label', `Switch to ${this.theme === 'light' ? 'dark' : 'light'} theme`);
    }

    // Setup system theme preference listener
    setupSystemThemeListener() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        mediaQuery.addEventListener('change', (e) => {
            if (!this.getStoredTheme()) {
                this.applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    // Get current theme
    getCurrentTheme() {
        return this.theme;
    }

    // Set theme programmatically
    setTheme(theme) {
        if (themeConfig[theme]) {
            this.applyTheme(theme);
        }
    }
}

// Create global theme manager instance
const themeManager = new ThemeManager();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { themeManager, ThemeManager, themeConfig };
}

// Global access
window.themeManager = themeManager;