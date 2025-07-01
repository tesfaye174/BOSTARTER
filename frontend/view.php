<?php
session_start();
require_once __DIR__ . "/../backend/config/database.php";

// Ottieni l'ID del progetto dalla query string
$project_id = (int)($_GET["id"] ?? 0);
$error = "";
$project = null;
$finanziamenti = [];

// Carica i dati del progetto se l'ID è valido
if ($project_id > 0) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Query per ottenere i dati del progetto con il nickname del creatore
        $stmt = $conn->prepare("
            SELECT p.*, u.nickname as creatore_nickname,
                   p.budget_raccolto as totale_raccolto,
                   0 as numero_sostenitori
            FROM progetti p 
            LEFT JOIN utenti u ON p.creatore_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        
        if ($project) {
            // La tabella finanziamenti non esiste, quindi saltiamo questa query
            $finanziamenti = [];
        }
    } catch(Exception $e) {
        $error = "Errore nel caricamento del progetto";
    }
}

// Se il progetto non è stato trovato, mostra errore
if (!$project) {
    $error = "Progetto non trovato";
}

// Variabili per la vista
$is_logged_in = isset($_SESSION["user_id"]);
$progress = $project ? min(100, ($project["totale_raccolto"] / $project["budget_richiesto"]) * 100) : 0;
$days_left = $project ? max(0, floor((strtotime($project["data_limite"]) - time()) / (60 * 60 * 24))) : 0;
?>
<!DOCTYPE html>
<html lang="it" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $project ? htmlspecialchars($project["nome"]) : "Progetto" ?> - BOSTARTER</title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/bootstrap.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="images/icon-144x144.png">
</head>
<body>
    <!-- Navbar Bootstrap -->
    <nav class="navbar navbar-expand-lg navbar-bostarter fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-gradient-bostarter" href="index.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#progetti">Progetti</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <?php if ($is_logged_in): ?>
                        <a class="nav-link" href="dash.php">Dashboard</a>
                        <a class="nav-link" href="auth/exit.php">Logout</a>
                    <?php else: ?>
                        <a class="nav-link" href="auth/login.php">Accedi</a>
                        <a class="btn btn-bostarter-primary ms-2" href="auth/signup.php">Registrati</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main style="padding-top: 100px;">
        <div class="container py-4">
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fade-up">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php else: ?>
                <!-- Header Progetto -->
                <div class="row mb-4">
                    <div class="col-12">
                        <nav aria-label="breadcrumb" class="animate-fade-left">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="index.php#progetti">Progetti</a></li>
                                <li class="breadcrumb-item active"><?= htmlspecialchars($project["nome"]) ?></li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="row">
                    <!-- Contenuto principale -->
                    <div class="col-lg-8">
                        <div class="card-bostarter mb-4 animate-fade-up">
                            <img src="images/project-placeholder.jpg" class="card-img-top" alt="<?= htmlspecialchars($project["nome"]) ?>" style="height: 400px; object-fit: cover;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h1 class="text-gradient-bostarter"><?= htmlspecialchars($project["nome"]) ?></h1>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-user me-2"></i>
                                            di <strong><?= htmlspecialchars($project["creatore_nickname"]) ?></strong>
                                        </p>
                                    </div>
                                    <span class="badge badge-bostarter">
                                        <i class="fas fa-<?= $project["tipo_progetto"] === "hardware" ? "microchip" : "code" ?> me-1"></i>
                                        <?= ucfirst($project["tipo_progetto"]) ?>
                                    </span>
                                </div>
                                <div class="mb-4">
                                    <h3>Descrizione del progetto</h3>
                                    <p class="lead"><?= nl2br(htmlspecialchars($project["descrizione"])) ?></p>
                                </div>
                                <?php if (!empty($project["specifiche_tecniche"])): ?>
                                <div class="mb-4">
                                    <h3>Specifiche tecniche</h3>
                                    <div class="bg-light p-3 rounded">
                                        <?= nl2br(htmlspecialchars($project["specifiche_tecniche"])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <!-- Timeline -->
                                <div class="mb-4">
                                    <h3>Timeline del progetto</h3>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-calendar-plus text-success me-3"></i>
                                                <div>
                                                    <strong>Avvio:</strong><br>
                                                    <small class="text-muted"><?= date("d/m/Y", strtotime($project["data_inserimento"])) ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-flag-checkered text-warning me-3"></i>
                                                <div>
                                                    <strong>Scadenza:</strong><br>
                                                    <small class="text-muted"><?= date("d/m/Y", strtotime($project["data_scadenza"])) ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Sostenitori -->
                        <?php if (!empty($finanziamenti)): ?>
                        <div class="card-bostarter animate-fade-up">
                            <div class="card-body">
                                <h3 class="mb-4">
                                    <i class="fas fa-heart text-danger me-2"></i>
                                    Sostenitori recenti
                                </h3>
                                <div class="row">
                                    <?php foreach ($finanziamenti as $finanziamento): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($finanziamento["nickname"]) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        ?<?= number_format($finanziamento["importo"]) ?> - 
                                                        <?= date("d/m/Y", strtotime($finanziamento["data_finanziamento"])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <div class="card-bostarter sticky-top animate-fade-right" style="top: 120px;">
                            <div class="card-body">
                                <!-- Progress -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Progresso</span>
                                        <span class="fw-bold"><?= number_format($progress, 1) ?>%</span>
                                    </div>
                                    <div class="progress-bostarter">
                                        <div class="progress-bar" style="width: <?= $progress ?>%"></div>
                                    </div>
                                </div>
                                <!-- Stats -->
                                <div class="row text-center mb-4">
                                    <div class="col-6">
                                        <h3 class="text-gradient-bostarter mb-0">?<?= number_format($project["totale_raccolto"]) ?></h3>
                                        <small class="text-muted">Raccolti</small>
                                    </div>
                                    <div class="col-6">
                                        <h3 class="text-gradient-bostarter mb-0"><?= number_format($project["numero_sostenitori"]) ?></h3>
                                        <small class="text-muted">Sostenitori</small>
                                    </div>
                                </div>
                                <div class="row text-center mb-4">
                                    <div class="col-6">
                                        <h4 class="mb-0">?<?= number_format($project["budget_richiesto"]) ?></h4>
                                        <small class="text-muted">Obiettivo</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="mb-0"><?= $days_left ?></h4>
                                        <small class="text-muted">Giorni rimasti</small>
                                    </div>
                                </div>
                                <!-- CTA -->
                                <?php if ($project["stato"] === "aperto" && $days_left > 0): ?>
                                    <?php if ($is_logged_in): ?>
                                        <a href="finanzia.php?id=<?= $project["id"] ?>" class="btn btn-bostarter-primary w-100 mb-3">
                                            <i class="fas fa-heart me-2"></i>Finanzia questo progetto
                                        </a>
                                    <?php else: ?>
                                        <a href="auth/login.php" class="btn btn-bostarter-primary w-100 mb-3">
                                            <i class="fas fa-sign-in-alt me-2"></i>Accedi per finanziare
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning text-center">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Campagna conclusa
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Azioni progetto -->
                                <div class="d-grid gap-2 mb-3">
                                    <?php if ($is_logged_in): ?>
                                        <a href="commenti.php?progetto_id=<?= $project["id"] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-comments me-2"></i>Commenti
                                        </a>
                                        <?php if ($project["tipo"] === "software"): ?>
                                            <a href="candidature.php?progetto_id=<?= $project["id"] ?>" class="btn btn-outline-success">
                                                <i class="fas fa-users me-2"></i>Candidature
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-primary" onclick="shareProject()">
                                        <i class="fas fa-share-alt me-2"></i>Condividi progetto
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <!-- Modal Bootstrap per supporto -->
    <?php if ($is_logged_in && $project && $project["stato"] === "aperto"): ?>
    <div class="modal fade modal-bostarter" id="supportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-heart me-2"></i>
                        Sostieni <?= htmlspecialchars($project["nome"]) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="supportForm" method="POST" action="support-view.php">
                        <input type="hidden" name="project_id" value="<?= $project["id"] ?>">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Importo (?)</label>
                            <input type="number" class="form-control form-control-bostarter" id="amount" name="amount" min="10" max="10000" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Messaggio (opzionale)</label>
                            <textarea class="form-control form-control-bostarter" id="message" name="message" rows="3" placeholder="Scrivi un messaggio di supporto..."></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-bostarter-primary">
                                <i class="fas fa-heart me-2"></i>Conferma supporto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <!-- Bootstrap JS -->
    <script src="https:
    <script>
    function shareProject() {
        if (navigator.share) {
            navigator.share({
                title: "<?= htmlspecialchars($project["nome"] ?? "Progetto BOSTARTER") ?>",
                text: "Guarda questo progetto interessante su BOSTARTER!",
                url: window.location.href
            });
        } else {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert("URL copiato negli appunti!");
            });
        }
    }
    window.addEventListener("scroll", function() {
        const navbar = document.querySelector(".navbar-bostarter");
        if (window.scrollY > 100) {
            navbar.classList.add("scrolled");
        } else {
            navbar.classList.remove("scrolled");
        }
    });
    </script>
</body>
</html>

