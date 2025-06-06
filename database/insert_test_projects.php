<?php
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get admin user ID
    $stmt = $db->prepare("SELECT id FROM utenti WHERE nickname = 'admin'");
    $stmt->execute();
    $adminId = $stmt->fetchColumn();

    if (!$adminId) {
        throw new Exception("Admin user not found. Please create admin user first.");
    }

    $testProjects = [
        [
            'nome' => 'EcoGarden - Giardino Verticale Smart',
            'descrizione' => 'Sistema di giardinaggio verticale intelligente per spazi urbani',
            'tipo_progetto' => 'hardware',
            'budget_richiesto' => 15000,
            'data_limite' => date('Y-m-d', strtotime('+45 days')),
            'creatore_id' => $adminId,
            'stato' => 'aperto'
        ],
        [
            'nome' => 'ArtBook Collection - Software Gestionale',
            'descrizione' => 'Software gestionale per collezioni di fumetti digitali',
            'tipo_progetto' => 'software',
            'budget_richiesto' => 8000,
            'data_limite' => date('Y-m-d', strtotime('+60 days')),
            'creatore_id' => $adminId,
            'stato' => 'aperto'
        ],
        [
            'nome' => 'FoodTech Innovation - App Cucina Sostenibile',
            'descrizione' => 'App intelligente per una cucina a zero sprechi',
            'tipo_progetto' => 'software',
            'budget_richiesto' => 12000,
            'data_limite' => date('Y-m-d', strtotime('+30 days')),
            'creatore_id' => $adminId,
            'stato' => 'aperto'
        ],
        [
            'nome' => 'SmartHome Controller',
            'descrizione' => 'Controller hardware per la domotica open source',
            'tipo_progetto' => 'hardware',
            'budget_richiesto' => 20000,
            'data_limite' => date('Y-m-d', strtotime('+90 days')),
            'creatore_id' => $adminId,
            'stato' => 'aperto'
        ],
        [
            'nome' => 'DevBoard Pro',
            'descrizione' => 'Scheda di sviluppo hardware per progetti IoT',
            'tipo_progetto' => 'hardware',
            'budget_richiesto' => 25000,
            'data_limite' => date('Y-m-d', strtotime('+75 days')),
            'creatore_id' => $adminId,
            'stato' => 'aperto'
        ]
    ];

    $stmt = $db->prepare("INSERT INTO progetti (nome, descrizione, tipo_progetto, budget_richiesto, data_limite, creatore_id, stato) 
                         VALUES (:nome, :descrizione, :tipo_progetto, :budget_richiesto, :data_limite, :creatore_id, :stato)");

    foreach ($testProjects as $project) {
        $stmt->execute($project);
    }

    echo "✓ Progetti di test inseriti con successo\n";
    
} catch (PDOException $e) {
    echo "✗ Errore nell'inserimento dei progetti: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ " . $e->getMessage() . "\n";
}
