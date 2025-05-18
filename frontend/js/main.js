// Funzionalità homepage BOSTARTER

document.addEventListener('DOMContentLoaded', function () {
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
                // Reset messaggi feedback
                const errorMsg = modal.querySelector('.error-message');
                const successMsg = modal.querySelector('.success-message');
                if(errorMsg) errorMsg.textContent = '';
                if(successMsg) successMsg.textContent = '';
            }
        });
    });
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

    // Migliora feedback login
    const loginForm = document.getElementById('login-form');
    if(loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = loginForm.querySelector('#login-email').value.trim();
            const password = loginForm.querySelector('#login-password').value.trim();
            const errorMsg = loginForm.querySelector('#login-error');
            const successMsg = loginForm.querySelector('#login-success');
            errorMsg.textContent = '';
            successMsg.textContent = '';
            if(!email || !password) {
                errorMsg.textContent = 'Inserisci email e password.';
                return;
            }
            // Simulazione login (da sostituire con chiamata reale)
            if(email === 'admin@bostarter.it' && password === 'admin') {
                successMsg.textContent = 'Accesso effettuato! Benvenuto.';
                setTimeout(()=>{
                    document.getElementById('login-modal').setAttribute('aria-hidden','true');
                    document.getElementById('login-modal').classList.remove('open');
                }, 1200);
            } else {
                errorMsg.textContent = 'Credenziali non valide. Riprova.';
            }
        });
    }
    // Gestione form di registrazione
    const registerForm = document.getElementById('register-form');
    if(registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = registerForm.querySelector('#register-email').value.trim();
            const nickname = registerForm.querySelector('#register-nickname').value.trim();
            const password = registerForm.querySelector('#register-password').value.trim();
            const isCreatore = registerForm.querySelector('#register-is-creatore').checked;
            const errorMsg = registerForm.querySelector('#register-error');
            const successMsg = registerForm.querySelector('#register-success');
            
            errorMsg.textContent = '';
            successMsg.textContent = '';
            
            if(!email || !nickname || !password) {
                errorMsg.textContent = 'Compila tutti i campi richiesti.';
                return;
            }
            
            // Simulazione registrazione (da sostituire con chiamata reale all'API)
            successMsg.textContent = 'Registrazione completata con successo!';
            
            // Reindirizzamento alla dashboard creatori dopo breve attesa
            setTimeout(() => {
                // Utilizzo percorso assoluto dal root del sito per evitare problemi di navigazione
                window.location.href = '/BOSTARTER/frontend/creatori/creatori_dashboard.html';
                // Nota: tutti gli utenti vengono reindirizzati alla pagina dei creatori, indipendentemente dall'opzione selezionata
            }, 1500);
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