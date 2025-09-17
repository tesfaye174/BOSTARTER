/**
 * BOSTARTER - CONFIGURAZIONE ASSETS
 * Gestisce i riferimenti agli assets ottimizzati
 */

// Versione degli assets per cache busting
window.BOSTARTER_CONFIG = {
    version: '5.0.0',
    assets: {
        css: {
            optimized: 'assets/css/bostarter-optimized.min.css',
            bootstrap: 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
        },
        js: {
            optimized: 'assets/js/bostarter-optimized.min.js',
            bootstrap: 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            fontawesome: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'
        },
        fonts: {
            inter: 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
        }
    },
    performance: {
        lazyLoadImages: true,
        debounceDelay: 150,
        throttleLimit: 16,
        animationDuration: 300
    }
};

// Funzione helper per caricare assets dinamicamente
window.BOSTARTER_CONFIG.loadAsset = function(type, url, callback) {
    if (type === 'css') {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = url;
        link.onload = callback;
        document.head.appendChild(link);
    } else if (type === 'js') {
        const script = document.createElement('script');
        script.src = url;
        script.onload = callback;
        document.body.appendChild(script);
    }
};
