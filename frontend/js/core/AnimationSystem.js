// BOSTARTER Animation System
// Centralized animation management for consistent user interface transitions

class AnimationSystem {
    constructor() {
        this.observers = [];
        this.config = {
            duration: {
                fast: 200,
                normal: 300,
                slow: 500
            },
            easing: {
                standard: 'ease',
                smooth: 'cubic-bezier(0.4, 0, 0.2, 1)',
                bounce: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
                decelerate: 'cubic-bezier(0.0, 0.0, 0.2, 1)',
                accelerate: 'cubic-bezier(0.4, 0.0, 1, 1)'
            },
            animations: {
                fadeIn: [
                    { opacity: 0, transform: 'translateY(20px)' },
                    { opacity: 1, transform: 'translateY(0)' }
                ],
                fadeOut: [
                    { opacity: 1, transform: 'translateY(0)' },
                    { opacity: 0, transform: 'translateY(20px)' }
                ],
                slideInRight: [
                    { transform: 'translateX(100%)', opacity: 0 },
                    { transform: 'translateX(0)', opacity: 1 }
                ],
                slideOutRight: [
                    { transform: 'translateX(0)', opacity: 1 },
                    { transform: 'translateX(100%)', opacity: 0 }
                ],
                slideInLeft: [
                    { transform: 'translateX(-100%)', opacity: 0 },
                    { transform: 'translateX(0)', opacity: 1 }
                ],
                slideOutLeft: [
                    { transform: 'translateX(0)', opacity: 1 },
                    { transform: 'translateX(-100%)', opacity: 0 }
                ],
                zoomIn: [
                    { transform: 'scale(0.9)', opacity: 0 },
                    { transform: 'scale(1)', opacity: 1 }
                ],
                zoomOut: [
                    { transform: 'scale(1)', opacity: 1 },
                    { transform: 'scale(0.9)', opacity: 0 }
                ],
                pulse: [
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.05)' },
                    { transform: 'scale(1)' }
                ]
            }
        };

        // Initialize
        this.setupReducedMotionDetection();
    }

    // Detect reduced motion preference
    setupReducedMotionDetection() {
        this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (event) => {
            this.prefersReducedMotion = event.matches;
            this.notifyObservers('reducedMotionChange', this.prefersReducedMotion);
        });
    }

    // Animate an element with Web Animations API
    animate(element, animationType, options = {}) {
        if (!element) return null;

        // No animations if reduced motion is preferred
        if (this.prefersReducedMotion && !options.ignoreReducedMotion) {
            // Just set final state instantly
            const keyframes = this.config.animations[animationType];
            if (keyframes && keyframes.length) {
                const finalState = keyframes[keyframes.length - 1];
                Object.entries(finalState).forEach(([prop, value]) => {
                    element.style[prop] = value;
                });
            }
            return null;
        }

        const duration = options.duration || this.config.duration.normal;
        const easing = options.easing || this.config.easing.standard;
        const keyframes = options.keyframes || this.config.animations[animationType]; if (!keyframes) {
            // Animation type not found - return null silently
            return null;
        }

        const animation = element.animate(keyframes, {
            duration: duration,
            easing: easing,
            fill: 'forwards',
            iterations: options.iterations || 1,
            delay: options.delay || 0
        });

        // Handle animation complete callback
        if (options.onComplete) {
            animation.onfinish = () => options.onComplete(element, animation);
        }

        return animation;
    }

    // Animate multiple elements with a stagger effect
    stagger(elements, animationType, options = {}) {
        if (!elements || !elements.length) return [];

        const staggerDelay = options.staggerDelay || 100;
        const animations = [];

        elements.forEach((element, index) => {
            const delayedOptions = { ...options, delay: (options.delay || 0) + (index * staggerDelay) };
            const animation = this.animate(element, animationType, delayedOptions);
            animations.push(animation);
        });

        return animations;
    }

    // Setup and observe scroll-based animations
    setupScrollAnimations(selector = '.animate-on-scroll', animationType = 'fadeIn', options = {}) {
        const elements = document.querySelectorAll(selector);
        if (!elements.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animate(entry.target, animationType, options);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: options.threshold || 0.1,
            rootMargin: options.rootMargin || '0px 0px -50px 0px'
        });

        elements.forEach(element => {
            observer.observe(element);
        });

        return observer;
    }

    // Setup hover animations
    setupHoverAnimation(selector, enterAnimation, leaveAnimation, options = {}) {
        const elements = document.querySelectorAll(selector);

        elements.forEach(element => {
            element.addEventListener('mouseenter', () => {
                this.animate(element, enterAnimation, options);
            });

            element.addEventListener('mouseleave', () => {
                this.animate(element, leaveAnimation, options);
            });
        });
    }

    // Animate progress bars
    animateProgressBars(selector = '.progress-bar', options = {}) {
        const progressBars = document.querySelectorAll(selector);

        progressBars.forEach((bar, index) => {
            const width = bar.style.width || bar.dataset.progress;
            if (!width) return;

            // Set initial state
            bar.style.width = '0%';

            // Create observer to animate when visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            bar.style.transition = `width ${options.duration || 1000}ms ${options.easing || this.config.easing.decelerate}`;
                            bar.style.width = width;
                        }, (options.baseDelay || 0) + (index * (options.staggerDelay || 100)));
                        observer.unobserve(bar);
                    }
                });
            }, {
                threshold: options.threshold || 0.1
            });

            observer.observe(bar);
        });
    }

    // Modal animations
    openModal(modal, animation = 'fadeIn', options = {}) {
        if (!modal) return;

        // First make it visible but transparent
        modal.style.display = 'flex';
        modal.style.opacity = '0';

        // Force reflow to ensure transition works
        void modal.offsetWidth;

        // Animate the modal
        return this.animate(modal, animation, {
            duration: options.duration || this.config.duration.normal,
            easing: options.easing || this.config.easing.decelerate,
            onComplete: () => {
                if (options.onComplete) options.onComplete(modal);
            }
        });
    }

    closeModal(modal, animation = 'fadeOut', options = {}) {
        if (!modal) return;

        return this.animate(modal, animation, {
            duration: options.duration || this.config.duration.normal,
            easing: options.easing || this.config.easing.accelerate,
            onComplete: (element) => {
                element.style.display = 'none';
                if (options.onComplete) options.onComplete(modal);
            }
        });
    }

    // Observer pattern
    addObserver(callback) {
        this.observers.push(callback);
    }

    removeObserver(callback) {
        this.observers = this.observers.filter(obs => obs !== callback);
    }

    notifyObservers(event, data) {
        this.observers.forEach(callback => {
            try {
                callback(event, data);
            } catch (error) {
                // Silent error handling for animation observer
            }
        });
    }
}

// Create a singleton instance
const animationSystem = new AnimationSystem();

// Export for ES modules
export default animationSystem;

// Make it available globally
window.AnimationSystem = animationSystem;
