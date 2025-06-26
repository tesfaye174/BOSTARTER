<!--
    BOSTARTER Homepage Scripts
    JavaScript includes separated from index.php for better modularity
    Contains all homepage-specific scripts and performance monitoring
-->

<!-- Scripts Moderni -->
<script src="https://cdn.jsdelivr.net/npm/intersection-observer@0.12.0/intersection-observer.js"></script>
<script src="/BOSTARTER/frontend/js/modern-interactions.js" defer></script>
<script src="/BOSTARTER/frontend/js/notifications.js" defer></script>
<script src="/BOSTARTER/frontend/js/homepage.js" defer></script>
<script src="/BOSTARTER/frontend/js/auth.js" defer></script>

<!-- Inizializzazione componenti moderni -->
<script>
    // Configurazione tema
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);

    // Performance monitoring avanzato
    window.addEventListener('load', () => {
        if ('performance' in window) {
            const paintMetrics = performance.getEntriesByType('paint');
            const navigationTiming = performance.getEntriesByType('navigation')[0];
            
            // Metriche di performance moderne
            const metrics = {
                loadTime: navigationTiming.loadEventEnd - navigationTiming.navigationStart,
                firstPaint: paintMetrics.find(m => m.name === 'first-paint')?.startTime,
                firstContentfulPaint: paintMetrics.find(m => m.name === 'first-contentful-paint')?.startTime,
                domInteractive: navigationTiming.domInteractive - navigationTiming.navigationStart,
                domComplete: navigationTiming.domComplete - navigationTiming.navigationStart
            };

            // Notifica di performance se necessario
            if (metrics.loadTime > 3000) {
                boNotifications.info('Stiamo ottimizzando la tua esperienza...');
            }

            // Log delle metriche
            console.table(metrics);
        }
    });

    // Gestione form newsletter moderna
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = newsletterForm.querySelector('input[type="email"]').value;
            
            try {
                const response = await fetch('/newsletter/subscribe.php', {
                    method: 'POST',
                    body: JSON.stringify({ email }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    boNotifications.success('Iscrizione completata con successo!');
                    newsletterForm.reset();
                } else {
                    throw new Error('Errore durante l\'iscrizione');
                }
            } catch (error) {
                boNotifications.error('Si è verificato un errore. Riprova più tardi.');
            }
        });
    }

    // Animazioni progress bar
    document.querySelectorAll('.progress-bar').forEach(bar => {
        const target = bar.getAttribute('data-progress');
        const fill = bar.querySelector('.progress-fill');
        
        if (fill && target) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        fill.style.width = `${target}%`;
                    }
                });
            });
            
            observer.observe(bar);
        }
    });

    // Benvenuto personalizzato
    if (!localStorage.getItem('welcomed')) {
        setTimeout(() => {
            boNotifications.success('Benvenuto su BOSTARTER!', {
                action: {
                    text: 'Esplora Progetti',
                    callback: () => window.location.href = '/esplora'
                }
            });
            localStorage.setItem('welcomed', 'true');
        }, 1000);
    }
</script>
