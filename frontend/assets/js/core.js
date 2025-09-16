/**
 * =====================================================
 * BOSTARTER - JAVASCRIPT CORE
 * =====================================================
 * 
 * Libreria JavaScript principale per la piattaforma BOSTARTER.
 * Contiene utilità, validazioni, animazioni e funzionalità comuni.
 * 
 * @author BOSTARTER Team
 * @version 2.0
 * @created 2025
 * @description Funzionalità JavaScript centralizzate
 */

// =====================================================
// INIZIALIZZAZIONE NAMESPACE GLOBALE
// =====================================================

// Crea il namespace globale BOSTARTER se non esiste
window.BOSTARTER = window.BOSTARTER || {};

// =====================================================
// UTILITÀ GENERALI
// =====================================================

BOSTARTER.utils = {
    /**
     * Formatta importi monetari in formato italiano (EUR)
     * @param {number} amount - Importo da formattare
     * @param {string} currency - Valuta (default: EUR)
     * @returns {string} Importo formattato (es. "1.234,56 €")
     */
    formatCurrency: function (amount, currency = 'EUR') {
        if (isNaN(amount) || amount === null || amount === undefined) {
            return '0,00 €';
        }
        
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    },

    /**
     * Formatta date in formato italiano
     * @param {string|Date} dateString - Data da formattare
     * @param {boolean} includeTime - Include ora nel formato (default: false)
     * @returns {string} Data formattata (es. "31/12/2024" o "31/12/2024, 14:30")
     */
    formatDate: function (dateString, includeTime = false) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            console.warn('BOSTARTER: Data non valida fornita a formatDate:', dateString);
            return 'Data non valida';
        }
        
        const options = {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        };
        
        if (includeTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
        }
        
        return date.toLocaleDateString('it-IT', options);
    },

    /**
     * Valida formato email secondo standard RFC 5322
     * @param {string} email - Email da validare
     * @returns {boolean} true se email è valida, false altrimenti
     */
    validateEmail: function (email) {
        if (!email || typeof email !== 'string') {
            return false;
        }
        
        // Pattern RFC 5322 semplificato ma robusto
        const emailPattern = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
        return emailPattern.test(email.trim());
    },

    /**
     * Mostra spinner di caricamento su elemento
     * @param {HTMLElement} element - Elemento su cui mostrare il loading
     * @param {string} text - Testo da mostrare (default: "Caricamento...")
     */
    showLoading: function (element, text = 'Caricamento...') {
        if (!element) {
            console.warn('BOSTARTER: Elemento non fornito a showLoading');
            return;
        }
        
        // Salva il contenuto originale
        if (!element.dataset.originalText) {
            element.dataset.originalText = element.innerHTML;
        }
        
        element.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> ${text}`;
        element.disabled = true;
        element.classList.add('loading-state');
    },

    /**
     * Nasconde spinner di caricamento e ripristina contenuto originale
     * @param {HTMLElement} element - Elemento da cui rimuovere il loading
     * @param {string} originalText - Testo originale (se non salvato automaticamente)
     */
    hideLoading: function (element, originalText = null) {
        if (!element) {
            console.warn('BOSTARTER: Elemento non fornito a hideLoading');
            return;
        }
        
        const textToRestore = originalText || element.dataset.originalText || 'Conferma';
        element.innerHTML = textToRestore;
        element.disabled = false;
        element.classList.remove('loading-state');
        
        // Pulisce il dataset se il testo è stato ripristinato
        if (originalText) {
            delete element.dataset.originalText;
        }
    },

    /**
     * Implementa debouncing per ottimizzare chiamate frequenti (es. ricerca)
     * @param {Function} func - Funzione da eseguire con debounce
     * @param {number} wait - Millisecondi di attesa (default: 300ms)
     * @param {boolean} immediate - Esegui immediatamente la prima chiamata
     * @returns {Function} Funzione con debounce applicato
     */
    debounce: function (func, wait = 300, immediate = false) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(this, args);
            };
            
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            
            if (callNow) func.apply(this, args);
        };
    },

    /**
     * Converte stringa in slug URL-friendly
     * @param {string} text - Testo da convertire
     * @returns {string} Slug formattato
     */
    createSlug: function(text) {
        if (!text) return '';
        
        return text
            .toLowerCase()
            .trim()
            // Sostituisce caratteri accentati
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            // Sostituisce spazi e caratteri speciali con trattini
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            // Rimuove trattini all'inizio e alla fine
            .replace(/^-+|-+$/g, '');
    },

    /**
     * Copia testo negli appunti
     * @param {string} text - Testo da copiare
     * @returns {Promise<boolean>} Promise che risolve con successo/fallimento
     */
    copyToClipboard: async function(text) {
        if (!text) return false;
        
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            } else {
                // Fallback per browser più vecchi
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                const success = document.execCommand('copy');
                document.body.removeChild(textArea);
                return success;
            }
        } catch (error) {
            console.error('BOSTARTER: Errore durante la copia:', error);
            return false;
        }
    }
};

// =====================================================
// VALIDAZIONE FORM
// =====================================================

BOSTARTER.validation = {
    /**
     * Valida tutti i campi obbligatori in un form
     * @param {HTMLFormElement} form - Form da validare
     * @returns {boolean} true se tutti i campi sono validi, false altrimenti
     */
    validateRequired: function (form) {
        if (!form || !form.querySelectorAll) {
            console.error('BOSTARTER: Form non valido fornito a validateRequired');
            return false;
        }
        
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            const fieldValue = field.value ? field.value.trim() : '';
            
            if (!fieldValue) {
                this.showFieldError(field, 'Questo campo è obbligatorio');
                isValid = false;
            } else {
                this.clearFieldError(field);
                
                // Validazione aggiuntiva per tipo di campo
                if (!this.validateFieldType(field)) {
                    isValid = false;
                }
            }
        });

        return isValid;
    },
    
    /**
     * Valida un singolo campo in base al suo tipo
     * @param {HTMLElement} field - Campo da validare
     * @returns {boolean} true se valido, false altrimenti
     */
    validateFieldType: function(field) {
        const value = field.value.trim();
        const type = field.type || field.tagName.toLowerCase();
        
        switch (type) {
            case 'email':
                if (!BOSTARTER.utils.validateEmail(value)) {
                    this.showFieldError(field, 'Inserisci un indirizzo email valido');
                    return false;
                }
                break;
                
            case 'tel':
            case 'phone':
                if (!this.validatePhoneNumber(value)) {
                    this.showFieldError(field, 'Inserisci un numero di telefono valido');
                    return false;
                }
                break;
                
            case 'url':
                if (!this.validateUrl(value)) {
                    this.showFieldError(field, 'Inserisci un URL valido');
                    return false;
                }
                break;
                
            case 'number':
                const min = parseFloat(field.min);
                const max = parseFloat(field.max);
                const numValue = parseFloat(value);
                
                if (isNaN(numValue)) {
                    this.showFieldError(field, 'Inserisci un numero valido');
                    return false;
                }
                
                if (!isNaN(min) && numValue < min) {
                    this.showFieldError(field, `Il valore deve essere almeno ${min}`);
                    return false;
                }
                
                if (!isNaN(max) && numValue > max) {
                    this.showFieldError(field, `Il valore non può superare ${max}`);
                    return false;
                }
                break;
        }
        
        // Validazione lunghezza minima
        const minLength = parseInt(field.minLength || field.dataset.minLength);
        if (!isNaN(minLength) && value.length < minLength) {
            this.showFieldError(field, `Minimo ${minLength} caratteri richiesti`);
            return false;
        }
        
        // Validazione lunghezza massima  
        const maxLength = parseInt(field.maxLength || field.dataset.maxLength);
        if (!isNaN(maxLength) && value.length > maxLength) {
            this.showFieldError(field, `Massimo ${maxLength} caratteri consentiti`);
            return false;
        }
        
        return true;
    },
    
    /**
     * Valida numero di telefono italiano
     * @param {string} phone - Numero da validare
     * @returns {boolean} true se valido, false altrimenti
     */
    validatePhoneNumber: function(phone) {
        if (!phone) return false;
        
        // Rimuove spazi, trattini e parentesi
        const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
        
        // Pattern per numeri italiani (fissi e mobili)
        const italianPhonePattern = /^(\+39|0039|39)?[23]?\d{8,10}$/;
        return italianPhonePattern.test(cleanPhone);
    },
    
    /**
     * Valida formato URL
     * @param {string} url - URL da validare
     * @returns {boolean} true se valido, false altrimenti
     */
    validateUrl: function(url) {
        if (!url) return false;
        
        try {
            new URL(url);
            return true;
        } catch {
            // Prova ad aggiungere http:// se manca il protocollo
            try {
                new URL('http://' + url);
                return true;
            } catch {
                return false;
            }
        }
    },

    /**
     * Mostra messaggio di errore su un campo specifico
     * @param {HTMLElement} field - Campo con errore
     * @param {string} message - Messaggio di errore da mostrare
     */
    showFieldError: function (field, message) {
        if (!field) {
            console.warn('BOSTARTER: Campo non fornito a showFieldError');
            return;
        }
        
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');

        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.setAttribute('role', 'alert');
            field.parentNode.appendChild(errorDiv);
        }
        
        errorDiv.textContent = message;
        
        // Aggiungi riferimento per screen reader
        if (!field.getAttribute('aria-describedby')) {
            const errorId = 'error-' + Math.random().toString(36).substr(2, 9);
            errorDiv.id = errorId;
            field.setAttribute('aria-describedby', errorId);
        }
    },

    /**
     * Rimuove messaggio di errore da un campo
     * @param {HTMLElement} field - Campo da cui rimuovere l'errore
     */
    clearFieldError: function (field) {
        if (!field) {
            console.warn('BOSTARTER: Campo non fornito a clearFieldError');
            return;
        }
        
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
        
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    },
    
    /**
     * Valida un intero form e mostra riassunto errori
     * @param {HTMLFormElement} form - Form da validare
     * @param {boolean} showSummary - Mostra riassunto errori (default: false)
     * @returns {boolean} true se valido, false altrimenti
     */
    validateForm: function(form, showSummary = false) {
        const isValid = this.validateRequired(form);
        
        if (!isValid && showSummary) {
            this.showValidationSummary(form);
        }
        
        return isValid;
    },
    
    /**
     * Mostra riassunto degli errori di validazione
     * @param {HTMLFormElement} form - Form con errori
     */
    showValidationSummary: function(form) {
        const errorFields = form.querySelectorAll('.is-invalid');
        if (errorFields.length === 0) return;
        
        let summaryHtml = '<div class="alert alert-danger alert-validation-summary" role="alert">';
        summaryHtml += '<h6><i class="fas fa-exclamation-triangle me-2"></i>Correggi i seguenti errori:</h6>';
        summaryHtml += '<ul class="mb-0">';
        
        errorFields.forEach((field, index) => {
            const errorMessage = field.parentNode.querySelector('.invalid-feedback');
            const fieldLabel = this.getFieldLabel(field);
            
            if (errorMessage) {
                summaryHtml += `<li>${fieldLabel}: ${errorMessage.textContent}</li>`;
            }
        });
        
        summaryHtml += '</ul></div>';
        
        // Rimuovi riassunto precedente
        const existingSummary = form.querySelector('.alert-validation-summary');
        if (existingSummary) {
            existingSummary.remove();
        }
        
        // Inserisci nuovo riassunto all'inizio del form
        form.insertAdjacentHTML('afterbegin', summaryHtml);
        
        // Scorri al riassunto
        form.querySelector('.alert-validation-summary').scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    },
    
    /**
     * Ottieni l'etichetta di un campo per i messaggi
     * @param {HTMLElement} field - Campo di input
     * @returns {string} Etichetta del campo
     */
    getFieldLabel: function(field) {
        // Cerca label associata
        const label = field.id ? document.querySelector(`label[for="${field.id}"]`) : null;
        if (label) {
            return label.textContent.replace('*', '').trim();
        }
        
        // Usa placeholder se disponibile
        if (field.placeholder) {
            return field.placeholder;
        }
        
        // Usa name del campo
        if (field.name) {
            return field.name.charAt(0).toUpperCase() + field.name.slice(1);
        }
        
        return 'Campo';
    }
};

// =====================================================
// GESTIONE DOM E INIZIALIZZAZIONE
// =====================================================

/**
 * Funzione di utilità per eseguire codice quando il DOM è pronto
 * @param {Function} fn - Funzione da eseguire al caricamento del DOM
 */
BOSTARTER.ready = function (fn) {
    if (document.readyState !== 'loading') {
        // DOM già caricato, esegui immediatamente
        fn();
    } else {
        // Attendi il caricamento del DOM
        document.addEventListener('DOMContentLoaded', fn);
    }
};

/**
 * Inizializzazione automatica delle funzionalità core
 * Viene eseguita automaticamente al caricamento del DOM
 */
BOSTARTER.ready(function () {
    console.log('BOSTARTER Core: Inizializzazione avviata');
    
    // =====================================================
    // SMOOTH SCROLLING PER LINK INTERNI
    // =====================================================
    
    /**
     * Aggiunge smooth scrolling a tutti i link con href che inizia con #
     * Migliora l'esperienza utente nella navigazione interna alla pagina
     */
    const internalLinks = document.querySelectorAll('a[href^="#"]');
    internalLinks.forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Aggiorna URL senza saltare
                if (history.pushState) {
                    history.pushState(null, null, targetId);
                }
            } else {
                console.warn(`BOSTARTER: Elemento target non trovato per ${targetId}`);
            }
        });
    });
    
    // =====================================================
    // INIZIALIZZAZIONE BOOTSTRAP COMPONENTS
    // =====================================================
    
    /**
     * Inizializza automaticamente i tooltip di Bootstrap se disponibile
     * Cerca tutti gli elementi con attributo data-bs-toggle="tooltip"
     */
    if (typeof bootstrap !== 'undefined') {
        try {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(tooltipTriggerEl => {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    // Configurazione personalizzata per tooltip
                    trigger: 'hover focus',
                    delay: { show: 500, hide: 100 }
                });
            });
            
            if (tooltipTriggerList.length > 0) {
                console.log(`BOSTARTER: Inizializzati ${tooltipTriggerList.length} tooltip`);
            }
        } catch (error) {
            console.error('BOSTARTER: Errore nell\'inizializzazione dei tooltip:', error);
        }
        
        /**
         * Inizializza popover se presenti
         */
        try {
            const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
            [...popoverTriggerList].map(popoverTriggerEl => {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            if (popoverTriggerList.length > 0) {
                console.log(`BOSTARTER: Inizializzati ${popoverTriggerList.length} popover`);
            }
        } catch (error) {
            console.error('BOSTARTER: Errore nell\'inizializzazione dei popover:', error);
        }
    } else {
        console.warn('BOSTARTER: Bootstrap non disponibile, alcuni componenti potrebbero non funzionare');
    }
    
    // =====================================================
    // GESTIONE AUTOMATICA ALERT
    // =====================================================
    
    /**
     * Nasconde automaticamente gli alert dopo 5 secondi
     * Esclusi quelli con classe .alert-permanent
     */
    const autoHideAlerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    autoHideAlerts.forEach((alert, index) => {
        // Ritardo incrementale per alert multipli
        const hideDelay = 5000 + (index * 500);
        
        setTimeout(() => {
            if (alert.parentNode && typeof bootstrap !== 'undefined') {
                try {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                } catch (error) {
                    console.warn('BOSTARTER: Errore nella chiusura automatica alert:', error);
                    // Fallback: rimuovi manualmente
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            }
        }, hideDelay);
    });
    
    if (autoHideAlerts.length > 0) {
        console.log(`BOSTARTER: Configurati ${autoHideAlerts.length} alert per auto-dismiss`);
    }
    
    // =====================================================
    // GESTIONE FORM AVANZATA
    // =====================================================
    
    /**
     * Aggiunge validazione in tempo reale sui form
     */
    const formsWithValidation = document.querySelectorAll('form[data-validate="true"]');
    formsWithValidation.forEach(form => {
        // Validazione in tempo reale sui campi
        const fields = form.querySelectorAll('input, textarea, select');
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                if (this.value.trim()) {
                    BOSTARTER.validation.validateFieldType(this);
                }
            });
            
            // Rimuovi errore durante la digitazione
            field.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    BOSTARTER.validation.clearFieldError(this);
                }
            });
        });
        
        // Validazione completa al submit
        form.addEventListener('submit', function(e) {
            if (!BOSTARTER.validation.validateForm(this, true)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
    
    console.log('BOSTARTER Core: Inizializzazione completata con successo');
});

// =====================================================
// GESTIONE LOGOUT E AUTENTICAZIONE
// =====================================================

/**
 * Gestisce il click sul pulsante logout con modal di conferma
 * @param {Event} event - Evento click
 * @param {string} logoutUrl - URL per il logout
 */
function handleLogoutClick(event, logoutUrl) {
    event.preventDefault();
    
    if (!logoutUrl) {
        console.error('BOSTARTER: URL di logout non fornito');
        return;
    }
    
    // Crea modal di conferma moderno e accessibile
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'logoutModalLabel');
    modal.setAttribute('aria-hidden', 'true');
    
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="logoutModalLabel">
                        <i class="fas fa-sign-out-alt text-warning me-2"></i>
                        Conferma Logout
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-question-circle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <p class="mb-3 fw-semibold">Sei sicuro di voler uscire dal tuo account?</p>
                    <p class="text-muted small mb-0">
                        Dovrai effettuare nuovamente il login per accedere alle funzionalità riservate.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Annulla
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmLogout">
                        <i class="fas fa-sign-out-alt me-1"></i> Sì, Esci
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Inizializza e mostra il modal Bootstrap
    const bootstrapModal = new bootstrap.Modal(modal, {
        backdrop: 'static',
        keyboard: true
    });
    bootstrapModal.show();
    
    // Gestione conferma logout
    const confirmButton = modal.querySelector('#confirmLogout');
    confirmButton.addEventListener('click', function() {
        // Mostra stato di caricamento
        const originalContent = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Uscita in corso...';
        this.disabled = true;
        
        // Disabilita anche il pulsante annulla
        const cancelButton = modal.querySelector('[data-bs-dismiss="modal"]');
        cancelButton.disabled = true;
        
        // Aggiungi feedback visivo con ritardo per migliore UX
        setTimeout(() => {
            window.location.href = logoutUrl;
        }, 800);
    });
    
    // Pulizia modal quando nascosto
    modal.addEventListener('hidden.bs.modal', function() {
        if (document.body.contains(modal)) {
            document.body.removeChild(modal);
        }
    });
    
    // Focus automatico sul pulsante principale
    modal.addEventListener('shown.bs.modal', function() {
        confirmButton.focus();
    });
}

/**
 * Sistema di notifiche per logout e autenticazione
 * Gestisce messaggi di successo, errore e informativi
 */
BOSTARTER.logout = {
    /**
     * Mostra messaggio di successo logout personalizzato
     * @param {string} username - Nome utente (opzionale)
     * @param {number} duration - Durata visualizzazione in ms (default: 5000)
     */
    showLogoutSuccess: function(username = null, duration = 5000) {
        const message = username ? 
            `Arrivederci ${username}! Grazie per aver utilizzato BOSTARTER.` : 
            'Logout effettuato con successo! Grazie per aver utilizzato BOSTARTER.';
            
        this.showNotification(message, 'success', duration);
    },
    
    /**
     * Mostra messaggio di errore durante logout
     * @param {string} errorMessage - Messaggio di errore personalizzato
     */
    showLogoutError: function(errorMessage = 'Si è verificato un errore durante il logout. Riprova.') {
        this.showNotification(errorMessage, 'danger', 7000);
    },
    
    /**
     * Mostra messaggio di sessione scaduta
     */
    showSessionExpired: function() {
        const message = 'La tua sessione è scaduta. Effettua nuovamente il login per continuare.';
        this.showNotification(message, 'warning', 8000);
    },
    
    /**
     * Mostra notifica generica con stili personalizzati
     * @param {string} message - Messaggio da mostrare
     * @param {string} type - Tipo di notifica (success, danger, warning, info)
     * @param {number} duration - Durata in millisecondi (default: 5000)
     * @param {Object} options - Opzioni aggiuntive
     */
    showNotification: function(message, type = 'info', duration = 5000, options = {}) {
        if (!message) {
            console.warn('BOSTARTER: Messaggio vuoto fornito a showNotification');
            return;
        }
        
        // Configurazione icone per tipo
        const iconMap = {
            success: 'fas fa-check-circle',
            danger: 'fas fa-exclamation-circle', 
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        const iconClass = iconMap[type] || iconMap.info;
        
        // Crea elemento notifica
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.setAttribute('role', 'alert');
        notification.style.cssText = `
            top: 20px; 
            right: 20px; 
            z-index: 1080; 
            min-width: 320px;
            max-width: 450px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border: none;
            border-radius: 8px;
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-start">
                <i class="${iconClass} me-2 mt-1 flex-shrink-0"></i>
                <div class="flex-grow-1">
                    ${message}
                </div>
                <button type="button" class="btn-close ms-2 flex-shrink-0" data-bs-dismiss="alert" aria-label="Chiudi"></button>
            </div>
        `;
        
        // Rimuovi notifiche precedenti dello stesso tipo se richiesto
        if (options.replacePrevious) {
            const existingNotifications = document.querySelectorAll(`.alert-${type}.position-fixed`);
            existingNotifications.forEach(existing => existing.remove());
        }
        
        document.body.appendChild(notification);
        
        // Auto-rimozione dopo il tempo specificato
        const autoRemoveTimer = setTimeout(() => {
            if (notification.parentNode && typeof bootstrap !== 'undefined') {
                try {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(notification);
                    bsAlert.close();
                } catch (error) {
                    console.warn('BOSTARTER: Errore nella rimozione automatica notifica:', error);
                    // Fallback: rimozione manuale con fade out
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }
            }
        }, duration);
        
        // Pausa timer al passaggio del mouse
        notification.addEventListener('mouseenter', () => clearTimeout(autoRemoveTimer));
        notification.addEventListener('mouseleave', () => {
            setTimeout(() => {
                if (notification.parentNode && typeof bootstrap !== 'undefined') {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(notification);
                    bsAlert.close();
                }
            }, 1000);
        });
        
        // Log per debug
        console.log(`BOSTARTER: Notifica mostrata - Tipo: ${type}, Durata: ${duration}ms`);
        
        return notification;
    }
};

// =====================================================
// EXPORT E COMPATIBILITA' MODULI
// =====================================================

/**
 * Export per compatibilità con ES6 modules e CommonJS
 * Permette l'uso in diversi ambienti (browser, Node.js, bundlers)
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BOSTARTER;
}

// Export per AMD (Asynchronous Module Definition)
if (typeof define === 'function' && define.amd) {
    define(function() {
        return BOSTARTER;
    });
}

// Assicurati che BOSTARTER sia sempre disponibile globalmente
if (typeof window !== 'undefined') {
    window.BOSTARTER = BOSTARTER;
}

console.log('BOSTARTER Core v2.0 caricato con successo');

// Theme toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    if (themeToggle && themeIcon) {
        // Check for saved theme preference or default to 'light'
        const currentTheme = localStorage.getItem('bostarter-theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('bostarter-theme', newTheme);
            updateThemeIcon(newTheme);
            
            // Trigger custom event for other components
            document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
        });
    }
    
    function updateThemeIcon(theme) {
        if (themeIcon) {
            if (theme === 'dark') {
                themeIcon.className = 'fas fa-sun';
                themeToggle.title = 'Passa al tema chiaro';
            } else {
                themeIcon.className = 'fas fa-moon';
                themeToggle.title = 'Passa al tema scuro';
            }
        }
    }
});

// High contrast mode toggle
function toggleHighContrast() {
    document.documentElement.classList.toggle('high-contrast');
    const isHighContrast = document.documentElement.classList.contains('high-contrast');
    localStorage.setItem('bostarter-high-contrast', isHighContrast);
}

// Initialize high contrast mode
document.addEventListener('DOMContentLoaded', function() {
    const savedHighContrast = localStorage.getItem('bostarter-high-contrast') === 'true';
    if (savedHighContrast) {
        document.documentElement.classList.add('high-contrast');
    }
});
