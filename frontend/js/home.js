/**
 * BOSTARTER Homepage - Enhanced JavaScript
 * Utilizza il nuovo sistema BOSTARTER core
 * @version 4.1.0
 */
(function () {
    'use strict';

    // Wait for BOSTARTER core to be ready
    document.addEventListener('bostarterReady', initHomepage);

    // Fallback initialization if core is already loaded
    if (window.BOSTARTER && document.readyState !== 'loading') {
        initHomepage();
    }

    function initHomepage() {
        const { utils, notifications } = window.BOSTARTER;

        utils.log('Initializing Homepage v4.1.0');

        // Initialize core features
        initHeroAnimations();
        initStatsCounters();
        initProjectCards();
        initNewsletterSubscription();
        loadFeaturedProjects();
    }

    function initHeroAnimations() {
        const hero = document.querySelector('.hero-gradient');
        if (!hero) return;

        // Staggered animation for hero elements
        const heroElements = hero.querySelectorAll('h1, .lead, .btn, .stats-card');

        heroElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';

            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 100 + (index * 150));
        });
    }

    function initStatsCounters() {
        const counters = document.querySelectorAll('[data-counter]');

        if (counters.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.dataset.counter || counter.textContent);
                    const suffix = counter.dataset.suffix || '';

                    animateCounter(counter, 0, target, 2000, suffix);
                    observer.unobserve(counter);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    }

    function animateCounter(element, start, end, duration, suffix = '') {
        const startTime = performance.now();

        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const currentValue = Math.floor(start + (end - start) * easeOutQuart(progress));
            element.textContent = currentValue.toLocaleString('it-IT') + suffix;

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        }

        requestAnimationFrame(updateCounter);
    }

    function easeOutQuart(t) {
        return 1 - Math.pow(1 - t, 4);
    }

    function initProjectCards() {
        const projectCards = document.querySelectorAll('.project-card');

        projectCards.forEach(card => {
            // Simplified hover effects using CSS
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-8px)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });

        // Animate cards on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        projectCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease-out';
            observer.observe(card);
        });
    }

    function initNewsletterSubscription() {
        const newsletterForm = document.querySelector('#newsletterForm, form[action*="subscribe"]');
        if (!newsletterForm) return;

        newsletterForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const emailInput = newsletterForm.querySelector('input[type="email"]');
            const email = emailInput?.value?.trim();

            if (!email || !isValidEmail(email)) {
                window.BOSTARTER.notifications.error('Inserisci un indirizzo email valido');
                return;
            }

            const submitBtn = newsletterForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Iscrizione...';

                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 1500));

                window.BOSTARTER.notifications.success('Iscrizione completata con successo!');
                newsletterForm.reset();

            } catch (error) {
                window.BOSTARTER.notifications.error('Errore durante l\'iscrizione');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    async function loadFeaturedProjects() {
        try {
            const response = await fetch('../backend/api/project.php?limit=6&status=aperto');
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.projects?.length > 0) {
                    updateProjectsSection(data.projects);
                }
            }
        } catch (error) {
            console.log('Using fallback projects:', error.message);
        }
    }

    function updateProjectsSection(projects) {
        const container = document.getElementById('projects-container');
        if (!container) return;

        container.innerHTML = '';

        projects.forEach(project => {
            const progress = project.budget_raccolto
                ? Math.round((project.budget_raccolto / project.budget_richiesto) * 100)
                : 0;

            const daysLeft = project.data_limite
                ? Math.max(0, Math.ceil((new Date(project.data_limite) - new Date()) / (1000 * 60 * 60 * 24)))
                : 0;

            const projectCard = createProjectCard(project, progress, daysLeft);
            container.appendChild(projectCard);
        });

        // Re-initialize animations for new cards
        initProjectCards();
    }

    function createProjectCard(project, progress, daysLeft) {
        const card = document.createElement('div');
        card.className = 'col-lg-4 col-md-6';
        card.innerHTML = `
            <div class="card project-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-primary fs-6">${escapeHtml(project.tipo || 'Software')}</span>
                        <span class="text-muted small">${daysLeft} giorni rimasti</span>
                    </div>
                    
                    <h5 class="card-title fw-bold mb-3">${escapeHtml(project.nome)}</h5>
                    <p class="card-text text-muted mb-4">${escapeHtml((project.descrizione || '').substring(0, 120))}...</p>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between small mb-2">
                            <span class="fw-semibold">€${(project.budget_raccolto || 0).toLocaleString('it-IT')}</span>
                            <span class="text-muted">${progress}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-gradient" style="width: ${Math.min(progress, 100)}%"></div>
                        </div>
                        <small class="text-muted">Obiettivo: €${(project.budget_richiesto || 0).toLocaleString('it-IT')}</small>
                    </div>
                    
                    <a href="view.php?id=${project.id}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-eye me-2"></i>Scopri di più
                    </a>
                </div>
            </div>
        `;
        return card;
    }

    // Utility functions
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();

