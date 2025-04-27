// BOSTARTER Frontend Logic

document.addEventListener('DOMContentLoaded', () => {
    const loginLink = document.getElementById('login-link');
    const registerLink = document.getElementById('register-link');
    const logoutLink = document.getElementById('logout-link');
    const userGreeting = document.getElementById('user-greeting');

    const loginModal = document.getElementById('login-modal');
    const registerModal = document.getElementById('register-modal');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const loginError = document.getElementById('login-error');
    const registerError = document.getElementById('register-error');
    const registerSuccess = document.getElementById('register-success');

    const closeModalButtons = document.querySelectorAll('.close-modal');

    const API_BASE_URL = '/BOSTARTER/backend/index.php/api'; // Adatta se la struttura URL è diversa

    // --- Funzioni Helper ---
    function showModal(modal) {
        modal.style.display = 'block';
    }

    function hideModal(modal) {
        modal.style.display = 'none';
        // Resetta messaggi di errore/successo quando si chiude
        if (loginError) loginError.textContent = '';
        if (registerError) registerError.textContent = '';
        if (registerSuccess) registerSuccess.textContent = '';
    }

    async function apiCall(endpoint, method = 'GET', body = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                // Aggiungere altri header se necessario (es. Authorization per token JWT)
            },
        };
        if (body) {
            options.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
            const data = await response.json();
            if (!response.ok) {
                // Lancia un errore con il messaggio dal backend, se disponibile
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            }
            return data;
        } catch (error) {
            console.error('API Call Error:', error);
            throw error; // Rilancia l'errore per gestirlo nel chiamante
        }
    }

    function updateUIForLoggedInUser(user) {
        loginLink.style.display = 'none';
        registerLink.style.display = 'none';
        logoutLink.style.display = 'inline';
        userGreeting.textContent = `Ciao, ${user.nickname}!`;
        userGreeting.style.display = 'inline';
        hideModal(loginModal);
        hideModal(registerModal);
    }

    function updateUIForLoggedOutUser() {
        loginLink.style.display = 'inline';
        registerLink.style.display = 'inline';
        logoutLink.style.display = 'none';
        userGreeting.style.display = 'none';
        userGreeting.textContent = '';
    }

    // --- Gestione Eventi ---

    // Mostra Modali
    loginLink.addEventListener('click', (e) => {
        e.preventDefault();
        showModal(loginModal);
    });

    registerLink.addEventListener('click', (e) => {
        e.preventDefault();
        showModal(registerModal);
    });

    // Chiudi Modali
    closeModalButtons.forEach(button => {
        button.addEventListener('click', () => {
            hideModal(loginModal);
            hideModal(registerModal);
        });
    });

    // Chiudi modale cliccando fuori dal form
    window.addEventListener('click', (event) => {
        if (event.target === loginModal) {
            hideModal(loginModal);
        }
        if (event.target === registerModal) {
            hideModal(registerModal);
        }
    });

    // Gestione Login
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        loginError.textContent = '';
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        try {
            const data = await apiCall('/login', 'POST', { email, password });
            console.log('Login success:', data);
            updateUIForLoggedInUser(data.user);
            // Qui potresti voler ricaricare i progetti o aggiornare altre parti dell'UI
        } catch (error) {
            loginError.textContent = error.message || 'Errore durante il login.';
        }
    });

    // Gestione Registrazione
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        registerError.textContent = '';
        registerSuccess.textContent = '';

        const formData = new FormData(registerForm);
        const userData = Object.fromEntries(formData.entries());

        // Converti anno_nascita in numero
        userData.anno_nascita = parseInt(userData.anno_nascita, 10);

        try {
            const data = await apiCall('/register', 'POST', userData);
            console.log('Register success:', data);
            registerSuccess.textContent = 'Registrazione avvenuta con successo! Ora puoi effettuare il login.';
            registerForm.reset(); // Pulisci il form
            // Potresti chiudere il modale di registrazione e aprire quello di login
            // hideModal(registerModal);
            // showModal(loginModal);
        } catch (error) {
            registerError.textContent = error.message || 'Errore durante la registrazione.';
        }
    });

    // Gestione Logout
    logoutLink.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            await apiCall('/logout', 'POST');
            updateUIForLoggedOutUser();
            // Ricarica la pagina o aggiorna l'UI come necessario
            console.log('Logout effettuato');
        } catch (error) {
            console.error('Errore durante il logout:', error);
            // Gestisci l'errore, magari mostrando un messaggio all'utente
        }
    });

    // --- Inizializzazione ---

    // Controlla se l'utente è già loggato all'avvio
    async function checkLoginStatus() {
        try {
            const data = await apiCall('/session', 'GET');
            if (data.loggedIn && data.user) {
                updateUIForLoggedInUser(data.user);
            } else {
                updateUIForLoggedOutUser();
            }
        } catch (error) {
            console.error('Errore nel controllo sessione:', error);
            updateUIForLoggedOutUser(); // Assumi logout in caso di errore
        }
        // Dopo aver controllato lo stato di login, carica i progetti
        loadProjects();
    }

    // Funzione per caricare e visualizzare i progetti
    async function loadProjects() {
        const projectsListDiv = document.getElementById('projects-list');
        projectsListDiv.innerHTML = '<p>Caricamento progetti...</p>'; // Messaggio di caricamento

        try {
            // Chiama l'endpoint API /projects
            const data = await apiCall('/projects', 'GET');
            const projects = data.projects; // L'API restituisce un oggetto con la chiave 'projects'

            if (!projects || projects.length === 0) {
                projectsListDiv.innerHTML = '<p>Nessun progetto trovato al momento.</p>';
                return;
            }

            projectsListDiv.innerHTML = ''; // Pulisci il messaggio di caricamento
            projects.forEach(project => {
                const projectCard = document.createElement('div');
                projectCard.className = 'project-card';
                // Formatta budget e totale finanziato come valuta (esempio semplice)
                const budgetFormatted = parseFloat(project.budget).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
                const fundedFormatted = parseFloat(project.totale_finanziato).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });

                projectCard.innerHTML = `
                    <h3>${escapeHTML(project.nome)}</h3>
                    <p>${escapeHTML(project.descrizione.substring(0, 150))}...</p>
                    <p><strong>Budget:</strong> ${budgetFormatted}</p>
                    <p><strong>Raccolti:</strong> ${fundedFormatted}</p>
                    <p><strong>Creatore:</strong> ${escapeHTML(project.creatore_nickname || 'Sconosciuto')}</p>
                    <p><strong>Stato:</strong> ${escapeHTML(project.stato)}</p>
                    <p><strong>Scadenza:</strong> ${new Date(project.data_limite).toLocaleDateString('it-IT')}</p>
                    <a href="#" data-project-name="${escapeHTML(project.nome)}" class="view-details-link">Vedi Dettagli</a>
                `;
                projectsListDiv.appendChild(projectCard);
            });

            // Aggiungi event listener per i link 'Vedi Dettagli' (se necessario)
            document.querySelectorAll('.view-details-link').forEach(link => {
                link.addEventListener('click', handleViewDetails);
            });

        } catch (error) {
            console.error('Errore caricamento progetti:', error);
            projectsListDiv.innerHTML = `<p>Errore nel caricamento dei progetti: ${escapeHTML(error.message)}</p>`;
        }
    }

    // Funzione per gestire il click su 'Vedi Dettagli' (placeholder)
    function handleViewDetails(event) {
        event.preventDefault();
        const projectName = event.target.getAttribute('data-project-name');
        console.log(`Visualizza dettagli per: ${projectName}`);
        // Qui potresti caricare una vista dettagliata del progetto
        alert(`Dettagli per: ${projectName} (da implementare)`);
        // Esempio: loadProjectDetails(projectName);
    }

    // Funzione per l'escape HTML semplice
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return str.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
    }

    // Avvia il controllo dello stato di login all'avvio
    checkLoginStatus();

});