import { validateEmail, validatePassword, sanitizeInput } from './validation.js';
import { API } from './api.js'; // Assuming API class is needed for direct calls if Auth doesn't cover all cases
import { Auth, ErrorHandler } from './auth.js'; // Assuming ErrorHandler is in auth.js or needs to be imported

document.addEventListener('DOMContentLoaded', function() {
    // Inizializza gli elementi del form
    initializeLoginForm();
    
    // Se c'è un token di accesso memorizzato, reindirizza alla dashboard
    checkSavedAuth();
});

function initializeLoginForm() {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const isAdminCheckbox = document.getElementById('isAdmin');
    const securityCodeGroup = document.getElementById('securityCodeGroup');
    const securityCodeInput = document.getElementById('securityCode');
    const loginErrorElement = document.getElementById('loginError');

    if (!form || !emailInput || !passwordInput || !togglePassword || !isAdminCheckbox || !securityCodeGroup || !securityCodeInput || !loginErrorElement) {
        console.error('Elementi del form di login non trovati.');
        return;
    }

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        const icon = this.querySelector('i');
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });

    // Toggle security code field
    isAdminCheckbox.addEventListener('change', function() {
        securityCodeGroup.style.display = this.checked ? 'block' : 'none';
        securityCodeInput.required = this.checked;
        if (!this.checked) {
            securityCodeInput.value = '';
            securityCodeInput.classList.remove('is-invalid');
            securityCodeInput.classList.remove('is-valid');
        }
    });

    // Real-time validation
    emailInput.addEventListener('input', function() {
        validateInput(this, validateEmail);
    });

    passwordInput.addEventListener('input', function() {
        // Basic length check for real-time feedback, full validation on submit
        validateInput(this, (val) => val.length >= 8);
    });
    
    securityCodeInput.addEventListener('input', function() {
        if (isAdminCheckbox.checked) {
            // Basic check, assuming security code needs specific validation
            validateInput(this, (val) => val.length > 0); 
        }
    });

    // Handle form submission
    form.addEventListener('submit', handleSubmit);
}

function validateInput(inputElement, validationFn) {
    const sanitizedValue = sanitizeInput(inputElement.value);
    if (validationFn(sanitizedValue)) {
        inputElement.classList.remove('is-invalid');
        inputElement.classList.add('is-valid');
        return true;
    } else {
        inputElement.classList.remove('is-valid');
        inputElement.classList.add('is-invalid');
        return false;
    }
}

async function handleSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const rememberMeCheckbox = document.getElementById('rememberMe');
    const isAdminCheckbox = document.getElementById('isAdmin');
    const securityCodeInput = document.getElementById('securityCode');
    const loginSpinner = document.getElementById('loginSpinner');
    const loginErrorElement = document.getElementById('loginError');

    // Reset previous errors
    ErrorHandler.hideError('loginError');
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    // Validate form fields before submitting
    let isValid = true;
    if (!validateInput(emailInput, validateEmail)) isValid = false;
    // Use a more specific password validation if needed, e.g., from validation.js
    if (!validateInput(passwordInput, (val) => val.length >= 8)) isValid = false; 
    if (isAdminCheckbox.checked && !validateInput(securityCodeInput, (val) => val && val.length >= 6)) isValid = false;

    if (!isValid) {
        ErrorHandler.showError('Compila correttamente tutti i campi richiesti.', 'loginError');
        return;
    }

    // Show spinner
    loginSpinner.classList.remove('d-none');
    form.querySelector('button[type="submit"]').disabled = true;

    const email = sanitizeInput(emailInput.value);
    const password = passwordInput.value; // Do not sanitize password
    const rememberMe = rememberMeCheckbox.checked;
    const isAdmin = isAdminCheckbox.checked;
    const securityCode = isAdmin ? sanitizeInput(securityCodeInput.value) : null;

    try {
        // Use Auth class for login
        const response = await Auth.login(email, password, isAdmin, securityCode);
        
        // Handle remember me functionality (if needed, Auth.login might handle token storage)
        if (rememberMe) {
            // Potentially store token differently or set longer expiry if API supports it
            // localStorage.setItem('rememberMe', 'true'); // Example
        }

        // Redirect based on role
        const user = Auth.getUser();
        if (user && user.role === 'admin') {
            window.location.href = 'admin.html'; // Redirect admin
        } else {
            window.location.href = 'projects.html'; // Redirect regular user
        }

    } catch (error) {
        console.error('Login failed:', error);
        let errorMessage = 'Errore durante il login. Riprova.';
        if (error instanceof ApiError) {
            errorMessage = error.message;
        } else if (error.message) {
            errorMessage = error.message;
        }
        ErrorHandler.showError(errorMessage, 'loginError');
    } finally {
        // Hide spinner and re-enable button
        loginSpinner.classList.add('d-none');
        form.querySelector('button[type="submit"]').disabled = false;
    }
}

function checkSavedAuth() {
    if (Auth.isAuthenticated()) {
        console.log('Utente già autenticato, reindirizzamento...');
        // Redirect based on role
        const user = Auth.getUser();
        if (user && user.role === 'admin') {
             window.location.href = 'admin.html';
        } else {
             window.location.href = 'projects.html'; // Or dashboard/index
        }
    }
}

// Funzione per gestire il login social (placeholder)
function socialLogin(provider) {
    console.log(`Tentativo di login con ${provider}`);
    // Implementare la logica di login social qui
    // Esempio: window.location.href = `/server/auth/login_${provider}.php`;
    ErrorHandler.showError(`Login con ${provider} non ancora implementato.`, 'loginError');
}

// Make socialLogin globally accessible if called directly from HTML onclick
window.socialLogin = socialLogin;

// Placeholder for ApiError if not defined elsewhere
// Ensure ApiError is defined, possibly import from api.js or define globally
if (typeof ApiError === 'undefined') {
    class ApiError extends Error {
        constructor(message, status, data) {
            super(message);
            this.name = 'ApiError';
            this.status = status;
            this.data = data;
        }
    }
    window.ApiError = ApiError; // Make it global if needed by other scripts not using modules
}