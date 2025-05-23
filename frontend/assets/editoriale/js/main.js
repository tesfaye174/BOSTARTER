document.addEventListener('DOMContentLoaded', () => {
    // Dati di esempio per gli articoli
    const articles = [
        {
            id: 1,
            title: 'Il Futuro dell\'Editoria Digitale',
            category: 'digitale',
            author: 'Marco Rossi',
            description: 'Analisi delle tendenze nell\'editoria digitale moderna',
            image: 'https://source.unsplash.com/random/800x600/?digital-publishing'
        },
        {
            id: 2,
            title: 'L\'Arte della Narrazione',
            category: 'narrativa',
            author: 'Laura Bianchi',
            description: 'Tecniche di storytelling per scrittori emergenti',
            image: 'https://source.unsplash.com/random/800x600/?storytelling'
        },
        {
            id: 3,
            title: 'Editoria Indipendente',
            category: 'indipendente',
            author: 'Giuseppe Verdi',
            description: 'Guida alla pubblicazione indipendente',
            image: 'https://source.unsplash.com/random/800x600/?independent-publishing'
        },
        {
            id: 4,
            title: 'Poesia Contemporanea',
            category: 'poesia',
            author: 'Anna Conti',
            description: 'Tendenze nella poesia moderna italiana',
            image: 'https://source.unsplash.com/random/800x600/?poetry'
        }
    ];

    // Dati di esempio per gli autori
    const authors = [
        {
            id: 1,
            name: 'Marco Rossi',
            specialty: 'Editoria Digitale',
            image: 'https://source.unsplash.com/random/400x400/?writer'
        },
        {
            id: 2,
            name: 'Laura Bianchi',
            specialty: 'Narrativa',
            image: 'https://source.unsplash.com/random/400x400/?author'
        },
        {
            id: 3,
            name: 'Giuseppe Verdi',
            specialty: 'Saggistica',
            image: 'https://source.unsplash.com/random/400x400/?journalist'
        }
    ];

    // Dati di esempio per gli eventi
    const events = [
        {
            id: 1,
            name: 'Workshop di Scrittura Creativa',
            date: '2024-02-15',
            description: 'Tecniche avanzate di scrittura creativa'
        },
        {
            id: 2,
            name: 'Fiera del Libro Indipendente',
            date: '2024-02-20',
            description: 'Esposizione di editori e autori indipendenti'
        },
        {
            id: 3,
            name: 'Seminario sull\'Editoria Digitale',
            date: '2024-02-25',
            description: 'Le nuove frontiere dell\'editoria online'
        }
    ];

    // Funzione per caricare la galleria degli articoli
    function loadArticleGallery(category = 'tutti') {
        const gallery = document.getElementById('article-gallery');
        gallery.innerHTML = '';

        const filteredArticles = category === 'tutti' 
            ? articles 
            : articles.filter(article => article.category === category);

        filteredArticles.forEach(article => {
            const card = document.createElement('div');
            card.className = 'article-card';
            card.innerHTML = `
                <img src="${article.image}" alt="${article.title}">
                <div class="article-info">
                    <h3>${article.title}</h3>
                    <p>${article.description}</p>
                    <p><em>Autore: ${article.author}</em></p>
                </div>
            `;
            gallery.appendChild(card);
        });
    }

    // Funzione per caricare la sezione degli autori
    function loadAuthors() {
        const authorsGrid = document.getElementById('featured-authors');
        if (!authorsGrid) return;

        authors.forEach(author => {
            const card = document.createElement('div');
            card.className = 'author-card';
            card.innerHTML = `
                <img src="${author.image}" alt="${author.name}">
                <h3>${author.name}</h3>
                <p>${author.specialty}</p>
            `;
            authorsGrid.appendChild(card);
        });
    }

    // Funzione per caricare gli eventi
    function loadEvents() {
        const eventsContainer = document.getElementById('editorial-events');
        if (!eventsContainer) return;

        events.forEach(event => {
            const card = document.createElement('div');
            card.className = 'event-card';
            const date = new Date(event.date);
            card.innerHTML = `
                <div class="event-date">
                    <div class="day">${date.getDate()}</div>
                    <div class="month">${date.toLocaleString('it-IT', { month: 'short' })}</div>
                </div>
                <div class="event-info">
                    <h3>${event.name}</h3>
                    <p>${event.description}</p>
                </div>
            `;
            eventsContainer.appendChild(card);
        });
    }

    // Gestione dei filtri
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            loadArticleGallery(button.dataset.filter);
        });
    });

    // Inizializzazione della pagina
    loadArticleGallery();
    loadAuthors();
    loadEvents();
});