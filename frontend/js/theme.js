// Configurazione del tema
const themeConfig = {
    light: {
        '--color-primary': '#3176FF',
        '--color-secondary': '#6C63FF',
        '--color-background': '#FFFFFF',
        '--color-text': '#1F2937',
        '--color-text-secondary': '#4B5563',
        '--color-border': '#E5E7EB',
        '--color-shadow': 'rgba(0, 0, 0, 0.1)'
    },
    dark: {
        '--color-primary': '#60A5FA',
        '--color-secondary': '#818CF8',
        '--color-background': '#1F2937',
        '--color-text': '#F9FAFB',
        '--color-text-secondary': '#D1D5DB',
        '--color-border': '#374151',
        '--color-shadow': 'rgba(0, 0, 0, 0.3)'
    }
};

// Classe per la gestione del tema
class ThemeManager {
    constructor() {
        this.theme = this.getStoredTheme() || this.getSystemTheme();
        this.init();
    }

    // Inizializzazione del gestore tema
    init() {
        this.applyTheme(this.theme);
        this.setupThemeToggle();
        this.setupSystemThemeListener();
    }

    // Ottiene il tema memorizzato
    getStoredTheme() {
        return localStorage.getItem('theme');
    }

    // Ottiene il tema del sistema
    getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    // Applica il tema
    applyTheme(theme) {
        const root = document.documentElement;
        const themeColors = themeConfig[theme];
        
        Object.entries(themeColors).forEach(([property, value]) => {
            root.style.setProperty(property, value);
        });
        
        document.body.classList.toggle('dark', theme === 'dark');
        localStorage.setItem('theme', theme);
        this.theme = theme;
    }

    // Configura il toggle del tema
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

    // Aggiorna l'icona del toggle
    updateToggleIcon(toggle) {
        const icon = toggle.querySelector('i');
        if (!icon) return;
        
        icon.className = this.theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        toggle.setAttribute('aria-label', `Passa al tema ${this.theme === 'light' ? 'scuro' : 'chiaro'}`);
    }

    // Configura il listener per il tema di sistema
    setupSystemThemeListener() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        mediaQuery.addEventListener('change', (e) => {
            if (!this.getStoredTheme()) {
                this.applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    // Ottiene il tema corrente
    getCurrentTheme() {
        return this.theme;
    }

    // Imposta il tema
    setTheme(theme) {
        if (themeConfig[theme]) {
            this.applyTheme(theme);
        }
    }
}

// Crea un'istanza globale del gestore tema
const themeManager = new ThemeManager();

// Esporta l'istanza e la classe
export { themeManager, ThemeManager }; 