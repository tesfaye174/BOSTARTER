// Gestione accessibilità
const accessibility = {
    // Gestione focus trap nei modali
    trapFocus(modal) {
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        modal.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        });
    },

    // Gestione annunci per screen reader
    announce(message, priority = 'polite') {
        const announcer = document.getElementById('sr-announcer') || createAnnouncer();
        announcer.setAttribute('aria-live', priority);
        announcer.textContent = message;
    },

    // Creazione elemento annunciatore
    createAnnouncer() {
        const announcer = document.createElement('div');
        announcer.id = 'sr-announcer';
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.className = 'visually-hidden';
        document.body.appendChild(announcer);
        return announcer;
    },

    // Gestione navigazione da tastiera
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Tasto ESC per chiudere modali
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal:not(.hidden)');
                if (openModal) {
                    const closeButton = openModal.querySelector('.modal__close');
                    if (closeButton) closeButton.click();
                }
            }

            // Navigazione con frecce nelle liste
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                const list = e.target.closest('[role="list"]');
                if (!list) return;

                const items = Array.from(list.querySelectorAll('[role="listitem"]'));
                const currentIndex = items.indexOf(e.target);
                if (currentIndex === -1) return;

                e.preventDefault();
                const nextIndex = e.key === 'ArrowDown'
                    ? Math.min(currentIndex + 1, items.length - 1)
                    : Math.max(currentIndex - 1, 0);
                items[nextIndex].focus();
            }
        });
    },

    // Gestione contrasto e tema
    setupThemeToggle() {
        const themeToggle = document.getElementById('theme-toggle');
        if (!themeToggle) return;

        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
        const currentTheme = localStorage.getItem('theme');

        const setTheme = (theme) => {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            accessibility.announce(`Tema ${theme} attivato`);
        };

        // Inizializza tema
        if (currentTheme) {
            setTheme(currentTheme);
        } else {
            setTheme(prefersDark.matches ? 'dark' : 'light');
        }

        // Gestione cambio tema
        themeToggle.addEventListener('click', () => {
            const newTheme = document.documentElement.getAttribute('data-theme') === 'dark'
                ? 'light'
                : 'dark';
            setTheme(newTheme);
        });

        // Ascolta preferenze sistema
        prefersDark.addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });
    },

    // Gestione zoom
    setupZoomControls() {
        const zoomIn = document.getElementById('zoom-in');
        const zoomOut = document.getElementById('zoom-out');
        const zoomReset = document.getElementById('zoom-reset');

        if (!zoomIn || !zoomOut || !zoomReset) return;

        const updateZoom = (level) => {
            document.documentElement.style.fontSize = `${level}%`;
            localStorage.setItem('zoom-level', level);
            accessibility.announce(`Zoom impostato al ${level}%`);
        };

        const currentZoom = parseInt(localStorage.getItem('zoom-level')) || 100;
        updateZoom(currentZoom);

        zoomIn.addEventListener('click', () => {
            const newZoom = Math.min(parseInt(localStorage.getItem('zoom-level')) + 10, 200);
            updateZoom(newZoom);
        });

        zoomOut.addEventListener('click', () => {
            const newZoom = Math.max(parseInt(localStorage.getItem('zoom-level')) - 10, 50);
            updateZoom(newZoom);
        });

        zoomReset.addEventListener('click', () => {
            updateZoom(100);
        });
    },

    // Gestione animazioni ridotte
    setupReducedMotion() {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        const updateMotion = (reduced) => {
            document.documentElement.classList.toggle('reduced-motion', reduced);
        };

        updateMotion(prefersReducedMotion.matches);

        prefersReducedMotion.addEventListener('change', (e) => {
            updateMotion(e.matches);
        });
    },

    // Inizializzazione
    init() {
        this.setupKeyboardNavigation();
        this.setupThemeToggle();
        this.setupZoomControls();
        this.setupReducedMotion();

        // Gestione focus trap per tutti i modali
        document.querySelectorAll('.modal').forEach(modal => {
            this.trapFocus(modal);
        });

        // Annuncia caricamento pagina
        this.announce('Pagina caricata', 'assertive');
    }
};

// Esporta il modulo
export default accessibility; 