/**
 * BOSTARTER Registration Form Enhancement
 * Advanced form validation, UX improvements, and security features
 */

// Modern Registration Form Handler
class ModernRegistrationForm {
    constructor() {
        this.form = document.getElementById('registerForm');
        this.inputs = this.form.querySelectorAll('input:not([type="hidden"])');
        this.submitButton = this.form.querySelector('button[type="submit"]');

        this.init();
    }

    init() {
        this.setupValidation();
        this.setupPasswordStrength();
        this.setupFormSubmission();
        this.addInputEffects();
    }

    setupValidation() {
        this.inputs.forEach(input => {
            input.addEventListener('input', () => this.validateInput(input));
            input.addEventListener('blur', () => this.validateInput(input));
        });
    }

    validateInput(input) {
        const value = input.value.trim();
        let isValid = true;
        let message = '';

        switch (input.id) {
            case 'email':
                isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                message = isValid ? '' : 'Email non valida';
                break;
            case 'password':
                isValid = value.length >= 8;
                message = isValid ? '' : 'Minimo 8 caratteri';
                break;
            case 'password_confirm':
                const password = document.getElementById('password').value;
                isValid = value === password;
                message = isValid ? '' : 'Le password non coincidono';
                break;
            case 'nome':
            case 'cognome':
                isValid = value.length >= 2;
                message = isValid ? '' : 'Minimo 2 caratteri';
                break;
        }

        this.updateInputStyle(input, isValid, message);
        return isValid;
    }

    updateInputStyle(input, isValid, message) {
        input.classList.toggle('valid', isValid);
        input.classList.toggle('invalid', !isValid);

        let messageElement = input.parentElement.querySelector('.validation-message');
        if (!messageElement && message) {
            messageElement = document.createElement('div');
            messageElement.className = 'validation-message';
            input.parentElement.appendChild(messageElement);
        }
        if (messageElement) {
            messageElement.textContent = message;
            messageElement.style.display = message ? 'block' : 'none';
        }
    }

    setupPasswordStrength() {
        const passwordInput = document.getElementById('password');
        if (!passwordInput) return;

        passwordInput.addEventListener('input', () => {
            const strength = this.calculatePasswordStrength(passwordInput.value);
            this.updatePasswordStrengthIndicator(strength);
        });
    }

    calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^A-Za-z0-9]/)) strength++;
        return (strength / 5) * 100;
    }

    updatePasswordStrengthIndicator(strength) {
        const strengthDiv = document.querySelector('.password-strength');
        if (!strengthDiv) return;

        let color = '#ff4444';
        if (strength > 60) color = '#ffbb33';
        if (strength > 80) color = '#00C851';

        strengthDiv.style.width = strength + '%';
        strengthDiv.style.backgroundColor = color;
        strengthDiv.style.height = '3px';
        strengthDiv.style.transition = 'all 0.3s ease';
        strengthDiv.style.marginTop = '5px';
    }

    setupFormSubmission() {
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();

            let isValid = true;
            this.inputs.forEach(input => {
                if (!this.validateInput(input)) {
                    isValid = false;
                }
            });

            if (isValid) {
                this.submitButton.disabled = true;
                this.submitButton.innerHTML = '<span class="spinner"></span> Creazione account...';
                this.form.submit();
            }
        });
    }

    addInputEffects() {
        this.inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('focused');
            });
        });
    }
}

// Initialize the form when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new ModernRegistrationForm();
});

// Miglioramento UX registrazione: focus automatico, animazioni, validazione accessibile
window.addEventListener('DOMContentLoaded', function () {
    // Focus automatico su primo campo vuoto
    const firstInput = document.querySelector('.registration-card input:not([type=hidden]):not([disabled])');
    if (firstInput && !firstInput.value) {
        setTimeout(() => firstInput.focus(), 300);
    }
    // Animazione di ingresso per i gruppi
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach((group, idx) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(20px)';
        setTimeout(() => {
            group.style.transition = 'all 0.3s';
            group.style.opacity = '1';
            group.style.transform = 'translateY(0)';
        }, idx * 80 + 200);
    });
    // Validazione accessibile
    const email = document.getElementById('email');
    if (email) {
        email.addEventListener('input', function () {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.classList.add('invalid');
            } else {
                email.classList.remove('invalid');
            }
        });
    }
    // Accessibilità: aria-live per errori
    const alerts = document.querySelectorAll('.alert[role=alert]');
    alerts.forEach(alert => {
        alert.setAttribute('aria-live', 'assertive');
    });
});

// Global functions for backward compatibility
window.togglePassword = function (fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.parentElement.querySelector('button');
    if (window.registrationForm) {
        window.registrationForm.togglePasswordVisibility(fieldId, button);
    }
};
