// Gestione della newsletter
class NewsletterManager {
    constructor() {
        this.form = document.getElementById('newsletter-form');
        this.emailInput = document.getElementById('newsletter-email');
        this.submitButton = document.getElementById('newsletter-submit');
        this.statusMessage = document.getElementById('newsletter-status');

        if (this.form) {
            this.initializeEventListeners();
        }
    }

    initializeEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        this.emailInput?.addEventListener('input', () => this.validateEmail());
    }

    validateEmail() {
        const email = this.emailInput.value;
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

        this.emailInput.classList.toggle('border-red-500', !isValid);
        this.submitButton.disabled = !isValid;

        return isValid;
    }

    showStatus(message, isError = false) {
        if (!this.statusMessage) return;

        this.statusMessage.textContent = message;
        this.statusMessage.classList.remove('text-green-600', 'text-red-600');
        this.statusMessage.classList.add(isError ? 'text-red-600' : 'text-green-600');
        this.statusMessage.classList.remove('hidden');

        // Nascondi il messaggio dopo 5 secondi
        setTimeout(() => {
            this.statusMessage.classList.add('hidden');
        }, 5000);
    }

    async handleSubmit(e) {
        e.preventDefault();

        if (!this.validateEmail()) {
            this.showStatus('Inserisci un indirizzo email valido', true);
            return;
        }

        const email = this.emailInput.value;

        try {
            this.submitButton.disabled = true;
            this.submitButton.innerHTML = `
                <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Iscrizione in corso...
            `;

            const response = await fetch('/api/newsletter/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email })
            });

            if (!response.ok) {
                throw new Error('Errore durante l\'iscrizione');
            }

            const data = await response.json();
            this.showStatus('Iscrizione completata con successo!');
            this.emailInput.value = '';
            this.submitButton.disabled = true;

        } catch (error) {
            console.error('Errore newsletter:', error);
            this.showStatus('Si è verificato un errore durante l\'iscrizione. Riprova più tardi.', true);

        } finally {
            this.submitButton.disabled = false;
            this.submitButton.innerHTML = 'Iscriviti';
        }
    }
}

// Inizializzazione
document.addEventListener('DOMContentLoaded', () => {
    new NewsletterManager();
});