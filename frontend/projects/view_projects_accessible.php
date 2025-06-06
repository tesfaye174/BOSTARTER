<?php
/**
 * View Projects - Accessible Display and filter projects
 * BOSTARTER - Crowdfunding Platform
 * Enhanced with WCAG 2.1 AA Accessibility Features
 */

require_once '../../backend/config/database.php';
require_once '../components/header.php';

// Get filter parameters
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? 'attivo';
$sort = $_GET['sort'] ?? 'recent';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    // Status filter
    if ($status && $status !== 'all') {
        $where_conditions[] = "p.stato = ?";
        $params[] = $status;
    }
    
    // Category filter
    if ($category && $category !== 'all') {
        $where_conditions[] = "p.categoria = ?";
        $params[] = $category;
    }
    
    // Search filter
    if ($search) {
        $where_conditions[] = "(p.titolo LIKE ? OR p.descrizione LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Build ORDER BY clause
    $order_clause = "ORDER BY ";
    switch ($sort) {
        case 'funding':
            $order_clause .= "p.finanziamento_attuale DESC";
            break;
        case 'deadline':
            $order_clause .= "p.deadline ASC";
            break;
        case 'alphabetical':
            $order_clause .= "p.titolo ASC";
            break;
        case 'recent':
        default:
            $order_clause .= "p.data_inserimento DESC";
            break;
    }
    
    // Count total projects for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        $where_clause
    ";
    
    $count_params = array_slice($params, 0, -2); // Remove LIMIT and OFFSET params
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_projects = $count_stmt->fetchColumn();
    $total_pages = ceil($total_projects / $per_page);
    
    // Get projects
    $sql = "
        SELECT p.id, p.titolo, p.descrizione, p.categoria, p.obiettivo_finanziario,
               p.finanziamento_attuale, p.deadline, p.stato, p.immagine_copertina,
               p.data_inserimento, u.username as creatore_nome,
               ROUND((p.finanziamento_attuale / p.obiettivo_finanziario) * 100, 1) as percentuale_completamento,
               DATEDIFF(p.deadline, NOW()) as giorni_rimanenti
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
    
    // Get categories for filter dropdown
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

// Generate page title for screen readers
$page_title = "Progetti";
if ($search) {
    $page_title .= " - Ricerca: " . htmlspecialchars($search);
}
if ($category && $category !== 'all') {
    $page_title .= " - Categoria: " . htmlspecialchars($category);
}
if ($status && $status !== 'all') {
    $page_title .= " - Stato: " . htmlspecialchars($status);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - BOSTARTER</title>
    <meta name="description" content="Esplora i progetti di crowdfunding su BOSTARTER. Filtra per categoria, stato e ordina per data, finanziamento o scadenza.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/accessibility.css" rel="stylesheet">
    
    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "<?php echo htmlspecialchars($page_title); ?>",
        "description": "Esplora i progetti di crowdfunding su BOSTARTER",
        "url": "<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>",
        "mainEntity": {
            "@type": "ItemList",
            "numberOfItems": <?php echo $total_projects; ?>
        }
    }
    </script>
    
    <style>
        .project-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid transparent;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .project-card:hover,
        .project-card:focus-within {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: var(--bs-primary);
        }
        
        .project-card:focus-within {
            outline: 3px solid var(--focus-color);
            outline-offset: 2px;
        }
        
        .project-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .progress-bar {
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #dee2e6;
        }
        
        .project-stats {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-attivo {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-completato {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-scaduto {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (prefers-reduced-motion: reduce) {
            .project-card {
                transition: none;
            }
            .project-card:hover {
                transform: none;
            }
        }
        
        .sr-only-focusable:focus {
            position: absolute;
            width: auto;
            height: auto;
            clip: auto;
            overflow: visible;
            background: white;
            padding: 8px;
            border: 2px solid #007bff;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Skip Links -->
    <div class="skip-links">
        <a href="#main-content" class="sr-only-focusable">Salta al contenuto principale</a>
        <a href="#filters" class="sr-only-focusable">Salta ai filtri</a>
        <a href="#projects-grid" class="sr-only-focusable">Salta alla griglia progetti</a>
        <a href="#pagination" class="sr-only-focusable">Salta alla paginazione</a>
    </div>

    <main id="main-content" role="main">
        <div class="container mt-4">
            <!-- Page Header -->
            <header class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h2 mb-2">
                            <span class="visually-hidden">Pagina: </span>
                            Progetti
                            <?php if ($search): ?>
                                <small class="text-muted">- Ricerca: "<?php echo htmlspecialchars($search); ?>"</small>
                            <?php endif; ?>
                        </h1>
                        <p class="lead text-muted mb-0">
                            <?php echo number_format($total_projects); ?> progetti trovati
                            <?php if ($category && $category !== 'all'): ?>
                                nella categoria <?php echo htmlspecialchars($category); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="create.php" class="btn btn-primary btn-lg" role="button">
                            <span aria-hidden="true">+</span> Crea Progetto
                        </a>
                    </div>
                </div>
            </header>

            <!-- Filters Section -->
            <section id="filters" class="filter-section" role="search" aria-labelledby="filters-heading">
                <h2 id="filters-heading" class="h5 mb-3">Filtra e Ordina Progetti</h2>
                
                <form method="GET" action="" class="row g-3" role="form" aria-label="Filtri progetti">
                    <!-- Search Input -->
                    <div class="col-md-4">
                        <label for="search-input" class="form-label">Cerca progetti</label>
                        <div class="input-group">
                            <input 
                                type="text" 
                                id="search-input"
                                name="search" 
                                class="form-control" 
                                placeholder="Titolo o descrizione..."
                                value="<?php echo htmlspecialchars($search); ?>"
                                aria-describedby="search-help"
                            >
                            <button 
                                type="submit" 
                                class="btn btn-outline-secondary"
                                aria-label="Avvia ricerca"
                            >
                                <span aria-hidden="true">🔍</span>
                            </button>
                        </div>
                        <div id="search-help" class="form-text">
                            Cerca per titolo o descrizione del progetto
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="col-md-2">
                        <label for="category-select" class="form-label">Categoria</label>
                        <select 
                            id="category-select"
                            name="category" 
                            class="form-select"
                            aria-describedby="category-help"
                        >
                            <option value="all" <?php echo $category === 'all' || !$category ? 'selected' : ''; ?>>
                                Tutte le categorie
                            </option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($cat)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="category-help" class="form-text">
                            Filtra per tipo di progetto
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label for="status-select" class="form-label">Stato</label>
                        <select 
                            id="status-select"
                            name="status" 
                            class="form-select"
                            aria-describedby="status-help"
                        >
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>
                                Tutti gli stati
                            </option>
                            <option value="attivo" <?php echo $status === 'attivo' ? 'selected' : ''; ?>>
                                Attivi
                            </option>
                            <option value="completato" <?php echo $status === 'completato' ? 'selected' : ''; ?>>
                                Completati
                            </option>
                            <option value="scaduto" <?php echo $status === 'scaduto' ? 'selected' : ''; ?>>
                                Scaduti
                            </option>
                        </select>
                        <div id="status-help" class="form-text">
                            Filtra per stato del progetto
                        </div>
                    </div>

                    <!-- Sort Options -->
                    <div class="col-md-2">
                        <label for="sort-select" class="form-label">Ordina per</label>
                        <select 
                            id="sort-select"
                            name="sort" 
                            class="form-select"
                            aria-describedby="sort-help"
                        >
                            <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>
                                Più recenti
                            </option>
                            <option value="funding" <?php echo $sort === 'funding' ? 'selected' : ''; ?>>
                                Più finanziati
                            </option>
                            <option value="deadline" <?php echo $sort === 'deadline' ? 'selected' : ''; ?>>
                                Scadenza
                            </option>
                            <option value="alphabetical" <?php echo $sort === 'alphabetical' ? 'selected' : ''; ?>>
                                Alfabetico
                            </option>
                        </select>
                        <div id="sort-help" class="form-text">
                            Scegli l'ordinamento
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="btn-group w-100" role="group" aria-label="Azioni filtri">
                            <button type="submit" class="btn btn-primary">
                                Applica Filtri
                            </button>
                            <a href="?" class="btn btn-outline-secondary" role="button">
                                Reset
                            </a>
                        </div>
                    </div>
                    
                    <!-- Hidden fields to preserve pagination -->
                    <input type="hidden" name="page" value="1">
                </form>
            </section>

            <!-- Results Summary -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div aria-live="polite" aria-atomic="true">
                            <span class="text-muted">
                                Showing <?php echo count($projects); ?> di <?php echo number_format($total_projects); ?> progetti
                                <?php if ($page > 1): ?>
                                    (Pagina <?php echo $page; ?> di <?php echo $total_pages; ?>)
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <!-- View Toggle (Future Enhancement) -->
                        <div class="btn-group" role="group" aria-label="Cambia vista">
                            <button type="button" class="btn btn-outline-secondary active" aria-pressed="true">
                                <span aria-hidden="true">⊞</span>
                                <span class="visually-hidden">Vista griglia</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" aria-pressed="false">
                                <span aria-hidden="true">☰</span>
                                <span class="visually-hidden">Vista lista</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Grid -->
            <section id="projects-grid" role="region" aria-labelledby="projects-heading">
                <h2 id="projects-heading" class="visually-hidden">Elenco progetti</h2>
                
                <?php if (empty($projects)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <span class="display-1" role="img" aria-label="Nessun risultato">🔍</span>
                        </div>
                        <h3 class="h4 text-muted mb-3">Nessun progetto trovato</h3>
                        <p class="text-muted mb-4">
                            <?php if ($search || $category !== 'all' || $status !== 'all'): ?>
                                Prova a modificare i filtri o <a href="?" class="text-decoration-none">rimuovi tutti i filtri</a>
                            <?php else: ?>
                                Non ci sono progetti disponibili al momento.
                            <?php endif; ?>
                        </p>
                        <a href="create.php" class="btn btn-primary" role="button">
                            Crea il primo progetto
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($projects as $index => $project): 
                            $progress_percent = min(100, $project['percentuale_completamento']);
                            $days_left = max(0, $project['giorni_rimanenti']);
                            $project_url = "detail.php?id=" . $project['id'];
                        ?>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <article 
                                    class="card project-card h-100" 
                                    role="article"
                                    aria-labelledby="project-title-<?php echo $project['id']; ?>"
                                    aria-describedby="project-summary-<?php echo $project['id']; ?>"
                                >
                                    <!-- Project Image -->
                                    <div class="position-relative">
                                        <?php if ($project['immagine_copertina']): ?>
                                            <img 
                                                src="../<?php echo htmlspecialchars($project['immagine_copertina']); ?>" 
                                                class="project-image card-img-top" 
                                                alt="Immagine di copertina per <?php echo htmlspecialchars($project['titolo']); ?>"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <div 
                                                class="project-image card-img-top bg-light d-flex align-items-center justify-content-center text-muted"
                                                role="img"
                                                aria-label="Nessuna immagine disponibile per <?php echo htmlspecialchars($project['titolo']); ?>"
                                            >
                                                <span class="display-4" aria-hidden="true">📷</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Status Badge -->
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span 
                                                class="badge status-badge status-<?php echo $project['stato']; ?>"
                                                role="status"
                                                aria-label="Stato progetto: <?php echo ucfirst($project['stato']); ?>"
                                            >
                                                <?php echo ucfirst($project['stato']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Project Content -->
                                    <div class="card-body d-flex flex-column">
                                        <!-- Project Header -->
                                        <header class="mb-3">
                                            <h3 id="project-title-<?php echo $project['id']; ?>" class="card-title h5 mb-2">
                                                <a 
                                                    href="<?php echo $project_url; ?>" 
                                                    class="text-decoration-none stretched-link"
                                                    aria-describedby="project-summary-<?php echo $project['id']; ?>"
                                                >
                                                    <?php echo htmlspecialchars($project['titolo']); ?>
                                                </a>
                                            </h3>
                                            
                                            <div class="project-meta text-muted small mb-2">
                                                <span class="me-3">
                                                    <span class="visually-hidden">Categoria: </span>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars(ucfirst($project['categoria'])); ?>
                                                    </span>
                                                </span>
                                                <span>
                                                    <span class="visually-hidden">Creatore: </span>
                                                    di <?php echo htmlspecialchars($project['creatore_nome']); ?>
                                                </span>
                                            </div>
                                        </header>

                                        <!-- Project Description -->
                                        <div id="project-summary-<?php echo $project['id']; ?>" class="mb-3 flex-grow-1">
                                            <p class="card-text text-muted">
                                                <?php echo htmlspecialchars(substr($project['descrizione'], 0, 120)); ?>
                                                <?php if (strlen($project['descrizione']) > 120): ?>...<?php endif; ?>
                                            </p>
                                        </div>

                                        <!-- Progress Section -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-semibold text-success">
                                                    €<?php echo number_format($project['finanziamento_attuale'], 0, ',', '.'); ?>
                                                </span>
                                                <span class="text-muted small">
                                                    <?php echo number_format($progress_percent, 1); ?>%
                                                </span>
                                            </div>
                                            
                                            <div 
                                                class="progress mb-2"
                                                role="progressbar" 
                                                aria-label="Progresso finanziamento progetto <?php echo htmlspecialchars($project['titolo']); ?>"
                                                aria-valuenow="<?php echo $progress_percent; ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100"
                                                aria-valuetext="<?php echo number_format($progress_percent, 1); ?>% dell'obiettivo raggiunto"
                                            >
                                                <div 
                                                    class="progress-bar bg-success" 
                                                    style="width: <?php echo $progress_percent; ?>%"
                                                    aria-hidden="true"
                                                ></div>
                                            </div>
                                            
                                            <div class="row text-center project-stats">
                                                <div class="col-4">
                                                    <div class="fw-semibold">€<?php echo number_format($project['obiettivo_finanziario'], 0, ',', '.'); ?></div>
                                                    <div class="small text-muted">Obiettivo</div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="fw-semibold">
                                                        <?php echo $days_left; ?>
                                                    </div>
                                                    <div class="small text-muted">
                                                        <?php echo $days_left === 1 ? 'Giorno' : 'Giorni'; ?> rimasti
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="fw-semibold">
                                                        <?php 
                                                        $creation_date = new DateTime($project['data_inserimento']);
                                                        echo $creation_date->format('d/m/Y');
                                                        ?>
                                                    </div>
                                                    <div class="small text-muted">Creato</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav id="pagination" aria-label="Navigazione pagine progetti" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a 
                                    class="page-link" 
                                    href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                                    aria-label="Pagina precedente"
                                >
                                    <span aria-hidden="true">&laquo;</span>
                                    <span class="visually-hidden">Precedente</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-hidden="true">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a 
                                    class="page-link" 
                                    href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"
                                    aria-label="Vai alla prima pagina"
                                >1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php if ($i === $page): ?>
                                    <span 
                                        class="page-link" 
                                        aria-current="page"
                                        aria-label="Pagina corrente, pagina <?php echo $i; ?>"
                                    >
                                        <?php echo $i; ?>
                                    </span>
                                <?php else: ?>
                                    <a 
                                        class="page-link" 
                                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                        aria-label="Vai alla pagina <?php echo $i; ?>"
                                    >
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a 
                                    class="page-link" 
                                    href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"
                                    aria-label="Vai all'ultima pagina"
                                >
                                    <?php echo $total_pages; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Next Page -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a 
                                    class="page-link" 
                                    href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                                    aria-label="Pagina successiva"
                                >
                                    <span class="visually-hidden">Successiva</span>
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-hidden="true">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Accessibility Enhancement Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhance form accessibility
            const form = document.querySelector('form[role="form"]');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // Auto-submit on select change (optional, with delay for accessibility)
            const selects = form.querySelectorAll('select');
            let submitTimeout;
            
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    clearTimeout(submitTimeout);
                    submitTimeout = setTimeout(() => {
                        // Announce change to screen readers
                        const liveRegion = document.createElement('div');
                        liveRegion.setAttribute('aria-live', 'polite');
                        liveRegion.setAttribute('aria-atomic', 'true');
                        liveRegion.className = 'visually-hidden';
                        liveRegion.textContent = 'Filtro modificato. Premi Invio per applicare o continua a modificare.';
                        document.body.appendChild(liveRegion);
                        
                        setTimeout(() => {
                            document.body.removeChild(liveRegion);
                        }, 3000);
                    }, 1000);
                });
            });
            
            // Enhanced keyboard navigation for project cards
            const projectCards = document.querySelectorAll('.project-card');
            
            projectCards.forEach(card => {
                const link = card.querySelector('.stretched-link');
                
                // Ensure card is keyboard accessible
                card.setAttribute('tabindex', '0');
                
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        link.click();
                    }
                });
                
                // Enhanced focus management
                card.addEventListener('focus', function() {
                    this.style.outline = '3px solid var(--bs-primary)';
                    this.style.outlineOffset = '2px';
                });
                
                card.addEventListener('blur', function() {
                    this.style.outline = '';
                    this.style.outlineOffset = '';
                });
            });
            
            // View toggle functionality (placeholder for future enhancement)
            const viewToggleBtns = document.querySelectorAll('[aria-label="Cambia vista"] button');
            
            viewToggleBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Update pressed states
                    viewToggleBtns.forEach(b => {
                        b.setAttribute('aria-pressed', 'false');
                        b.classList.remove('active');
                    });
                    
                    this.setAttribute('aria-pressed', 'true');
                    this.classList.add('active');
                    
                    // Announce view change
                    const viewType = this.querySelector('.visually-hidden').textContent;
                    const announcement = document.createElement('div');
                    announcement.setAttribute('aria-live', 'polite');
                    announcement.className = 'visually-hidden';
                    announcement.textContent = `Vista cambiata in: ${viewType}`;
                    document.body.appendChild(announcement);
                    
                    setTimeout(() => {
                        document.body.removeChild(announcement);
                    }, 2000);
                });
            });
            
            // Focus management for pagination
            const paginationLinks = document.querySelectorAll('#pagination .page-link');
            
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Store scroll position
                    sessionStorage.setItem('projectsScrollPosition', window.pageYOffset);
                });
            });
            
            // Restore scroll position after pagination navigation
            const savedScrollPosition = sessionStorage.getItem('projectsScrollPosition');
            if (savedScrollPosition) {
                window.scrollTo(0, parseInt(savedScrollPosition));
                sessionStorage.removeItem('projectsScrollPosition');
            }
            
            // Announce filter results
            const resultsCount = document.querySelector('[aria-live="polite"]');
            if (resultsCount && window.location.search.includes('search=')) {
                setTimeout(() => {
                    const announcement = document.createElement('div');
                    announcement.setAttribute('aria-live', 'assertive');
                    announcement.className = 'visually-hidden';
                    announcement.textContent = resultsCount.textContent + ' per la ricerca effettuata.';
                    document.body.appendChild(announcement);
                    
                    setTimeout(() => {
                        document.body.removeChild(announcement);
                    }, 3000);
                }, 1000);
            }
        });
    </script>
</body>
</html>
