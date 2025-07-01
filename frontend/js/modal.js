/**
 * BOSTARTER - Modal Accessibility Manager
 * Gestione dell'accessibilità per modali e dialog
 */
(function (window, document) {
    'use strict';
    const ModalAccessibility = {
        // Configurazione
        config: {
            modalSelector: '.modal, [role="dialog"], [data-modal]',
            backdropSelector: '.modal-backdrop, .modal-overlay',
            closeSelector: '.modal-close, [data-modal-close]',
            focusableSelectors: [
                'button',
                '[href]',
                'input',
                'select',
                'textarea',
                '[tabindex]:not([tabindex="-1"])'
            ].join(',')
        },
        // Stato
        state: {
            openModals: [],
            lastFocusedElement: null,
            scrollPosition: 0
        },
        /**
         * Inizializza il modal accessibility manager
         */
        init() {
            this.setupEventListeners();
            this.observeModalChanges();
        },
        /**
         * Setup event listeners globali
         */
        setupEventListeners() {
            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                this.handleKeyDown(e);
            });
            // Modal triggers
            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-modal-target]');
                if (trigger) {
                    e.preventDefault();
                    const modalId = trigger.getAttribute('data-modal-target');
                    this.openModal(modalId);
                }
                const closeBtn = e.target.closest(this.config.closeSelector);
                if (closeBtn) {
                    e.preventDefault();
                    const modal = closeBtn.closest(this.config.modalSelector);
                    if (modal) {
                        this.closeModal(modal);
                    }
                }
            });
            // Backdrop clicks
            document.addEventListener('click', (e) => {
                if (e.target.matches(this.config.backdropSelector)) {
                    const modal = e.target.closest(this.config.modalSelector) ||
                        document.querySelector(this.config.modalSelector + '.open');
                    if (modal) {
                        this.closeModal(modal);
                    }
                }
            });
        },
        /**
         * Osserva i cambiamenti nei modali (MutationObserver)
         */
        observeModalChanges() {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const target = mutation.target;
                        if (target.matches(this.config.modalSelector)) {
                            if (target.classList.contains('open') || target.classList.contains('active')) {
                                this.onModalOpened(target);
                            } else {
                                this.onModalClosed(target);
                            }
                        }
                    }
                });
            });
            observer.observe(document.body, {
                attributes: true,
                subtree: true,
                attributeFilter: ['class']
            });
        },
        /**
         * Apre un modal
         */
        openModal(modalId) {
            const modal = document.getElementById(modalId) ||
                document.querySelector(`[data-modal-id="${modalId}"]`);
            if (!modal) {
                console.warn(`Modal non trovato: ${modalId}`);
                return;
            }
            // Salva l'elemento attualmente focalizzato
            this.state.lastFocusedElement = document.activeElement;
            // Salva la posizione di scroll
            this.state.scrollPosition = window.pageYOffset;
            // Prepara il modal per l'accessibilità
            this.prepareModal(modal);
            // Aggiungi alla lista dei modali aperti
            this.state.openModals.push(modal);
            // Apri il modal
            modal.classList.add('open', 'active');
            modal.setAttribute('aria-hidden', 'false');
            // Blocca lo scroll del body
            this.lockBodyScroll();
            // Focus management
            this.setInitialFocus(modal);
        },
        /**
         * Chiude un modal
         */
        closeModal(modal) {
            if (!modal) return;
            // Rimuovi dalla lista dei modali aperti
            const index = this.state.openModals.indexOf(modal);
            if (index > -1) {
                this.state.openModals.splice(index, 1);
            }
            // Chiudi il modal
            modal.classList.remove('open', 'active');
            modal.setAttribute('aria-hidden', 'true');
            // Se non ci sono più modali aperti
            if (this.state.openModals.length === 0) {
                this.unlockBodyScroll();
                this.restoreFocus();
            } else {
                // Focus sull'ultimo modal aperto
                const lastModal = this.state.openModals[this.state.openModals.length - 1];
                this.setInitialFocus(lastModal);
            }
        },
        /**
         * Prepara un modal per l'accessibilità
         */
        prepareModal(modal) {
            // Imposta attributi ARIA se non presenti
            if (!modal.hasAttribute('role')) {
                modal.setAttribute('role', 'dialog');
            }
            if (!modal.hasAttribute('aria-modal')) {
                modal.setAttribute('aria-modal', 'true');
            }
            // Trova o crea un titolo
            let titleElement = modal.querySelector('.modal-title, h1, h2, h3');
            if (titleElement && !modal.hasAttribute('aria-labelledby')) {
                if (!titleElement.id) {
                    titleElement.id = this.generateId('modal-title');
                }
                modal.setAttribute('aria-labelledby', titleElement.id);
            }
            // Trova la descrizione se presente
            let descElement = modal.querySelector('.modal-description, .modal-body p:first-child');
            if (descElement && !modal.hasAttribute('aria-describedby')) {
                if (!descElement.id) {
                    descElement.id = this.generateId('modal-desc');
                }
                modal.setAttribute('aria-describedby', descElement.id);
            }
        },
        /**
         * Gestione eventi da tastiera
         */
        handleKeyDown(e) {
            const currentModal = this.getCurrentModal();
            if (!currentModal) return;
            switch (e.key) {
                case 'Escape':
                    e.preventDefault();
                    this.closeModal(currentModal);
                    break;
                case 'Tab':
                    this.handleTabNavigation(e, currentModal);
                    break;
            }
        },
        /**
         * Gestisce la navigazione con Tab
         */
        handleTabNavigation(e, modal) {
            const focusableElements = this.getFocusableElements(modal);
            if (focusableElements.length === 0) {
                e.preventDefault();
                return;
            }
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        },
        /**
         * Ottiene gli elementi focalizzabili nel modal
         */
        getFocusableElements(modal) {
            const elements = modal.querySelectorAll(this.config.focusableSelectors);
            return Array.from(elements).filter(el => {
                return !el.disabled &&
                    !el.hasAttribute('aria-hidden') &&
                    el.offsetWidth > 0 &&
                    el.offsetHeight > 0;
            });
        },
        /**
         * Imposta il focus iniziale
         */
        setInitialFocus(modal) {
            // Cerca un elemento con autofocus
            let targetElement = modal.querySelector('[autofocus]');
            // Se non c'è autofocus, cerca il primo input o button
            if (!targetElement) {
                targetElement = modal.querySelector('input, textarea, select, button');
            }
            // Se non ci sono input, prendi il primo elemento focalizzabile
            if (!targetElement) {
                const focusableElements = this.getFocusableElements(modal);
                targetElement = focusableElements[0];
            }
            // Come ultimo resort, focalizza il modal stesso
            if (!targetElement) {
                modal.setAttribute('tabindex', '-1');
                targetElement = modal;
            }
            setTimeout(() => {
                if (targetElement) {
                    targetElement.focus();
                }
            }, 100);
        },
        /**
         * Ripristina il focus
         */
        restoreFocus() {
            if (this.state.lastFocusedElement) {
                setTimeout(() => {
                    this.state.lastFocusedElement.focus();
                    this.state.lastFocusedElement = null;
                }, 100);
            }
        },
        /**
         * Blocca lo scroll del body
         */
        lockBodyScroll() {
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = this.getScrollbarWidth() + 'px';
        },
        /**
         * Sblocca lo scroll del body
         */
        unlockBodyScroll() {
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            window.scrollTo(0, this.state.scrollPosition);
        },
        /**
         * Calcola la larghezza della scrollbar
         */
        getScrollbarWidth() {
            const outer = document.createElement('div');
            outer.style.visibility = 'hidden';
            outer.style.overflow = 'scroll';
            document.body.appendChild(outer);
            const inner = document.createElement('div');
            outer.appendChild(inner);
            const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
            outer.parentNode.removeChild(outer);
            return scrollbarWidth;
        },
        /**
         * Ottiene il modal attualmente aperto
         */
        getCurrentModal() {
            return this.state.openModals[this.state.openModals.length - 1] || null;
        },
        /**
         * Callback quando un modal viene aperto
         */
        onModalOpened(modal) {
            if (!this.state.openModals.includes(modal)) {
                this.prepareModal(modal);
                this.state.openModals.push(modal);
                if (this.state.openModals.length === 1) {
                    this.state.lastFocusedElement = document.activeElement;
                    this.state.scrollPosition = window.pageYOffset;
                    this.lockBodyScroll();
                }
                this.setInitialFocus(modal);
            }
        },
        /**
         * Callback quando un modal viene chiuso
         */
        onModalClosed(modal) {
            const index = this.state.openModals.indexOf(modal);
            if (index > -1) {
                this.state.openModals.splice(index, 1);
                if (this.state.openModals.length === 0) {
                    this.unlockBodyScroll();
                    this.restoreFocus();
                }
            }
        },
        /**
         * Genera un ID unico
         */
        generateId(prefix) {
            return `${prefix}-${Math.random().toString(36).substr(2, 9)}`;
        },
        /**
         * API pubblica per aprire modal
         */
        open(modalId) {
            this.openModal(modalId);
        },
        /**
         * API pubblica per chiudere modal
         */
        close(modal) {
            if (typeof modal === 'string') {
                modal = document.getElementById(modal);
            }
            this.closeModal(modal);
        },
        /**
         * API pubblica per chiudere tutti i modal
         */
        closeAll() {
            [...this.state.openModals].forEach(modal => {
                this.closeModal(modal);
            });
        }
    };
    // Inizializzazione automatica
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ModalAccessibility.init();
        });
    } else {
        ModalAccessibility.init();
    }
    // Esporta il modal accessibility manager
    window.BOSTARTERModal = ModalAccessibility;
})(window, document);

