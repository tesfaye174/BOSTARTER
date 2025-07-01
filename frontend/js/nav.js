/**
 * BOSTARTER - Navigation Manager
 * Gestione della navigazione, sidebar e mobile menu
 */
(function (window, document) {
    'use strict';
    const NavigationManager = {
        // Elementi DOM
        elements: {
            sidebar: null,
            mobileToggle: null,
            overlay: null,
            navLinks: null
        },
        // Stato
        state: {
            sidebarOpen: false,
            isMobile: false
        },
        /**
         * Inizializza il navigation manager
         */
        init() {
            this.findElements();
            this.detectMobile();
            this.setupEventListeners();
            this.setupKeyboardNavigation();
            this.createMobileElements();
        },
        /**
         * Trova gli elementi nel DOM
         */
        findElements() {
            this.elements.sidebar = document.querySelector('.sidebar, .navigation-sidebar, [data-sidebar]');
            this.elements.mobileToggle = document.querySelector('.mobile-toggle, .nav-toggle, [data-mobile-toggle]');
            this.elements.overlay = document.querySelector('.sidebar-overlay, [data-sidebar-overlay]');
            this.elements.navLinks = document.querySelectorAll('.nav-link, .sidebar-link, [data-nav-link]');
        },
        /**
         * Rileva se siamo su mobile
         */
        detectMobile() {
            this.state.isMobile = window.innerWidth < 768;
        },
        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Resize listener
            window.addEventListener('resize', this.debounce(() => {
                this.detectMobile();
                this.handleResize();
            }, 250));
            // Mobile toggle
            if (this.elements.mobileToggle) {
                this.elements.mobileToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleSidebar();
                });
            }
            // Overlay click
            if (this.elements.overlay) {
                this.elements.overlay.addEventListener('click', () => {
                    this.closeSidebar();
                });
            }
            // Navigation links
            this.elements.navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    this.handleNavClick(e, link);
                });
            });
            // ESC key per chiudere sidebar
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.state.sidebarOpen) {
                    this.closeSidebar();
                }
            });
        },
        /**
         * Setup navigazione da tastiera
         */
        setupKeyboardNavigation() {
            this.elements.navLinks.forEach((link, index) => {
                link.addEventListener('keydown', (e) => {
                    const isFirst = index === 0;
                    const isLast = index === this.elements.navLinks.length - 1;
                    switch (e.key) {
                        case 'ArrowDown':
                            e.preventDefault();
                            if (!isLast) {
                                this.elements.navLinks[index + 1].focus();
                            }
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            if (!isFirst) {
                                this.elements.navLinks[index - 1].focus();
                            }
                            break;
                        case 'Home':
                            e.preventDefault();
                            this.elements.navLinks[0].focus();
                            break;
                        case 'End':
                            e.preventDefault();
                            this.elements.navLinks[this.elements.navLinks.length - 1].focus();
                            break;
                    }
                });
            });
        },
        /**
         * Crea elementi mobile se non esistono
         */
        createMobileElements() {
            // Crea toggle mobile se non esiste
            if (!this.elements.mobileToggle && this.elements.sidebar) {
                this.createMobileToggle();
            }
            // Crea overlay se non esiste
            if (!this.elements.overlay && this.elements.sidebar) {
                this.createOverlay();
            }
        },
        /**
         * Crea il toggle mobile
         */
        createMobileToggle() {
            const toggle = document.createElement('button');
            toggle.className = 'mobile-toggle';
            toggle.setAttribute('aria-label', 'Toggle navigation');
            toggle.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            `;
            // Stile inline
            const style = document.createElement('style');
            style.textContent = `
                .mobile-toggle {
                    display: none;
                    position: fixed;
                    top: 20px;
                    left: 20px;
                    z-index: 1001;
                    background: var(--bg-primary, #fff);
                    border: 1px solid var(--border-color, #e5e7eb);
                    border-radius: 8px;
                    width: 44px;
                    height: 44px;
                    cursor: pointer;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                @media (max-width: 767px) {
                    .mobile-toggle {
                        display: flex;
                    }
                }
            `;
            document.head.appendChild(style);
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSidebar();
            });
            document.body.appendChild(toggle);
            this.elements.mobileToggle = toggle;
        },
        /**
         * Crea l'overlay
         */
        createOverlay() {
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            const style = document.createElement('style');
            style.textContent = `
                .sidebar-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 999;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s ease;
                }
                .sidebar-overlay.active {
                    opacity: 1;
                    visibility: visible;
                }
            `;
            document.head.appendChild(style);
            overlay.addEventListener('click', () => {
                this.closeSidebar();
            });
            document.body.appendChild(overlay);
            this.elements.overlay = overlay;
        },
        /**
         * Toggle sidebar
         */
        toggleSidebar() {
            if (this.state.sidebarOpen) {
                this.closeSidebar();
            } else {
                this.openSidebar();
            }
        },
        /**
         * Apri sidebar
         */
        openSidebar() {
            this.state.sidebarOpen = true;
            if (this.elements.sidebar) {
                this.elements.sidebar.classList.add('open', 'active');
            }
            if (this.elements.overlay) {
                this.elements.overlay.classList.add('active');
            }
            if (this.elements.mobileToggle) {
                this.elements.mobileToggle.setAttribute('aria-expanded', 'true');
            }
            // Impedisci scroll del body su mobile
            if (this.state.isMobile) {
                document.body.style.overflow = 'hidden';
            }
            // Focus management
            if (this.elements.navLinks.length > 0) {
                this.elements.navLinks[0].focus();
            }
        },
        /**
         * Chiudi sidebar
         */
        closeSidebar() {
            this.state.sidebarOpen = false;
            if (this.elements.sidebar) {
                this.elements.sidebar.classList.remove('open', 'active');
            }
            if (this.elements.overlay) {
                this.elements.overlay.classList.remove('active');
            }
            if (this.elements.mobileToggle) {
                this.elements.mobileToggle.setAttribute('aria-expanded', 'false');
            }
            // Ripristina scroll del body
            document.body.style.overflow = '';
            // Focus management
            if (this.elements.mobileToggle) {
                this.elements.mobileToggle.focus();
            }
        },
        /**
         * Gestisci click sui link di navigazione
         */
        handleNavClick(e, link) {
            // Rimuovi classe active da tutti i link
            this.elements.navLinks.forEach(l => {
                l.classList.remove('active', 'current');
            });
            // Aggiungi classe active al link corrente
            link.classList.add('active', 'current');
            // Chiudi sidebar su mobile dopo click
            if (this.state.isMobile && this.state.sidebarOpen) {
                // Delay per permettere la navigazione
                setTimeout(() => {
                    this.closeSidebar();
                }, 150);
            }
        },
        /**
         * Gestisci resize
         */
        handleResize() {
            // Chiudi sidebar se passiamo da mobile a desktop
            if (!this.state.isMobile && this.state.sidebarOpen) {
                this.closeSidebar();
            }
        },
        /**
         * Utility debounce
         */
        debounce(func, wait) {
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
    };
    // Inizializzazione automatica
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            NavigationManager.init();
        });
    } else {
        NavigationManager.init();
    }
    // Esporta il navigation manager
    window.BOSTARTERNavigation = NavigationManager;
})(window, document);

