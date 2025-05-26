// Classe per la gestione delle analisi
class AnalyticsManager {
    constructor() {
        this.events = [];
        this.init();
    }

    // Inizializzazione
    init() {
        this.trackPageView();
        this.trackUserBehavior();
        this.trackPerformance();
    }

    // Traccia la visualizzazione della pagina
    trackPageView() {
        const pageData = {
            url: window.location.href,
            title: document.title,
            referrer: document.referrer,
            timestamp: new Date().toISOString()
        };

        this.logEvent('page_view', pageData);
    }

    // Traccia il comportamento dell'utente
    trackUserBehavior() {
        // Traccia i click
        document.addEventListener('click', (event) => {
            const target = event.target;
            const clickData = {
                element: target.tagName.toLowerCase(),
                id: target.id,
                class: target.className,
                text: target.textContent?.trim(),
                timestamp: new Date().toISOString()
            };

            this.logEvent('click', clickData);
        });

        // Traccia gli scroll
        let lastScrollTop = 0;
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset;
            const scrollData = {
                direction: scrollTop > lastScrollTop ? 'down' : 'up',
                position: scrollTop,
                timestamp: new Date().toISOString()
            };

            this.logEvent('scroll', scrollData);
            lastScrollTop = scrollTop;
        });

        // Traccia i form
        document.addEventListener('submit', (event) => {
            const form = event.target;
            const formData = {
                id: form.id,
                action: form.action,
                method: form.method,
                timestamp: new Date().toISOString()
            };

            this.logEvent('form_submit', formData);
        });
    }

    // Traccia le performance
    trackPerformance() {
        if (window.performance) {
            const performanceData = {
                navigation: performance.getEntriesByType('navigation')[0],
                resources: performance.getEntriesByType('resource'),
                paint: performance.getEntriesByType('paint'),
                timestamp: new Date().toISOString()
            };

            this.logEvent('performance', performanceData);
        }
    }

    // Traccia un evento
    logEvent(name, data) {
        const event = {
            name,
            data,
            timestamp: new Date().toISOString()
        };

        this.events.push(event);
        this.sendEvent(event);
    }

    // Invia un evento al server
    async sendEvent(event) {
        try {
            await fetch('/api/analytics', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(event)
            });
        } catch (error) {
            console.error('Errore nell\'invio dell\'evento:', error);
            // Salva l'evento in locale per reinvio successivo
            this.saveEvent(event);
        }
    }

    // Salva un evento in locale
    saveEvent(event) {
        const events = JSON.parse(localStorage.getItem('analytics_events') || '[]');
        events.push(event);
        localStorage.setItem('analytics_events', JSON.stringify(events));
    }

    // Reinvia gli eventi salvati
    async resendEvents() {
        const events = JSON.parse(localStorage.getItem('analytics_events') || '[]');
        
        for (const event of events) {
            try {
                await this.sendEvent(event);
                events.splice(events.indexOf(event), 1);
            } catch (error) {
                console.error('Errore nel reinvio dell\'evento:', error);
            }
        }
        
        localStorage.setItem('analytics_events', JSON.stringify(events));
    }

    // Traccia un'azione dell'utente
    trackUserAction(action, data = {}) {
        this.logEvent('user_action', {
            action,
            ...data,
            timestamp: new Date().toISOString()
        });
    }

    // Traccia un errore
    trackError(error, data = {}) {
        this.logEvent('error', {
            message: error.message,
            stack: error.stack,
            ...data,
            timestamp: new Date().toISOString()
        });
    }

    // Traccia una conversione
    trackConversion(type, data = {}) {
        this.logEvent('conversion', {
            type,
            ...data,
            timestamp: new Date().toISOString()
        });
    }

    // Traccia un'impressione
    trackImpression(element, data = {}) {
        this.logEvent('impression', {
            element: element.id || element.className,
            ...data,
            timestamp: new Date().toISOString()
        });
    }

    // Traccia un'interazione
    trackInteraction(element, type, data = {}) {
        this.logEvent('interaction', {
            element: element.id || element.className,
            type,
            ...data,
            timestamp: new Date().toISOString()
        });
    }

    // Ottiene tutti gli eventi
    getEvents() {
        return this.events;
    }

    // Pulisce gli eventi
    clearEvents() {
        this.events = [];
        localStorage.removeItem('analytics_events');
    }
}

// Crea un'istanza globale del gestore analisi
const analyticsManager = new AnalyticsManager();

// Esporta l'istanza e la classe
export { analyticsManager, AnalyticsManager }; 