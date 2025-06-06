/**
 * ===== GENERATORE DATI MOCK CENTRALIZZATO =====
 * Sistema unificato per generare dati mock per tutte le categorie
 * Elimina duplicazione di codice tra i manager delle categorie
 */

class MockDataGenerator {
    constructor() {
        this.categories = {
            arte: {
                types: ['Pittura ad Olio', 'Scultura Contemporanea', 'Installazione Multimediale', 'Fotografia Concettuale', 'Arte Digitale', 'Murales Urbano'],
                subcategories: ['pittura', 'scultura', 'installazioni', 'fotografia', 'digitale', 'street'],
                creators: ['Artista', 'Pittore', 'Scultore', 'Fotografo'],
                specialties: ['Pittore', 'Scultrice', 'Artista Digitale', 'Fotografa', 'Installatrice', 'Street Artist'],
                artistNames: ['Marco Rossi', 'Giulia Bianchi', 'Alessandro Verdi', 'Francesca Neri', 'Luca Ferrari', 'Sofia Romano', 'Andrea Ricci', 'Elena Greco'],
                imagePrefix: 'art-project',
                avatarPrefix: 'artist',
                goalRange: { min: 5000, max: 35000 },
                backersRange: { min: 10, max: 160 },
                customField: 'creativity'
            },
            tecnologia: {
                types: ['App Mobile', 'Applicazione Web', 'Intelligenza Artificiale', 'Internet of Things', 'Blockchain', 'Gaming'],
                subcategories: ['app-mobile', 'web-app', 'ai-ml', 'iot', 'blockchain', 'gaming'],
                creators: ['Developer', 'Sviluppatore', 'Tech Lead', 'CTO'],
                specialties: ['Frontend Developer', 'Backend Developer', 'Full Stack', 'Data Scientist', 'AI Engineer', 'Blockchain Developer'],
                artistNames: ['Luca Tech', 'Maria Code', 'Giovanni Dev', 'Sara AI', 'Marco Blockchain', 'Elena Mobile', 'Andrea IoT', 'Chiara Gaming'],
                imagePrefix: 'tech-project',
                avatarPrefix: 'developer',
                goalRange: { min: 10000, max: 60000 },
                backersRange: { min: 15, max: 215 },
                customField: 'innovation'
            }
        };
    }

    /**
     * Genera progetti mock per una categoria specifica
     * @param {string} category - Nome della categoria (arte, tecnologia, etc.)
     * @param {number} count - Numero di progetti da generare
     * @returns {Array} Array di progetti mock
     */
    generateProjects(category, count = 24) {
        const config = this.categories[category];
        if (!config) {
            throw new Error(`Category ${category} not supported`);
        }

        const projects = [];

        for (let i = 1; i <= count; i++) {
            const subcategory = config.subcategories[Math.floor(Math.random() * config.subcategories.length)];
            const typeIndex = config.subcategories.indexOf(subcategory);

            const project = {
                id: i,
                title: this.generateProjectTitle(category, i),
                description: this.generateProjectDescription(config.types[typeIndex], category),
                category: subcategory,
                image: `/frontend/images/${config.imagePrefix}-${(i % 6) + 1}.jpg`,
                creator: `${config.creators[Math.floor(Math.random() * config.creators.length)]} ${i}`,
                goal: Math.floor(Math.random() * (config.goalRange.max - config.goalRange.min)) + config.goalRange.min,
                raised: 0,
                backers: Math.floor(Math.random() * (config.backersRange.max - config.backersRange.min)) + config.backersRange.min,
                daysLeft: Math.floor(Math.random() * 45) + 1,
                featured: Math.random() > 0.8,
                createdAt: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000),
                tags: [subcategory, config.types[typeIndex].toLowerCase().replace(/\s+/g, '-')]
            };

            // Add category-specific custom field
            project[config.customField] = Math.floor(Math.random() * 100) + 1;

            projects.push(project);
        }

        // Calculate raised amounts and progress
        projects.forEach(project => {
            project.raised = Math.floor(project.goal * (Math.random() * 0.8 + 0.1));
            project.progress = Math.round((project.raised / project.goal) * 100);
        });

        return projects;
    }

    /**
     * Genera artisti/creator mock per una categoria specifica
     * @param {string} category - Nome della categoria
     * @param {number} count - Numero di creator da generare
     * @returns {Array} Array di creator mock
     */
    generateCreators(category, count = 8) {
        const config = this.categories[category];
        if (!config) {
            throw new Error(`Category ${category} not supported`);
        }

        const creators = [];

        for (let i = 0; i < count; i++) {
            creators.push({
                id: i + 1,
                name: config.artistNames[i],
                specialty: config.specialties[Math.floor(Math.random() * config.specialties.length)],
                projects: Math.floor(Math.random() * 8) + 1,
                followers: Math.floor(Math.random() * 2000) + 100,
                avatar: `/frontend/images/${config.avatarPrefix}-${i + 1}.jpg`,
                featured: Math.random() > 0.5
            });
        }

        return creators;
    }

    /**
     * Genera tecnologie mock (specifico per categoria tecnologia)
     * @param {number} count - Numero di tecnologie da generare
     * @returns {Array} Array di tecnologie mock
     */
    generateTechnologies(count = 8) {
        const techNames = ['React', 'Vue.js', 'Node.js', 'Python', 'TensorFlow', 'Blockchain', 'IoT', 'AR/VR'];
        const descriptions = ['Frontend Framework', 'Progressive Framework', 'Runtime Environment', 'Programming Language', 'ML Library', 'Distributed Ledger', 'Connected Devices', 'Extended Reality'];
        const technologies = [];

        for (let i = 0; i < count; i++) {
            technologies.push({
                id: i + 1,
                name: techNames[i],
                description: descriptions[i],
                projects: Math.floor(Math.random() * 12) + 1,
                popularity: Math.floor(Math.random() * 100) + 1,
                icon: `/frontend/images/tech-${i + 1}.svg`,
                trending: Math.random() > 0.6
            });
        }

        return technologies;
    }

    /**
     * Genera titolo progetto
     * @param {string} category - Categoria
     * @param {number} index - Indice progetto
     * @returns {string} Titolo generato
     */
    generateProjectTitle(category, index) {
        const titles = {
            arte: `Opera Artistica ${index}`,
            tecnologia: `Progetto Tech ${index}`,
            default: `Progetto ${index}`
        };

        return titles[category] || titles.default;
    }

    /**
     * Genera descrizione progetto
     * @param {string} type - Tipo di progetto
     * @param {string} category - Categoria
     * @returns {string} Descrizione generata
     */
    generateProjectDescription(type, category) {
        const descriptions = {
            arte: `Un'opera innovativa di ${type} che esplora temi contemporanei attraverso tecniche tradizionali e moderne.`,
            tecnologia: `Una soluzione innovativa nel campo ${type} che rivoluziona il modo di interagire con la tecnologia.`,
            default: `Un progetto innovativo di ${type} con approcci creativi e tecniche all'avanguardia.`
        };

        return descriptions[category] || descriptions.default;
    }

    /**
     * Simula delay API per testing
     * @param {number} ms - Millisecondi di delay
     * @returns {Promise} Promise che si risolve dopo il delay
     */
    async simulateApiDelay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Aggiunge una nuova categoria di supporto
     * @param {string} name - Nome categoria
     * @param {Object} config - Configurazione categoria
     */
    addCategory(name, config) {
        this.categories[name] = config;
    }

    /**
     * Ottiene la configurazione per una categoria
     * @param {string} category - Nome categoria
     * @returns {Object} Configurazione categoria
     */
    getCategoryConfig(category) {
        return this.categories[category];
    }
}

// Esporta istanza singleton
window.MockDataGenerator = new MockDataGenerator();

// Compatibilità con CommonJS se necessario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MockDataGenerator;
}
