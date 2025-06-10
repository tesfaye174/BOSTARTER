/**
 * ===== JAVASCRIPT UNIFICATO PER ELIMINARE RIPETIZIONI =====
 * Sistema centralizzato per gestire funzionalità comuni
 */

// Namespace globale unificato
window.BOSTARTER = window.BOSTARTER || {};

/**
 * Manager centralizzato per animazioni
 */
BOSTARTER.AnimationManager = (function () {
    'use strict';

    // Configurazione predefinita
    const CONFIG = {
        durata: 300,
        scaglionamento: 100,
        soglia: 0.1,
        easing: 'ease-out'
    };

    let observatori = new Map();

    /**
     * Inizializza tutti gli osservatori per le animazioni
     */
    function inizializzaOsservatori() {
        inizializzaOsservatoreAnimazioni();
        inizializzaOsservatoreContatori();
        inizializzaOsservatoreProgressBar();
    }

    /**
     * Osservatore per animazioni generiche
     */
    function inizializzaOsservatoreAnimazioni() {
        const osservatore = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const elemento = entry.target;
                    const tipoAnimazione = elemento.dataset.animation || 'fadeIn';
                    const ritardo = parseInt(elemento.dataset.delay) || 0;

                    setTimeout(() => {
                        elemento.classList.add('animate', tipoAnimazione);
                    }, ritardo);

                    osservatore.unobserve(elemento);
                }
            });
        }, {
            threshold: CONFIG.soglia,
            rootMargin: '0px 0px -50px 0px'
        });

        document.querySelectorAll('[data-animation]').forEach(el => {
            osservatore.observe(el);
        });

        observatori.set('animazioni', osservatore);
    }

    /**
     * Osservatore per contatori animati
     */
    function inizializzaOsservatoreContatori() {
        const osservatore = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animaContatore(entry.target);
                    osservatore.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.counter, [data-counter]').forEach(el => {
            osservatore.observe(el);
        });

        observatori.set('contatori', osservatore);
    }

    /**
     * Osservatore per progress bar
     */
    function inizializzaOsservatoreProgressBar() {
        const osservatore = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animaProgressBar(entry.target);
                    osservatore.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('.progress-bar, [data-progress]').forEach(el => {
            osservatore.observe(el);
        });

        observatori.set('progress', osservatore);
    }

    /**
     * Anima un contatore da 0 al valore target
     */
    function animaContatore(elemento) {
        const target = parseInt(elemento.dataset.target || elemento.textContent.replace(/\D/g, ''));
        const durata = parseInt(elemento.dataset.duration || 2000);
        const startTime = performance.now();

        function aggiornaContatore(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / durata, 1);

            // Easing function per animazione fluida
            const easeOutCubic = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(target * easeOutCubic);

            elemento.textContent = current.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(aggiornaContatore);
            }
        }

        requestAnimationFrame(aggiornaContatore);
    }

    /**
     * Anima una progress bar
     */
    function animaProgressBar(elemento) {
        const target = parseInt(elemento.dataset.progress || elemento.style.width || 0);
        elemento.style.width = '0%';

        setTimeout(() => {
            elemento.style.transition = `width ${CONFIG.durata}ms ${CONFIG.easing}`;
            elemento.style.width = target + '%';
        }, 100);
    }

    /**
     * Anima card con scaglionamento
     */
    function animaCards(selector = '.card', ritardo = CONFIG.scaglionamento) {
        const cards = document.querySelectorAll(selector);

        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = `all ${CONFIG.durata * 2}ms ${CONFIG.easing}`;
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * ritardo);
        });
    }

    return {
        inizializza: inizializzaOsservatori,
        animaContatore,
        animaProgressBar,
        animaCards,
        configurazione: CONFIG
    };
})();

/**
 * Manager per le chiamate API unificate
 */
BOSTARTER.ApiManager = (function () {
    'use strict';

    const BASE_URL = '/BOSTARTER/backend/api/';

    /**
     * Richiesta API generica con gestione errori unificata
     */
    async function richiestaApi(endpoint, opzioni = {}) {
        const configurazionePredefinita = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const configFinale = { ...configurazionePredefinita, ...opzioni };

        try {
            const risposta = await fetch(BASE_URL + endpoint, configFinale);

            if (!risposta.ok) {
                throw new Error(`Errore HTTP: ${risposta.status}`);
            }

            const dati = await risposta.json();
            return { successo: true, dati };

        } catch (errore) {
            console.error('Errore API:', errore);
            return {
                successo: false,
                errore: errore.message || 'Errore di connessione'
            };
        }
    }

    /**
     * GET semplificata
     */
    function get(endpoint, parametri = {}) {
        const url = new URL(BASE_URL + endpoint, window.location.origin);
        Object.keys(parametri).forEach(key => {
            url.searchParams.append(key, parametri[key]);
        });

        return richiestaApi(url.pathname + url.search);
    }

    /**
     * POST semplificata
     */
    function post(endpoint, dati = {}) {
        return richiestaApi(endpoint, {
            method: 'POST',
            body: JSON.stringify(dati)
        });
    }

    /**
     * PUT semplificata
     */
    function put(endpoint, dati = {}) {
        return richiestaApi(endpoint, {
            method: 'PUT',
            body: JSON.stringify(dati)
        });
    }

    /**
     * DELETE semplificata
     */
    function delete_(endpoint) {
        return richiestaApi(endpoint, {
            method: 'DELETE'
        });
    }

    return {
        get,
        post,
        put,
        delete: delete_,
        richiesta: richiestaApi
    };
})();

/**
 * Manager per notifiche unificate
 */
BOSTARTER.NotificationManager = (function () {
    'use strict';

    let container;

    /**
     * Inizializza il sistema di notifiche
     */
    function inizializza() {
        container = document.getElementById('notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2 max-w-sm';
            container.setAttribute('role', 'alert');
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
    }

    /**
     * Mostra una notifica
     */
    function mostra(messaggio, tipo = 'info', durata = 5000) {
        if (!container) inizializza();

        const notifica = document.createElement('div');
        notifica.className = `notification notification-${tipo} p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full opacity-0`;

        const icona = ottieniIcona(tipo);
        notifica.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${icona}
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">${messaggio}</p>
                </div>
                <button class="ml-4 flex-shrink-0 text-gray-400 hover:text-gray-600" onclick="this.closest('.notification').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        container.appendChild(notifica);

        // Anima l'entrata
        setTimeout(() => {
            notifica.classList.remove('translate-x-full', 'opacity-0');
        }, 10);

        // Rimuovi automaticamente
        if (durata > 0) {
            setTimeout(() => {
                rimuoviNotifica(notifica);
            }, durata);
        }

        return notifica;
    }

    /**
     * Rimuove una notifica con animazione
     */
    function rimuoviNotifica(notifica) {
        notifica.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (notifica.parentNode) {
                notifica.parentNode.removeChild(notifica);
            }
        }, 300);
    }

    /**
     * Ottieni icona per tipo di notifica
     */
    function ottieniIcona(tipo) {
        const icone = {
            'success': '<i class="fas fa-check-circle text-green-500"></i>',
            'error': '<i class="fas fa-exclamation-circle text-red-500"></i>',
            'warning': '<i class="fas fa-exclamation-triangle text-yellow-500"></i>',
            'info': '<i class="fas fa-info-circle text-blue-500"></i>'
        };
        return icone[tipo] || icone.info;
    }

    // Metodi di convenienza
    function successo(messaggio, durata) {
        return mostra(messaggio, 'success', durata);
    }

    function errore(messaggio, durata) {
        return mostra(messaggio, 'error', durata);
    }

    function avviso(messaggio, durata) {
        return mostra(messaggio, 'warning', durata);
    }

    function info(messaggio, durata) {
        return mostra(messaggio, 'info', durata);
    }

    return {
        inizializza,
        mostra,
        successo,
        errore,
        avviso,
        info
    };
})();

/**
 * Manager per utility comuni
 */
BOSTARTER.Utils = (function () {
    'use strict';

    /**
     * Formatta una data in italiano
     */
    function formattaData(data) {
        const opzioni = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        return new Date(data).toLocaleDateString('it-IT', opzioni);
    }

    /**
     * Formatta un numero come valuta
     */
    function formattaValuta(importo, valuta = 'EUR') {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: valuta
        }).format(importo);
    }

    /**
     * Debounce per ottimizzare le performance
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle per limitare chiamate frequenti
     */
    function throttle(func, limit) {
        let inThrottle;
        return function () {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Copia testo negli appunti
     */
    async function copiaNegliAppunti(testo) {
        try {
            await navigator.clipboard.writeText(testo);
            BOSTARTER.NotificationManager.successo('Copiato negli appunti!');
            return true;
        } catch (err) {
            console.error('Errore nella copia:', err);
            BOSTARTER.NotificationManager.errore('Impossibile copiare');
            return false;
        }
    }

    return {
        formattaData,
        formattaValuta,
        debounce,
        throttle,
        copiaNegliAppunti
    };
})();

/**
 * Inizializzazione automatica
 */
document.addEventListener('DOMContentLoaded', function () {
    BOSTARTER.AnimationManager.inizializza();
    BOSTARTER.NotificationManager.inizializza();

    console.log('🚀 BOSTARTER Sistema Unificato Inizializzato');
});
