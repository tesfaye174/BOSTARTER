document.addEventListener('DOMContentLoaded', () => {
    // Dati di esempio per i fumetti
    const comics = [
        {
            id: 1,
            titolo: 'Le Avventure di Luna',
            autore: 'Maria Verdi',
            categoria: 'fumetto-italiano',
            immagine: 'path/to/comic1.jpg',
            descrizione: 'Una storia fantasy ambientata in un mondo magico'
        },
        {
            id: 2,
            titolo: 'Tokyo Dreams',
            autore: 'Giuseppe Rossi',
            categoria: 'manga',
            immagine: 'path/to/comic2.jpg',
            descrizione: 'Un manga sulla vita quotidiana a Tokyo'
        },
        // Altri fumetti...
    ];

    // Dati di esempio per gli artisti
    const artists = [
        {
            id: 1,
            nome: 'Maria Verdi',
            foto: 'path/to/artist1.jpg',
            specialita: 'Fumetto fantasy italiano'
        },
        {
            id: 2,
            nome: 'Giuseppe Rossi',
            foto: 'path/to/artist2.jpg',
            specialita: 'Manga e illustrazione digitale'
        },
        // Altri artisti...
    ];

    // Dati di esempio per gli eventi
    const events = [
        {
            id: 1,
            titolo: 'Comicon Italia',
            data: '2024-04-25',
            luogo: 'Centro Fiere',
            descrizione: 'La più grande fiera del fumetto in Italia'
        },
        {
            id: 2,
            titolo: 'Workshop di Manga',
            data: '2024-05-10',
            luogo: 'Scuola di Fumetto',
            descrizione: 'Corso intensivo di disegno manga'
        },
        // Altri eventi...
    ];

    // Funzione per creare una card fumetto
    function createComicCard(comic) {
        return `
            <div class="comic-card" data-category="${comic.categoria}">
                <img src="${comic.immagine}" alt="${comic.titolo}">
                <div class="comic-info">
                    <h3>${comic.titolo}</h3>
                    <p>Autore: ${comic.autore}</p>
                    <p>${comic.descrizione}</p>
                </div>
            </div>
        `;
    }

    // Funzione per creare una card artista
    function createArtistCard(artist) {
        return `
            <div class="artist-card">
                <img src="${artist.foto}" alt="${artist.nome}">
                <h3>${artist.nome}</h3>
                <p>${artist.specialita}</p>
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
    const comicsGallery = document.getElementById('comics-gallery');
    const artistGrid = document.getElementById('featured-artists');
    const eventsContainer = document.getElementById('comic-events');

    comics.forEach(comic => {
        comicsGallery.innerHTML += createComicCard(comic);
    });

    artists.forEach(artist => {
        artistGrid.innerHTML += createArtistCard(artist);
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
            const comicCards = document.querySelectorAll('.comic-card');

            comicCards.forEach(card => {
                if (filterValue === 'tutti' || card.getAttribute('data-category') === filterValue) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Animazione al caricamento delle card
    const cards = document.querySelectorAll('.comic-card, .artist-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}));