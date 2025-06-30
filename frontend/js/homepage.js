/**
 * BOSTARTER Homepage UI Enhancement
 * Modern homepage interactions and animations
 */
class HomepageUI {
    constructor() {
        this.init();
    }
    init() {
        this.setupAnimations();
        this.setupInteractions();
        this.setupScrollEffects();
        this.setupProjectCards();
        this.setupStatsCounters();
        this.setupMobileMenu();
        this.setupThemeToggle();
        this.setupNotifications();
    }
    setupAnimations() {
        // Hero section animations
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            this.animateHeroSection(heroSection);
        }
        // Reveal animations for other sections
        const revealElements = document.querySelectorAll('[data-reveal]');
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1 }
        );
        revealElements.forEach(el => observer.observe(el));
    }
    animateHeroSection(hero) {
        const elements = hero.querySelectorAll('.animate-in');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            setTimeout(() => {
                el.style.transition = 'all 0.6s ease-out';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 100 * index);
        });
    }
    setupInteractions() {
        // Setup magnetic effect for CTAs
        const magneticButtons = document.querySelectorAll('.magnetic-button');
        magneticButtons.forEach(btn => this.setupMagneticEffect(btn));
        // Setup smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
    setupMagneticEffect(element) {
        const strength = 50;
        const magneticArea = element.getBoundingClientRect();
        element.addEventListener('mousemove', (e) => {
            const x = e.clientX - magneticArea.left - magneticArea.width / 2;
            const y = e.clientY - magneticArea.top - magneticArea.height / 2;
            element.style.transform = `translate(${x / strength}px, ${y / strength}px)`;
        });
        element.addEventListener('mouseleave', () => {
            element.style.transform = 'translate(0px, 0px)';
        });
    }
    setupScrollEffects() {
        let lastScroll = 0;
        const header = document.querySelector('.site-header');
        if (!header) return;
        const handleScroll = () => {
            const currentScroll = window.pageYOffset;
            // Header show/hide logic
            if (currentScroll > lastScroll && currentScroll > 100) {
                header.classList.add('header-hidden');
            } else {
                header.classList.remove('header-hidden');
            }
            // Add shadow when scrolled
            if (currentScroll > 0) {
                header.classList.add('header-scrolled');
            } else {
                header.classList.remove('header-scrolled');
            }
            lastScroll = currentScroll;
        };
        // Use requestAnimationFrame for smooth performance
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        });
    }
    setupProjectCards() {
        const cards = document.querySelectorAll('.project-card');
        cards.forEach(card => {
            this.addCardHoverEffect(card);
            card.addEventListener('click', () => this.openProjectModal(card));
        });
    }
    addCardHoverEffect(card) {
        const handleMouseMove = (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = (y - centerY) / 20;
            const rotateY = -(x - centerX) / 20;
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        };
        const resetCard = () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        };
        card.addEventListener('mousemove', handleMouseMove);
        card.addEventListener('mouseleave', resetCard);
    }
    setupStatsCounters() {
        const stats = document.querySelectorAll('.stat-counter');
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.5 }
        );
        stats.forEach(stat => observer.observe(stat));
    }
    animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000;
        const step = target / duration * 10;
        let current = 0;
        const updateCounter = () => {
            current += step;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };
        requestAnimationFrame(updateCounter);
    }
    setupMobileMenu() {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', () => {
                this.toggleMobileMenu();
            });
        }
        // Close mobile menu when clicking on links
        const mobileLinks = mobileMenu?.querySelectorAll('a');
        mobileLinks?.forEach(link => {
            link.addEventListener('click', () => {
                this.closeMobileMenu();
            });
        });
    }
    toggleMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const toggle = document.getElementById('mobile-menu-toggle');
        if (mobileMenu.style.maxHeight === '0px' || !mobileMenu.style.maxHeight) {
            mobileMenu.style.maxHeight = '400px';
            toggle.setAttribute('aria-expanded', 'true');
            toggle.classList.add('active');
        } else {
            this.closeMobileMenu();
        }
    }
    closeMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const toggle = document.getElementById('mobile-menu-toggle');
        mobileMenu.style.maxHeight = '0px';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.classList.remove('active');
    }
    setupThemeToggle() {
        const toggle = document.querySelector('.theme-toggle');
        if (!toggle) return;
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        toggle.addEventListener('click', () => {
            const newTheme = document.documentElement.getAttribute('data-theme') === 'light'
                ? 'dark'
                : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            // Animate theme icons
            this.updateThemeIcons(newTheme === 'dark');
        });
    }
    updateThemeIcons(isDark) {
        const sunIcon = document.querySelector('.theme-toggle .sun-icon');
        const moonIcon = document.querySelector('.theme-toggle .moon-icon');
        if (sunIcon && moonIcon) {
            if (isDark) {
                sunIcon.style.transform = 'rotate(-90deg) scale(0)';
                moonIcon.style.transform = 'rotate(0) scale(1)';
            } else {
                sunIcon.style.transform = 'rotate(0) scale(1)';
                moonIcon.style.transform = 'rotate(90deg) scale(0)';
            }
        }
    }
    setupNotifications() {
        // Auto-hide existing notifications
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => this.hideNotification(alert), 5000);
        });
    }
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notifications-container');
        if (!container) return;
        const notification = document.createElement('div');
        notification.className = `notification bg-white border-l-4 border-${this.getTypeColor(type)} rounded-lg shadow-lg p-4 transition-all duration-300 transform translate-x-full`;
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas ${this.getTypeIcon(type)} text-${this.getTypeColor(type)}"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900">${message}</p>
                </div>
                <button class="ml-4 flex-shrink-0 text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(notification);
        // Trigger animation
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        // Auto-hide
        setTimeout(() => {
            this.hideNotification(notification);
        }, duration);
    }
    hideNotification(notification) {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }
    getTypeColor(type) {
        const colors = {
            success: 'green-500',
            error: 'red-500',
            warning: 'yellow-500',
            info: 'blue-500'
        };
        return colors[type] || colors.info;
    }
    getTypeIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }
    // Utility method for smooth scrolling
    smoothScrollTo(target) {
        const element = document.querySelector(target);
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
    handleNewsletterSubscription(email) {
        if (!this.validateEmail(email)) {
            this.showNotification('Inserisci un indirizzo email valido', 'error');
            return;
        }
        // Simulate API call
        this.showNotification('Iscrizione in corso...', 'info');
        setTimeout(() => {
            this.showNotification('Iscrizione completata con successo!', 'success');
        }, 1500);
    }
    validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
}
// Initialize homepage UI when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.homepageUI = new HomepageUI();
    // Add global styles for animations
    const styles = `
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
        }
        .notification {
            max-width: 400px;
        }
        #mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        #mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        #mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }
    `;
    const styleSheet = document.createElement('style');
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);
});
// Newsletter form handling
document.addEventListener('DOMContentLoaded', () => {
    const newsletterForm = document.querySelector('form[action*="subscribe"]');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const emailInput = newsletterForm.querySelector('input[type="email"]');
            if (emailInput && window.homepageUI) {
                window.homepageUI.handleNewsletterSubscription(emailInput.value);
                emailInput.value = '';
            }
        });
    }
});
// Export for external use
window.HomepageUI = HomepageUI;
