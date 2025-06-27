/**
 * BOSTARTER - Core Utilities
 * Funzioni di utilità comuni per tutta l'applicazione
 * @version 1.0
 */

(function (window, document) {
    'use strict';

    // Namespace principale
    window.BOSTARTERUtils = window.BOSTARTERUtils || {};

    /**
     * Utilità per la manipolazione del DOM
     */
    const DOM = {
        /**
         * Selettore sicuro per elementi
         */
        $(selector, context = document) {
            return context.querySelector(selector);
        },

        /**
         * Selettore multiplo
         */
        $$(selector, context = document) {
            return context.querySelectorAll(selector);
        },

        /**
         * Crea elemento con attributi
         */
        createElement(tag, attributes = {}, content = '') {
            const element = document.createElement(tag);

            Object.keys(attributes).forEach(key => {
                if (key === 'className') {
                    element.className = attributes[key];
                } else if (key === 'innerHTML') {
                    element.innerHTML = attributes[key];
                } else {
                    element.setAttribute(key, attributes[key]);
                }
            });

            if (content) {
                element.textContent = content;
            }

            return element;
        },

        /**
         * Aggiunge classe in modo sicuro
         */
        addClass(element, className) {
            if (element && className) {
                element.classList.add(className);
            }
        },

        /**
         * Rimuove classe in modo sicuro
         */
        removeClass(element, className) {
            if (element && className) {
                element.classList.remove(className);
            }
        },

        /**
         * Toggle classe
         */
        toggleClass(element, className) {
            if (element && className) {
                element.classList.toggle(className);
            }
        }
    };

    /**
     * Utilità per le richieste HTTP
     */
    const HTTP = {
        /**
         * Richiesta GET semplificata
         */
        async get(url, options = {}) {
            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        ...options.headers
                    },
                    ...options
                });

                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }

                return await response.json();
            } catch (error) {
                console.error('Errore richiesta GET:', error);
                throw error;
            }
        },

        /**
         * Richiesta POST semplificata
         */
        async post(url, data = {}, options = {}) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        ...options.headers
                    },
                    body: JSON.stringify(data),
                    ...options
                });

                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }

                return await response.json();
            } catch (error) {
                console.error('Errore richiesta POST:', error);
                throw error;
            }
        }
    };

    /**
     * Utilità per la formattazione
     */
    const Format = {
        /**
         * Formatta cifre monetarie
         */
        currency(amount, currency = 'EUR') {
            return new Intl.NumberFormat('it-IT', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },

        /**
         * Formatta date
         */
        date(date, options = {}) {
            const defaultOptions = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };

            return new Intl.DateTimeFormat('it-IT', {
                ...defaultOptions,
                ...options
            }).format(new Date(date));
        },

        /**
         * Trunca testo
         */
        truncate(text, maxLength = 100) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        },

        /**
         * Formatta numeri
         */
        number(num, decimals = 0) {
            return new Intl.NumberFormat('it-IT', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
        }
    };

    /**
     * Utilità per storage locale
     */
    const Storage = {
        /**
         * Salva nel localStorage
         */
        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (error) {
                console.error('Errore salvataggio localStorage:', error);
                return false;
            }
        },

        /**
         * Recupera dal localStorage
         */
        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (error) {
                console.error('Errore recupero localStorage:', error);
                return defaultValue;
            }
        },

        /**
         * Rimuove dal localStorage
         */
        remove(key) {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (error) {
                console.error('Errore rimozione localStorage:', error);
                return false;
            }
        }
    };

    /**
     * Utilità per debouncing
     */
    const debounce = function (func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    /**
     * Utilità per throttling
     */
    const throttle = function (func, limit) {
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
    };

    // Esporta le utilità
    window.BOSTARTERUtils = {
        DOM,
        HTTP,
        Format,
        Storage,
        debounce,
        throttle
    };

})(window, document);
