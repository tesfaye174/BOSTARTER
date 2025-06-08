<?php
/**
 * Visualizza progetti - Mostra e filtra i progetti
 * BOSTARTER - Piattaforma di Crowdfunding
 */

require_once '../../backend/config/database.php';
require_once '../../backend/utils/NavigationHelper.php';
require_once '../components/header.php';

// Recupera parametri di filtro
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? 'attivo';
$sort = $_GET['sort'] ?? 'recent';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// URL base per i filtri
$base_filter_url = NavigationHelper::url('projects');

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Costruisce la WHERE per i filtri
    $where_conditions = [];
    $params = [];
    
    // Filtro stato
    if ($status && $status !== 'all') {
        $where_conditions[] = "p.stato = ?";
        $params[] = $status;
    }
    
    // Filtro categoria
    if ($category && $category !== 'all') {
        $where_conditions[] = "p.categoria = ?";
        $params[] = $category;
    }
    
    // Filtro ricerca
    if ($search) {
        $where_conditions[] = "(p.titolo LIKE ? OR p.descrizione LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Costruisce l'ORDER BY per l'ordinamento
    $order_clause = "ORDER BY ";
    switch ($sort) {
        case 'recent':
            $order_clause .= "p.data_creazione DESC";
            break;
        case 'ending_soon':
            $order_clause .= "p.data_scadenza ASC";
            break;
        case 'most_funded':
            $order_clause .= "p.finanziamento_attuale DESC";
            break;
        case 'most_backed':
            $order_clause .= "(SELECT COUNT(*) FROM finanziamenti f WHERE f.progetto_id = p.id) DESC";
            break;
        case 'alphabetical':
            $order_clause .= "p.titolo ASC";
            break;
        default:
            $order_clause .= "p.data_creazione DESC";
    }
    
    // Recupera il conteggio totale per la paginazione
    $count_sql = "
        SELECT COUNT(*) as total
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        $where_clause
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_projects = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_projects / $per_page);
    
    // Recupera i progetti
    $sql = "
        SELECT 
            p.id,
            p.titolo,
            p.descrizione,
            p.categoria,
            p.obiettivo_finanziario,
            p.finanziamento_attuale,
            p.data_creazione,
            p.data_scadenza,
            p.stato,
            p.immagine_principale,
            u.nome as creatore_nome,
            u.cognome as creatore_cognome,
            u.avatar as creatore_avatar,
            (SELECT COUNT(*) FROM finanziamenti f WHERE f.progetto_id = p.id AND f.stato_pagamento = 'completato') as numero_finanziatori,
            DATEDIFF(p.data_scadenza, NOW()) as giorni_rimanenti,
            ROUND((p.finanziamento_attuale / p.obiettivo_finanziario) * 100, 1) as percentuale_completamento
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        $where_clause
        $order_clause
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
    
    // Recupera le categorie per il filtro
    $categories_stmt = $pdo->prepare("SELECT DISTINCT categoria FROM progetti ORDER BY categoria");
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    error_log("View projects error: " . $e->getMessage());
    $projects = [];
    $categories = [];
    $total_projects = 0;
    $total_pages = 1;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progetti - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .project-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .project-image {
            height: 200px;
            object-fit: cover;
        }
        .progress-custom {
            height: 8px;
            border-radius: 4px;
        }
        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        .creator-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Intestazione Pagina -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="display-4 mb-3">Esplora i Progetti</h1>
                <p class="text-center text-muted">Scopri progetti innovativi e supporta le idee che ti ispirano</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo NavigationHelper::url('create_project'); ?>" class="btn btn-success btn-lg">
                        <i class="fas fa-plus"></i> Crea un Nuovo Progetto
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sezione Filtri -->
        <div class="filter-section">
            <form method="GET" action="<?php echo NavigationHelper::url('projects'); ?>" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <select name="category" class="form-select">
                        <option value="">Tutte le categorie</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($cat)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stato</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tutti</option>
                        <option value="attivo" <?= $status === 'attivo' ? 'selected' : '' ?>>Attivi</option>
                        <option value="finanziato" <?= $status === 'finanziato' ? 'selected' : '' ?>>Finanziati</option>
                        <option value="scaduto" <?= $status === 'scaduto' ? 'selected' : '' ?>>Scaduti</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ordina per</label>
                    <select name="sort" class="form-select">
                        <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Più recenti</option>
                        <option value="ending_soon" <?= $sort === 'ending_soon' ? 'selected' : '' ?>>In scadenza</option>
                        <option value="most_funded" <?= $sort === 'most_funded' ? 'selected' : '' ?>>Più finanziati</option>
                        <option value="most_backed" <?= $sort === 'most_backed' ? 'selected' : '' ?>>Più supportati</option>
                        <option value="alphabetical" <?= $sort === 'alphabetical' ? 'selected' : '' ?>>Alfabetico</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cerca</label>
                    <input type="text" name="search" class="form-control" placeholder="Cerca progetti..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtra</button>
                </div>
            </form>
        </div>

        <!-- Info Risultati -->
        <div class="row mb-3">
            <div class="col-12">
                <p class="text-muted">
                    Trovati <?= $total_projects ?> progetti
                    <?php if ($search): ?>
                        per "<?= htmlspecialchars($search) ?>"
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Griglia Progetti -->
        <div class="row">
            <?php if (empty($projects)): ?>
                <div class="col-12 text-center py-5">
                    <h3 class="text-muted">Nessun progetto trovato</h3>
                    <p>Prova a modificare i filtri di ricerca</p>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card project-card h-100 position-relative">
                            <span class="badge bg-primary category-badge">
                                <?= ucfirst(htmlspecialchars($project['categoria'])) ?>
                            </span>
                            
                            <?php if ($project['immagine_principale']): ?>
                                <img src="<?= htmlspecialchars($project['immagine_principale']) ?>" 
                                     class="card-img-top project-image" 
                                     alt="<?= htmlspecialchars($project['titolo']) ?>">
                            <?php else: ?>
                                <div class="card-img-top project-image bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">
                                    <a href="<?php echo NavigationHelper::url('project_detail', ['id' => $project['id']]); ?>" 
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($project['titolo']) ?>
                                    </a>
                                </h5>
                                
                                <p class="card-text text-muted small flex-grow-1">
                                    <?= htmlspecialchars(substr($project['descrizione'], 0, 120)) ?>...
                                </p>
                                
                                <!-- Info Creatore -->
                                <div class="creator-info mb-3">
                                    <small>
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($project['creatore_nome'] . ' ' . $project['creatore_cognome']) ?>
                                    </small>
                                </div>
                                
                                <!-- Progresso -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>€<?= number_format($project['finanziamento_attuale'], 0) ?></small>
                                        <small><?= $project['percentuale_completamento'] ?>%</small>
                                    </div>
                                    <div class="progress progress-custom">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?= min(100, $project['percentuale_completamento']) ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Obiettivo: €<?= number_format($project['obiettivo_finanziario'], 0) ?>
                                    </small>
                                </div>
                                
                                <!-- Righe Statistiche -->
                                <div class="row text-center small mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Sostenitori</small>
                                        <div class="fw-bold">
                                            <?= $project['numero_finanziatori'] ?>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Giorni</small>
                                        <div class="fw-bold <?= $project['giorni_rimanenti'] <= 7 ? 'text-danger' : '' ?>">
                                            <?= max(0, $project['giorni_rimanenti']) ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Badge Stato -->
                                <div class="mt-auto">
                                    <?php
                                    $status_class = match($project['stato']) {
                                        'attivo' => 'bg-success',
                                        'finanziato' => 'bg-primary',
                                        'scaduto' => 'bg-secondary',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $status_class ?> w-100 py-2">
                                        <?= ucfirst($project['stato']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Paginazione -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Projects pagination" class="mt-5">
                <ul class="pagination justify-content-center">
                    <!-- Precedente -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= NavigationHelper::url('projects', array_merge($_GET, ['page' => $page - 1])) ?>">
                            Precedente
                        </a>
                    </li>
                    
                    <!-- Numeri di Pagina -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    ?>
                    
                    <?php if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= NavigationHelper::url('projects', array_merge($_GET, ['page' => 1])) ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= NavigationHelper::url('projects', array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= NavigationHelper::url('projects', array_merge($_GET, ['page' => $total_pages])) ?>">
                                <?= $total_pages ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Successivo -->
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= NavigationHelper::url('projects', array_merge($_GET, ['page' => $page + 1])) ?>">
                            Successivo
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome already incluso nel layout principale -->
</body>
</html>

<?php require_once '../components/footer.php'; ?>
