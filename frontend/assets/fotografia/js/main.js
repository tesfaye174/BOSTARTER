// Dati di esempio per le fotografie
const photos = [
    {
        id: 1,
        title: 'Ritratto Urbano',
        photographer: 'Andrea Rossi',
        category: 'ritratti',
        image: 'https://source.unsplash.com/random/800x600/?portrait,urban',
        description: 'Studio sulla vita urbana attraverso i volti della città'
    },
    {
        id: 2,
        title: 'Alba in Montagna',
        photographer: 'Sofia Bianchi',
        category: 'paesaggi',
        image: 'https://source.unsplash.com/random/800x600/?landscape,mountain',
        description: 'Paesaggio montano all\'alba con giochi di luce naturale'
    },
    {
        id: 3,
        title: 'Vita di Strada',
        photographer: 'Marco Verdi',
        category: 'street',
        image: 'https://source.unsplash.com/random/800x600/?street,photography',
        description: 'Istantanea della vita quotidiana nel centro città'
    }
];

// Dati di esempio per i fotografi
const photographers = [
    {
        name: 'Andrea Rossi',
        specialty: 'Ritrattista',
        image: 'https://source.unsplash.com/random/200x200/?photographer,portrait'
    },
    {
        name: 'Sofia Bianchi',
        specialty: 'Fotografa Paesaggista',
        image: 'https://source.unsplash.com/random/200x200/?photographer,landscape'
    },
    {
        name: 'Marco Verdi',
        specialty: 'Street Photographer',
        image: 'https://source.unsplash.com/random/200x200/?photographer,street'
    }
];

// Dati di esempio per gli eventi
const events = [
    {
        date: '18 MAG',
        title: 'Mostra Fotografica',
        description: 'Esposizione di fotografie d\'autore contemporanee'
    },
    {
        date: '23 MAG',
        title: 'Workshop di Street Photography',
        description: 'Tecniche e approcci alla fotografia di strada'
    }
];

// Funzione per creare una card della fotografia
function createPhotoCard(photo) {
    return `
        <article class="photo-card" data-category="${photo.category}">
            <img src="${photo.image}" alt="${photo.title}">
            <div class="photo-info">
                <h3>${photo.title}</h3>
                <p class="photographer">${photo.photographer}</p>
                <p>${photo.description}</p>
            </div>
        </article>
    `;
}

// Funzione per creare una card del fotografo
function createPhotographerCard(photographer) {
    return `
        <div class="artist-card">
            <img src="${photographer.image}" alt="${photographer.name}">
            <h3>${photographer.name}</h3>
            <p>${photographer.specialty}</p>
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
    const gallery = document.getElementById('photo-gallery');
    gallery.innerHTML = photos.map(photo => createPhotoCard(photo)).join('');
}

// Inizializzazione della sezione fotografi
function initPhotographers() {
    const photographersGrid = document.getElementById('featured-photographers');
    photographersGrid.innerHTML = photographers.map(photographer => createPhotographerCard(photographer)).join('');
}

// Inizializzazione della sezione eventi
function initEvents() {
    const eventsContainer = document.getElementById('photo-events');
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
            const photoCards = document.querySelectorAll('.photo-card');
            
            photoCards.forEach(card => {
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
    initPhotographers();
    initEvents();
    initFilters();
});