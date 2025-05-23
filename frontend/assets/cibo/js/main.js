document.addEventListener('DOMContentLoaded', () => {
    // Dati di esempio per i piatti
    const foodItems = [
        {
            id: 1,
            name: 'Pasta al Pomodoro',
            category: 'primi',
            chef: 'Marco Rossi',
            description: 'Pasta fresca con pomodorini ciliegino e basilico',
            image: 'https://source.unsplash.com/random/800x600/?pasta'
        },
        {
            id: 2,
            name: 'Risotto ai Funghi',
            category: 'primi',
            chef: 'Laura Bianchi',
            description: 'Risotto cremoso con funghi porcini freschi',
            image: 'https://source.unsplash.com/random/800x600/?risotto'
        },
        {
            id: 3,
            name: 'Bistecca alla Fiorentina',
            category: 'secondi',
            chef: 'Giuseppe Verdi',
            description: 'Bistecca di manzo alla griglia con erbe aromatiche',
            image: 'https://source.unsplash.com/random/800x600/?steak'
        },
        {
            id: 4,
            name: 'Tiramisù',
            category: 'dessert',
            chef: 'Anna Conti',
            description: 'Dolce tradizionale italiano al caffè',
            image: 'https://source.unsplash.com/random/800x600/?tiramisu'
        }
    ];

    // Dati di esempio per gli chef
    const chefs = [
        {
            id: 1,
            name: 'Marco Rossi',
            specialty: 'Cucina Mediterranea',
            image: 'https://source.unsplash.com/random/400x400/?chef'
        },
        {
            id: 2,
            name: 'Laura Bianchi',
            specialty: 'Pasticceria',
            image: 'https://source.unsplash.com/random/400x400/?pastry-chef'
        },
        {
            id: 3,
            name: 'Giuseppe Verdi',
            specialty: 'Cucina Toscana',
            image: 'https://source.unsplash.com/random/400x400/?italian-chef'
        }
    ];

    // Dati di esempio per gli eventi
    const events = [
        {
            id: 1,
            name: 'Workshop di Pasta Fresca',
            date: '2024-02-15',
            description: 'Impara l\'arte della pasta fatta in casa'
        },
        {
            id: 2,
            name: 'Degustazione Vini',
            date: '2024-02-20',
            description: 'Serata di degustazione con i migliori vini italiani'
        },
        {
            id: 3,
            name: 'Corso di Pasticceria',
            date: '2024-02-25',
            description: 'Tecniche base di pasticceria moderna'
        }
    ];

    // Funzione per caricare la galleria dei piatti
    function loadFoodGallery(category = 'tutti') {
        const gallery = document.getElementById('food-gallery');
        gallery.innerHTML = '';

        const filteredItems = category === 'tutti' 
            ? foodItems 
            : foodItems.filter(item => item.category === category);

        filteredItems.forEach(item => {
            const card = document.createElement('div');
            card.className = 'food-card';
            card.innerHTML = `
                <img src="${item.image}" alt="${item.name}">
                <div class="food-info">
                    <h3>${item.name}</h3>
                    <p>${item.description}</p>
                    <p><em>Chef: ${item.chef}</em></p>
                </div>
            `;
            gallery.appendChild(card);
        });
    }

    // Funzione per caricare la sezione degli chef
    function loadChefs() {
        const chefsGrid = document.getElementById('featured-chefs');
        if (!chefsGrid) return;

        chefs.forEach(chef => {
            const card = document.createElement('div');
            card.className = 'chef-card';
            card.innerHTML = `
                <img src="${chef.image}" alt="${chef.name}">
                <h3>${chef.name}</h3>
                <p>${chef.specialty}</p>
            `;
            chefsGrid.appendChild(card);
        });
    }

    // Funzione per caricare gli eventi
    function loadEvents() {
        const eventsContainer = document.getElementById('food-events');
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
            loadFoodGallery(button.dataset.filter);
        });
    });

    // Inizializzazione della pagina
    loadFoodGallery();
    loadChefs();
    loadEvents();
});