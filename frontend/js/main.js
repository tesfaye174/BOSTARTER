/**
 * BOSTARTER - Interazioni JavaScript moderne
 * Versione: 1.0 - Giugno 2025
 */

// Wrapper IIFE per evitare conflitti con lo scope globale
(function () {
    'use strict';

    // Controlla se il DOM è caricato
    document.addEventListener('DOMContentLoaded', init);

    // Gestione animazioni al caricamento e allo scroll
    const animations = {
        init() {
            // Animazioni al caricamento
            const animateElements = document.querySelectorAll('[data-animate]');
            animateElements.forEach((el, index) => {
                const delay = index * 100;
                setTimeout(() => {
                    el.classList.add('animate-in');
                }, delay);
            });

            // Animazioni allo scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                observer.observe(el);
            });
        }
    };

    // Gestione form e validazioni
    const forms = {
        init() {
            document.querySelectorAll('form[data-validate]').forEach(form => {
                form.addEventListener('submit', this.validateForm);
            });
        },

        validateForm(e) {
            const form = e.currentTarget;
            let valid = true;

            // Validazione campi richiesti
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('error');

                    // Mostra messaggio di errore
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.textContent = 'Questo campo è obbligatorio';

                    // Rimuovi messaggi di errore esistenti
                    const existingError = field.parentNode.querySelector('.error-message');
                    if (existingError) existingError.remove();

                    field.parentNode.appendChild(errorMsg);

                    // Rimuovi classe di errore quando l'utente inizia a digitare
                    field.addEventListener('input', function () {
                        this.classList.remove('error');
                        const errorMsg = this.parentNode.querySelector('.error-message');
                        if (errorMsg) errorMsg.remove();
                    }, { once: true });
                }
            });

            if (!valid) {
                e.preventDefault();
                window.boNotifications.show('Per favore, completa tutti i campi richiesti', 'warning');
            }
        }
    };

    // Funzionalità di ricerca
    const search = {
        init() {
            const searchForm = document.querySelector('.search-form');
            if (searchForm) {
                searchForm.addEventListener('submit', this.handleSearch);
            }
        },

        handleSearch(e) {
            e.preventDefault();
            const input = this.querySelector('input[type="search"]');
            const query = input.value.trim();

            if (query.length < 3) {
                window.boNotifications.show('Inserisci almeno 3 caratteri per la ricerca', 'info');
                return;
            }

            // Redirect alla pagina di ricerca o esegui ricerca AJAX
            window.location.href = `/BOSTARTER/frontend/search.php?q=${encodeURIComponent(query)}`;
        }
    };

    // Gestione dark mode (se supportata)
    const darkMode = {
        init() {
            const toggle = document.getElementById('dark-mode-toggle');
            if (!toggle) return;

            // Verifica preferenza utente salvata
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            this.setDarkMode(isDarkMode);

            // Aggiorna stato toggle
            toggle.checked = isDarkMode;

            // Ascolta cambiamenti
            toggle.addEventListener('change', () => {
                this.setDarkMode(toggle.checked);
            });
        },

        setDarkMode(isDark) {
            if (isDark) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'false');
            }
        }
    };

    // Inizializzazione componenti
    function init() {
        // notifications.init(); // Non più necessario
        animations.init();
        forms.init();
        search.init();
        darkMode.init();

        // Nascondere l'overlay di caricamento
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
            }, 500);
        }

        // Attivazione dei menu di navigazione 
        const mobileMenuButton = document.getElementById('mobile-menu-toggle');
        if (mobileMenuButton) {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
                document.body.classList.toggle('menu-open');
            });
        }

        // Service Worker per PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/BOSTARTER/frontend/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registrato correttamente con scope:', registration.scope);
                    })
                    .catch(err => {
                        console.error('ServiceWorker registration failed:', err);
                    });
            });
        }
    }
})();
