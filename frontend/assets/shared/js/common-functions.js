/**
 * ===== JAVASCRIPT COMUNE CONDIVISO BOSTARTER =====
 * Funzioni riutilizzabili per tutte le categorie
 */

// Namespace globale per evitare conflitti
window.BostarterCommon = (function () {
    'use strict';

    /**
     * Helper function for date formatting - uses centralized Utils
     * @param {Date|string} date - Date to format
     * @returns {Object} Object with day and month
     */    function formatDate(date) {
        if (window.Utils && window.Utils.formatDate) {
            const dateObj = new Date(date);
            return {
                day: Utils.formatDate(dateObj, { day: 'numeric' }),
                month: Utils.formatDate(dateObj, { month: 'short' })
            };
        } else {
            // Fallback implementation
            const dateObj = new Date(date);
            return {
                day: dateObj.getDate(),
                month: dateObj.toLocaleDateString('it-IT', { month: 'short' })
            };
        }
    }

    /**
     * Gestione filtri unificata
     * @param {string} containerSelector - Selettore per i pulsanti filtro
     * @param {string} itemSelector - Selettore per gli elementi da filtrare
     * @param {string} categoryAttribute - Attributo per la categoria (default: data-category)
     */
    function initFilters(containerSelector = '.filters', itemSelector = '[data-category]', categoryAttribute = 'data-category') {
        const filterButtons = document.querySelectorAll(`${containerSelector} .filter-btn`);

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Rimuovi classe active da tutti i bottoni
                filterButtons.forEach(btn => btn.classList.remove('active'));

                // Aggiungi classe active al bottone cliccato
                button.classList.add('active');

                const filter = button.getAttribute('data-filter');
                const items = document.querySelectorAll(itemSelector);

                items.forEach(item => {
                    const category = item.getAttribute(categoryAttribute);

                    if (filter === 'tutti' || category === filter) {
                        item.style.display = 'block';
                        item.style.animation = 'fadeIn 0.5s ease-in';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    }

    /**
     * Factory per creare card standard
     * @param {Object} data - Dati per la card
     * @param {string} type - Tipo di card ('project', 'profile', 'event')
     */
    function createCard(data, type = 'project') {
        const templates = {
            project: (data) => `
                <article class="${data.cardClass || 'card'}" data-category="${data.category}">
                    <img src="${data.image}" alt="${data.title}" loading="lazy">
                    <div class="card-info">
                        <h3>${data.title}</h3>
                        ${data.author ? `<p class="author">${data.author}</p>` : ''}
                        <p>${data.description}</p>
                        ${data.meta ? `<div class="card-meta">${data.meta}</div>` : ''}
                    </div>
                </article>
            `,

            profile: (data) => `
                <div class="profile-card" data-category="${data.category || ''}">
                    <img src="${data.image}" alt="${data.name}">
                    <h3>${data.name}</h3>
                    ${data.specialty ? `<p class="specialty">${data.specialty}</p>` : ''}
                    ${data.bio ? `<p class="bio">${data.bio}</p>` : ''}
                </div>
            `,

            event: (data) => `
                <div class="event-card">
                    <div class="event-date">
                        <div class="day">${formatDate(data.date).day}</div>
                        <div class="month">${formatDate(data.date).month}</div>
                    </div>
                    <div class="event-content">
                        <h3>${data.title}</h3>
                        <p class="location">${data.location}</p>
                        <p>${data.description}</p>
                    </div>
                </div>
            `
        };

        return templates[type] ? templates[type](data) : '';
    }

    /**
     * Animazioni di caricamento per le card
     * @param {string} selector - Selettore per gli elementi da animare
     * @param {number} delay - Ritardo tra le animazioni (ms)
     */
    function animateCards(selector = '.card', delay = 100) {
        const cards = document.querySelectorAll(selector);

        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * delay);
        });
    }

    /**
     * Effetti hover avanzati
     * @param {string} selector - Selettore per gli elementi
     */
    function initHoverEffects(selector = '.card') {
        const elements = document.querySelectorAll(selector);

        elements.forEach(element => {
            element.addEventListener('mouseenter', () => {
                element.classList.add('hover-lift');
            });

            element.addEventListener('mouseleave', () => {
                element.classList.remove('hover-lift');
            });
        });
    }

    /**
     * Inizializzazione Intersection Observer per animazioni scroll
     * @param {string} selector - Selettore per gli elementi da osservare
     * @param {Object} options - Opzioni per l'observer
     */
    function initScrollAnimations(selector = '.animate-on-scroll', options = {}) {
        const defaultOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observerOptions = { ...defaultOptions, ...options };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.classList.add('animate-on-scroll');
            observer.observe(element);
        });
    }

    /**
     * Gestione ricerca in tempo reale
     * @param {string} inputSelector - Selettore per l'input di ricerca
     * @param {string} itemSelector - Selettore per gli elementi da filtrare
     * @param {Array} searchFields - Campi su cui effettuare la ricerca
     */
    function initSearch(inputSelector = '#search', itemSelector = '.card', searchFields = ['title', 'description']) {
        const searchInput = document.querySelector(inputSelector);
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const items = document.querySelectorAll(itemSelector);

            items.forEach(item => {
                let matchFound = false;

                searchFields.forEach(field => {
                    const element = item.querySelector(`.${field}`) || item.querySelector(`[data-${field}]`);
                    if (element && element.textContent.toLowerCase().includes(searchTerm)) {
                        matchFound = true;
                    }
                });

                if (matchFound || searchTerm === '') {
                    item.style.display = 'block';
                    item.style.animation = 'fadeIn 0.3s ease-in';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    /**
     * Lazy loading per immagini
     * @param {string} selector - Selettore per le immagini
     */
    function initLazyLoading(selector = 'img[data-src]') {
        const images = document.querySelectorAll(selector);

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }    /**
     * Inizializzazione completa per una pagina categoria
     * @param {Object} config - Configurazione per l'inizializzazione
     */
    function initCategoryPage(config = {}) {
        const defaultConfig = {
            enableFilters: true,
            enableSearch: false,
            enableAnimations: true,
            enableHover: true,
            enableLazyLoading: true,
            filterContainer: '.filters',
            itemSelector: '.card',
            searchSelector: '#search'
        };

        const options = { ...defaultConfig, ...config };

        // Inizializza filtri
        if (options.enableFilters) {
            initFilters(options.filterContainer, options.itemSelector);
        }

        // Inizializza ricerca
        if (options.enableSearch) {
            initSearch(options.searchSelector, options.itemSelector);
        }

        // Inizializza animazioni
        if (options.enableAnimations) {
            animateCards(options.itemSelector);
            initScrollAnimations();
        }

        // Inizializza hover effects
        if (options.enableHover) {
            initHoverEffects(options.itemSelector);
        }

        // Inizializza lazy loading
        if (options.enableLazyLoading) {
            initLazyLoading();
        }
    }

    // API pubblica
    return {
        initFilters,
        createCard,
        animateCards,
        initHoverEffects,
        initScrollAnimations,
        initSearch,
        initLazyLoading,
        initCategoryPage
    };

})();

// Auto-inizializzazione se il DOM è già caricato
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Inizializzazione base per tutte le pagine
        BostarterCommon.initScrollAnimations();
    });
} else {
    BostarterCommon.initScrollAnimations();
}
