// Gestione dell'autenticazione
class Auth {
    static async login(email, password, isAdmin = false, securityCode = null) {
        try {
            const response = await API.login(email, password, false, isAdmin, securityCode);
            
            // Salva il token JWT e i dati utente
            localStorage.setItem('token', response.token);
            localStorage.setItem('user', JSON.stringify(response.user));
            
            return response;
        } catch (error) {
            console.error('Errore login:', error);
            throw error;
        }
    }

    static async register(userData) {
        try {
            return await API.register(userData);
        } catch (error) {
            console.error('Errore registrazione:', error);
            throw error;
        }
    }

    static logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        // Chiamata asincrona al logout lato server, ma non aspettiamo la risposta
        API.logout().catch(err => console.error('Errore durante il logout:', err));
        window.location.href = 'index.html';
    }

    static isAuthenticated() {
        return !!localStorage.getItem('token');
    }

    static getToken() {
        return localStorage.getItem('token');
    }

    static getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }

    static isAdmin() {
        const user = this.getUser();
        return user && user.role === 'admin';
    }

    static isCreator() {
        const user = this.getUser();
        return user && (user.role === 'creator' || user.role === 'admin');
    }

    static async validateToken() {
        try {
            const token = this.getToken();
            if (!token) return false;

            // Utilizzare API class per validare il token
            await API.request('auth/validate.php');
            return true;
        } catch (error) {
            console.error('Errore validazione token:', error);
            return false;
        }
    }

    static async checkAuth() {
        // Verifica se l'utente è autenticato, altrimenti torna false
        if (!this.isAuthenticated()) {
            return false;
        }
        
        // Controlla validità del token
        const isValid = await this.validateToken();
        
        // Se token non valido, esegui logout
        if (!isValid) {
            this.logout();
            return false;
        }
        
        return true;
    }
}

// Gestione degli errori
class ErrorHandler {
    static showError(message, elementId = 'error-message') {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            
            // Aggiungi classe per animazione
            errorElement.classList.add('animate__animated', 'animate__shakeX');
            
            // Rimuovi le classi dopo l'animazione
            setTimeout(() => {
                errorElement.classList.remove('animate__animated', 'animate__shakeX');
            }, 1000);
        } else {
            console.error(message);
        }
    }

    static hideError(elementId = 'error-message') {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }
}

// Validazione form
class FormValidator {
    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static validatePassword(password) {
        return password.length >= 8;
    }

    static validateSecurityCode(code) {
        return code && code.length >= 6;
    }

    static validateForm(formData) {
        const errors = [];

        if (!this.validateEmail(formData.email)) {
            errors.push('Email non valida');
        }

        if (!this.validatePassword(formData.password)) {
            errors.push('La password deve contenere almeno 8 caratteri');
        }

        if (formData.isAdmin && !this.validateSecurityCode(formData.securityCode)) {
            errors.push('Il codice di sicurezza deve contenere almeno 6 caratteri');
        }

        return errors;
    }
}

// Funzione per il logout, richiamata dall'handler onclick
function logout(event) {
    event.preventDefault();
    Auth.logout();
}

// Inizializzazione dell'autenticazione
document.addEventListener('DOMContentLoaded', async () => {
    await Auth.checkAuth();
});