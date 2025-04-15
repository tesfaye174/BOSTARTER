class App {
    constructor() {
        this.api = '/api';
        this.init();
    }

    async init() {
        // Check authentication status
        const token = localStorage.getItem('token');
        if (token) {
            await this.loadDashboard();
        } else {
            this.showLoginForm();
        }
    }

    async loadDashboard() {
        try {
            const response = await fetch(`${this.api}/projects`);
            const projects = await response.json();
            this.renderProjects(projects);
        } catch (error) {
            console.error('Failed to load projects:', error);
        }
    }

    renderProjects(projects) {
        const container = document.getElementById('app');
        // Render project list
    }

    showLoginForm() {
        const container = document.getElementById('app');
        // Render login form
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new App();
});