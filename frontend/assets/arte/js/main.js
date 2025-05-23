// Dati di esempio per le opere d'arte
const artworks = [
    {
        id: 1,
        title: 'Armonia in Blu',
        artist: 'Marco Rossi',
        category: 'pittura',
        image: 'https://source.unsplash.com/random/800x600/?painting,blue',
        description: 'Un'esplorazione delle tonalità blu in chiave contemporanea'
    },
    {
        id: 2,
        title: 'Metamorfosi',
        artist: 'Laura Bianchi',
        category: 'scultura',
        image: 'https://source.unsplash.com/random/800x600/?sculpture,modern',
        description: 'Scultura in bronzo che rappresenta la trasformazione'
    },
    {
        id: 3,
        title: 'Pixel Dreams',
        artist: 'Giovanni Verdi',
        category: 'digitale',
        image: 'https://source.unsplash.com/random/800x600/?digital,art',
        description: 'Arte digitale che fonde realtà e immaginazione'
    }
];

// Dati di esempio per gli artisti
const artists = [
    {
        name: 'Marco Rossi',
        specialty: 'Pittore Contemporaneo',
        image: 'https://source.unsplash.com/random/200x200/?artist,painter'
    },
    {
        name: 'Laura Bianchi',
        specialty: 'Scultrice',
        image: 'https://source.unsplash.com/random/200x200/?artist,sculptor'
    },
    {
        name: 'Giovanni Verdi',
        specialty: 'Artista Digitale',
        image: 'https://source.unsplash.com/random/200x200/?artist,digital'
    }
];

// Dati di esempio per gli eventi
const events = [
    {
        date: '15 MAG',
        title: 'Mostra d\'Arte Contemporanea',
        description: 'Una straordinaria esposizione di arte moderna nel cuore della città'
    },
    {
        date: '22 MAG',
        title: 'Workshop di Scultura',
        description: 'Impara le tecniche base della scultura con artisti professionisti'
    }
];

// Funzione per creare una card dell'opera d'arte
function createArtworkCard(artwork) {
    return `
        <article class="art-card" data-category="${artwork.category}">
            <img src="${artwork.image}" alt="${artwork.title}">
            <div class="art-info">
                <h3>${artwork.title}</h3>
                <p class="artist">${artwork.artist}</p>
                <p>${artwork.description}</p>
            </div>
        </article>
    `;
}

// Funzione per creare una card dell'artista
function createArtistCard(artist) {
    return `
        <div class="artist-card">
            <img src="${artist.image}" alt="${artist.name}">
            <h3>${artist.name}</h3>
            <p>${artist.specialty}</p>
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
    const gallery = document.getElementById('art-gallery');
    gallery.innerHTML = artworks.map(artwork => createArtworkCard(artwork)).join('');
}

// Inizializzazione della sezione artisti
function initArtists() {
    const artistsGrid = document.getElementById('featured-artists');
    artistsGrid.innerHTML = artists.map(artist => createArtistCard(artist)).join('');
}

// Inizializzazione della sezione eventi
function initEvents() {
    const eventsContainer = document.getElementById('art-events');
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
            const artCards = document.querySelectorAll('.art-card');
            
            artCards.forEach(card => {
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
    initArtists();
    initEvents();
    initFilters();
});