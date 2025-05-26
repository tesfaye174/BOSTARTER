// Configurazione dell'API
const API_CONFIG = {
    baseUrl: 'http://localhost:8080/api',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
};

// Classe per la gestione delle API
class ApiManager {
    constructor() {
        this.baseUrl = API_CONFIG.baseUrl;
        this.timeout = API_CONFIG.timeout;
        this.headers = { ...API_CONFIG.headers };
    }

    // Gestione degli errori
    handleError(error) {
        if (error.response) {
            throw new Error(error.response.data.message || 'Errore nella richiesta');
        } else if (error.request) {
            throw new Error('Nessuna risposta dal server');
        } else {
            throw new Error('Errore nella configurazione della richiesta');
        }
    }

    // Gestione del timeout
    async timeoutPromise(ms) {
        return new Promise((_, reject) => {
            setTimeout(() => reject(new Error('Timeout della richiesta')), ms);
        });
    }

    // Metodo per le richieste GET
    async get(endpoint, params = {}) {
        try {
            const url = new URL(`${this.baseUrl}${endpoint}`);
            Object.entries(params).forEach(([key, value]) => {
                url.searchParams.append(key, value);
            });

            const response = await Promise.race([
                fetch(url, {
                    method: 'GET',
                    headers: this.headers
                }),
                this.timeoutPromise(this.timeout)
            ]);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            this.handleError(error);
        }
    }

    // Metodo per le richieste POST
    async post(endpoint, data = {}) {
        try {
            const response = await Promise.race([
                fetch(`${this.baseUrl}${endpoint}`, {
                    method: 'POST',
                    headers: this.headers,
                    body: JSON.stringify(data)
                }),
                this.timeoutPromise(this.timeout)
            ]);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            this.handleError(error);
        }
    }

    // Metodo per le richieste PUT
    async put(endpoint, data = {}) {
        try {
            const response = await Promise.race([
                fetch(`${this.baseUrl}${endpoint}`, {
                    method: 'PUT',
                    headers: this.headers,
                    body: JSON.stringify(data)
                }),
                this.timeoutPromise(this.timeout)
            ]);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            this.handleError(error);
        }
    }

    // Metodo per le richieste DELETE
    async delete(endpoint) {
        try {
            const response = await Promise.race([
                fetch(`${this.baseUrl}${endpoint}`, {
                    method: 'DELETE',
                    headers: this.headers
                }),
                this.timeoutPromise(this.timeout)
            ]);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            this.handleError(error);
        }
    }

    // Imposta il token di autenticazione
    setAuthToken(token) {
        if (token) {
            this.headers['Authorization'] = `Bearer ${token}`;
        } else {
            delete this.headers['Authorization'];
        }
    }

    // Rimuove il token di autenticazione
    removeAuthToken() {
        delete this.headers['Authorization'];
    }
}

// Crea un'istanza globale del gestore API
const apiManager = new ApiManager();

// Esporta l'istanza e la classe
export { apiManager, ApiManager };