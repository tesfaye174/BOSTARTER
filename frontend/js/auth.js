document.addEventListener('DOMContentLoaded', function() {
    // Carica i modali nel DOM
    fetch('/frontend/components/auth-modals.html')
        .then(response => response.text())
        .then(html => {
            document.body.insertAdjacentHTML('beforeend', html);
            setupAuthEventListeners();
            setupCreatorLinkHandler();
        });
});

import { validateForm } from './validation.js';

function setupAuthEventListeners() {
    // Event listeners per aprire i modali
    document.getElementById('login-link')?.addEventListener('click', (e) => {
        e.preventDefault();
        openModal('login-modal');
    });

    document.getElementById('register-link')?.addEventListener('click', (e) => {
        e.preventDefault();
        openModal('register-modal');
    });

    // Gestione form login
    document.getElementById('login-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'login');

        try {
            const response = await fetch('/backend/auth_api.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showNotification('Accesso effettuato con successo', 'success');
                setTimeout(() => window.location.href = '/frontend/dashboard.html', 1000);
            } else {
                showError('login-error', data.message);
            }
        } catch (error) {
            showError('login-error', 'Errore durante l\'accesso. Riprova più tardi.');
        }
    });

    // Gestione form registrazione con validazione e retry automatico
    document.getElementById('register-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        // Validazione form
        const validation = validateForm(formData);
        if (!validation.isValid) {
            // Mostra errori di validazione
            Object.entries(validation.errors).forEach(([field, message]) => {
                const input = form.querySelector(`[name="${field}"]`);
                showFormError(input, message);
            });
            return;
        }

        formData.append('action', 'register');

        const maxRetries = 3;
        let retryCount = 0;
        let lastError = null;

        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        const loadingSpinner = document.createElement('span');
        loadingSpinner.className = 'loading-spinner ml-2';

        while (retryCount < maxRetries) {
            try {
                submitButton.disabled = true;
                submitButton.innerHTML = retryCount === 0 ? 
                    'Registrazione in corso...' + loadingSpinner.outerHTML : 
                    `Nuovo tentativo (${retryCount + 1}/${maxRetries})` + loadingSpinner.outerHTML;

                const response = await fetch('/backend/auth_api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    // Resetta eventuali errori precedenti
                    form.querySelectorAll('.form-group.error').forEach(group => {
                        group.classList.remove('error');
                        group.querySelector('.error-message').textContent = '';
                    });

                    showNotification('Registrazione completata con successo! Effettua il login per continuare', 'success');
                    setTimeout(() => switchModal('register-modal', 'login-modal'), 2000);
                    return;
                } else {
                    lastError = data.message;
                    throw new Error(data.message || 'Errore durante la registrazione');
                }
            } catch (error) {
                lastError = error.message;
                retryCount++;

                if (retryCount < maxRetries) {
                    const waitTime = Math.min(1000 * Math.pow(2, retryCount), 5000);
                    showNotification(`Tentativo fallito. Nuovo tentativo tra ${waitTime/1000} secondi...`, 'warning');
                    await new Promise(resolve => setTimeout(resolve, waitTime));
                }
            }
        }

        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;

        // Gestione errori specifici
        if (lastError?.toLowerCase().includes('già registrato')) {
            showError('register-error', 'Questo indirizzo email è già registrato. Prova ad accedere.');
            const emailInput = form.querySelector('[name="email"]');
            showFormError(emailInput, 'Email già registrata');
        } else if (lastError?.toLowerCase().includes('non valido')) {
            showError('register-error', 'Verifica i dati inseriti e riprova.');
        } else {
            showError('register-error', 'Si è verificato un errore durante la registrazione. Per favore, riprova più tardi.');
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        clearErrors();
    }
}

function switchModal(fromModalId, toModalId) {
    closeModal(fromModalId);
    setTimeout(() => openModal(toModalId), 300);
}

function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.text-red-500');
    errorElements.forEach(element => {
        element.textContent = '';
        element.classList.add('hidden');
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white z-50`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

function setupCreatorLinkHandler() {
    const creatorLink = document.getElementById('creator-link');
    if (creatorLink) {
        creatorLink.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const response = await fetch('/backend/user.php');
                const userData = await response.json();
                
                if (response.ok) {
                    if (userData.role === 'creator') {
                        window.location.href = '/frontend/dashboard.html';
                    } else {
                        showNotification('Accesso riservato ai creatori. Registrati come creatore per continuare.', 'error');
                        openModal('register-modal');
                    }
                } else {
                    openModal('login-modal');
                }
            } catch (error) {
                showNotification('Effettua l\'accesso per continuare', 'error');
                openModal('login-modal');
            }
        });
    }
}