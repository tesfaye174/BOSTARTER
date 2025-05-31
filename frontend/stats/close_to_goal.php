<?php
/**
 * Pagina statistiche - Progetti Vicini all'Obiettivo
 * Mostra i progetti attivi che sono vicini a raggiungere il loro obiettivo di finanziamento
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
$limit = 15;
$offset = ($page - 1) * $limit;

// Parametri di filtro
$soglia_min = max(50, intval($_GET['soglia_min'] ?? 70)); // Percentuale minima completamento
$soglia_max = min(100, intval($_GET['soglia_max'] ?? 95)); // Percentuale massima completamento
$tipo_progetto = $_GET['tipo'] ?? 'all'; // all, hardware, software
$giorni_rimanenti = intval($_GET['giorni'] ?? 0); // 0 = tutti, altrimenti filtra per giorni rimanenti

// Costruisci la query con filtri
$where_conditions = [
    "p.stato = 'aperto'",
    "p.data_fine > NOW()",
    "p.budget_raccolto >= (p.budget_obiettivo * ? / 100)",
    "p.budget_raccolto < (p.budget_obiettivo * ? / 100)"
];
$params = [$soglia_min, $soglia_max];

// Filtro tipo progetto
if ($tipo_progetto === 'hardware') {
    $where_conditions[] = "p.tipo = 'hardware'";
} elseif ($tipo_progetto === 'software') {
    $where_conditions[] = "p.tipo = 'software'";
}

// Filtro giorni rimanenti
if ($giorni_rimanenti > 0) {
    $where_conditions[] = "DATEDIFF(p.data_fine, NOW()) <= ?";
    $params[] = $giorni_rimanenti;
}

$where_clause = implode(' AND ', $where_conditions);

// Query per contare il totale
$count_query = "
    SELECT COUNT(*) as total
    FROM progetti p
    WHERE $where_clause
";

$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_projects = $stmt->fetch()['total'];
$total_pages = ceil($total_projects / $limit);

// Query principale per i progetti vicini all'obiettivo
$query = "
    SELECT 
        p.id,
        p.nome,
        p.descrizione,
        p.budget_obiettivo,
        p.budget_raccolto,
        p.data_inizio,
        p.data_fine,
        p.tipo,
        p.immagine_principale,
        u.nickname AS creatore_nickname,
        u.id AS creatore_id,
        ROUND((p.budget_raccolto / p.budget_obiettivo) * 100, 2) AS percentuale_completamento,
        DATEDIFF(p.data_fine, NOW()) AS giorni_rimanenti,
        COUNT(DISTINCT f.id) AS numero_finanziatori,
        COUNT(DISTINCT c.id) AS numero_commenti,
        p.budget_obiettivo - p.budget_raccolto AS importo_mancante,
        CASE 
            WHEN DATEDIFF(p.data_fine, NOW()) <= 3 THEN 'urgente'
            WHEN DATEDIFF(p.data_fine, NOW()) <= 7 THEN 'critico'
            WHEN DATEDIFF(p.data_fine, NOW()) <= 14 THEN 'attenzione'
            ELSE 'normale'
        END AS urgenza
    FROM progetti p
    JOIN utenti u ON p.creatore_id = u.id
    LEFT JOIN finanziamenti f ON p.id = f.progetto_id
    LEFT JOIN commenti c ON p.id = c.progetto_id
    WHERE $where_clause
    GROUP BY p.id, p.nome, p.descrizione, p.budget_obiettivo, p.budget_raccolto, 
             p.data_inizio, p.data_fine, p.tipo, p.immagine_principale, 
             u.nickname, u.id
    ORDER BY percentuale_completamento DESC, giorni_rimanenti ASC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Statistiche generali
$stats_query = "
    SELECT 
        COUNT(*) as total_close_projects,
        AVG((p.budget_raccolto / p.budget_obiettivo) * 100) as avg_completion,
        SUM(p.budget_obiettivo - p.budget_raccolto) as total_amount_needed,
        AVG(DATEDIFF(p.data_fine, NOW())) as avg_days_remaining,
        COUNT(CASE WHEN DATEDIFF(p.data_fine, NOW()) <= 7 THEN 1 END) as urgent_projects
    FROM progetti p
    WHERE $where_clause
";

$stmt = $conn->prepare($stats_query);
$stmt->execute(array_slice($params, 0, -2)); // Rimuovi limit e offset
$general_stats = $stmt->fetch();

// Log visualizzazione
if (isset($_SESSION['user_id'])) {
    $mongoLogger->logActivity($_SESSION['user_id'], 'view_close_to_goal_stats', [
        'min_threshold' => $soglia_min,
        'max_threshold' => $soglia_max,
        'project_type' => $tipo_progetto,
        'days_remaining' => $giorni_rimanenti,
        'page' => $page
    ]);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progetti Vicini all'Obiettivo - Statistiche - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/frontend/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-warning">
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
                <li class="breadcrumb-item active">Vicini all'Obiettivo</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h1 class="h3 mb-3">
                            <i class="fas fa-bullseye me-2"></i>
                            Progetti Vicini all'Obiettivo
                        </h1>
                        <p class="mb-0">Progetti attivi che hanno quasi raggiunto il loro obiettivo di finanziamento</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiche Generali -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <i class="fas fa-bullseye fa-2x text-warning mb-2"></i>
                        <h4 class="mb-0"><?php echo number_format($general_stats['total_close_projects']); ?></h4>
                        <small class="text-muted">Progetti Vicini</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <i class="fas fa-percentage fa-2x text-warning mb-2"></i>
                        <h4 class="mb-0"><?php echo number_format($general_stats['avg_completion'], 1); ?>%</h4>
                        <small class="text-muted">Completamento Medio</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <i class="fas fa-euro-sign fa-2x text-warning mb-2"></i>
                        <h4 class="mb-0">€<?php echo number_format($general_stats['total_amount_needed'], 0, ',', '.'); ?></h4>
                        <small class="text-muted">Totale Mancante</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-danger mb-2"></i>
                        <h4 class="mb-0"><?php echo number_format($general_stats['urgent_projects']); ?></h4>
                        <small class="text-muted">Progetti Urgenti</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtri -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label for="soglia_min" class="form-label">% Min</label>
                        <input type="number" 
                               class="form-control" 
                               id="soglia_min" 
                               name="soglia_min" 
                               min="0" max="100" 
                               value="<?php echo $soglia_min; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="soglia_max" class="form-label">% Max</label>
                        <input type="number" 
                               class="form-control" 
                               id="soglia_max" 
                               name="soglia_max" 
                               min="0" max="100" 
                               value="<?php echo $soglia_max; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="tipo" class="form-label">Tipo Progetto</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <option value="all" <?php echo $tipo_progetto === 'all' ? 'selected' : ''; ?>>Tutti i tipi</option>
                            <option value="hardware" <?php echo $tipo_progetto === 'hardware' ? 'selected' : ''; ?>>Hardware</option>
                            <option value="software" <?php echo $tipo_progetto === 'software' ? 'selected' : ''; ?>>Software</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="giorni" class="form-label">Giorni Rimanenti</label>
                        <select class="form-select" id="giorni" name="giorni">
                            <option value="0" <?php echo $giorni_rimanenti === 0 ? 'selected' : ''; ?>>Tutti</option>
                            <option value="3" <?php echo $giorni_rimanenti === 3 ? 'selected' : ''; ?>>≤ 3 giorni</option>
                            <option value="7" <?php echo $giorni_rimanenti === 7 ? 'selected' : ''; ?>>≤ 7 giorni</option>
                            <option value="14" <?php echo $giorni_rimanenti === 14 ? 'selected' : ''; ?>>≤ 14 giorni</option>
                            <option value="30" <?php echo $giorni_rimanenti === 30 ? 'selected' : ''; ?>>≤ 30 giorni</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-warning d-block">
                            <i class="fas fa-filter me-2"></i>Filtra
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista Progetti -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Progetti Trovati
                    <?php if ($total_projects > 0): ?>
                        <span class="badge bg-warning text-dark ms-2"><?php echo $total_projects; ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($projects)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nessun progetto trovato</h5>
                        <p class="text-muted">Prova a modificare i filtri di ricerca.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                        <div class="border-bottom p-3">
                            <div class="row align-items-center">
                                <!-- Immagine e Info Base -->
                                <div class="col-md-8">
                                    <div class="d-flex">
                                        <div class="project-image me-3">
                                            <?php if ($project['immagine_principale']): ?>
                                                <img src="<?php echo htmlspecialchars($project['immagine_principale']); ?>" 
                                                     alt="<?php echo htmlspecialchars($project['nome']); ?>"
                                                     class="img-fluid rounded">
                                            <?php else: ?>
                                                <div class="placeholder-image bg-light rounded d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-2"><?php echo htmlspecialchars($project['nome']); ?></h5>
                                                <span class="badge bg-<?php echo $project['tipo'] === 'hardware' ? 'primary' : 'info'; ?>">
                                                    <?php echo ucfirst($project['tipo']); ?>
                                                </span>
                                                <?php
                                                $urgency_colors = [
                                                    'urgente' => 'danger',
                                                    'critico' => 'warning',
                                                    'attenzione' => 'info',
                                                    'normale' => 'success'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $urgency_colors[$project['urgenza']]; ?> ms-2">
                                                    <?php echo $project['giorni_rimanenti']; ?> giorni
                                                </span>
                                            </div>
                                            <p class="text-muted mb-2">
                                                di <strong><?php echo htmlspecialchars($project['creatore_nickname']); ?></strong>
                                            </p>
                                            <p class="mb-0">
                                                <?php echo htmlspecialchars(substr($project['descrizione'], 0, 120)); ?>
                                                <?php if (strlen($project['descrizione']) > 120): ?>...<?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistiche -->
                                <div class="col-md-4">
                                    <div class="text-end">
                                        <!-- Progresso -->
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="text-muted">Progresso</small>
                                                <strong class="text-<?php echo $project['percentuale_completamento'] >= 90 ? 'success' : 'warning'; ?>">
                                                    <?php echo $project['percentuale_completamento']; ?>%
                                                </strong>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-<?php echo $project['percentuale_completamento'] >= 90 ? 'success' : 'warning'; ?>" 
                                                     style="width: <?php echo $project['percentuale_completamento']; ?>%"></div>
                                            </div>
                                        </div>

                                        <!-- Budget -->
                                        <div class="mb-2">
                                            <div class="fw-bold text-success">
                                                €<?php echo number_format($project['budget_raccolto'], 0, ',', '.'); ?>
                                            </div>
                                            <small class="text-muted">
                                                di €<?php echo number_format($project['budget_obiettivo'], 0, ',', '.'); ?>
                                            </small>
                                        </div>

                                        <!-- Mancante -->
                                        <div class="mb-2">
                                            <div class="text-danger fw-bold">
                                                €<?php echo number_format($project['importo_mancante'], 0, ',', '.'); ?>
                                            </div>
                                            <small class="text-muted">mancanti</small>
                                        </div>

                                        <!-- Statistiche Aggiuntive -->
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>
                                                <i class="fas fa-users me-1"></i>
                                                <?php echo $project['numero_finanziatori']; ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-comments me-1"></i>
                                                <?php echo $project['numero_commenti']; ?>
                                            </span>
                                        </div>

                                        <!-- Azioni -->
                                        <div class="mt-3">
                                            <a href="/frontend/projects/fund.php?id=<?php echo $project['id']; ?>" 
                                               class="btn btn-warning btn-sm">
                                                <i class="fas fa-hand-holding-heart me-1"></i>
                                                Sostieni
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Paginazione -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginazione progetti" class="mt-4">
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
                        <a href="/frontend/stats/top_creators.php" class="btn btn-outline-success me-2">
                            <i class="fas fa-trophy me-1"></i>Top Creatori
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
        .project-image {
            width: 80px;
            height: 60px;
            flex-shrink: 0;
        }

        .project-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder-image {
            width: 80px;
            height: 60px;
            font-size: 24px;
        }

        .progress {
            background-color: #e9ecef;
        }

        .badge {
            font-size: 0.75em;
        }

        .border-bottom:last-child {
            border-bottom: none !important;
        }
    </style>
</body>
</html>
