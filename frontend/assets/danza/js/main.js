document.addEventListener('DOMContentLoaded', () => {
    // Dati di esempio per gli spettacoli di danza
    const danceShows = [
        {
            id: 1,
            name: 'Il Lago dei Cigni',
            category: 'classica',
            choreographer: 'Maria Rossi',
            description: 'Una nuova interpretazione del classico balletto di Tchaikovsky',
            image: 'https://source.unsplash.com/random/800x600/?ballet'
        },
        {
            id: 2,
            name: 'Urban Dance Festival',
            category: 'contemporanea',
            choreographer: 'Marco Bianchi',
            description: 'Festival di danza contemporanea e street dance',
            image: 'https://source.unsplash.com/random/800x600/?contemporary-dance'
        },
        {
            id: 3,
            name: 'Flamenco Passion',
            category: 'folk',
            choreographer: 'Carmen Rodriguez',
            description: 'Spettacolo di flamenco tradizionale',
            image: 'https://source.unsplash.com/random/800x600/?flamenco'
        },
        {
            id: 4,
            name: 'Hip Hop Battle',
            category: 'urbana',
            choreographer: 'Alex Smith',
            description: 'Competizione di hip hop e breakdance',
            image: 'https://source.unsplash.com/random/800x600/?hip-hop-dance'
        }
    ];

    // Dati di esempio per i ballerini
    const dancers = [
        {
            id: 1,
            name: 'Sofia Verdi',
            specialty: 'Danza Classica',
            image: 'https://source.unsplash.com/random/400x400/?ballerina'
        },
        {
            id: 2,
            name: 'Luca Romano',
            specialty: 'Danza Contemporanea',
            image: 'https://source.unsplash.com/random/400x400/?dancer'
        },
        {
            id: 3,
            name: 'Elena Costa',
            specialty: 'Flamenco',
            image: 'https://source.unsplash.com/random/400x400/?flamenco-dancer'
        }
    ];

    // Dati di esempio per gli spettacoli in programma
    const upcomingShows = [
        {
            id: 1,
            name: 'Gala di Danza Classica',
            date: '2024-02-15',
            description: 'Una serata dedicata ai grandi classici del balletto'
        },
        {
            id: 2,
            name: 'Festival di Danza Contemporanea',
            date: '2024-02-20',
            description: 'Esibizioni di compagnie internazionali di danza contemporanea'
        },
        {
            id: 3,
            name: 'Serata di Danze Folk',
            date: '2024-02-25',
            description: 'Spettacolo di danze tradizionali da tutto il mondo'
        }
    ];

    // Funzione per caricare la galleria degli spettacoli
    function loadDanceGallery(category = 'tutti') {
        const gallery = document.getElementById('dance-gallery');
        gallery.innerHTML = '';

        const filteredShows = category === 'tutti' 
            ? danceShows 
            : danceShows.filter(show => show.category === category);

        filteredShows.forEach(show => {
            const card = document.createElement('div');
            card.className = 'dance-card';
            card.innerHTML = `
                <img src="${show.image}" alt="${show.name}">
                <div class="dance-info">
                    <h3>${show.name}</h3>
                    <p>${show.description}</p>
                    <p><em>Coreografo: ${show.choreographer}</em></p>
                </div>
            `;
            gallery.appendChild(card);
        });
    }

    // Funzione per caricare la sezione dei ballerini
    function loadDancers() {
        const dancersGrid = document.getElementById('featured-dancers');
        if (!dancersGrid) return;

        dancers.forEach(dancer => {
            const card = document.createElement('div');
            card.className = 'dancer-card';
            card.innerHTML = `
                <img src="${dancer.image}" alt="${dancer.name}">
                <h3>${dancer.name}</h3>
                <p>${dancer.specialty}</p>
            `;
            dancersGrid.appendChild(card);
        });
    }

    // Funzione per caricare gli spettacoli in programma
    function loadUpcomingShows() {
        const showsContainer = document.getElementById('dance-shows');
        if (!showsContainer) return;

        upcomingShows.forEach(show => {
            const card = document.createElement('div');
            card.className = 'show-card';
            const date = new Date(show.date);
            card.innerHTML = `
                <div class="show-date">
                    <div class="day">${date.getDate()}</div>
                    <div class="month">${date.toLocaleString('it-IT', { month: 'short' })}</div>
                </div>
                <div class="show-info">
                    <h3>${show.name}</h3>
                    <p>${show.description}</p>
                </div>
            `;
            showsContainer.appendChild(card);
        });
    }

    // Gestione dei filtri
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            loadDanceGallery(button.dataset.filter);
        });
    });

    // Inizializzazione della pagina
    loadDanceGallery();
    loadDancers();
    loadUpcomingShows();
});