// Gestione dell'autenticazione e degli errori
import { API, ApiError } from './api.js'; // Assicurati che ApiError sia esportato da api.js

class Auth {
    static TOKEN_KEY = 'token';
    static USER_KEY = 'user';

    static async login(email, password, isAdmin = false, securityCode = null) {
        try {
            // Nota: rememberMe non sembra essere usato nell'API.login attuale
            const response = await API.login(email, password, false, isAdmin, securityCode);

            // Salva il token JWT e i dati utente
            localStorage.setItem(this.TOKEN_KEY, response.token);
            localStorage.setItem(this.USER_KEY, JSON.stringify(response.user));

            return response;
        } catch (error) {
            console.error('Errore login:', error);
            // Rilancia l'errore per essere gestito dal chiamante (es. nel form di login)
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
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.USER_KEY);
        // Chiamata asincrona al logout lato server, non blocca la navigazione
        API.logout().catch(err => console.error('Errore durante il logout API:', err));
        // Reindirizza alla pagina principale dopo il logout
        window.location.href = 'index.html';
    }

    static isAuthenticated() {
        return !!localStorage.getItem(this.TOKEN_KEY);
    }

    static getToken() {
        return localStorage.getItem(this.TOKEN_KEY);
    }

    static getUser() {
        const user = localStorage.getItem(this.USER_KEY);
        try {
            return user ? JSON.parse(user) : null;
        } catch (e) {
            console.error('Errore nel parsing dei dati utente:', e);
            // Rimuovi dati utente corrotti
            localStorage.removeItem(this.USER_KEY);
            return null;
        }
    }

    static isAdmin() {
        const user = this.getUser();
        // Assicurati che 'role' esista e sia 'admin'
        return user && user.role === 'admin';
    }

    static isCreator() {
        const user = this.getUser();
        // Un utente può essere creatore o admin per avere privilegi da creatore
        return user && (user.role === 'creator' || user.role === 'admin');
    }

    static async validateToken() {
        const token = this.getToken();
        if (!token) return false;

        try {
            // Utilizza API class per validare il token sul server
            // Assumendo che 'auth/validate.php' restituisca { success: true } se valido
            await API.request('auth/validate.php');
            return true;
        } catch (error) {
            console.error('Errore validazione token:', error);
            // Se l'errore è dovuto a token non valido (es. 401 Unauthorized), restituisci false
            if (error instanceof ApiError && error.status === 401) {
                return false;
            }
            // Per altri errori (es. rete), potresti voler gestire diversamente,
            // ma per semplicità consideriamo il token non valido
            return false;
        }
    }

    static async checkAuthAndRedirect(requiredRole = null, redirectPath = 'login.html') {
        if (!this.isAuthenticated()) {
            window.location.href = redirectPath;
            return false;
        }

        const isValid = await this.validateToken();
        if (!isValid) {
            this.logout(); // Esegue il logout completo se il token non è valido
            return false;
        }

        // Controllo del ruolo se richiesto
        if (requiredRole) {
            const user = this.getUser();
            let hasPermission = false;
            if (requiredRole === 'admin') {
                hasPermission = this.isAdmin();
            } else if (requiredRole === 'creator') {
                hasPermission = this.isCreator();
            }
            // Aggiungere altri ruoli se necessario

            if (!hasPermission) {
                console.warn(`Accesso negato: ruolo richiesto '${requiredRole}', utente ha ruolo '${user?.role}'`);
                // Reindirizza a una pagina di accesso negato o alla home
                window.location.href = 'index.html';
                return false;
            }
        }

        return true; // Autenticato e autorizzato
    }
}

// Gestione degli errori UI
class ErrorHandler {
    static showError(message, elementId = 'error-message', isGlobal = false) {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.innerHTML = message; // Usa innerHTML per permettere tag semplici come <br>
            errorElement.style.display = 'block';
            errorElement.classList.remove('d-none', 'alert-success');
            errorElement.classList.add('alert', 'alert-danger');

            // Aggiungi animazione
            errorElement.classList.add('animate__animated', 'animate__shakeX');
            setTimeout(() => {
                errorElement.classList.remove('animate__animated', 'animate__shakeX');
            }, 1000);
        } else if (isGlobal) {
            // Fallback per errori globali se l'elemento specifico non esiste
            alert(`Errore: ${message}`);
        } else {
            console.error(`Elemento errore non trovato ('${elementId}') per il messaggio: ${message}`);
        }
    }

    static hideError(elementId = 'error-message') {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.style.display = 'none';
            errorElement.classList.add('d-none');
            errorElement.textContent = ''; // Pulisci il contenuto
        }
    }

    static showSuccess(message, elementId = 'success-message') {
        const successElement = document.getElementById(elementId);
        if (successElement) {
            successElement.innerHTML = message;
            successElement.style.display = 'block';
            successElement.classList.remove('d-none', 'alert-danger');
            successElement.classList.add('alert', 'alert-success');
        } else {
            console.log('Successo:', message); // Fallback
        }
    }

    static hideSuccess(elementId = 'success-message') {
        const successElement = document.getElementById(elementId);
        if (successElement) {
            successElement.style.display = 'none';
            successElement.classList.add('d-none');
            successElement.textContent = '';
        }
    }
}

// Validazione form (potrebbe essere spostata in un file utils.js se usata altrove)
class FormValidator {
    static validateEmail(email) {
        if (!email) return false;
        const re = /^[^s@]+@[^s@]+.[^s@]+$/;
        return re.test(String(email).toLowerCase());
    }

    static validatePassword(password) {
        // Almeno 8 caratteri, una lettera maiuscola, una minuscola, un numero
        // const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
        // Semplificato: almeno 8 caratteri
        return password && password.length >= 8;
    }

    static validateSecurityCode(code) {
        // Codice numerico di 6 cifre
        const re = /^\d{6}$/;
        return code && re.test(code);
    }

    // Esempio di validazione per un form di registrazione
    static validateRegistrationForm(formData) {
        const errors = {};
        if (!formData.nickname) errors.nickname = 'Il nickname è obbligatorio.';
        if (!this.validateEmail(formData.email)) errors.email = 'Email non valida.';
        if (!this.validatePassword(formData.password)) errors.password = 'La password deve contenere almeno 8 caratteri.';
        if (formData.password !== formData.confirmPassword) errors.confirmPassword = 'Le password non coincidono.';
        // Aggiungere altre validazioni (es. nome, cognome, tipo utente)
        return errors; // Restituisce un oggetto di errori, vuoto se valido
    }

     // Esempio di validazione per un form di login
    static validateLoginForm(formData) {
        const errors = {};
        if (!this.validateEmail(formData.email)) errors.email = 'Email non valida.';
        if (!formData.password) errors.password = 'La password è obbligatoria.';
        if (formData.isAdmin && !this.validateSecurityCode(formData.securityCode)) {
             errors.securityCode = 'Il codice di sicurezza deve essere di 6 cifre.';
        }
        return errors;
    }
}

// Esporta le classi per poterle usare in altri moduli
export { Auth, ErrorHandler, FormValidator };

// Event listener globale per il logout (se esiste un bottone con id 'logout-button')
document.addEventListener('DOMContentLoaded', () => {
    const logoutButton = document.getElementById('logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', (event) => {
            event.preventDefault();
            Auth.logout();
        });
    }

    // Potrebbe essere utile aggiornare lo stato UI (es. mostrare/nascondere link login/profilo)
    // updateLoginStateUI(); // Funzione da implementare per aggiornare l'UI
});

// Funzione helper (esempio) per aggiornare l'UI in base allo stato di login
/*
function updateLoginStateUI() {
    const user = Auth.getUser();
    const loginLink = document.getElementById('login-link');
    const registerLink = document.getElementById('register-link');
    const profileLink = document.getElementById('profile-link'); // Link al profilo utente
    const logoutButton = document.getElementById('logout-button');

    if (user) {
        if (loginLink) loginLink.style.display = 'none';
        if (registerLink) registerLink.style.display = 'none';
        if (profileLink) profileLink.style.display = 'block'; // Mostra link profilo
        if (logoutButton) logoutButton.style.display = 'block';
        // Potresti anche mostrare il nome utente da qualche parte
    } else {
        if (loginLink) loginLink.style.display = 'block';
        if (registerLink) registerLink.style.display = 'block';
        if (profileLink) profileLink.style.display = 'none';
        if (logoutButton) logoutButton.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', updateLoginStateUI);
*/