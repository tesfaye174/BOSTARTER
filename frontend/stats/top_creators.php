<?php
/**
 * Pagina statistiche - Top Creatori
 * Mostra i creatori con più progetti finanziati con successo
 */

session_start();
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/services/MongoLogger.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$mongoLogger = new MongoLogger();

// Parametri di paginazione
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Parametri di filtro
$periodo = $_GET['periodo'] ?? 'all'; // all, year, month
$tipo_progetto = $_GET['tipo'] ?? 'all'; // all, hardware, software

// Costruisci la query con filtri
$where_conditions = ["p.stato = 'finanziato'"];
$params = [];

// Filtro periodo
if ($periodo === 'year') {
    $where_conditions[] = "p.data_creazione >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
} elseif ($periodo === 'month') {
    $where_conditions[] = "p.data_creazione >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
}

// Filtro tipo progetto
if ($tipo_progetto === 'hardware') {
    $where_conditions[] = "p.tipo = 'hardware'";
} elseif ($tipo_progetto === 'software') {
    $where_conditions[] = "p.tipo = 'software'";
}

$where_clause = implode(' AND ', $where_conditions);

// Query per contare il totale
$count_query = "
    SELECT COUNT(DISTINCT u.id) as total
    FROM utenti u
    JOIN progetti p ON u.id = p.creatore_id
    WHERE $where_clause
";

$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_creators = $stmt->fetch()['total'];
$total_pages = ceil($total_creators / $limit);

// Query principale per i top creatori
$query = "
    SELECT 
        u.id,
        u.nickname,
        u.email,
        u.data_registrazione,
        COUNT(DISTINCT p.id) as progetti_finanziati,
        SUM(p.budget_obiettivo) as budget_totale_obiettivo,
        SUM(p.budget_raccolto) as budget_totale_raccolto,
        AVG(p.budget_raccolto / p.budget_obiettivo * 100) as percentuale_media_successo,
        MAX(p.budget_raccolto) as progetto_max_raccolto,
        MIN(p.data_creazione) as primo_progetto,
        MAX(p.data_fine) as ultimo_progetto_finito,
        COUNT(DISTINCT f.utente_id) as finanziatori_unici
    FROM utenti u
    JOIN progetti p ON u.id = p.creatore_id
    LEFT JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE $where_clause
    GROUP BY u.id, u.nickname, u.email, u.data_registrazione
    ORDER BY progetti_finanziati DESC, budget_totale_raccolto DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$creators = $stmt->fetchAll();

// Statistiche generali
$stats_query = "
    SELECT 
        COUNT(DISTINCT u.id) as total_creators_with_funded_projects,
        COUNT(DISTINCT p.id) as total_funded_projects,
        SUM(p.budget_raccolto) as total_amount_raised,
        AVG(p.budget_raccolto) as avg_amount_per_project,
        COUNT(DISTINCT f.utente_id) as total_unique_backers
    FROM utenti u
    JOIN progetti p ON u.id = p.creatore_id
    LEFT JOIN finanziamenti f ON p.id = f.progetto_id
    WHERE $where_clause
";

$stmt = $conn->prepare($stats_query);
$stmt->execute(array_slice($params, 0, -2)); // Rimuovi limit e offset
$general_stats = $stmt->fetch();

// Log visualizzazione
if (isset($_SESSION['user_id'])) {
    $mongoLogger->logActivity($_SESSION['user_id'], 'view_top_creators_stats', [
        'period' => $periodo,
        'project_type' => $tipo_progetto,
        'page' => $page
    ]);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Creatori - Statistiche - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/frontend/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/frontend/">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="/frontend/dashboard.html">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                    <a class="nav-link" href="/frontend/auth/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="/frontend/auth/login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/frontend/">Home</a></li>
                <li class="breadcrumb-item">Statistiche</li>
                <li class="breadcrumb-item active">Top Creatori</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h1 class="h3 mb-3">
                            <i class="fas fa-trophy me-2"></i>
                            Top Creatori
                        </h1>
                        <p class="mb-0">I creatori con più progetti finanziati con successo</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiche Generali -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-success mb-2"></i>
                        <h4 class="mb-0"><?php echo number_format($general_stats['total_creators_with_funded_projects']); ?></h4>
                        <small class="text-muted">Creatori di Successo</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <i class="fas fa-project-diagram fa-2x text-success mb-2"></i>
                        <h4 class="mb-0"><?php echo number_format($general_stats['total_funded_projects']); ?></h4>
                        <small class="text-muted">Progetti Finanziati</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <i class="fas fa-euro-sign fa-2x text-success mb-2"></i>
                        <h4 class="mb-0">€<?php echo number_format($general_stats['total_amount_raised'], 0, ',', '.'); ?></h4>
                        <small class="text-muted">Totale Raccolto</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <i class="fas fa-heart fa-2x text-success mb-2"></i>
                        <h4 class="mb-0"><?php echo number_format($general_stats['total_unique_backers']); ?></h4>
                        <small class="text-muted">Finanziatori Unici</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtri -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="periodo" class="form-label">Periodo</label>
                        <select class="form-select" id="periodo" name="periodo">
                            <option value="all" <?php echo $periodo === 'all' ? 'selected' : ''; ?>>Tutti i tempi</option>
                            <option value="year" <?php echo $periodo === 'year' ? 'selected' : ''; ?>>Ultimo anno</option>
                            <option value="month" <?php echo $periodo === 'month' ? 'selected' : ''; ?>>Ultimo mese</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="tipo" class="form-label">Tipo Progetto</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <option value="all" <?php echo $tipo_progetto === 'all' ? 'selected' : ''; ?>>Tutti i tipi</option>
                            <option value="hardware" <?php echo $tipo_progetto === 'hardware' ? 'selected' : ''; ?>>Hardware</option>
                            <option value="software" <?php echo $tipo_progetto === 'software' ? 'selected' : ''; ?>>Software</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-success d-block">
                            <i class="fas fa-filter me-2"></i>Applica Filtri
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista Top Creatori -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-ranking-star me-2"></i>
                    Classifica Creatori
                    <?php if ($total_creators > 0): ?>
                        <span class="badge bg-success ms-2"><?php echo $total_creators; ?> trovati</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($creators)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nessun creatore trovato</h5>
                        <p class="text-muted">Prova a modificare i filtri di ricerca.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-success">
                                <tr>
                                    <th>Posizione</th>
                                    <th>Creatore</th>
                                    <th>Progetti Finanziati</th>
                                    <th>Budget Raccolto</th>
                                    <th>Tasso di Successo</th>
                                    <th>Finanziatori</th>
                                    <th>Attività</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($creators as $index => $creator): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $position = $offset + $index + 1;
                                                if ($position <= 3): 
                                                ?>
                                                    <i class="fas fa-medal text-warning me-2"></i>
                                                <?php endif; ?>
                                                <strong>#<?php echo $position; ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-placeholder me-3">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($creator['nickname']); ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        Dal <?php echo date('d/m/Y', strtotime($creator['data_registrazione'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">
                                                <?php echo $creator['progetti_finanziati']; ?>
                                            </div>
                                            <small class="text-muted">progetti</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                €<?php echo number_format($creator['budget_totale_raccolto'], 0, ',', '.'); ?>
                                            </div>
                                            <small class="text-muted">
                                                su €<?php echo number_format($creator['budget_totale_obiettivo'], 0, ',', '.'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php $success_rate = round($creator['percentuale_media_successo'], 1); ?>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?php echo min(100, $success_rate); ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo $success_rate; ?>%</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary">
                                                <?php echo number_format($creator['finanziatori_unici']); ?>
                                            </div>
                                            <small class="text-muted">sostenitori</small>
                                        </td>
                                        <td>
                                            <div class="text-muted small">
                                                <div>
                                                    <i class="fas fa-calendar-plus me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($creator['primo_progetto'])); ?>
                                                </div>
                                                <?php if ($creator['ultimo_progetto_finito']): ?>
                                                    <div>
                                                        <i class="fas fa-calendar-check me-1"></i>
                                                        <?php echo date('d/m/Y', strtotime($creator['ultimo_progetto_finito'])); ?>
                                                    </div>
                                                <?php endif; ?>
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

        <!-- Paginazione -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginazione creatori" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Prima pagina -->
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Pagine numerate -->
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Ultima pagina -->
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- Link ad altre statistiche -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title">Altre Statistiche</h6>
                        <a href="/frontend/stats/close_to_goal.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-bullseye me-1"></i>Progetti Vicini all'Obiettivo
                        </a>
                        <a href="/frontend/projects/list_open.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>Tutti i Progetti
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .avatar-placeholder {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .table tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .progress {
            background-color: #e9ecef;
        }

        .badge {
            font-size: 0.75em;
        }
    </style>
</body>
</html>
