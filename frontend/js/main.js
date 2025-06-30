/**
 * BOSTARTER - Main JavaScript (Bootstrap Enhanced)
 */
(function() {
    "use strict";
    // Inizializzazione
    document.addEventListener("DOMContentLoaded", function() {
        console.log("BOSTARTER: Sistema Bootstrap inizializzato");
        // Tema dinamico
        const savedTheme = localStorage.getItem("theme") || "light";
        document.documentElement.setAttribute("data-bs-theme", savedTheme);
        // Gestione mobile menu
        const navbar = document.querySelector(".navbar-toggler");
        if (navbar) {
            navbar.addEventListener("click", function() {
                this.classList.toggle("collapsed");
            });
        }
        // Lazy loading immagini
        if ("IntersectionObserver" in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.add("loaded");
                        imageObserver.unobserve(img);
                    }
                });
            });
            document.querySelectorAll("img[loading=\"lazy\"]").forEach(img => {
                imageObserver.observe(img);
            });
        }
        // Tooltips Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        // Popovers Bootstrap
        const popoverTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"popover\"]"));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
    // API pubblica
    window.BOSTARTER = {
        version: "2.0.0",
        framework: "Bootstrap 5.3.3",
        initialized: true,
        showToast: function(message, type = "primary") {
            const toastHtml = `
                <div class="toast align-items-center text-bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            let container = document.querySelector(".toast-container");
            if (!container) {
                container = document.createElement("div");
                container.className = "toast-container position-fixed bottom-0 end-0 p-3";
                document.body.appendChild(container);
            }
            container.insertAdjacentHTML("beforeend", toastHtml);
            const toast = new bootstrap.Toast(container.lastElementChild);
            toast.show();
        }
    };
})();
