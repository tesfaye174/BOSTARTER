/**
 * BOSTARTER - Unified JavaScript Bundle
 * Bundle unificato di tutte le funzionalità principali
 * @version 1.0
 */

(function (window, document) {
    'use strict';

    /**
     * BOSTARTER Main Application
     */
    const BOSTARTERApp = {
        // Configurazione globale
        config: {
            apiBaseUrl: '/BOSTARTER/backend/api/',
            version: '1.0',
            debug: false
        },

        // Moduli
        modules: {},

        // Stato globale
        state: {
            initialized: false,
            user: null,
            theme: 'auto'
        },

        /**
         * Inizializza l'applicazione
         */
        init() {
            if (this.state.initialized) return;

            console.log('🚀 Inizializzazione BOSTARTER App...');

            this.loadModules();
            this.setupGlobalEventListeners();
            this.initializeComponents();

            this.state.initialized = true;

            // Dispatch evento di inizializzazione
            this.dispatch('app:initialized');

            console.log('✅ BOSTARTER App inizializzata');
        },

        /**
         * Carica i moduli
         */
        loadModules() {
            // Dashboard Management
            this.modules.dashboard = {
                init() {
                    this.setupStats();
                    this.setupCharts();
                    this.setupNotifications();
                },

                setupStats() {
                    this.loadUserStats();
                    this.loadProjectStats();
                },

                async loadUserStats() {
                    try {
                        const response = await fetch(`${BOSTARTERApp.config.apiBaseUrl}user_stats.php`);
                        const data = await response.json();

                        if (data.success) {
                            this.updateStatsDisplay(data.stats);
                        }
                    } catch (error) {
                        console.error('Errore caricamento stats utente:', error);
                    }
                },

                async loadProjectStats() {
                    try {
                        const response = await fetch(`${BOSTARTERApp.config.apiBaseUrl}project_stats.php`);
                        const data = await response.json();

                        if (data.success) {
                            this.updateProjectStatsDisplay(data.stats);
                        }
                    } catch (error) {
                        console.error('Errore caricamento stats progetti:', error);
                    }
                },

                updateStatsDisplay(stats) {
                    // Aggiorna i contatori nella dashboard
                    const elements = {
                        projectsCount: document.querySelector('[data-stat="projects-count"]'),
                        fundingTotal: document.querySelector('[data-stat="funding-total"]'),
                        backersCount: document.querySelector('[data-stat="backers-count"]')
                    };

                    if (elements.projectsCount) {
                        this.animateNumber(elements.projectsCount, stats.projects_count || 0);
                    }

                    if (elements.fundingTotal) {
                        elements.fundingTotal.textContent = this.formatCurrency(stats.funding_total || 0);
                    }

                    if (elements.backersCount) {
                        this.animateNumber(elements.backersCount, stats.backers_count || 0);
                    }
                },

                updateProjectStatsDisplay(stats) {
                    // Aggiorna le statistiche dei progetti
                    const successRate = document.querySelector('[data-stat="success-rate"]');
                    const avgFunding = document.querySelector('[data-stat="avg-funding"]');

                    if (successRate) {
                        successRate.textContent = `${(stats.success_rate || 0).toFixed(1)}%`;
                    }

                    if (avgFunding) {
                        avgFunding.textContent = this.formatCurrency(stats.avg_funding || 0);
                    }
                },

                setupCharts() {
                    if (typeof Chart !== 'undefined') {
                        this.initFundingChart();
                        this.initProjectsChart();
                    }
                },

                async initFundingChart() {
                    const canvas = document.querySelector('#fundingChart');
                    if (!canvas) return;

                    try {
                        const response = await fetch(`${BOSTARTERApp.config.apiBaseUrl}funding_chart_data.php`);
                        const data = await response.json();

                        if (data.success) {
                            new Chart(canvas, {
                                type: 'line',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: 'Finanziamenti',
                                        data: data.values,
                                        borderColor: '#3b82f6',
                                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function (value) {
                                                    return BOSTARTERApp.modules.dashboard.formatCurrency(value);
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    } catch (error) {
                        console.error('Errore caricamento chart finanziamenti:', error);
                    }
                },

                async initProjectsChart() {
                    const canvas = document.querySelector('#projectsChart');
                    if (!canvas) return;

                    try {
                        const response = await fetch(`${BOSTARTERApp.config.apiBaseUrl}projects_chart_data.php`);
                        const data = await response.json();

                        if (data.success) {
                            new Chart(canvas, {
                                type: 'doughnut',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        data: data.values,
                                        backgroundColor: [
                                            '#3b82f6',
                                            '#10b981',
                                            '#f59e0b',
                                            '#ef4444'
                                        ]
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: {
                                            position: 'bottom'
                                        }
                                    }
                                }
                            });
                        }
                    } catch (error) {
                        console.error('Errore caricamento chart progetti:', error);
                    }
                },

                setupNotifications() {
                    this.loadRecentNotifications();
                    this.setupNotificationPolling();
                },

                async loadRecentNotifications() {
                    try {
                        const response = await fetch(`${BOSTARTERApp.config.apiBaseUrl}notifications.php?action=recent`);
                        const data = await response.json();

                        if (data.success) {
                            this.displayNotifications(data.notifications);
                        }
                    } catch (error) {
                        console.error('Errore caricamento notifiche:', error);
                    }
                },

                displayNotifications(notifications) {
                    const container = document.querySelector('.notifications-list');
                    if (!container) return;

                    if (notifications.length === 0) {
                        container.innerHTML = '<p class="text-gray-500">Nessuna notifica recente</p>';
                        return;
                    }

                    const html = notifications.map(notification => `
                        <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-notification-id="${notification.id}">
                            <div class="notification-content">
                                <p class="notification-message">${notification.message}</p>
                                <span class="notification-time">${this.formatRelativeTime(notification.created_at)}</span>
                            </div>
                            ${!notification.is_read ? '<div class="notification-badge"></div>' : ''}
                        </div>
                    `).join('');

                    container.innerHTML = html;
                },

                setupNotificationPolling() {
                    // Polling ogni 30 secondi per nuove notifiche
                    setInterval(() => {
                        this.loadRecentNotifications();
                    }, 30000);
                },

                animateNumber(element, targetValue) {
                    const startValue = parseInt(element.textContent) || 0;
                    const duration = 1000;
                    const startTime = performance.now();

                    const animate = (currentTime) => {
                        const elapsed = currentTime - startTime;
                        const progress = Math.min(elapsed / duration, 1);

                        const currentValue = Math.floor(startValue + (targetValue - startValue) * progress);
                        element.textContent = currentValue.toLocaleString('it-IT');

                        if (progress < 1) {
                            requestAnimationFrame(animate);
                        }
                    };

                    requestAnimationFrame(animate);
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('it-IT', {
                        style: 'currency',
                        currency: 'EUR'
                    }).format(amount);
                },

                formatRelativeTime(dateString) {
                    const date = new Date(dateString);
                    const now = new Date();
                    const diffInSeconds = Math.floor((now - date) / 1000);

                    if (diffInSeconds < 60) return 'Ora';
                    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} min fa`;
                    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} ore fa`;
                    return `${Math.floor(diffInSeconds / 86400)} giorni fa`;
                }
            };

            // Quick Actions Module
            this.modules.quickActions = {
                init() {
                    this.setupActionButtons();
                    this.setupShortcuts();
                },

                setupActionButtons() {
                    // Nuovo progetto
                    const newProjectBtn = document.querySelector('[data-action="new-project"]');
                    if (newProjectBtn) {
                        newProjectBtn.addEventListener('click', () => {
                            window.location.href = '/BOSTARTER/frontend/create-project.php';
                        });
                    }

                    // Visualizza profilo
                    const profileBtn = document.querySelector('[data-action="view-profile"]');
                    if (profileBtn) {
                        profileBtn.addEventListener('click', () => {
                            window.location.href = '/BOSTARTER/frontend/profile.php';
                        });
                    }

                    // Impostazioni
                    const settingsBtn = document.querySelector('[data-action="settings"]');
                    if (settingsBtn) {
                        settingsBtn.addEventListener('click', () => {
                            window.location.href = '/BOSTARTER/frontend/settings.php';
                        });
                    }
                },

                setupShortcuts() {
                    document.addEventListener('keydown', (e) => {
                        if (e.ctrlKey || e.metaKey) {
                            switch (e.key) {
                                case 'n':
                                    e.preventDefault();
                                    window.location.href = '/BOSTARTER/frontend/create-project.php';
                                    break;
                                case 'p':
                                    e.preventDefault();
                                    window.location.href = '/BOSTARTER/frontend/profile.php';
                                    break;
                                case ',':
                                    e.preventDefault();
                                    window.location.href = '/BOSTARTER/frontend/settings.php';
                                    break;
                            }
                        }
                    });
                }
            };

            // Search Module
            this.modules.search = {
                init() {
                    this.setupSearchInput();
                    this.setupFilters();
                },

                setupSearchInput() {
                    const searchInput = document.querySelector('[data-search="global"]');
                    if (!searchInput) return;

                    searchInput.addEventListener('input', this.debounce((e) => {
                        this.performSearch(e.target.value);
                    }, 300));
                },

                async performSearch(query) {
                    if (query.length < 2) return;

                    try {
                        const response = await fetch(`${BOSTARTERApp.config.apiBaseUrl}search.php?q=${encodeURIComponent(query)}`);
                        const data = await response.json();

                        if (data.success) {
                            this.displaySearchResults(data.results);
                        }
                    } catch (error) {
                        console.error('Errore ricerca:', error);
                    }
                },

                displaySearchResults(results) {
                    const container = document.querySelector('.search-results');
                    if (!container) return;

                    if (results.length === 0) {
                        container.innerHTML = '<p class="text-gray-500">Nessun risultato trovato</p>';
                        return;
                    }

                    const html = results.map(result => `
                        <div class="search-result-item">
                            <h4><a href="${result.url}">${result.title}</a></h4>
                            <p>${result.description}</p>
                            <span class="result-type">${result.type}</span>
                        </div>
                    `).join('');

                    container.innerHTML = html;
                },

                setupFilters() {
                    // Implementazione filtri di ricerca
                },

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
        },

        /**
         * Setup event listeners globali
         */
        setupGlobalEventListeners() {
            // Gestione errori globali
            window.addEventListener('error', (e) => {
                if (this.config.debug) {
                    console.error('Errore JavaScript:', e.error);
                }
            });

            // Gestione promesse rifiutate
            window.addEventListener('unhandledrejection', (e) => {
                if (this.config.debug) {
                    console.error('Promise rifiutata:', e.reason);
                }
            });

            // Gestione online/offline
            window.addEventListener('online', () => {
                this.showNotification('Connessione ripristinata', 'success');
            });

            window.addEventListener('offline', () => {
                this.showNotification('Connessione persa', 'warning');
            });
        },

        /**
         * Inizializza i componenti
         */
        initializeComponents() {
            // Inizializza i moduli se la pagina li richiede
            if (document.querySelector('.dashboard-container')) {
                this.modules.dashboard.init();
            }

            this.modules.quickActions.init();
            this.modules.search.init();
        },

        /**
         * Mostra una notifica
         */
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;

            const container = document.querySelector('.notification-container') || document.body;
            container.appendChild(notification);

            // Rimuovi dopo 5 secondi
            setTimeout(() => {
                notification.remove();
            }, 5000);
        },

        /**
         * Dispatch eventi personalizzati
         */
        dispatch(eventName, detail = {}) {
            const event = new CustomEvent(eventName, { detail });
            document.dispatchEvent(event);
        },

        /**
         * API per altri script
         */
        api: {
            getUser() {
                return BOSTARTERApp.state.user;
            },

            setUser(user) {
                BOSTARTERApp.state.user = user;
                BOSTARTERApp.dispatch('user:updated', { user });
            },

            showNotification(message, type) {
                BOSTARTERApp.showNotification(message, type);
            }
        }
    };

    // Inizializzazione automatica
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            BOSTARTERApp.init();
        });
    } else {
        BOSTARTERApp.init();
    }

    // Esporta l'app globalmente
    window.BOSTARTER = BOSTARTERApp;

})(window, document);
