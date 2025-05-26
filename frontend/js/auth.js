document.addEventListener('DOMContentLoaded', function() {
    // Carica i modali nel DOM
    fetch('/frontend/components/auth-modals.html')
        .then(response => response.text())
        .then(html => {
            document.body.insertAdjacentHTML('beforeend', html);
            setupAuthEventListeners();
            setupCreatorLinkHandler();
        });
});

import { validateForm } from './validation.js';
import { Logger } from './logger.js';
import { Analytics } from './analytics.js';
import { Notifications } from './notifications.js';

class Auth {
    constructor() {
        this.logger = new Logger('Auth');
        this.analytics = new Analytics();
        this.notifications = new Notifications();
        this.isAuthenticated = false;
        this.user = null;
        this.sessionTimeout = 30 * 60 * 1000; // 30 minuti
        this.initialize();
    }

    async initialize() {
        try {
            // Verifica token JWT
            const token = localStorage.getItem('auth_token');
            if (token) {
                await this.validateToken(token);
            }

            // Inizializza event listeners
            this.setupEventListeners();
            
            // Verifica sessione attiva
            this.checkSession();
            
            // Inizializza biometric auth se disponibile
            this.initializeBiometricAuth();
            
            this.logger.info('Sistema di autenticazione inizializzato');
        } catch (error) {
            this.logger.error('Errore durante l\'inizializzazione', error);
        }
    }

    setupEventListeners() {
        // Login form
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleLogin(e.target);
            });
        }

        // Register form
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleRegister(e.target);
            });
        }

        // Logout button
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.handleLogout());
        }

        // Password visibility toggle
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => this.togglePasswordVisibility(e));
        });

        // Remember me checkbox
        const rememberMe = document.getElementById('remember-me');
        if (rememberMe) {
            rememberMe.addEventListener('change', (e) => this.handleRememberMe(e));
        }
    }

    async handleLogin(form) {
        try {
            const formData = new FormData(form);
            const email = formData.get('email');
            const password = formData.get('password');
            const rememberMe = formData.get('remember-me') === 'on';

            // Validazione input
            if (!this.validateLoginInput(email, password)) {
                return;
            }

            // Mostra loading state
            this.setLoadingState(true);

            // Tentativo di login
            const response = await fetch('/backend/user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'login',
                    email,
                    password,
                    remember_me: rememberMe
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Salva token e dati utente
                await this.handleSuccessfulLogin(data, rememberMe);
                
                // Traccia evento analytics
                this.analytics.trackEvent('login', 'success');
                
                // Mostra notifica
                this.notifications.show('success', 'Login effettuato con successo');
                
                // Redirect
                window.location.href = '/dashboard';
            } else {
                throw new Error(data.message || 'Errore durante il login');
            }
        } catch (error) {
            this.logger.error('Errore login', error);
            this.notifications.show('error', error.message);
            this.analytics.trackEvent('login', 'error', { error: error.message });
        } finally {
            this.setLoadingState(false);
        }
    }

    async handleRegister(form) {
        try {
            const formData = new FormData(form);
            const userData = {
                email: formData.get('email'),
                password: formData.get('password'),
                confirm_password: formData.get('confirm-password'),
                nome: formData.get('nome'),
                cognome: formData.get('cognome'),
                nickname: formData.get('nickname')
            };

            // Validazione input
            if (!this.validateRegisterInput(userData)) {
                return;
            }

            // Mostra loading state
            this.setLoadingState(true);

            // Tentativo di registrazione
            const response = await fetch('/backend/user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'register',
                    ...userData
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Traccia evento analytics
                this.analytics.trackEvent('register', 'success');
                
                // Mostra notifica
                this.notifications.show('success', 'Registrazione completata con successo');
                
                // Redirect al login
                this.switchModal('register-modal', 'login-modal');
            } else {
                throw new Error(data.message || 'Errore durante la registrazione');
            }
        } catch (error) {
            this.logger.error('Errore registrazione', error);
            this.notifications.show('error', error.message);
            this.analytics.trackEvent('register', 'error', { error: error.message });
        } finally {
            this.setLoadingState(false);
        }
    }

    async handleLogout() {
        try {
            // Rimuovi token e dati sessione
            localStorage.removeItem('auth_token');
            sessionStorage.clear();
            
            // Chiamata API logout
            await fetch('/backend/user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'logout'
                })
            });

            // Traccia evento analytics
            this.analytics.trackEvent('logout', 'success');
            
            // Mostra notifica
            this.notifications.show('success', 'Logout effettuato con successo');
            
            // Redirect alla home
            window.location.href = '/';
        } catch (error) {
            this.logger.error('Errore logout', error);
            this.notifications.show('error', 'Errore durante il logout');
        }
    }

    async validateToken(token) {
        try {
            const response = await fetch('/backend/user.php', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                this.isAuthenticated = true;
                this.user = data.data;
                return true;
            }
            
            return false;
        } catch (error) {
            this.logger.error('Errore validazione token', error);
            return false;
        }
    }

    async handleSuccessfulLogin(data, rememberMe) {
        // Salva token
        if (rememberMe) {
            localStorage.setItem('auth_token', data.token);
        } else {
            sessionStorage.setItem('auth_token', data.token);
        }

        // Aggiorna stato
        this.isAuthenticated = true;
        this.user = data.user_data;

        // Imposta timeout sessione
        this.setSessionTimeout();

        // Aggiorna UI
        this.updateUI();
    }

    setSessionTimeout() {
        if (this.sessionTimer) {
            clearTimeout(this.sessionTimer);
        }

        this.sessionTimer = setTimeout(() => {
            this.handleLogout();
        }, this.sessionTimeout);
    }

    checkSession() {
        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
        if (token) {
            this.validateToken(token);
        }
    }

    async initializeBiometricAuth() {
        if (window.PublicKeyCredential) {
            try {
                const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
                if (available) {
                    this.setupBiometricAuth();
                }
            } catch (error) {
                this.logger.error('Errore inizializzazione biometric auth', error);
            }
        }
    }

    async setupBiometricAuth() {
        const biometricButton = document.getElementById('biometric-auth');
        if (biometricButton) {
            biometricButton.style.display = 'block';
            biometricButton.addEventListener('click', async () => {
                try {
                    const credential = await this.getBiometricCredential();
                    if (credential) {
                        await this.handleBiometricLogin(credential);
                    }
                } catch (error) {
                    this.logger.error('Errore biometric auth', error);
                    this.notifications.show('error', 'Errore durante l\'autenticazione biometrica');
                }
            });
        }
    }

    validateLoginInput(email, password) {
        if (!email || !password) {
            this.notifications.show('error', 'Tutti i campi sono obbligatori');
            return false;
        }

        if (!this.validateEmail(email)) {
            this.notifications.show('error', 'Email non valida');
            return false;
        }

        return true;
    }

    validateRegisterInput(data) {
        if (!data.email || !data.password || !data.confirm_password || !data.nome || !data.cognome || !data.nickname) {
            this.notifications.show('error', 'Tutti i campi sono obbligatori');
            return false;
        }

        if (!this.validateEmail(data.email)) {
            this.notifications.show('error', 'Email non valida');
            return false;
        }

        if (data.password !== data.confirm_password) {
            this.notifications.show('error', 'Le password non coincidono');
            return false;
        }

        if (data.password.length < 8) {
            this.notifications.show('error', 'La password deve essere di almeno 8 caratteri');
            return false;
        }

        return true;
    }

    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    setLoadingState(isLoading) {
        const buttons = document.querySelectorAll('button[type="submit"]');
        buttons.forEach(button => {
            button.disabled = isLoading;
            button.innerHTML = isLoading ? 
                '<i class="ri-loader-4-line animate-spin"></i> Caricamento...' : 
                button.dataset.originalText || button.innerHTML;
        });
    }

    togglePasswordVisibility(event) {
        const button = event.currentTarget;
        const input = button.previousElementSibling;
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        button.innerHTML = type === 'password' ? 
            '<i class="ri-eye-line"></i>' : 
            '<i class="ri-eye-off-line"></i>';
    }

    updateUI() {
        const authButtons = document.querySelectorAll('.auth-button');
        const userMenu = document.querySelector('.user-menu');
        
        if (this.isAuthenticated) {
            authButtons.forEach(button => button.style.display = 'none');
            if (userMenu) {
                userMenu.style.display = 'block';
                // Aggiorna dati utente nel menu
                const userName = userMenu.querySelector('.user-name');
                if (userName) {
                    userName.textContent = this.user.nickname;
                }
            }
        } else {
            authButtons.forEach(button => button.style.display = 'block');
            if (userMenu) {
                userMenu.style.display = 'none';
            }
        }
    }

    switchModal(fromId, toId) {
        const fromModal = document.getElementById(fromId);
        const toModal = document.getElementById(toId);
        
        if (fromModal && toModal) {
            fromModal.classList.add('hidden');
            toModal.classList.remove('hidden');
        }
    }
}

// Esporta l'istanza
export const auth = new Auth();

function setupAuthEventListeners() {
    // Event listeners per aprire i modali
    document.getElementById('login-link')?.addEventListener('click', (e) => {
        e.preventDefault();
        openModal('login-modal');
    });

    document.getElementById('register-link')?.addEventListener('click', (e) => {
        e.preventDefault();
        openModal('register-modal');
    });

    // Gestione form login
    document.getElementById('login-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'login');

        try {
            const response = await fetch('/backend/auth_api.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showNotification('Accesso effettuato con successo', 'success');
                setTimeout(() => window.location.href = '/frontend/dashboard.html', 1000);
            } else {
                showError('login-error', data.message);
            }
        } catch (error) {
            showError('login-error', 'Errore durante l\'accesso. Riprova più tardi.');
        }
    });

    // Gestione form registrazione con validazione e retry automatico
    document.getElementById('register-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        // Validazione form
        const validation = validateForm(formData);
        if (!validation.isValid) {
            // Mostra errori di validazione
            Object.entries(validation.errors).forEach(([field, message]) => {
                const input = form.querySelector(`[name="${field}"]`);
                showFormError(input, message);
            });
            return;
        }

        formData.append('action', 'register');

        const maxRetries = 3;
        let retryCount = 0;
        let lastError = null;

        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        const loadingSpinner = document.createElement('span');
        loadingSpinner.className = 'loading-spinner ml-2';

        while (retryCount < maxRetries) {
            try {
                submitButton.disabled = true;
                submitButton.innerHTML = retryCount === 0 ? 
                    'Registrazione in corso...' + loadingSpinner.outerHTML : 
                    `Nuovo tentativo (${retryCount + 1}/${maxRetries})` + loadingSpinner.outerHTML;

                const response = await fetch('/backend/auth_api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    // Resetta eventuali errori precedenti
                    form.querySelectorAll('.form-group.error').forEach(group => {
                        group.classList.remove('error');
                        group.querySelector('.error-message').textContent = '';
                    });

                    showNotification('Registrazione completata con successo! Effettua il login per continuare', 'success');
                    setTimeout(() => switchModal('register-modal', 'login-modal'), 2000);
                    return;
                } else {
                    lastError = data.message;
                    throw new Error(data.message || 'Errore durante la registrazione');
                }
            } catch (error) {
                lastError = error.message;
                retryCount++;

                if (retryCount < maxRetries) {
                    const waitTime = Math.min(1000 * Math.pow(2, retryCount), 5000);
                    showNotification(`Tentativo fallito. Nuovo tentativo tra ${waitTime/1000} secondi...`, 'warning');
                    await new Promise(resolve => setTimeout(resolve, waitTime));
                }
            }
        }

        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;

        // Gestione errori specifici
        if (lastError?.toLowerCase().includes('già registrato')) {
            showError('register-error', 'Questo indirizzo email è già registrato. Prova ad accedere.');
            const emailInput = form.querySelector('[name="email"]');
            showFormError(emailInput, 'Email già registrata');
        } else if (lastError?.toLowerCase().includes('non valido')) {
            showError('register-error', 'Verifica i dati inseriti e riprova.');
        } else {
            showError('register-error', 'Si è verificato un errore durante la registrazione. Per favore, riprova più tardi.');
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        clearErrors();
    }
}

function switchModal(fromModalId, toModalId) {
    closeModal(fromModalId);
    setTimeout(() => openModal(toModalId), 300);
}

function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.text-red-500');
    errorElements.forEach(element => {
        element.textContent = '';
        element.classList.add('hidden');
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white z-50`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

function setupCreatorLinkHandler() {
    const creatorLink = document.getElementById('creator-link');
    if (creatorLink) {
        creatorLink.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const response = await fetch('/backend/user.php');
                const userData = await response.json();
                
                if (response.ok) {
                    if (userData.role === 'creator') {
                        window.location.href = '/frontend/dashboard.html';
                    } else {
                        showNotification('Accesso riservato ai creatori. Registrati come creatore per continuare.', 'error');
                        openModal('register-modal');
                    }
                } else {
                    openModal('login-modal');
                }
            } catch (error) {
                showNotification('Effettua l\'accesso per continuare', 'error');
                openModal('login-modal');
            }
        });
    }
}