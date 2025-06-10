/**
 * BOSTARTER - Sistema di Navigazione Avanzato
 * Gestisce la navigazione moderna con menu mobile, dropdown utente e cambio tema
 * Include animazioni fluide e accessibilità completa
 */

(function () {
    'use strict';

    /**
     * Inizializza il menu mobile con animazioni e interazioni
     */
    function inizializzaMenuMobile() {
        const toggleMobile = document.getElementById('mobile-menu-toggle');
        const menuMobile = document.getElementById('mobile-menu');

        if (!toggleMobile || !menuMobile) return;

        // Listener per il click sul toggle del menu
        toggleMobile.addEventListener('click', function (evento) {
            evento.preventDefault();
            commutaMenuMobile();
        });

        /**
         * Commuta lo stato aperto/chiuso del menu mobile
         */
        function commutaMenuMobile() {
            const eAperto = menuMobile.classList.contains('show');

            if (eAperto) {
                chiudiMenuMobile();
            } else {
                apriMenuMobile();
            }
        }

        /**
         * Apre il menu mobile con animazione
         */
        function apriMenuMobile() {
            menuMobile.style.maxHeight = '500px';
            menuMobile.classList.add('show');
            toggleMobile.classList.add('active');
            toggleMobile.setAttribute('aria-expanded', 'true');

            // Animiamo l'icona hamburger
            animaHamburger(true);
        }

        /**
         * Chiude il menu mobile con animazione
         */
        function chiudiMenuMobile() {
            menuMobile.style.maxHeight = '0';
            menuMobile.classList.remove('show');
            toggleMobile.classList.remove('active');
            toggleMobile.setAttribute('aria-expanded', 'false');

            // Animiamo l'icona hamburger
            animaHamburger(false);
        }        /**
         * Anima l'icona hamburger trasformandola in X
         */
        function animaHamburger(eAperto) {
            const lineette = toggleMobile.querySelectorAll('span');
            if (lineette.length === 3) {
                if (eAperto) {
                    // Trasformiamo le lineette in una X
                    lineette[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                    lineette[1].style.opacity = '0';
                    lineette[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
                } else {
                    // Ripristiniamo le lineette normali
                    lineette[0].style.transform = 'rotate(0deg) translate(0, 0)';
                    lineette[1].style.opacity = '1';
                    lineette[2].style.transform = 'rotate(0deg) translate(0, 0)';
                }
            }
        }

        // Chiudiamo il menu quando si clicca su un link
        const linkMobili = menuMobile.querySelectorAll('a');
        linkMobili.forEach(function (link) {
            link.addEventListener('click', chiudiMenuMobile);
        });

        // Chiudiamo il menu quando si clicca fuori
        document.addEventListener('click', function (evento) {
            if (!menuMobile.contains(evento.target) && !toggleMobile.contains(evento.target)) {
                chiudiMenuMobile();
            }
        });

        // Chiudiamo il menu con il tasto ESC
        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && menuMobile.classList.contains('show')) {
                chiudiMenuMobile();
            }
        });
    }

    // User Menu functionality
    function initUserMenu() {
        const userButton = document.getElementById('user-menu-button');
        const userDropdown = document.getElementById('user-menu-dropdown');

        if (!userButton || !userDropdown) return;

        userButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleUserMenu();
        });

        function toggleUserMenu() {
            const isOpen = userDropdown.style.opacity === '1';

            if (isOpen) {
                closeUserMenu();
            } else {
                openUserMenu();
            }
        }

        function openUserMenu() {
            userDropdown.style.opacity = '1';
            userDropdown.style.visibility = 'visible';
            userDropdown.style.transform = 'translateY(0)';
            userButton.setAttribute('aria-expanded', 'true');
        }

        function closeUserMenu() {
            userDropdown.style.opacity = '0';
            userDropdown.style.visibility = 'hidden';
            userDropdown.style.transform = 'translateY(-10px)';
            userButton.setAttribute('aria-expanded', 'false');
        }

        // Close on outside click
        document.addEventListener('click', function (e) {
            if (!userButton.contains(e.target) && !userDropdown.contains(e.target)) {
                closeUserMenu();
            }
        });

        // Close on ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && userDropdown.style.opacity === '1') {
                closeUserMenu();
                userButton.focus();
            }
        });

        // Enhanced hover effects
        const userContainer = document.querySelector('.user-menu-container-enhanced');
        if (userContainer) {
            let hoverTimeout;

            userContainer.addEventListener('mouseenter', function () {
                clearTimeout(hoverTimeout);
                openUserMenu();
            });

            userContainer.addEventListener('mouseleave', function () {
                hoverTimeout = setTimeout(closeUserMenu, 300);
            });
        }
    }

    // Theme Toggle functionality
    function initThemeToggle() {
        const themeToggle = document.getElementById('theme-toggle');
        const mobileThemeToggle = document.getElementById('mobile-theme-toggle');

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', newTheme);
            document.body.classList.toggle('dark', newTheme === 'dark');

            // Save to localStorage
            localStorage.setItem('theme', newTheme);

            // Update icons
            updateThemeIcons(newTheme);

            // Dispatch theme change event
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
        }

        function updateThemeIcons(theme) {
            const sunIcons = document.querySelectorAll('.ri-sun-line');
            const moonIcons = document.querySelectorAll('.ri-moon-line');

            if (theme === 'dark') {
                sunIcons.forEach(function (icon) { icon.classList.add('hidden'); });
                moonIcons.forEach(function (icon) { icon.classList.remove('hidden'); });
            } else {
                sunIcons.forEach(function (icon) { icon.classList.remove('hidden'); });
                moonIcons.forEach(function (icon) { icon.classList.add('hidden'); });
            }
        }

        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.body.classList.toggle('dark', savedTheme === 'dark');
            updateThemeIcons(savedTheme);
        }

        // Add event listeners
        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
        }

        if (mobileThemeToggle) {
            mobileThemeToggle.addEventListener('click', toggleTheme);
        }

        // Initialize theme on load
        initializeTheme();
    }

    // Header scroll effects
    function initScrollEffects() {
        const header = document.querySelector('.modern-header') || document.querySelector('.header-sticky');
        if (!header) return;

        let lastScrollY = 0;
        let ticking = false;

        function updateHeader() {
            const scrollY = window.scrollY;

            if (scrollY > 50) {
                header.classList.add('header-scrolled');
            } else {
                header.classList.remove('header-scrolled');
            }

            // Hide/show header on scroll
            if (scrollY > lastScrollY && scrollY > 100) {
                header.style.transform = 'translateY(-100%)';
            } else {
                header.style.transform = 'translateY(0)';
            }

            lastScrollY = scrollY;
            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateHeader);
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestTick, { passive: true });
    }

    // Notifications system
    function initNotifications() {
        let container = document.getElementById('notifications-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2 max-w-sm';
            container.setAttribute('role', 'region');
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-label', 'Notifiche');
            document.body.appendChild(container);
        }

        const notifications = new Map();
        const maxNotifications = 5;

        function showNotification(message, type, duration) {
            type = type || 'info';
            duration = duration || 5000;

            const id = Date.now().toString(36) + Math.random().toString(36).substr(2);
            const notification = createNotification(id, message, type);

            container.appendChild(notification);
            notifications.set(id, notification);

            // Trigger entrance animation
            setTimeout(function () {
                notification.classList.add('show');
            }, 10);

            // Auto remove
            if (duration > 0) {
                setTimeout(function () {
                    removeNotification(id);
                }, duration);
            }

            // Remove oldest if too many
            if (notifications.size > maxNotifications) {
                const oldestId = notifications.keys().next().value;
                removeNotification(oldestId);
            }

            return id;
        }

        function createNotification(id, message, type) {
            const notification = document.createElement('div');
            notification.id = 'notification-' + id;
            notification.className = 'notification notification-' + type + ' transform translate-x-full opacity-0 transition-all duration-300 bg-white rounded-lg shadow-lg p-4 border-l-4';

            const borderColors = {
                success: 'border-green-500',
                error: 'border-red-500',
                warning: 'border-yellow-500',
                info: 'border-blue-500'
            };

            const icons = {
                success: 'fas fa-check-circle text-green-500',
                error: 'fas fa-exclamation-circle text-red-500',
                warning: 'fas fa-exclamation-triangle text-yellow-500',
                info: 'fas fa-info-circle text-blue-500'
            };

            notification.classList.add(borderColors[type]);

            notification.innerHTML =
                '<div class="flex items-start">' +
                '<div class="flex-shrink-0">' +
                '<i class="' + icons[type] + ' text-lg" aria-hidden="true"></i>' +
                '</div>' +
                '<div class="ml-3 flex-1">' +
                '<p class="text-sm font-medium text-gray-900">' + message + '</p>' +
                '</div>' +
                '<div class="ml-4 flex-shrink-0">' +
                '<button type="button" class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded" onclick="window.boNotifications.remove(\'' + id + '\')">' +
                '<i class="fas fa-times" aria-hidden="true"></i>' +
                '<span class="sr-only">Chiudi notifica</span>' +
                '</button>' +
                '</div>' +
                '</div>';

            return notification;
        }

        function removeNotification(id) {
            const notification = notifications.get(id);
            if (notification) {
                notification.classList.add('opacity-0', 'translate-x-full');
                setTimeout(function () {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                    notifications.delete(id);
                }, 300);
            }
        }

        // Expose notification methods
        window.boNotifications = {
            show: showNotification,
            remove: removeNotification,
            success: function (message, duration) { return showNotification(message, 'success', duration); },
            error: function (message, duration) { return showNotification(message, 'error', duration); },
            warning: function (message, duration) { return showNotification(message, 'warning', duration); },
            info: function (message, duration) { return showNotification(message, 'info', duration); }
        };
    }

    // Form enhancements
    function initFormEnhancements() {
        const forms = document.querySelectorAll('form');
        forms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.textContent;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Caricamento...';

                    // Re-enable after 3 seconds if page doesn't change
                    setTimeout(function () {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    }, 3000);
                }
            });
        });
    }

    // Smooth scrolling for anchor links
    function initSmoothScrolling() {
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        anchorLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                const targetId = this.getAttribute('href').substring(1);
                const target = document.getElementById(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Initialize everything when DOM is ready
    function init() {
        initMobileMenu();
        initUserMenu();
        initThemeToggle();
        initScrollEffects();
        initNotifications();
        initFormEnhancements();
        initSmoothScrolling();

        // Loading overlay management
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            window.addEventListener('load', function () {
                setTimeout(function () {
                    loadingOverlay.style.opacity = '0';
                    setTimeout(function () {
                        loadingOverlay.style.display = 'none';
                    }, 300);
                }, 500);
            });
        }

        // Console welcome message
        console.log('%c🚀 BOSTARTER Navigation System loaded successfully!', 'color: #3176FF; font-size: 16px; font-weight: bold;');
    }

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
const spans = this.mobileMenuToggle.querySelectorAll('span');
if (spans.length === 3) {
    if (isOpen) {
        spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
        spans[1].style.opacity = '0';
        spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
    } else {
        spans[0].style.transform = 'rotate(0deg) translate(0, 0)';
        spans[1].style.opacity = '1';
        spans[2].style.transform = 'rotate(0deg) translate(0, 0)';
    }
}
    }

setupUserMenu() {
    if (!this.userMenuButton || !this.userMenuDropdown) return;

    // Toggle user menu on click
    this.userMenuButton.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.toggleUserMenu();
    });

    // Close user menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!this.userMenuButton.contains(e.target) && !this.userMenuDropdown.contains(e.target)) {
            this.closeUserMenu();
        }
    });

    // Handle escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.isUserMenuOpen()) {
            this.closeUserMenu();
            this.userMenuButton.focus();
        }
    });

    // Enhanced hover effects
    const userMenuContainer = document.querySelector('.user-menu-container-enhanced');
    if (userMenuContainer) {
        let hoverTimeout;

        userMenuContainer.addEventListener('mouseenter', () => {
            clearTimeout(hoverTimeout);
            this.openUserMenu();
        });

        userMenuContainer.addEventListener('mouseleave', () => {
            hoverTimeout = setTimeout(() => {
                this.closeUserMenu();
            }, 300);
        });
    }
}

toggleUserMenu() {
    if (this.isUserMenuOpen()) {
        this.closeUserMenu();
    } else {
        this.openUserMenu();
    }
}

openUserMenu() {
    this.userMenuDropdown.style.opacity = '1';
    this.userMenuDropdown.style.visibility = 'visible';
    this.userMenuDropdown.style.transform = 'translateY(0)';
    this.userMenuButton.setAttribute('aria-expanded', 'true');

    // Focus management
    const firstItem = this.userMenuDropdown.querySelector('a');
    if (firstItem) {
        setTimeout(() => firstItem.focus(), 100);
    }
}

closeUserMenu() {
    this.userMenuDropdown.style.opacity = '0';
    this.userMenuDropdown.style.visibility = 'hidden';
    this.userMenuDropdown.style.transform = 'translateY(-10px)';
    this.userMenuButton.setAttribute('aria-expanded', 'false');
}

isUserMenuOpen() {
    return this.userMenuDropdown.style.opacity === '1';
}

setupThemeToggle() {
    const toggleTheme = () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        document.body.classList.toggle('dark', newTheme === 'dark');

        // Save to localStorage
        localStorage.setItem('theme', newTheme);

        // Update icons
        this.updateThemeIcons(newTheme);

        // Dispatch theme change event
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
    };

    // Desktop theme toggle
    if (this.themeToggle) {
        this.themeToggle.addEventListener('click', toggleTheme);
    }

    // Mobile theme toggle
    if (this.mobileThemeToggle) {
        this.mobileThemeToggle.addEventListener('click', toggleTheme);
    }

    // Initialize theme
    this.initializeTheme();
}

initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    document.body.classList.toggle('dark', savedTheme === 'dark');
    this.updateThemeIcons(savedTheme);
}

updateThemeIcons(theme) {
    const sunIcons = document.querySelectorAll('.ri-sun-line');
    const moonIcons = document.querySelectorAll('.ri-moon-line');

    if (theme === 'dark') {
        sunIcons.forEach(icon => icon.classList.add('hidden'));
        moonIcons.forEach(icon => icon.classList.remove('hidden'));
    } else {
        sunIcons.forEach(icon => icon.classList.remove('hidden'));
        moonIcons.forEach(icon => icon.classList.add('hidden'));
    }
}

setupScrollEffects() {
    let lastScrollY = 0;
    let ticking = false;

    const updateHeader = () => {
        const scrollY = window.scrollY;

        if (scrollY > 50) {
            this.header?.classList.add('header-scrolled');
        } else {
            this.header?.classList.remove('header-scrolled');
        }

        // Hide/show header on scroll
        if (scrollY > lastScrollY && scrollY > 100) {
            this.header?.style.setProperty('transform', 'translateY(-100%)');
        } else {
            this.header?.style.setProperty('transform', 'translateY(0)');
        }

        lastScrollY = scrollY;
        ticking = false;
    };

    const requestTick = () => {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    };

    window.addEventListener('scroll', requestTick, { passive: true });
}

setupStickyHeader() {
    if (!this.header) return;

    // Add scroll listener for sticky effects
    window.addEventListener('scroll', () => {
        if (window.scrollY > 0) {
            this.header.classList.add('scrolled');
        } else {
            this.header.classList.remove('scrolled');
        }
    }, { passive: true });
}

setupAccessibility() {
    this.setupFocusTrap();
    this.setupSkipLinks();
    this.setupKeyboardNavigation();
}

setupFocusTrap() {
    // Focus trap per mobile menu
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Tab' && this.isMobileMenuOpen()) {
            const focusableElements = this.mobileMenu.querySelectorAll(
                'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    });
}

setupSkipLinks() {
    const skipLinks = document.querySelectorAll('.skip-link');
    skipLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = link.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            if (target) {
                target.focus();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

setupKeyboardNavigation() {
    // Supporto navigazione keyboard per dropdown
    document.addEventListener('keydown', (e) => {
        if (this.isUserMenuOpen()) {
            const menuItems = this.userMenuDropdown.querySelectorAll('a');
            const currentIndex = Array.from(menuItems).indexOf(document.activeElement);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = currentIndex < menuItems.length - 1 ? currentIndex + 1 : 0;
                menuItems[nextIndex].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : menuItems.length - 1;
                menuItems[prevIndex].focus();
            }
        }
    });
}

setupAnimations() {
    // Aggiungi animazioni di entrata per elementi header
    const navItems = document.querySelectorAll('.nav-link-enhanced');
    navItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.classList.add('animate-fade-in');
    });

    // Parallax effect leggero per il logo
    const logo = document.querySelector('.navbar-brand-enhanced');
    if (logo) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            logo.style.transform = `translateY(${scrolled * 0.1}px)`;
        }, { passive: true });
    }
}

setupNotifications() {
    // Setup notification bell animation
    const notificationBell = document.querySelector('[aria-label="Notifiche"]');
    if (notificationBell) {
        notificationBell.classList.add('notification-bell');

        // Add click handler for notifications
        notificationBell.addEventListener('click', () => {
            this.showNotifications();
        });
    }
}

showNotifications() {
    // This would integrate with your notification system
    console.log('Showing notifications...');

    // Example notification
    if (window.boNotifications) {
        window.boNotifications.info('Nessuna nuova notifica');
    }
}
}

/**
 * BOSTARTER Notification System
 */
class BOStarterNotifications {
    constructor() {
        this.container = document.getElementById('notifications-container');
        this.notifications = new Map();
        this.maxNotifications = 5;
        this.defaultDuration = 5000;

        this.init();
    }

    init() {
        if (!this.container) {
            this.createContainer();
        }
    }

    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notifications-container';
        this.container.className = 'fixed top-4 right-4 z-50 space-y-2 max-w-sm';
        this.container.setAttribute('role', 'region');
        this.container.setAttribute('aria-live', 'polite');
        this.container.setAttribute('aria-label', 'Notifiche');
        document.body.appendChild(this.container);
    }

    show(message, type = 'info', duration = this.defaultDuration) {
        const id = this.generateId();
        const notification = this.createNotification(id, message, type);

        this.container.appendChild(notification);
        this.notifications.set(id, notification);

        // Trigger entrance animation
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });

        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                this.remove(id);
            }, duration);
        }

        // Remove oldest if too many
        this.enforceMaxNotifications();

        return id;
    }

    createNotification(id, message, type) {
        const notification = document.createElement('div');
        notification.id = `notification-${id}`;
        notification.className = `notification notification-${type} transform translate-x-full opacity-0 transition-all duration-300 bg-white rounded-lg shadow-lg p-4 border-l-4`;

        const borderColors = {
            success: 'border-green-500',
            error: 'border-red-500',
            warning: 'border-yellow-500',
            info: 'border-blue-500'
        };

        const icons = {
            success: 'fas fa-check-circle text-green-500',
            error: 'fas fa-exclamation-circle text-red-500',
            warning: 'fas fa-exclamation-triangle text-yellow-500',
            info: 'fas fa-info-circle text-blue-500'
        };

        notification.classList.add(borderColors[type]);

        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="${icons[type]} text-lg" aria-hidden="true"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900">${message}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button type="button" class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded" onclick="window.boNotifications.remove('${id}')">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        <span class="sr-only">Chiudi notifica</span>
                    </button>
                </div>
            </div>
        `;

        return notification;
    }

    remove(id) {
        const notification = this.notifications.get(id);
        if (notification) {
            notification.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                this.notifications.delete(id);
            }, 300);
        }
    }

    success(message, duration) {
        return this.show(message, 'success', duration);
    }

    error(message, duration) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration) {
        return this.show(message, 'info', duration);
    }

    clear() {
        this.notifications.forEach((notification, id) => {
            this.remove(id);
        });
    }

    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }

    enforceMaxNotifications() {
        if (this.notifications.size > this.maxNotifications) {
            const oldestId = this.notifications.keys().next().value;
            this.remove(oldestId);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Inizializza navigazione
    window.boNavigation = new BOStarterNavigation();

    // Inizializza notifiche
    window.boNotifications = new BOStarterNotifications();

    // Loading overlay management
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        // Nascondi loading overlay dopo il caricamento
        window.addEventListener('load', () => {
            setTimeout(() => {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 300);
            }, 500);
        });
    }

    // Form enhancements
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                const originalText = submitBtn.textContent;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Caricamento...';

                // Re-abilita dopo 3 secondi se la pagina non cambia
                setTimeout(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                }, 3000);
            }
        });
    });

    // Smooth scrolling per anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Console welcome message
    console.log('%c🚀 BOSTARTER Frontend caricato con successo!', 'color: #3176FF; font-size: 16px; font-weight: bold;');
});

// Export per utilizzo globale
window.BOStarterNavigation = BOStarterNavigation;
window.BOStarterNotifications = BOStarterNotifications;
