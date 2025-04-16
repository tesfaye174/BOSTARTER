import { API } from './api.js';
// Rimosso import { showErrorMessage, showSuccessMessage } from './notifications.js';
// Importa le funzioni di validazione specifiche e sanitizeInput
import { validatePassword, validateEmail, validateNickname, validateName, sanitizeInput } from './validation.js';
// Importa ErrorHandler da auth.js (o un file di utilità se spostato)
import { ErrorHandler } from './auth.js'; 

// Rimuoviamo RegistrationManager, useremo API direttamente

// Gestione dell'interfaccia utente
class RegistrationUI {
    static initialize() {
        const form = document.getElementById('registrationForm');
        if (!form) {
            console.error('Elemento form di registrazione non trovato.');
            return;
        }

        // Inizializza i validatori
        this.initializeValidators(form);

        // Inizializza i toggle per le password
        this.initializePasswordToggles(form);

        // Inizializza il form
        this.initializeForm(form);
    }

    static initializeValidators(form) {
        const inputs = form.querySelectorAll('input[required], input[pattern], input[minlength]');

        inputs.forEach(input => {
            input.addEventListener('input', () => this.validateInput(input));
            // input.addEventListener('blur', () => this.validateInput(input)); // Potrebbe essere fastidioso, validiamo on input e submit
        });

        // Validazione specifica per conferma password
        const passwordConfirmInput = form.querySelector('#password_confirm');
        const passwordInput = form.querySelector('#password');
        if (passwordConfirmInput && passwordInput) {
            passwordConfirmInput.addEventListener('input', () => {
                const isValid = passwordConfirmInput.value === passwordInput.value;
                this.updateInputValidation(passwordConfirmInput, isValid, 'Le password non coincidono');
            });
        }

        // Validazione password strength in tempo reale
        if (passwordInput) {
            passwordInput.addEventListener('input', () => {
                this.validatePasswordStrength(passwordInput.value);
            });
        }
    }

    static validateInput(input) {
        const value = sanitizeInput(input.value); // Sanitizza l'input
        let isValid = false;
        let errorMessage = input.title || 'Valore non valido'; // Usa il title per il messaggio di default

        // Usa le funzioni di validazione importate
        switch (input.id) {
            case 'name':
            case 'surname':
                isValid = validateName(value);
                errorMessage = 'Inserisci un nome/cognome valido (min 2 caratteri, lettere, spazi, apostrofi, trattini).';
                break;
            case 'nickname':
                isValid = validateNickname(value);
                errorMessage = 'Il nickname deve contenere 3-30 caratteri alfanumerici, trattini o underscore.';
                break;
            case 'email':
                isValid = validateEmail(value);
                errorMessage = 'Inserisci un indirizzo email valido.';
                break;
            case 'password':
                // La validazione della forza viene gestita separatamente in tempo reale
                // Qui controlliamo solo se è vuoto se 'required'
                isValid = input.required ? value.length > 0 : true; 
                // La validazione completa della password avviene con validatePasswordStrength
                // e al momento del submit
                errorMessage = 'La password è obbligatoria.';
                // Non mostrare errore qui per la complessità, gestito da strength indicator
                if (isValid) errorMessage = ''; 
                break;
            case 'password_confirm':
                const passwordValue = document.getElementById('password')?.value || '';
                isValid = value === passwordValue;
                errorMessage = 'Le password non coincidono.';
                break;
            case 'terms':
                isValid = input.checked;
                errorMessage = 'Devi accettare i termini per continuare.';
                break;
            // Aggiungere altri casi se necessario
            default:
                // Fallback alla validazione HTML5
                isValid = input.checkValidity(); 
                if (input.validity.patternMismatch && input.title) {
                    errorMessage = input.title;
                }
                break;
        }

        this.updateInputValidation(input, isValid, errorMessage);
        return isValid;
    }

    static validatePasswordStrength(password) {
        const strengthBar = document.getElementById('passwordStrength');
        const strengthFeedback = document.getElementById('passwordFeedback');
        const passwordInput = document.getElementById('password');

        if (!strengthBar || !strengthFeedback || !passwordInput) return false;

        const result = {
            length: password.length >= 12,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        let score = 0;
        if (result.length) score++;
        if (result.uppercase) score++;
        if (result.lowercase) score++;
        if (result.number) score++;
        if (result.special) score++;

        const strengthPercent = (score / 5) * 100;
        strengthBar.style.width = `${strengthPercent}%`;

        let strengthMessage = 'La password deve contenere almeno 12 caratteri';
        let colorClass = 'bg-danger';

        if (score >= 5) {
            strengthMessage = 'Password forte';
            colorClass = 'bg-success';
        } else if (score >= 3) {
            strengthMessage = 'Password media';
            colorClass = 'bg-warning';
        } else {
            strengthMessage = 'Password debole. Requisiti: 12+ caratteri, maiuscole, minuscole, numeri, simboli.';
            colorClass = 'bg-danger';
        }
        
        // Aggiorna i messaggi e le classi
        strengthFeedback.textContent = strengthMessage;
        strengthBar.className = `progress-bar ${colorClass}`;

        // Aggiorna la validità dell'input password basata sulla forza (almeno media)
        const isStrongEnough = score >= 3; // Consideriamo 'media' come minima accettabile
        // this.updateInputValidation(passwordInput, isStrongEnough, strengthMessage); 
        // Non aggiorniamo is-invalid qui per non essere troppo aggressivi durante la digitazione
        // La validazione finale avverrà al submit
        return isStrongEnough; 
    }

    // Rimosso getStrengthColor e getStrengthMessage

    static updateInputValidation(input, isValid, errorMessage) {
        const feedbackElement = input.closest('.form-group, .mb-3, .col-md-6')?.querySelector('.invalid-feedback');
        
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            if (feedbackElement) {
                feedbackElement.textContent = ''; // Pulisci messaggio errore se valido
            }
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            if (feedbackElement) {
                feedbackElement.textContent = errorMessage;
            }
        }
    }

    static initializePasswordToggles(form) {
        const toggleButtons = form.querySelectorAll('#togglePassword, #toggleConfirmPassword');
        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetId = button.id === 'togglePassword' ? 'password' : 'password_confirm';
                const input = form.querySelector(`#${targetId}`);
                if (input) {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    const icon = button.querySelector('i');
                    icon.classList.toggle('bi-eye');
                    icon.classList.toggle('bi-eye-slash');
                }
            });
        });
    }

    // Rimosso initializeSkills, gestito da HTML o altro JS se necessario

    static initializeForm(form) {
        const submitButton = form.querySelector('#submitButton');
        const spinner = submitButton?.querySelector('.spinner-border');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            ErrorHandler.hideError('registrationError');
            ErrorHandler.hideError('registrationSuccess'); // Nascondi successo precedente

            let isFormValid = true;
            const inputsToValidate = form.querySelectorAll('input[required], input[pattern], input[minlength], input#password_confirm, input#terms');
            
            inputsToValidate.forEach(input => {
                // Esegui la validazione per ogni input e aggiorna lo stato generale
                if (!this.validateInput(input)) {
                    isFormValid = false;
                }
            });

            // Validazione specifica forza password al submit
            const passwordInput = form.querySelector('#password');
            if (passwordInput && !validatePassword(passwordInput.value)) {
                this.updateInputValidation(passwordInput, false, 'La password non soddisfa i requisiti di sicurezza.');
                isFormValid = false;
            }

            if (!isFormValid) {
                ErrorHandler.showError('Per favore, correggi gli errori nel modulo.', 'registrationError');
                // Focus sul primo campo non valido
                form.querySelector('.is-invalid')?.focus();
                return;
            }

            // Mostra spinner e disabilita bottone
            if (spinner) spinner.classList.remove('d-none');
            if (submitButton) submitButton.disabled = true;

            // Prepara i dati utente
            const userData = {
                email: sanitizeInput(form.querySelector('#email').value),
                password: form.querySelector('#password').value, // Non sanitizzare la password
                nickname: sanitizeInput(form.querySelector('#nickname').value),
                name: sanitizeInput(form.querySelector('#name').value),
                surname: sanitizeInput(form.querySelector('#surname').value),
                // Aggiungere altri campi se presenti nel form
            };

            try {
                // Usa API.register
                const response = await API.register(userData);
                
                // Mostra messaggio di successo
                ErrorHandler.showSuccess('Registrazione avvenuta con successo! Puoi ora effettuare il login.', 'registrationSuccess');
                form.reset(); // Resetta il form
                form.querySelectorAll('.is-valid').forEach(el => el.classList.remove('is-valid')); // Rimuovi classi validazione
                // Potresti reindirizzare al login dopo un breve ritardo:
                // setTimeout(() => { window.location.href = 'login.html'; }, 3000);

            } catch (error) {
                console.error('Registration failed:', error);
                let errorMessage = 'Errore durante la registrazione. Riprova.';
                if (error instanceof ApiError) { // Assicurati che ApiError sia disponibile
                    errorMessage = error.message; // Usa il messaggio dall'errore API
                } else if (error.message) {
                    errorMessage = error.message;
                }
                ErrorHandler.showError(errorMessage, 'registrationError');
            } finally {
                // Nascondi spinner e riabilita bottone
                if (spinner) spinner.classList.add('d-none');
                if (submitButton) submitButton.disabled = false;
            }
        });
    }
}

// Inizializzazione all'avvio
document.addEventListener('DOMContentLoaded', () => {
    RegistrationUI.initialize();
});

// Assicurati che ErrorHandler abbia un metodo showSuccess o crealo
if (typeof ErrorHandler.showSuccess !== 'function') {
    ErrorHandler.showSuccess = function(message, elementId = 'success-message') {
        const successElement = document.getElementById(elementId);
        if (successElement) {
            successElement.textContent = message;
            successElement.classList.remove('d-none'); // Assicurati che sia visibile
            successElement.classList.add('alert', 'alert-success'); // Assicurati che abbia le classi corrette
            successElement.style.display = 'block';
        } else {
            console.log('Success:', message); // Fallback se l'elemento non esiste
        }
    };
    // Aggiungi anche un metodo per nascondere il successo
    ErrorHandler.hideSuccess = function(elementId = 'success-message') {
        const successElement = document.getElementById(elementId);
        if (successElement) {
            successElement.style.display = 'none';
            successElement.classList.add('d-none');
        }
    };
}

// Assicurati che ApiError sia definito (potrebbe essere in api.js)
if (typeof ApiError === 'undefined') {
    class ApiError extends Error {
        constructor(message, status, data) {
            super(message);
            this.name = 'ApiError';
            this.status = status;
            this.data = data;
        }
    }
    window.ApiError = ApiError; 
}