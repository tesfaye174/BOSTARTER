// Utility functions for BOSTARTER

const Utils = {
    // Funzione per formattare le date
    formatDate(date) {
        return new Intl.DateTimeFormat('it-IT', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    },

    // Funzione per formattare le valute
    formatCurrency(amount) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    },

    // Funzione per calcolare la percentuale di progresso
    calculateProgress(current, target) {
        return Math.min(Math.round((current / target) * 100), 100);
    },

    // Funzione per validare gli input
    validateInput(value, rules) {
        const validations = {
            required: (val) => val && val.trim().length > 0,
            email: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val),
            minLength: (val, min) => val && val.length >= min,
            maxLength: (val, max) => val && val.length <= max,
            numeric: (val) => /^\d+$/.test(val),
            url: (val) => /^(https?:\/\/)?[\w\-]+(\.[\w\-]+)+[\w\-\.,@?^=%&:/~\+#]*$/.test(val)
        };

        for (const [rule, value] of Object.entries(rules)) {
            if (!validations[rule](value)) {
                return false;
            }
        }
        return true;
    },

    // Funzione per gestire gli errori
    handleError(error, element) {
        element.classList.add('error');
        const errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        errorMessage.textContent = error;
        element.appendChild(errorMessage);

        setTimeout(() => {
            element.classList.remove('error');
            errorMessage.remove();
        }, 3000);
    },

    // Funzione per mostrare notifiche
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    },

    // Funzione per gestire il debounce
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Funzione per gestire il throttle
    throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // Funzione per copiare negli appunti
    copyToClipboard(text) {
        return navigator.clipboard.writeText(text)
            .then(() => this.showNotification('Testo copiato negli appunti!', 'success'))
            .catch(() => this.showNotification('Impossibile copiare il testo', 'error'));
    },

    // Funzione per generare slug
    generateSlug(text) {
        return text
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)+/g, '');
    }
};

export default Utils;