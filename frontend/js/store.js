// Store centralizzato per la gestione dello stato dell'applicazione BOSTARTER

// Stato iniziale dell'applicazione
const initialState = {
    user: null,
    isAuthenticated: false,
    projects: [],
    featuredProjects: [],
    categories: [],
    notifications: [],
    currentPage: 1,
    isLoading: false,
    error: null,
    filters: {
        category: null,
        sortBy: 'newest',
        searchQuery: ''
    },
    statistics: {
        topCreators: [],
        topProjects: [],
        topFunders: []
    }
};

// Classe per la gestione dello stato
class Store {
    constructor() {
        this.state = { ...initialState };
        this.listeners = new Set();

        // Inizializza lo store con i dati salvati
        this.initialize();
    }

    // Ottiene lo stato corrente
    getState() {
        return { ...this.state };
    }

    // Inizializza lo store con i dati salvati
    initialize() {
        const savedState = localStorage.getItem('bostarter_state');
        if (savedState) {
            try {
                const parsedState = JSON.parse(savedState);
                this.state = { ...this.state, ...parsedState };
            } catch (error) {
                // Silent error handling for state loading
            }
        }

        // Salva lo stato quando l'utente chiude la pagina
        window.addEventListener('beforeunload', () => this.saveState());
    }

    // Salva lo stato nel localStorage
    saveState() {
        const stateToSave = {
            user: this.state.user,
            filters: this.state.filters
        };
        localStorage.setItem('bostarter_state', JSON.stringify(stateToSave));
    }

    // Imposta lo stato
    setState(newState) {
        this.state = {
            ...this.state,
            ...newState
        };
        this.notifyListeners();
        this.saveState();
    }

    // Sottoscrive un listener
    subscribe(listener) {
        this.listeners.add(listener);
        return () => this.listeners.delete(listener);
    }

    // Notifica i listener
    notifyListeners() {
        this.listeners.forEach(listener => listener(this.state));
    }

    // Azioni per l'utente
    setUser(user) {
        this.setState({
            user,
            isAuthenticated: !!user
        });
    }

    clearUser() {
        this.setState({
            user: null,
            isAuthenticated: false
        });
    }

    // Azioni per i progetti
    setProjects(projects) {
        this.setState({ projects });
    }

    setFeaturedProjects(projects) {
        this.setState({ featuredProjects: projects });
    }

    addProject(project) {
        this.setState({
            projects: [...this.state.projects, project]
        });
    }

    updateProject(updatedProject) {
        this.setState({
            projects: this.state.projects.map(project =>
                project.id === updatedProject.id ? updatedProject : project
            )
        });
    }

    deleteProject(projectId) {
        this.setState({
            projects: this.state.projects.filter(project => project.id !== projectId)
        });
    }

    // Azioni per le categorie
    setCategories(categories) {
        this.setState({ categories });
    }

    // Azioni per le notifiche
    addNotification(notification) {
        this.setState({
            notifications: [...this.state.notifications, notification]
        });
    }

    removeNotification(notificationId) {
        this.setState({
            notifications: this.state.notifications.filter(
                notification => notification.id !== notificationId
            )
        });
    }

    // Azioni per il caricamento
    setLoading(isLoading) {
        this.setState({ isLoading });
    }

    // Azioni per gli errori
    setError(error) {
        this.setState({ error });
    }

    clearError() {
        this.setState({ error: null });
    }

    // Azioni per i filtri
    setFilters(filters) {
        this.setState({
            filters: {
                ...this.state.filters,
                ...filters
            }
        });
    }

    // Azioni per la paginazione
    setCurrentPage(page) {
        this.setState({ currentPage: page });
    }

    // Reset dello stato
    reset() {
        this.setState({ ...initialState });
    }

    // Gestione statistiche
    updateStatistics(key, value) {
        this.setState({
            statistics: {
                ...this.state.statistics,
                [key]: value
            }
        });
    }
}

// Crea un'istanza globale dello store
const store = new Store();

// Esporta l'istanza e la classe
export { store, Store };