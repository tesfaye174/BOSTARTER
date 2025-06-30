<?php
session_start();
$stats = [
    "total_projects" => 12,
    "total_funding" => 45000,
    "total_backers" => 284,
    "success_rate" => 73.5,
    "top_creatori" => [],
    "progetti_completamento" => [],
    "top_finanziatori" => []
];
$featured_projects = [];
$is_logged_in = isset($_SESSION["user_id"]);
$username = $_SESSION["nickname"] ?? "";
try {
    require_once __DIR__ . "/../backend/config/database.php";
    $db = Database::getInstance();
    $conn = $db->getConnection();
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM progetti");
        $stats["total_projects"] = $result->fetch()["count"] ?? 12;
    } catch(Exception $e) {}
    try {
        $result = $conn->query("
            SELECT p.id, p.nome, p.descrizione, p.tipo_progetto as tipo, 
                   p.budget_richiesto, p.data_scadenza,
                   COALESCE(SUM(f.importo), 0) as total_funding 
            FROM progetti p 
            LEFT JOIN finanziamenti f ON p.id = f.progetto_id 
            WHERE p.stato = \"aperto\" 
            GROUP BY p.id 
            ORDER BY total_funding DESC 
            LIMIT 6
        ");
        $featured_projects = $result->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        $featured_projects = [];
    }
} catch(Exception $e) {
    error_log("Database error: " . $e->getMessage());
}
if (empty($featured_projects)) {
    $featured_projects = [
        [
            "id" => 1,
            "nome" => "SmartHome AI Assistant",
            "descrizione" => "Un assistente AI per la casa che apprende dalle tue abitudini.",
            "tipo" => "hardware",
            "total_funding" => 8500,
            "budget_richiesto" => 15000,
            "data_scadenza" => date("Y-m-d", strtotime("+25 days"))
        ],
        [
            "id" => 2,
            "nome" => "EcoTrack App",
            "descrizione" => "App mobile per tracciare la tua impronta ecologica.",
            "tipo" => "software",
            "total_funding" => 12000,
            "budget_richiesto" => 20000,
            "data_scadenza" => date("Y-m-d", strtotime("+18 days"))
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSTARTER - Crowdfunding Italiano</title>
    <link href="https:
    <link href="https:
    <style>
        body { padding-top: 76px; }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5rem 0;
        }
        .project-card { transition: transform 0.3s ease; }
        .project-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <div class="navbar-nav ms-auto">
                <?php if ($is_logged_in): ?>
                    <span class="nav-link">Ciao, <?= htmlspecialchars($username) ?></span>
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                    <a class="nav-link" href="auth/logout.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="auth/login.php">Login</a>
                    <a class="nav-link" href="auth/register.php">Registrati</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Trasforma le tue idee in realtà</h1>
            <p class="lead mb-4">BOSTARTER è la piattaforma italiana per il crowdfunding di progetti innovativi.</p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="#progetti" class="btn btn-bostarter-light btn-lg">Esplora Progetti</a>
                <a href="auth/register.php" class="btn btn-outline-light btn-lg">Inizia Ora</a>
            </div>
        </div>
    </section>
    <section id="progetti" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Progetti in Evidenza</h2>
            <div class="row g-4">
                <?php foreach ($featured_projects as $project): 
                    $progress = round(($project["total_funding"] / $project["budget_richiesto"]) * 100, 1);
                    $days_left = max(0, floor((strtotime($project["data_scadenza"]) - time()) / (60 * 60 * 24)));
                ?>
                    <div class="col-lg-6">
                        <div class="card h-100 shadow-sm project-card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($project["nome"]) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($project["descrizione"]) ?></p>
                                <div class="progress mb-2">
                                    <div class="progress-bar" style="width: <?= min($progress, 100) ?>%">
                                        <?= $progress ?>%
                                    </div>
                                </div>
                                <small class="text-muted">€<?= number_format($project["total_funding"]) ?> / €<?= number_format($project["budget_richiesto"]) ?> - <?= $days_left ?> giorni rimasti</small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2025 BOSTARTER. Tutti i diritti riservati.</p>
        </div>
    </footer>
    <script src="https:
</body>
</html>
