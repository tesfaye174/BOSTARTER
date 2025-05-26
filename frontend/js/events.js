// Importa le utility
import { debounce } from './utils.js';

// Importa il gestore API
import { apiManager } from './api.js';

// Importa il gestore dello store
import { store } from './store.js';

// Importa il gestore delle notifiche
import { notificationManager } from './notifications.js';

// Gestione degli eventi di autenticazione
export const initAuthEvents = () => {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(loginForm);
            const data = {
                email: formData.get('email'),
                password: formData.get('password')
            };
            
            try {
                const response = await apiManager.post('/auth/login', data);
                store.setUser(response.user);
                notificationManager.success('Login effettuato con successo');
                window.location.href = '/';
            } catch (error) {
                notificationManager.error(error.message);
            }
        });
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(registerForm);
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                confirmPassword: formData.get('confirmPassword')
            };
            
            try {
                const response = await apiManager.post('/auth/register', data);
                store.setUser(response.user);
                notificationManager.success('Registrazione completata con successo');
                window.location.href = '/';
            } catch (error) {
                notificationManager.error(error.message);
            }
        });
    }
};

// Gestione degli eventi dei progetti
export const initProjectEvents = () => {
    const createProjectForm = document.getElementById('createProjectForm');
    const projectFilters = document.querySelector('.projects-filters');
    
    if (createProjectForm) {
        createProjectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(createProjectForm);
            const data = {
                title: formData.get('title'),
                description: formData.get('description'),
                targetAmount: formData.get('targetAmount'),
                category: formData.get('category'),
                image: formData.get('image')
            };
            
            try {
                const response = await apiManager.post('/projects', data);
                notificationManager.success('Progetto creato con successo');
                window.location.href = `/projects/${response.id}`;
            } catch (error) {
                notificationManager.error(error.message);
            }
        });
    }
    
    if (projectFilters) {
        const handleFilterChange = debounce(async () => {
            const category = projectFilters.querySelector('[name="category"]').value;
            const sort = projectFilters.querySelector('[name="sort"]').value;
            
            const queryParams = new URLSearchParams();
            if (category) queryParams.append('category', category);
            if (sort) queryParams.append('sort', sort);
            
            window.location.href = `/projects?${queryParams.toString()}`;
        }, 300);
        
        projectFilters.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', handleFilterChange);
        });
    }
};

// Gestione degli eventi di paginazione
export const initPaginationEvents = () => {
    const handlePageChange = (page) => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        window.location.href = url.toString();
    };
    
    window.handlePageChange = handlePageChange;
};

// Gestione degli eventi di ricerca
export const initSearchEvents = () => {
    const searchInput = document.querySelector('.search-input');
    
    if (searchInput) {
        const handleSearch = debounce(async (e) => {
            const query = e.target.value.trim();
            
            if (query.length < 2) return;
            
            try {
                const results = await apiManager.get('/projects/search', { query });
                // Aggiorna l'UI con i risultati
                updateSearchResults(results);
            } catch (error) {
                notificationManager.error('Errore durante la ricerca');
            }
        }, 300);
        
        searchInput.addEventListener('input', handleSearch);
    }
};

// Gestione degli eventi del tema
export const initThemeEvents = () => {
    const themeToggle = document.querySelector('.theme-toggle');
    
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.body.classList.contains('dark') ? 'light' : 'dark';
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', currentTheme);
        });
    }
};

// Gestione degli eventi del menu mobile
export const initMobileMenuEvents = () => {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            menuToggle.setAttribute('aria-expanded', 
                mobileMenu.classList.contains('active'));
        });
        
        // Chiudi il menu quando si clicca fuori
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                mobileMenu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
};

// Gestione degli eventi delle modali
export const initModalEvents = () => {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const closeButton = modal.querySelector('.modal-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                modal.classList.remove('active');
            });
        }
        
        // Chiudi la modale quando si clicca fuori
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
        
        // Chiudi la modale con il tasto ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                modal.classList.remove('active');
            }
        });
    });
};

// Gestione degli eventi di scroll
export const initScrollEvents = () => {
    const handleScroll = debounce(() => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const header = document.querySelector('.header');
        
        if (header) {
            if (scrollTop > 100) {
                header.classList.add('header-scrolled');
            } else {
                header.classList.remove('header-scrolled');
            }
        }
        
        // Animazioni al scroll
        const elements = document.querySelectorAll('.animate-on-scroll');
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('visible');
            }
        });
    }, 100);
    
    window.addEventListener('scroll', handleScroll);
};

// Inizializzazione di tutti gli eventi
export const initEvents = () => {
    initAuthEvents();
    initProjectEvents();
    initPaginationEvents();
    initSearchEvents();
    initThemeEvents();
    initMobileMenuEvents();
    initModalEvents();
    initScrollEvents();
}; 