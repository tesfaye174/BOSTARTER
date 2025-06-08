/**
 * BOSTARTER Mobile Responsiveness Optimizer
 * Handles responsive design, touch interactions, and mobile performance
 */

class MobileOptimizer {
    constructor() {
        this.isMobile = this.detectMobile();
        this.isTablet = this.detectTablet();
        this.orientation = this.getOrientation();
        this.touchCapable = 'ontouchstart' in window;
        this.viewportWidth = window.innerWidth;
        this.viewportHeight = window.innerHeight;

        this.init();
    }

    init() {
        this.setupViewportOptimization();
        this.optimizeForMobile();
        this.setupTouchInteractions();
        this.optimizeScrolling();
        this.setupOrientationHandling();
        this.optimizeForms();
        this.setupResizeHandler();
    }

    detectMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    detectTablet() {
        return /iPad|Android(?!.*Mobile)/i.test(navigator.userAgent);
    }

    getOrientation() {
        return window.innerHeight > window.innerWidth ? 'portrait' : 'landscape';
    }

    setupViewportOptimization() {
        // Ensure proper viewport meta tag
        let viewport = document.querySelector('meta[name="viewport"]');
        if (!viewport) {
            viewport = document.createElement('meta');
            viewport.name = 'viewport';
            document.head.appendChild(viewport);
        }

        // Set optimal viewport settings
        viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover';

        // Add safe area support for devices with notches
        if (this.isMobile) {
            this.addSafeAreaSupport();
        }
    }

    addSafeAreaSupport() {
        const style = document.createElement('style');
        style.textContent = `
            :root {
                --safe-area-inset-top: env(safe-area-inset-top, 0px);
                --safe-area-inset-right: env(safe-area-inset-right, 0px);
                --safe-area-inset-bottom: env(safe-area-inset-bottom, 0px);
                --safe-area-inset-left: env(safe-area-inset-left, 0px);
            }

            .safe-area-padding {
                padding-top: var(--safe-area-inset-top);
                padding-right: var(--safe-area-inset-right);
                padding-bottom: var(--safe-area-inset-bottom);
                padding-left: var(--safe-area-inset-left);
            }

            .safe-area-margin {
                margin-top: var(--safe-area-inset-top);
                margin-right: var(--safe-area-inset-right);
                margin-bottom: var(--safe-area-inset-bottom);
                margin-left: var(--safe-area-inset-left);
            }
        `;
        document.head.appendChild(style);
    }

    optimizeForMobile() {
        if (!this.isMobile) return;

        document.body.classList.add('mobile-device');

        // Optimize tap targets
        this.optimizeTapTargets();

        // Reduce animation complexity on mobile
        this.optimizeMobileAnimations();

        // Optimize images for mobile
        this.optimizeMobileImages();

        // Enable hardware acceleration for smoother scrolling
        this.enableHardwareAcceleration();
    }

    optimizeTapTargets() {
        const style = document.createElement('style');
        style.textContent = `
            .mobile-device button,
            .mobile-device .btn,
            .mobile-device a,
            .mobile-device input[type="button"],
            .mobile-device input[type="submit"],
            .mobile-device .clickable {
                min-height: 44px;
                min-width: 44px;
                position: relative;
            }

            .mobile-device button:before,
            .mobile-device .btn:before,
            .mobile-device a:before {
                content: '';
                position: absolute;
                top: -10px;
                left: -10px;
                right: -10px;
                bottom: -10px;
                z-index: -1;
            }

            .mobile-device .form-control {
                min-height: 44px;
                font-size: 16px; /* Prevents zoom on iOS */
            }
        `;
        document.head.appendChild(style);
    }

    optimizeMobileAnimations() {
        if (this.isMobile) {
            const style = document.createElement('style');
            style.textContent = `
                .mobile-device * {
                    animation-duration: 0.3s !important;
                    transition-duration: 0.3s !important;
                }

                .mobile-device .complex-animation {
                    animation: none !important;
                }

                .mobile-device .parallax {
                    transform: none !important;
                }
            `;
            document.head.appendChild(style);
        }
    }

    optimizeMobileImages() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            // Add loading="lazy" for images not in viewport
            if (!img.hasAttribute('loading')) {
                img.loading = 'lazy';
            }

            // Reduce image quality on slow connections
            if ('connection' in navigator && navigator.connection.effectiveType === 'slow-2g') {
                this.reduceMobileImageQuality(img);
            }
        });
    }

    reduceMobileImageQuality(img) {
        const src = img.src;
        if (src && !src.includes('q=')) {
            const separator = src.includes('?') ? '&' : '?';
            img.src = `${src}${separator}q=70&format=webp`;
        }
    }

    enableHardwareAcceleration() {
        const style = document.createElement('style');
        style.textContent = `
            .mobile-device {
                -webkit-transform: translateZ(0);
                transform: translateZ(0);
                -webkit-backface-visibility: hidden;
                backface-visibility: hidden;
                -webkit-perspective: 1000;
                perspective: 1000;
            }

            .mobile-device .scroll-container {
                -webkit-overflow-scrolling: touch;
                overflow-scrolling: touch;
            }
        `;
        document.head.appendChild(style);
    }

    setupTouchInteractions() {
        if (!this.touchCapable) return;

        // Remove hover states on touch devices
        this.removeMobileHoverStates();

        // Add touch-friendly interactions
        this.addTouchGestures();

        // Prevent double-tap zoom on buttons
        this.preventDoubleTapZoom();
    }

    removeMobileHoverStates() {
        const style = document.createElement('style');
        style.textContent = `
            @media (hover: none) and (pointer: coarse) {
                .hover-effect:hover {
                    transform: none !important;
                    background-color: initial !important;
                    color: initial !important;
                }
            }
        `;
        document.head.appendChild(style);
    }

    addTouchGestures() {
        let startY = 0;
        let startX = 0;

        document.addEventListener('touchstart', (e) => {
            startY = e.touches[0].clientY;
            startX = e.touches[0].clientX;
        }, { passive: true });

        document.addEventListener('touchmove', (e) => {
            const currentY = e.touches[0].clientY;
            const currentX = e.touches[0].clientX;
            const deltaY = startY - currentY;
            const deltaX = startX - currentX;

            // Custom gesture handling can be added here
            this.handleTouchGesture(deltaX, deltaY, e);
        }, { passive: true });
    }

    handleTouchGesture(deltaX, deltaY, event) {
        // Pull-to-refresh gesture
        if (deltaY < -100 && window.scrollY === 0) {
            this.triggerPullToRefresh();
        }

        // Swipe gestures for navigation
        if (Math.abs(deltaX) > 100 && Math.abs(deltaY) < 50) {
            if (deltaX > 0) {
                this.handleSwipeLeft();
            } else {
                this.handleSwipeRight();
            }
        }
    } triggerPullToRefresh() {
        // Implement pull-to-refresh functionality
        // Add visual feedback and refresh logic
    }

    handleSwipeLeft() {
        // Handle left swipe (e.g., next page)
    }

    handleSwipeRight() {
        // Handle right swipe (e.g., previous page)
    }

    preventDoubleTapZoom() {
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (e) => {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    }

    optimizeScrolling() {
        // Smooth scrolling for mobile
        if (this.isMobile) {
            document.documentElement.style.scrollBehavior = 'smooth';

            // Optimize scroll performance
            let ticking = false;
            const updateScrollPosition = () => {
                // Update scroll-dependent elements efficiently
                this.handleScrollOptimization();
                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(updateScrollPosition);
                    ticking = true;
                }
            }, { passive: true });
        }
    }

    handleScrollOptimization() {
        // Throttled scroll handling for mobile performance
        const scrollTop = window.pageYOffset;

        // Hide/show navigation on scroll
        const header = document.querySelector('.header, .navbar');
        if (header) {
            if (scrollTop > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
    } setupOrientationHandling() {
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.handleOrientationChange();
            }, 100);
        });

        // Also listen for resize events
        window.addEventListener('resize', Utils.debounce(() => {
            this.handleResize();
        }, 250));
    }

    handleOrientationChange() {
        const newOrientation = this.getOrientation();
        if (newOrientation !== this.orientation) {
            this.orientation = newOrientation;
            document.body.className = document.body.className.replace(/orientation-\w+/, '');
            document.body.classList.add(`orientation-${newOrientation}`);

            // Trigger re-layout
            this.optimizeForCurrentOrientation();
        }
    }

    optimizeForCurrentOrientation() {
        // Adjust layout based on orientation
        if (this.orientation === 'landscape' && this.isMobile) {
            // Optimize for landscape mobile view
            this.optimizeLandscapeLayout();
        } else {
            // Optimize for portrait view
            this.optimizePortraitLayout();
        }
    }

    optimizeLandscapeLayout() {
        const style = document.createElement('style');
        style.id = 'landscape-optimizations';
        style.textContent = `
            @media screen and (orientation: landscape) and (max-height: 500px) {
                .mobile-device .header {
                    height: 40px;
                }
                
                .mobile-device .hero-section {
                    padding: 20px 0;
                }
                
                .mobile-device .btn {
                    padding: 8px 16px;
                }
            }
        `;

        // Remove existing landscape styles
        const existing = document.getElementById('landscape-optimizations');
        if (existing) existing.remove();

        document.head.appendChild(style);
    }

    optimizePortraitLayout() {
        const landscapeStyles = document.getElementById('landscape-optimizations');
        if (landscapeStyles) {
            landscapeStyles.remove();
        }
    }

    optimizeForms() {
        if (!this.isMobile) return;

        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            // Optimize form inputs for mobile
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                this.optimizeFormInput(input);
            });

            // Add mobile-friendly validation
            this.addMobileValidation(form);
        });
    }

    optimizeFormInput(input) {
        // Set appropriate input types and attributes
        if (input.type === 'email') {
            input.autocomplete = 'email';
            input.inputMode = 'email';
        } else if (input.type === 'tel') {
            input.inputMode = 'tel';
        } else if (input.type === 'number') {
            input.inputMode = 'numeric';
        }

        // Prevent zoom on focus for iOS
        if (input.type === 'text' || input.type === 'email' || input.type === 'password') {
            input.style.fontSize = '16px';
        }

        // Add touch-friendly styling
        input.addEventListener('focus', () => {
            input.closest('.form-group')?.classList.add('focused');
        });

        input.addEventListener('blur', () => {
            input.closest('.form-group')?.classList.remove('focused');
        });
    }

    addMobileValidation(form) {
        // Add visual feedback for mobile users
        const style = document.createElement('style');
        style.textContent = `
            .mobile-device .form-group.focused {
                transform: scale(1.02);
                transition: transform 0.2s ease;
            }
            
            .mobile-device .form-control:invalid {
                border-color: #ff4444;
                box-shadow: 0 0 0 2px rgba(255, 68, 68, 0.2);
            }
            
            .mobile-device .form-control:valid {
                border-color: #22c55e;
            }
        `;
        document.head.appendChild(style);
    }

    setupResizeHandler() {
        this.handleResize = Utils.debounce(() => {
            const newWidth = window.innerWidth;
            const newHeight = window.innerHeight;

            if (Math.abs(newWidth - this.viewportWidth) > 100 ||
                Math.abs(newHeight - this.viewportHeight) > 100) {

                this.viewportWidth = newWidth;
                this.viewportHeight = newHeight;

                // Re-optimize for new dimensions
                this.optimizeForCurrentViewport();
            }
        }, 250);

        window.addEventListener('resize', this.handleResize);
    }

    optimizeForCurrentViewport() {
        // Adjust optimizations based on current viewport
        if (this.viewportWidth < 768) {
            document.body.classList.add('mobile-layout');
            document.body.classList.remove('desktop-layout');
        } else {
            document.body.classList.add('desktop-layout');
            document.body.classList.remove('mobile-layout');
        }
    }

    // Get mobile performance metrics
    getPerformanceMetrics() {
        return {
            isMobile: this.isMobile,
            isTablet: this.isTablet,
            touchCapable: this.touchCapable,
            orientation: this.orientation,
            viewportSize: {
                width: this.viewportWidth,
                height: this.viewportHeight
            },
            devicePixelRatio: window.devicePixelRatio || 1,
            connection: navigator.connection ? {
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                saveData: navigator.connection.saveData
            } : null
        };
    }
}

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.MobileOptimizer = new MobileOptimizer();
    });
} else {
    window.MobileOptimizer = new MobileOptimizer();
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MobileOptimizer;
}
