// Importa le costanti
import { API_ENDPOINTS } from './constants.js';

// Classe per la gestione del routing
class Router {
    constructor() {
        this.routes = new Map();
        this.currentRoute = null;
        this.params = {};
        this.query = {};

        // Gestione degli eventi di navigazione
        window.addEventListener('popstate', this.handleRoute.bind(this));
        document.addEventListener('click', this.handleClick.bind(this));
    }

    // Aggiunge una nuova route
    add(path, component) {
        this.routes.set(path, component);
    }

    // Gestisce il click sui link
    handleClick(event) {
        const link = event.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

        event.preventDefault();
        this.navigate(href);
    }

    // Gestisce la navigazione
    navigate(path) {
        window.history.pushState(null, '', path);
        this.handleRoute();
    }

    // Gestisce il cambio di route
    handleRoute() {
        const path = window.location.pathname;
        const queryString = window.location.search;
        this.query = this.parseQuery(queryString);

        // Cerca la route corrispondente
        for (const [route, component] of this.routes) {
            const params = this.matchRoute(path, route);
            if (params) {
                this.params = params;
                this.currentRoute = route;
                this.render(component);
                return;
            }
        }

        // Route non trovata
        this.handleNotFound();
    }

    // Analizza i parametri della query
    parseQuery(queryString) {
        const params = {};
        const searchParams = new URLSearchParams(queryString);

        for (const [key, value] of searchParams) {
            params[key] = value;
        }

        return params;
    }

    // Verifica se il path corrisponde alla route
    matchRoute(path, route) {
        const routeParts = route.split('/');
        const pathParts = path.split('/');

        if (routeParts.length !== pathParts.length) {
            return null;
        }

        const params = {};

        for (let i = 0; i < routeParts.length; i++) {
            const routePart = routeParts[i];
            const pathPart = pathParts[i];

            if (routePart.startsWith(':')) {
                const paramName = routePart.slice(1);
                params[paramName] = pathPart;
            } else if (routePart !== pathPart) {
                return null;
            }
        }

        return params;
    }

    // Renderizza il componente
    async render(component) {
        const main = document.querySelector('main');
        if (!main) return;

        try {
            const content = await component(this.params, this.query);
            main.innerHTML = content;

            // Aggiorna il titolo della pagina
            document.title = component.title || 'BoStarter';

            // Aggiorna i meta tag
            this.updateMetaTags(component.meta);

            // Aggiorna i link attivi
            this.updateActiveLinks();
        } catch (error) {
            // Silent error handling for route rendering
            this.handleError(error);
        }
    }

    // Aggiorna i meta tag
    updateMetaTags(meta = {}) {
        const metaTags = document.querySelectorAll('meta[name^="description"], meta[name^="keywords"]');
        metaTags.forEach(tag => tag.remove());

        if (meta.description) {
            const description = document.createElement('meta');
            description.name = 'description';
            description.content = meta.description;
            document.head.appendChild(description);
        }

        if (meta.keywords) {
            const keywords = document.createElement('meta');
            keywords.name = 'keywords';
            keywords.content = meta.keywords;
            document.head.appendChild(keywords);
        }
    }

    // Aggiorna i link attivi
    updateActiveLinks() {
        const links = document.querySelectorAll('a[href]');
        links.forEach(link => {
            const href = link.getAttribute('href');
            if (href === window.location.pathname) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Gestisce la route non trovata
    handleNotFound() {
        this.render(async () => {
            return `
                <div class="not-found">
                    <h1>404 - Pagina non trovata</h1>
                    <p>La pagina che stai cercando non esiste.</p>
                    <a href="/" class="btn btn-primary">Torna alla home</a>
                </div>
            `;
        });
    }

    // Gestisce gli errori
    handleError(error) {
        this.render(async () => {
            return `
                <div class="error">
                    <h1>Errore</h1>
                    <p>Si è verificato un errore durante il caricamento della pagina.</p>
                    <a href="/" class="btn btn-primary">Torna alla home</a>
                </div>
            `;
        });
    }

    // Ottiene i parametri della route corrente
    getParams() {
        return this.params;
    }

    // Ottiene i parametri della query
    getQuery() {
        return this.query;
    }

    // Ottiene la route corrente
    getCurrentRoute() {
        return this.currentRoute;
    }
}

// Crea un'istanza globale del router
const router = new Router();

// Esporta l'istanza e la classe
export { router, Router }; 