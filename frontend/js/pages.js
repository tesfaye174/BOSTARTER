// Importa i componenti
import { 
    Card, 
    Button, 
    Input, 
    Select, 
    Modal, 
    Alert, 
    ProjectCard, 
    Pagination, 
    Loading, 
    Error 
} from './components.js';

// Importa le utility
import { formatCurrency, formatDate } from './utils.js';

// Importa le costanti
import { API_ENDPOINTS, MESSAGES } from './constants.js';

// Importa il gestore API
import { apiManager } from './api.js';

// Importa il gestore dello store
import { store } from './store.js';

// Pagina Home
export const HomePage = async () => {
    try {
        const [featuredProjects, categories] = await Promise.all([
            apiManager.get(API_ENDPOINTS.PROJECTS.FEATURED),
            apiManager.get(API_ENDPOINTS.CATEGORIES.LIST)
        ]);

        return `
            <section class="hero">
                <div class="hero-content">
                    <h1 class="hero-title">Finanzia i tuoi progetti</h1>
                    <p class="hero-description">
                        BoStarter è la piattaforma che ti permette di realizzare i tuoi progetti
                        attraverso il crowdfunding.
                    </p>
                    <div class="hero-buttons">
                        <a href="/projects/create" class="btn btn-primary">Crea un progetto</a>
                        <a href="/projects" class="btn btn-secondary">Esplora progetti</a>
                    </div>
                </div>
            </section>

            <section class="featured-projects">
                <h2 class="section-title">Progetti in evidenza</h2>
                <div class="projects-grid">
                    ${featuredProjects.map(project => ProjectCard({ project })).join('')}
                </div>
            </section>

            <section class="categories">
                <h2 class="section-title">Categorie</h2>
                <div class="categories-grid">
                    ${categories.map(category => Card({
                        title: category.name,
                        description: category.description,
                        image: category.image,
                        link: `/categories/${category.id}`
                    })).join('')}
                </div>
            </section>

            <section class="how-it-works">
                <h2 class="section-title">Come funziona</h2>
                <div class="steps-grid">
                    <div class="step">
                        <div class="step-icon">1</div>
                        <h3 class="step-title">Crea il tuo progetto</h3>
                        <p class="step-description">
                            Descrivi il tuo progetto e imposta l'obiettivo di raccolta.
                        </p>
                    </div>
                    <div class="step">
                        <div class="step-icon">2</div>
                        <h3 class="step-title">Promuovi</h3>
                        <p class="step-description">
                            Condividi il tuo progetto sui social e con la tua rete.
                        </p>
                    </div>
                    <div class="step">
                        <div class="step-icon">3</div>
                        <h3 class="step-title">Raccogli fondi</h3>
                        <p class="step-description">
                            Ricevi i contributi e realizza il tuo progetto.
                        </p>
                    </div>
                </div>
            </section>
        `;
    } catch (error) {
        return Error({ message: MESSAGES.ERROR.LOADING });
    }
};

// Pagina Progetti
export const ProjectsPage = async (params, query) => {
    try {
        const { page = 1, category, sort } = query;
        const projects = await apiManager.get(API_ENDPOINTS.PROJECTS.LIST, {
            page,
            category,
            sort
        });

        return `
            <section class="projects-header">
                <h1 class="page-title">Progetti</h1>
                <div class="projects-filters">
                    ${Select({
                        name: 'category',
                        label: 'Categoria',
                        options: store.getState().categories.map(category => ({
                            value: category.id,
                            label: category.name
                        })),
                        value: category
                    })}
                    ${Select({
                        name: 'sort',
                        label: 'Ordina per',
                        options: [
                            { value: 'newest', label: 'Più recenti' },
                            { value: 'most_funded', label: 'Più finanziati' },
                            { value: 'most_backers', label: 'Più sostenitori' }
                        ],
                        value: sort
                    })}
                </div>
            </section>

            <section class="projects-grid">
                ${projects.items.map(project => ProjectCard({ project })).join('')}
            </section>

            ${Pagination({
                currentPage: parseInt(page),
                totalPages: projects.totalPages,
                onPageChange: 'handlePageChange'
            })}
        `;
    } catch (error) {
        return Error({ message: MESSAGES.ERROR.LOADING });
    }
};

// Pagina Dettaglio Progetto
export const ProjectDetailPage = async (params) => {
    try {
        const project = await apiManager.get(API_ENDPOINTS.PROJECTS.DETAIL(params.id));
        const progress = (project.currentAmount / project.targetAmount) * 100;

        return `
            <article class="project-detail">
                <div class="project-header">
                    <img src="${project.image}" alt="${project.title}" class="project-image">
                    <div class="project-info">
                        <h1 class="project-title">${project.title}</h1>
                        <p class="project-description">${project.description}</p>
                        <div class="project-stats">
                            <div class="stat">
                                <span class="stat-value">${formatCurrency(project.currentAmount)}</span>
                                <span class="stat-label">raccolti</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">${project.backers}</span>
                                <span class="stat-label">sostenitori</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">${project.daysLeft}</span>
                                <span class="stat-label">giorni rimasti</span>
                            </div>
                        </div>
                        <div class="project-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${progress}%"></div>
                            </div>
                            <div class="progress-stats">
                                <span class="progress-amount">${formatCurrency(project.currentAmount)}</span>
                                <span class="progress-target">di ${formatCurrency(project.targetAmount)}</span>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-large">Sostieni questo progetto</button>
                    </div>
                </div>

                <div class="project-content">
                    <div class="project-details">
                        <h2 class="section-title">Dettagli del progetto</h2>
                        <div class="project-details-content">
                            ${project.details}
                        </div>
                    </div>

                    <div class="project-rewards">
                        <h2 class="section-title">Ricompense</h2>
                        <div class="rewards-grid">
                            ${project.rewards.map(reward => `
                                <div class="reward-card">
                                    <h3 class="reward-title">${reward.title}</h3>
                                    <p class="reward-description">${reward.description}</p>
                                    <div class="reward-price">${formatCurrency(reward.price)}</div>
                                    <button class="btn btn-secondary">Seleziona</button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </article>
        `;
    } catch (error) {
        return Error({ message: MESSAGES.ERROR.LOADING });
    }
};

// Pagina Crea Progetto
export const CreateProjectPage = () => {
    return `
        <section class="create-project">
            <h1 class="page-title">Crea un nuovo progetto</h1>
            <form class="project-form" id="createProjectForm">
                ${Input({
                    name: 'title',
                    label: 'Titolo del progetto',
                    placeholder: 'Inserisci il titolo del progetto',
                    required: true
                })}
                
                ${Input({
                    type: 'textarea',
                    name: 'description',
                    label: 'Descrizione',
                    placeholder: 'Descrivi il tuo progetto',
                    required: true
                })}
                
                ${Input({
                    type: 'number',
                    name: 'targetAmount',
                    label: 'Obiettivo di raccolta',
                    placeholder: 'Inserisci l\'importo obiettivo',
                    required: true
                })}
                
                ${Input({
                    type: 'file',
                    name: 'image',
                    label: 'Immagine del progetto',
                    required: true
                })}
                
                ${Select({
                    name: 'category',
                    label: 'Categoria',
                    options: store.getState().categories.map(category => ({
                        value: category.id,
                        label: category.name
                    })),
                    required: true
                })}
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Crea progetto</button>
                    <a href="/projects" class="btn btn-secondary">Annulla</a>
                </div>
            </form>
        </section>
    `;
};

// Pagina Profilo
export const ProfilePage = async () => {
    try {
        const user = store.getState().user;
        if (!user) {
            return `
                <div class="auth-required">
                    <h2>Accesso richiesto</h2>
                    <p>Per accedere al tuo profilo, effettua il login o registrati.</p>
                    <div class="auth-buttons">
                        <a href="/login" class="btn btn-primary">Accedi</a>
                        <a href="/register" class="btn btn-secondary">Registrati</a>
                    </div>
                </div>
            `;
        }

        const userProjects = await apiManager.get(API_ENDPOINTS.PROJECTS.LIST, {
            userId: user.id
        });

        return `
            <section class="profile">
                <div class="profile-header">
                    <img src="${user.avatar}" alt="${user.name}" class="profile-avatar">
                    <div class="profile-info">
                        <h1 class="profile-name">${user.name}</h1>
                        <p class="profile-email">${user.email}</p>
                    </div>
                </div>

                <div class="profile-content">
                    <div class="profile-section">
                        <h2 class="section-title">I tuoi progetti</h2>
                        <div class="projects-grid">
                            ${userProjects.items.map(project => ProjectCard({ project })).join('')}
                        </div>
                    </div>

                    <div class="profile-section">
                        <h2 class="section-title">Progetti sostenuti</h2>
                        <div class="projects-grid">
                            ${user.backedProjects.map(project => ProjectCard({ project })).join('')}
                        </div>
                    </div>
                </div>
            </section>
        `;
    } catch (error) {
        return Error({ message: MESSAGES.ERROR.LOADING });
    }
};

// Pagina Login
export const LoginPage = () => {
    return `
        <section class="auth-page">
            <div class="auth-card">
                <h1 class="auth-title">Accedi</h1>
                <form class="auth-form" id="loginForm">
                    ${Input({
                        type: 'email',
                        name: 'email',
                        label: 'Email',
                        placeholder: 'Inserisci la tua email',
                        required: true
                    })}
                    
                    ${Input({
                        type: 'password',
                        name: 'password',
                        label: 'Password',
                        placeholder: 'Inserisci la tua password',
                        required: true
                    })}
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Accedi</button>
                    </div>
                    
                    <div class="auth-links">
                        <a href="/register">Non hai un account? Registrati</a>
                        <a href="/forgot-password">Password dimenticata?</a>
                    </div>
                </form>
            </div>
        </section>
    `;
};

// Pagina Registrazione
export const RegisterPage = () => {
    return `
        <section class="auth-page">
            <div class="auth-card">
                <h1 class="auth-title">Registrati</h1>
                <form class="auth-form" id="registerForm">
                    ${Input({
                        name: 'name',
                        label: 'Nome',
                        placeholder: 'Inserisci il tuo nome',
                        required: true
                    })}
                    
                    ${Input({
                        type: 'email',
                        name: 'email',
                        label: 'Email',
                        placeholder: 'Inserisci la tua email',
                        required: true
                    })}
                    
                    ${Input({
                        type: 'password',
                        name: 'password',
                        label: 'Password',
                        placeholder: 'Inserisci la tua password',
                        required: true
                    })}
                    
                    ${Input({
                        type: 'password',
                        name: 'confirmPassword',
                        label: 'Conferma password',
                        placeholder: 'Conferma la tua password',
                        required: true
                    })}
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Registrati</button>
                    </div>
                    
                    <div class="auth-links">
                        <a href="/login">Hai già un account? Accedi</a>
                    </div>
                </form>
            </div>
        </section>
    `;
}; 