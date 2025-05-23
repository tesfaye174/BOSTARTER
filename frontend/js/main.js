// Funzionalità homepage BOSTARTER

// Configurazione CSRF (gestita da ApiService)
let CSRF_TOKEN = null; // Mantenuto per compatibilità con addCSRFTokenToForm se necessario, ma ApiService gestisce l'header X-CSRF-Token

// Funzione per recuperare il token CSRF dal backend (ora gestita internamente da ApiService o chiamata prima delle richieste)
// async function fetchCSRFToken() { ... } // Commentata o rimossa se ApiService la gestisce
}

// Funzione per aggiungere token CSRF a tutte le richieste (non più usata per fetch API, ma utile per form tradizionali)
function addCSRFTokenToForm(formElement) {
    if (CSRF_TOKEN) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'csrf_token';
        tokenInput.value = CSRF_TOKEN;
        formElement.appendChild(tokenInput);
    } else {
        console.warn('CSRF Token not available to add to form.');
    }
}

// Rate Limiting per le form
const rateLimitStore = new Map();
const RATE_LIMIT = {
    maxAttempts: 5,
    timeWindow: 300000 // 5 minuti in millisecondi
};

function checkRateLimit(actionType) {
    const now = Date.now();
    const userKey = `${actionType}_${now}`;
    
    if (!rateLimitStore.has(actionType)) {
        rateLimitStore.set(actionType, []);
    }
    
    const attempts = rateLimitStore.get(actionType);
    const validAttempts = attempts.filter(timestamp => now - timestamp < RATE_LIMIT.timeWindow);
    
    rateLimitStore.set(actionType, validAttempts);
    
    if (validAttempts.length >= RATE_LIMIT.maxAttempts) {
        return false;
    }
    
    validAttempts.push(now);
    return true;
}

document.addEventListener('DOMContentLoaded', async function () {
    // Recupera il token CSRF all'avvio tramite ApiService (se necessario, altrimenti ApiService lo gestisce internamente)
    // await fetchCSRFToken(); // Rimosso o gestito diversamente se ApiService lo fa

    // Controlla se esiste una sessione utente attiva e aggiorna l'UI
    try {
        const sessionData = await ApiService.request(API_CONFIG.ENDPOINTS.AUTH.CHECK_SESSION);

        if (sessionData.isLoggedIn) {
             // Aggiorna la sessione locale con i dati dal backend
             SessionManager.setSession(sessionData.user);
             // Aggiorna UI per utente loggato
             updateUIForLoggedInUser(sessionData.user);
         } else {
             // Assicurati che la sessione locale sia pulita se il backend dice che non è loggato
             SessionManager.clearSession();
             updateUIForLoggedOutUser();
         }
     } catch (error) {
         console.error('Errore durante il controllo sessione:', error);
         // In caso di errore, assumi che l'utente non sia loggato e pulisci la sessione locale
         SessionManager.clearSession();
         updateUIForLoggedOutUser();
     }

    // Funzione per aggiornare l'UI quando l'utente è loggato
    function updateUIForLoggedInUser(user) {
        const loginButtons = document.querySelectorAll('[data-modal="login-modal"], [data-modal="register-modal"]');
        loginButtons.forEach(btn => btn.style.display = 'none');
        
        const userMenuContainer = document.querySelector('.user-menu');
        if (userMenuContainer) {
            userMenuContainer.innerHTML = `
                <span class="user-nickname">Ciao, ${user?.nickname}</span>
                <button id="logout-button" class="btn btn-outline">Logout</button>
            `;
        }
    }

    // Funzione per aggiornare l'UI quando l'utente non è loggato
    function updateUIForLoggedOutUser() {
        const loginButtons = document.querySelectorAll('[data-modal="login-modal"], [data-modal="register-modal"]');
        loginButtons.forEach(btn => btn.style.display = ''); // Mostra i pulsanti di login/register
        
        const userMenuContainer = document.querySelector('.user-menu');
        if (userMenuContainer) {
            userMenuContainer.innerHTML = ''; // Rimuovi il menu utente
        }
    }

    // Gestione logout
    document.addEventListener('click', async function(e) {
        if (e.target && ['logout-button', 'logout-link', 'mobile-logout-link'].includes(e.target.id)) {
            e.preventDefault();
            // Utilizza l'API di logout nel backend
            try {
                await ApiService.logout();
                SessionManager.clearSession();
                window.location.href = '/BOSTARTER/frontend/index.html';
            } catch (error) {
                console.error('Errore durante il logout:', error);
                // In caso di errore API, procedi comunque con il logout lato client
                SessionManager.clearSession();
                window.location.href = '/BOSTARTER/frontend/index.html';
            }
        }
    });

    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function () {
            const expanded = mobileMenuBtn.getAttribute('aria-expanded') === 'true';
            mobileMenuBtn.setAttribute('aria-expanded', !expanded);
            mobileMenu.setAttribute('aria-hidden', expanded);
            mobileMenu.classList.toggle('open');
        });
        // Chiudi menu al click fuori
        document.addEventListener('click', function (e) {
            if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
                mobileMenu.setAttribute('aria-hidden', 'true');
                mobileMenu.classList.remove('open');
            }
        });
    }

    // Animazione scroll per "Come funziona"
    const steps = document.querySelectorAll('.step-card');
    if (steps.length) {
        const revealSteps = () => {
            steps.forEach(step => {
                const rect = step.getBoundingClientRect();
                if (rect.top < window.innerHeight - 50) {
                    step.classList.add('visible');
                }
            });
        };
        window.addEventListener('scroll', revealSteps);
        revealSteps();
    }

    // Aggiorna anno footer
    const yearSpan = document.getElementById('current-year');
    if (yearSpan) {
        yearSpan.textContent = new Date().getFullYear();
    }

    // Placeholder: caricamento dinamico progetti in evidenza
    const featuredProjects = document.getElementById('featured-projects-list');
    if (featuredProjects) {
        // Simulazione caricamento
        featuredProjects.innerHTML = '<div class="project-card">Progetto 1</div><div class="project-card">Progetto 2</div><div class="project-card">Progetto 3</div>';
    }

    // Placeholder: caricamento statistiche
    const creatorsList = document.getElementById('stat-creators-list');
    const projectsList = document.getElementById('stat-projects-list');
    const fundersList = document.getElementById('stat-funders-list');
    if (creatorsList) creatorsList.innerHTML = '<li>Mario Rossi</li><li>Anna Bianchi</li>';
    if (projectsList) projectsList.innerHTML = '<li>EcoLampada (95%)</li><li>Libro Illustrato (90%)</li>';
    if (fundersList) fundersList.innerHTML = '<li>Giulia Verdi</li><li>Luca Neri</li>';

    // Gestione modali login/register (apertura/chiusura)
    document.querySelectorAll('[data-modal]').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const modalId = link.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                modal.classList.add('open');
                // Focus automatico sul primo input abilitato
                const firstInput = modal.querySelector('input:not([type=hidden]):not([disabled]),button,select,textarea');
                if(firstInput) firstInput.focus();
                // Reset messaggi feedback e classi di errore
                const errorMsg = modal.querySelector('.error-message');
                const successMsg = modal.querySelector('.success-message');
                const formGroups = modal.querySelectorAll('.form-group');
                formGroups.forEach(group => group.classList.remove('error', 'success'));
                if(errorMsg) errorMsg.textContent = '';
                if(successMsg) successMsg.textContent = '';
                // Gestione accessibilità
                const closeBtn = modal.querySelector('.close-modal');
                if(closeBtn) {
                    closeBtn.setAttribute('aria-label', 'Chiudi modale');
                    closeBtn.focus();
                }
            }
        });
    });

    // Funzione per mostrare errori nei form
    function showFormError(input, message) {
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            formGroup.classList.add('error');
            formGroup.classList.add('shake-error');
            const errorElement = formGroup.querySelector('.error-message');
            if (errorElement) {
                errorElement.textContent = message;
            }
            // Rimuovi l'animazione di shake dopo che è completata
            setTimeout(() => {
                formGroup.classList.remove('shake-error');
            }, 400);
        }
    }

    // Funzione per gestire lo stato di caricamento dei pulsanti
    function setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.classList.add('btn-loading');
            button.setAttribute('aria-busy', 'true');
            const spinner = document.createElement('div');
            spinner.className = 'loading-spinner';
            button.appendChild(spinner);
        } else {
            button.classList.remove('btn-loading');
            button.setAttribute('aria-busy', 'false');
            const spinner = button.querySelector('.loading-spinner');
            if (spinner) spinner.remove();
        }
    }
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = btn.closest('.modal');
            if (modal) {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('open');
            }
        });
    });
    // Chiudi modale con ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.open').forEach(modal => {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('open');
            });
        }
    });

    // Gestione errori form e feedback visivi
    function showFormError(formElement, message) {
        const errorDiv = formElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            errorDiv.setAttribute('aria-hidden', 'false');
            
            // Animazione di shake per feedback visivo
            errorDiv.classList.add('shake-animation');
            setTimeout(() => errorDiv.classList.remove('shake-animation'), 500);
        }
    }

    function showFormSuccess(formElement, message) {
        const successDiv = formElement.querySelector('.success-message');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            successDiv.setAttribute('aria-hidden', 'false');
        }
    }

    // Validazione email
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Validazione password con requisiti più stringenti
    function validatePassword(password) {
        const minLength = 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        
        return password.length >= minLength && 
               hasUpperCase && 
               hasLowerCase && 
               hasNumbers && 
               hasSpecialChar;
    }

    // Gestione form di login con rate limiting e CSRF
    const loginForm = document.getElementById('login-form');
    if(loginForm) {
        addCSRFToken(loginForm);
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!checkRateLimit('login')) {
                showFormError(loginForm, 'Troppi tentativi. Riprova tra qualche minuto.');
                return;
            }
            
            const email = loginForm.querySelector('#login-email').value.trim();
            const password = loginForm.querySelector('#login-password').value.trim();
            const errorMsg = loginForm.querySelector('#login-error');
            const successMsg = loginForm.querySelector('#login-success');
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            
            // Validazione campi
            if(!email || !password) {
                showFormError(loginForm, 'Inserisci email e password.');
                return;
            }
            
            if(!validateEmail(email)) {
                showFormError(loginForm, 'Inserisci un indirizzo email valido.');
                return;
            }
            
            // La validazione della password con requisiti più stringenti è gestita lato backend per sicurezza
            // Qui possiamo fare una validazione base sulla lunghezza se necessario, ma la validazione complessa è meglio server-side.
            // if(!validatePassword(password)) {
            //     showFormError(loginForm, 'La password non soddisfa i requisiti minimi.');
            //     return;
            }
            
            // Disabilita il pulsante e mostra caricamento
            setButtonLoading(submitBtn, true);
            errorMsg.textContent = ''; // Pulisce messaggi precedenti
            successMsg.textContent = '';
            
            // Chiamata API per il login
            try {
                // Utilizza la funzione ApiService per il login (CSRF token gestito internamente da ApiService)
                const data = await ApiService.login({ email, password });

                if (data.status === 'success') {
                    SessionManager.setSession(data.user);
                    showFormSuccess(loginForm, data.message);

                    setTimeout(() => {
                        // Chiudi modale e ricarica la pagina o aggiorna l'UI per l'utente loggato
                        const loginModal = document.getElementById('login-modal');
                        if(loginModal) {
                            loginModal.setAttribute('aria-hidden','true');
                            loginModal.classList.remove('open');
                        }
                        window.location.reload();
                    }, 1000); // Chiudi modale e ricarica dopo 1 secondo

                } else {
                    showFormError(loginForm, data.message);
                }
            } catch (error) {
                // Gestisci errori API o di rete
                console.error('Errore durante il login:', error);
                showFormError(loginForm, error.message || 'Errore durante il login. Riprova più tardi.');
            } finally {
                // Ripristina lo stato del pulsante
                setButtonLoading(submitBtn, false);
            }
        });
    }

    // Gestione form di registrazione
    const registerForm = document.getElementById('register-form');
    if(registerForm) {
        // Non aggiungere CSRF token qui, ApiService lo gestisce automaticamente
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = registerForm.querySelector('#register-email').value.trim();
            const nickname = registerForm.querySelector('#register-nickname').value.trim();
            const password = registerForm.querySelector('#register-password').value.trim();
            const isCreatore = registerForm.querySelector('#register-is-creatore').checked;
            const errorMsg = registerForm.querySelector('#register-error');
            const successMsg = registerForm.querySelector('#register-success');
            const submitBtn = registerForm.querySelector('button[type="submit"]');
            
            // Validazione campi
            if(!email || !nickname || !password) {
                showFormError(registerForm, 'Compila tutti i campi richiesti.');
                return;
            }
            
            if(!validateEmail(email)) {
                showFormError(registerForm, 'Inserisci un indirizzo email valido.');
                return;
            }
            
            // La validazione della password con requisiti più stringenti è gestita lato backend per sicurezza
            // Qui possiamo fare una validazione base sulla lunghezza se necessario, ma la validazione complessa è meglio server-side.
            // if(!validatePassword(password)) {
            //     showFormError(registerForm, 'La password non soddisfa i requisiti minimi.');
            //     return;
            // }
            
            if(nickname.length < 3) {
                showFormError(registerForm, 'Il nickname deve essere di almeno 3 caratteri.');
                return;
            }
            
            // Disabilita il pulsante e mostra caricamento
            setButtonLoading(submitBtn, true);
            errorMsg.textContent = ''; // Pulisce messaggi precedenti
            successMsg.textContent = '';
            
            // Chiamata API per la registrazione
            try {
                const data = await ApiService.register({
                    email,
                    nickname,
                    password,
                    isCreatore
                });

                if (data.status === 'success') { // Assumendo che l'API di registrazione ritorni 'status: success'
                    SessionManager.setSession(data.user); // Assumendo che l'API ritorni i dati utente in 'user'
                    showFormSuccess(registerForm, data.message);

                    setTimeout(() => {
                        // Chiudi modale e reindirizza
                        const registerModal = document.getElementById('register-modal');
                        if(registerModal) {
                            registerModal.setAttribute('aria-hidden','true');
                            registerModal.classList.remove('open');
                        }
                        const redirectPath = data.user.userType === 'creator' ? // Assumendo userType sia in data.user
                            '/BOSTARTER/frontend/creatori/creatori_dashboard.html' :
                            '/BOSTARTER/frontend/dashboard.html';
                        window.location.href = redirectPath;
                    }, 1500);
                } else {
                    showFormError(registerForm, data.message);
                }
            } catch (error) {
                console.error('Errore durante la registrazione:', error);
                showFormError(registerForm, error.message || 'Errore durante la registrazione. Riprova più tardi.');
            } finally {
                setButtonLoading(submitBtn, false);
            }
        });
    }

    // Passaggio rapido tra login e registrazione
    const goToRegister = document.getElementById('go-to-register');
    if(goToRegister) {
        goToRegister.addEventListener('click', function(e){
            e.preventDefault();
            document.getElementById('login-modal').setAttribute('aria-hidden','true');
            document.getElementById('login-modal').classList.remove('open');
            document.getElementById('register-modal').setAttribute('aria-hidden','false');
            document.getElementById('register-modal').classList.add('open');
            const firstInput = document.getElementById('register-modal').querySelector('input:not([type=hidden]):not([disabled]),button,select,textarea');
            if(firstInput) firstInput.focus();
        });
    }
    const goToLogin = document.getElementById('go-to-login');
    if(goToLogin) {
        goToLogin.addEventListener('click', function(e){
            e.preventDefault();
            document.getElementById('register-modal').setAttribute('aria-hidden','true');
            document.getElementById('register-modal').classList.remove('open');
            document.getElementById('login-modal').setAttribute('aria-hidden','false');
            document.getElementById('login-modal').classList.add('open');
            const firstInput = document.getElementById('login-modal').querySelector('input:not([type=hidden]):not([disabled]),button,select,textarea');
            if(firstInput) firstInput.focus();
        });
    }
});