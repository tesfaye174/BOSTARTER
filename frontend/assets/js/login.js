// Script per la pagina di login BOSTARTER
// Gestisce l'attivazione dinamica del campo codice amministratore

document.addEventListener('DOMContentLoaded', function() {
    // Attiva campo codice amministratore quando email contiene "admin"
    const emailInput = document.getElementById('email');
    const adminContainer = document.getElementById('adminCodeContainer');
    const adminCodeInput = document.getElementById('admin_code');

    if (emailInput && adminContainer && adminCodeInput) {
        emailInput.addEventListener('input', function() {
            const isAdmin = this.value.toLowerCase().includes('admin');

            if (isAdmin) {
                adminContainer.style.display = 'block';
                adminContainer.classList.add('animate-fade-up');
                adminCodeInput.required = true;
            } else {
                adminContainer.style.display = 'none';
                adminCodeInput.required = false;
                adminCodeInput.value = '';
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
