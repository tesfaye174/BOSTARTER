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
        // Statistiche creatore
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM progetti WHERE creatore_id = ?");
        $stmt->execute([$user_id]);
        $stats["progetti_creati"] = $stmt->fetch()["count"] ?? 0;
        
        $stmt = $conn->prepare("SELECT COALESCE(SUM(budget_raccolto), 0) as totale FROM progetti WHERE creatore_id = ?");
        $stmt->execute([$user_id]);
        $stats["fondi_raccolti"] = $stmt->fetch()["totale"] ?? 0;
        
        // Progetti del creatore
        $stmt = $conn->prepare("
            SELECT * FROM progetti 
            WHERE creatore_id = ? 
            ORDER BY data_inserimento DESC
        ");
        $stmt->execute([$user_id]);
        $progetti = $stmt->fetchAll();
    } else {
        // Statistiche investitore - per ora zero dato che non ci sono finanziamenti
        $stats["finanziamenti_fatti"] = 0;
        $stats["totale_investito"] = 0;
        $finanziamenti = [];
    }
} catch(Exception $e) {
    $error = "Errore nel caricamento dei dati: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="home.php">Home</a>
                <a class="nav-link" href="view.php">Progetti</a>
                <?php if ($tipo_utente === "creatore"): ?>
                    <a class="nav-link" href="new.php">Nuovo Progetto</a>
                <?php endif; ?>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($nickname) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="auth/exit.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4" style="margin-top: 80px;">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-6">Benvenuto, <?= htmlspecialchars($nickname) ?>!</h1>
                <p class="text-muted">Dashboard <?= $tipo_utente === "creatore" ? "Creatore" : "Investitore" ?></p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Statistiche -->
        <div class="row mb-4">
            <?php if ($tipo_utente === "creatore"): ?>
                <div class="col-md-6">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-project-diagram me-2"></i>Progetti Creati</h5>
                            <h2><?= $stats["progetti_creati"] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-euro-sign me-2"></i>Fondi Raccolti</h5>
                            <h2>€<?= number_format($stats["fondi_raccolti"], 2) ?></h2>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-6">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-hand-holding-usd me-2"></i>Finanziamenti</h5>
                            <h2><?= $stats["finanziamenti_fatti"] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-coins me-2"></i>Totale Investito</h5>
                            <h2>€<?= number_format($stats["totale_investito"], 2) ?></h2>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contenuto principale -->
        <div class="row">
            <div class="col-12">
                <?php if ($tipo_utente === "creatore"): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">I Tuoi Progetti</h5>
                            <a href="new.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>Nuovo Progetto
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($progetti)): ?>
                                <p class="text-muted text-center py-4">Nessun progetto creato ancora.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Stato</th>
                                                <th>Raccolto</th>
                                                <th>Data</th>
                                                <th>Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($progetti as $progetto): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($progetto['nome']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $progetto['stato'] === 'aperto' ? 'success' : 'secondary' ?>">
                                                            <?= ucfirst($progetto['stato']) ?>
                                                        </span>
                                                    </td>
                                                    <td>€<?= number_format($progetto['budget_raccolto'], 2) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($progetto['data_inserimento'])) ?></td>
                                                    <td>
                                                        <a href="view.php?id=<?= $progetto['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">I Tuoi Finanziamenti</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($finanziamenti)): ?>
                                <p class="text-muted text-center py-4">Nessun finanziamento effettuato ancora.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Progetto</th>
                                                <th>Importo</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($finanziamenti as $finanziamento): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($finanziamento['nome']) ?></td>
                                                    <td>€<?= number_format($finanziamento['importo'], 2) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($finanziamento['data_finanziamento'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
