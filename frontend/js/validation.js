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

// Esporta le funzioni
export { ValidationRules, validateForm };