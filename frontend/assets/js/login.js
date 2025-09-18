// Script per la pagina di login BOSTARTER
// Gestisce l'attivazione dinamica del campo codice amministratore

document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const adminContainer = document.getElementById('adminCodeContainer');
    const adminCodeInput = document.getElementById('admin_code');

    if (emailInput && adminContainer && adminCodeInput) {
        // Funzione per mostrare/nascondere il campo admin
        function toggleAdminField(show) {
            if (show) {
                adminContainer.style.display = 'block';
                adminContainer.classList.add('animate-fade-up');
                adminCodeInput.required = true;
                adminCodeInput.focus();
            } else {
                adminContainer.style.display = 'none';
                adminCodeInput.required = false;
                adminCodeInput.value = '';
            }
        }

        // Controlla se ci sono errori relativi al codice amministratore
        const errorAlert = document.querySelector('.alert-danger');
        if (errorAlert && errorAlert.textContent.toLowerCase().includes('codice amministratore')) {
            toggleAdminField(true);
        }

        // Controlla se il campo admin è già visibile (impostato dal server)
        if (adminContainer.style.display === 'block') {
            adminCodeInput.required = true;
        }

        // Controllo dinamico basato sull'email
        emailInput.addEventListener('input', function() {
            const email = this.value.toLowerCase().trim();

            // Mostra il campo se l'email contiene "admin" o se è un dominio amministrativo
            const isAdmin = email.includes('admin') ||
                          email.includes('@bostarter.it') ||
                          email.includes('@admin.') ||
                          email.includes('.admin');

            toggleAdminField(isAdmin);
        });

        // Controllo anche al blur (quando l'utente finisce di digitare)
        emailInput.addEventListener('blur', function() {
            const email = this.value.toLowerCase().trim();

            // Se l'email sembra essere di un admin ma il campo non è visibile, mostralo
            if ((email.includes('admin') || email.includes('@bostarter.it')) &&
                adminContainer.style.display === 'none') {
                // Piccolo delay per UX migliore
                setTimeout(() => toggleAdminField(true), 300);
            }
        });
    }

    // Animazioni di focus per gli input
    document.querySelectorAll('.form-control').forEach(function(input) {
        input.addEventListener('focus', function() {
            if (this.parentElement) {
                this.parentElement.classList.add('focused');
            }
        });

        input.addEventListener('blur', function() {
            if (this.parentElement) {
                this.parentElement.classList.remove('focused');
            }
        });
    });

    // Auto-hide alerts dopo 5 secondi
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            try {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } catch (e) {
                // Fallback se Bootstrap non è disponibile
                alert.style.display = 'none';
            }
        });
    }, 5000);
});
