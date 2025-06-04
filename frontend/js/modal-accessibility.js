/**
 * Login modal accessibility enhancements
 * Handles modal behavior with focus trap, ESC key, and click outside functionality
 */

document.addEventListener('DOMContentLoaded', function () {
    // Miglioramento accessibilità modale login
    const loginModal = document.getElementById('login-modal');
    const closeBtn = document.getElementById('close-login-modal');
    const loginContent = document.getElementById('login-modal-content');

    if (!loginModal || !closeBtn || !loginContent) return;

    // Chiudi con ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !loginModal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // Chiudi cliccando fuori
    loginModal.addEventListener('mousedown', function (e) {
        if (e.target === loginModal) {
            closeModal();
        }
    });

    // Focus trap
    loginModal.addEventListener('transitionend', function () {
        if (!loginModal.classList.contains('hidden')) {
            const focusableElement = loginContent.querySelector('input, button, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusableElement) {
                focusableElement.focus();
            }
        }
    });

    // Chiudi con bottone
    closeBtn.addEventListener('click', function () {
        closeModal();
    });

    function closeModal() {
        loginModal.classList.add('hidden');
        loginModal.classList.add('opacity-0');
    }
});
