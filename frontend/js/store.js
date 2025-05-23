// Store centralizzato per la gestione dello stato dell'applicazione BOSTARTER

const Store = {
    state: {
        user: null,
        projects: [],
        categories: [],
        notifications: [],
        loading: false,
        errors: {},
        filters: {
            category: null,
            sort: 'newest',
            search: ''
        },
        statistics: {
            topCreators: [],
            topProjects: [],
            topFunders: []
        }
    },

    listeners: new Set(),

    // Inizializza lo store con i dati salvati
    initialize() {
        const savedState = localStorage.getItem('bostarter_state');
        if (savedState) {
            try {
                const parsedState = JSON.parse(savedState);
                this.state = { ...this.state, ...parsedState };
            } catch (error) {
                console.error('Errore nel caricamento dello stato:', error);
            }
        }

        // Salva lo stato quando l'utente chiude la pagina
        window.addEventListener('beforeunload', () => this.saveState());
    },

    // Salva lo stato nel localStorage
    saveState() {
        const stateToSave = {
            user: this.state.user,
            filters: this.state.filters
        };
        localStorage.setItem('bostarter_state', JSON.stringify(stateToSave));
    },

    // Aggiorna lo stato e notifica i listener
    setState(newState) {
        this.state = { ...this.state, ...newState };
        this.notifyListeners();
        this.saveState();
    },

    // Aggiorna una proprietà specifica dello stato
    updateState(key, value) {
        if (key.includes('.')) {
            const keys = key.split('.');
            let current = this.state;
            for (let i = 0; i < keys.length - 1; i++) {
                current = current[keys[i]];
            }
            current[keys[keys.length - 1]] = value;
        } else {
            this.state[key] = value;
        }
        this.notifyListeners();
        this.saveState();
    },

    // Sottoscrivi un listener per i cambiamenti dello stato
    subscribe(listener) {
        this.listeners.add(listener);
        return () => this.listeners.delete(listener);
    },

    // Notifica tutti i listener dei cambiamenti
    notifyListeners() {
        this.listeners.forEach(listener => listener(this.state));
    },

    // Gestione utente
    setUser(user) {
        this.updateState('user', user);
    },

    clearUser() {
        this.updateState('user', null);
    },

    // Gestione progetti
    setProjects(projects) {
        this.updateState('projects', projects);
    },

    addProject(project) {
        this.updateState('projects', [...this.state.projects, project]);
    },

    updateProject(projectId, updates) {
        const updatedProjects = this.state.projects.map(project =>
            project.id === projectId ? { ...project, ...updates } : project
        );
        this.setProjects(updatedProjects);
    },

    removeProject(projectId) {
        const filteredProjects = this.state.projects.filter(project => project.id !== projectId);
        this.setProjects(filteredProjects);
    },

    // Gestione filtri
    setFilter(key, value) {
        this.updateState(`filters.${key}`, value);
    },

    clearFilters() {
        this.updateState('filters', {
            category: null,
            sort: 'newest',
            search: ''
        });
    },

    // Gestione statistiche
    updateStatistics(key, value) {
        this.updateState(`statistics.${key}`, value);
    },

    // Gestione errori
    setError(key, error) {
        this.updateState(`errors.${key}`, error);
    },

    clearError(key) {
        const newErrors = { ...this.state.errors };
        delete newErrors[key];
        this.updateState('errors', newErrors);
    },

    clearAllErrors() {
        this.updateState('errors', {});
    },

    // Gestione loading state
    setLoading(isLoading) {
        this.updateState('loading', isLoading);
    }
};

// Inizializza lo store
Store.initialize();

export default Store;