/**
 * BOSTARTER - JavaScript Essenziale
 * Funzionalità di base per il sistema
 */
(function (window, document) {
    "use strict";

    /**
     * Inizializzazione principale
     */
    function init() {
        setupNavbar();
        setupForms();
        setupModals();
        setupTooltips();
    }

    /**
     * Setup navbar mobile
     */
    function setupNavbar() {
        const navbar = document.querySelector('.navbar-toggler');
        if (navbar) {
            navbar.addEventListener('click', function () {
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                if (target) {
                    target.classList.toggle('show');
                }
            });
        }
    }

    /**
     * Setup form validation
     */
    function setupForms() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', function (e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    }

    /**
     * Setup modals
     */
    function setupModals() {
        // Auto-focus sui modali
        document.addEventListener('shown.bs.modal', function (e) {
            const firstInput = e.target.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        });
    }

    /**
     * Setup tooltips Bootstrap
     */
    function setupTooltips() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
    }

    // Inizializzazione
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export API essenziale
    window.BOSTARTER = {
        version: "1.0.0",
        init: init
    };

})(window, document);
