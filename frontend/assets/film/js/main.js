document.addEventListener('DOMContentLoaded', () => {
    // Dati di esempio per i film
    const films = [
        {
            id: 1,
            titolo: 'Il Sogno di Maria',
            regista: 'Laura Bianchi',
            categoria: 'cortometraggi',
            immagine: 'path/to/film1.jpg',
            descrizione: 'Un viaggio onirico attraverso i ricordi d\'infanzia'
        },
        {
            id: 2,
            titolo: 'Vita di Strada',
            regista: 'Marco Rossi',
            categoria: 'documentari',
            immagine: 'path/to/film2.jpg',
            descrizione: 'La vita quotidiana nei quartieri popolari'
        },
        // Altri film...
    ];

    // Dati di esempio per i registi
    const directors = [
        {
            id: 1,
            nome: 'Laura Bianchi',
            foto: 'path/to/director1.jpg',
            specialita: 'Cortometraggi sperimentali'
        },
        {
            id: 2,
            nome: 'Marco Rossi',
            foto: 'path/to/director2.jpg',
            specialita: 'Documentari sociali'
        },
        // Altri registi...
    ];

    // Dati di esempio per gli eventi
    const events = [
        {
            id: 1,
            titolo: 'Festival del Cinema Indipendente',
            data: '2024-03-15',
            luogo: 'Teatro Aurora',
            descrizione: 'Proiezioni e incontri con registi emergenti'
        },
        {
            id: 2,
            titolo: 'Workshop di Regia',
            data: '2024-03-20',
            luogo: 'Studio Cinematografico Centrale',
            descrizione: 'Corso intensivo di regia cinematografica'
        },
        // Altri eventi...
    ];

    // Funzione per creare una card film
    function createFilmCard(film) {
        return `
            <div class="movie-card" data-category="${film.categoria}">
                <img src="${film.immagine}" alt="${film.titolo}">
                <div class="movie-info">
                    <h3>${film.titolo}</h3>
                    <p>Regia: ${film.regista}</p>
                    <p>${film.descrizione}</p>
                </div>
            </div>
        `;
    }

    // Funzione per creare una card regista
    function createDirectorCard(director) {
        return `
            <div class="director-card">
                <img src="${director.foto}" alt="${director.nome}">
                <h3>${director.nome}</h3>
                <p>${director.specialita}</p>
            </div>
        `;
    }

    // Funzione per creare una card evento
    function createEventCard(event) {
        const date = new Date(event.data);
        return `
            <div class="event-card">
                <div class="event-date">
                    <div class="day">${date.getDate()}</div>
                    <div class="month">${date.toLocaleString('it-IT', { month: 'short' })}</div>
                </div>
                <div class="event-info">
                    <h3>${event.titolo}</h3>
                    <p>${event.luogo}</p>
                    <p>${event.descrizione}</p>
                </div>
            </div>
        `;
    }

    // Popolamento iniziale delle sezioni
    const filmGallery = document.getElementById('film-gallery');
    const directorGrid = document.getElementById('featured-directors');
    const eventsContainer = document.getElementById('film-events');

    films.forEach(film => {
        filmGallery.innerHTML += createFilmCard(film);
    });

    directors.forEach(director => {
        directorGrid.innerHTML += createDirectorCard(director);
    });

    events.forEach(event => {
        eventsContainer.innerHTML += createEventCard(event);
    });

    // Gestione dei filtri
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Rimuovi la classe active da tutti i bottoni
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Aggiungi la classe active al bottone cliccato
            button.classList.add('active');

            const filterValue = button.getAttribute('data-filter');
            const filmCards = document.querySelectorAll('.movie-card');

            filmCards.forEach(card => {
                if (filterValue === 'tutti' || card.getAttribute('data-category') === filterValue) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});