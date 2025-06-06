/**
 * Volume Analysis Frontend Controller
 * Handles the user interface for redundancy analysis and volume calculations
 */

class VolumeAnalysisController {
    constructor() {
        this.apiBaseUrl = '../backend/api/volume_analysis.php';
        this.charts = {};
        this.currentData = null;

        this.init();
    }

    async init() {
        try {
            // Set up event listeners
            this.setupEventListeners();

            // Load initial data
            await this.loadFullAnalysis();

            // Hide loading and show content
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('content').classList.remove('hidden');
        } catch (error) {
            // Silent error handling for initialization
            this.showError('Errore durante l\'inizializzazione dell\'analisi');
        }
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Consistency testing buttons
        const testBtn = document.getElementById('test-consistency-btn');
        if (testBtn) {
            testBtn.addEventListener('click', () => this.testConsistency());
        }

        const fixBtn = document.getElementById('fix-inconsistencies-btn');
        if (fixBtn) {
            fixBtn.addEventListener('click', () => this.fixInconsistencies());
        }
    }

    async loadFullAnalysis() {
        try {
            const response = await fetch(`${this.apiBaseUrl}?action=full_analysis`);
            const result = await response.json();

            if (result.success) {
                this.currentData = result.data;
                this.populateOverview();
                this.createCharts();
                await this.loadRecommendations();
            } else {
                throw new Error(result.message || 'Failed to load analysis');
            }
        } catch (error) {
            // Silent error handling for analysis loading
            this.showError('Errore nel caricamento dell\'analisi');
        }
    }

    populateOverview() {
        if (!this.currentData) return;

        const data = this.currentData;

        // Update quick stats
        document.getElementById('redundancy-cost').textContent =
            data.redundancy_analysis?.total_redundancy_cost?.toFixed(2) || '--';
        document.getElementById('non-redundancy-cost').textContent =
            data.redundancy_analysis?.total_non_redundancy_cost?.toFixed(2) || '--';
        document.getElementById('recommendation').textContent =
            data.recommendations?.strategy || '--';
        document.getElementById('total-projects').textContent =
            data.current_stats?.total_projects || '--';

        // Update parameters
        const params = data.parameters || {};
        document.getElementById('param-wi').textContent = params.wI || 1;
        document.getElementById('param-wb').textContent = params.wB || 0.5;
        document.getElementById('param-a').textContent = params.a || 2;
        document.getElementById('param-projects').textContent = params.num_projects || 10;
        document.getElementById('param-fundings').textContent = params.fundings_per_project || 3;
        document.getElementById('param-users').textContent = params.num_users || 5;

        // Update operations
        const ops = data.operations_analysis || {};
        document.getElementById('op-add-freq').textContent = ops.add_project_frequency || 1;
        document.getElementById('op-view-freq').textContent = ops.view_all_frequency || 1;
        document.getElementById('op-count-freq').textContent = ops.count_projects_frequency || 3;
    }

    createCharts() {
        if (!this.currentData) return;

        this.createCostComparisonChart();
        this.createOperationsChart();
    }

    createCostComparisonChart() {
        const ctx = document.getElementById('costChart');
        if (!ctx) return;

        const redundancyCost = this.currentData.redundancy_analysis?.total_redundancy_cost || 0;
        const nonRedundancyCost = this.currentData.redundancy_analysis?.total_non_redundancy_cost || 0;

        if (this.charts.costChart) {
            this.charts.costChart.destroy();
        }

        this.charts.costChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Con Ridondanza', 'Senza Ridondanza'],
                datasets: [{
                    label: 'Costo (operazioni/mese)',
                    data: [redundancyCost, nonRedundancyCost],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)'
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Confronto Costi di Gestione'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Operazioni/Mese'
                        }
                    }
                }
            }
        });
    }

    createOperationsChart() {
        const ctx = document.getElementById('operationsChart');
        if (!ctx) return;

        const ops = this.currentData.operations_analysis || {};

        if (this.charts.operationsChart) {
            this.charts.operationsChart.destroy();
        }

        this.charts.operationsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Aggiungi Progetto', 'Visualizza Tutti', 'Conta Progetti'],
                datasets: [{
                    data: [
                        ops.add_project_frequency || 1,
                        ops.view_all_frequency || 1,
                        ops.count_projects_frequency || 3
                    ],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(147, 51, 234, 0.8)'
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(147, 51, 234, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribuzione Frequenze Operazioni'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    async loadRecommendations() {
        try {
            const response = await fetch(`${this.apiBaseUrl}?action=recommendations`);
            const result = await response.json();

            if (result.success) {
                this.displayRecommendations(result.data);
            }
        } catch (error) {
            // Silent error handling for recommendations loading
        }
    }

    displayRecommendations(recommendations) {
        const container = document.getElementById('recommendations-content');
        if (!container) return;

        const strategy = recommendations.strategy || 'Non determinata';
        const reasoning = recommendations.reasoning || 'Nessuna spiegazione disponibile';
        const savings = recommendations.estimated_savings || 0;

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-900 mb-2">Strategia Raccomandata</h4>
                    <p class="text-blue-800 text-lg font-medium">${strategy}</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-green-900 mb-2">Risparmio Stimato</h4>
                    <p class="text-green-800 text-lg font-medium">${savings.toFixed(2)} operazioni/mese</p>
                </div>
            </div>
            <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-900 mb-2">Motivazione</h4>
                <p class="text-gray-700">${reasoning}</p>
            </div>
        `;
    }

    async loadPerformanceImpact() {
        try {
            const response = await fetch(`${this.apiBaseUrl}?action=performance_impact`);
            const result = await response.json();

            if (result.success) {
                this.displayPerformanceImpact(result.data);
            }
        } catch (error) {
            // Silent error handling for performance impact loading
        }
    }

    displayPerformanceImpact(impact) {
        const container = document.getElementById('performance-content');
        if (!container) return;

        container.innerHTML = `
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-red-900 mb-2">Query Lente</h4>
                        <p class="text-red-800 text-xl font-bold">${impact.slow_queries || 0}</p>
                        <p class="text-red-600 text-sm">operazioni identificate</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-yellow-900 mb-2">Utilizzo Memoria</h4>
                        <p class="text-yellow-800 text-xl font-bold">${impact.memory_usage || 0}%</p>
                        <p class="text-yellow-600 text-sm">memoria utilizzata</p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-blue-900 mb-2">Tempo Risposta</h4>
                        <p class="text-blue-800 text-xl font-bold">${impact.response_time || 0}ms</p>
                        <p class="text-blue-600 text-sm">tempo medio</p>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Dettagli Impatto</h4>
                    <ul class="space-y-2 text-gray-700">
                        ${(impact.details || []).map(detail => `<li>• ${detail}</li>`).join('')}
                    </ul>
                </div>
            </div>
        `;
    }

    async testConsistency() {
        const button = document.getElementById('test-consistency-btn');
        const originalText = button.textContent;

        try {
            button.textContent = 'Testing...';
            button.disabled = true;

            const response = await fetch(this.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'test_consistency' })
            });

            const result = await response.json();

            if (result.success) {
                this.displayConsistencyResults(result.data, 'test');
            } else {
                throw new Error(result.message || 'Test failed');
            }
        } catch (error) {
            // Silent error handling for consistency testing
            this.showError('Errore durante il test di consistenza');
        } finally {
            button.textContent = originalText;
            button.disabled = false;
        }
    }

    async fixInconsistencies() {
        const button = document.getElementById('fix-inconsistencies-btn');
        const originalText = button.textContent;

        try {
            button.textContent = 'Fixing...';
            button.disabled = true;

            const response = await fetch(this.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'fix_inconsistencies' })
            });

            const result = await response.json();

            if (result.success) {
                this.displayConsistencyResults(result.data, 'fix');
            } else {
                throw new Error(result.message || 'Fix failed');
            }
        } catch (error) {
            // Silent error handling for fixing inconsistencies
            this.showError('Errore durante la correzione delle inconsistenze');
        } finally {
            button.textContent = originalText;
            button.disabled = false;
        }
    }

    displayConsistencyResults(results, type) {
        const container = document.getElementById('consistency-results');
        if (!container) return;

        const title = type === 'test' ? 'Risultati Test Consistenza' : 'Risultati Correzione';
        const bgColor = type === 'test' ? 'bg-blue-50' : 'bg-green-50';
        const textColor = type === 'test' ? 'text-blue-900' : 'text-green-900';

        container.innerHTML = `
            <div class="${bgColor} p-4 rounded-lg">
                <h4 class="font-semibold ${textColor} mb-3">${title}</h4>
                <div class="space-y-2">
                    <p class="${textColor}">Inconsistenze trovate: ${results.inconsistencies_found || 0}</p>
                    ${type === 'fix' ? `<p class="${textColor}">Inconsistenze corrette: ${results.inconsistencies_fixed || 0}</p>` : ''}
                    <p class="${textColor}">Tempo esecuzione: ${results.execution_time || 0}ms</p>
                </div>
                ${results.details && results.details.length > 0 ? `
                    <div class="mt-3">
                        <h5 class="font-medium ${textColor} mb-2">Dettagli:</h5>
                        <ul class="space-y-1 text-sm ${textColor}">
                            ${results.details.map(detail => `<li>• ${detail}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
            </div>
        `;
    }

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            if (button.dataset.tab === tabName) {
                button.classList.add('active', 'border-blue-500', 'text-blue-600');
                button.classList.remove('border-transparent', 'text-gray-500');
            } else {
                button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            }
        });

        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        const activeTab = document.getElementById(`${tabName}-tab`);
        if (activeTab) {
            activeTab.classList.remove('hidden');

            // Load data for specific tabs
            if (tabName === 'performance') {
                this.loadPerformanceImpact();
            }
        }
    }

    showError(message) {
        // Simple error display - could be enhanced with a proper notification system
        const errorDiv = document.createElement('div');
        errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
        errorDiv.textContent = message;

        const content = document.getElementById('content') || document.getElementById('loading');
        content.insertBefore(errorDiv, content.firstChild);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new VolumeAnalysisController();
});
