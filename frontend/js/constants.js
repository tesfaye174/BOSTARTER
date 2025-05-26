// Costanti per le API
export const API_ENDPOINTS = {
    AUTH: {
        LOGIN: '/auth/login',
        REGISTER: '/auth/register',
        LOGOUT: '/auth/logout',
        REFRESH: '/auth/refresh'
    },
    PROJECTS: {
        LIST: '/projects',
        FEATURED: '/projects/featured',
        DETAIL: (id) => `/projects/${id}`,
        CREATE: '/projects',
        UPDATE: (id) => `/projects/${id}`,
        DELETE: (id) => `/projects/${id}`,
        SEARCH: '/projects/search'
    },
    CATEGORIES: {
        LIST: '/categories',
        DETAIL: (id) => `/categories/${id}`
    },
    USERS: {
        PROFILE: '/users/profile',
        UPDATE: '/users/profile',
        PASSWORD: '/users/password'
    }
};

// Costanti per i messaggi
export const MESSAGES = {
    AUTH: {
        LOGIN_SUCCESS: 'Login effettuato con successo',
        LOGIN_ERROR: 'Errore durante il login',
        REGISTER_SUCCESS: 'Registrazione completata con successo',
        REGISTER_ERROR: 'Errore durante la registrazione',
        LOGOUT_SUCCESS: 'Logout effettuato con successo',
        LOGOUT_ERROR: 'Errore durante il logout'
    },
    PROJECTS: {
        CREATE_SUCCESS: 'Progetto creato con successo',
        CREATE_ERROR: 'Errore durante la creazione del progetto',
        UPDATE_SUCCESS: 'Progetto aggiornato con successo',
        UPDATE_ERROR: 'Errore durante l\'aggiornamento del progetto',
        DELETE_SUCCESS: 'Progetto eliminato con successo',
        DELETE_ERROR: 'Errore durante l\'eliminazione del progetto',
        NOT_FOUND: 'Progetto non trovato'
    },
    VALIDATION: {
        REQUIRED_FIELD: 'Campo obbligatorio',
        INVALID_EMAIL: 'Email non valida',
        INVALID_PASSWORD: 'La password deve contenere almeno 8 caratteri',
        PASSWORDS_NOT_MATCH: 'Le password non coincidono',
        INVALID_URL: 'URL non valido'
    }
};

// Costanti per le configurazioni
export const CONFIG = {
    API: {
        BASE_URL: 'http://localhost:8080/api',
        TIMEOUT: 10000,
        HEADERS: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    },
    AUTH: {
        TOKEN_KEY: 'auth_token',
        REFRESH_TOKEN_KEY: 'refresh_token',
        TOKEN_EXPIRY: 3600 // 1 ora
    },
    PAGINATION: {
        ITEMS_PER_PAGE: 12,
        MAX_PAGES: 5
    },
    ANIMATIONS: {
        DURATION: 300,
        EASING: 'ease-in-out'
    }
};

// Costanti per i temi
export const THEMES = {
    LIGHT: 'light',
    DARK: 'dark'
};

// Costanti per i tipi di notifica
export const NOTIFICATION_TYPES = {
    SUCCESS: 'success',
    ERROR: 'error',
    WARNING: 'warning',
    INFO: 'info'
};

// Costanti per i filtri
export const FILTERS = {
    SORT_BY: {
        NEWEST: 'newest',
        OLDEST: 'oldest',
        MOST_FUNDED: 'most_funded',
        MOST_BACKERS: 'most_backers'
    },
    STATUS: {
        ACTIVE: 'active',
        COMPLETED: 'completed',
        CANCELLED: 'cancelled'
    }
};

// Costanti per le validazioni
export const VALIDATION = {
    PASSWORD_MIN_LENGTH: 8,
    PASSWORD_MAX_LENGTH: 32,
    USERNAME_MIN_LENGTH: 3,
    USERNAME_MAX_LENGTH: 20,
    PROJECT_TITLE_MIN_LENGTH: 5,
    PROJECT_TITLE_MAX_LENGTH: 100,
    PROJECT_DESCRIPTION_MIN_LENGTH: 50,
    PROJECT_DESCRIPTION_MAX_LENGTH: 5000
};

// Costanti per le animazioni
export const ANIMATIONS = {
    FADE_IN: 'fade-in',
    SLIDE_UP: 'slide-up',
    SLIDE_DOWN: 'slide-down',
    SLIDE_LEFT: 'slide-left',
    SLIDE_RIGHT: 'slide-right',
    SCALE_IN: 'scale-in',
    SCALE_OUT: 'scale-out',
    ROTATE_IN: 'rotate-in',
    ROTATE_OUT: 'rotate-out'
};

// Costanti per i breakpoint
export const BREAKPOINTS = {
    XS: 0,
    SM: 576,
    MD: 768,
    LG: 992,
    XL: 1200,
    XXL: 1400
}; 