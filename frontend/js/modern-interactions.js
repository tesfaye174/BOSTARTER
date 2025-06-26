// Modern Interactions for BOSTARTER
document.addEventListener('DOMContentLoaded', function () {
    // Intersection Observer per animazioni al scroll
    const animateOnScroll = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        animateOnScroll.observe(el);
    });

    // Gestione moderna della search bar
    const searchBar = document.querySelector('.search-bar input');
    if (searchBar) {
        let timeout = null;
        searchBar.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                // Aggiunta classe per effetto di ricerca
                searchBar.parentElement.classList.add('searching');

                // Simulazione ricerca
                fetch(`/api/search?q=${e.target.value}`)
                    .then(res => res.json())
                    .then(data => {
                        // Gestione risultati
                    })
                    .finally(() => {
                        searchBar.parentElement.classList.remove('searching');
                    });
            }, 500);
        });
    }

    // Progress bars animate
    const animateProgress = () => {
        document.querySelectorAll('.progress-bar').forEach(bar => {
            const fill = bar.querySelector('.progress-fill');
            if (fill) {
                const target = fill.getAttribute('data-target');
                fill.style.width = '0%';
                setTimeout(() => {
                    fill.style.width = target + '%';
                }, 100);
            }
        });
    };
    animateProgress();

    // Hover effects per le cards
    document.querySelectorAll('.project-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.classList.add('card-hover');
        });
        card.addEventListener('mouseleave', () => {
            card.classList.remove('card-hover');
        });
    });

    // Menu mobile moderno
    const mobileMenu = document.querySelector('.mobile-menu-button');
    const nav = document.querySelector('.nav-links');
    if (mobileMenu && nav) {
        mobileMenu.addEventListener('click', () => {
            nav.classList.toggle('nav-open');
            document.body.classList.toggle('menu-open');
        });
    }

    // Gestione scroll navbar
    let lastScroll = 0;
    const navbar = document.querySelector('.modern-nav');
    if (navbar) {
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            if (currentScroll <= 0) {
                navbar.classList.remove('scroll-up');
                return;
            }

            if (currentScroll > lastScroll && !navbar.classList.contains('scroll-down')) {
                navbar.classList.remove('scroll-up');
                navbar.classList.add('scroll-down');
            } else if (currentScroll < lastScroll && navbar.classList.contains('scroll-down')) {
                navbar.classList.remove('scroll-down');
                navbar.classList.add('scroll-up');
            }
            lastScroll = currentScroll;
        });
    }

    // Like/Bookmark animations
    document.querySelectorAll('.action-button').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            this.classList.add('animate');
            setTimeout(() => this.classList.remove('animate'), 600);
        });
    });

    // Lazy loading immagini
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    } else {
        // Fallback per browser più vecchi
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
        document.body.appendChild(script);
    }
});

// Tema dinamico
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
}

// Sistema di preferenze colore
const userPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
const savedTheme = localStorage.getItem('theme') || (userPrefersDark ? 'dark' : 'light');
setTheme(savedTheme);

// Gestione switch tema
const themeToggle = document.querySelector('.theme-toggle');
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        setTheme(currentTheme === 'dark' ? 'light' : 'dark');
    });
}
