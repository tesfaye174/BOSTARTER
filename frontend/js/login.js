/**
 * BOSTARTER Authentication UI Enhancement
 * Modern login form interactions and validations
 */
// Modern Auth/Login UX
class LoginForm {
    constructor() {
        this.form = document.querySelector('.auth-form');
        this.loginBtn = document.getElementById('loginBtn');
        this.email = document.getElementById('email');
        this.password = document.getElementById('password');
        this.toggleBtn = document.querySelector('.password-toggle');
        this.init();
    }
    init() {
        if (this.email) {
            this.email.addEventListener('input', () => this.validateEmail());
        }
        if (this.password) {
            this.password.addEventListener('input', () => this.validatePassword());
        }
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.togglePassword());
        }
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    }
    validateEmail() {
        if (!this.email) return true;
        const value = this.email.value.trim();
        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        this.setFieldState(this.email, valid, valid ? '' : 'Email non valida');
        return valid;
    }
    validatePassword() {
        if (!this.password) return true;
        const value = this.password.value;
        const valid = value.length >= 8;
        this.setFieldState(this.password, valid, valid ? '' : 'Minimo 8 caratteri');
        return valid;
    }
    setFieldState(field, valid, message) {
        field.classList.toggle('invalid', !valid);
        field.classList.toggle('valid', valid);
        let errorDiv = field.parentElement.querySelector('.field-error');
        if (!valid) {
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                field.parentElement.appendChild(errorDiv);
            }
            errorDiv.textContent = message;
        } else if (errorDiv) {
            errorDiv.remove();
        }
    }
    togglePassword() {
        if (!this.password || !this.toggleBtn) return;
        const iconShow = this.toggleBtn.querySelector('.show-icon');
        const iconHide = this.toggleBtn.querySelector('.hide-icon');
        if (this.password.type === 'password') {
            this.password.type = 'text';
            if (iconShow) iconShow.style.display = 'none';
            if (iconHide) iconHide.style.display = 'inline';
            this.toggleBtn.setAttribute('aria-label', 'Nascondi password');
        } else {
            this.password.type = 'password';
            if (iconShow) iconShow.style.display = 'inline';
            if (iconHide) iconHide.style.display = 'none';
            this.toggleBtn.setAttribute('aria-label', 'Mostra password');
        }
    }
    handleSubmit(e) {
        let valid = this.validateEmail() & this.validatePassword();
        if (!valid) {
            e.preventDefault();
            if (this.email && !this.email.classList.contains('valid')) this.email.focus();
        } else if (this.loginBtn) {
            this.loginBtn.disabled = true;
            this.loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accesso in corso...';
        }
    }
}
document.addEventListener('DOMContentLoaded', () => { new LoginForm(); });
// Miglioramento UX login: focus automatico, animazioni, validazione accessibile
window.addEventListener('DOMContentLoaded', function () {
    // Focus automatico su primo campo vuoto
    const firstInput = document.querySelector('.auth-card input:not([type=hidden]):not([disabled])');
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
    const password = document.getElementById('password');
    if (email) {
        email.addEventListener('input', function () {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.classList.add('invalid');
            } else {
                email.classList.remove('invalid');
            }
        });
    }
    if (password) {
        password.addEventListener('input', function () {
            if (password.value.length < 8) {
                password.classList.add('invalid');
            } else {
                password.classList.remove('invalid');
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
function togglePassword(fieldId) {
    if (window.authUI) {
        window.authUI.togglePasswordVisibility(fieldId);
    }
}
function openPasswordReset() {
    if (window.authUI) {
        window.authUI.openModal('passwordResetModal');
    }
}
function closePasswordReset() {
    if (window.authUI) {
        window.authUI.closeModal('passwordResetModal');
    }
}
// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.authUI = new AuthUI();
});

