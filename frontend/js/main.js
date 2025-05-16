// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // Initialize mobile menu
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (isValid) {
                // Add form submission logic here
                console.log('Form submitted successfully');
            }
        });
    });

    // Initialize plugins
    initializePlugins();

    // Dynamic content loading
    const loadMoreBtn = document.querySelector('.load-more');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('/api/content');
                const data = await response.json();
                // Handle the loaded content
                console.log('Content loaded:', data);
            } catch (error) {
                console.error('Error loading content:', error);
            }
        });
    }
});

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Window resize handler with debounce
window.addEventListener('resize', debounce(() => {
    // Handle resize events
    console.log('Window resized');
}, 250));

// Initialize any third-party libraries or plugins
function initializePlugins() {
    // Add initialization code for external libraries here
    console.log('Plugins initialized');
}

// Call plugin initialization
initializePlugins();

// --- Miglioramento gestione autenticazione e modali ---
// Gestione modali login/register e autenticazione
const loginLink = document.getElementById('login-link');
const registerLink = document.getElementById('register-link');
const logoutLink = document.getElementById('logout-link');
const userGreeting = document.getElementById('user-greeting');
const loginModal = document.getElementById('login-modal');
const registerModal = document.getElementById('register-modal');
const closeModalBtns = document.querySelectorAll('.close-modal');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const goToRegister = document.getElementById('go-to-register');
const goToLogin = document.getElementById('go-to-login');

function showModal(modal) {
    modal.setAttribute('aria-hidden', 'false');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('open'), 10);
}
function hideModal(modal) {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    modal.style.display = 'none';
}
if (loginLink) loginLink.addEventListener('click', e => { e.preventDefault(); showModal(loginModal); });
if (registerLink) registerLink.addEventListener('click', e => { e.preventDefault(); showModal(registerModal); });
if (closeModalBtns) closeModalBtns.forEach(btn => btn.addEventListener('click', e => {
    hideModal(btn.closest('.modal'));
}));
window.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (loginModal && loginModal.classList.contains('open')) hideModal(loginModal);
        if (registerModal && registerModal.classList.contains('open')) hideModal(registerModal);
    }
});
if (goToRegister) goToRegister.addEventListener('click', e => { e.preventDefault(); hideModal(loginModal); showModal(registerModal); });
if (goToLogin) goToLogin.addEventListener('click', e => { e.preventDefault(); hideModal(registerModal); showModal(loginModal); });

// Mobile login/register links
const loginLinkMobile = document.getElementById('login-link-mobile');
if (loginLinkMobile) loginLinkMobile.addEventListener('click', e => { e.preventDefault(); hideModal(registerModal); showModal(loginModal); });
const registerLinkMobile = document.getElementById('register-link-mobile');
if (registerLinkMobile) registerLinkMobile.addEventListener('click', e => { e.preventDefault(); hideModal(loginModal); showModal(registerModal); });

// Simulazione database utenti (solo per demo frontend)
let utentiFake = JSON.parse(localStorage.getItem('utentiFake') || '[]');
function salvaUtentiFake() {
    localStorage.setItem('utentiFake', JSON.stringify(utentiFake));
}
function trovaUtente(email, password) {
    return utentiFake.find(u => u.email === email && u.password === password);
}
function trovaUtentePerEmail(email) {
    return utentiFake.find(u => u.email === email);
}

// LOGIN
if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const email = loginForm.email.value.trim();
        const password = loginForm.password.value;
        const errorMsg = document.getElementById('login-error');
        errorMsg.textContent = '';
        if (!email || !password) {
            errorMsg.textContent = 'Compila tutti i campi.';
            return;
        }
        const utente = trovaUtente(email, password);
        if (utente) {
            // Login riuscito
            hideModal(loginModal);
            localStorage.setItem('utenteLoggato', JSON.stringify(utente));
            if (utente.ruolo === 'creatore') {
                window.location.href = 'frontend/creatori_dashboard.html';
            } else {
                window.location.href = 'frontend/dashboard.html';
            }
        } else {
            errorMsg.textContent = 'Credenziali non valide.';
        }
    });
}
// REGISTRAZIONE
if (registerForm) {
    registerForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const email = registerForm.email.value.trim();
        const nickname = registerForm.nickname.value.trim();
        const password = registerForm.password.value;
        const isCreatore = registerForm.querySelector('[name="is_creatore"]') ? registerForm.querySelector('[name="is_creatore"]').checked : false;
        const errorMsg = document.getElementById('register-error');
        const successMsg = document.getElementById('register-success');
        errorMsg.textContent = '';
        successMsg.textContent = '';
        if (!email || !nickname || !password) {
            errorMsg.textContent = 'Compila tutti i campi.';
            return;
        }
        if (trovaUtentePerEmail(email)) {
            errorMsg.textContent = 'Email già registrata.';
            return;
        }
        // Salva nuovo utente
        const nuovoUtente = { email, nickname, password, ruolo: isCreatore ? 'creatore' : 'utente' };
        utentiFake.push(nuovoUtente);
        salvaUtentiFake();
        successMsg.textContent = 'Registrazione avvenuta con successo!';
        setTimeout(() => {
            hideModal(registerModal);
            localStorage.setItem('utenteLoggato', JSON.stringify(nuovoUtente));
            if (isCreatore) {
                window.location.href = 'frontend/creatori_dashboard.html';
            } else {
                window.location.href = 'frontend/dashboard.html';
            }
        }, 800);
    });
}
// LOGOUT
if (logoutLink) {
    logoutLink.addEventListener('click', e => {
        e.preventDefault();
        localStorage.removeItem('utenteLoggato');
        window.location.href = 'index.html';
    });
}
// Mostra/nascondi link in base allo stato login
function aggiornaNavLogin() {
    const utente = JSON.parse(localStorage.getItem('utenteLoggato') || 'null');
    if (utente) {
        if (loginLink) loginLink.classList.add('hidden-by-default');
        if (registerLink) registerLink.classList.add('hidden-by-default');
        if (logoutLink) logoutLink.classList.remove('hidden-by-default');
        if (userGreeting) {
            userGreeting.classList.remove('hidden-by-default');
            userGreeting.textContent = 'Ciao, ' + (utente.nickname || utente.email);
        }
    } else {
        if (loginLink) loginLink.classList.remove('hidden-by-default');
        if (registerLink) registerLink.classList.remove('hidden-by-default');
        if (logoutLink) logoutLink.classList.add('hidden-by-default');
        if (userGreeting) {
            userGreeting.classList.add('hidden-by-default');
            userGreeting.textContent = '';
        }
    }
}
document.addEventListener('DOMContentLoaded', aggiornaNavLogin);
