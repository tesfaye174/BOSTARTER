// Dati di esempio per le produzioni teatrali
const theaterProductions = [
    {
        id: 1,
        title: 'Amleto Contemporaneo',
        company: 'Teatro Nuovo Milano',
        category: 'dramma',
        image: 'https://source.unsplash.com/random/800x600/?theater,hamlet,drama',
        description: 'Una reinterpretazione moderna del capolavoro shakespeariano, ambientata nella Milano contemporanea.',
        date: '2024-02-15',
        venue: 'Teatro alla Scala',
        genre: 'Dramma'
    },
    {
        id: 2,
        title: 'La Commedia degli Equivoci',
        company: 'Compagnia del Sorriso',
        category: 'commedia',
        image: 'https://source.unsplash.com/random/800x600/?theater,comedy,performance',
        description: 'Una brillante commedia che gioca con gli equivoci e le situazioni paradossali della vita quotidiana.',
        date: '2024-02-20',
        venue: 'Teatro Piccolo',
        genre: 'Commedia'
    },
    {
        id: 3,
        title: 'Danza delle Ombre',
        company: 'Ensemble Contemporaneo',
        category: 'danza-teatro',
        image: 'https://source.unsplash.com/random/800x600/?dance,theater,contemporary',
        description: 'Uno spettacolo che fonde danza contemporanea e teatro, esplorando i confini dell\'espressione artistica.',
        date: '2024-02-25',
        venue: 'Teatro dell\'Opera',
        genre: 'Danza-Teatro'
    },
    {
        id: 4,
        title: 'Pinocchio Reimagined',
        company: 'Teatro per Famiglie',
        category: 'famiglia',
        image: 'https://source.unsplash.com/random/800x600/?puppet,theater,family',
        description: 'Una versione innovativa della storia di Pinocchio, con pupazzi animatronici e scenografie digitali.',
        date: '2024-03-01',
        venue: 'Teatro dei Bambini',
        genre: 'Famiglia'
    },
    {
        id: 5,
        title: 'Cabaret Noir',
        company: 'Compagnia Underground',
        category: 'musical',
        image: 'https://source.unsplash.com/random/800x600/?cabaret,musical,noir',
        description: 'Un musical dark e coinvolgente ambientato nella Berlino degli anni \'30, con musiche originali.',
        date: '2024-03-05',
        venue: 'Teatro Sperimentale',
        genre: 'Musical'
    },
    {
        id: 6,
        title: 'Monologo dell\'Anima',
        company: 'Teatro Intimo',
        category: 'monologo',
        image: 'https://source.unsplash.com/random/800x600/?monologue,theater,soul',
        description: 'Un intenso monologo che esplora le profondità dell\'animo umano attraverso la poesia e la prosa.',
        date: '2024-03-10',
        venue: 'Piccolo Teatro Studio',
        genre: 'Monologo'
    }
];

// Dati di esempio per le compagnie teatrali
const theaterCompanies = [
    {
        name: 'Teatro Nuovo Milano',
        specialty: 'Dramma Contemporaneo',
        image: 'https://source.unsplash.com/random/200x200/?theater,company,drama',
        bio: 'Compagnia teatrale fondata nel 1985, specializzata in rivisitazioni moderne dei classici.'
    },
    {
        name: 'Compagnia del Sorriso',
        specialty: 'Commedia & Satira',
        image: 'https://source.unsplash.com/random/200x200/?comedy,theater,smile',
        bio: 'Gruppo teatrale dedicato alla commedia intelligente e alla satira sociale contemporanea.'
    },
    {
        name: 'Ensemble Contemporaneo',
        specialty: 'Teatro Sperimentale',
        image: 'https://source.unsplash.com/random/200x200/?contemporary,theater,experimental',
        bio: 'Collettivo artistico che esplora nuove forme espressive attraverso teatro, danza e multimedia.'
    },
    {
        name: 'Teatro per Famiglie',
        specialty: 'Spettacoli per Bambini',
        image: 'https://source.unsplash.com/random/200x200/?family,theater,children',
        bio: 'Compagnia specializzata in spettacoli educativi e divertenti per tutta la famiglia.'
    }
];

// Dati di esempio per gli eventi teatrali
const theaterEvents = [
    {
        title: 'Festival del Teatro Contemporaneo',
        date: '2024-03-15',
        location: 'Milano, Italia',
        description: 'Una settimana dedicata alle nuove tendenze del teatro contemporaneo con compagnie internazionali.'
    },
    {
        title: 'Notte Bianca del Teatro',
        date: '2024-03-22',
        location: 'Roma, Italia',
        description: 'Una notte speciale con spettacoli gratuiti in tutti i teatri della città fino all\'alba.'
    },
    {
        title: 'Workshop di Recitazione',
        date: '2024-04-01',
        location: 'Firenze, Italia',
        description: 'Masterclass intensivo con attori professionisti per aspiranti interpreti.'
    },
    {
        title: 'Premio Nazionale Teatro Emergente',
        date: '2024-04-10',
        location: 'Napoli, Italia',
        description: 'Cerimonia di premiazione per le migliori produzioni teatrali emergenti dell\'anno.'
    }
];

// Funzione per creare card delle produzioni teatrali
function createTheaterCard(production) {
    return `
        <div class="theater-item" data-category="${production.category}">
            <img src="${production.image}" alt="${production.title}" loading="lazy">
            <div class="theater-info">
                <div class="company">${production.company}</div>
                <h3>${production.title}</h3>
                <p>${production.description}</p>
                <div class="theater-meta">
                    <div class="theater-date">${formatDate(production.date)}</div>
                    <div class="theater-genre">${production.genre}</div>
                </div>
                <div class="venue-info">
                    <small><strong>Teatro:</strong> ${production.venue}</small>
                </div>
            </div>
        </div>
    `;
}

// Funzione per creare card delle compagnie teatrali
function createCompanyCard(company) {
    return `
        <div class="company-card">
            <img src="${company.image}" alt="${company.name}" loading="lazy">
            <h3>${company.name}</h3>
            <div class="specialty">${company.specialty}</div>
            <p class="bio">${company.bio}</p>
        </div>
    `;
}

// Funzione per creare card degli eventi teatrali
function createTheaterEventCard(event) {
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

// Funzione per formattare la data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Funzione per filtrare le produzioni teatrali
function filterTheaterProductions(category) {
    const theaterCards = document.querySelectorAll('.theater-item');
    
    theaterCards.forEach(card => {
        if (category === 'tutti' || card.dataset.category === category) {
            card.style.display = 'block';
            card.style.animation = 'fadeInTheater 0.8s ease-out';
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
function addTheaterParallax() {
    const banner = document.querySelector('.theater-banner');
    if (!banner) return;
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.3;
        banner.style.transform = `translateY(${rate}px)`;
    });
}

// Funzione per aggiungere effetti hover teatrali
function addTheaterHoverEffects() {
    const theaterItems = document.querySelectorAll('.theater-item');
    
    theaterItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) rotateX(5deg) scale(1.02)';
            this.style.boxShadow = '0 25px 50px rgba(139,0,0,0.3)';
            
            // Effetto spotlight
            const spotlight = document.createElement('div');
            spotlight.className = 'spotlight-effect';
            this.appendChild(spotlight);
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) rotateX(0deg) scale(1)';
            this.style.boxShadow = '0 2px 8px rgba(139,0,0,0.15)';
            
            // Rimuovi effetto spotlight
            const spotlight = this.querySelector('.spotlight-effect');
            if (spotlight) {
                spotlight.remove();
            }
        });
    });
}

// Funzione per aggiungere effetto di tenda che si apre
function addCurtainEffect() {
    const gallery = document.getElementById('theater-gallery');
    if (!gallery) return;
    
    // Crea l'effetto tenda
    const curtainLeft = document.createElement('div');
    const curtainRight = document.createElement('div');
    
    curtainLeft.className = 'curtain-left';
    curtainRight.className = 'curtain-right';
    
    gallery.appendChild(curtainLeft);
    gallery.appendChild(curtainRight);
    
    // Apri la tenda dopo un breve ritardo
    setTimeout(() => {
        curtainLeft.style.transform = 'translateX(-100%)';
        curtainRight.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            curtainLeft.remove();
            curtainRight.remove();
        }, 1000);
    }, 500);
}

// Funzione per inizializzare la pagina teatro
function initializeTheaterPage() {
    // Popola la galleria teatro
    const gallery = document.getElementById('theater-gallery');
    if (gallery) {
        gallery.innerHTML = theaterProductions.map(createTheaterCard).join('');
    }
    
    // Popola la sezione compagnie
    const companiesGrid = document.getElementById('companies-grid');
    if (companiesGrid) {
        companiesGrid.innerHTML = theaterCompanies.map(createCompanyCard).join('');
    }
    
    // Popola la sezione eventi
    const eventsContainer = document.getElementById('theater-events-container');
    if (eventsContainer) {
        eventsContainer.innerHTML = theaterEvents.map(createTheaterEventCard).join('');
    }
    
    // Aggiungi event listeners per i filtri
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const category = btn.dataset.filter;
            filterTheaterProductions(category);
        });
    });
    
    // Aggiungi effetti teatrali
    addTheaterParallax();
    setTimeout(addTheaterHoverEffects, 100);
    addCurtainEffect();
    
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
    document.querySelectorAll('.theater-item, .company-card, .event-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(40px)';
        el.style.transition = 'opacity 1s ease, transform 1s ease';
        observer.observe(el);
    });
    
    // Aggiungi effetto di typing al titolo del banner
    const bannerTitle = document.querySelector('.theater-banner h1');
    if (bannerTitle) {
        const text = bannerTitle.textContent;
        bannerTitle.textContent = '';
        let i = 0;
        const typeWriter = () => {
            if (i < text.length) {
                bannerTitle.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 150);
            } else {
                // Aggiungi effetto di spotlight finale
                bannerTitle.style.textShadow = '0 0 20px #ffd700, 0 0 40px #ffd700';
            }
        };
        setTimeout(typeWriter, 800);
    }
}

// Aggiungi stili CSS per le animazioni teatrali
const theaterStyles = document.createElement('style');
theaterStyles.textContent = `
    @keyframes fadeInTheater {
        from {
            opacity: 0;
            transform: translateY(40px) rotateX(-10deg);
        }
        to {
            opacity: 1;
            transform: translateY(0) rotateX(0deg);
        }
    }
    
    .venue-info {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid rgba(255,215,0,0.2);
        color: #cccccc;
    }
    
    .spotlight-effect {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at center, rgba(255,215,0,0.1) 0%, transparent 70%);
        pointer-events: none;
        animation: spotlight 2s ease-in-out infinite alternate;
    }
    
    @keyframes spotlight {
        0% { opacity: 0.3; }
        100% { opacity: 0.7; }
    }
    
    .curtain-left, .curtain-right {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 50%;
        background: linear-gradient(135deg, #8b0000, #dc143c);
        z-index: 1000;
        transition: transform 1s ease-in-out;
    }
    
    .curtain-left {
        left: 0;
        border-right: 3px solid #ffd700;
    }
    
    .curtain-right {
        right: 0;
        border-left: 3px solid #ffd700;
    }
    
    .theater-item {
        perspective: 1000px;
    }
    
    .company-card:nth-child(even) {
        animation-delay: 0.3s;
    }
    
    .company-card:nth-child(odd) {
        animation-delay: 0.6s;
    }
    
    .theater-banner h1 {
        border-right: 3px solid #ffd700;
        animation: blinkCursor 1.5s infinite;
    }
    
    @keyframes blinkCursor {
        0%, 50% { border-color: #ffd700; }
        51%, 100% { border-color: transparent; }
    }
`;
document.head.appendChild(theaterStyles);

// Inizializza quando il DOM è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTheaterPage);
} else {
    initializeTheaterPage();
}