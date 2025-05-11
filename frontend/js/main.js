// Modern BOSTARTER main.js

document.addEventListener('DOMContentLoaded', () => {
    // Elementi DOM principali
    const loginLink = document.getElementById('login-link');
    const registerLink = document.getElementById('register-link');
    const logoutLink = document.getElementById('logout-link');
    const userGreeting = document.getElementById('user-greeting');
    const themeToggle = document.getElementById('theme-toggle');
    const ctaButton = document.querySelector('.cta-button');
    const featuredProjects = document.querySelector('.featured-projects');
    const modals = document.querySelectorAll('.modal');
    const closeModalButtons = document.querySelectorAll('.close-modal');

    // Gestione modali login/registrazione (se presenti)
    function showModal(modal) {
        if (!modal) return;
        modal.classList.add('active');
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('visible'), 10);
        const firstInput = modal.querySelector('input, button:not(.close-modal)');
        if (firstInput) firstInput.focus();
        document.body.style.overflow = 'hidden';
    }

    function hideModal(modal) {
        if (!modal) return;
        modal.classList.remove('visible');
        setTimeout(() => {
            modal.classList.remove('active');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            const errorMsg = modal.querySelector('.error-message');
            const successMsg = modal.querySelector('.success-message');
            const form = modal.querySelector('form');
            if (errorMsg) errorMsg.textContent = '';
            if (successMsg) successMsg.textContent = '';
            if (form) form.reset();
        }, 200);
    }

    // Eventi per apertura modali
    if (loginLink) loginLink.addEventListener('click', e => {
        e.preventDefault();
        showModal(document.getElementById('login-modal'));
    });
    if (registerLink) registerLink.addEventListener('click', e => {
        e.preventDefault();
        showModal(document.getElementById('register-modal'));
    });

    // Eventi per chiusura modali
    closeModalButtons.forEach(button => {
        button.addEventListener('click', e => {
            const modal = button.closest('.modal');
            hideModal(modal);
        });
    });
    // Chiudi modale con ESC
    window.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                if (modal.classList.contains('active')) hideModal(modal);
            });
        }
    });

    // Gestione login/logout UI (placeholder, da integrare con backend)
    function updateUIForLoggedInUser(userName) {
        if (userGreeting) {
            userGreeting.textContent = `Benvenuto, ${userName}!`;
            userGreeting.style.display = 'inline';
        }
        if (loginLink) loginLink.style.display = 'none';
        if (registerLink) registerLink.style.display = 'none';
        if (logoutLink) logoutLink.style.display = 'inline';
    }
    function updateUIForLoggedOutUser() {
        if (loginLink) loginLink.style.display = 'inline';
        if (registerLink) registerLink.style.display = 'inline';
        if (logoutLink) logoutLink.style.display = 'none';
        if (userGreeting) {
            userGreeting.style.display = 'none';
            userGreeting.textContent = '';
        }
    }
    // Esempio: utente loggato (da sostituire con logica reale)
    // updateUIForLoggedInUser('John Doe'); // This should be called after successful login
    // Check session status on load
    // TODO: Add API call to check session status and update UI accordingly

    if (logoutLink) logoutLink.addEventListener('click', e => {
        e.preventDefault();
        // TODO: Implement API call for logout
        // On success:
        updateUIForLoggedOutUser();
        console.log('Logout action triggered. API call needed.'); // Changed from alert
    });

    // Tema chiaro/scuro con salvataggio preferenza
    function setTheme(dark) {
        document.body.classList.toggle('dark-mode', dark);
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-moon', !dark);
                icon.classList.toggle('fa-sun', dark);
            }
        }
        localStorage.setItem('bostarter-theme', dark ? 'dark' : 'light');
    }
    // Carica preferenza tema
    const savedTheme = localStorage.getItem('bostarter-theme');
    if (savedTheme) setTheme(savedTheme === 'dark');

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = !document.body.classList.contains('dark-mode');
            setTheme(isDark);
        });
    }

    // Scroll fluido ai progetti in evidenza
    if (ctaButton && featuredProjects) {
        ctaButton.addEventListener('click', () => {
            featuredProjects.scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Accessibilità: chiudi modale cliccando fuori dal contenuto
    modals.forEach(modal => {
        modal.addEventListener('click', e => {
            if (e.target === modal) hideModal(modal);
        });
    });

    // Placeholder per login/registrazione (da integrare con backend)
    // The following event listeners are redundant if the ones around line 42-49 are active and correct.
    // If those earlier listeners correctly open modals, these console.log versions should be removed.
    /*
    if (loginLink) loginLink.addEventListener('click', e => {
        e.preventDefault();
        // showModal(document.getElementById('login-modal')); // Assuming you want to show a modal
        console.log('Login link clicked. Modal should open. API call for login form submission needed.'); // Changed from alert
    });
    if (registerLink) registerLink.addEventListener('click', e => {
        e.preventDefault();
        // showModal(document.getElementById('register-modal')); // Assuming you want to show a modal
        console.log('Register link clicked. Modal should open. API call for registration form submission needed.'); // Changed from alert
    });
    */

    // Modern BOSTARTER main.js

    // ANIMAZIONI MODERNE E PARALLAX HERO
    // Animazione fade-in per i progetti in evidenza
    const projectCards = document.querySelectorAll('.project');
    projectCards.forEach((card, idx) => {
        card.style.opacity = 0;
        card.style.transform = 'translateY(40px) scale(0.98)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.7s cubic-bezier(.4,0,.2,1), transform 0.7s cubic-bezier(.4,0,.2,1)';
            card.style.opacity = 1;
            card.style.transform = 'translateY(0) scale(1)';
        }, 300 + idx * 180);
    });

    // Effetto parallax sulle immagini hero
    const heroLeft = document.querySelector('.hero-img-left');
    const heroRight = document.querySelector('.hero-img-right');
    window.addEventListener('scroll', () => {
        const scrollY = window.scrollY;
        if (heroLeft) heroLeft.style.transform = `translateY(${scrollY * 0.15}px) scale(1.05)`;
        if (heroRight) heroRight.style.transform = `translateY(-${scrollY * 0.12}px) scale(1.05)`;
    });

    // Animazione all'ingresso della hero-content
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
        heroContent.style.opacity = 0;
        heroContent.style.transform = 'translateY(40px)';
        setTimeout(() => {
            heroContent.style.transition = 'opacity 1s cubic-bezier(.4,0,.2,1), transform 1s cubic-bezier(.4,0,.2,1)';
            heroContent.style.opacity = 1;
            heroContent.style.transform = 'translateY(0)';
        }, 300);
    }

    // --- INTEGRAZIONE BACKEND ---
    const API_BASE = '/BOSTARTER/backend/index.php/api';

    // Utility per fetch con gestione errori
    async function apiFetch(url, options = {}) {
        try {
            const res = await fetch(url, options);
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.error || 'Errore di rete');
            return data;
        } catch (err) {
            throw new Error(err.message || 'Errore di rete');
        }
    }

    // LOGIN migliorato
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = loginForm.email.value;
            const password = loginForm.password.value;
            // Sezione codice sicurezza solo se visibile (admin)
            const securityCodeInput = document.getElementById('login-security-code');
            const security_code = securityCodeInput ? securityCodeInput.value : undefined;
            const errorMsg = document.getElementById('login-error');
            errorMsg.textContent = '';
            try {
                const payload = { email, password };
                if (security_code !== undefined && security_code !== '') payload.security_code = security_code;
                const data = await apiFetch(`${API_BASE}/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                hideModal(document.getElementById('login-modal'));
                updateUIForLoggedInUser(data.nickname || data.name || email);
                loginForm.reset();
            } catch (err) {
                errorMsg.textContent = err.message;
            }
        });
    }

    // Mostra campo codice sicurezza se l'email corrisponde a un admin
    const loginEmailInput = document.getElementById('login-email');
    const adminSecurityWrapper = document.getElementById('admin-security-code-wrapper');
    if (loginEmailInput && adminSecurityWrapper) {
        loginEmailInput.addEventListener('blur', async () => {
            const email = loginEmailInput.value;
            if (!email) {
                adminSecurityWrapper.style.display = 'none';
                return;
            }
            try {
                const res = await apiFetch(`${API_BASE}/is-admin?email=${encodeURIComponent(email)}`);
                if (res && res.is_admin) {
                    adminSecurityWrapper.style.display = '';
                } else {
                    adminSecurityWrapper.style.display = 'none';
                }
            } catch {
                adminSecurityWrapper.style.display = 'none';
            }
        });
    }

    // REGISTRAZIONE migliorata: prende i dati dal form, invia correttamente nickname, nome, cognome, anno e luogo
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = registerForm['register-email']?.value || '';
            const nickname = registerForm['register-nickname']?.value || '';
            const password = registerForm['register-password']?.value || '';
            // Campi aggiuntivi opzionali (puoi aggiungerli nel form se vuoi)
            const nome = nickname;
            const cognome = '';
            const anno_nascita = 2000;
            const luogo_nascita = '';
            const errorMsg = document.getElementById('register-error');
            const successMsg = document.getElementById('register-success');
            errorMsg.textContent = '';
            successMsg.textContent = '';
            try {
                await apiFetch(`${API_BASE}/register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, nickname, password, nome, cognome, anno_nascita, luogo_nascita })
                });
                successMsg.textContent = 'Registrazione avvenuta! Ora puoi accedere.';
                registerForm.reset();
            } catch (err) {
                errorMsg.textContent = err.message;
            }
        });
    }

    // LOGOUT migliorato
    if (logoutLink) logoutLink.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            await apiFetch(`${API_BASE}/logout`, { method: 'POST' });
        } catch { }
        updateUIForLoggedOutUser();
    });

    // Verifica sessione utente al caricamento
    async function checkSession() {
        try {
            const res = await fetch(`${API_BASE}/session`);
            if (res.ok) {
                const data = await res.json();
                if (data && data.nickname) {
                    updateUIForLoggedInUser(data.nickname);
                } else {
                    updateUIForLoggedOutUser();
                }
            } else {
                updateUIForLoggedOutUser();
            }
        } catch {
            updateUIForLoggedOutUser();
        }
    }
    checkSession();

    // Navigazione tra login e registrazione
    const goToRegister = document.getElementById('go-to-register');
    const goToLogin = document.getElementById('go-to-login');
    if (goToRegister) {
        goToRegister.addEventListener('click', e => {
            e.preventDefault();
            hideModal(document.getElementById('login-modal'));
            showModal(document.getElementById('register-modal'));
        });
    }
    if (goToLogin) {
        goToLogin.addEventListener('click', e => {
            e.preventDefault();
            hideModal(document.getElementById('register-modal'));
            showModal(document.getElementById('login-modal'));
        });
    }

    // CARICAMENTO PROGETTI IN EVIDENZA migliorato
    async function loadFeaturedProjects() {
        const grid = document.querySelector('.projects-grid');
        if (!grid) return;
        grid.innerHTML = '<div style="text-align:center;width:100%">Caricamento...</div>';
        try {
            const data = await apiFetch(`${API_BASE}/projects`);
            grid.innerHTML = '';
            (Array.isArray(data) ? data.slice(0, 3) : []).forEach(project => {
                const card = document.createElement('article');
                card.className = 'project-card';
                card.innerHTML = `
                    <img src="frontend/images/hero1.jpg" alt="Immagine progetto" />
                    <div class="project-card-content">
                        <h3>${project.name}</h3>
                        <div class="project-creator"><i class='fas fa-user'></i> ${project.creatore_nickname || 'Creatore'}</div>
                        <div class="project-summary">${project.description ? project.description.substring(0, 120) + (project.description.length > 120 ? '...' : '') : ''}</div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width:${Math.min(100, Math.round((project.current_funding || 0) / (project.budget || 1) * 100))}%"></div>
                        </div>
                        <div class="project-stats">
                            <span><i class='fas fa-euro-sign'></i> ${project.current_funding || 0} / ${project.budget}</span>
                            <span><i class='fas fa-calendar'></i> ${project.deadline ? project.deadline : ''}</span>
                        </div>
                        <a href="#" class="cta-button-outline" style="margin-top:12px">Dettagli</a>
                    </div>
                `;
                grid.appendChild(card);
            });
            if (grid.innerHTML === '') grid.innerHTML = '<div style="text-align:center;width:100%">Nessun progetto trovato.</div>';
        } catch (err) {
            grid.innerHTML = `<div style="color:red;text-align:center;width:100%">${err.message}</div>`;
        }
    }
    loadFeaturedProjects();

    // CREAZIONE PROGETTO migliorata
    const projectForm = document.getElementById('project-creation-form');
    if (projectForm) {
        projectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const title = projectForm['project-title'].value;
            const category = projectForm['project-category'].value;
            const description = projectForm['project-description'].value;
            const budget = parseFloat(projectForm['funding-goal'].value);
            const duration = parseInt(projectForm['campaign-duration'].value, 10);
            const deadline = new Date();
            deadline.setDate(deadline.getDate() + duration);
            let feedback = document.getElementById('project-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.id = 'project-feedback';
                feedback.style.marginTop = '1em';
                projectForm.appendChild(feedback);
            }
            feedback.textContent = '';
            try {
                await apiFetch(`${API_BASE}/projects`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: title,
                        description,
                        budget,
                        deadline: deadline.toISOString().slice(0, 10),
                        status: 'active',
                        project_type: category || 'altro'
                    })
                });
                feedback.style.color = 'green';
                feedback.textContent = 'Progetto creato con successo!';
                projectForm.reset();
                loadFeaturedProjects();
            } catch (err) {
                feedback.style.color = 'red';
                feedback.textContent = err.message;
            }
        });
    }

    // AGGIUNTA RICOMPENSA migliorata
    const rewardForm = document.getElementById('reward-form');
    if (rewardForm) {
        rewardForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const projectId = rewardForm['reward-project-id'].value;
            const code = rewardForm['reward-code'].value;
            const description = rewardForm['reward-description'].value;
            const photo = rewardForm['reward-photo'].value;
            let feedback = document.getElementById('reward-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.id = 'reward-feedback';
                feedback.style.marginTop = '1em';
                rewardForm.appendChild(feedback);
            }
            feedback.textContent = '';
            try {
                await apiFetch(`${API_BASE}/projects/${projectId}/rewards`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code, description, photo })
                });
                feedback.style.color = 'green';
                feedback.textContent = 'Ricompensa aggiunta con successo!';
                rewardForm.reset();
            } catch (err) {
                feedback.style.color = 'red';
                feedback.textContent = err.message;
            }
        });
    }

    // VISUALIZZAZIONE RICOMPENSE migliorata
    const loadRewardsBtn = document.getElementById('load-rewards-btn');
    if (loadRewardsBtn) {
        loadRewardsBtn.addEventListener('click', async () => {
            const projectId = document.getElementById('rewards-project-id-view').value;
            const rewardsList = document.getElementById('rewards-list');
            rewardsList.innerHTML = '<div style="text-align:center">Caricamento...</div>';
            if (!projectId) {
                rewardsList.textContent = 'Inserisci un ID progetto valido.';
                return;
            }
            try {
                const data = await apiFetch(`${API_BASE}/projects/${projectId}/rewards`);
                if (Array.isArray(data) && data.length > 0) {
                    const ul = document.createElement('ul');
                    data.forEach(reward => {
                        const li = document.createElement('li');
                        li.innerHTML = `<strong>${reward.code}</strong>: ${reward.description} ${reward.photo ? `<br><img src='${reward.photo}' alt='Foto ricompensa' style='max-width:100px;'>` : ''}`;
                        ul.appendChild(li);
                    });
                    rewardsList.innerHTML = '';
                    rewardsList.appendChild(ul);
                } else {
                    rewardsList.textContent = 'Nessuna ricompensa trovata per questo progetto.';
                }
            } catch (err) {
                rewardsList.textContent = err.message;
            }
        });
    }

    // --- MODALE DETTAGLIO PROGETTO ---
    // Crea la modale se non esiste
    let projectDetailModal = document.getElementById('project-detail-modal');
    if (!projectDetailModal) {
        projectDetailModal = document.createElement('div');
        projectDetailModal.id = 'project-detail-modal';
        projectDetailModal.className = 'modal';
        projectDetailModal.innerHTML = `
            <div class="modal-content" id="project-detail-content">
                <button type="button" class="close-modal" aria-label="Chiudi modale">&times;</button>
                <div id="project-detail-body"></div>
            </div>`;
        document.body.appendChild(projectDetailModal);
        projectDetailModal.querySelector('.close-modal').onclick = () => hideModal(projectDetailModal);
        projectDetailModal.onclick = e => { if (e.target === projectDetailModal) hideModal(projectDetailModal); };
    }

    // Funzione per mostrare dettaglio progetto
    async function showProjectDetail(projectId) {
        const body = document.getElementById('project-detail-body');
        body.innerHTML = '<div style="text-align:center">Caricamento...</div>';
        showModal(projectDetailModal);
        try {
            const project = await apiFetch(`${API_BASE}/project?name=${encodeURIComponent(projectId)}`);
            const rewards = await apiFetch(`${API_BASE}/projects/${project.id}/rewards`);
            const comments = await apiFetch(`${API_BASE}/projects/${project.id}/comments`);
            let profiles = [];
            if (project.project_type === 'software') {
                profiles = await apiFetch(`${API_BASE}/projects/${project.id}/profiles`);
            }
            // Statistiche progetto
            const percent = project.budget > 0 ? Math.round((project.current_funding || 0) * 100 / project.budget) : 0;
            const daysLeft = project.deadline ? Math.max(0, Math.ceil((new Date(project.deadline) - new Date()) / 86400000)) : '-';
            body.innerHTML = `
                <h2>${project.name}</h2>
                <p>${project.description || ''}</p>
                <p><strong>Creatore:</strong> ${project.creator_nickname || '-'}</p>
                <p><strong>Budget:</strong> €${project.budget} &nbsp; <strong>Raccolti:</strong> €${project.current_funding || 0} &nbsp; <strong>Completamento:</strong> ${percent}%</p>
                <p><strong>Stato:</strong> ${project.status} &nbsp; <strong>Giorni rimanenti:</strong> ${daysLeft}</p>
                <h3>Ricompense</h3>
                <ul>${rewards.map(r => `<li><b>${r.code}</b>: ${r.description}</li>`).join('') || '<li>Nessuna ricompensa</li>'}</ul>
                ${project.project_type === 'software' && profiles.length > 0 ? `
                <h3>Profili richiesti</h3>
                <ul>${profiles.map(p => `<li><b>${p.profile_name}</b> (Skill: ${p.skills_required}) <button class='cta-button-outline' data-profile='${p.id}'>Candidati</button></li>`).join('')}</ul>
                <div id='application-feedback'></div>
                ` : ''}
                <h3>Commenti</h3>
                <ul id='comments-list'>
                ${comments.map(c => `<li><b>${c.nickname || 'Utente'}</b>: ${c.comment_text} ${c.response ? `<br><i>Risposta creatore: ${c.response}</i>` : ''}
                ${project.is_creator ? `<form class='reply-form' data-comment='${c.id}'><input type='text' name='response' placeholder='Rispondi...' required><button type='submit'>Rispondi</button></form><div class='reply-feedback' id='reply-feedback-${c.id}'></div>` : ''}
                </li>`).join('') || '<li>Nessun commento</li>'}
                </ul>
                <h3>Finanzia il progetto</h3>
                <form id="funding-form">
                    <label>Importo (€): <input type="number" name="amount" min="1" required></label>
                    <label>Reward:
                        <select name="reward_id" required>
                            <option value="">Seleziona...</option>
                            ${rewards.map(r => `<option value="${r.id}">${r.code}</option>`).join('')}
                        </select>
                    </label>
                    <button type="submit" class="cta-button">Finanzia</button>
                    <div id="funding-feedback"></div>
                </form>
                <h3>Aggiungi un commento</h3>
                <form id="comment-form">
                    <textarea name="comment_text" rows="2" required placeholder="Scrivi un commento..."></textarea>
                    <button type="submit" class="cta-button">Invia</button>
                    <div id="comment-feedback"></div>
                </form>
            `;
            // Gestione finanziamento
            const fundingForm = document.getElementById('funding-form');
            if (fundingForm) {
                fundingForm.onsubmit = async e => {
                    e.preventDefault();
                    const amount = fundingForm.amount.value;
                    const reward_id = fundingForm.reward_id.value;
                    const feedback = document.getElementById('funding-feedback');
                    feedback.textContent = '';
                    try {
                        await apiFetch(`${API_BASE}/fundings`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ project_id: project.id, amount, reward_id })
                        });
                        feedback.style.color = 'green';
                        feedback.textContent = 'Finanziamento effettuato!';
                        fundingForm.reset();
                        loadFeaturedProjects();
                    } catch (err) {
                        feedback.style.color = 'red';
                        feedback.textContent = err.message;
                    }
                };
            }
            // Gestione commento
            const commentForm = document.getElementById('comment-form');
            if (commentForm) {
                commentForm.onsubmit = async e => {
                    e.preventDefault();
                    const comment_text = commentForm.comment_text.value;
                    const feedback = document.getElementById('comment-feedback');
                    feedback.textContent = '';
                    try {
                        await apiFetch(`${API_BASE}/projects/${project.id}/comments`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ comment_text })
                        });
                        feedback.style.color = 'green';
                        feedback.textContent = 'Commento inserito!';
                        commentForm.reset();
                        showProjectDetail(project.name); // ricarica commenti
                    } catch (err) {
                        feedback.style.color = 'red';
                        feedback.textContent = err.message;
                    }
                };
            }
            // Gestione candidatura ai profili software
            if (project.project_type === 'software' && profiles.length > 0) {
                body.querySelectorAll('button[data-profile]').forEach(btn => {
                    btn.onclick = async () => {
                        const profileId = btn.getAttribute('data-profile');
                        const feedback = document.getElementById('application-feedback');
                        feedback.textContent = '';
                        try {
                            await apiFetch(`${API_BASE}/projects/${project.id}/applications`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ profile_id: profileId })
                            });
                            feedback.style.color = 'green';
                            feedback.textContent = 'Candidatura inviata!';
                        } catch (err) {
                            feedback.style.color = 'red';
                            feedback.textContent = err.message;
                        }
                    };
                });
            }
            // Gestione risposta ai commenti (solo per creatore)
            if (project.is_creator) {
                body.querySelectorAll('.reply-form').forEach(form => {
                    form.onsubmit = async e => {
                        e.preventDefault();
                        const commentId = form.getAttribute('data-comment');
                        const response = form.response.value;
                        const feedback = document.getElementById('reply-feedback-' + commentId);
                        feedback.textContent = '';
                        try {
                            await apiFetch(`${API_BASE}/projects/${project.id}/comments`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ comment_id: commentId, response })
                            });
                            feedback.style.color = 'green';
                            feedback.textContent = 'Risposta inviata!';
                            form.reset();
                            showProjectDetail(project.name); // ricarica commenti
                        } catch (err) {
                            feedback.style.color = 'red';
                            feedback.textContent = err.message;
                        }
                    };
                });
            }
        } catch (err) {
            body.innerHTML = `<div style='color:red'>${err.message}</div>`;
        }
    }

    // Collega click sulle card progetto
    function enableProjectCardClicks() {
        const grid = document.querySelector('.projects-grid');
        if (!grid) return;
        grid.querySelectorAll('.project').forEach(card => {
            card.style.cursor = 'pointer';
            card.onclick = () => {
                const title = card.querySelector('h3')?.textContent;
                if (title) showProjectDetail(title);
            };
        });
    }
    // Richiama dopo ogni caricamento progetti
    async function loadFeaturedProjectsAndEnableClicks() {
        await loadFeaturedProjects();
        enableProjectCardClicks();
    }
    loadFeaturedProjectsAndEnableClicks();

    // --- GESTIONE SKILL UTENTE ---
    const userSkillsList = document.getElementById('user-skills-list');
    const addSkillForm = document.getElementById('add-skill-form');
    const skillSelect = document.getElementById('skill-select');
    const skillLevel = document.getElementById('skill-level');
    const addSkillFeedback = document.getElementById('add-skill-feedback');

    // Carica tutte le skill disponibili (per select)
    async function loadAllSkills() {
        if (!skillSelect) return;
        try {
            const skills = await apiFetch(`${API_BASE}/skills`);
            skillSelect.innerHTML = '<option value="">Seleziona...</option>' +
                skills.map(s => `<option value="${s.id}">${s.competency}</option>`).join('');
        } catch {
            skillSelect.innerHTML = '<option value="">Errore caricamento skill</option>';
        }
    }

    // Carica le skill dell'utente
    async function loadUserSkills() {
        if (!userSkillsList) return;
        userSkillsList.innerHTML = '<div>Caricamento...</div>';
        try {
            const userSkills = await apiFetch(`${API_BASE}/user/skills`);
            if (Array.isArray(userSkills) && userSkills.length > 0) {
                userSkillsList.innerHTML = '<ul>' + userSkills.map(s =>
                    `<li><b>${s.competency}</b> (livello ${s.level}) <button class='cta-button-outline' data-skill='${s.id}'>Rimuovi</button></li>`
                ).join('') + '</ul>';
                // Gestione rimozione skill
                userSkillsList.querySelectorAll('button[data-skill]').forEach(btn => {
                    btn.onclick = async () => {
                        try {
                            await apiFetch(`${API_BASE}/user/skills/${btn.getAttribute('data-skill')}`, { method: 'DELETE' });
                            loadUserSkills();
                        } catch (err) {
                            alert(err.message);
                        }
                    };
                });
            } else {
                userSkillsList.innerHTML = '<div>Nessuna skill presente.</div>';
            }
        } catch (err) {
            userSkillsList.innerHTML = `<div style='color:red'>${err.message}</div>`;
        }
    }

    // Aggiunta skill
    if (addSkillForm) {
        addSkillForm.onsubmit = async e => {
            e.preventDefault();
            addSkillFeedback.textContent = '';
            try {
                await apiFetch(`${API_BASE}/user/skills`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ skill_id: skillSelect.value, level: skillLevel.value })
                });
                addSkillFeedback.style.color = 'green';
                addSkillFeedback.textContent = 'Skill aggiunta!';
                addSkillForm.reset();
                loadUserSkills();
            } catch (err) {
                addSkillFeedback.style.color = 'red';
                addSkillFeedback.textContent = err.message;
            }
        };
    }

    // --- GESTIONE SKILL GLOBALI (SOLO ADMIN) ---
    const adminSkillsSection = document.getElementById('admin-skills-section');
    const addGlobalSkillForm = document.getElementById('add-global-skill-form');
    const addGlobalSkillFeedback = document.getElementById('add-global-skill-feedback');

    // Mostra la sezione solo se l'utente è admin (verifica via API)
    async function checkIfAdmin() {
        try {
            const res = await apiFetch(`${API_BASE}/session`);
            if (res && res.is_admin) {
                adminSkillsSection.style.display = '';
            }
        } catch { }
    }
    checkIfAdmin();

    // Aggiunta nuova skill globale
    if (addGlobalSkillForm) {
        addGlobalSkillForm.onsubmit = async e => {
            e.preventDefault();
            addGlobalSkillFeedback.textContent = '';
            try {
                await apiFetch(`${API_BASE}/skills`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ competency: document.getElementById('global-skill-name').value, level: 0 })
                });
                addGlobalSkillFeedback.style.color = 'green';
                addGlobalSkillFeedback.textContent = 'Competenza aggiunta!';
                addGlobalSkillForm.reset();
                loadAllSkills();
            } catch (err) {
                addGlobalSkillFeedback.style.color = 'red';
                addGlobalSkillFeedback.textContent = err.message;
            }
        };
    }

    // Carica skill all'avvio
    loadAllSkills();
    loadUserSkills();

    // --- GESTIONE PROFILI RICHIESTI PER PROGETTI SOFTWARE (SOLO CREATORE) ---
    const softwareProfilesSection = document.getElementById('software-profiles-section');
    const addProfileForm = document.getElementById('add-profile-form');
    const profileSkillSelect = document.getElementById('profile-skill-select');
    const addProfileFeedback = document.getElementById('add-profile-feedback');
    const profilesList = document.getElementById('profiles-list');

    // Mostra la sezione solo se l'utente è creatore (verifica via API)
    async function checkIfCreator() {
        try {
            const res = await apiFetch(`${API_BASE}/session`);
            if (res && res.is_creator) {
                softwareProfilesSection.style.display = '';
            }
        } catch { }
    }
    checkIfCreator();

    // Carica tutte le skill disponibili per il select profilo
    async function loadProfileSkills() {
        if (!profileSkillSelect) return;
        try {
            const skills = await apiFetch(`${API_BASE}/skills`);
            profileSkillSelect.innerHTML = '<option value="">Seleziona...</option>' +
                skills.map(s => `<option value="${s.id}">${s.competency}</option>`).join('');
        } catch {
            profileSkillSelect.innerHTML = '<option value="">Errore caricamento skill</option>';
        }
    }

    // Aggiunta profilo richiesto
    if (addProfileForm) {
        addProfileForm.onsubmit = async e => {
            e.preventDefault();
            addProfileFeedback.textContent = '';
            try {
                await apiFetch(`${API_BASE}/projects/${addProfileForm['profile-project-id'].value}/profiles`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        profile_name: addProfileForm['profile-name'].value,
                        skill_id: profileSkillSelect.value,
                        required_level: addProfileForm['profile-skill-level'].value
                    })
                });
                addProfileFeedback.style.color = 'green';
                addProfileFeedback.textContent = 'Profilo aggiunto!';
                addProfileForm.reset();
                loadProfilesList(addProfileForm['profile-project-id'].value);
            } catch (err) {
                addProfileFeedback.style.color = 'red';
                addProfileFeedback.textContent = err.message;
            }
        };
    }

    // Carica profili richiesti per un progetto software
    async function loadProfilesList(projectId) {
        if (!profilesList || !projectId) return;
        profilesList.innerHTML = '<div>Caricamento...</div>';
        try {
            const profiles = await apiFetch(`${API_BASE}/projects/${projectId}/profiles`);
            if (Array.isArray(profiles) && profiles.length > 0) {
                profilesList.innerHTML = '<ul>' + profiles.map(p =>
                    `<li><b>${p.profile_name}</b> (Skill: ${p.skills_required})</li>`
                ).join('') + '</ul>';
            } else {
                profilesList.innerHTML = '<div>Nessun profilo richiesto per questo progetto.</div>';
            }
        } catch (err) {
            profilesList.innerHTML = `<div style='color:red'>${err.message}</div>`;
        }
    }

    // Carica skill per profili all'avvio
    loadProfileSkills();

    // --- GESTIONE COMPONENTI HARDWARE (solo per progetti hardware) ---
    const hardwareComponentsSection = document.getElementById('hardware-components-section');
    const addHardwareComponentForm = document.getElementById('add-hardware-component-form');
    const hardwareComponentsList = document.getElementById('hardware-components-list');

    // Mostra la sezione solo se l'utente è creatore e il progetto è hardware
    async function checkIfHardwareCreator() {
        try {
            const res = await apiFetch(`${API_BASE}/session`);
            if (res && res.is_creator) {
                hardwareComponentsSection.style.display = '';
            }
        } catch { }
    }
    checkIfHardwareCreator();

    // Aggiunta componente hardware
    if (addHardwareComponentForm) {
        addHardwareComponentForm.onsubmit = async e => {
            e.preventDefault();
            const projectId = addHardwareComponentForm['hardware-project-id'].value;
            const name = addHardwareComponentForm['component-name'].value;
            const description = addHardwareComponentForm['component-description'].value;
            const price = addHardwareComponentForm['component-price'].value;
            const quantity = addHardwareComponentForm['component-quantity'].value;
            const feedback = document.getElementById('add-hardware-feedback');
            feedback.textContent = '';
            try {
                await apiFetch(`${API_BASE}/projects/${projectId}/hardware-components`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, description, price, quantity })
                });
                feedback.style.color = 'green';
                feedback.textContent = 'Componente aggiunto!';
                addHardwareComponentForm.reset();
                loadHardwareComponents(projectId);
            } catch (err) {
                feedback.style.color = 'red';
                feedback.textContent = err.message;
            }
        };
    }

    // Carica componenti hardware per un progetto
    async function loadHardwareComponents(projectId) {
        if (!hardwareComponentsList || !projectId) return;
        hardwareComponentsList.innerHTML = '<div>Caricamento...</div>';
        try {
            const components = await apiFetch(`${API_BASE}/projects/${projectId}/hardware-components`);
            if (Array.isArray(components) && components.length > 0) {
                hardwareComponentsList.innerHTML = '<ul>' + components.map(c =>
                    `<li><b>${c.name}</b> (${c.quantity}x, €${c.price}): ${c.description}</li>`
                ).join('') + '</ul>';
            } else {
                hardwareComponentsList.innerHTML = '<div>Nessun componente per questo progetto.</div>';
            }
        } catch (err) {
            hardwareComponentsList.innerHTML = `<div style='color:red'>${err.message}</div>`;
        }
    }

    // --- STATISTICHE COMMUNITY ---
    async function loadStatistics() {
        // Top creatori per affidabilità
        try {
            const creators = await apiFetch(`${API_BASE}/statistics/top-creators`);
            const list = document.getElementById('stat-creators-list');
            if (Array.isArray(creators) && creators.length > 0) {
                list.innerHTML = creators.map((c, i) => `
                    <li class="stat-anim" style="animation-delay:${i * 0.12}s">
                        <span class="stat-icon"><i class='fas fa-user-astronaut'></i></span>
                        <b>${c.nickname}</b> <span class='stat-value'>${c.reliability}%</span>
                    </li>`).join('');
            } else {
                list.innerHTML = '<li>Nessun dato disponibile.</li>';
            }
        } catch { document.getElementById('stat-creators-list').innerHTML = '<li>Errore caricamento.</li>'; }
        // Top progetti più vicini al completamento
        try {
            const projects = await apiFetch(`${API_BASE}/statistics/top-projects`);
            const list = document.getElementById('stat-projects-list');
            if (Array.isArray(projects) && projects.length > 0) {
                const images = [
                    'frontend/images/hero1.jpg',
                    'frontend/images/hero2.jpg',
                    'frontend/images/hero-left.svg'
                ];
                list.innerHTML = projects.map((p, i) => `
                    <li class="stat-anim stat-project" style="animation-delay:${i * 0.12}s">
                        <img src="${images[i % images.length]}" alt="Progetto" class="stat-thumb" />
                        <b>${p.name}</b> <span class='stat-value'>Mancano €${p.diff}</span>
                    </li>`).join('');
            } else {
                list.innerHTML = '<li>Nessun dato disponibile.</li>';
            }
        } catch { document.getElementById('stat-projects-list').innerHTML = '<li>Errore caricamento.</li>'; }
        // Top finanziatori
        try {
            const funders = await apiFetch(`${API_BASE}/statistics/top-funders`);
            const list = document.getElementById('stat-funders-list');
            if (Array.isArray(funders) && funders.length > 0) {
                list.innerHTML = funders.map((f, i) => `
                    <li class="stat-anim" style="animation-delay:${i * 0.12}s">
                        <span class="stat-icon"><i class='fas fa-gem'></i></span>
                        <b>${f.nickname}</b> <span class='stat-value'>€${f.total_funded}</span>
                    </li>`).join('');
            } else {
                list.innerHTML = '<li>Nessun dato disponibile.</li>';
            }
        } catch { document.getElementById('stat-funders-list').innerHTML = '<li>Errore caricamento.</li>'; }
        // Attiva animazioni
        document.querySelectorAll('.stat-anim').forEach(el => {
            el.classList.add('stat-fadein');
        });
    }
    loadStatistics();

    // --- RICERCA PROGETTI DINAMICA ---
    const projectSearchBar = document.getElementById('project-search-bar');
    const projectsGrid = document.querySelector('.projects-grid');
    let searchTimeout;

    if (projectSearchBar && projectsGrid) {
        projectSearchBar.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = projectSearchBar.value.trim();
            searchTimeout = setTimeout(() => {
                searchProjects(query);
            }, 300);
        });
    }

    async function searchProjects(query) {
        projectsGrid.innerHTML = '<div style="text-align:center;width:100%">Caricamento...</div>';
        try {
            const url = query ? `${API_BASE}/projects?search=${encodeURIComponent(query)}` : `${API_BASE}/projects`;
            const data = await apiFetch(url);
            projectsGrid.innerHTML = '';
            (Array.isArray(data) ? data : []).forEach(project => {
                const card = document.createElement('article');
                card.className = 'project';
                card.innerHTML = `
                    <h3>${project.name}</h3>
                    <p>${project.description || ''}</p>
                    <p><strong>Budget:</strong> €${project.budget}</p>
                    <a href="#" class="cta-button-outline">Dettagli</a>
                `;
                projectsGrid.appendChild(card);
            });
            if (projectsGrid.innerHTML === '')
                projectsGrid.innerHTML = '<div style="text-align:center;width:100%">Nessun progetto trovato.</div>';
            enableProjectCardClicks();
        } catch (err) {
            projectsGrid.innerHTML = `<div style='color:red;text-align:center;width:100%'>${err.message}</div>`;
        }
    }
});

// Miglioramento generazione dinamica card progetti e statistiche, animazioni, micro-interazioni, gestione modali, dark mode, error/success form

document.addEventListener('DOMContentLoaded', function () {
    // Modal login/register migliorata
    const loginModal = document.getElementById('login-modal');
    const registerModal = document.getElementById('register-modal');
    const goToRegister = document.getElementById('go-to-register');
    const goToLogin = document.getElementById('go-to-login');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const themeToggle = document.getElementById('theme-toggle');

    function openModal(modal) {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        setTimeout(() => {
            const firstInput = modal.querySelector('input,select,textarea,button');
            if (firstInput) firstInput.focus();
        }, 100);
    }
    function closeModal(modal) {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }
    if (goToRegister) goToRegister.onclick = e => { e.preventDefault(); closeModal(loginModal); openModal(registerModal); };
    if (goToLogin) goToLogin.onclick = e => { e.preventDefault(); closeModal(registerModal); openModal(loginModal); };
    closeModalBtns.forEach(btn => btn.onclick = function () {
        closeModal(this.closest('.modal'));
    });
    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            [loginModal, registerModal].forEach(m => closeModal(m));
        }
    });

    // Miglioramento dark mode toggle
    function setTheme(dark) {
        document.body.classList.toggle('dark-mode', dark);
        localStorage.setItem('bostarter-theme', dark ? 'dark' : 'light');
        themeToggle.innerHTML = dark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    }
    themeToggle.onclick = () => setTheme(!document.body.classList.contains('dark-mode'));
    if (localStorage.getItem('bostarter-theme') === 'dark' || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        setTheme(true);
    }

    // Miglioramento animazioni fade-in statistiche
    function animateStats() {
        document.querySelectorAll('.stat-card').forEach((el, i) => {
            el.classList.add('stat-anim');
            el.style.animationDelay = (i * 0.18) + 's';
        });
    }
    setTimeout(animateStats, 400);

    // Miglioramento generazione dinamica card progetti in evidenza
    function renderFeaturedProjects(projects) {
        const grid = document.getElementById('featured-projects-list');
        if (!grid) return;
        grid.innerHTML = '';
        projects.forEach((p, i) => {
            const card = document.createElement('div');
            card.className = 'project-card';
            card.tabIndex = 0;
            card.innerHTML = `
                <img src="${p.image}" alt="Anteprima progetto ${p.title}" class="project-img" loading="lazy">
                <h3 class="project-title">${p.title}</h3>
                <p class="project-desc">${p.description}</p>
                <div class="progress-bar-container" aria-label="Avanzamento progetto">
                    <div class="progress-bar" style="width:${Math.min(100, Math.round(p.raised / p.goal * 100))}%"></div>
                </div>
                <div class="project-meta">
                    <span class="creator"><i class="fas fa-user"></i> ${p.creator}</span>
                    <span class="goal">€${p.raised} / €${p.goal}</span>
                </div>
            `;
            card.addEventListener('mouseenter', () => card.classList.add('stat-fadein'));
            card.addEventListener('focus', () => card.classList.add('stat-fadein'));
            card.addEventListener('mouseleave', () => card.classList.remove('stat-fadein'));
            card.addEventListener('blur', () => card.classList.remove('stat-fadein'));
            grid.appendChild(card);
        });
    }
    // Esempio dati demo (da sostituire con fetch API)
    if (document.getElementById('featured-projects-list')) {
        renderFeaturedProjects([
            { image: 'frontend/images/demo1.jpg', title: 'Smart Garden', description: 'Un orto intelligente per tutti.', raised: 3200, goal: 5000, creator: 'Luca B.' },
            { image: 'frontend/images/demo2.jpg', title: 'EcoLamp', description: 'Lampada ecosostenibile e smart.', raised: 2100, goal: 3000, creator: 'Sara M.' },
            { image: 'frontend/images/demo3.jpg', title: 'ArtBook', description: 'Libro d’arte interattivo.', raised: 1800, goal: 2500, creator: 'Anna R.' }
        ]);
    }

    // Miglioramento gestione error/success nei form modali
    function showFormMessage(form, type, msg) {
        const err = form.querySelector('.error-message');
        const succ = form.querySelector('.success-message');
        if (err) err.textContent = type === 'error' ? msg : '';
        if (succ) succ.textContent = type === 'success' ? msg : '';
    }
    // Esempio: gestione submit login/register (da integrare con backend)
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.onsubmit = function (e) {
            e.preventDefault();
            showFormMessage(loginForm, 'error', 'Funzionalità demo: login non attivo.');
        };
    }
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.onsubmit = function (e) {
            e.preventDefault();
            showFormMessage(registerForm, 'success', 'Registrazione demo avvenuta con successo!');
        };
    }
});