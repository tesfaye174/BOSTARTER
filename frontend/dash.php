<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . "/../backend/config/database.php";

if (!isset($_SESSION["user_id"])) {
    session_regenerate_id(true); // Rigenera l'ID sessione per sicurezza
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
        
        $stmt = $conn->prepare("SELECT COALESCE(SUM(budget_richiesto), 0) as totale FROM progetti WHERE creatore_id = ?");
        $stmt->execute([$user_id]);
        $stats["fondi_raccolti"] = $stmt->fetch()["totale"] ?? 0;
        
        // Progetti del creatore
        $stmt = $conn->prepare("
            SELECT * FROM progetti 
            WHERE creatore_id = ? 
            ORDER BY data_creazione DESC
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
    <?php $page_title = 'Dashboard'; include __DIR__ . '/includes/head.php'; ?>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top glass-effect">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">

                    <a class="nav-link" href="view.php">Progetti</a>
                    <?php if ($tipo_utente === "creatore"): ?>
                    <a class="nav-link" href="new.php">Nuovo Progetto</a>
                    <?php endif; ?>
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($nickname) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="auth/exit.php">Esci</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Header con messaggio personalizzato -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <?php 
                $welcome_messages = [
                    "Ciao {name}! Bentornato nella tua dashboard.",
                    "Eccoti qui, {name}! Pronto per nuove avventure?",
                    "Benvenuto {name}! Vediamo cosa puoi creare oggi.",
                    "Ciao {name}! La tua creativity station ti aspetta."
                ];
                $random_welcome = str_replace('{name}', htmlspecialchars($nickname), $welcome_messages[array_rand($welcome_messages)]);
                ?>
                <h1 class="display-5 fw-bold text-gradient-primary"><?= $random_welcome ?></h1>
                <p class="lead text-muted">
                    <?php if ($tipo_utente === "creatore"): ?>
                    Gestisci i tuoi progetti e monitora i tuoi progressi.
                    <?php else: ?>
                    Tieni traccia dei progetti che hai supportato.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><strong>Ops!</strong> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Statistiche -->
        <div class="row g-4 mb-5">
            <?php if ($tipo_utente === "creatore"): ?>
            <div class="col-md-6">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-accent);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h5 class="card-title">Progetti Creati</h5>
                        <p class="card-text display-4 fw-bold"><?= $stats["progetti_creati"] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-info);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <h5 class="card-title">Fondi Raccolti</h5>
                        <p class="card-text display-4 fw-bold">€<?= number_format($stats["fondi_raccolti"], 2) ?></p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-md-6">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-primary);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <h5 class="card-title">Finanziamenti Fatti</h5>
                        <p class="card-text display-4 fw-bold"><?= $stats["finanziamenti_fatti"] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-secondary);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <h5 class="card-title">Totale Investito</h5>
                        <p class="card-text display-4 fw-bold">€<?= number_format($stats["totale_investito"], 2) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Contenuto principale -->
        <div class="row">
            <div class="col-12">
                <?php if ($tipo_utente === "creatore"): ?>
                <div class="card content-card">
                    <div class="card-header">
                        <h5 class="mb-0">I Tuoi Progetti</h5>
                        <a href="new.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Nuovo Progetto
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($progetti)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-lightbulb fa-4x text-primary mb-4"></i>
                            <h4 class="mb-3">È il momento di creare!</h4>
                            <p class="text-muted mb-4">La tua prossima grande idea è a un solo click di distanza.</p>
                            <a href="new.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i>Crea il Tuo Primo Progetto
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome Progetto</th>
                                        <th>Stato</th>
                                        <th>Budget Raccolto</th>
                                        <th>Data Creazione</th>
                                        <th class="text-end">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($progetti as $progetto): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($progetto['nome']) ?></div>
                                            <small
                                                class="text-muted"><?= htmlspecialchars(substr($progetto['descrizione'], 0, 40)) ?>...</small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge rounded-pill bg-<?= $progetto['stato'] === 'aperto' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($progetto['stato']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold">€<?= number_format($progetto['budget_raccolto'], 2) ?>
                                            </div>
                                            <small class="text-muted">di
                                                €<?= number_format($progetto['budget_totale'], 2) ?></small>
                                        </td>
                                        <td><?= date('d M Y', strtotime($progetto['data_inserimento'])) ?></td>
                                        <td class="text-end">
                                            <a href="view.php?id=<?= $progetto['id'] ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i> Vedi
                                            </a>
                                            <a href="#" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit me-1"></i> Modifica
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
                <div class="card content-card">
                    <div class="card-header">
                        <h5 class="mb-0">I Tuoi Finanziamenti</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($finanziamenti)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search-dollar fa-4x text-primary mb-4"></i>
                            <h4 class="mb-3">Scopri nuove opportunità</h4>
                            <p class="text-muted mb-4">Esplora i progetti e supporta le idee che ti appassionano.</p>
                            <a href="view.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket me-2"></i>Esplora i Progetti
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Progetto</th>
                                        <th>Importo Investito</th>
                                        <th>Data Finanziamento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($finanziamenti as $finanziamento): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">
                                                <?= htmlspecialchars($finanziamento['nome_progetto']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">€<?= number_format($finanziamento['importo'], 2) ?>
                                            </div>
                                        </td>
                                        <td><?= date('d M Y', strtotime($finanziamento['data_finanziamento'])) ?></td>
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

    <?php include __DIR__ . '/includes/scripts.php'; ?>
    <script src="<?= $basePath ?>js/dash.js"></script>
</body>

</html>