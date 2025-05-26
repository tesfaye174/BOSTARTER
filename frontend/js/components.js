// Importa le utility
import { formatCurrency, formatDate, truncateText } from './utils.js';
import { MESSAGES } from './constants.js';

// Componente Card
export const Card = ({ title, description, image, link, className = '' }) => {
    return `
        <div class="card ${className}">
            ${image ? `<img src="${image}" alt="${title}" class="card-image">` : ''}
            <div class="card-content">
                <h3 class="card-title">${title}</h3>
                <p class="card-description">${truncateText(description, 100)}</p>
                ${link ? `<a href="${link}" class="card-link">Leggi di più</a>` : ''}
            </div>
        </div>
    `;
};

// Componente Button
export const Button = ({ text, type = 'button', variant = 'primary', className = '', onClick = '' }) => {
    return `
        <button 
            type="${type}" 
            class="btn btn-${variant} ${className}"
            ${onClick ? `onclick="${onClick}"` : ''}
        >
            ${text}
        </button>
    `;
};

// Componente Input
export const Input = ({ 
    type = 'text',
    name,
    label,
    placeholder = '',
    value = '',
    required = false,
    className = '',
    error = ''
}) => {
    return `
        <div class="form-group ${className}">
            ${label ? `<label for="${name}" class="form-label">${label}</label>` : ''}
            <input 
                type="${type}"
                id="${name}"
                name="${name}"
                class="form-input ${error ? 'is-invalid' : ''}"
                placeholder="${placeholder}"
                value="${value}"
                ${required ? 'required' : ''}
            >
            ${error ? `<div class="form-error">${error}</div>` : ''}
        </div>
    `;
};

// Componente Select
export const Select = ({
    name,
    label,
    options = [],
    value = '',
    required = false,
    className = '',
    error = ''
}) => {
    return `
        <div class="form-group ${className}">
            ${label ? `<label for="${name}" class="form-label">${label}</label>` : ''}
            <select 
                id="${name}"
                name="${name}"
                class="form-select ${error ? 'is-invalid' : ''}"
                ${required ? 'required' : ''}
            >
                ${options.map(option => `
                    <option value="${option.value}" ${option.value === value ? 'selected' : ''}>
                        ${option.label}
                    </option>
                `).join('')}
            </select>
            ${error ? `<div class="form-error">${error}</div>` : ''}
        </div>
    `;
};

// Componente Modal
export const Modal = ({ 
    id,
    title,
    content,
    footer,
    className = ''
}) => {
    return `
        <div id="${id}" class="modal ${className}">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">${title}</h2>
                    <button class="modal-close" onclick="document.getElementById('${id}').classList.remove('active')">
                        &times;
                    </button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                ${footer ? `
                    <div class="modal-footer">
                        ${footer}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
};

// Componente Alert
export const Alert = ({ 
    type = 'info',
    message,
    className = ''
}) => {
    return `
        <div class="alert alert-${type} ${className}">
            ${message}
        </div>
    `;
};

// Componente ProjectCard
export const ProjectCard = ({ 
    project,
    className = ''
}) => {
    const progress = (project.currentAmount / project.targetAmount) * 100;
    
    return `
        <div class="project-card ${className}">
            <img src="${project.image}" alt="${project.title}" class="project-image">
            <div class="project-content">
                <h3 class="project-title">${project.title}</h3>
                <p class="project-description">${truncateText(project.description, 150)}</p>
                <div class="project-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progress}%"></div>
                    </div>
                    <div class="progress-stats">
                        <span class="progress-amount">${formatCurrency(project.currentAmount)}</span>
                        <span class="progress-target">di ${formatCurrency(project.targetAmount)}</span>
                    </div>
                </div>
                <div class="project-meta">
                    <span class="project-backers">${project.backers} sostenitori</span>
                    <span class="project-days">${project.daysLeft} giorni rimasti</span>
                </div>
                <a href="/projects/${project.id}" class="btn btn-primary">Sostieni</a>
            </div>
        </div>
    `;
};

// Componente Pagination
export const Pagination = ({
    currentPage,
    totalPages,
    onPageChange,
    className = ''
}) => {
    const pages = [];
    const maxPages = 5;
    
    let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
    let endPage = Math.min(totalPages, startPage + maxPages - 1);
    
    if (endPage - startPage + 1 < maxPages) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }
    
    return `
        <div class="pagination ${className}">
            ${currentPage > 1 ? `
                <button 
                    class="pagination-prev"
                    onclick="${onPageChange}(${currentPage - 1})"
                >
                    Precedente
                </button>
            ` : ''}
            
            ${pages.map(page => `
                <button 
                    class="pagination-page ${page === currentPage ? 'active' : ''}"
                    onclick="${onPageChange}(${page})"
                >
                    ${page}
                </button>
            `).join('')}
            
            ${currentPage < totalPages ? `
                <button 
                    class="pagination-next"
                    onclick="${onPageChange}(${currentPage + 1})"
                >
                    Successivo
                </button>
            ` : ''}
        </div>
    `;
};

// Componente Loading
export const Loading = ({ className = '' }) => {
    return `
        <div class="loading ${className}">
            <div class="loading-spinner"></div>
            <p class="loading-text">Caricamento...</p>
        </div>
    `;
};

// Componente Error
export const Error = ({ 
    message = MESSAGES.ERROR.DEFAULT,
    className = ''
}) => {
    return `
        <div class="error ${className}">
            <div class="error-icon">!</div>
            <p class="error-message">${message}</p>
        </div>
    `;
}; 