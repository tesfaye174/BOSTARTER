// Sistema di internazionalizzazione per BOSTARTER

import Config from './config.js';

const I18n = {
    currentLocale: Config.i18n.defaultLocale,
    translations: {},
    isInitialized: false,

    // Load translations for a specific language
    async loadTranslations(locale) {
        try {
            const response = await fetch(`/BOSTARTER/frontend/locales/${locale}.json`);
            if (!response.ok) throw new Error(`Errore nel caricamento delle traduzioni per ${locale}`);
            this.translations[locale] = await response.json();
            console.log(`Traduzioni caricate per ${locale}`);
        } catch (error) {
            console.error('Errore nel caricamento delle traduzioni:', error);
            // Fallback alle traduzioni di default se disponibili
            if (locale !== Config.i18n.fallbackLocale) {
                await this.loadTranslations(Config.i18n.fallbackLocale);
            }
        }
    },

    // Imposta la lingua corrente
    async setLocale(locale) {
        if (!Config.i18n.supportedLocales.includes(locale)) {
            console.warn(`Lingua ${locale} non supportata, uso ${Config.i18n.fallbackLocale}`);
            locale = Config.i18n.fallbackLocale;
        }

        if (!this.translations[locale]) {
            await this.loadTranslations(locale);
        }

        this.currentLocale = locale;
        document.documentElement.lang = locale;
        localStorage.setItem(Config.storage.keys.language, locale);

        // Aggiorna tutti i testi nella pagina
        this.updatePageTranslations();
    },

    // Ottieni una traduzione
    t(key, params = {}) {
        const keys = key.split('.');
        let translation = this.translations[this.currentLocale] || {};

        for (const k of keys) {
            translation = translation[k];
            if (!translation) {
                console.warn(`Traduzione mancante per la chiave: ${key}`);
                return key;
            }
        }

        // Sostituisci i parametri nella traduzione
        return this.interpolate(translation, params);
    },

    // Sostituisci i parametri nella stringa di traduzione
    interpolate(text, params) {
        return text.replace(/\{([^}]+)\}/g, (_, key) => {
            return params[key] !== undefined ? params[key] : `{${key}}`;
        });
    },

    // Formatta date secondo la lingua corrente
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        return new Intl.DateTimeFormat(
            this.currentLocale,
            { ...defaultOptions, ...options }
        ).format(new Date(date));
    },

    // Formatta numeri secondo la lingua corrente
    formatNumber(number, options = {}) {
        return new Intl.NumberFormat(
            this.currentLocale,
            options
        ).format(number);
    },

    // Formatta valute secondo la lingua corrente
    formatCurrency(amount, currency = 'EUR') {
        return new Intl.NumberFormat(this.currentLocale, {
            style: 'currency',
            currency
        }).format(amount);
    },

    // Update all translations on the page
    updatePageTranslations() {
        // Aggiorna elementi con attributo data-i18n
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            element.textContent = this.t(key);
        });

        // Aggiorna placeholder con attributo data-i18n-placeholder
        document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
            const key = element.getAttribute('data-i18n-placeholder');
            element.placeholder = this.t(key);
        });

        // Aggiorna titoli con attributo data-i18n-title
        document.querySelectorAll('[data-i18n-title]').forEach(element => {
            const key = element.getAttribute('data-i18n-title');
            element.title = this.t(key);
        });
    },

    // Initialize the internationalization system
    async initialize() {
        // Carica la lingua salvata o usa quella di default
        const savedLocale = localStorage.getItem(Config.storage.keys.language);
        const browserLocale = navigator.language.split('-')[0];
        const initialLocale = savedLocale ||
            (Config.i18n.supportedLocales.includes(browserLocale) ? browserLocale : Config.i18n.defaultLocale);

        await this.setLocale(initialLocale);

        // Aggiungi listener per il cambio lingua
        document.addEventListener('DOMContentLoaded', () => {
            const languageSelector = document.querySelector('#language-selector');
            if (languageSelector) {
                languageSelector.value = this.currentLocale;
                languageSelector.addEventListener('change', (e) => {
                    this.setLocale(e.target.value);
                });
            }
        });
    }
};

export default I18n;