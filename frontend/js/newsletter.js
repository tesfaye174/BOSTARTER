document.addEventListener('DOMContentLoaded', () => {
    const newsletterForm = document.querySelector('.newsletter-form');
    const emailInput = document.querySelector('.newsletter-email');
    const subscribeButton = document.querySelector('.newsletter-subscribe');
    const feedbackMessage = document.createElement('p');
    feedbackMessage.className = 'mt-3 text-sm';
    let isSubmitting = false;

    // Aggiunge il messaggio di feedback dopo il form
    newsletterForm.appendChild(feedbackMessage);

    // Validazione email avanzata in tempo reale
    emailInput.addEventListener('input', () => {
        const email = emailInput.value.trim();
        const isValid = validateEmail(email);
        const isEmpty = email === '';

        emailInput.classList.toggle('border-red-500', !isValid && !isEmpty);
        emailInput.classList.toggle('border-green-500', isValid && !isEmpty);
        subscribeButton.disabled = !isValid || isEmpty;
        subscribeButton.classList.toggle('opacity-50', !isValid || isEmpty);

        if (!isEmpty) {
            showFeedback(
                isValid ? 'Email valido!' : 'Inserisci un indirizzo email valido',
                isValid ? 'success' : 'error'
            );
        } else {
            feedbackMessage.textContent = '';
        }
    });

    // Gestione sottoscrizione avanzata con feedback visivi
    newsletterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (isSubmitting) return;

        const email = emailInput.value.trim();
        if (!validateEmail(email)) {
            showFeedback('Inserisci un indirizzo email valido', 'error');
            emailInput.focus();
            return;
        }

        try {
            isSubmitting = true;
            subscribeButton.classList.add('btn-loading');
            subscribeButton.disabled = true;

            // Chiamata API per l'iscrizione alla newsletter
            const response = await fetch(API_CONFIG.BASE_URL + API_CONFIG.ENDPOINTS.NEWSLETTER.SUBSCRIBE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();
            
            if (data.success) {
                showFeedback('Iscrizione completata con successo! Ti invieremo una email di conferma.', 'success');
            } else {
                throw new Error(data.error || 'Errore durante l\'iscrizione');
            }
            emailInput.value = '';
            subscribeButton.classList.add('success');
            
            // Tracciamento evento con dati aggiuntivi
            trackEvent('newsletter_subscription', { 
                email,
                timestamp: new Date().toISOString(),
                source: 'website'
            });
        } catch (error) {
            showFeedback('Si è verificato un errore. Per favore riprova più tardi.', 'error');
            console.error('Errore durante l\'iscrizione:', error);
        } finally {
            isSubmitting = false;
            subscribeButton.classList.remove('btn-loading');
            subscribeButton.disabled = false;
        }
    });

    // Funzione di validazione email migliorata
    function validateEmail(email) {
        if (!email) return false;
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(email) && email.length <= 254 && !email.startsWith('.') && !email.endsWith('.');
    }

    // Funzione per mostrare feedback
    function showFeedback(message, type) {
        feedbackMessage.textContent = message;
        feedbackMessage.className = `mt-3 text-sm ${
            type === 'success' ? 'text-green-600' : 'text-red-600'
        }`;
    }

    // Funzione per il tracciamento eventi (placeholder)
    function trackEvent(eventName, data) {
        console.log('Event tracked:', eventName, data);
        // Implementare con il sistema di analytics scelto
    }
}));