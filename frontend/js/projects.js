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