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
        // Real-time validation
        this.inputs.forEach(input => {
            input.addEventListener('input', () => this.validateInput(input));
            input.addEventListener('blur', () => this.validateInput(input, true));
        });
        // Form-level validation
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (this.validateForm()) {
                this.submitForm();
            }
        });
    }
    validateInput(input, isFinal = false) {
        const value = input.value.trim();
        const name = input.name;
        let isValid = true;
        let message = '';
        switch (name) {
            case 'email':
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                isValid = emailRegex.test(value);
                message = isValid ? '' : 'Please enter a valid email address';
                break;
            case 'password':
                const hasUpperCase = /[A-Z]/.test(value);
                const hasLowerCase = /[a-z]/.test(value);
                const hasNumbers = /\d/.test(value);
                const hasSpecialChar = /[!@#$%^&*]/.test(value);
                const isLongEnough = value.length >= 8;
                isValid = hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChar && isLongEnough;
                if (!isValid && isFinal) {
                    message = 'Password must contain at least 8 characters, including uppercase, lowercase, numbers and special characters';
                }
                break;
            case 'confirmPassword':
                const password = this.form.querySelector('input[name="password"]').value;
                isValid = value === password;
                message = isValid ? '' : 'Passwords do not match';
                break;
            case 'username':
                const usernameRegex = /^[a-zA-Z0-9_-]{3,20}$/;
                isValid = usernameRegex.test(value);
                message = isValid ? '' : 'Username must be 3-20 characters and may contain letters, numbers, underscores and hyphens';
                break;
            default:
                isValid = value.length > 0;
                message = isValid ? '' : 'This field is required';
        }
        this.updateInputStyle(input, isValid, message);
        return isValid;
    }
    updateInputStyle(input, isValid, message) {
        const formGroup = input.closest('.form-group');
        const feedback = formGroup.querySelector('.validation-feedback');
        input.classList.toggle('is-valid', isValid && input.value.length > 0);
        input.classList.toggle('is-invalid', !isValid && input.value.length > 0);
        if (feedback) {
            feedback.textContent = message;
            feedback.classList.toggle('text-danger', !isValid);
            feedback.classList.toggle('text-success', isValid);
        }
        this.updateSubmitButtonState();
    }
    setupPasswordStrength() {
        const passwordInput = this.form.querySelector('input[name="password"]');
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength-meter mt-2';
        passwordInput.parentNode.appendChild(strengthIndicator);
        passwordInput.addEventListener('input', () => {
            const strength = this.calculatePasswordStrength(passwordInput.value);
            this.updatePasswordStrengthIndicator(strength, strengthIndicator);
        });
    }
    calculatePasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score += 20;
        if (password.length >= 12) score += 10;
        // Character variety
        if (/[A-Z]/.test(password)) score += 20;
        if (/[a-z]/.test(password)) score += 20;
        if (/[0-9]/.test(password)) score += 20;
        if (/[^A-Za-z0-9]/.test(password)) score += 20;
        // Complexity bonus
        if (score >= 80 && password.length >= 12) score += 10;
        return Math.min(100, score);
    }
    updatePasswordStrengthIndicator(strength, indicator) {
        const strengthClass = strength < 40 ? 'weak' :
            strength < 70 ? 'medium' : 'strong';
        indicator.className = `password-strength-meter mt-2 strength-${strengthClass}`;
        indicator.style.width = `${strength}%`;
        const strengthText = strength < 40 ? 'Weak' :
            strength < 70 ? 'Medium' : 'Strong';
        indicator.setAttribute('data-strength', strengthText);
    }
    async submitForm() {
        try {
            this.setLoadingState(true);
            const formData = new FormData(this.form);
            const response = await fetch('/api/register', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await response.json();
            if (response.ok) {
                this.showSuccess('Registration successful! Redirecting...');
                setTimeout(() => window.location.href = '/login', 2000);
            } else {
                throw new Error(data.message || 'Registration failed');
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.setLoadingState(false);
        }
    }
    setLoadingState(isLoading) {
        this.submitButton.disabled = isLoading;
        this.submitButton.innerHTML = isLoading ?
            '<span class="spinner-border spinner-border-sm me-2"></span>Processing...' :
            'Register';
    }
    showSuccess(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success mt-3 animate-fade-in';
        alert.textContent = message;
        this.form.insertAdjacentElement('beforebegin', alert);
    }
    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger mt-3 animate-fade-in';
        alert.textContent = message;
        this.form.insertAdjacentElement('beforebegin', alert);
    }
    updateSubmitButtonState() {
        const isValid = Array.from(this.inputs).every(input =>
            input.classList.contains('is-valid')
        );
        this.submitButton.disabled = !isValid;
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

