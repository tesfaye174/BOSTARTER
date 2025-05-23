// Dati di esempio per i contenuti musicali
const music = [
    {
        id: 1,
        title: 'Sinfonia del Mattino',
        musician: 'Roberto Bianchi',
        category: 'classica',
        image: 'https://source.unsplash.com/random/800x600/?classical,music',
        description: 'Composizione classica contemporanea per orchestra'
    },
    {
        id: 2,
        title: 'Blue Night',
        musician: 'Marco Rossi',
        category: 'jazz',
        image: 'https://source.unsplash.com/random/800x600/?jazz,music',
        description: 'Improvvisazione jazz con influenze moderne'
    },
    {
        id: 3,
        title: 'Digital Dreams',
        musician: 'Laura Verdi',
        category: 'elettronica',
        image: 'https://source.unsplash.com/random/800x600/?electronic,music',
        description: 'Composizione elettronica sperimentale'
    }
];

// Dati di esempio per i musicisti
const musicians = [
    {
        name: 'Roberto Bianchi',
        specialty: 'Compositore Classico',
        image: 'https://source.unsplash.com/random/200x200/?classical,musician'
    },
    {
        name: 'Marco Rossi',
        specialty: 'Jazzista',
        image: 'https://source.unsplash.com/random/200x200/?jazz,musician'
    },
    {
        name: 'Laura Verdi',
        specialty: 'Producer Elettronico',
        image: 'https://source.unsplash.com/random/200x200/?electronic,musician'
    }
];

// Dati di esempio per gli eventi
const events = [
    {
        date: '28 MAG',
        title: 'Concerto di Musica Classica',
        description: 'Serata dedicata alle nuove composizioni classiche'
    },
    {
        date: '31 MAG',
        title: 'Festival Jazz',
        description: 'Esibizione di talenti emergenti della scena jazz'
    }
];

// Funzione per creare una card del contenuto musicale
function createMusicCard(music) {
    return `
        <article class="music-card" data-category="${music.category}">
            <img src="${music.image}" alt="${music.title}">
            <div class="music-info">
                <h3>${music.title}</h3>
                <p class="musician">${music.musician}</p>
                <p>${music.description}</p>
            </div>
        </article>
    `;
}

// Funzione per creare una card del musicista
function createMusicianCard(musician) {
    return `
        <div class="artist-card">
            <img src="${musician.image}" alt="${musician.name}">
            <h3>${musician.name}</h3>
            <p>${musician.specialty}</p>
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
    const gallery = document.getElementById('music-gallery');
    gallery.innerHTML = music.map(item => createMusicCard(item)).join('');
}

// Inizializzazione della sezione musicisti
function initMusicians() {
    const musiciansGrid = document.getElementById('featured-musicians');
    musiciansGrid.innerHTML = musicians.map(musician => createMusicianCard(musician)).join('');
}

// Inizializzazione della sezione eventi
function initEvents() {
    const eventsContainer = document.getElementById('music-events');
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
            const musicCards = document.querySelectorAll('.music-card');
            
            musicCards.forEach(card => {
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
    initMusicians();
    initEvents();
    initFilters();
});