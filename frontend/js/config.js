/**
 * BOSTARTER JavaScript Dependencies Configuration
 * Configurazione centralizzata delle dipendenze JavaScript
 */

window.BostarterConfig = {
    // Versione dell'applicazione
    version: '2.0.0',

    // Percorsi base
    paths: {
        base: '/frontend',
        js: '/frontend/js',
        css: '/frontend/css',
        api: '/backend/api'
    },

    // Moduli core da caricare sempre
    coreModules: [
        '/frontend/js/core/Utils.js',
        '/frontend/js/error-handler.js',
        '/frontend/js/core/NotificationSystem.js',
        '/frontend/js/utils/common-functions.js'
    ],

    // Moduli per pagine di categoria
    categoryModules: [
        '/frontend/js/utils/category-config.js',
        '/frontend/js/managers/base-category-manager.js'
    ],

    // Configurazione per categorie specifiche
    categories: {
        tecnologia: {
            manager: '/frontend/js/managers/tecnologia-manager.js',
            color: '#3B82F6',
            icon: 'ri-computer-line'
        },
        arte: {
            manager: '/frontend/js/managers/arte-manager.js',
            color: '#E91E63',
            icon: 'ri-palette-line'
        },
        musica: {
            manager: '/frontend/js/managers/musica-manager.js',
            color: '#9C27B0',
            icon: 'ri-music-line'
        },
        film: {
            manager: '/frontend/js/managers/film-manager.js',
            color: '#FF9800',
            icon: 'ri-movie-line'
        }
        // Altri manager saranno aggiunti quando necessario
    },

    // Configurazione API
    api: {
        baseUrl: '/backend/api',
        timeout: 10000,
        retries: 3
    },

    // Configurazione cache
    cache: {
        version: 'v2.0.0',
        staticTTL: 86400000, // 24 ore
        dynamicTTL: 3600000   // 1 ora
    },

    // Configurazione performance
    performance: {
        lazyLoadThreshold: 100,
        debounceDelay: 300,
        throttleDelay: 100
    },

    // Feature flags
    features: {
        serviceWorker: true,
        notifications: true,
        analytics: false,
        darkMode: true,
        accessibility: true
    },

    // Configurazione sviluppo
    development: {
        debug: false,
        verbose: false,
        mockData: false
    }
};

// Funzione helper per caricare moduli
window.BostarterConfig.loadModule = async function (path) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = path;
        script.onload = () => resolve(path);
        script.onerror = () => reject(new Error(`Failed to load ${path}`));
        document.head.appendChild(script);
    });
};

// Funzione helper per caricare moduli di categoria
window.BostarterConfig.loadCategoryModules = async function (categoryName) {
    const category = this.categories[categoryName];
    if (!category) {
        throw new Error(`Category ${categoryName} not found`);
    }

    // Carica moduli base categoria
    for (const module of this.categoryModules) {
        await this.loadModule(module);
    }

    // Carica manager specifico della categoria
    if (category.manager) {
        await this.loadModule(category.manager);
    }

    return category;
};
