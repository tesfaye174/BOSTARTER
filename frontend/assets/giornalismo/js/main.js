// Dati di esempio per gli articoli
const articles = [
    {
        id: 1,
        title: 'Il Futuro del Giornalismo Digitale',
        author: 'Marco Rossi',
        category: 'tecnologia',
        image: 'https://source.unsplash.com/random/800x600/?journalism,digital',
        description: 'Un\'analisi approfondita su come la tecnologia sta trasformando il mondo del giornalismo.',
        date: '2024-01-15',
        readTime: '5 min'
    },
    {
        id: 2,
        title: 'Inchiesta: Sostenibilità Ambientale',
        author: 'Laura Bianchi',
        category: 'ambiente',
        image: 'https://source.unsplash.com/random/800x600/?environment,sustainability',
        description: 'Un\'inchiesta dettagliata sulle pratiche sostenibili nelle aziende italiane.',
        date: '2024-01-12',
        readTime: '8 min'
    },
    {
        id: 3,
        title: 'Politica Locale: Nuove Riforme',
        author: 'Giovanni Verdi',
        category: 'politica',
        image: 'https://source.unsplash.com/random/800x600/?politics,government',
        description: 'Analisi delle nuove riforme proposte dal governo locale.',
        date: '2024-01-10',
        readTime: '6 min'
    },
    {
        id: 4,
        title: 'Sport: Campionato Invernale',
        author: 'Anna Neri',
        category: 'sport',
        image: 'https://source.unsplash.com/random/800x600/?sports,winter',
        description: 'Resoconto completo del campionato invernale di calcio.',
        date: '2024-01-08',
        readTime: '4 min'
    },
    {
        id: 5,
        title: 'Cultura: Festival della Letteratura',
        author: 'Paolo Blu',
        category: 'cultura',
        image: 'https://source.unsplash.com/random/800x600/?books,literature',
        description: 'Reportage dal festival della letteratura contemporanea.',
        date: '2024-01-05',
        readTime: '7 min'
    },
    {
        id: 6,
        title: 'Economia: Mercati Emergenti',
        author: 'Sofia Rosa',
        category: 'economia',
        image: 'https://source.unsplash.com/random/800x600/?economy,market',
        description: 'Analisi dei trend nei mercati emergenti europei.',
        date: '2024-01-03',
        readTime: '9 min'
    }
];

// Dati di esempio per i giornalisti
const journalists = [
    {
        name: 'Marco Rossi',
        specialty: 'Giornalismo Tecnologico',
        image: 'https://source.unsplash.com/random/200x200/?journalist,tech',
        bio: 'Esperto in tecnologia e innovazione digitale'
    },
    {
        name: 'Laura Bianchi',
        specialty: 'Giornalismo Ambientale',
        image: 'https://source.unsplash.com/random/200x200/?journalist,environment',
        bio: 'Specializzata in tematiche ambientali e sostenibilità'
    },
    {
        name: 'Giovanni Verdi',
        specialty: 'Giornalismo Politico',
        image: 'https://source.unsplash.com/random/200x200/?journalist,politics',
        bio: 'Corrispondente politico con 15 anni di esperienza'
    },
    {
        name: 'Anna Neri',
        specialty: 'Giornalismo Sportivo',
        image: 'https://source.unsplash.com/random/200x200/?journalist,sports',
        bio: 'Cronista sportiva e commentatrice'
    }
];

// Dati di esempio per gli eventi
const events = [
    {
        title: 'Conferenza sul Giornalismo Digitale',
        date: '2024-02-15',
        location: 'Milano',
        description: 'Un evento dedicato alle nuove frontiere del giornalismo nell\'era digitale.'
    },
    {
        title: 'Workshop di Scrittura Creativa',
        date: '2024-02-20',
        location: 'Roma',
        description: 'Laboratorio pratico per migliorare le tecniche di scrittura giornalistica.'
    },
    {
        title: 'Dibattito: Etica nel Giornalismo',
        date: '2024-02-25',
        location: 'Torino',
        description: 'Tavola rotonda sui principi etici del giornalismo moderno.'
    }
];

// Funzione per creare card degli articoli
function createArticleCard(article) {
    return `
        <div class="article-card" data-category="${article.category}">
            <img src="${article.image}" alt="${article.title}" loading="lazy">
            <div class="article-info">
                <h3>${article.title}</h3>
                <p class="article-meta">
                    <strong>Di:</strong> ${article.author} | 
                    <strong>Categoria:</strong> ${article.category} | 
                    <strong>Lettura:</strong> ${article.readTime}
                </p>
                <p>${article.description}</p>
                <p class="article-date"><small>${formatDate(article.date)}</small></p>
            </div>
        </div>
    `;
}

// Funzione per creare card dei giornalisti
function createJournalistCard(journalist) {
    return `
        <div class="journalist-card">
            <img src="${journalist.image}" alt="${journalist.name}" loading="lazy">
            <h3>${journalist.name}</h3>
            <p class="specialty">${journalist.specialty}</p>
            <p class="bio">${journalist.bio}</p>
        </div>
    `;
}

// Funzione per creare card degli eventi
function createEventCard(event) {
    const eventDate = new Date(event.date);
    const day = eventDate.getDate();
    const month = eventDate.toLocaleDateString('it-IT', { month: 'short' });
    
    return `
        <div class="event-card">
            <div class="event-date">
                <span class="day">${day}</span>
                <span class="month">${month}</span>
            </div>
            <div class="event-info">
                <h3>${event.title}</h3>
                <p><strong>Luogo:</strong> ${event.location}</p>
                <p>${event.description}</p>
            </div>
        </div>
    `;
}

// Funzione per formattare la data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Funzione per filtrare gli articoli
function filterArticles(category) {
    const articleCards = document.querySelectorAll('.article-card');
    
    articleCards.forEach(card => {
        if (category === 'tutti' || card.dataset.category === category) {
            card.style.display = 'block';
            card.style.animation = 'fadeIn 0.5s ease-in';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Aggiorna i pulsanti attivi
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-filter="${category}"]`).classList.add('active');
}

// Funzione per inizializzare la pagina
function initializePage() {
    // Popola la galleria degli articoli
    const gallery = document.getElementById('articles-gallery');
    if (gallery) {
        gallery.innerHTML = articles.map(createArticleCard).join('');
    }
    
    // Popola la sezione giornalisti
    const journalistsGrid = document.getElementById('journalists-grid');
    if (journalistsGrid) {
        journalistsGrid.innerHTML = journalists.map(createJournalistCard).join('');
    }
    
    // Popola la sezione eventi
    const eventsContainer = document.getElementById('events-container');
    if (eventsContainer) {
        eventsContainer.innerHTML = events.map(createEventCard).join('');
    }
    
    // Aggiungi event listeners per i filtri
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const category = btn.dataset.filter;
            filterArticles(category);
        });
    });
    
    // Aggiungi animazioni di scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Osserva tutti gli elementi animabili
    document.querySelectorAll('.article-card, .journalist-card, .event-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// Aggiungi stili CSS per le animazioni
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .article-meta {
        color: #718096;
        font-size: 0.85em;
        margin-bottom: 10px;
    }
    
    .article-date {
        margin-top: 15px;
        color: #a0aec0;
    }
    
    .specialty {
        color: #3182ce;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .bio {
        color: #718096;
        font-size: 0.9em;
    }
`;
document.head.appendChild(style);

// Inizializza quando il DOM è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePage);
} else {
    initializePage();
}