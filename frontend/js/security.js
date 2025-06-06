/**
 * Frontend Security Utilities for BOSTARTER
 */

class SecurityUtils {
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    static sanitizeInput(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }
    
    static validatePasswordStrength(password) {
        const requirements = {
            minLength: password.length >= 8,
            hasUpper: /[A-Z]/.test(password),
            hasLower: /[a-z]/.test(password),
            hasNumber: /\d/.test(password),
            hasSpecial: /[^A-Za-z0-9]/.test(password)
        };
        
        const score = Object.values(requirements).filter(req => req).length;
        
        return {
            score: score,
            isValid: score >= 4,
            requirements: requirements,
            strength: score <= 2 ? 'weak' : score <= 3 ? 'medium' : 'strong'
        };
    }
    
    static addCSRFToken() {
        // Add CSRF token to forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (!form.querySelector('input[name="csrf_token"]')) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = this.generateCSRFToken();
                form.appendChild(csrfInput);
            }
        });
    }
    
    static generateCSRFToken() {
        return Array.from(crypto.getRandomValues(new Uint8Array(32)))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    }
}

// Auto-initialize security features
document.addEventListener('DOMContentLoaded', function() {
    SecurityUtils.addCSRFToken();
});