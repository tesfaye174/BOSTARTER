document.addEventListener('DOMContentLoaded', () => {
    // Configurazione Intersection Observer con performance ottimizzate
    const observerOptions = {
        threshold: [0.1, 0.2, 0.5],
        rootMargin: '100px'
    };

    // Animazioni per la sezione 'Come funziona'
    const howItWorksSteps = document.querySelectorAll('.how-it-works .bg-gradient-to-br');
    const howItWorksObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                howItWorksObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    if (howItWorksSteps.length) {
        howItWorksSteps.forEach((step, index) => {
            step.style.opacity = '0';
            step.style.transform = 'translateY(30px)';
            step.style.transition = `all 0.6s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.2}s`;
            howItWorksObserver.observe(step);
        });
    }

    // Animazioni avanzate per le icone con transizioni fluide
    howItWorksSteps.forEach(step => {
        const icon = step.querySelector('.w-16');
        if (icon) {
            icon.style.transition = 'transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
            
            step.addEventListener('mouseenter', () => {
                icon.style.transform = 'scale(1.15) rotate(8deg)';
            });

            step.addEventListener('mouseleave', () => {
                icon.style.transform = 'scale(1) rotate(0deg)';
            });
        }
    });

    // Animazioni per la sezione 'Progetti recenti'
    const projectCards = document.querySelectorAll('.project-card');
    const projectObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                animateProgressBar(entry.target);
                projectObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

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

    // Effetti hover avanzati per le card dei progetti con transizioni migliorate
    projectCards.forEach(card => {
        card.style.transition = 'all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
        
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-8px) scale(1.02)';
            card.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.15)';
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) scale(1)';
            card.style.boxShadow = 'none';
        });
    });

    // Gestione accessibilità per focus
    const interactiveElements = document.querySelectorAll('.how-it-works .bg-gradient-to-br, .project-card');
    interactiveElements.forEach(element => {
        element.addEventListener('focus', () => {
            element.style.outline = '2px solid #3176FF';
            element.style.outlineOffset = '2px';
        });

        element.addEventListener('blur', () => {
            element.style.outline = 'none';
            element.style.outlineOffset = '0';
        });
    });
}));