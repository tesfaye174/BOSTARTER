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
        // Loading animation
        window.addEventListener('load', () => {
            this.hideLoadingOverlay();
            this.animateHeroSection();
        });

        // Intersection Observer for reveal animations
        this.setupRevealAnimations();
    }

    hideLoadingOverlay() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.style.display = 'none';
                overlay.remove();
            }, 500);
        }
    }

    animateHeroSection() {
        const heroElements = document.querySelectorAll('.hero-section h1, .hero-section p, .hero-section .btn');
        heroElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';

            setTimeout(() => {
                element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 200 + 300);
        });
    }

    setupRevealAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Observe elements for animation
        document.querySelectorAll('[data-aos], .statistic-card, .project-card').forEach(el => {
            observer.observe(el);
        });
    }

    setupInteractions() {
        // Add magnetic effect to CTA buttons
        this.setupMagneticEffect();

        // Add hover effects to navigation items
        this.setupNavigationEffects();

        // Add parallax effect to floating elements
        this.setupParallaxEffect();
    }

    setupMagneticEffect() {
        const magneticElements = document.querySelectorAll('.magnetic, .btn-primary');

        magneticElements.forEach(element => {
            element.addEventListener('mousemove', (e) => {
                const rect = element.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;

                element.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px) scale(1.02)`;
            });

            element.addEventListener('mouseleave', () => {
                element.style.transform = 'translate(0, 0) scale(1)';
            });
        });
    }

    setupNavigationEffects() {
        const navItems = document.querySelectorAll('nav a');

        navItems.forEach(item => {
            item.addEventListener('mouseenter', () => {
                item.style.transform = 'translateY(-2px)';
            });

            item.addEventListener('mouseleave', () => {
                item.style.transform = 'translateY(0)';
            });
        });
    }

    setupParallaxEffect() {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.parallax-element');

            parallaxElements.forEach(element => {
                const speed = element.dataset.speed || 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    }

    setupScrollEffects() {
        // Navbar scroll effect
        let lastScrollTop = 0;
        const navbar = document.querySelector('header');

        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down
                navbar.style.transform = 'translateY(-100%)';
            } else {
                // Scrolling up
                navbar.style.transform = 'translateY(0)';
            }

            // Add blur effect when scrolling
            if (scrollTop > 50) {
                navbar.classList.add('bg-white/95');
                navbar.style.backdropFilter = 'blur(20px)';
            } else {
                navbar.classList.remove('bg-white/95');
                navbar.style.backdropFilter = 'blur(10px)';
            }

            lastScrollTop = scrollTop;
        });
    }

    setupProjectCards() {
        const projectCards = document.querySelectorAll('.project-card, article[role="listitem"]');

        projectCards.forEach(card => {
            // Add click interaction
            card.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    this.openProjectModal(card);
                }
            });

            // Add hover effects
            card.addEventListener('mouseenter', () => {
                this.addCardHoverEffect(card);
            });

            card.addEventListener('mouseleave', () => {
                this.removeCardHoverEffect(card);
            });
        });
    }

    addCardHoverEffect(card) {
        const image = card.querySelector('img');
        const progressBar = card.querySelector('[role="progressbar"] div');

        if (image) {
            image.style.transform = 'scale(1.1)';
        }

        if (progressBar) {
            progressBar.style.animationPlayState = 'running';
        }
    }

    removeCardHoverEffect(card) {
        const image = card.querySelector('img');

        if (image) {
            image.style.transform = 'scale(1)';
        }
    }

    openProjectModal(card) {
        // Simulate project modal opening
        const projectTitle = card.querySelector('h3')?.textContent || 'Progetto';
        this.showNotification(`Apertura dettagli: ${projectTitle}`, 'info');
    }

    setupStatsCounters() {
        const counters = document.querySelectorAll('.text-3xl');

        const animateCounter = (counter) => {
            const target = parseInt(counter.textContent.replace(/[^\d]/g, ''));
            const duration = 2000;
            const start = performance.now();

            const animate = (currentTime) => {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);

                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const current = Math.floor(target * easeOutQuart);

                const originalText = counter.textContent;
                const suffix = originalText.replace(/[\d,]/g, '');
                counter.textContent = current.toLocaleString() + suffix;

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };

            requestAnimationFrame(animate);
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        });

        counters.forEach(counter => observer.observe(counter));
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
        const themeToggle = document.getElementById('theme-toggle');

        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                this.toggleTheme();
            });
        }

        // Load saved theme
        this.loadTheme();
    }

    toggleTheme() {
        const body = document.body;
        const isDark = body.classList.toggle('dark');

        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        this.updateThemeIcons(isDark);

        this.showNotification(
            `Tema ${isDark ? 'scuro' : 'chiaro'} attivato`,
            'success'
        );
    }

    loadTheme() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        const isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);

        if (isDark) {
            document.body.classList.add('dark');
        }

        this.updateThemeIcons(isDark);
    }

    updateThemeIcons(isDark) {
        const sunIcon = document.querySelector('.ri-sun-line');
        const moonIcon = document.querySelector('.ri-moon-line');

        if (sunIcon && moonIcon) {
            if (isDark) {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            } else {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
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

    // Method to handle newsletter subscription
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
