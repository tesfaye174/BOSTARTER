<?php
session_start();
require_once __DIR__ . "/../backend/config/database.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}
$user_id = $_SESSION["user_id"];
$nickname = $_SESSION["nickname"];
$tipo_utente = $_SESSION["tipo_utente"];
$stats = ["progetti_creati" => 0, "fondi_raccolti" => 0, "finanziamenti_fatti" => 0, "totale_investito" => 0];
$progetti = [];
$finanziamenti = [];
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    if ($tipo_utente === "creatore") {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM progetti WHERE creatore_id = ?");
        $stmt->execute([$user_id]);
        $stats["progetti_creati"] = $stmt->fetch()["count"] ?? 0;
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(f.importo), 0) as totale 
            FROM progetti p 
            JOIN finanziamenti f ON p.id = f.progetto_id 
            WHERE p.creatore_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats["fondi_raccolti"] = $stmt->fetch()["totale"] ?? 0;
        $stmt = $conn->prepare("
            SELECT p.*, COALESCE(SUM(f.importo), 0) as totale_raccolto
            FROM progetti p 
            LEFT JOIN finanziamenti f ON p.id = f.progetto_id 
            WHERE p.creatore_id = ? 
            GROUP BY p.id 
            ORDER BY p.data_inserimento DESC
        ");
        $stmt->execute([$user_id]);
        $progetti = $stmt->fetchAll();
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM finanziamenti WHERE utente_id = ?");
        $stmt->execute([$user_id]);
        $stats["finanziamenti_fatti"] = $stmt->fetch()["count"] ?? 0;
        $stmt = $conn->prepare("SELECT COALESCE(SUM(importo), 0) as totale FROM finanziamenti WHERE utente_id = ?");
        $stmt->execute([$user_id]);
        $stats["totale_investito"] = $stmt->fetch()["totale"] ?? 0;
        $stmt = $conn->prepare("
            SELECT p.nome, p.descrizione, f.importo, f.data_finanziamento
            FROM progetti p 
            JOIN finanziamenti f ON p.id = f.progetto_id 
            WHERE f.utente_id = ? 
            ORDER BY f.data_finanziamento DESC
        ");
        $stmt->execute([$user_id]);
        $finanziamenti = $stmt->fetchAll();
    }
} catch(Exception $e) {
    $error = "Errore nel caricamento dei dati";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BOSTARTER</title>
    <link href="https:
    <link href="https:
    <style>
        body { 
            padding-top: 76px; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { margin: 0; font-size: 2.5rem; font-weight: bold; }
        .stat-card p { margin: 0; opacity: 0.9; font-size: 1.1rem; }
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
        }
        .project-card {
            transition: transform 0.3s ease;
            border: 1px solid #e9ecef;
        }
        .project-card:hover { transform: translateY(-3px); }
        .navbar { backdrop-filter: blur(10px); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-white">Ciao, <?= htmlspecialchars($nickname) ?>!</span>
                <a class="nav-link text-white" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link text-white" href="index.php">
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <a class="nav-link text-white" href="auth/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card p-4">
                    <h1 class="mb-2">
                        <i class="fas fa-tachometer-alt text-primary me-3"></i>Dashboard
                        <small class="text-muted fs-5">(<?= ucfirst($tipo_utente) ?>)</small>
                    </h1>
                    <p class="text-muted mb-0">Benvenuto nella tua dashboard personale</p>
                </div>
            </div>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <!-- Statistiche -->
        <div class="row mb-4">
            <?php if ($tipo_utente === "creatore"): ?>
                <div class="col-md-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-project-diagram fa-2x mb-3"></i>
                        <h3><?= number_format($stats["progetti_creati"]) ?></h3>
                        <p>Progetti Creati</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-euro-sign fa-2x mb-3"></i>
                        <h3>€<?= number_format($stats["fondi_raccolti"]) ?></h3>
                        <p>Fondi Raccolti</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-heart fa-2x mb-3"></i>
                        <h3><?= number_format($stats["finanziamenti_fatti"]) ?></h3>
                        <p>Progetti Finanziati</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card text-center">
                        <i class="fas fa-chart-line fa-2x mb-3"></i>
                        <h3>€<?= number_format($stats["totale_investito"]) ?></h3>
                        <p>Totale Investito</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- Contenuto principale -->
        <div class="row">
            <div class="col-12">
                <?php if ($tipo_utente === "creatore"): ?>
                    <!-- Sezione Creatore -->
                    <div class="dashboard-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="mb-0">
                                <i class="fas fa-folder text-primary me-2"></i>I Tuoi Progetti
                            </h2>
                            <a href="create-project.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Nuovo Progetto
                            </a>
                        </div>
                        <?php if (!empty($progetti)): ?>
                            <div class="row">
                                <?php foreach ($progetti as $progetto): 
                                    $progress = $progetto["budget_richiesto"] > 0 
                                        ? round(($progetto["totale_raccolto"] / $progetto["budget_richiesto"]) * 100, 1) 
                                        : 0;
                                    $days_left = max(0, floor((strtotime($progetto["data_scadenza"]) - time()) / (60 * 60 * 24)));
                                ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="project-card card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary">
                                                    <?= htmlspecialchars($progetto["nome"]) ?>
                                                </h5>
                                                <p class="card-text text-muted">
                                                    <?= htmlspecialchars(substr($progetto["descrizione"], 0, 100)) ?>...
                                                </p>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between small mb-2">
                                                        <span class="fw-bold">Raccolti: €<?= number_format($progetto["totale_raccolto"]) ?></span>
                                                        <span>Obiettivo: €<?= number_format($progetto["budget_richiesto"]) ?></span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-success" style="width: <?= min($progress, 100) ?>%"></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between mt-2">
                                                        <span class="badge bg-<?= $progetto["stato"] === "aperto" ? "success" : "secondary" ?>">
                                                            <?= ucfirst($progetto["stato"]) ?>
                                                        </span>
                                                        <small class="text-muted"><?= $days_left ?> giorni rimasti</small>
                                                    </div>
                                                </div>
                                                <a href="project.php?id=<?= $progetto["id"] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>Visualizza Dettagli
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-folder-open text-muted" style="font-size: 4rem;"></i>
                                </div>
                                <h4 class="text-muted mb-3">Nessun progetto ancora</h4>
                                <p class="text-muted mb-4">Sei pronto a lanciare il tuo primo progetto?</p>
                                <a href="create-project.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-rocket me-2"></i>Crea il Tuo Primo Progetto
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Sezione Utente Standard -->
                    <div class="dashboard-card p-4">
                        <h2 class="mb-4">
                            <i class="fas fa-heart text-danger me-2"></i>I Tuoi Finanziamenti
                        </h2>
                        <?php if (!empty($finanziamenti)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="fas fa-project-diagram me-1"></i>Progetto</th>
                                            <th><i class="fas fa-euro-sign me-1"></i>Importo</th>
                                            <th><i class="fas fa-calendar me-1"></i>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($finanziamenti as $finanziamento): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($finanziamento["nome"]) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($finanziamento["descrizione"]) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success fs-6">
                                                        €<?= number_format($finanziamento["importo"]) ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted">
                                                    <?= date("d/m/Y", strtotime($finanziamento["data_finanziamento"])) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-heart text-muted" style="font-size: 4rem;"></i>
                                </div>
                                <h4 class="text-muted mb-3">Nessun finanziamento ancora</h4>
                                <p class="text-muted mb-4">Non hai ancora finanziato nessun progetto.</p>
                                <a href="index.php#progetti" class="btn btn-primary btn-lg">
                                    <i class="fas fa-search me-2"></i>Esplora i Progetti
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https:
</body>
</html>
