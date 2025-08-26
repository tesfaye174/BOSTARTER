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
    <title>BOSTARTER - La tua piattaforma di crowdfunding</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/app.css">
    <link rel="stylesheet" href="css/custom.css">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: rgba(0,0,0,0.9);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#progetti">Progetti</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#come-funziona">Come Funziona</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="statistiche.php">Statistiche</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($username) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dash.php"><i
                                        class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="new.php"><i class="fas fa-plus me-2"></i>Nuovo
                                    Progetto</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="auth/exit.php"><i
                                        class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ms-2" href="auth/signup.php">
                            <i class="fas fa-user-plus me-1"></i>Registrati
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Logout Message -->
    <?php if ($logout_message): ?>
    <div class="fixed-top" style="top: 70px; z-index: 1050;">
        <div class="container">
            <?= $logout_message ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-gradient text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold mb-4">
                        Trasforma le tue <span class="text-warning">idee innovative</span> in realtà
                    </h1>
                    <p class="lead fs-4 mb-5">
                        BOSTARTER è la piattaforma italiana leader per il crowdfunding di progetti tecnologici.
                        Dai vita alle tue idee o supporta i progetti del futuro.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#progetti" class="btn btn-warning btn-lg px-4 py-3">
                            <i class="fas fa-search me-2"></i>Esplora Progetti
                        </a>
                        <?php if (!$is_logged_in): ?>
                        <a href="auth/signup.php" class="btn btn-outline-light btn-lg px-4 py-3">
                            <i class="fas fa-rocket me-2"></i>Inizia Ora
                        </a>
                        <?php else: ?>
                        <a href="new.php" class="btn btn-outline-light btn-lg px-4 py-3">
                            <i class="fas fa-plus me-2"></i>Crea Progetto
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stats-card text-center p-4 rounded-3">
                                <h3 class="fw-bold"><?= number_format($stats['total_projects']) ?></h3>
                                <p class="mb-0">Progetti Attivi</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card text-center p-4 rounded-3">
                                <h3 class="fw-bold">€<?= number_format($stats['total_funding']) ?></h3>
                                <p class="mb-0">Finanziamenti</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card text-center p-4 rounded-3">
                                <h3 class="fw-bold"><?= number_format($stats['total_backers']) ?></h3>
                                <p class="mb-0">Sostenitori</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card text-center p-4 rounded-3">
                                <h3 class="fw-bold"><?= $stats['success_rate'] ?>%</h3>
                                <p class="mb-0">Successo</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Progetti in Evidenza -->
    <section id="progetti" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Progetti in Evidenza</h2>
                <p class="lead text-muted">Scopri i progetti più innovativi che stanno cambiando il futuro</p>
            </div>

            <div class="row g-4" id="projects-container">
                <?php foreach ($featured_projects as $project): 
                    $progress = round(($project["total_funding"] / $project["budget_richiesto"]) * 100, 1);
                    $days_left = max(0, floor((strtotime($project["data_scadenza"]) - time()) / (60 * 60 * 24)));
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card project-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($project["tipo"]) ?></span>
                                <span class="text-muted small"><?= $days_left ?> giorni rimasti</span>
                            </div>

                            <h5 class="card-title fw-bold mb-3"><?= htmlspecialchars($project["nome"]) ?></h5>
                            <p class="card-text text-muted mb-4">
                                <?= htmlspecialchars(substr($project["descrizione"], 0, 120)) ?>...</p>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="fw-semibold">€<?= number_format($project["total_funding"]) ?></span>
                                    <span class="text-muted"><?= $progress ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-gradient" style="width: <?= min($progress, 100) ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">Obiettivo:
                                    €<?= number_format($project["budget_richiesto"]) ?></small>
                            </div>

                            <a href="view.php?id=<?= $project["id"] ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-eye me-2"></i>Scopri di più
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
                <a href="dash.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-th-large me-2"></i>Vedi Tutti i Progetti
                </a>
            </div>
        </div>
    </section>

    <!-- Come Funziona -->
    <section id="come-funziona" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Come Funziona BOSTARTER</h2>
                <p class="lead text-muted">Tre semplici passi per trasformare le tue idee in realtà</p>
            </div>

            <div class="row g-5">
                <div class="col-lg-4 text-center">
                    <div class="feature-icon">
                        <i class="fas fa-lightbulb text-white fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-3">1. Condividi la tua Idea</h4>
                    <p class="text-muted">Crea il tuo progetto con una descrizione dettagliata, immagini e video.
                        Definisci
                        l'obiettivo di finanziamento e le reward per i sostenitori.</p>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="feature-icon">
                        <i class="fas fa-users text-white fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-3">2. Raccogli Sostenitori</h4>
                    <p class="text-muted">Promuovi il tuo progetto e attira sostenitori che credono nella tua visione.
                        Ogni
                        contributo ti avvicina al tuo obiettivo.</p>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="feature-icon">
                        <i class="fas fa-rocket text-white fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-3">3. Realizza il Progetto</h4>
                    <p class="text-muted">Una volta raggiunto l'obiettivo, ricevi i fondi e inizia a sviluppare il tuo
                        progetto. Mantieni aggiornati i tuoi sostenitori sui progressi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section text-white py-5">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Pronto a iniziare?</h2>
            <p class="lead mb-5">Unisciti a migliaia di innovatori che hanno già trasformato le loro idee in successi.
            </p>

            <?php if (!$is_logged_in): ?>
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <a href="auth/signup.php" class="btn btn-light btn-lg px-5 py-3">
                    <i class="fas fa-user-plus me-2"></i>Registrati Gratis
                </a>
                <a href="auth/login.php" class="btn btn-outline-light btn-lg px-5 py-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Accedi
                </a>
            </div>
            <?php else: ?>
            <a href="new.php" class="btn btn-light btn-lg px-5 py-3">
                <i class="fas fa-plus me-2"></i>Crea il tuo Primo Progetto
            </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-rocket me-2"></i>BOSTARTER
                    </h5>
                    <p class="text-muted">La piattaforma italiana leader per il crowdfunding di progetti innovativi.
                        Trasforma le tue idee in realtà.</p>
                </div>
                <div class="col-lg-2 col-6 mb-4">
                    <h6 class="fw-bold mb-3">Esplora</h6>
                    <ul class="list-unstyled">
                        <li><a href="dash.php" class="text-muted text-decoration-none">Progetti</a></li>
                        <li><a href="statistiche.php" class="text-muted text-decoration-none">Statistiche</a></li>
                        <li><a href="#come-funziona" class="text-muted text-decoration-none">Come Funziona</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6 mb-4">
                    <h6 class="fw-bold mb-3">Account</h6>
                    <ul class="list-unstyled">
                        <?php if (!$is_logged_in): ?>
                        <li><a href="auth/login.php" class="text-muted text-decoration-none">Login</a></li>
                        <li><a href="auth/signup.php" class="text-muted text-decoration-none">Registrati</a></li>
                        <?php else: ?>
                        <li><a href="dash.php" class="text-muted text-decoration-none">Dashboard</a></li>
                        <li><a href="new.php" class="text-muted text-decoration-none">Nuovo Progetto</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6 class="fw-bold mb-3">Connettiti</h6>
                    <p class="text-muted small">Seguici sui social media per rimanere aggiornato sui progetti più
                        innovativi.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-muted"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0 text-muted">&copy; 2025 BOSTARTER. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="js/core.js"></script>
    <script src="js/home.js"></script>

    <script>
    // Auto-dismiss alerts dopo 5 secondi
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = bootstrap.Alert.getInstance(alert);
                if (bsAlert) bsAlert.close();
            });
        }, 5000);
    });
    </script>
</body>

</html>