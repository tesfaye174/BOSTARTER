<?php
/**
 * Dashboard Utente BOSTARTER
 *
 * Visualizzazione personalizzata basata sul ruolo:
 * - Per creatori: progetti creati, statistiche, affidabilità
 * - Per utenti: finanziamenti effettuati, progetti supportati
 */

// Avvia sessione
session_start();

// Verifica autenticazione
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

// Reindirizza se non loggato
if (!isLoggedIn()) {
    session_regenerate_id(true);
    header("Location: auth/login.php");
    exit;
}

// Recupera dati utente
$user_id = $_SESSION["user_id"];
$nickname = $_SESSION["nickname"];
$tipo_utente = $_SESSION["tipo_utente"];

// Inizializzazione array statistiche
$stats = [
    "progetti_creati" => 0,
    "fondi_raccolti" => 0,
    "finanziamenti_fatti" => 0,
    "totale_investito" => 0
];

// Array per contenere progetti e finanziamenti
$progetti = [];
$finanziamenti = [];

// Connessione al database e recupero dati
try {
    require_once "../backend/config/database.php";
    $db = Database::getInstance();

    // IMPORTANTE: Chiudi eventuali result set pendenti
    while ($db->query('SELECT 1')) {
        // Consuma eventuali result set pendenti
        break;
    }

    if ($tipo_utente === "creatore") {
        // Statistiche creatore
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM progetti WHERE creatore_id = ? AND is_active = TRUE");
        $stmt->execute([$user_id]);
        $stats["progetti_creati"] = $stmt->fetch()["count"] ?? 0;

        // Fondi totali richiesti (budget_richiesto)
        $stmt = $db->prepare("SELECT COALESCE(SUM(budget_richiesto), 0) as totale FROM progetti WHERE creatore_id = ? AND is_active = TRUE");
        $stmt->execute([$user_id]);
        $stats["fondi_richiesti"] = $stmt->fetch()["totale"] ?? 0;

        // Fondi raccolti (calcolati dai finanziamenti completati)
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(f.importo), 0) as totale_raccolto
            FROM progetti p
            LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completed'
            WHERE p.creatore_id = ? AND p.is_active = TRUE
        ");
        $stmt->execute([$user_id]);
        $stats["fondi_raccolti"] = $stmt->fetch()["totale_raccolto"] ?? 0;

        // Affidabilità dal database (se esiste tabella creatori)
        $stmt = $db->prepare("
            SELECT COALESCE(c.affidabilita, 0) as affidabilita, COALESCE(c.nr_progetti, 0) as nr_progetti
            FROM utenti u
            LEFT JOIN creatori c ON u.id = c.utente_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $creatorData = $stmt->fetch();
        $stats["affidabilita"] = $creatorData["affidabilita"] ?? 0;
        $stats["nr_progetti_calcolato"] = $creatorData["nr_progetti"] ?? $stats["progetti_creati"];

        // Progetti del creatore con dati calcolati
        $stmt = $db->prepare("
            SELECT
                p.id,
                p.titolo as nome,
                p.descrizione,
                p.stato,
                p.budget_richiesto as budget_totale,
                p.data_inserimento,
                p.tipo_progetto,
                COALESCE(SUM(f.importo), 0) as budget_raccolto,
                COUNT(f.id) as numero_finanziamenti
            FROM progetti p
            LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completed'
            WHERE p.creatore_id = ? AND p.is_active = TRUE
            GROUP BY p.id, p.titolo, p.descrizione, p.stato, p.budget_richiesto, p.data_inserimento, p.tipo_progetto
            ORDER BY p.data_inserimento DESC
        ");
        $stmt->execute([$user_id]);
        $progetti = $stmt->fetchAll();

    } else {
        // Statistiche investitore
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM finanziamenti WHERE utente_id = ? AND stato_pagamento = 'completed'");
        $stmt->execute([$user_id]);
        $stats["finanziamenti_fatti"] = $stmt->fetch()["count"] ?? 0;

        $stmt = $db->prepare("SELECT COALESCE(SUM(importo), 0) as totale FROM finanziamenti WHERE utente_id = ? AND stato_pagamento = 'completed'");
        $stmt->execute([$user_id]);
        $stats["totale_investito"] = $stmt->fetch()["totale"] ?? 0;

        // Finanziamenti effettuati con dettagli progetti
        $stmt = $db->prepare("
            SELECT
                f.importo,
                f.data_finanziamento,
                p.titolo as nome_progetto,
                p.id as progetto_id,
                f.stato_pagamento
            FROM finanziamenti f
            JOIN progetti p ON f.progetto_id = p.id
            WHERE f.utente_id = ? AND f.stato_pagamento = 'completed'
            ORDER BY f.data_finanziamento DESC
        ");
        $stmt->execute([$user_id]);
        $finanziamenti = $stmt->fetchAll();

        // Progetti unici finanziati
        $stats["progetti_finanziati"] = count(array_unique(array_column($finanziamenti, 'progetto_id')));
    }

    // Statistiche comuni
    $stats["progetti_attivi"] = 0;
    $stats["progetti_completati"] = 0;

    if ($tipo_utente === "creatore") {
        foreach ($progetti as $progetto) {
            if ($progetto['stato'] === 'aperto') {
                $stats["progetti_attivi"]++;
            } elseif ($progetto['stato'] === 'chiuso') {
                $stats["progetti_completati"]++;
            }
        }
    }

} catch(Exception $e) {
    error_log('Errore dashboard: ' . $e->getMessage());
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
                            <li><a class="dropdown-item" href="auth/logout.php">Esci</a></li>
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
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-accent);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h5 class="card-title">Progetti Creati</h5>
                        <p class="card-text display-4 fw-bold"><?= $stats["progetti_creati"] ?></p>
                        <small class="text-muted">
                            <?= $stats["progetti_attivi"] ?> attivi, <?= $stats["progetti_completati"] ?> completati
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-info);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <h5 class="card-title">Fondi Raccolti</h5>
                        <p class="card-text display-4 fw-bold">€<?= number_format($stats["fondi_raccolti"], 0) ?></p>
                        <small class="text-muted">
                            di €<?= number_format($stats["fondi_richiesti"], 0) ?> richiesti
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-success);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h5 class="card-title">Affidabilità</h5>
                        <p class="card-text display-4 fw-bold"><?= number_format($stats["affidabilita"], 1) ?>%</p>
                        <small class="text-muted">
                            Basata sui progetti completati
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-warning);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="card-title">Success Rate</h5>
                        <p class="card-text display-4 fw-bold">
                            <?php
                            $successRate = $stats["progetti_creati"] > 0 ?
                                round(($stats["progetti_completati"] / $stats["progetti_creati"]) * 100, 1) : 0;
                            echo $successRate;
                            ?>%
                        </p>
                        <small class="text-muted">
                            Progetti completati
                        </small>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-md-6 col-lg-4">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-primary);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <h5 class="card-title">Finanziamenti Fatti</h5>
                        <p class="card-text display-4 fw-bold"><?= $stats["finanziamenti_fatti"] ?></p>
                        <small class="text-muted">
                            Investimenti effettuati
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-secondary);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <h5 class="card-title">Totale Investito</h5>
                        <p class="card-text display-4 fw-bold">€<?= number_format($stats["totale_investito"], 0) ?></p>
                        <small class="text-muted">
                            Valore totale finanziamenti
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card stat-card h-100" style="--stat-color: var(--bostarter-success);">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h5 class="card-title">Progetti Supportati</h5>
                        <p class="card-text display-4 fw-bold"><?= $stats["progetti_finanziati"] ?? 0 ?></p>
                        <small class="text-muted">
                            Progetti unici finanziati
                        </small>
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
                                        <th>Progetto</th>
                                        <th>Tipo</th>
                                        <th>Progresso</th>
                                        <th>Finanziatori</th>
                                        <th>Data Creazione</th>
                                        <th class="text-end">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($progetti as $progetto):
                                        $progressPercent = $progetto['budget_totale'] > 0 ?
                                            min(100, round(($progetto['budget_raccolto'] / $progetto['budget_totale']) * 100, 1)) : 0;
                                        $progressColor = $progressPercent >= 100 ? 'success' :
                                                        ($progressPercent >= 50 ? 'warning' : 'info');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">
                                                <a href="view.php?id=<?= $progetto['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($progetto['nome']) ?>
                                                </a>
                                            </div>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(substr($progetto['descrizione'], 0, 50)) ?>...
                                            </small>
                                            <div class="mt-1">
                                                <small class="badge bg-<?= $progressColor ?>">
                                                    €<?= number_format($progetto['budget_raccolto'], 0) ?> /
                                                    €<?= number_format($progetto['budget_totale'], 0) ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-<?= $progetto['tipo_progetto'] === 'hardware' ? 'cogs' : 'code' ?> me-1"></i>
                                                <?= ucfirst($progetto['tipo_progetto']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar bg-<?= $progressColor ?>"
                                                         style="width: <?= $progressPercent ?>%"></div>
                                                </div>
                                                <small class="text-muted fw-bold">
                                                    <?= $progressPercent ?>%
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-users me-1"></i>
                                                <?= $progetto['numero_finanziamenti'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($progetto['data_inserimento'])) ?>
                                            <br>
                                            <small class="text-muted">
                                                <?= date('H:i', strtotime($progetto['data_inserimento'])) ?>
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?= $progetto['id'] ?>"
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>Vedi
                                                </a>
                                                <a href="edit.php?id=<?= $progetto['id'] ?>"
                                                   class="btn btn-outline-secondary">
                                                    <i class="fas fa-edit me-1"></i>Modifica
                                                </a>
                                            </div>
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
                                        <th>Stato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($finanziamenti as $finanziamento): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">
                                                <a href="view.php?id=<?= $finanziamento['progetto_id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($finanziamento['nome_progetto']) ?>
                                                </a>
                                            </div>
                                            <small class="text-muted">
                                                Progetto finanziato
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">
                                                €<?= number_format($finanziamento['importo'], 2) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($finanziamento['data_finanziamento'])) ?>
                                            <br>
                                            <small class="text-muted">
                                                <?= date('H:i', strtotime($finanziamento['data_finanziamento'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $finanziamento['stato_pagamento'] === 'completed' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($finanziamento['stato_pagamento']) ?>
                                            </span>
                                        </td>
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