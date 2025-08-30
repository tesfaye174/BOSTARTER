/**
 * BOSTARTER Core Application JS
 * File principale per funzionalità comuni
 */

// Inizializzazione quando il DOM è caricato
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 BOSTARTER App initialized');

    // Inizializza tutti i tooltip di Bootstrap se presenti
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Inizializza tutti i dropdown di Bootstrap se presenti
    if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    }

    // Inizializza animazioni on scroll
    initScrollAnimations();

    // Inizializza gestione form
    initFormValidation();
});

// Funzioni di utilità globali
window.BOSTARTER = {
    // Formatta numeri come valuta
    formatCurrency: function (amount) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    },

    // Formatta date in formato italiano
    formatDate: function (dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('it-IT');
    },

    // Mostra notifica temporanea
    showNotification: function (message, type = 'info', duration = 5000) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show dynamic-notification`;
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        `;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        // Auto-rimozione
        if (duration > 0) {
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 150);
                }
            }, duration);
        }
    },

    // Valida CSRF token
    validateCSRF: function () {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : null;
    },

    // Fetch con gestione errori e CSRF
    apiCall: async function (url, options = {}) {
        const csrfToken = this.validateCSRF();

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            }
        };

        const mergedOptions = { ...defaultOptions, ...options };
        if (mergedOptions.headers && options.headers) {
            mergedOptions.headers = { ...defaultOptions.headers, ...options.headers };
        }

        try {
            const response = await fetch(url, mergedOptions);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('API call failed:', error);
            this.showNotification('Errore di connessione. Riprova più tardi.', 'danger');
            throw error;
        }
    }
};

// Inizializza animazioni scroll
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    // Osserva elementi con classe animate-initial
    document.querySelectorAll('.animate-initial').forEach(el => {
        observer.observe(el);
    });
}

// Inizializza validazione form
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                BOSTARTER.showNotification('Controlla i campi evidenziati in rosso.', 'warning');
            }
            form.classList.add('was-validated');
        });
    });
}

// Gestione errori JavaScript globali
window.addEventListener('error', function (event) {
    console.error('JavaScript Error:', event.error);
    if (window.BOSTARTER && typeof window.BOSTARTER.showNotification === 'function') {
        window.BOSTARTER.showNotification('Si è verificato un errore. Ricarica la pagina.', 'danger');
    }
});

// Gestione promesse rifiutate
window.addEventListener('unhandledrejection', function (event) {
    console.error('Unhandled Promise Rejection:', event.reason);
    if (window.BOSTARTER && typeof window.BOSTARTER.showNotification === 'function') {
        window.BOSTARTER.showNotification('Errore di rete. Controlla la connessione.', 'danger');
    }
});

// Funzione per caricare dinamicamente script di pagina
function loadPageScript(scriptName) {
    const script = document.createElement('script');
    script.src = `js/${scriptName}.js`;
    script.onerror = () => console.warn(`Could not load script: ${scriptName}.js`);
    document.head.appendChild(script);
}

// === FUNZIONI LOGOUT AVANZATE ===

// Gestione logout con conferma intelligente
function handleLogoutClick(event, logoutUrl) {
    event.preventDefault();

    // Verifica se ci sono form non salvati
    const hasUnsavedChanges = checkUnsavedChanges();

    if (hasUnsavedChanges) {
        BOSTARTER.confirmLogout(logoutUrl, {
            title: 'Attenzione: Modifiche non salvate',
            message: 'Hai delle modifiche non salvate. Sei sicuro di voler uscire?',
            warning: true
        });
    } else {
        // Submit a POST to perform logout (safer than GET)
        submitPostLogout(logoutUrl);
    }
}

// Helper: submit a POST request to logout URL including CSRF token
function submitPostLogout(logoutUrl) {
    // Extract pathname from URL (in case it's absolute)
    var action = logoutUrl.split('?')[0];

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = action;

    // Add CSRF token from meta tag
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = csrfMeta.getAttribute('content');
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}

// Controlla se ci sono modifiche non salvate nei form
function checkUnsavedChanges() {
    const forms = document.querySelectorAll('form');
    let hasChanges = false;

    forms.forEach(form => {
        const formData = new FormData(form);
        const originalData = form.dataset.originalData;

        if (originalData) {
            const original = JSON.parse(originalData);
            for (let [key, value] of formData.entries()) {
                if (original[key] !== value) {
                    hasChanges = true;
                    break;
                }
            }
        }
    });

    return hasChanges;
}

// Aggiungi funzioni al namespace BOSTARTER
window.BOSTARTER = window.BOSTARTER || {};

// Conferma logout personalizzata
BOSTARTER.confirmLogout = function (logoutUrl, options = {}) {
    const defaults = {
        title: 'Conferma Logout',
        message: 'Sei sicuro di voler uscire da BOSTARTER?',
        warning: false
    };

    const config = { ...defaults, ...options };

    // Crea modal di conferma dinamico
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header ${config.warning ? 'bg-warning text-dark' : 'bg-primary text-white'}">
                    <h5 class="modal-title">
                        <i class="fas fa-${config.warning ? 'exclamation-triangle' : 'sign-out-alt'} me-2"></i>
                        ${config.title}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <p>${config.message}</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-danger" id="confirmLogout">
                        <i class="fas fa-sign-out-alt me-2"></i>Sì, Esci
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annulla
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Inizializza modal Bootstrap
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();

    // Gestione conferma
    modal.querySelector('#confirmLogout').addEventListener('click', () => {
        bootstrapModal.hide();
        window.location.href = logoutUrl;
    });

    // Rimuovi modal dopo la chiusura
    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
};

// Auto-logout per inattività (opzionale)
BOSTARTER.setupAutoLogout = function (timeoutMinutes = 30) {
    let timeoutId;
    let warningId;

    function resetTimer() {
        clearTimeout(timeoutId);
        clearTimeout(warningId);

        // Warning a 5 minuti dalla scadenza
        warningId = setTimeout(() => {
            BOSTARTER.showNotification(
                'La sessione scadrà tra 5 minuti per inattività. Muovi il mouse per estenderla.',
                'warning',
                10000
            );
        }, (timeoutMinutes - 5) * 60 * 1000);

        // Logout automatico
        timeoutId = setTimeout(() => {
            BOSTARTER.showNotification('Sessione scaduta per inattività. Verrai disconnesso.', 'danger', 3000);
            setTimeout(() => {
                submitPostLogout('auth/exit.php?reason=timeout');
            }, 3000);
        }, timeoutMinutes * 60 * 1000);
    }

    // Eventi per reset timer
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
        document.addEventListener(event, resetTimer, true);
    });

    resetTimer(); // Avvia il timer
};
