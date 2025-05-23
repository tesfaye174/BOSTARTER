// Configurazione centralizzata per BOSTARTER

const Config = {
    // Configurazione API
    api: {
        baseUrl: '/BOSTARTER/backend',
        endpoints: {
            auth: {
                login: '/auth/login.php',
                register: '/auth/register.php',
                logout: '/auth/logout.php',
                checkSession: '/auth/check_session.php'
            },
            projects: {
                create: '/projects/create.php',
                getAll: '/projects/get_all.php',
                getByCreator: '/projects/get_by_creator.php',
                getFeatured: '/projects/get_featured.php',
                fund: '/projects/fund.php',
                addReward: '/projects/add_reward.php',
                publish: '/projects/publish.php'
            },
            statistics: {
                getTopCreators: '/statistics/get_top_creators.php',
                getTopProjects: '/statistics/get_top_projects.php',
                getTopFunders: '/statistics/get_top_funders.php'
            },
            newsletter: {
                subscribe: '/newsletter/subscribe.php'
            }
        },
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    },

    // Configurazione UI
    ui: {
        animations: {
            duration: 300,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)'
        },
        breakpoints: {
            mobile: 480,
            tablet: 768,
            desktop: 1024,
            wide: 1280
        },
        colors: {
            primary: '#3176FF',
            secondary: '#FF6B35',
            success: '#10B981',
            error: '#EF4444',
            warning: '#F59E0B',
            info: '#3B82F6'
        }
    },

    // Configurazione validazione
    validation: {
        minLength: {
            username: 3,
            password: 8,
            projectTitle: 5,
            description: 20
        },
        maxLength: {
            username: 50,
            password: 128,
            projectTitle: 100,
            description: 5000
        }
    },

    // Configurazione progetti
    projects: {
        categories: [
            { id: 'arte', name: 'Arte', icon: 'ri-brush-line' },
            { id: 'design', name: 'Design', icon: 'ri-palette-fill' },
            { id: 'film', name: 'Film', icon: 'ri-film-line' },
            { id: 'musica', name: 'Musica', icon: 'ri-music-2-fill' },
            { id: 'gioco', name: 'Gioco', icon: 'ri-game-fill' },
            { id: 'letteratura', name: 'Letteratura', icon: 'ri-book-fill' },
            { id: 'politica', name: 'Politica', icon: 'ri-government-fill' },
            { id: 'scienza', name: 'Scienza', icon: 'ri-flask-fill' },
            { id: 'sport', name: 'Sport', icon: 'ri-football-fill' },
            { id: 'animali', name: 'Animali', icon: 'ri-paw-fill' },
            { id: 'biologia', name: 'Biologia', icon: 'ri-flask-fill' },
            { id: 'cinema', name: 'Cinema', icon: 'ri-film-line' },
            { id: 'cultura', name: 'Cultura', icon: 'ri-palette-line' },
            { id: 'moda', name: 'Moda', icon: 'ri-shirt-line' },
            { id: 'tecnologia', name: 'Tecnologia', icon: 'ri-code-line' },
            { id: 'viaggi', name: 'Viaggi', icon: 'ri-plane-fill' },
            { id: 'fumetti', name: 'Fumetti', icon: 'ri-book-line' }
        ],
        defaultImage: '/frontend/images/project-placeholder.svg',
        maxRewards: 10,
        minFundingGoal: 1000,
        maxFundingGoal: 1000000,
        maxDuration: 90 // giorni
    },

    // Configurazione notifiche
    notifications: {
        position: 'top-right',
        duration: 5000,
        maxVisible: 5
    },

    // Configurazione localStorage
    storage: {
        keys: {
            authToken: 'bostarter_auth_token',
            user: 'bostarter_user',
            theme: 'bostarter_theme',
            language: 'bostarter_language'
        }
    },

    // Configurazione internazionalizzazione
    i18n: {
        defaultLocale: 'it',
        supportedLocales: ['it', 'en', 'es', 'fr', 'de'],
        fallbackLocale: 'en'
    }
};

export default Config;