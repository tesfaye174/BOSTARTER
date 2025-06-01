/**
 * Enhanced Authentication System for BOSTARTER
 * Handles login, registration, and authentication state management
 */

document.addEventListener('DOMContentLoaded', function () {
    initializeAuthSystem();
});

function initializeAuthSystem() {
    // Check authentication status
    checkAuthStatus();

    // Setup event listeners
    setupModalEventListeners();
    setupFormEventListeners();
    setupPasswordToggleListeners();
    setupPasswordValidation();
    setupCreatorLinkHandler();
}

// Authentication status check
async function checkAuthStatus() {
    try {
        const formData = new FormData();
        formData.append('action', 'check_auth');

        const response = await fetch('/backend/auth_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success && data.authenticated) {
            updateUIForLoggedInUser(data.user);
        }
    } catch (error) {
        console.log('Authentication check failed:', error);
    }
}

// Modal event listeners
function setupModalEventListeners() {
    // Open login modal
    const loginLink = document.getElementById('login-link');
    if (loginLink) {
        loginLink.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('login-modal');
        });
    }

    // Open register modal
    const registerLink = document.getElementById('register-link');
    if (registerLink) {
        registerLink.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('register-modal');
        });
    }

    // Switch between modals
    const showRegisterBtn = document.getElementById('show-register-modal');
    if (showRegisterBtn) {
        showRegisterBtn.addEventListener('click', () => {
            switchModal('login-modal', 'register-modal');
        });
    }

    const showLoginBtn = document.getElementById('show-login-modal');
    if (showLoginBtn) {
        showLoginBtn.addEventListener('click', () => {
            switchModal('register-modal', 'login-modal');
        });
    }

    // Close modal buttons
    const closeLoginBtn = document.getElementById('close-login-modal');
    if (closeLoginBtn) {
        closeLoginBtn.addEventListener('click', () => closeModal('login-modal'));
    }

    const closeRegisterBtn = document.getElementById('close-register-modal');
    if (closeRegisterBtn) {
        closeRegisterBtn.addEventListener('click', () => closeModal('register-modal'));
    }

    // Close modals when clicking outside
    document.addEventListener('click', (e) => {
        if (e.target.id === 'login-modal') {
            closeModal('login-modal');
        }
        if (e.target.id === 'register-modal') {
            closeModal('register-modal');
        }
    });
}

// Form event listeners
function setupFormEventListeners() {
    // Login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Register form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
}

// Password toggle functionality
function setupPasswordToggleListeners() {
    // Login password toggle
    const loginPasswordToggle = document.getElementById('toggle-login-password');
    if (loginPasswordToggle) {
        loginPasswordToggle.addEventListener('click', () => {
            togglePasswordVisibility('login-password', loginPasswordToggle);
        });
    }

    // Register password toggle
    const registerPasswordToggle = document.getElementById('toggle-register-password');
    if (registerPasswordToggle) {
        registerPasswordToggle.addEventListener('click', () => {
            togglePasswordVisibility('register-password', registerPasswordToggle);
        });
    }

    // Register confirm password toggle
    const confirmPasswordToggle = document.getElementById('toggle-register-confirm-password');
    if (confirmPasswordToggle) {
        confirmPasswordToggle.addEventListener('click', () => {
            togglePasswordVisibility('register-confirm-password', confirmPasswordToggle);
        });
    }
}

// Password validation
function setupPasswordValidation() {
    const passwordInput = document.getElementById('register-password');
    const confirmPasswordInput = document.getElementById('register-confirm-password');

    if (passwordInput) {
        passwordInput.addEventListener('input', () => {
            validatePasswordStrength(passwordInput.value);
        });
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', () => {
            validatePasswordMatch();
        });
    }

    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', validatePasswordMatch);
    }
}

// Handle login
async function handleLogin(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = document.getElementById('login-submit-btn');
    const btnText = document.getElementById('login-btn-text');
    const btnIcon = document.getElementById('login-btn-icon');
    const spinner = document.getElementById('login-spinner');

    // Clear previous errors
    hideError('login-error');

    // Get form data
    const formData = new FormData(form);
    formData.append('action', 'login');

    // Update button state
    setButtonLoading(submitBtn, btnText, btnIcon, spinner, 'Accesso in corso...');

    try {
        const response = await fetch('/backend/auth_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Accesso effettuato con successo!', 'success');

            // Redirect based on user type
            setTimeout(() => {
                if (data.redirect === 'creatori_dashboard') {
                    window.location.href = '/frontend/dashboard.html';
                } else {
                    window.location.href = '/frontend/dashboard.html';
                }
            }, 1000);
        } else {
            showError('login-error', data.message || 'Errore durante l\'accesso');
        }
    } catch (error) {
        console.error('Login error:', error);
        showError('login-error', 'Errore di connessione. Riprova più tardi.');
    } finally {
        resetButtonState(submitBtn, btnText, btnIcon, spinner, 'Accedi');
    }
}

// Handle registration
async function handleRegister(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = document.getElementById('register-submit-btn');
    const btnText = document.getElementById('register-btn-text');
    const btnIcon = document.getElementById('register-btn-icon');
    const spinner = document.getElementById('register-spinner');

    // Clear previous errors
    hideError('register-error');

    // Validate form
    if (!validateRegistrationForm(form)) {
        return;
    }

    // Get form data
    const formData = new FormData(form);
    formData.append('action', 'register');

    // Update button state
    setButtonLoading(submitBtn, btnText, btnIcon, spinner, 'Registrazione in corso...');

    try {
        const response = await fetch('/backend/auth_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Registrazione completata con successo!', 'success');

            // Redirect based on user type
            setTimeout(() => {
                if (data.redirect === 'creatori_dashboard') {
                    window.location.href = '/frontend/dashboard.html';
                } else {
                    window.location.href = '/frontend/dashboard.html';
                }
            }, 1500);
        } else {
            showError('register-error', data.message || 'Errore durante la registrazione');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showError('register-error', 'Errore di connessione. Riprova più tardi.');
    } finally {
        resetButtonState(submitBtn, btnText, btnIcon, spinner, 'Crea Account');
    }
}

// Validate registration form
function validateRegistrationForm(form) {
    let isValid = true;

    // Get form fields
    const nome = form.querySelector('[name="nome"]').value.trim();
    const cognome = form.querySelector('[name="cognome"]').value.trim();
    const nickname = form.querySelector('[name="nickname"]').value.trim();
    const email = form.querySelector('[name="email"]').value.trim();
    const annoNascita = parseInt(form.querySelector('[name="anno_nascita"]').value);
    const luogoNascita = form.querySelector('[name="luogo_nascita"]').value.trim();
    const tipoUtente = form.querySelector('[name="tipo_utente"]').value;
    const password = form.querySelector('[name="password"]').value;
    const confirmPassword = form.querySelector('[name="confirm_password"]').value;

    // Validate required fields
    if (!nome) {
        showFieldError('[name="nome"]', 'Il nome è obbligatorio');
        isValid = false;
    }

    if (!cognome) {
        showFieldError('[name="cognome"]', 'Il cognome è obbligatorio');
        isValid = false;
    }

    if (!nickname || nickname.length < 3 || nickname.length > 20) {
        showFieldError('[name="nickname"]', 'Il nickname deve essere tra 3 e 20 caratteri');
        isValid = false;
    }

    if (!email || !isValidEmail(email)) {
        showFieldError('[name="email"]', 'Inserisci un email valida');
        isValid = false;
    }

    if (!annoNascita || annoNascita < 1900 || annoNascita > new Date().getFullYear()) {
        showFieldError('[name="anno_nascita"]', 'Anno di nascita non valido');
        isValid = false;
    }

    if (!luogoNascita) {
        showFieldError('[name="luogo_nascita"]', 'Il luogo di nascita è obbligatorio');
        isValid = false;
    }

    if (!tipoUtente) {
        showFieldError('[name="tipo_utente"]', 'Seleziona il tipo di utente');
        isValid = false;
    }

    if (!isValidPassword(password)) {
        showFieldError('[name="password"]', 'Password non conforme ai requisiti');
        isValid = false;
    }

    if (password !== confirmPassword) {
        showFieldError('[name="confirm_password"]', 'Le password non corrispondono');
        isValid = false;
    }

    return isValid;
}

// Password strength validation
function validatePasswordStrength(password) {
    const lengthCheck = document.getElementById('length-check');
    const uppercaseCheck = document.getElementById('uppercase-check');
    const numberCheck = document.getElementById('number-check');

    // Length check
    if (password.length >= 8) {
        updateCheckStatus(lengthCheck, true);
    } else {
        updateCheckStatus(lengthCheck, false);
    }

    // Uppercase check
    if (/[A-Z]/.test(password)) {
        updateCheckStatus(uppercaseCheck, true);
    } else {
        updateCheckStatus(uppercaseCheck, false);
    }

    // Number check
    if (/[0-9]/.test(password)) {
        updateCheckStatus(numberCheck, true);
    } else {
        updateCheckStatus(numberCheck, false);
    }
}

// Password match validation
function validatePasswordMatch() {
    const password = document.getElementById('register-password').value;
    const confirmPassword = document.getElementById('register-confirm-password').value;
    const matchIndicator = document.getElementById('password-match');

    if (confirmPassword && password !== confirmPassword) {
        matchIndicator.classList.remove('hidden');
        matchIndicator.querySelector('span').textContent = 'Le password non corrispondono';
        matchIndicator.querySelector('i').className = 'ri-close-circle-line mr-1';
        matchIndicator.className = matchIndicator.className.replace('text-green-500', 'text-red-500');
    } else if (confirmPassword && password === confirmPassword) {
        matchIndicator.classList.remove('hidden');
        matchIndicator.querySelector('span').textContent = 'Le password corrispondono';
        matchIndicator.querySelector('i').className = 'ri-check-circle-line mr-1';
        matchIndicator.className = matchIndicator.className.replace('text-red-500', 'text-green-500');
    } else {
        matchIndicator.classList.add('hidden');
    }
}

// Utility functions
function togglePasswordVisibility(inputId, toggleBtn) {
    const input = document.getElementById(inputId);
    const icon = toggleBtn.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ri-eye-off-line';
    } else {
        input.type = 'password';
        icon.className = 'ri-eye-line';
    }
}

function updateCheckStatus(element, isValid) {
    const icon = element.querySelector('i');
    const text = element.querySelector('span');

    if (isValid) {
        icon.className = 'ri-check-circle-line mr-1';
        element.className = element.className.replace('text-gray-500', 'text-green-500');
    } else {
        icon.className = 'ri-close-circle-line mr-1';
        element.className = element.className.replace('text-green-500', 'text-gray-500');
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPassword(password) {
    return password.length >= 8 && /[A-Z]/.test(password) && /[0-9]/.test(password);
}

function setButtonLoading(button, textElement, iconElement, spinner, loadingText) {
    button.disabled = true;
    textElement.textContent = loadingText;
    iconElement.classList.add('hidden');
    spinner.classList.remove('hidden');
}

function resetButtonState(button, textElement, iconElement, spinner, originalText) {
    button.disabled = false;
    textElement.textContent = originalText;
    iconElement.classList.remove('hidden');
    spinner.classList.add('hidden');
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden', 'opacity-0');
        modal.classList.add('opacity-100');
        const content = modal.querySelector('[id$="-modal-content"]');
        if (content) {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('opacity-0');
        modal.classList.remove('opacity-100');
        const content = modal.querySelector('[id$="-modal-content"]');
        if (content) {
            content.classList.add('scale-95');
            content.classList.remove('scale-100');
        }

        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 300);
    }
    clearAllErrors();
}

function switchModal(fromModalId, toModalId) {
    closeModal(fromModalId);
    setTimeout(() => openModal(toModalId), 300);
}

function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        const messageElement = errorElement.querySelector('span') || errorElement;
        messageElement.textContent = message;
        errorElement.classList.remove('hidden');
    }
}

function hideError(elementId) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.classList.add('hidden');
    }
}

function showFieldError(selector, message) {
    const field = document.querySelector(selector);
    if (field) {
        field.classList.add('border-red-500');

        // Create or update error message
        let errorMsg = field.parentNode.querySelector('.field-error');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'field-error text-red-500 text-xs mt-1';
            field.parentNode.appendChild(errorMsg);
        }
        errorMsg.textContent = message;

        // Remove error on input
        field.addEventListener('input', function removeError() {
            field.classList.remove('border-red-500');
            if (errorMsg) errorMsg.remove();
            field.removeEventListener('input', removeError);
        }, { once: true });
    }
}

function clearAllErrors() {
    // Clear form field errors
    document.querySelectorAll('.border-red-500').forEach(field => {
        field.classList.remove('border-red-500');
    });

    document.querySelectorAll('.field-error').forEach(error => {
        error.remove();
    });

    // Clear modal errors
    hideError('login-error');
    hideError('register-error');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';

    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="ri-${type === 'success' ? 'check' : type === 'error' ? 'error-warning' : 'info'}-line mr-2"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function updateUIForLoggedInUser(user) {
    // Update navigation for logged in user
    const loginLink = document.getElementById('login-link');
    const registerLink = document.getElementById('register-link');
    const userDropdown = document.getElementById('user-dropdown');

    if (loginLink) loginLink.style.display = 'none';
    if (registerLink) registerLink.style.display = 'none';
    if (userDropdown) userDropdown.classList.remove('hidden');
}

function setupCreatorLinkHandler() {
    const creatorLink = document.getElementById('creator-link');
    if (creatorLink) {
        creatorLink.addEventListener('click', async (e) => {
            e.preventDefault();

            try {
                const formData = new FormData();
                formData.append('action', 'check_auth');

                const response = await fetch('/backend/auth_api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success && data.authenticated) {
                    if (data.user?.tipo_utente === 'creatore') {
                        window.location.href = '/frontend/dashboard.html';
                    } else {
                        showNotification('Accesso riservato ai creatori. Registrati come creatore per continuare.', 'error');
                        openModal('register-modal');
                        // Pre-select creator type
                        setTimeout(() => {
                            const userTypeSelect = document.getElementById('register-user-type');
                            if (userTypeSelect) userTypeSelect.value = 'creatore';
                        }, 100);
                    }
                } else {
                    showNotification('Effettua l\'accesso per continuare', 'error');
                    openModal('login-modal');
                }
            } catch (error) {
                console.error('Creator link error:', error);
                showNotification('Effettua l\'accesso per continuare', 'error');
                openModal('login-modal');
            }
        });
    }
}

// Logout function
async function handleLogout() {
    try {
        const formData = new FormData();
        formData.append('action', 'logout');

        const response = await fetch('/backend/auth_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Logout effettuato con successo', 'success');
            setTimeout(() => {
                window.location.href = '/index.html';
            }, 1000);
        }
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = '/index.html';
    }
}

// Make logout function globally available
window.handleLogout = handleLogout;