// Scopri Tutti - JavaScript per overview di tutte le categorie

// Dati delle categorie
const categories = [
    {
        id: 'arte',
        name: 'Arte',
        description: 'Esplora opere d\'arte contemporanea e artisti emergenti che stanno ridefinendo il panorama artistico italiano.',
        icon: '🎨',
        projects: 24,
        artists: 18,
        events: 12,
        color: '#e74c3c',
        link: '../arte/index.html'
    },
    {
        id: 'artigianato',
        name: 'Artigianato',
        description: 'Scopri l\'arte tradizionale italiana attraverso creazioni uniche realizzate da maestri artigiani.',
        icon: '🏺',
        projects: 32,
        artists: 25,
        events: 8,
        color: '#c17767',
        link: '../artigianato/index.html'
    },
    {
        id: 'cibo',
        name: 'Cibo',
        description: 'Esplora la ricchezza della cucina italiana attraverso chef innovativi e ricette tradizionali.',
        icon: '🍝',
        projects: 45,
        artists: 30,
        events: 20,
        color: '#FF6B6B',
        link: '../cibo/index.html'
    },
    {
        id: 'danza',
        name: 'Danza',
        description: 'Scopri l\'arte del movimento attraverso spettacoli di danza contemporanea e classica.',
        icon: '💃',
        projects: 28,
        artists: 22,
        events: 15,
        color: '#FF69B4',
        link: '../danza/index.html'
    },
    {
        id: 'design',
        name: 'Design',
        description: 'Esplora progetti innovativi di design contemporaneo che uniscono funzionalità ed estetica.',
        icon: '📐',
        projects: 38,
        artists: 28,
        events: 18,
        color: '#2c3e50',
        link: '../design/index.html'
    },
    {
        id: 'editoriale',
        name: 'Editoriale',
        description: 'Scopri l\'editoria contemporanea attraverso autori emergenti e pubblicazioni innovative.',
        icon: '📚',
        projects: 42,
        artists: 35,
        events: 25,
        color: '#2C3E50',
        link: '../editoriale/index.html'
    },
    {
        id: 'film',
        name: 'Film',
        description: 'Esplora il cinema indipendente italiano attraverso progetti cinematografici innovativi.',
        icon: '🎬',
        projects: 35,
        artists: 40,
        events: 22,
        color: '#8b0000',
        link: '../film/index.html'
    },
    {
        id: 'fotografia',
        name: 'Fotografia',
        description: 'Scopri la fotografia d\'autore attraverso l\'obiettivo di fotografi talentuosi.',
        icon: '📸',
        projects: 50,
        artists: 35,
        events: 30,
        color: '#1c1c1c',
        link: '../fotografia/index.html'
    },
    {
        id: 'fumetti',
        name: 'Fumetti',
        description: 'Esplora il mondo dei fumetti attraverso artisti emergenti e nuove storie illustrate.',
        icon: '💭',
        projects: 28,
        artists: 20,
        events: 12,
        color: '#ff6b35',
        link: '../fumetti/index.html'
    },
    {
        id: 'giochi',
        name: 'Giochi',
        description: 'Scopri progetti innovativi di videogiochi e giochi da tavolo creati da sviluppatori italiani.',
        icon: '🎮',
        projects: 22,
        artists: 18,
        events: 10,
        color: '#1a1a2e',
        link: '../giochi/index.html'
    },
    {
        id: 'giornalismo',
        name: 'Giornalismo',
        description: 'Esplora il giornalismo contemporaneo attraverso articoli innovativi e giornalisti emergenti.',
        icon: '📰',
        projects: 65,
        artists: 45,
        events: 35,
        color: '#2c5aa0',
        link: '../giornalismo/index.html'
    },
    {
        id: 'moda',
        name: 'Moda',
        description: 'Scopri il fashion design italiano attraverso stilisti emergenti e collezioni innovative.',
        icon: '👗',
        projects: 40,
        artists: 30,
        events: 25,
        color: '#d63384',
        link: '../moda/index.html'
    },
    {
        id: 'musica',
        name: 'Musica',
        description: 'Esplora il panorama musicale italiano attraverso artisti emergenti e nuovi talenti.',
        icon: '🎵',
        projects: 55,
        artists: 42,
        events: 40,
        color: '#6c1cd1',
        link: '../musica/index.html'
    },
    {
        id: 'teatro',
        name: 'Teatro',
        description: 'Scopri il teatro contemporaneo attraverso compagnie innovative e spettacoli originali.',
        icon: '🎭',
        projects: 30,
        artists: 25,
        events: 20,
        color: '#8b4513',
        link: '../teatro/index.html'
    },
    {
        id: 'tecnologia',
        name: 'Tecnologia',
        description: 'Esplora progetti tecnologici innovativi che stanno trasformando il futuro digitale.',
        icon: '💻',
        projects: 48,
        artists: 35,
        events: 28,
        color: '#007bff',
        link: '../tecnologia/index.html'
    }
];

// Funzione per creare una card categoria
function createCategoryCard(category) {
    return `
        <div class="category-card" data-category="${category.id}" onclick="navigateToCategory('${category.link}')">
            <div class="category-image" style="background: linear-gradient(135deg, ${category.color}, ${category.color}dd)">
                <span class="category-icon">${category.icon}</span>
            </div>
            <div class="category-content">
                <h3 class="category-title">${category.name}</h3>
                <p class="category-description">${category.description}</p>
                <div class="category-stats">
                    <div class="stat">
                        <span class="stat-number">${category.projects}</span>
                        <span class="stat-label">Progetti</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">${category.artists}</span>
                        <span class="stat-label">Artisti</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">${category.events}</span>
                        <span class="stat-label">Eventi</span>
                    </div>
                </div>
                <a href="${category.link}" class="category-link">
                    Esplora ${category.name} →
                </a>
            </div>
        </div>
    `;
}

// Funzione per navigare a una categoria
function navigateToCategory(link) {
    window.location.href = link;
}

// Funzione per calcolare le statistiche totali
function calculateTotalStats() {
    const totalProjects = categories.reduce((sum, cat) => sum + cat.projects, 0);
    const totalArtists = categories.reduce((sum, cat) => sum + cat.artists, 0);
    const totalEvents = categories.reduce((sum, cat) => sum + cat.events, 0);
    const totalCategories = categories.length;
    
    return { totalProjects, totalArtists, totalEvents, totalCategories };
}

// Funzione per aggiornare le statistiche
function updateStats() {
    const stats = calculateTotalStats();
    
    document.querySelector('.stats-grid').innerHTML = `
        <div class="stat-item">
            <h3>${stats.totalCategories}</h3>
            <p>Categorie Creative</p>
        </div>
        <div class="stat-item">
            <h3>${stats.totalProjects}</h3>
            <p>Progetti Attivi</p>
        </div>
        <div class="stat-item">
            <h3>${stats.totalArtists}</h3>
            <p>Artisti e Creativi</p>
        </div>
        <div class="stat-item">
            <h3>${stats.totalEvents}</h3>
            <p>Eventi e Workshop</p>
        </div>
    `;
}

// Funzione per caricare le categorie
function loadCategories() {
    const grid = document.querySelector('.categories-grid');
    if (!grid) return;
    
    grid.innerHTML = categories.map(category => createCategoryCard(category)).join('');
    
    // Aggiungi animazioni staggered
    const cards = document.querySelectorAll('.category-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
}

// Funzione per aggiungere effetti hover
function addHoverEffects() {
    const cards = document.querySelectorAll('.category-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

// Funzione per aggiungere effetti di scroll
function addScrollEffects() {
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
    
    // Osserva le sezioni
    document.querySelectorAll('.category-card, .intro-section, .stats-section, .cta-section').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// Funzione per aggiungere ricerca
function addSearchFunctionality() {
    // Crea il campo di ricerca
    const searchContainer = document.createElement('div');
    searchContainer.className = 'search-container';
    searchContainer.style.cssText = `
        text-align: center;
        margin: 2rem 0;
        padding: 0 2rem;
    `;
    
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Cerca una categoria...';
    searchInput.style.cssText = `
        width: 100%;
        max-width: 500px;
        padding: 1rem 1.5rem;
        border: 2px solid #e9ecef;
        border-radius: 25px;
        font-size: 1.1rem;
        outline: none;
        transition: all 0.3s ease;
    `;
    
    searchInput.addEventListener('focus', function() {
        this.style.borderColor = 'var(--primary-color)';
        this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
    });
    
    searchInput.addEventListener('blur', function() {
        this.style.borderColor = '#e9ecef';
        this.style.boxShadow = 'none';
    });
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.category-card');
        
        cards.forEach(card => {
            const categoryName = card.querySelector('.category-title').textContent.toLowerCase();
            const categoryDesc = card.querySelector('.category-description').textContent.toLowerCase();
            
            if (categoryName.includes(searchTerm) || categoryDesc.includes(searchTerm)) {
                card.style.display = 'block';
                card.style.opacity = '1';
            } else {
                card.style.display = 'none';
                card.style.opacity = '0';
            }
        });
    });
    
    searchContainer.appendChild(searchInput);
    
    // Inserisci il campo di ricerca prima della griglia
    const grid = document.querySelector('.categories-grid');
    if (grid && grid.parentNode) {
        grid.parentNode.insertBefore(searchContainer, grid);
    }
}

// Funzione di inizializzazione
function init() {
    // Carica le categorie
    loadCategories();
    
    // Aggiorna le statistiche
    updateStats();
    
    // Aggiungi effetti
    setTimeout(() => {
        addHoverEffects();
        addScrollEffects();
        addSearchFunctionality();
    }, 100);
    
    // Aggiungi smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Avvia l'applicazione quando il DOM è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

// Esporta le funzioni per uso globale
window.navigateToCategory = navigateToCategory;