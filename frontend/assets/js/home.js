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
        initHomePageFeatures(); // Add new enhanced features
        loadFeaturedProjects();
    }

    function initHeroAnimations() {
        const hero = document.querySelector('.hero-gradient');
        if (!hero) return;

        // Staggered animation for hero elements
        const heroElements = hero.querySelectorAll('h1, .lead, .btn, .stats-card');

        heroElements.forEach((element, index) => {
            element.classList.add('animate-initial');
            setTimeout(() => {
                element.classList.add('animate-in');
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
            card.classList.add('card-lift');
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

    // ==========================================
    // HOME PAGE ENHANCED FEATURES
    // ==========================================

    function initHomePageFeatures() {
        // Initialize tooltips
        initTooltips();

        // Initialize counter animations for statistics
        initCounterAnimations();

        // Initialize scroll-to-top button
        initScrollToTop();

        // Initialize smooth scrolling
        initSmoothScrolling();

        // Initialize page loading animation
        initPageLoading();

        // Initialize enhanced interactions
        initEnhancedInteractions();

        // Initialize stagger animations
        initStaggerAnimations();
    }

    function initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: { show: 300, hide: 100 },
                animation: true,
                html: true
            });
        });
    }

    function initCounterAnimations() {
        const animateCounter = function(element, target) {
            let current = 0;
            const increment = target / 100;
            const duration = 2000; // 2 seconds
            const startTime = performance.now();

            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Easing function for smooth animation
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                current = Math.floor(target * easeOutQuart);

                element.textContent = current.toLocaleString('it-IT');

                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }

            requestAnimationFrame(updateCounter);
        };

        // Intersection Observer for fade-in animations with stagger
        const observerOptions = {
            threshold: 0.2,
            rootMargin: '0px 0px -100px 0px'
        };

        let staggerDelay = 0;
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('fade-in-up');
                    }, staggerDelay);
                    staggerDelay += 150; // Stagger delay between elements

                    // Reset stagger for next section
                    if (staggerDelay > 600) staggerDelay = 0;
                }
            });
        }, observerOptions);

        // Observe all sections for animation
        document.querySelectorAll('section:not(.fade-in-up)').forEach(section => {
            observer.observe(section);
        });

        // Animate counters when they come into view
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.counter');
                    counters.forEach((counter, index) => {
                        setTimeout(() => {
                            const target = parseInt(counter.getAttribute('data-target'));
                            animateCounter(counter, target);
                        }, index * 300); // Stagger counter animations
                    });
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        // Observe statistics section for counter animation
        const statsSection = document.querySelector('section.py-5.fade-in-up');
        if (statsSection) {
            counterObserver.observe(statsSection);
        }
    }

    function initScrollToTop() {
        const scrollToTopBtn = document.getElementById('scrollToTopBtn');
        if (!scrollToTopBtn) return;

        // Enhanced scroll detection with throttle
        let scrollTimeout;
        const throttledScroll = () => {
            if (!scrollTimeout) {
                scrollTimeout = setTimeout(() => {
                    const scrolled = window.pageYOffset > 300;
                    scrollToTopBtn.classList.toggle('visible', scrolled);
                    scrollTimeout = null;
                }, 16); // ~60fps
            }
        };

        window.addEventListener('scroll', throttledScroll);

        scrollToTopBtn.addEventListener('click', () => {
            // Smooth scroll to top with easing
            const startPosition = window.pageYOffset;
            const startTime = performance.now();
            const duration = 800;

            function scrollStep(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Easing function
                const easeOutCubic = 1 - Math.pow(1 - progress, 3);

                window.scrollTo(0, startPosition * (1 - easeOutCubic));

                if (progress < 1) {
                    requestAnimationFrame(scrollStep);
                }
            }

            requestAnimationFrame(scrollStep);
        });
    }

    function initSmoothScrolling() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const targetPosition = target.offsetTop - 80; // Account for fixed navbar
                    const startPosition = window.pageYOffset;
                    const distance = targetPosition - startPosition;
                    const duration = 1000;
                    const startTime = performance.now();

                    function scrollStep(currentTime) {
                        const elapsed = currentTime - startTime;
                        const progress = Math.min(elapsed / duration, 1);

                        // Easing function
                        const easeOutQuart = 1 - Math.pow(1 - progress, 4);

                        window.scrollTo(0, startPosition + distance * easeOutQuart);

                        if (progress < 1) {
                            requestAnimationFrame(scrollStep);
                        }
                    }

                    requestAnimationFrame(scrollStep);
                }
            });
        });
    }

    function initPageLoading() {
        // Add loading class to body initially
        document.body.classList.add('page-loading');

        // Remove loading class when page is fully loaded
        window.addEventListener('load', () => {
            setTimeout(() => {
                document.body.classList.remove('page-loading');
                document.body.classList.add('page-loaded');
            }, 300);
        });

        // Fallback for DOMContentLoaded
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.body.classList.add('loaded');
            }, 100);
        });
    }

    function initEnhancedInteractions() {
        // Enhanced card hover effects
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                // Add subtle glow effect
                this.style.boxShadow = '0 20px 60px rgba(0,0,0,0.15), 0 0 20px rgba(102, 126, 234, 0.1)';
            });

            card.addEventListener('mouseleave', function() {
                // Reset to original shadow
                this.style.boxShadow = '';
            });
        });

        // Enhanced button interactions
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mousedown', function() {
                this.style.transform = 'translateY(1px) scale(0.98)';
            });

            btn.addEventListener('mouseup', function() {
                this.style.transform = '';
            });

            btn.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });

        // Parallax effect for hero section
        const hero = document.querySelector('.hero-section');
        if (hero) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                hero.style.transform = `translateY(${rate}px)`;
            });
        }
    }

    function initStaggerAnimations() {
        // Stagger animation for feature cards
        const featureCards = document.querySelectorAll('.card');
        featureCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 150);
        });

        // Stagger animation for statistics
        const statCards = document.querySelectorAll('.bg-white');
        statCards.forEach((stat, index) => {
            stat.style.opacity = '0';
            stat.style.transform = 'translateY(50px)';

            setTimeout(() => {
                stat.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                stat.style.opacity = '1';
                stat.style.transform = 'translateY(0)';
            }, 500 + index * 200);
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
            // using fallback projects due to error
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
                        <div class="progress progress-thin">
                            <div class="progress-bar bg-gradient" style="--progress: ${Math.min(progress, 100)}%"></div>
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

