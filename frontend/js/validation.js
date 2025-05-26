// Importa le costanti
import { VALIDATION } from './constants.js';

// Importa le utility
import { isValidEmail, isValidPassword, isValidUrl } from './utils.js';

// Funzioni di validazione per il form di registrazione
const ValidationRules = {
    email: {
        pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
        minLength: 5,
        maxLength: 254,
        validate: (email) => {
            if (!email) return { isValid: false, message: 'L\'email è obbligatoria' };
            if (email.length < ValidationRules.email.minLength) {
                return { isValid: false, message: 'L\'email è troppo corta' };
            }
            if (email.length > ValidationRules.email.maxLength) {
                return { isValid: false, message: 'L\'email è troppo lunga' };
            }
            if (!ValidationRules.email.pattern.test(email)) {
                return { isValid: false, message: 'Formato email non valido' };
            }
            if (email.startsWith('.') || email.endsWith('.')) {
                return { isValid: false, message: 'L\'email non può iniziare o finire con un punto' };
            }
            return { isValid: true };
        }
    },
    password: {
        minLength: 8,
        maxLength: 128,
        pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/,
        validate: (password) => {
            if (!password) return { isValid: false, message: 'La password è obbligatoria' };
            if (password.length < ValidationRules.password.minLength) {
                return { isValid: false, message: `La password deve contenere almeno ${ValidationRules.password.minLength} caratteri` };
            }
            if (password.length > ValidationRules.password.maxLength) {
                return { isValid: false, message: 'La password è troppo lunga' };
            }
            if (!ValidationRules.password.pattern.test(password)) {
                return { 
                    isValid: false, 
                    message: 'La password deve contenere almeno una lettera maiuscola, una minuscola, un numero e un carattere speciale'
                };
            }
            return { isValid: true };
        }
    },
    username: {
        minLength: 3,
        maxLength: 50,
        pattern: /^[a-zA-Z0-9_-]+$/,
        validate: (username) => {
            if (!username) return { isValid: false, message: 'Il nome utente è obbligatorio' };
            if (username.length < ValidationRules.username.minLength) {
                return { isValid: false, message: `Il nome utente deve contenere almeno ${ValidationRules.username.minLength} caratteri` };
            }
            if (username.length > ValidationRules.username.maxLength) {
                return { isValid: false, message: 'Il nome utente è troppo lungo' };
            }
            if (!ValidationRules.username.pattern.test(username)) {
                return { isValid: false, message: 'Il nome utente può contenere solo lettere, numeri, underscore e trattini' };
            }
            return { isValid: true };
        }
    }
};

// Funzione per validare tutti i campi del form
function validateForm(formData) {
    const errors = {};
    let isValid = true;

    // Valida email
    const emailResult = ValidationRules.email.validate(formData.get('email'));
    if (!emailResult.isValid) {
        errors.email = emailResult.message;
        isValid = false;
    }

    // Valida password
    const passwordResult = ValidationRules.password.validate(formData.get('password'));
    if (!passwordResult.isValid) {
        errors.password = passwordResult.message;
        isValid = false;
    }

    // Valida username
    const usernameResult = ValidationRules.username.validate(formData.get('username'));
    if (!usernameResult.isValid) {
        errors.username = usernameResult.message;
        isValid = false;
    }

    return { isValid, errors };
}

// Classe per la gestione delle validazioni
class ValidationManager {
    constructor() {
        this.errors = new Map();
    }

    // Validazione del form di login
    validateLoginForm(data) {
        this.errors.clear();
        
        if (!data.email) {
            this.errors.set('email', 'L\'email è obbligatoria');
        } else if (!isValidEmail(data.email)) {
            this.errors.set('email', 'Inserisci un\'email valida');
        }
        
        if (!data.password) {
            this.errors.set('password', 'La password è obbligatoria');
        }
        
        return this.errors.size === 0;
    }

    // Validazione del form di registrazione
    validateRegisterForm(data) {
        this.errors.clear();
        
        if (!data.name) {
            this.errors.set('name', 'Il nome è obbligatorio');
        } else if (data.name.length < VALIDATION.USERNAME_MIN_LENGTH) {
            this.errors.set('name', `Il nome deve contenere almeno ${VALIDATION.USERNAME_MIN_LENGTH} caratteri`);
        } else if (data.name.length > VALIDATION.USERNAME_MAX_LENGTH) {
            this.errors.set('name', `Il nome non può superare ${VALIDATION.USERNAME_MAX_LENGTH} caratteri`);
        }
        
        if (!data.email) {
            this.errors.set('email', 'L\'email è obbligatoria');
        } else if (!isValidEmail(data.email)) {
            this.errors.set('email', 'Inserisci un\'email valida');
        }
        
        if (!data.password) {
            this.errors.set('password', 'La password è obbligatoria');
        } else if (!isValidPassword(data.password)) {
            this.errors.set('password', 'La password deve contenere almeno 8 caratteri');
        }
        
        if (!data.confirmPassword) {
            this.errors.set('confirmPassword', 'Conferma la password');
        } else if (data.password !== data.confirmPassword) {
            this.errors.set('confirmPassword', 'Le password non coincidono');
        }
        
        return this.errors.size === 0;
    }

    // Validazione del form di creazione progetto
    validateProjectForm(data) {
        this.errors.clear();
        
        if (!data.title) {
            this.errors.set('title', 'Il titolo è obbligatorio');
        } else if (data.title.length < VALIDATION.PROJECT_TITLE_MIN_LENGTH) {
            this.errors.set('title', `Il titolo deve contenere almeno ${VALIDATION.PROJECT_TITLE_MIN_LENGTH} caratteri`);
        } else if (data.title.length > VALIDATION.PROJECT_TITLE_MAX_LENGTH) {
            this.errors.set('title', `Il titolo non può superare ${VALIDATION.PROJECT_TITLE_MAX_LENGTH} caratteri`);
        }
        
        if (!data.description) {
            this.errors.set('description', 'La descrizione è obbligatoria');
        } else if (data.description.length < VALIDATION.PROJECT_DESCRIPTION_MIN_LENGTH) {
            this.errors.set('description', `La descrizione deve contenere almeno ${VALIDATION.PROJECT_DESCRIPTION_MIN_LENGTH} caratteri`);
        } else if (data.description.length > VALIDATION.PROJECT_DESCRIPTION_MAX_LENGTH) {
            this.errors.set('description', `La descrizione non può superare ${VALIDATION.PROJECT_DESCRIPTION_MAX_LENGTH} caratteri`);
        }
        
        if (!data.targetAmount) {
            this.errors.set('targetAmount', 'L\'obiettivo di raccolta è obbligatorio');
        } else if (isNaN(data.targetAmount) || data.targetAmount <= 0) {
            this.errors.set('targetAmount', 'L\'obiettivo di raccolta deve essere un numero positivo');
        }
        
        if (!data.category) {
            this.errors.set('category', 'La categoria è obbligatoria');
        }
        
        if (!data.image) {
            this.errors.set('image', 'L\'immagine è obbligatoria');
        }
        
        return this.errors.size === 0;
    }

    // Validazione del form di modifica profilo
    validateProfileForm(data) {
        this.errors.clear();
        
        if (!data.name) {
            this.errors.set('name', 'Il nome è obbligatorio');
        } else if (data.name.length < VALIDATION.USERNAME_MIN_LENGTH) {
            this.errors.set('name', `Il nome deve contenere almeno ${VALIDATION.USERNAME_MIN_LENGTH} caratteri`);
        } else if (data.name.length > VALIDATION.USERNAME_MAX_LENGTH) {
            this.errors.set('name', `Il nome non può superare ${VALIDATION.USERNAME_MAX_LENGTH} caratteri`);
        }
        
        if (!data.email) {
            this.errors.set('email', 'L\'email è obbligatoria');
        } else if (!isValidEmail(data.email)) {
            this.errors.set('email', 'Inserisci un\'email valida');
        }
        
        if (data.website && !isValidUrl(data.website)) {
            this.errors.set('website', 'Inserisci un URL valido');
        }
        
        return this.errors.size === 0;
    }

    // Validazione del form di modifica password
    validatePasswordForm(data) {
        this.errors.clear();
        
        if (!data.currentPassword) {
            this.errors.set('currentPassword', 'La password attuale è obbligatoria');
        }
        
        if (!data.newPassword) {
            this.errors.set('newPassword', 'La nuova password è obbligatoria');
        } else if (!isValidPassword(data.newPassword)) {
            this.errors.set('newPassword', 'La password deve contenere almeno 8 caratteri');
        }
        
        if (!data.confirmPassword) {
            this.errors.set('confirmPassword', 'Conferma la nuova password');
        } else if (data.newPassword !== data.confirmPassword) {
            this.errors.set('confirmPassword', 'Le password non coincidono');
        }
        
        return this.errors.size === 0;
    }

    // Validazione del form di contatto
    validateContactForm(data) {
        this.errors.clear();
        
        if (!data.name) {
            this.errors.set('name', 'Il nome è obbligatorio');
        }
        
        if (!data.email) {
            this.errors.set('email', 'L\'email è obbligatoria');
        } else if (!isValidEmail(data.email)) {
            this.errors.set('email', 'Inserisci un\'email valida');
        }
        
        if (!data.message) {
            this.errors.set('message', 'Il messaggio è obbligatorio');
        } else if (data.message.length < 10) {
            this.errors.set('message', 'Il messaggio deve contenere almeno 10 caratteri');
        }
        
        return this.errors.size === 0;
    }

    // Ottiene gli errori
    getErrors() {
        return Object.fromEntries(this.errors);
    }

    // Ottiene l'errore per un campo specifico
    getError(field) {
        return this.errors.get(field);
    }

    // Verifica se ci sono errori
    hasErrors() {
        return this.errors.size > 0;
    }

    // Verifica se un campo specifico ha errori
    hasError(field) {
        return this.errors.has(field);
    }

    // Pulisce gli errori
    clearErrors() {
        this.errors.clear();
    }
}

// Crea un'istanza globale del gestore validazioni
const validationManager = new ValidationManager();

// Esporta le funzioni
export { ValidationRules, validateForm, validationManager, ValidationManager };