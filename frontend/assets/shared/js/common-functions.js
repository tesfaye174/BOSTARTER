/**
 * ===== JAVASCRIPT COMUNE CONDIVISO BOSTARTER =====
 * Funzioni riutilizzabili per tutte le categorie
 */

// Namespace globale per evitare conflitti
window.BostarterCommon = (function () {
    'use strict';

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
     * Formattazione date uniforme
     * @param {string} dateString - Data in formato YYYY-MM-DD
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        const months = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu',
            'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];

        return {
            day: date.getDate().toString().padStart(2, '0'),
            month: months[date.getMonth()],
            year: date.getFullYear()
        };
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
    }

    /**
     * Gestione notifiche toast
     * @param {string} message - Messaggio da mostrare
     * @param {string} type - Tipo di notifica ('success', 'error', 'info')
     * @param {number} duration - Durata in ms
     */
    function showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease-out;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        `;

        document.body.appendChild(notification);

        // Animazione di entrata
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Rimozione automatica
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, duration);
    }

    /**
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
        formatDate,
        animateCards,
        initHoverEffects,
        initScrollAnimations,
        initSearch,
        initLazyLoading,
        showNotification,
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
