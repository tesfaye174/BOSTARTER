// Dati di esempio per i prodotti artigianali
const crafts = [
    {
        id: 1,
        title: 'Vaso in Ceramica',
        artisan: 'Maria Bianchi',
        category: 'ceramica',
        image: 'https://source.unsplash.com/random/800x600/?ceramic,vase',
        description: 'Vaso fatto a mano con tecniche tradizionali'
    },
    {
        id: 2,
        title: 'Tavolo in Legno',
        artisan: 'Giuseppe Verdi',
        category: 'legno',
        image: 'https://source.unsplash.com/random/800x600/?wooden,table',
        description: 'Tavolo artigianale in legno massello'
    },
    {
        id: 3,
        title: 'Sciarpa Tessuta',
        artisan: 'Anna Rossi',
        category: 'tessuti',
        image: 'https://source.unsplash.com/random/800x600/?woven,scarf',
        description: 'Sciarpa tessuta a mano con lana pregiata'
    }
];

// Dati di esempio per gli artigiani
const artisans = [
    {
        name: 'Maria Bianchi',
        specialty: 'Ceramista',
        image: 'https://source.unsplash.com/random/200x200/?ceramist'
    },
    {
        name: 'Giuseppe Verdi',
        specialty: 'Falegname',
        image: 'https://source.unsplash.com/random/200x200/?carpenter'
    },
    {
        name: 'Anna Rossi',
        specialty: 'Tessitrice',
        image: 'https://source.unsplash.com/random/200x200/?weaver'
    }
];

// Dati di esempio per gli eventi
const events = [
    {
        date: '20 MAG',
        title: 'Workshop di Ceramica',
        description: 'Impara le tecniche base della lavorazione della ceramica'
    },
    {
        date: '27 MAG',
        title: 'Dimostrazione di Tessitura',
        description: 'Scopri l\'arte della tessitura tradizionale'
    }
];

// Funzione per creare una card del prodotto artigianale
function createCraftCard(craft) {
    return `
        <article class="craft-card" data-category="${craft.category}">
            <img src="${craft.image}" alt="${craft.title}">
            <div class="craft-info">
                <h3>${craft.title}</h3>
                <p class="artisan">${craft.artisan}</p>
                <p>${craft.description}</p>
            </div>
        </article>
    `;
}

// Funzione per creare una card dell'artigiano
function createArtisanCard(artisan) {
    return `
        <div class="artist-card">
            <img src="${artisan.image}" alt="${artisan.name}">
            <h3>${artisan.name}</h3>
            <p>${artisan.specialty}</p>
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
    const gallery = document.getElementById('craft-gallery');
    gallery.innerHTML = crafts.map(craft => createCraftCard(craft)).join('');
}

// Inizializzazione della sezione artigiani
function initArtisans() {
    const artisansGrid = document.getElementById('featured-artisans');
    artisansGrid.innerHTML = artisans.map(artisan => createArtisanCard(artisan)).join('');
}

// Inizializzazione della sezione eventi
function initEvents() {
    const eventsContainer = document.getElementById('craft-events');
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
            const craftCards = document.querySelectorAll('.craft-card');
            
            craftCards.forEach(card => {
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
    initArtisans();
    initEvents();
    initFilters();
});