// Dati di esempio per i progetti di design
const designs = [
    {
        id: 1,
        title: 'Interior Minimalista',
        designer: 'Paolo Bianchi',
        category: 'interni',
        image: 'https://source.unsplash.com/random/800x600/?minimal,interior',
        description: 'Progetto di interni con focus sulla semplicità e funzionalità'
    },
    {
        id: 2,
        title: 'Lampada Moderna',
        designer: 'Laura Rossi',
        category: 'prodotto',
        image: 'https://source.unsplash.com/random/800x600/?modern,lamp',
        description: 'Lampada dal design innovativo e sostenibile'
    },
    {
        id: 3,
        title: 'Brand Identity',
        designer: 'Marco Verdi',
        category: 'grafica',
        image: 'https://source.unsplash.com/random/800x600/?brand,design',
        description: 'Progetto di identità visiva per brand emergente'
    }
];

// Dati di esempio per i designer
const designers = [
    {
        name: 'Paolo Bianchi',
        specialty: 'Interior Designer',
        image: 'https://source.unsplash.com/random/200x200/?interior,designer'
    },
    {
        name: 'Laura Rossi',
        specialty: 'Product Designer',
        image: 'https://source.unsplash.com/random/200x200/?product,designer'
    },
    {
        name: 'Marco Verdi',
        specialty: 'Graphic Designer',
        image: 'https://source.unsplash.com/random/200x200/?graphic,designer'
    }
];

// Dati di esempio per gli eventi
const events = [
    {
        date: '25 MAG',
        title: 'Design Week',
        description: 'Esposizione dei migliori progetti di design contemporaneo'
    },
    {
        date: '30 MAG',
        title: 'Workshop di Product Design',
        description: 'Scopri le tecniche di progettazione di prodotti innovativi'
    }
];

// Funzione per creare una card del progetto di design
function createDesignCard(design) {
    return `
        <article class="design-card" data-category="${design.category}">
            <img src="${design.image}" alt="${design.title}">
            <div class="design-info">
                <h3>${design.title}</h3>
                <p class="designer">${design.designer}</p>
                <p>${design.description}</p>
            </div>
        </article>
    `;
}

// Funzione per creare una card del designer
function createDesignerCard(designer) {
    return `
        <div class="artist-card">
            <img src="${designer.image}" alt="${designer.name}">
            <h3>${designer.name}</h3>
            <p>${designer.specialty}</p>
        </div>
    `;
}

// Funzione per creare una card dell'evento
function createEventCard(event) {
    return `
        <div class="event-card">
            <div class="event-date">${event.date}</div>
            <div class="event-info">
                <h3>${event.title}</h3>
                <p>${event.description}</p>
            </div>
        </div>
    `;
}

// Inizializzazione della galleria
function initGallery() {
    const gallery = document.getElementById('design-gallery');
    gallery.innerHTML = designs.map(design => createDesignCard(design)).join('');
}

// Inizializzazione della sezione designer
function initDesigners() {
    const designersGrid = document.getElementById('featured-designers');
    designersGrid.innerHTML = designers.map(designer => createDesignerCard(designer)).join('');
}

// Inizializzazione della sezione eventi
function initEvents() {
    const eventsContainer = document.getElementById('design-events');
    eventsContainer.innerHTML = events.map(event => createEventCard(event)).join('');
}

// Gestione dei filtri
function initFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Rimuovi la classe active da tutti i bottoni
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Aggiungi la classe active al bottone cliccato
            button.classList.add('active');
            
            const filter = button.getAttribute('data-filter');
            const designCards = document.querySelectorAll('.design-card');
            
            designCards.forEach(card => {
                if (filter === 'tutti' || card.getAttribute('data-category') === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

// Inizializzazione della pagina
document.addEventListener('DOMContentLoaded', () => {
    initGallery();
    initDesigners();
    initEvents();
    initFilters();
});