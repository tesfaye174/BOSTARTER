<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisi del Volume - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analysis-card {
            transition: transform 0.2s;
        }
        .analysis-card:hover {
            transform: translateY(-2px);
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #3B82F6;
        }
        .loading-spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #3B82F6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">BOSTARTER</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Home</a>
                <a class="nav-link" href="top_creators.php">Top Creators</a>
                <a class="nav-link" href="close_to_goal.php">Near Goal</a>
                <a class="nav-link active" href="#">Volume Analysis</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Analisi del Volume</h1>
            <p class="text-gray-600">Valutazione della ridondanza per il campo #nr_progetti secondo le specifiche del corso</p>
        </div>

        <!-- Loading State -->
        <div id="loading" class="text-center py-16">
            <div class="loading-spinner mx-auto mb-4"></div>
            <p class="text-gray-600">Caricamento analisi in corso...</p>
        </div>

        <!-- Main Content -->
        <div id="content" class="hidden">
            <!-- Quick Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="analysis-card bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Costo Ridondanza</h3>
                    <div class="metric-value" id="redundancy-cost">--</div>
                    <p class="text-sm text-gray-600">operazioni/mese</p>
                </div>
                
                <div class="analysis-card bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Costo Non-Ridondanza</h3>
                    <div class="metric-value" id="non-redundancy-cost">--</div>
                    <p class="text-sm text-gray-600">operazioni/mese</p>
                </div>
                
                <div class="analysis-card bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Raccomandazione</h3>
                    <div class="metric-value text-sm" id="recommendation">--</div>
                    <p class="text-sm text-gray-600">strategia ottimale</p>
                </div>
                
                <div class="analysis-card bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Progetti Totali</h3>
                    <div class="metric-value" id="total-projects">--</div>
                    <p class="text-sm text-gray-600">nel sistema</p>
                </div>
            </div>

            <!-- Analysis Tabs -->
            <div class="bg-white rounded-lg shadow-lg">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6">
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm active" data-tab="overview">
                            Panoramica
                        </button>
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm" data-tab="operations">
                            Analisi Operazioni
                        </button>
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm" data-tab="performance">
                            Impatto Performance
                        </button>
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm" data-tab="consistency">
                            Test Consistenza
                        </button>
                    </nav>
                </div>

                <!-- Overview Tab -->
                <div id="overview-tab" class="tab-content p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Cost Comparison Chart -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Confronto Costi</h3>
                            <canvas id="costChart" width="400" height="200"></canvas>
                        </div>
                        
                        <!-- Parameters -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Parametri Utilizzati</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="font-medium">Coefficiente wI:</span>
                                    <span id="param-wi">1</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">Coefficiente wB:</span>
                                    <span id="param-wb">0.5</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">Coefficiente a:</span>
                                    <span id="param-a">2</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">Numero Progetti:</span>
                                    <span id="param-projects">10</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">Finanziamenti per Progetto:</span>
                                    <span id="param-fundings">3</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">Numero Utenti:</span>
                                    <span id="param-users">5</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operations Tab -->
                <div id="operations-tab" class="tab-content p-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">Analisi delle Operazioni</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">Aggiungi Progetto</h4>
                            <p class="text-2xl font-bold text-blue-600" id="op-add-freq">1</p>
                            <p class="text-sm text-gray-600">volte/mese</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">Visualizza Tutti</h4>
                            <p class="text-2xl font-bold text-green-600" id="op-view-freq">1</p>
                            <p class="text-sm text-gray-600">volte/mese</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">Conta Progetti</h4>
                            <p class="text-2xl font-bold text-purple-600" id="op-count-freq">3</p>
                            <p class="text-sm text-gray-600">volte/mese</p>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <canvas id="operationsChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Performance Tab -->
                <div id="performance-tab" class="tab-content p-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">Impatto sulle Performance</h3>
                    <div id="performance-content">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>

                <!-- Consistency Tab -->
                <div id="consistency-tab" class="tab-content p-6 hidden">
                    <h3 class="text-lg font-semibold mb-4">Test di Consistenza</h3>
                    <div class="space-y-4">
                        <button id="test-consistency-btn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Esegui Test Consistenza
                        </button>
                        <button id="fix-inconsistencies-btn" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Correggi Inconsistenze
                        </button>
                    </div>
                    <div id="consistency-results" class="mt-6">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>
            </div>

            <!-- Recommendations Section -->
            <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Raccomandazioni</h3>
                <div id="recommendations-content">
                    <!-- Will be populated dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script src="../js/volume-analysis.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/bootstrap.bundle.min.js"></script>
</body>
</html>
