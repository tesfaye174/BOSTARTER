/**
 * BOSTARTER Master JavaScript - Sistema Unificato
 * Versione Ottimizzata Bootstrap 5.3.3
 */
(function(window, document) {
    "use strict";
    class BOSTARTERMaster {
        constructor() {
            this.version = "2.0.0";
            this.framework = "Bootstrap 5.3.3";
            this.cache = new Map();
            this.observers = new Map();
            this.init();
        }
        init() {
            this.setupNavbar();
            this.setupAnimations();
            this.setupInteractions();
            this.setupForms();
            this.setupModals();
            this.setupTooltips();
            console.log(` BOSTARTER Master v${this.version} - Ready!`);
        }
        // === NAVBAR === //
        setupNavbar() {
            const navbar = document.querySelector(".navbar-bostarter");
            if (!navbar) return;
            let lastScroll = 0;
            let ticking = false;
            const updateNavbar = () => {
                const currentScroll = window.pageYOffset;
                if (currentScroll > 100) {
                    navbar.classList.add("scrolled");
                } else {
                    navbar.classList.remove("scrolled");
                }
                // Auto-hide on mobile
                if (window.innerWidth <= 768) {
                    if (currentScroll > lastScroll && currentScroll > 200) {
                        navbar.style.transform = "translateY(-100%)";
                    } else {
                        navbar.style.transform = "translateY(0)";
                    }
                }
                lastScroll = currentScroll;
                ticking = false;
            };
            window.addEventListener("scroll", () => {
                if (!ticking) {
                    requestAnimationFrame(updateNavbar);
                    ticking = true;
                }
            });
        }
        // === ANIMATIONS === //
        setupAnimations() {
            if (!("IntersectionObserver" in window)) return;
            const animationObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        // Counter animation
                        if (target.hasAttribute("data-counter")) {
                            this.animateCounter(target);
                        }
                        // Progress bars
                        if (target.classList.contains("progress-bar")) {
                            this.animateProgressBar(target);
                        }
                        // General animations
                        if (target.classList.contains("animate-on-scroll")) {
                            target.classList.add("animate-fade-up");
                        }
                        animationObserver.unobserve(target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: "0px 0px -50px 0px"
            });
            // Observe elements
            document.querySelectorAll("[data-counter], .progress-bar, .animate-on-scroll").forEach(el => {
                animationObserver.observe(el);
            });
            this.observers.set("animation", animationObserver);
        }
        animateCounter(element) {
            const target = parseInt(element.getAttribute("data-counter"));
            const duration = 2000;
            const steps = 60;
            const increment = target / steps;
            let current = 0;
            const timer = setInterval(() => {
                current = Math.min(target, current + increment);
                // Smart formatting
                if (target >= 1000000) {
                    element.textContent = (current / 1000000).toFixed(1) + "M";
                } else if (target >= 1000) {
                    element.textContent = Math.floor(current / 1000) + "K";
                } else {
                    element.textContent = Math.floor(current).toLocaleString("it-IT");
                }
                if (current >= target) {
                    clearInterval(timer);
                    // Final formatting
                    if (target >= 1000000) {
                        element.textContent = (target / 1000000).toFixed(1) + "M";
                    } else if (target >= 1000) {
                        element.textContent = Math.floor(target / 1000) + "K";
                    } else {
                        element.textContent = target.toLocaleString("it-IT");
                    }
                }
            }, duration / steps);
        }
        animateProgressBar(progressBar) {
            const targetWidth = progressBar.getAttribute("data-width") || progressBar.style.width;
            if (!targetWidth) return;
            progressBar.style.width = "0%";
            setTimeout(() => {
                progressBar.style.width = targetWidth;
            }, 100);
        }
        // === INTERACTIONS === //
        setupInteractions() {
            // Smooth scroll for anchor links
            document.addEventListener("click", (e) => {
                const link = e.target.closest("a[href^=\"#\"]");
                if (!link) return;
                const targetId = link.getAttribute("href");
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault();
                    const offsetTop = targetElement.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: "smooth"
                    });
                }
            });
            // Card hover effects
            document.querySelectorAll(".card-bostarter").forEach(card => {
                card.addEventListener("mouseenter", () => {
                    card.style.transform = "translateY(-8px)";
                });
                card.addEventListener("mouseleave", () => {
                    card.style.transform = "translateY(0)";
                });
            });
        }
        // === FORMS === //
        setupForms() {
            document.addEventListener("submit", (e) => {
                const form = e.target;
                if (!form.classList.contains("needs-validation")) return;
                e.preventDefault();
                e.stopPropagation();
                const isValid = this.validateForm(form);
                if (isValid) {
                    this.showNotification("Form inviato con successo!", "success");
                    // Allow form submission
                    form.classList.remove("needs-validation");
                    form.submit();
                } else {
                    this.showNotification("Controlla i campi del form", "danger");
                }
                form.classList.add("was-validated");
            });
        }
        validateForm(form) {
            let isValid = true;
            const requiredFields = form.querySelectorAll("[required]");
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add("is-invalid");
                    isValid = false;
                } else {
                    field.classList.remove("is-invalid");
                    field.classList.add("is-valid");
                }
            });
            return isValid;
        }
        // === MODALS === //
        setupModals() {
            if (typeof bootstrap === "undefined") return;
            // Auto-focus first input in modals
            document.addEventListener("shown.bs.modal", (e) => {
                const modal = e.target;
                const firstInput = modal.querySelector("input, textarea, select");
                if (firstInput) {
                    firstInput.focus();
                }
            });
        }
        // === TOOLTIPS === //
        setupTooltips() {
            if (typeof bootstrap === "undefined") return;
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    animation: true,
                    delay: { show: 300, hide: 100 }
                });
            });
            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"popover\"]"));
            popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl, {
                    animation: true,
                    trigger: "hover focus"
                });
            });
        }
        // === PUBLIC API === //
        showNotification(message, type = "primary", duration = 5000) {
            const notification = document.createElement("div");
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 400px;
                animation: slideInRight 0.3s ease-out;
            `;
            notification.innerHTML = `
                <i class="fas fa-${this.getIconForType(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.remove("show");
                    setTimeout(() => {
                        notification.remove();
                    }, 150);
                }
            }, duration);
        }
        getIconForType(type) {
            const icons = {
                success: "check-circle",
                danger: "exclamation-triangle",
                warning: "exclamation-circle",
                info: "info-circle",
                primary: "bell"
            };
            return icons[type] || "bell";
        }
        showLoading(element, text = "Caricamento...") {
            const originalContent = element.innerHTML;
            element.innerHTML = `<span class="loading-spinner me-2"></span>${text}`;
            element.disabled = true;
            return () => {
                element.innerHTML = originalContent;
                element.disabled = false;
            };
        }
        // Cache management
        setCache(key, value, ttl = 300000) { // 5 minutes default
            this.cache.set(key, {
                value,
                expires: Date.now() + ttl
            });
        }
        getCache(key) {
            const item = this.cache.get(key);
            if (!item) return null;
            if (Date.now() > item.expires) {
                this.cache.delete(key);
                return null;
            }
            return item.value;
        }
        // Cleanup
        destroy() {
            this.observers.forEach(observer => observer.disconnect());
            this.cache.clear();
        }
    }
    // Auto-initialize when DOM is ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
            window.BOSTARTER = new BOSTARTERMaster();
        });
    } else {
        window.BOSTARTER = new BOSTARTERMaster();
    }
    // Add global styles
    const globalStyles = document.createElement("style");
    globalStyles.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }
        .animate-fade-up {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
    `;
    document.head.appendChild(globalStyles);
})(window, document);
