// API endpoints
const API_BASE_URL = '/server';
const API_TIMEOUT = 15000; // 15 secondi di timeout per le richieste

// Definizione della classe ApiError per errori specifici dell'API
class ApiError extends Error {
    constructor(message, status, data = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status; // Codice di stato HTTP (o 0 per errori di rete/timeout)
        this.data = data;     // Dati aggiuntivi dall'errore (es. corpo della risposta JSON)
    }
}

class API {
    static BASE_URL = API_BASE_URL;
    static TOKEN_KEY = 'token';

    /**
     * Metodo base per le richieste API con gestione timeout
     */
    static async request(endpoint, options = {}) {
        const token = localStorage.getItem(this.TOKEN_KEY);
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        // Imposta un timeout per la richiesta
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT);
        
        try {
            const response = await fetch(`${this.BASE_URL}/${endpoint}`, {
                ...options,
                headers,
                signal: controller.signal
            });

            // Pulisci il timeout una volta ottenuta la risposta
            clearTimeout(timeoutId);

            // Gestione errori HTTP
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: response.statusText }));
                throw new ApiError(
                    errorData.message || `Errore HTTP: ${response.status}`,
                    response.status,
                    errorData
                );
            }

            // Gestisce risposte vuote (es. 204 No Content)
            if (response.status === 204) {
                return null; // O un oggetto vuoto {}, a seconda delle esigenze
            }

            return await response.json();
        } catch (error) {
            clearTimeout(timeoutId); // Assicurati che il timeout sia cancellato anche in caso di errore
            // Gestisci errori di timeout
            if (error.name === 'AbortError') {
                throw new ApiError('La richiesta ha impiegato troppo tempo a rispondere (Timeout)', 408);
            }

            // Rilancia l'errore originale se è già un ApiError
            if (error instanceof ApiError) {
                throw error;
            }

            // Altrimenti, crea un nuovo ApiError generico
            console.error('Errore API non gestito:', error);
            // Tenta di fornire un messaggio più specifico se possibile
            const message = error.message || 'Errore di rete o del server sconosciuto';
            throw new ApiError(message, 0); // Status 0 per errori non HTTP
        }
    }

    // Autenticazione
    static async login(email, password, rememberMe = false, isAdmin = false, securityCode = null) {
        return this.request('auth/login.php', {
            method: 'POST',
            body: JSON.stringify({ email, password, rememberMe, isAdmin, securityCode })
        });
    }

    static async register(userData) {
        return this.request('auth/register.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    static async logout() {
        return this.request('auth/logout.php', {
            method: 'POST'
        });
    }

    // Gestione Progetti
    static async getProjects(page = 1, limit = 10, filters = {}) {
        // Costruisci query string dai filtri
        const queryParams = new URLSearchParams({
            page: page.toString(),
            limit: limit.toString()
        });
        
        // Aggiungi filtri aggiuntivi
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                queryParams.append(key, value.toString());
            }
        });
        
        return this.request(`projects/list.php?${queryParams.toString()}`);
    }

    static async getProject(id) {
        return this.request(`projects/detail.php?id=${id}`);
    }

    static async createProject(projectData) {
        return this.request('projects/create.php', {
            method: 'POST',
            body: JSON.stringify(projectData)
        });
    }

    static async updateProject(id, projectData) {
        return this.request(`projects/update.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(projectData)
        });
    }

    static async deleteProject(id) {
        return this.request(`projects/delete.php?id=${id}`, {
            method: 'DELETE'
        });
    }

    // Gestione Finanziamenti e Rewards
    static async createFunding(fundingData) {
        return this.request('funding.php', {
            method: 'POST',
            body: JSON.stringify(fundingData)
        });
    }

    static async getProjectFundings(projectId) {
        return this.request(`funding.php?project_id=${projectId}`);
    }

    static async getProjectRewards(projectId) {
        return this.request(`rewards.php?project_id=${projectId}`);
    }

    static async createReward(rewardData) {
        return this.request('rewards.php', {
            method: 'POST',
            body: JSON.stringify(rewardData)
        });
    }

    // Gestione Competenze
    static async getSkills() {
        return this.request('skills.php');
    }

    static async addSkill(skillData) {
        return this.request('skills.php', {
            method: 'POST',
            body: JSON.stringify(skillData)
        });
    }

    // Gestione Candidature
    static async applyForProject(applicationData) {
        return this.request('applications.php', {
            method: 'POST',
            body: JSON.stringify(applicationData)
        });
    }

    static async getProjectApplications(projectId) {
        return this.request(`applications.php?project_id=${projectId}`);
    }

    static async updateApplicationStatus(applicationId, status) {
        return this.request(`applications.php?id=${applicationId}`, {
            method: 'PUT',
            body: JSON.stringify({ status })
        });
    }

    // Gestione Commenti
    static async addComment(commentData) {
        return this.request('comments.php', {
            method: 'POST',
            body: JSON.stringify(commentData)
        });
    }

    static async getProjectComments(projectId) {
        return this.request(`comments.php?project_id=${projectId}`);
    }

    static async addCommentReply(commentId, replyData) {
        return this.request(`comments.php?id=${commentId}/reply`, {
            method: 'POST',
            body: JSON.stringify(replyData)
        });
    }

    // Statistiche
    static async getStats() {
        return this.request('statistics/general.php');
    }

    static async getTopCreators() {
        return this.request('statistics/top_creators.php');
    }

    static async getNearCompletionProjects() {
        return this.request('statistics/near_completion.php');
    }

    static async getTopFunders() {
        return this.request('statistics/top_funders.php');
    }

    // Gestione Utente
    static async getUserProfile() {
        return this.request('profile.php');
    }

    static async updateUserProfile(userData) {
        return this.request('profile.php', {
            method: 'PUT',
            body: JSON.stringify(userData)
        });
    }

    static async getUserSkills() {
        return this.request('users.php?id=${userId}/skills');
    }

    static async updateUserSkills(userId, skills) {
        return this.request(`users.php?id=${userId}/skills`, {
            method: 'PUT',
            body: JSON.stringify({ skills })
        });
    }

    // Metodi per le donazioni
    static async makeDonation(projectId, amount) {
        return this.request('donations.php', {
            method: 'POST',
            body: JSON.stringify({ projectId, amount })
        });
    }

    // Metodi per l'amministrazione
    static async getAdminStats() {
        return this.request('admin/stats.php');
    }

    static async getAdminUsers() {
        return this.request('admin/users.php');
    }

    static async updateUserStatus(userId, status) {
        return this.request(`admin/users.php?id=${userId}`, {
            method: 'PUT',
            body: JSON.stringify({ status })
        });
    }
}

/**
 * Classe personalizzata per gli errori API
 */
// Esporta la classe API e ApiError per l'uso in altri moduli
export { API, ApiError };