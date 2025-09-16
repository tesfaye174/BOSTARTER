/**
 * BOSTARTER Frontend Message System
 * Sistema di messaggi dinamici per l'interfaccia utente
 */

class FrontendMessages {
    static getRandomMessage(type) {
        const messages = {
            loading: [
                'Caricamento in corso...',
                'Un momento, stiamo elaborando...',
                'Quasi pronto...',
                'Preparazione dati...'
            ],
            success: [
                'Operazione completata con successo!',
                'Perfetto! Tutto è andato bene.',
                'Fantastico! Operazione riuscita.',
                'Eccellente! Completato con successo.'
            ],
            error: [
                'Ops! Qualcosa è andato storto.',
                'Si è verificato un problema. Riprova.',
                'Errore durante l\'operazione.',
                'Spiacente, qualcosa non ha funzionato.'
            ],
            validation: [
                'Controlla i dati inseriti.',
                'Alcuni campi necessitano correzioni.',
                'Verifica le informazioni inserite.',
                'Completa tutti i campi richiesti.'
            ],
            unauthorized: [
                'Accesso non autorizzato.',
                'Devi effettuare il login per continuare.',
                'Sessione scaduta, riaccedi per favore.',
                'Autorizzazione necessaria per questa azione.'
            ],
            networkError: [
                'Problemi di connessione. Verifica la rete.',
                'Connessione instabile. Riprova.',
                'Errore di rete. Controlla la connessione.',
                'Difficoltà di comunicazione con il server.'
            ]
        };

        if (!messages[type]) {
            return 'Messaggio non disponibile';
        }

        const messageArray = messages[type];
        return messageArray[Math.floor(Math.random() * messageArray.length)];
    }

    static showNotification(type, message = null, duration = 5000) {
        const finalMessage = message || this.getRandomMessage(type);

        // Rimuovi notifiche esistenti
        const existingNotifications = document.querySelectorAll('.dynamic-notification');
        existingNotifications.forEach(notification => notification.remove());

        // Crea nuova notifica
        const notification = document.createElement('div');
        notification.className = `dynamic-notification alert alert-${this.getBootstrapClass(type)} alert-dismissible fade show`;
        notification.classList.add('dynamic-notification');

        notification.innerHTML = `
            <i class="fas ${this.getIcon(type)} me-2"></i>
            ${finalMessage}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto-rimozione
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, duration);
        }

        return notification;
    }

    static getBootstrapClass(type) {
        const classMap = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info',
            loading: 'info',
            validation: 'warning',
            unauthorized: 'warning',
            networkError: 'danger'
        };
        return classMap[type] || 'info';
    }

    static getIcon(type) {
        const iconMap = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle',
            loading: 'fa-spinner fa-spin',
            validation: 'fa-exclamation-triangle',
            unauthorized: 'fa-lock',
            networkError: 'fa-wifi'
        };
        return iconMap[type] || 'fa-info-circle';
    }

    // Messaggi specifici per form
    static getFormMessage(field, error) {
        const fieldMessages = {
            email: {
                required: 'Non dimenticare di inserire la tua email',
                invalid: 'Controlla che l\'email sia scritta correttamente'
            },
            password: {
                required: 'La password è necessaria per continuare',
                tooShort: 'Usa una password più lunga per maggiore sicurezza',
                weak: 'Scegli una password più robusta'
            },
            nickname: {
                required: 'Scegli un nickname per il tuo profilo',
                taken: 'Questo nickname non è disponibile, prova con un altro'
            },
            nome: {
                required: 'Inserisci il tuo nome'
            },
            cognome: {
                required: 'Inserisci il tuo cognome'
            }
        };

        if (fieldMessages[field] && fieldMessages[field][error]) {
            return fieldMessages[field][error];
        }

        return this.getRandomMessage('validation');
    }
}

// Funzioni di utilità globali
window.showSuccess = (message) => FrontendMessages.showNotification('success', message);
window.showError = (message) => FrontendMessages.showNotification('error', message);
window.showWarning = (message) => FrontendMessages.showNotification('warning', message);
window.showInfo = (message) => FrontendMessages.showNotification('info', message);
window.showLoading = (message) => FrontendMessages.showNotification('loading', message, 0);

// Export per uso moderno
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FrontendMessages;
}
