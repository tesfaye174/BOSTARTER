document.addEventListener('DOMContentLoaded', () => {
    // Animazioni sezione 'Come funziona'
    const howItWorksSection = document.querySelector('.how-it-works');
    const steps = document.querySelectorAll('.how-it-works .step');

    // Intersection Observer per animazioni al scroll
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '50px'
    };

    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                sectionObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Osserva gli step per animazioni
    if (steps.length) {
        steps.forEach((step, index) => {
            step.style.opacity = '0';
            step.style.transform = 'translateY(30px)';
            step.style.transition = `all 0.6s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.2}s`;
            sectionObserver.observe(step);
        });
    }

    // Animazioni hover per gli step
    steps.forEach(step => {
        step.addEventListener('mouseenter', () => {
            step.classList.add('step-hover');
        });

        step.addEventListener('mouseleave', () => {
            step.classList.remove('step-hover');
        });
    });

    // Gestione Progetti Recenti
    const projectCards = document.querySelectorAll('.project-card');
    const projectObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('project-fade-in');
                animateProgressBar(entry.target);
                projectObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Osserva le card dei progetti per animazioni
    if (projectCards.length) {
        projectCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `all 0.6s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.15}s`;
            projectObserver.observe(card);
        });
    }

    // Animazione barra di progresso
    function animateProgressBar(card) {
        const progressBar = card.querySelector('.progress-fill');
        if (progressBar) {
            const width = progressBar.style.width;
            progressBar.style.width = '0%';
            setTimeout(() => {
                progressBar.style.width = width;
                progressBar.style.transition = 'width 1.5s cubic-bezier(0.4, 0, 0.2, 1)';
            }, 100);
        }
    }

    // Effetti hover per le card dei progetti
    projectCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.classList.add('card-hover');
        });

        card.addEventListener('mouseleave', () => {
            card.classList.remove('card-hover');
        });
    });
});