document.addEventListener('DOMContentLoaded', () => {
    // Dati di esempio per i giochi
    const games = [
        {
            id: 1,
            titolo: 'Avventura nel Tempo',
            sviluppatore: 'Studio Creativo',
            categoria: 'videogiochi',
            immagine: 'path/to/game1.jpg',
            descrizione: 'Un\'avventura RPG attraverso diverse epoche storiche'
        },
        {
            id: 2,
            titolo: 'Misteri della Città',
            sviluppatore: 'Board Game Lab',
            categoria: 'giochi-tavolo',
            immagine: 'path/to/game2.jpg',
            descrizione: 'Un gioco investigativo cooperativo'
        },
        // Altri giochi...
    ];

    // Dati di esempio per gli sviluppatori
    const developers = [
        {
            id: 1,
            nome: 'Studio Creativo',
            foto: 'path/to/dev1.jpg',
            specialita: 'Videogiochi RPG e Avventura'
        },
        {
            id: 2,
            nome: 'Board Game Lab',
            foto: 'path/to/dev2.jpg',
            specialita: 'Giochi da tavolo strategici'
        },
        // Altri sviluppatori...
    ];

    // Dati di esempio per gli eventi
    const events = [
        {
            id: 1,
            titolo: 'Game Jam Estate 2024',
            data: '2024-07-15',
            luogo: 'Hub Videogiochi',
            descrizione: '48 ore di sviluppo creativo di videogiochi'
        },
        {
            id: 2,
            titolo: 'Board Game Festival',
            data: '2024-08-20',
            luogo: 'Centro Congressi',
            descrizione: 'Festival dei giochi da tavolo indipendenti'
        },
        // Altri eventi...
    ];

    // Funzione per creare una card gioco
    function createGameCard(game) {
        return `
            <div class="game-card" data-category="${game.categoria}">
                <img src="${game.immagine}" alt="${game.titolo}">
                <div class="game-info">
                    <h3>${game.titolo}</h3>
                    <p>Sviluppatore: ${game.sviluppatore}</p>
                    <p>${game.descrizione}</p>
                </div>
            </div>
        `;
    }

    // Funzione per creare una card sviluppatore
    function createDeveloperCard(developer) {
        return `
            <div class="developer-card">
                <img src="${developer.foto}" alt="${developer.nome}">
                <h3>${developer.nome}</h3>
                <p>${developer.specialita}</p>
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
    const gamesGallery = document.getElementById('games-gallery');
    const developerGrid = document.getElementById('featured-developers');
    const eventsContainer = document.getElementById('game-events');

    games.forEach(game => {
        gamesGallery.innerHTML += createGameCard(game);
    });

    developers.forEach(developer => {
        developerGrid.innerHTML += createDeveloperCard(developer);
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
            const gameCards = document.querySelectorAll('.game-card');

            gameCards.forEach(card => {
                if (filterValue === 'tutti' || card.getAttribute('data-category') === filterValue) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Animazioni per le card
    const cards = document.querySelectorAll('.game-card, .developer-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});