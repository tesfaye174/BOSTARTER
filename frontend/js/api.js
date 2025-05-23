// Configurazione delle chiamate API per BOSTARTER

const API_CONFIG = {
    BASE_URL: '/BOSTARTER/backend',
    ENDPOINTS: {
        AUTH: {
            LOGIN: '/auth/login.php',
            REGISTER: '/auth/register.php',
            LOGOUT: '/auth/logout.php',
            CHECK_SESSION: '/auth/check_session.php'
        },
        PROJECTS: {
            CREATE: '/projects/create.php',
            GET_ALL: '/projects/get_all.php',
            GET_BY_CREATOR: '/projects/get_by_creator.php',
            GET_FEATURED: '/projects/get_featured.php',
            FUND: '/projects/fund.php',
            ADD_REWARD: '/projects/add_reward.php',
            PUBLISH: '/projects/publish.php'
        },
        STATISTICS: {
            GET_TOP_CREATORS: '/statistics/get_top_creators.php',
            GET_TOP_PROJECTS: '/statistics/get_top_projects.php',
            GET_TOP_FUNDERS: '/statistics/get_top_funders.php'
        },
        NEWSLETTER: {
            SUBSCRIBE: '/newsletter/subscribe.php'
        }
    }
};

// Classe per gestire le chiamate API
class ApiService {
    static async request(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            credentials: 'include'
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(API_CONFIG.BASE_URL + endpoint, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Errore nella richiesta API');
            }

            return result;
        } catch (error) {
            console.error('Errore API:', error);
            throw error;
        }
    }

    // Metodi per l'autenticazione
    static async login(credentials) {
        return this.request(API_CONFIG.ENDPOINTS.AUTH.LOGIN, 'POST', credentials);
    }

    static async register(userData) {
        return this.request(API_CONFIG.ENDPOINTS.AUTH.REGISTER, 'POST', userData);
    }

    static async logout() {
        return this.request(API_CONFIG.ENDPOINTS.AUTH.LOGOUT, 'POST');
    }

    // Metodi per i progetti
    static async createProject(projectData) {
        return this.request(API_CONFIG.ENDPOINTS.PROJECTS.CREATE, 'POST', projectData);
    }

    static async getCreatorProjects(creatorId) {
        return this.request(`${API_CONFIG.ENDPOINTS.PROJECTS.GET_BY_CREATOR}?creator_id=${creatorId}`);
    }

    static async fundProject(fundingData) {
        return this.request(API_CONFIG.ENDPOINTS.PROJECTS.FUND, 'POST', fundingData);
    }

    static async addProjectReward(rewardData) {
        return this.request(API_CONFIG.ENDPOINTS.PROJECTS.ADD_REWARD, 'POST', rewardData);
    }

    static async publishProject(projectId) {
        return this.request(API_CONFIG.ENDPOINTS.PROJECTS.PUBLISH, 'POST', { project_id: projectId });
    }
}

// Gestore della sessione utente
const SessionManager = {
    setSession(userData) {
        localStorage.setItem('user_session', JSON.stringify(userData));
    },

    getSession() {
        const session = localStorage.getItem('user_session');
        return session ? JSON.parse(session) : null;
    },

    clearSession() {
        localStorage.removeItem('user_session');
    },

    isLoggedIn() {
        return !!this.getSession();
    }
};