// Gestione dei filtri e delle card dei progetti
document.addEventListener('DOMContentLoaded', () => {
    // Selettori degli elementi
    const projectsGrid = document.getElementById('projects-grid');
    const categoryButtons = document.querySelectorAll('[data-category]');
    const statusFilter = document.getElementById('status-filter');
    const sortFilter = document.getElementById('sort-filter');

    // Gestione dei filtri per categoria
    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Rimuove la classe attiva da tutti i pulsanti
            categoryButtons.forEach(btn => {
                btn.classList.remove('bg-primary', 'text-white');
                btn.classList.add('text-gray-700', 'hover:bg-gray-200');
                btn.setAttribute('aria-pressed', 'false');
            });

            // Aggiunge la classe attiva al pulsante cliccato
            button.classList.remove('text-gray-700', 'hover:bg-gray-200');
            button.classList.add('bg-primary', 'text-white');
            button.setAttribute('aria-pressed', 'true');

            // Filtra i progetti
            filterProjects();
        });
    });

    // Gestione dei filtri per stato e ordinamento
    statusFilter.addEventListener('change', filterProjects);
    sortFilter.addEventListener('change', filterProjects);

    // Funzione per filtrare i progetti
    function filterProjects() {
        const selectedCategory = document.querySelector('[data-category][aria-pressed="true"]').dataset.category;
        const selectedStatus = statusFilter.value;
        const selectedSort = sortFilter.value;

        const projects = Array.from(projectsGrid.children);

        projects.forEach(project => {
            const projectCategory = project.dataset.category;
            const isVisible = selectedCategory === 'tutti' || projectCategory === selectedCategory;
            project.style.display = isVisible ? 'block' : 'none';

            // Aggiunge una transizione fluida
            if (isVisible) {
                project.classList.remove('opacity-0', 'scale-95');
                project.classList.add('opacity-100', 'scale-100');
            } else {
                project.classList.add('opacity-0', 'scale-95');
                project.classList.remove('opacity-100', 'scale-100');
            }
        });

        // Implementazione dell'ordinamento (da completare in base ai dati reali)
        if (selectedSort === 'finanziati') {
            // Ordina per importo raccolto
        } else if (selectedSort === 'scadenza') {
            // Ordina per giorni rimasti
        } else {
            // Ordina per data (più recenti)
        }
    }

    // Inizializza i filtri al caricamento
    filterProjects();
});

// Funzione per caricare i progetti in evidenza
async function loadFeaturedProjects() {
    try {
        const response = await fetch('/api/projects/featured');
        const projects = await response.json();

        const featuredProjectsContainer = document.getElementById('featured-projects');
        if (!featuredProjectsContainer) return;

        featuredProjectsContainer.innerHTML = projects.map(project => `
            <article class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                <a href="/frontend/project.html?id=${project.id}" class="block">
                    <img src="${project.image_url}" alt="${project.title}" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">${project.title}</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">${project.description}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <img src="${project.creator_avatar}" alt="${project.creator_name}" class="w-8 h-8 rounded-full">
                                <span class="text-sm text-gray-700 dark:text-gray-300">${project.creator_name}</span>
                            </div>
                            <span class="text-primary font-medium">${project.funding_percentage}%</span>
                        </div>
                        <div class="mt-4 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="bg-primary h-2 rounded-full" style="width: ${project.funding_percentage}%"></div>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-sm">
                            <span class="text-gray-600 dark:text-gray-400">${project.funding_amount}€ raccolti</span>
                            <span class="text-gray-600 dark:text-gray-400">${project.days_left} giorni rimasti</span>
                        </div>
                    </div>
                </a>
            </article>
        `).join('');
    } catch (error) {
        console.error('Errore nel caricamento dei progetti in evidenza:', error);
    }
}

// Funzione per animare le statistiche
function animateStats() {
    const stats = {
        projects: { target: 1250, current: 0, element: document.getElementById('stats-projects') },
        creators: { target: 850, current: 0, element: document.getElementById('stats-creators') },
        backers: { target: 15000, current: 0, element: document.getElementById('stats-backers') },
        funded: { target: 2500000, current: 0, element: document.getElementById('stats-funded') }
    };

    const duration = 2000; // 2 secondi per l'animazione
    const steps = 60; // 60 step per un'animazione fluida
    const interval = duration / steps;

    Object.entries(stats).forEach(([key, stat]) => {
        if (!stat.element) return;

        const increment = stat.target / steps;
        let current = 0;
        let step = 0;

        const animate = () => {
            step++;
            current += increment;

            if (step <= steps) {
                if (key === 'funded') {
                    stat.element.textContent = Math.round(current).toLocaleString('it-IT') + '€';
                } else {
                    stat.element.textContent = Math.round(current).toLocaleString('it-IT');
                }
                setTimeout(animate, interval);
            } else {
                // Assicuriamoci che il valore finale sia esatto
                if (key === 'funded') {
                    stat.element.textContent = stat.target.toLocaleString('it-IT') + '€';
                } else {
                    stat.element.textContent = stat.target.toLocaleString('it-IT');
                }
            }
        };

        animate();
    });
}

// Funzione per osservare quando le statistiche entrano nel viewport
function setupStatsObserver() {
    const statsSection = document.querySelector('section:has(#stats-projects)');
    if (!statsSection) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateStats();
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    observer.observe(statsSection);
}

// Inizializzazione
document.addEventListener('DOMContentLoaded', () => {
    loadFeaturedProjects();
    setupStatsObserver();
});