// Dati di esempio per gli articoli di moda
const fashionItems = [
    {
        id: 1,
        title: 'Collezione Primavera Elegante',
        designer: 'Elena Rossi',
        category: 'alta-moda',
        image: 'https://source.unsplash.com/random/800x600/?fashion,elegant,spring',
        description: 'Una collezione raffinata che celebra l\'eleganza primaverile con tessuti pregiati e linee fluide.',
        price: '€1,200 - €2,800',
        season: 'Primavera 2024'
    },
    {
        id: 2,
        title: 'Street Style Urbano',
        designer: 'Marco Bianchi',
        category: 'streetwear',
        image: 'https://source.unsplash.com/random/800x600/?streetwear,urban,fashion',
        description: 'Abbigliamento urbano che unisce comfort e stile per la vita di tutti i giorni.',
        price: '€80 - €350',
        season: 'Tutto l\'anno'
    },
    {
        id: 3,
        title: 'Accessori Luxury',
        designer: 'Sofia Verdi',
        category: 'accessori',
        image: 'https://source.unsplash.com/random/800x600/?luxury,accessories,fashion',
        description: 'Accessori di lusso realizzati a mano con materiali pregiati e design innovativo.',
        price: '€200 - €1,500',
        season: 'Collezione permanente'
    },
    {
        id: 4,
        title: 'Moda Sostenibile',
        designer: 'Anna Neri',
        category: 'sostenibile',
        image: 'https://source.unsplash.com/random/800x600/?sustainable,fashion,eco',
        description: 'Abbigliamento eco-friendly realizzato con materiali riciclati e processi sostenibili.',
        price: '€120 - €450',
        season: 'Estate 2024'
    },
    {
        id: 5,
        title: 'Vintage Reinterpretato',
        designer: 'Paolo Blu',
        category: 'vintage',
        image: 'https://source.unsplash.com/random/800x600/?vintage,fashion,retro',
        description: 'Pezzi vintage reinterpretati con un tocco moderno per un look unico e ricercato.',
        price: '€150 - €600',
        season: 'Autunno 2024'
    },
    {
        id: 6,
        title: 'Abiti da Sera Glamour',
        designer: 'Giulia Rosa',
        category: 'sera',
        image: 'https://source.unsplash.com/random/800x600/?evening,dress,glamour',
        description: 'Abiti da sera che incarnano il glamour e la sofisticatezza per occasioni speciali.',
        price: '€800 - €3,200',
        season: 'Collezione Gala'
    }
];

// Dati di esempio per i designer
const designers = [
    {
        name: 'Elena Rossi',
        specialty: 'Alta Moda Italiana',
        image: 'https://source.unsplash.com/random/200x200/?fashion,designer,woman',
        bio: 'Designer di fama internazionale specializzata in alta moda con oltre 15 anni di esperienza.'
    },
    {
        name: 'Marco Bianchi',
        specialty: 'Streetwear & Urban',
        image: 'https://source.unsplash.com/random/200x200/?fashion,designer,man',
        bio: 'Pioniere del streetwear italiano, unisce tradizione sartoriale e cultura urbana contemporanea.'
    },
    {
        name: 'Sofia Verdi',
        specialty: 'Accessori di Lusso',
        image: 'https://source.unsplash.com/random/200x200/?luxury,designer,accessories',
        bio: 'Creatrice di accessori luxury, famosa per l\'uso innovativo di materiali pregiati.'
    },
    {
        name: 'Anna Neri',
        specialty: 'Moda Sostenibile',
        image: 'https://source.unsplash.com/random/200x200/?sustainable,fashion,eco',
        bio: 'Ambasciatrice della moda sostenibile, pioniera nell\'uso di materiali eco-friendly.'
    }
];

// Dati di esempio per gli eventi fashion
const fashionEvents = [
    {
        title: 'Milano Fashion Week',
        date: '2024-02-20',
        location: 'Milano, Italia',
        description: 'La settimana della moda più importante d\'Italia con le collezioni dei migliori designer.'
    },
    {
        title: 'Sustainable Fashion Summit',
        date: '2024-03-05',
        location: 'Roma, Italia',
        description: 'Conferenza internazionale dedicata alla moda sostenibile e alle innovazioni eco-friendly.'
    },
    {
        title: 'Vintage Fashion Fair',
        date: '2024-03-15',
        location: 'Firenze, Italia',
        description: 'Fiera del vintage con pezzi unici e collezioni storiche di alta moda italiana.'
    },
    {
        title: 'Young Designers Showcase',
        date: '2024-03-25',
        location: 'Torino, Italia',
        description: 'Vetrina per giovani talenti emergenti nel mondo della moda e del design.'
    }
];

// Funzione per creare card degli articoli fashion
function createFashionCard(item) {
    return `
        <div class="fashion-item" data-category="${item.category}">
            <img src="${item.image}" alt="${item.title}" loading="lazy">
            <div class="fashion-info">
                <div class="designer">${item.designer}</div>
                <h3>${item.title}</h3>
                <p>${item.description}</p>
                <div class="fashion-meta">
                    <div class="season"><strong>Stagione:</strong> ${item.season}</div>
                    <div class="fashion-price">${item.price}</div>
                </div>
            </div>
        </div>
    `;
}

// Funzione per creare card dei designer
function createDesignerCard(designer) {
    return `
        <div class="designer-card">
            <img src="${designer.image}" alt="${designer.name}" loading="lazy">
            <h3>${designer.name}</h3>
            <div class="specialty">${designer.specialty}</div>
            <p class="bio">${designer.bio}</p>
        </div>
    `;
}

// Funzione per creare card degli eventi fashion
function createFashionEventCard(event) {
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
                <div class="location">${event.location}</div>
                <p>${event.description}</p>
            </div>
        </div>
    `;
}

// Funzione per filtrare gli articoli fashion
function filterFashionItems(category) {
    const fashionCards = document.querySelectorAll('.fashion-item');
    
    fashionCards.forEach(card => {
        if (category === 'tutti' || card.dataset.category === category) {
            card.style.display = 'block';
            card.style.animation = 'fadeInUp 0.6s ease-out';
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

// Funzione per aggiungere effetti di parallax al banner
function addParallaxEffect() {
    const banner = document.querySelector('.fashion-banner');
    if (!banner) return;
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        banner.style.transform = `translateY(${rate}px)`;
    });
}

// Funzione per aggiungere effetti hover avanzati
function addAdvancedHoverEffects() {
    const fashionItems = document.querySelectorAll('.fashion-item');
    
    fashionItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02) rotateY(2deg)';
            this.style.boxShadow = '0 20px 40px rgba(139,90,60,0.15)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1) rotateY(0deg)';
            this.style.boxShadow = '0 2px 8px rgba(139,90,60,0.05)';
        });
    });
}

// Funzione per inizializzare la pagina
function initializeFashionPage() {
    // Popola la galleria fashion
    const gallery = document.getElementById('fashion-gallery');
    if (gallery) {
        gallery.innerHTML = fashionItems.map(createFashionCard).join('');
    }
    
    // Popola la sezione designer
    const designersGrid = document.getElementById('designers-grid');
    if (designersGrid) {
        designersGrid.innerHTML = designers.map(createDesignerCard).join('');
    }
    
    // Popola la sezione eventi
    const eventsContainer = document.getElementById('fashion-events-container');
    if (eventsContainer) {
        eventsContainer.innerHTML = fashionEvents.map(createFashionEventCard).join('');
    }
    
    // Aggiungi event listeners per i filtri
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const category = btn.dataset.filter;
            filterFashionItems(category);
        });
    });
    
    // Aggiungi effetti avanzati
    addParallaxEffect();
    setTimeout(addAdvancedHoverEffects, 100);
    
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
    document.querySelectorAll('.fashion-item, .designer-card, .event-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        observer.observe(el);
    });
    
    // Aggiungi effetto di typing al titolo del banner
    const bannerTitle = document.querySelector('.fashion-banner h1');
    if (bannerTitle) {
        const text = bannerTitle.textContent;
        bannerTitle.textContent = '';
        let i = 0;
        const typeWriter = () => {
            if (i < text.length) {
                bannerTitle.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            }
        };
        setTimeout(typeWriter, 500);
    }
}

// Aggiungi stili CSS per le animazioni
const fashionStyles = document.createElement('style');
fashionStyles.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fashion-meta {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid rgba(139,90,60,0.1);
    }
    
    .season {
        color: #8d6e63;
        font-size: 0.9em;
        margin-bottom: 10px;
    }
    
    .fashion-item {
        perspective: 1000px;
    }
    
    .fashion-banner h1 {
        border-right: 2px solid white;
        animation: blink 1s infinite;
    }
    
    @keyframes blink {
        0%, 50% { border-color: white; }
        51%, 100% { border-color: transparent; }
    }
    
    .designer-card:nth-child(even) {
        animation-delay: 0.2s;
    }
    
    .designer-card:nth-child(odd) {
        animation-delay: 0.4s;
    }
`;
document.head.appendChild(fashionStyles);

// Inizializza quando il DOM è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFashionPage);
} else {
    initializeFashionPage();
}