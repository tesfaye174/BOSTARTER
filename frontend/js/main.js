// Using ES6 modules for better organization
import { ApiManager } from './api.js';
import { AuthSystem } from './auth.js';
import { UIManager } from './ui/UIManager.js';
import { EventBus } from './utils/EventBus.js';

// Centralized configuration
const config = {
    apiUrl: 'http://localhost:8080/api',
    theme: {
        colors: {
            primary: '#3176FF',
            secondary: '#FF6B35',
            success: '#10B981',
            warning: '#F59E0B',
            error: '#EF4444'
        }
    },
    searchDelay: 300,
    notificationDuration: 5000,
    scrollThreshold: 100,
    themeKey: 'bostarter-theme',
    languageKey: 'bostarter-language',
    currencyKey: 'bostarter-currency'
};

// Application initialization
class App {
    constructor() {
        this.api = new ApiManager(config.apiUrl);
        this.auth = new AuthSystem(this.api);
        this.ui = new UIManager();
        this.eventBus = new EventBus();

        this.init();
    }

    async init() {
        await this.auth.checkAuthStatus();
        this.setupEventListeners();
        this.ui.initializeComponents();
    }
}