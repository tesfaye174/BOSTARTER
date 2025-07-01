<?php
session_start();

// Gestisci messaggio di logout
$logout_message = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $logout_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>Logout effettuato con successo! Grazie per aver utilizzato BOSTARTER.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

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
                   p.budget_richiesto, p.budget_raccolto as total_funding, p.data_scadenza
            FROM progetti p 
            WHERE p.stato = 'aperto' 
            ORDER BY p.budget_raccolto DESC 
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/app.css">
    <link rel="stylesheet" href="css/custom.css">
    <!-- Favicon -->
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="images/icon-144x144.png">
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
                <a class="nav-link" href="dash.php">Dashboard</a>
                <a class="nav-link" href="auth/exit.php">Logout</a>
                <?php else: ?>
                <a class="nav-link" href="auth/login.php">Login</a>
                <a class="nav-link" href="auth/signup.php">Registrati</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Trasforma le tue idee in realt?</h1>
            <p class="lead mb-4">BOSTARTER ? la piattaforma italiana per il crowdfunding di progetti innovativi.</p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="#progetti" class="btn btn-bostarter-light btn-lg">Esplora Progetti</a>
                <a href="auth/signup.php" class="btn btn-outline-light btn-lg">Inizia Ora</a>
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
                            <small class="text-muted">?<?= number_format($project["total_funding"]) ?> /
                                ?<?= number_format($project["budget_richiesto"]) ?> - <?= $days_left ?> giorni
                                rimasti</small>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>