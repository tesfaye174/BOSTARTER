/**
 * ===== CONFIGURAZIONE CATEGORIE CONFORME AL PDF =====
 * Configurazione per SOLO hardware e software secondo le specifiche PDF
 * Progetto Basi di Dati A.A. 2024/2025
 */

window.BostarterConfig = {

    // Configurazione Hardware (conforme al PDF)
    hardware: {
        colors: {
            primary: '#2196f3',      // Blu per hardware
            accent: '#42a5f5',
            secondary: '#1976d2',
            light: '#e3f2fd',
            dark: '#0d47a1'
        },
        selectors: {
            cardClass: 'hardware-card',
            itemSelector: '.hardware-card',
            profileSelector: '.hardware-creator-card',
            categoryFilter: '.hardware-filter'
        },
        filters: [
            'tutti',
            'elettronica',
            'robotica',
            'iot',
            'dispositivi',
            'componenti',
            'automazione',
            'sensori'
        ],
        texts: {
            title: 'Progetti Hardware',
            subtitle: 'Innovazioni hardware: elettronica, robotica, IoT e dispositivi fisici',
            profileType: 'Hardware Creator',
            description: 'Scopri e finanzia progetti hardware innovativi',
            emptyMessage: 'Nessun progetto hardware trovato',
            loadingMessage: 'Caricamento progetti hardware...'
        },
        features: [
            'Componenti elettroniche',
            'Dispositivi IoT',
            'Robotica e automazione',
            'Gadget innovativi',
            'Prototipazione hardware',
            'Sistemi embedded'
        ],
        icons: {
            primary: 'ri-cpu-line',
            category: 'ri-circuit-board-line',
            filter: 'ri-filter-3-line',
            project: 'ri-hard-drive-2-line'
        },
        meta: {
            keywords: 'hardware, elettronica, robotica, iot, dispositivi, innovazione, crowdfunding',
            ogImage: '/frontend/images/category-hardware-og.jpg'
        }
    },

    // Configurazione Software (conforme al PDF)
    software: {
        colors: {
            primary: '#4caf50',      // Verde per software
            accent: '#66bb6a',
            secondary: '#388e3c',
            light: '#e8f5e8',
            dark: '#1b5e20'
        },
        selectors: {
            cardClass: 'software-card',
            itemSelector: '.software-card',
            profileSelector: '.software-creator-card',
            categoryFilter: '.software-filter'
        },
        filters: [
            'tutti',
            'web',
            'mobile',
            'desktop',
            'ai',
            'saas',
            'gaming',
            'blockchain'
        ],
        texts: {
            title: 'Progetti Software',
            subtitle: 'Soluzioni software: applicazioni, piattaforme e servizi digitali',
            profileType: 'Software Developer',
            description: 'Sostieni lo sviluppo di applicazioni e soluzioni software innovative',
            emptyMessage: 'Nessun progetto software trovato',
            loadingMessage: 'Caricamento progetti software...'
        },
        features: [
            'Applicazioni web e mobile',
            'Piattaforme digitali',
            'AI e Machine Learning',
            'SaaS e servizi cloud',
            'Sviluppo gaming',
            'Soluzioni blockchain'
        ],
        icons: {
            primary: 'ri-code-line',
            category: 'ri-smartphone-line',
            filter: 'ri-filter-3-line',
            project: 'ri-computer-line'
        },
        meta: {
            keywords: 'software, applicazioni, web, mobile, ai, saas, sviluppo, crowdfunding',
            ogImage: '/frontend/images/category-software-og.jpg'
        }
    }
};

/**
 * ===== UTILITY FUNCTIONS CONFORMI AL PDF =====
 */

// Ottieni configurazione per categoria (solo hardware/software)
window.getCategoryConfig = function (category) {
    const validCategories = ['hardware', 'software'];
    if (!validCategories.includes(category)) {
        // Invalid category - return null silently
        return null;
    }
    return window.BostarterConfig[category] || null;
};

// Ottieni lista delle categorie valide secondo il PDF
window.getValidCategories = function () {
    return ['hardware', 'software'];
};

// Verifica se una categoria è conforme al PDF
window.isCategoryCompliant = function (category) {
    return ['hardware', 'software'].includes(category);
};

// Migra categoria legacy a categoria conforme
window.migrateLegacyCategory = function (legacyCategory) {
    const migrationMap = {
        // Tecnologia -> Hardware/Software (richiede classificazione manuale)
        'tecnologia': 'hardware', // Default a hardware

        // Tutte le altre categorie non sono supportate nel PDF
        'arte': null,
        'artigianato': null,
        'cibo': null,
        'danza': null,
        'design': null,
        'editoriale': null,
        'film': null,
        'fotografia': null,
        'fumetti': null,
        'giochi': 'software', // I giochi sono software
        'giornalismo': null,
        'moda': null,
        'musica': null,
        'teatro': null
    };

    return migrationMap[legacyCategory] || null;
};

/**
 * ===== CONFIGURAZIONE API ENDPOINTS CONFORMI =====
 */
window.BostarterAPI = {
    endpoints: {
        projects: {
            hardware: '/backend/api/projects_hardware.php',
            software: '/backend/api/projects_software.php',
            all: '/backend/api/projects.php'
        },
        categories: '/backend/api/categories_compliant.php',
        filters: '/backend/api/filters_compliant.php'
    },

    // Parametri per query conformi al PDF
    queryParams: {
        categoryTypes: ['hardware', 'software'],
        defaultLimit: 12,
        defaultSort: 'data_creazione DESC',
        statusFilter: 'attivo'
    }
};

/**
 * ===== CONFIGURAZIONE UI COMPONENTS =====
 */
window.BostarterUI = {
    components: {
        categorySelector: {
            hardware: {
                label: 'Hardware',
                icon: 'ri-cpu-line',
                description: 'Progetti di elettronica e dispositivi'
            },
            software: {
                label: 'Software',
                icon: 'ri-code-line',
                description: 'Applicazioni e soluzioni digitali'
            }
        },

        projectCard: {
            template: 'compliant-project-card',
            className: 'project-card-compliant',
            showCategory: true,
            showProgress: true,
            showCreator: true
        },

        filters: {
            show: true,
            position: 'sidebar',
            style: 'accordion',
            responsive: true
        }
    },

    // Configurazione responsive conforme
    responsive: {
        breakpoints: {
            mobile: '768px',
            tablet: '1024px',
            desktop: '1200px'
        },
        gridColumns: {
            mobile: 1,
            tablet: 2,
            desktop: 3
        }
    }
};
