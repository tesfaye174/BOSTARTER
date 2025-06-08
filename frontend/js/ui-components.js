/* ===== BOSTARTER UI COMPONENTS MANAGER ===== */
/* Modern component system with enhanced interactivity */

class UIComponentsManager {
    constructor() {
        this.components = new Map();
        this.initialized = false;
        this.themeState = {
            current: localStorage.getItem('bostarter-theme') || 'light',
            available: ['light', 'dark', 'auto']
        };

        this.init();
    }

    init() {
        if (this.initialized) return;

        // Initialize all components
        this.setupThemeToggle();
        this.setupNavigation();
        this.setupModals();
        this.setupDropdowns();
        this.setupTabs();
        this.setupAccordions();
        this.setupTooltips();
        this.setupCarousels();
        this.setupFilters();
        this.setupSearch();
        this.setupInfiniteScroll(); this.initialized = true;
    }

    // ===== THEME SYSTEM =====
    setupThemeToggle() {
        const themeToggle = document.querySelector('.theme-toggle');
        if (!themeToggle) return;

        // Apply saved theme
        this.applyTheme(this.themeState.current);

        themeToggle.addEventListener('click', () => {
            this.cycleTheme();
        });

        // Listen for system theme changes
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            if (this.themeState.current === 'auto') {
                this.applyTheme('auto');
            }
        });
    }

    cycleTheme() {
        const currentIndex = this.themeState.available.indexOf(this.themeState.current);
        const nextIndex = (currentIndex + 1) % this.themeState.available.length;
        const nextTheme = this.themeState.available[nextIndex];

        this.themeState.current = nextTheme;
        this.applyTheme(nextTheme);
        localStorage.setItem('bostarter-theme', nextTheme);

        // Show theme change notification
        const themeNames = {
            light: 'Tema Chiaro',
            dark: 'Tema Scuro',
            auto: 'Automatico'
        };

        window.showNotification(`Tema cambiato: ${themeNames[nextTheme]}`, 'info', 2000);
    }

    applyTheme(theme) {
        const root = document.documentElement;
        root.className = root.className.replace(/theme-\w+/g, '');

        if (theme === 'dark') {
            root.classList.add('dark');
        } else if (theme === 'auto') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                root.classList.add('dark');
            }
        }

        // Update theme toggle icon
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            const icons = {
                light: '🌞',
                dark: '🌙',
                auto: '🔄'
            };
            themeToggle.textContent = icons[theme] || icons.light;
        }
    }

    // ===== NAVIGATION =====
    setupNavigation() {
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mobileMenu = document.querySelector('.mobile-menu');
        const navLinks = document.querySelectorAll('.nav-link');

        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', () => {
                this.toggleMobileMenu(mobileMenu);
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!mobileMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    this.closeMobileMenu(mobileMenu);
                }
            });
        }

        // Active link highlighting
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });

        // Scroll-based navigation highlight
        this.setupScrollNavigation();
    }

    toggleMobileMenu(menu) {
        const isOpen = menu.classList.contains('show');

        if (isOpen) {
            this.closeMobileMenu(menu);
        } else {
            this.openMobileMenu(menu);
        }
    }

    openMobileMenu(menu) {
        menu.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Focus first link
        const firstLink = menu.querySelector('a');
        if (firstLink) {
            firstLink.focus();
        }
    }

    closeMobileMenu(menu) {
        menu.classList.remove('show');
        document.body.style.overflow = '';
    }

    setupScrollNavigation() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link[href^="#"]');

        if (sections.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const id = entry.target.getAttribute('id');
                const navLink = document.querySelector(`.nav-link[href="#${id}"]`);

                if (entry.isIntersecting && navLink) {
                    navLinks.forEach(link => link.classList.remove('active'));
                    navLink.classList.add('active');
                }
            });
        }, {
            rootMargin: '-20% 0px -70% 0px'
        });

        sections.forEach(section => {
            observer.observe(section);
        });
    }

    // ===== MODALS =====
    setupModals() {
        const modalTriggers = document.querySelectorAll('[data-modal]');
        const modals = document.querySelectorAll('.modal-overlay');

        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.modal;
                const modal = document.querySelector(`#${modalId}`);
                if (modal) {
                    this.openModal(modal);
                }
            });
        });

        modals.forEach(modal => {
            // Close on overlay click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });

            // Close button
            const closeBtn = modal.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeModal(modal);
                });
            }
        });
    }

    openModal(modal) {
        if (window.animationManager) {
            window.animationManager.showModal(modal);
        } else {
            modal.classList.remove('hidden');
            modal.classList.add('show');
        }

        document.body.style.overflow = 'hidden';

        // Emit custom event
        modal.dispatchEvent(new CustomEvent('modal:open'));
    }

    closeModal(modal) {
        if (window.animationManager) {
            window.animationManager.hideModal(modal);
        } else {
            modal.classList.remove('show');
            modal.classList.add('hidden');
        }

        document.body.style.overflow = '';

        // Emit custom event
        modal.dispatchEvent(new CustomEvent('modal:close'));
    }

    // ===== DROPDOWNS =====
    setupDropdowns() {
        const dropdownTriggers = document.querySelectorAll('[data-dropdown]');

        dropdownTriggers.forEach(trigger => {
            const dropdownId = trigger.dataset.dropdown;
            const dropdown = document.querySelector(`#${dropdownId}`);

            if (!dropdown) return;

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown(dropdown);
            });

            // Close on click outside
            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
                    this.closeDropdown(dropdown);
                }
            });
        });
    }

    toggleDropdown(dropdown) {
        const isOpen = !dropdown.classList.contains('hidden');

        // Close all other dropdowns
        document.querySelectorAll('.dropdown').forEach(d => {
            if (d !== dropdown) {
                this.closeDropdown(d);
            }
        });

        if (isOpen) {
            this.closeDropdown(dropdown);
        } else {
            this.openDropdown(dropdown);
        }
    }

    openDropdown(dropdown) {
        dropdown.classList.remove('hidden');
        dropdown.style.opacity = '0';
        dropdown.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            dropdown.style.opacity = '1';
            dropdown.style.transform = 'translateY(0)';
        }, 10);
    }

    closeDropdown(dropdown) {
        dropdown.style.opacity = '0';
        dropdown.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            dropdown.classList.add('hidden');
        }, 200);
    }

    // ===== TABS =====
    setupTabs() {
        const tabGroups = document.querySelectorAll('.tab-group');

        tabGroups.forEach(group => {
            const tabs = group.querySelectorAll('.tab-button');
            const panels = group.querySelectorAll('.tab-panel');

            tabs.forEach((tab, index) => {
                tab.addEventListener('click', () => {
                    // Remove active from all tabs and panels
                    tabs.forEach(t => t.classList.remove('active'));
                    panels.forEach(p => p.classList.remove('active'));

                    // Add active to clicked tab and corresponding panel
                    tab.classList.add('active');
                    if (panels[index]) {
                        panels[index].classList.add('active');
                    }

                    // Emit custom event
                    group.dispatchEvent(new CustomEvent('tab:change', {
                        detail: { index, tab, panel: panels[index] }
                    }));
                });
            });
        });
    }

    // ===== ACCORDIONS =====
    setupAccordions() {
        const accordions = document.querySelectorAll('.accordion');

        accordions.forEach(accordion => {
            const items = accordion.querySelectorAll('.accordion-item');

            items.forEach(item => {
                const trigger = item.querySelector('.accordion-trigger');
                const content = item.querySelector('.accordion-content');

                if (!trigger || !content) return;

                trigger.addEventListener('click', () => {
                    const isOpen = item.classList.contains('open');

                    // Close all other items (if single-open accordion)
                    if (accordion.dataset.single === 'true') {
                        items.forEach(i => {
                            if (i !== item) {
                                this.closeAccordionItem(i);
                            }
                        });
                    }

                    if (isOpen) {
                        this.closeAccordionItem(item);
                    } else {
                        this.openAccordionItem(item);
                    }
                });
            });
        });
    }

    openAccordionItem(item) {
        const content = item.querySelector('.accordion-content');
        if (!content) return;

        item.classList.add('open');
        content.style.maxHeight = content.scrollHeight + 'px';
    }

    closeAccordionItem(item) {
        const content = item.querySelector('.accordion-content');
        if (!content) return;

        item.classList.remove('open');
        content.style.maxHeight = '0';
    }

    // ===== TOOLTIPS =====
    setupTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');

        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target);
            });

            element.addEventListener('mouseleave', (e) => {
                this.hideTooltip(e.target);
            });
        });
    }

    showTooltip(element) {
        const text = element.dataset.tooltip;
        const position = element.dataset.tooltipPosition || 'top';

        if (!text) return;

        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: var(--gray-900);
            color: white;
            padding: 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            white-space: nowrap;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        `;

        document.body.appendChild(tooltip);

        // Position tooltip
        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        let top, left;

        switch (position) {
            case 'top':
                top = rect.top - tooltipRect.height - 8;
                left = rect.left + (rect.width - tooltipRect.width) / 2;
                break;
            case 'bottom':
                top = rect.bottom + 8;
                left = rect.left + (rect.width - tooltipRect.width) / 2;
                break;
            case 'left':
                top = rect.top + (rect.height - tooltipRect.height) / 2;
                left = rect.left - tooltipRect.width - 8;
                break;
            case 'right':
                top = rect.top + (rect.height - tooltipRect.height) / 2;
                left = rect.right + 8;
                break;
        }

        tooltip.style.top = top + window.scrollY + 'px';
        tooltip.style.left = left + 'px';

        setTimeout(() => {
            tooltip.style.opacity = '1';
        }, 10);

        element._tooltip = tooltip;
    }

    hideTooltip(element) {
        if (element._tooltip) {
            element._tooltip.style.opacity = '0';
            setTimeout(() => {
                if (element._tooltip && element._tooltip.parentElement) {
                    element._tooltip.remove();
                }
                delete element._tooltip;
            }, 200);
        }
    }

    // ===== SEARCH FUNCTIONALITY =====
    setupSearch() {
        const searchInputs = document.querySelectorAll('.search-input');

        searchInputs.forEach(input => {
            let searchTimeout;

            input.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);

                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value, input);
                }, 300);
            });

            // Clear search
            const clearBtn = input.parentElement.querySelector('.search-clear');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    input.value = '';
                    this.performSearch('', input);
                    input.focus();
                });
            }
        });
    }

    performSearch(query, input) {
        const target = input.dataset.searchTarget;
        const items = document.querySelectorAll(target);

        if (!target || !items.length) return;

        const searchQuery = query.toLowerCase().trim();

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const matches = !searchQuery || text.includes(searchQuery);

            item.style.display = matches ? '' : 'none';

            if (matches && searchQuery) {
                this.highlightSearchTerm(item, searchQuery);
            } else {
                this.removeHighlight(item);
            }
        });

        // Emit search event
        input.dispatchEvent(new CustomEvent('search:performed', {
            detail: { query, results: Array.from(items).filter(item => item.style.display !== 'none') }
        }));
    }

    highlightSearchTerm(element, term) {
        // Simple highlight implementation
        const originalHTML = element.dataset.originalHTML || element.innerHTML;
        element.dataset.originalHTML = originalHTML;

        const regex = new RegExp(`(${term})`, 'gi');
        const highlightedHTML = originalHTML.replace(regex, '<mark>$1</mark>');
        element.innerHTML = highlightedHTML;
    }

    removeHighlight(element) {
        if (element.dataset.originalHTML) {
            element.innerHTML = element.dataset.originalHTML;
            delete element.dataset.originalHTML;
        }
    }

    // ===== INFINITE SCROLL =====
    setupInfiniteScroll() {
        const infiniteContainers = document.querySelectorAll('[data-infinite-scroll]');

        infiniteContainers.forEach(container => {
            const loadMoreBtn = container.querySelector('.load-more-btn');
            const loader = container.querySelector('.infinite-loader');

            if (container.dataset.infiniteScroll === 'auto') {
                this.setupAutoInfiniteScroll(container, loader);
            } else if (loadMoreBtn) {
                this.setupManualInfiniteScroll(container, loadMoreBtn, loader);
            }
        });
    }

    setupAutoInfiniteScroll(container, loader) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadMoreContent(container);
                }
            });
        }, {
            rootMargin: '100px'
        });

        if (loader) {
            observer.observe(loader);
        }
    }

    setupManualInfiniteScroll(container, loadMoreBtn, loader) {
        loadMoreBtn.addEventListener('click', () => {
            this.loadMoreContent(container);
        });
    }

    async loadMoreContent(container) {
        const endpoint = container.dataset.endpoint;
        const page = parseInt(container.dataset.page || '1');

        if (!endpoint) return;

        try {
            // Show loader
            const loader = container.querySelector('.infinite-loader');
            if (loader) {
                loader.style.display = 'block';
            }

            const response = await fetch(`${endpoint}?page=${page + 1}`);
            const data = await response.json();

            if (data.success && data.content) {
                // Add new content
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.content;

                const newItems = tempDiv.children;
                const targetContainer = container.querySelector('.infinite-content') || container;

                Array.from(newItems).forEach(item => {
                    targetContainer.appendChild(item);

                    // Animate new items
                    if (window.animationManager) {
                        item.style.opacity = '0';
                        item.style.transform = 'translateY(20px)';

                        setTimeout(() => {
                            item.style.transition = 'all 0.5s ease';
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                        }, 50);
                    }
                });

                // Update page counter
                container.dataset.page = (page + 1).toString();

                // Hide load more if no more content
                if (!data.hasMore) {
                    const loadMoreBtn = container.querySelector('.load-more-btn');
                    if (loadMoreBtn) {
                        loadMoreBtn.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            window.showNotification('Errore nel caricamento contenuti', 'error');
        } finally {
            // Hide loader
            const loader = container.querySelector('.infinite-loader');
            if (loader) {
                loader.style.display = 'none';
            }
        }
    }

    // ===== CLEANUP =====
    destroy() {
        // Clean up event listeners and observers
        this.components.clear();
    }
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    window.uiComponentsManager = new UIComponentsManager();
});

// ===== EXPORT =====
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UIComponentsManager;
}
