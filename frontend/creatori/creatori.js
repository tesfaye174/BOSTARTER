/* Logica dashboard creatore BOSTARTER - Versione migliorata per UI/UX */

document.addEventListener('DOMContentLoaded', function () {
    // Animazione di caricamento iniziale con effetto di fade e slide
    const mainContent = document.getElementById('main-content');
    const sections = document.querySelectorAll('section');
    
    if (mainContent) {
        mainContent.style.opacity = '0';
        mainContent.style.transform = 'translateY(30px)';
        mainContent.style.transition = 'opacity 1s cubic-bezier(0.4, 0, 0.2, 1), transform 1s cubic-bezier(0.4, 0, 0.2, 1)';
        
        requestAnimationFrame(() => {
            mainContent.style.opacity = '1';
            mainContent.style.transform = 'translateY(0)';
        });
    }
    
    // Animazione delle sezioni con Intersection Observer
    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                sectionObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.2,
        rootMargin: '50px'
    });

    if (sections.length) {
        sections.forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(40px)';
            section.style.transition = 'opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), transform 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
            sectionObserver.observe(section);
        });
    }
    
    // Gestione della sidebar mobile
    const sidebar = document.querySelector('.dashboard-sidebar');
    
    // Funzione per gestire la responsività della sidebar
    function handleSidebarResponsive() {
        if (window.innerWidth <= 1100) {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Scroll alla sezione corrispondente con animazione fluida
                    const targetId = this.getAttribute('href').substring(1);
                    const targetSection = document.getElementById(targetId);
                    if (targetSection) {
                        e.preventDefault();
                        window.scrollTo({
                            top: targetSection.offsetTop - 80,
                            behavior: 'smooth'
                        });
                        
                        // Evidenzia la sezione cliccata
                        targetSection.style.boxShadow = '0 0 0 3px rgba(43,124,255,0.3)';
                        setTimeout(() => {
                            targetSection.style.boxShadow = '';
                            targetSection.style.transition = 'box-shadow 0.5s ease';
                        }, 800);
                    }
                });
            });
        }
    }
    
    // Inizializza la responsività
    handleSidebarResponsive();
    window.addEventListener('resize', handleSidebarResponsive);
    
    // Gestione del menu mobile
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            
            // Animazione avanzata del menu mobile con effetti di transizione
            if (!isExpanded) {
                mobileMenu.style.display = 'block';
                mobileMenu.style.opacity = '0';
                mobileMenu.style.transform = 'translateY(-20px) scale(0.98)';
                mobileMenu.style.transition = 'opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                
                requestAnimationFrame(() => {
                    mobileMenu.style.opacity = '1';
                    mobileMenu.style.transform = 'translateY(0) scale(1)';
                });
                
                // Animazione degli elementi del menu
                const menuItems = mobileMenu.querySelectorAll('a');
                menuItems.forEach((item, index) => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-10px)';
                    item.style.transition = `all 0.3s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.1}s`;
                    
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0)';
                    }, 100 + (index * 100));
                });
            } else {
                mobileMenu.style.opacity = '0';
                mobileMenu.style.transform = 'translateY(-20px) scale(0.98)';
                
                // Animazione di uscita degli elementi del menu
                const menuItems = mobileMenu.querySelectorAll('a');
                menuItems.forEach((item, index) => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-10px)';
                });
                
                setTimeout(() => {
                    mobileMenu.style.display = 'none';
                    mobileMenu.style.transform = '';
                    
                    // Reset degli stili degli elementi del menu
                    menuItems.forEach(item => {
                        item.style.opacity = '';
                        item.style.transform = '';
                    });
                }, 400);
            }
            
            mobileMenu.setAttribute('aria-hidden', isExpanded);
            
            // Chiudi menu al click fuori
            const closeMenu = function(e) {
                if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    mobileMenuBtn.setAttribute('aria-expanded', 'false');
                    mobileMenu.style.opacity = '0';
                    mobileMenu.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        mobileMenu.style.display = 'none';
                        mobileMenu.style.transform = '';
                    }, 300);
                    mobileMenu.setAttribute('aria-hidden', 'true');
                    document.removeEventListener('click', closeMenu);
                }
            };
            
            if (!isExpanded) {
                setTimeout(() => {
                    document.addEventListener('click', closeMenu);
                }, 100);
            }
        });
    
    // --- Progetti ---
    const projectForm = document.getElementById('project-creation-form');
    const myProjectsList = document.getElementById('my-projects-list');
    const statProjects = document.getElementById('stat-projects');
    const statCards = document.querySelectorAll('.stat-card');

    // Animazione delle card statistiche
    if (statCards.length) {
        statCards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('stat-card-animated');
            }, 500 + (index * 200));
        });
    }

    if (projectForm) {
        // Aggiungi effetti di focus ai campi del form
        const formInputs = projectForm.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('input-focused');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('input-focused');
            });
        });

        projectForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const title = projectForm['project-title'].value.trim();
            const category = projectForm['project-category'].value;
            const description = projectForm['project-description'].value.trim();
            const goal = projectForm['funding-goal'].value;
            const duration = projectForm['campaign-duration'].value;
            if (!title || !category || !description || !goal || !duration) {
                showFeedback(projectForm, 'Compila tutti i campi obbligatori!', false);
                return;
            }
            
            // Effetto di caricamento sul pulsante
            const submitBtn = projectForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Creazione in corso...';
            submitBtn.disabled = true;
            
            // Simula salvataggio progetto con ritardo per mostrare l'effetto
            setTimeout(() => {
                addProjectToList({ title, category, description, goal, duration });
                showFeedback(projectForm, 'Progetto creato con successo!', true);
                projectForm.reset();
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 800);
        });
    }

    function addProjectToList(project) {
        if (myProjectsList) {
            const div = document.createElement('div');
            div.className = 'project-card';
            div.style.opacity = '0';
            div.style.transform = 'translateY(20px)';
            div.innerHTML = `<h4>${project.title}</h4><p>${project.description}</p><span>Categoria: ${project.category}</span><br><span>Obiettivo: €${project.goal}</span><br><span>Durata: ${project.duration} giorni</span>`;
            myProjectsList.appendChild(div);
            
            // Animazione di entrata per la nuova card
            setTimeout(() => {
                div.style.transition = 'all 0.5s ease';
                div.style.opacity = '1';
                div.style.transform = 'translateY(0)';
            }, 50);
            
            // Aggiorna le statistiche con animazione
            updateProjectStats(true);
        }
    }
    
    function updateProjectStats(animate = false) {
        if (statProjects) {
            const newValue = myProjectsList ? myProjectsList.children.length : 0;
            
            if (animate) {
                // Animazione del contatore
                const currentValue = parseInt(statProjects.textContent) || 0;
                statProjects.style.transform = 'scale(1.2)';
                statProjects.style.color = '#1a68e5';
                
                setTimeout(() => {
                    statProjects.textContent = newValue;
                    setTimeout(() => {
                        statProjects.style.transition = 'all 0.5s ease';
                        statProjects.style.transform = 'scale(1)';
                        statProjects.style.color = '#2b7cff';
                    }, 300);
                }, 200);
            } else {
                statProjects.textContent = newValue;
            }
        }
    }

    // --- Ricompense ---
    const rewardForm = document.getElementById('reward-form');
    const rewardsList = document.getElementById('rewards-list');
    const loadRewardsBtn = document.getElementById('load-rewards-btn');
    const statRewards = document.getElementById('stat-rewards');
    let rewards = [];

    if (rewardForm) {
        rewardForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const projectId = rewardForm['reward-project-id'].value;
            const code = rewardForm['reward-code'].value.trim();
            const description = rewardForm['reward-description'].value.trim();
            const photo = rewardForm['reward-photo'].value.trim();
            if (!projectId || !code || !description) {
                showFeedback(rewardForm, 'Compila tutti i campi obbligatori!', false);
                return;
            }
            rewards.push({ projectId, code, description, photo });
            showFeedback(rewardForm, 'Ricompensa aggiunta!', true);
            rewardForm.reset();
            updateRewardStats();
        });
    }
    if (loadRewardsBtn && rewardsList) {
        loadRewardsBtn.addEventListener('click', function () {
            const projectId = document.getElementById('rewards-project-id-view').value;
            const filtered = rewards.filter(r => r.projectId === projectId);
            rewardsList.innerHTML = filtered.length ? filtered.map(r => `<div class='reward-card'><strong>${r.code}</strong>: ${r.description}${r.photo ? `<br><img src='${r.photo}' alt='Foto Ricompensa' class='reward-photo'>` : ''}</div>`).join('') : '<p>Nessuna ricompensa trovata.</p>';
        });
    }
    function updateRewardStats() {
        if (statRewards) {
            statRewards.textContent = rewards.length;
        }
    }

    // --- Skill utente ---
    const userSkillsList = document.getElementById('user-skills-list');
    const addSkillForm = document.getElementById('add-skill-form');
    const skillSelect = document.getElementById('skill-select');
    const skillLevel = document.getElementById('skill-level');
    let userSkills = [];
    const skillOptions = ['Arduino', 'Raspberry Pi', 'Python', 'C++', 'Design', '3D Printing', 'Marketing'];
    if (skillSelect) {
        skillOptions.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt;
            option.textContent = opt;
            skillSelect.appendChild(option);
        });
    }
    if (addSkillForm) {
        addSkillForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const skill = skillSelect.value;
            const level = skillLevel.value;
            if (!skill || level === '' || isNaN(level) || level < 0 || level > 5) {
                showFeedback(addSkillForm, 'Seleziona una skill e un livello valido (0-5).', false, 'add-skill-feedback');
                return;
            }
            userSkills.push({ skill, level });
            renderUserSkills();
            showFeedback(addSkillForm, 'Skill aggiunta!', true, 'add-skill-feedback');
            addSkillForm.reset();
        });
    }
    function renderUserSkills() {
        if (userSkillsList) {
            userSkillsList.innerHTML = userSkills.length ? userSkills.map(s => `<span class='skill-badge'>${s.skill} <small>(liv. ${s.level})</small></span>`).join(' ') : '<p>Nessuna skill aggiunta.</p>';
        }
    }

    // --- Componenti hardware/software (placeholder) ---
    // Da implementare: logica simile per componenti hardware/software

    // --- Feedback utente ---
    function showFeedback(form, message, success, feedbackId) {
        let feedback;
        if (feedbackId) {
            feedback = document.getElementById(feedbackId);
        } else {
            feedback = form.querySelector('.form-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'form-feedback';
                form.appendChild(feedback);
            }
        }
        
        // Rimuovi classi e stili precedenti
        feedback.classList.remove('success', 'error', 'visible', 'shake');
        feedback.style = '';
        
        // Imposta stili base
        const styles = {
            fontWeight: '600',
            letterSpacing: '0.02em',
            borderRadius: '8px',
            padding: '0.7em 1.2em',
            marginTop: '0.7em',
            opacity: '0',
            transform: 'translateY(-10px)',
            transition: 'all 0.3s ease',
            backgroundColor: success ? 'rgba(76, 175, 80, 0.1)' : 'rgba(244, 67, 54, 0.1)',
            color: success ? '#2e7d32' : '#d32f2f',
            border: `1px solid ${success ? '#4caf50' : '#f44336'}`,
            boxShadow: `0 2px 8px ${success ? 'rgba(76, 175, 80, 0.2)' : 'rgba(244, 67, 54, 0.2)'}`
        };
        
        // Applica stili
        Object.assign(feedback.style, styles);
        feedback.textContent = message;
        
        // Animazione di entrata
        requestAnimationFrame(() => {
            feedback.style.opacity = '1';
            feedback.style.transform = 'translateY(0)';
        });
        
        // Aggiungi icona
        const icon = success ? '✓' : '!';
        feedback.textContent = `${icon} ${message}`;
        
        // Se è un errore, aggiungi effetto shake
        if (!success) {
            setTimeout(() => {
                feedback.classList.add('shake');
            }, 100);
        }
        
        // Rimuovi il feedback dopo un delay
        setTimeout(() => {
            feedback.style.opacity = '0';
            feedback.style.transform = 'translateY(-10px)';
            
            setTimeout(() => {
                feedback.remove();
            }, 300);
        }, 4000);
    }
    }
});
