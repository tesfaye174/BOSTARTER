import { API } from './api.js';
import { showErrorMessage, showSuccessMessage } from './notifications.js';
import { validatePassword } from './validation.js';

// Gestione della registrazione
class RegistrationManager {
    static async register(userData) {
        try {
            const response = await fetch('/api/auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            });

            if (!response.ok) {
                throw new Error('Errore nella registrazione');
            }

            return await response.json();
        } catch (error) {
            console.error('Errore:', error);
            throw error;
        }
    }

    static async validateUsername(username) {
        try {
            const response = await fetch(`/api/auth/validate-username?username=${encodeURIComponent(username)}`);
            return await response.json();
        } catch (error) {
            console.error('Errore:', error);
            throw error;
        }
    }

    static async validateEmail(email) {
        try {
            const response = await fetch(`/api/auth/validate-email?email=${encodeURIComponent(email)}`);
            return await response.json();
        } catch (error) {
            console.error('Errore:', error);
            throw error;
        }
    }
}

// Gestione dell'interfaccia utente
class RegistrationUI {
    static initialize() {
        // Inizializza i validatori
        this.initializeValidators();

        // Inizializza i toggle per le password
        this.initializePasswordToggles();

        // Inizializza la gestione delle competenze
        this.initializeSkills();

        // Inizializza il form
        this.initializeForm();
    }

    static initializeValidators() {
        const form = document.getElementById('registrationForm');
        const inputs = form.querySelectorAll('input, select');

        inputs.forEach(input => {
            input.addEventListener('input', () => this.validateInput(input));
            input.addEventListener('blur', () => this.validateInput(input));
        });
    }

    static validateInput(input) {
        const value = input.value.trim();
        let isValid = true;
        let errorMessage = '';

        switch (input.id) {
            case 'name':
            case 'surname':
                isValid = /^[a-zA-Z\s]{2,}$/.test(value);
                errorMessage = 'Inserisci un nome valido (solo lettere e spazi)';
                break;

            case 'nickname':
                isValid = /^[a-zA-Z0-9_]{3,20}$/.test(value);
                errorMessage = 'Il nickname deve contenere tra 3 e 20 caratteri alfanumerici';
                break;

            case 'email':
                isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                errorMessage = 'Inserisci un indirizzo email valido';
                break;

            case 'password':
                isValid = this.validatePasswordStrength(value);
                errorMessage = 'La password deve contenere almeno 8 caratteri';
                break;

            case 'password_confirm':
                isValid = value === document.getElementById('password').value;
                errorMessage = 'Le password non coincidono';
                break;

            case 'birthYear':
                const year = parseInt(value);
                const currentYear = new Date().getFullYear();
                isValid = year >= 1900 && year <= currentYear - 18;
                errorMessage = 'Inserisci un anno di nascita valido';
                break;

            case 'birthPlace':
                isValid = value.length >= 2;
                errorMessage = 'Inserisci un luogo di nascita valido';
                break;

            case 'securityCode':
                isValid = value.length >= 6;
                errorMessage = 'Il codice di sicurezza deve contenere almeno 6 caratteri';
                break;
        }

        this.updateInputValidation(input, isValid, errorMessage);
        return isValid;
    }

    static validatePasswordStrength(password) {
        let strength = 0;
        const strengthBar = document.getElementById('password-strength-bar');
        const strengthText = document.getElementById('password-strength-text');

        if (password.length >= 8) strength += 20;
        if (password.length >= 12) strength += 20;
        if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength += 20;
        if (/[0-9]/.test(password)) strength += 20;
        if (/[^A-Za-z0-9]/.test(password)) strength += 20;

        strengthBar.style.width = `${strength}%`;
        strengthBar.className = `progress-bar bg-${this.getStrengthColor(strength)}`;

        const strengthMessage = this.getStrengthMessage(strength);
        strengthText.textContent = strengthMessage;

        return strength >= 40;
    }

    static getStrengthColor(strength) {
        if (strength < 40) return 'danger';
        if (strength < 80) return 'warning';
        return 'success';
    }

    static getStrengthMessage(strength) {
        if (strength < 40) return 'Password debole';
        if (strength < 80) return 'Password media';
        return 'Password forte';
    }

    static updateInputValidation(input, isValid, errorMessage) {
        input.classList.remove('is-valid', 'is-invalid');
        input.classList.add(isValid ? 'is-valid' : 'is-invalid');

        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = errorMessage;
        }
    }

    static initializePasswordToggles() {
        const toggleButtons = document.querySelectorAll('[data-toggle="password"]');
        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                button.querySelector('i').classList.toggle('bi-eye');
                button.querySelector('i').classList.toggle('bi-eye-slash');
            });
        });
    }

    static initializeSkills() {
        const roleSelect = document.getElementById('role');
        const skillsSection = document.getElementById('skillsSection');

        roleSelect.addEventListener('change', () => {
            const isCreator = roleSelect.value === 'creatore';
            skillsSection.style.display = isCreator ? 'block' : 'none';
        });
    }

    static initializeForm() {
        const form = document.getElementById('registrationForm');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!this.validateForm()) {
                return;
            }

            const formData = new FormData(form);
            const userData = {
                name: formData.get('name'),
                surname: formData.get('surname'),
                nickname: formData.get('nickname'),
                email: formData.get('email'),
                password: formData.get('password'),
                birthYear: formData.get('birthYear'),
                birthPlace: formData.get('birthPlace'),
                role: formData.get('role')
            };

            if (formData.get('role') === 'amministratore') {
                userData.securityCode = formData.get('securityCode');
            }

            if (formData.get('role') === 'creatore') {
                const skills = [];
                const skillInputs = form.querySelectorAll('select[name="skills[]"]');
                const levelInputs = form.querySelectorAll('input[name="levels[]"]');

                skillInputs.forEach((select, index) => {
                    if (select.value) {
                        skills.push({
                            skill: select.value,
                            level: parseInt(levelInputs[index].value)
                        });
                    }
                });

                userData.skills = skills;
            }

            try {
                const result = await RegistrationManager.register(userData);
                if (result.success) {
                    this.showSuccess('Registrazione completata con successo!');
                    setTimeout(() => {
                        window.location.href = '/login.html';
                    }, 2000);
                } else {
                    this.showError(result.message || 'Errore durante la registrazione');
                }
            } catch (error) {
                this.showError('Errore di connessione al server');
            }
        });
    }

    static validateForm() {
        const form = document.getElementById('registrationForm');
        const inputs = form.querySelectorAll('input, select');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateInput(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    static showError(message) {
        const errorDiv = document.getElementById('registrationError');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    static showSuccess(message) {
        const successDiv = document.getElementById('registrationSuccess');
        successDiv.textContent = message;
        successDiv.style.display = 'block';
    }
}

// Inizializzazione
document.addEventListener('DOMContentLoaded', () => {
    RegistrationUI.initialize();
});

async function handleRegistration(event) {
    event.preventDefault();

    // Ottieni i dati dal form
    const formData = {
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        password_confirm: document.getElementById('password_confirm').value,
        nickname: document.getElementById('nickname').value,
        name: document.getElementById('name').value,
        surname: document.getElementById('surname').value
    };

    // Validazione base
    if (!formData.email || !formData.password || !formData.password_confirm || 
        !formData.nickname || !formData.name || !formData.surname) {
        showErrorMessage('Tutti i campi sono obbligatori');
        return;
    }

    // Validazione email
    if (!formData.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        showErrorMessage('Formato email non valido');
        return;
    }

    // Validazione password
    if (!validatePassword(formData.password)) {
        showErrorMessage('La password deve essere di almeno 12 caratteri e contenere almeno una lettera maiuscola, un numero e un carattere speciale');
        return;
    }

    // Verifica che le password coincidano
    if (formData.password !== formData.password_confirm) {
        showErrorMessage('Le password non coincidono');
        return;
    }

    try {
        // Mostra indicatore di caricamento
        const submitButton = document.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Registrazione in corso...';

        // Effettua la richiesta di registrazione
        const response = await API.register(formData);

        if (response.success) {
            showSuccessMessage(response.message);
            // Reindirizza alla pagina di login dopo 2 secondi
            setTimeout(() => {
                window.location.href = '/login.html';
            }, 2000);
        } else {
            showErrorMessage(response.message);
        }
    } catch (error) {
        showErrorMessage(error.message || 'Errore durante la registrazione');
    } finally {
        // Ripristina il pulsante
        const submitButton = document.querySelector('button[type="submit"]');
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

// Aggiungi la validazione in tempo reale
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registrationForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password_confirm');
    const emailInput = document.getElementById('email');
    const nicknameInput = document.getElementById('nickname');

    // Validazione password in tempo reale
    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const isValid = validatePassword(password);
        passwordInput.setCustomValidity(isValid ? '' : 'Password non valida');
        passwordInput.reportValidity();
    });

    // Verifica password coincidenti in tempo reale
    confirmPasswordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const isValid = password === confirmPassword;
        confirmPasswordInput.setCustomValidity(isValid ? '' : 'Le password non coincidono');
        confirmPasswordInput.reportValidity();
    });

    // Validazione email in tempo reale
    emailInput.addEventListener('input', () => {
        const email = emailInput.value;
        const isValid = email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/);
        emailInput.setCustomValidity(isValid ? '' : 'Email non valida');
        emailInput.reportValidity();
    });

    // Gestione submit del form
    form.addEventListener('submit', handleRegistration);
});

export { handleRegistration }; 