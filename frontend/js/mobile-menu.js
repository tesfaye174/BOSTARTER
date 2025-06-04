/**
 * Mobile hamburger menu functionality
 * Handles mobile menu toggle with accessibility features
 */

document.addEventListener('DOMContentLoaded', function () {
    // Hamburger menu mobile accessibility
    const menuBtn = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            const expanded = menuBtn.getAttribute('aria-expanded') === 'true';
            menuBtn.setAttribute('aria-expanded', !expanded);
            mobileMenu.classList.toggle('hidden');
        });
    }
});
