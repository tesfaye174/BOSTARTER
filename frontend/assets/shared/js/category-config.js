/**
 * ===== CONFIGURAZIONI CATEGORIE BOSTARTER =====
 * Configurazioni centralizzate per ogni categoria
 */

window.BostarterConfig = {

    // Configurazione Arte
    arte: {
        colors: {
            primary: '#2997ff',
            accent: '#41a7ff',
            secondary: '#1a73e8'
        },
        selectors: {
            cardClass: 'art-card',
            itemSelector: '.art-card',
            profileSelector: '.artist-card'
        },
        filters: ['tutti', 'pittura', 'scultura', 'fotografia', 'digital'],
        texts: {
            title: 'Arte & Creatività',
            subtitle: 'Esplora il mondo dell\'arte e della creatività',
            profileType: 'Artista'
        }
    },

    // Configurazione Musica
    musica: {
        colors: {
            primary: '#6c1cd1',
            accent: '#8e44ad',
            secondary: '#9b59b6'
        },
        selectors: {
            cardClass: 'music-card',
            itemSelector: '.music-card',
            profileSelector: '.musician-card'
        },
        filters: ['tutti', 'rock', 'pop', 'classica', 'jazz', 'elettronica'],
        texts: {
            title: 'Musica & Suoni',
            subtitle: 'Scopri artisti e progetti musicali',
            profileType: 'Musicista'
        }
    },

    // Configurazione Design
    design: {
        colors: {
            primary: '#2c3e50',
            accent: '#34495e',
            secondary: '#3498db'
        },
        selectors: {
            cardClass: 'design-card',
            itemSelector: '.design-card',
            profileSelector: '.designer-card'
        },
        filters: ['tutti', 'grafico', 'web', 'prodotto', 'ux-ui'],
        texts: {
            title: 'Design & Innovazione',
            subtitle: 'Progetti di design contemporaneo',
            profileType: 'Designer'
        }
    },

    // Configurazione Fotografia
    fotografia: {
        colors: {
            primary: '#000000',
            accent: '#333333',
            secondary: '#666666'
        },
        selectors: {
            cardClass: 'photo-card',
            itemSelector: '.photo-card',
            profileSelector: '.photographer-card'
        },
        filters: ['tutti', 'ritratto', 'paesaggio', 'street', 'moda', 'reportage'],
        texts: {
            title: 'Fotografia & Immagini',
            subtitle: 'L\'arte di catturare momenti',
            profileType: 'Fotografo'
        }
    },

    // Configurazione Cinema
    film: {
        colors: {
            primary: '#e50914',
            accent: '#f40612',
            secondary: '#b20710'
        },
        selectors: {
            cardClass: 'movie-card',
            itemSelector: '.movie-card',
            profileSelector: '.director-card'
        },
        filters: ['tutti', 'drama', 'commedia', 'azione', 'documentario', 'animazione'],
        texts: {
            title: 'Cinema & Video',
            subtitle: 'Storie che prendono vita',
            profileType: 'Regista'
        }
    },

    // Configurazione Moda
    moda: {
        colors: {
            primary: '#d4af37',
            accent: '#f4d03f',
            secondary: '#b7950b'
        },
        selectors: {
            cardClass: 'fashion-card',
            itemSelector: '.fashion-card',
            profileSelector: '.designer-card'
        },
        filters: ['tutti', 'alta-moda', 'streetwear', 'vintage', 'sostenibile'],
        texts: {
            title: 'Moda & Stile',
            subtitle: 'Tendenze e creatività nel fashion',
            profileType: 'Stilista'
        }
    },

    // Configurazione Cibo
    cibo: {
        colors: {
            primary: '#e67e22',
            accent: '#f39c12',
            secondary: '#d68910'
        },
        selectors: {
            cardClass: 'food-card',
            itemSelector: '.food-card',
            profileSelector: '.chef-card'
        },
        filters: ['tutti', 'tradizionale', 'fusion', 'vegano', 'dolci', 'bevande'],
        texts: {
            title: 'Cibo & Gastronomia',
            subtitle: 'Sapori e tradizioni culinarie',
            profileType: 'Chef'
        }
    },

    // Configurazione Tecnologia
    tecnologia: {
        colors: {
            primary: '#00d4ff',
            accent: '#33ddff',
            secondary: '#0099cc'
        },
        selectors: {
            cardClass: 'tech-card',
            itemSelector: '.tech-card',
            profileSelector: '.developer-card'
        },
        filters: ['tutti', 'web', 'mobile', 'ai', 'blockchain', 'iot'],
        texts: {
            title: 'Tecnologia & Innovazione',
            subtitle: 'Il futuro prende forma',
            profileType: 'Developer'
        }
    },

    // Configurazione Giochi
    giochi: {
        colors: {
            primary: '#ff6b35',
            accent: '#ff8c42',
            secondary: '#e55a2b'
        },
        selectors: {
            cardClass: 'game-card',
            itemSelector: '.game-card',
            profileSelector: '.developer-card'
        },
        filters: ['tutti', 'indie', 'mobile', 'pc', 'console', 'vr'],
        texts: {
            title: 'Gaming & Intrattenimento',
            subtitle: 'Mondi virtuali e interattivi',
            profileType: 'Game Developer'
        }
    },

    // Configurazione Fumetti
    fumetti: {
        colors: {
            primary: '#ff4081',
            accent: '#ff6ec7',
            secondary: '#e91e63'
        },
        selectors: {
            cardClass: 'comic-card',
            itemSelector: '.comic-card',
            profileSelector: '.artist-card'
        },
        filters: ['tutti', 'superhero', 'manga', 'indie', 'webcomic'],
        texts: {
            title: 'Fumetti & Illustrazione',
            subtitle: 'Storie disegnate e fantasia',
            profileType: 'Fumettista'
        }
    },

    // Configurazione Giornalismo
    giornalismo: {
        colors: {
            primary: '#3182ce',
            accent: '#4299e1',
            secondary: '#2b77cb'
        },
        selectors: {
            cardClass: 'article-card',
            itemSelector: '.article-card',
            profileSelector: '.journalist-card'
        },
        filters: ['tutti', 'cronaca', 'sport', 'cultura', 'politica', 'economia'],
        texts: {
            title: 'Giornalismo & Informazione',
            subtitle: 'Storie che contano',
            profileType: 'Giornalista'
        }
    },

    // Configurazione Artigianato
    artigianato: {
        colors: {
            primary: '#c17767',
            accent: '#d4a574',
            secondary: '#a6614f'
        },
        selectors: {
            cardClass: 'craft-card',
            itemSelector: '.craft-card',
            profileSelector: '.artisan-card'
        },
        filters: ['tutti', 'ceramica', 'legno', 'tessile', 'metallo', 'vetro'],
        texts: {
            title: 'Artigianato & Tradizioni',
            subtitle: 'Maestria e saperi antichi',
            profileType: 'Artigiano'
        }
    },

    // Configurazione Teatro
    teatro: {
        colors: {
            primary: '#8e44ad',
            accent: '#a569bd',
            secondary: '#7d3c98'
        },
        selectors: {
            cardClass: 'theater-card',
            itemSelector: '.theater-card',
            profileSelector: '.actor-card'
        },
        filters: ['tutti', 'classico', 'contemporaneo', 'musical', 'cabaret'],
        texts: {
            title: 'Teatro & Performance',
            subtitle: 'L\'arte della rappresentazione',
            profileType: 'Attore'
        }
    },

    // Configurazione Danza
    danza: {
        colors: {
            primary: '#9b59b6',
            accent: '#bb6bd9',
            secondary: '#8e44ad'
        },
        selectors: {
            cardClass: 'dance-card',
            itemSelector: '.dance-card',
            profileSelector: '.dancer-card'
        },
        filters: ['tutti', 'classica', 'moderna', 'hip-hop', 'latino', 'folk'],
        texts: {
            title: 'Danza & Movimento',
            subtitle: 'Espressione attraverso il corpo',
            profileType: 'Danzatore'
        }
    },

    // Configurazione Editoriale
    editoriale: {
        colors: {
            primary: '#3498db',
            accent: '#5dade2',
            secondary: '#2e86c1'
        },
        selectors: {
            cardClass: 'editorial-card',
            itemSelector: '.editorial-card',
            profileSelector: '.author-card'
        },
        filters: ['tutti', 'romanzo', 'saggio', 'poesia', 'biografia', 'tecnico'],
        texts: {
            title: 'Editoria & Letteratura',
            subtitle: 'Parole che ispirano',
            profileType: 'Autore'
        }
    }
};

/**
 * Funzione per ottenere la configurazione di una categoria
 * @param {string} category - Nome della categoria
 * @returns {Object} Configurazione della categoria
 */
window.BostarterConfig.getConfig = function (category) {
    return this[category] || {};
};

/**
 * Funzione per applicare la configurazione a una pagina
 * @param {string} category - Nome della categoria
 */
window.BostarterConfig.applyConfig = function (category) {
    const config = this.getConfig(category);
    if (!config.colors) return;

    // Applica i colori CSS custom properties
    const root = document.documentElement;
    root.style.setProperty('--primary-color', config.colors.primary);
    root.style.setProperty('--accent-color', config.colors.accent);
    root.style.setProperty('--secondary-color', config.colors.secondary);

    // Aggiorna meta tags se presenti
    const titleElement = document.querySelector('title');
    if (titleElement && config.texts?.title) {
        titleElement.textContent = `${config.texts.title} | BOSTARTER`;
    }

    return config;
};
