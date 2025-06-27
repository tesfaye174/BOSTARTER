/**
 * BOSTARTER - Theme Manager
 * Gestione del tema scuro/chiaro e preferenze utente
 * @version 1.0
 */

(function (window, document) {
    'use strict';

    const ThemeManager = {
        // Costanti
        STORAGE_KEY: 'bostarter_theme',
        THEMES: {
            LIGHT: 'light',
            DARK: 'dark',
            AUTO: 'auto'
        },

        // Stato corrente
        currentTheme: null,
        mediaQuery: null,

        /**
         * Inizializza il theme manager
         */
        init() {
            this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            this.loadTheme();
            this.setupEventListeners();
            this.createThemeToggle();
        },

        /**
         * Carica il tema salvato o usa quello di sistema
         */
        loadTheme() {
            const savedTheme = localStorage.getItem(this.STORAGE_KEY);
            const systemTheme = this.getSystemTheme();

            this.currentTheme = savedTheme || this.THEMES.AUTO;
            this.applyTheme(this.currentTheme === this.THEMES.AUTO ? systemTheme : this.currentTheme);
        },

        /**
         * Applica il tema
         */
        applyTheme(theme) {
            const html = document.documentElement;

            // Rimuovi tutte le classi tema
            html.classList.remove('light', 'dark');

            // Aggiungi la classe del tema corrente
            if (theme === this.THEMES.DARK) {
                html.classList.add('dark');
            } else {
                html.classList.add('light');
            }

            // Aggiorna gli attributi data
            html.setAttribute('data-theme', theme);

            // Aggiorna i meta tag per il colore della barra di stato
            this.updateMetaTheme(theme);

            // Dispatch evento per altri componenti
            this.dispatchThemeChange(theme);
        },

        /**
         * Ottiene il tema di sistema
         */
        getSystemTheme() {
            return this.mediaQuery && this.mediaQuery.matches ? this.THEMES.DARK : this.THEMES.LIGHT;
        },

        /**
         * Cambia tema
         */
        setTheme(theme) {
            if (!Object.values(this.THEMES).includes(theme)) {
                console.warn('Tema non valido:', theme);
                return;
            }

            this.currentTheme = theme;
            localStorage.setItem(this.STORAGE_KEY, theme);

            const actualTheme = theme === this.THEMES.AUTO ? this.getSystemTheme() : theme;
            this.applyTheme(actualTheme);
        },

        /**
         * Toggle tra tema chiaro e scuro
         */
        toggle() {
            const newTheme = this.getCurrentAppliedTheme() === this.THEMES.DARK
                ? this.THEMES.LIGHT
                : this.THEMES.DARK;

            this.setTheme(newTheme);
        },

        /**
         * Ottiene il tema attualmente applicato
         */
        getCurrentAppliedTheme() {
            return document.documentElement.classList.contains('dark')
                ? this.THEMES.DARK
                : this.THEMES.LIGHT;
        },

        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Ascolta i cambiamenti del tema di sistema
            this.mediaQuery.addEventListener('change', (e) => {
                if (this.currentTheme === this.THEMES.AUTO) {
                    this.applyTheme(e.matches ? this.THEMES.DARK : this.THEMES.LIGHT);
                }
            });

            // Ascolta i tasti di scelta rapida
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                    e.preventDefault();
                    this.toggle();
                }
            });
        },

        /**
         * Crea il toggle button per il tema
         */
        createThemeToggle() {
            // Verifica se esiste già un toggle
            if (document.querySelector('.theme-toggle')) return;

            const toggle = document.createElement('button');
            toggle.className = 'theme-toggle';
            toggle.setAttribute('aria-label', 'Cambia tema');
            toggle.innerHTML = `
                <svg class="theme-icon sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
                <svg class="theme-icon moon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m21 12.79-.29.71-.29-.71A16.5 16.5 0 0 1 12.79 21l-.71-.29.71-.29A16.5 16.5 0 0 0 21 12.79z"></path>
                </svg>
            `;

            // Stili inline per il toggle
            const style = document.createElement('style');
            style.textContent = `
                .theme-toggle {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 1000;
                    width: 44px;
                    height: 44px;
                    border: none;
                    border-radius: 50%;
                    background: var(--bg-primary, #fff);
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                }
                
                .theme-toggle:hover {
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    transform: translateY(-2px);
                }
                
                .theme-icon {
                    transition: all 0.3s ease;
                }
                
                .dark .sun-icon {
                    opacity: 0;
                    transform: rotate(180deg);
                }
                
                .light .moon-icon {
                    opacity: 0;
                    transform: rotate(-180deg);
                }
                
                .dark .moon-icon {
                    opacity: 1;
                    transform: rotate(0deg);
                }
                
                .light .sun-icon {
                    opacity: 1;
                    transform: rotate(0deg);
                }
            `;

            document.head.appendChild(style);

            // Event listener per il toggle
            toggle.addEventListener('click', () => {
                this.toggle();
            });

            // Aggiungi il toggle alla pagina
            document.body.appendChild(toggle);
        },

        /**
         * Aggiorna i meta tag per il tema
         */
        updateMetaTheme(theme) {
            const themeColor = theme === this.THEMES.DARK ? '#1f2937' : '#ffffff';

            let metaTheme = document.querySelector('meta[name="theme-color"]');
            if (!metaTheme) {
                metaTheme = document.createElement('meta');
                metaTheme.name = 'theme-color';
                document.head.appendChild(metaTheme);
            }

            metaTheme.content = themeColor;
        },

        /**
         * Dispatch evento di cambio tema
         */
        dispatchThemeChange(theme) {
            const event = new CustomEvent('themechange', {
                detail: { theme }
            });
            document.dispatchEvent(event);
        }
    };

    // Inizializzazione automatica
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ThemeManager.init();
        });
    } else {
        ThemeManager.init();
    }

    // Esporta il theme manager
    window.BOSTARTERTheme = ThemeManager;

})(window, document);
