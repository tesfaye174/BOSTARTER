<?php
/**
 * Project Detail - Accessible View
 * BOSTARTER - Crowdfunding Platform
 * Enhanced with WCAG 2.1 AA Accessibility Features
 */

session_start();
require_once '../../backend/config/database.php';
require_once '../../backend/services/MongoLogger.php';

$database = Database::getInstance();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$project_id) {
    header("Location: view_projects_accessible.php");
    exit();
}

// Get project details with enhanced data
$project_query = "SELECT p.*, u.username as creator_name, u.email as creator_email,
                         COALESCE(pf.total_funded, 0) as total_funded,
                         COALESCE(pf.funding_percentage, 0) as funding_percentage,
                         COALESCE(pf.backers_count, 0) as backers_count,
                         DATEDIFF(p.deadline, NOW()) as days_left,
                         DATE_FORMAT(p.deadline, '%d/%m/%Y') as formatted_deadline,
                         DATE_FORMAT(p.data_inserimento, '%d/%m/%Y') as formatted_creation
                  FROM progetti p
                  JOIN utenti u ON p.creatore_id = u.id
                  LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.id = pf.project_id
                  WHERE p.id = :project_id";

$project_stmt = $db->prepare($project_query);
$project_stmt->bindParam(':project_id', $project_id);
$project_stmt->execute();
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: view_projects_accessible.php");
    exit();
}

// Log project view activity
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$mongoLogger->logActivity($user_id, 'project_view', [
    'timestamp' => date('Y-m-d H:i:s'),
    'project_id' => $project_id,
    'project_title' => $project['titolo'],
    'creator_id' => $project['creatore_id']
]);

// Calculate progress and status
$progress_percent = min(100, round(($project['finanziamento_attuale'] / $project['obiettivo_finanziario']) * 100, 1));
$days_left = max(0, $project['days_left']);
$is_funded = $progress_percent >= 100;
$is_expired = $days_left <= 0;

// Get project updates/news (if available)
$updates_query = "SELECT * FROM project_updates WHERE project_id = :project_id ORDER BY created_at DESC LIMIT 5";
try {
    $updates_stmt = $db->prepare($updates_query);
    $updates_stmt->bindParam(':project_id', $project_id);
    $updates_stmt->execute();
    $updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $updates = [];
}

// Get similar projects
$similar_query = "SELECT p.id, p.titolo, p.immagine_copertina, p.finanziamento_attuale, p.obiettivo_finanziario
                  FROM progetti p 
                  WHERE p.categoria = :category AND p.id != :project_id AND p.stato = 'attivo'
                  LIMIT 3";
$similar_stmt = $db->prepare($similar_query);
$similar_stmt->bindParam(':category', $project['categoria']);
$similar_stmt->bindParam(':project_id', $project_id);
$similar_stmt->execute();
$similar_projects = $similar_stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title for SEO and accessibility
$page_title = htmlspecialchars($project['titolo']) . " - Progetto di " . htmlspecialchars($project['creator_name']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - BOSTARTER</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($project['descrizione'], 0, 155)); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($project['creator_name']); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($project['descrizione'], 0, 155)); ?>">
    <meta property="og:image" content="<?php echo $project['immagine_copertina'] ? '../' . htmlspecialchars($project['immagine_copertina']) : '../images/default-project.jpg'; ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
    <meta property="twitter:title" content="<?php echo $page_title; ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars(substr($project['descrizione'], 0, 155)); ?>">
    <meta property="twitter:image" content="<?php echo $project['immagine_copertina'] ? '../' . htmlspecialchars($project['immagine_copertina']) : '../images/default-project.jpg'; ?>">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": "<?php echo htmlspecialchars($project['titolo']); ?>",
        "description": "<?php echo htmlspecialchars($project['descrizione']); ?>",
        "image": "<?php echo $project['immagine_copertina'] ? '../' . htmlspecialchars($project['immagine_copertina']) : '../images/default-project.jpg'; ?>",
        "category": "<?php echo htmlspecialchars($project['categoria']); ?>",
        "offers": {
            "@type": "Offer",
            "price": "<?php echo $project['obiettivo_finanziario']; ?>",
            "priceCurrency": "EUR",
            "availability": "<?php echo $is_expired ? 'OutOfStock' : 'InStock'; ?>"
        },
        "brand": {
            "@type": "Organization",
            "name": "BOSTARTER"
        },
        "creator": {
            "@type": "Person",
            "name": "<?php echo htmlspecialchars($project['creator_name']); ?>"
        }
    }
    </script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/accessibility.css" rel="stylesheet">
    
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .project-image-container {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .project-main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        
        .funding-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #e9ecef;
            transition: border-color 0.3s ease;
        }
        
        .funding-card:hover,
        .funding-card:focus-within {
            border-color: var(--bs-primary);
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            margin: 0.5rem 0;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .progress-enhanced {
            height: 12px;
            border-radius: 6px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-fund {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-fund:hover {
            background: linear-gradient(45deg, #218838, #1ea085);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .creator-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            border-left: 4px solid var(--bs-primary);
        }
        
        .tab-content {
            min-height: 300px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }
        
        .breadcrumb-item a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: white;
        }
        
        .breadcrumb-item.active {
            color: rgba(255,255,255,0.6);
        }
    </style>
</head>
<body>
    <!-- Skip Links -->
    <div class="skip-links">
        <a href="#main-content" class="sr-only-focusable">Salta al contenuto principale</a>
        <a href="#project-details" class="sr-only-focusable">Salta ai dettagli del progetto</a>
        <a href="#funding-section" class="sr-only-focusable">Salta alla sezione finanziamento</a>
        <a href="#project-tabs" class="sr-only-focusable">Salta alle schede informative</a>
    </div>

    <?php require_once '../components/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section" role="banner" aria-labelledby="project-hero-title">
        <div class="container">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="Navigazione breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="../index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="view_projects_accessible.php">Progetti</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="view_projects_accessible.php?category=<?php echo urlencode($project['categoria']); ?>">
                            <?php echo htmlspecialchars(ucfirst($project['categoria'])); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($project['titolo']); ?>
                    </li>
                </ol>
            </nav>
            
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 id="project-hero-title" class="display-5 fw-bold mb-3">
                        <?php echo htmlspecialchars($project['titolo']); ?>
                    </h1>
                    <p class="lead mb-4">
                        <?php echo htmlspecialchars(substr($project['descrizione'], 0, 150)); ?>
                        <?php if (strlen($project['descrizione']) > 150): ?>...<?php endif; ?>
                    </p>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <span class="badge bg-light text-dark fs-6 px-3 py-2">
                            <span class="visually-hidden">Categoria: </span>
                            <?php echo htmlspecialchars(ucfirst($project['categoria'])); ?>
                        </span>
                        <span class="badge <?php echo $is_expired ? 'bg-danger' : ($is_funded ? 'bg-success' : 'bg-warning text-dark'); ?> fs-6 px-3 py-2">
                            <span class="visually-hidden">Stato: </span>
                            <?php 
                            if ($is_expired) echo "Scaduto";
                            elseif ($is_funded) echo "Finanziato";
                            else echo "Attivo";
                            ?>
                        </span>
                        <span class="text-light">
                            <span class="visually-hidden">Creato da: </span>
                            di <strong><?php echo htmlspecialchars($project['creator_name']); ?></strong>
                        </span>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <!-- Quick Stats -->
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo number_format($progress_percent, 1); ?>%</span>
                                <span class="stat-label">Finanziato</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $days_left; ?></span>
                                <span class="stat-label"><?php echo $days_left === 1 ? 'Giorno' : 'Giorni'; ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo number_format($project['backers_count'] ?? 0); ?></span>
                                <span class="stat-label">Sostenitori</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main id="main-content" role="main">
        <div class="container">
            <div class="row g-4">
                <!-- Project Content -->
                <div class="col-lg-8">
                    <!-- Project Image -->
                    <section id="project-details" aria-labelledby="project-image-heading">
                        <h2 id="project-image-heading" class="visually-hidden">Immagine del progetto</h2>
                        <div class="project-image-container mb-4">
                            <?php if ($project['immagine_copertina']): ?>
                                <img 
                                    src="../<?php echo htmlspecialchars($project['immagine_copertina']); ?>" 
                                    class="project-main-image" 
                                    alt="Immagine principale del progetto: <?php echo htmlspecialchars($project['titolo']); ?>"
                                    loading="eager"
                                >
                            <?php else: ?>
                                <div 
                                    class="project-main-image bg-light d-flex align-items-center justify-content-center text-muted"
                                    role="img"
                                    aria-label="Nessuna immagine disponibile per il progetto <?php echo htmlspecialchars($project['titolo']); ?>"
                                >
                                    <div class="text-center">
                                        <span class="display-1" aria-hidden="true">📷</span>
                                        <p class="mt-2 mb-0">Nessuna immagine disponibile</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Project Information Tabs -->
                    <section id="project-tabs" aria-labelledby="project-tabs-heading">
                        <h2 id="project-tabs-heading" class="visually-hidden">Informazioni dettagliate del progetto</h2>
                        
                        <ul class="nav nav-tabs" role="tablist" aria-labelledby="project-tabs-heading">
                            <li class="nav-item" role="presentation">
                                <button 
                                    class="nav-link active" 
                                    id="description-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#description-panel" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="description-panel" 
                                    aria-selected="true"
                                >
                                    Descrizione
                                </button>
                            </li>
                            <?php if (!empty($updates)): ?>
                            <li class="nav-item" role="presentation">
                                <button 
                                    class="nav-link" 
                                    id="updates-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#updates-panel" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="updates-panel" 
                                    aria-selected="false"
                                >
                                    Aggiornamenti <span class="badge bg-primary ms-1"><?php echo count($updates); ?></span>
                                </button>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item" role="presentation">
                                <button 
                                    class="nav-link" 
                                    id="creator-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#creator-panel" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="creator-panel" 
                                    aria-selected="false"
                                >
                                    Creatore
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button 
                                    class="nav-link" 
                                    id="faq-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#faq-panel" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="faq-panel" 
                                    aria-selected="false"
                                >
                                    FAQ
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-4" id="project-tabs-content">
                            <!-- Description Panel -->
                            <div 
                                class="tab-pane fade show active" 
                                id="description-panel" 
                                role="tabpanel" 
                                aria-labelledby="description-tab"
                            >
                                <h3 class="h4 mb-3">Descrizione del Progetto</h3>
                                <div class="prose">
                                    <?php echo nl2br(htmlspecialchars($project['descrizione'])); ?>
                                </div>
                                
                                <!-- Project Details -->
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <h4 class="h6 text-muted mb-2">Informazioni Progetto</h4>
                                        <dl class="row">
                                            <dt class="col-sm-5">Data Creazione:</dt>
                                            <dd class="col-sm-7"><?php echo $project['formatted_creation']; ?></dd>
                                            
                                            <dt class="col-sm-5">Scadenza:</dt>
                                            <dd class="col-sm-7">
                                                <time datetime="<?php echo $project['deadline']; ?>">
                                                    <?php echo $project['formatted_deadline']; ?>
                                                </time>
                                            </dd>
                                            
                                            <dt class="col-sm-5">Categoria:</dt>
                                            <dd class="col-sm-7"><?php echo htmlspecialchars(ucfirst($project['categoria'])); ?></dd>
                                            
                                            <dt class="col-sm-5">Stato:</dt>
                                            <dd class="col-sm-7">
                                                <span class="badge <?php echo $is_expired ? 'bg-danger' : ($is_funded ? 'bg-success' : 'bg-warning text-dark'); ?>">
                                                    <?php 
                                                    if ($is_expired) echo "Scaduto";
                                                    elseif ($is_funded) echo "Finanziato";
                                                    else echo "Attivo";
                                                    ?>
                                                </span>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <!-- Updates Panel -->
                            <?php if (!empty($updates)): ?>
                            <div 
                                class="tab-pane fade" 
                                id="updates-panel" 
                                role="tabpanel" 
                                aria-labelledby="updates-tab"
                            >
                                <h3 class="h4 mb-3">Aggiornamenti del Progetto</h3>
                                <div class="timeline">
                                    <?php foreach ($updates as $update): ?>
                                    <article class="update-item border-start border-3 ps-3 mb-4">
                                        <header class="update-header mb-2">
                                            <h4 class="h6 mb-1"><?php echo htmlspecialchars($update['title']); ?></h4>
                                            <time class="text-muted small" datetime="<?php echo $update['created_at']; ?>">
                                                <?php echo date('d/m/Y H:i', strtotime($update['created_at'])); ?>
                                            </time>
                                        </header>
                                        <div class="update-content">
                                            <?php echo nl2br(htmlspecialchars($update['content'])); ?>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Creator Panel -->
                            <div 
                                class="tab-pane fade" 
                                id="creator-panel" 
                                role="tabpanel" 
                                aria-labelledby="creator-tab"
                            >
                                <h3 class="h4 mb-3">Informazioni sul Creatore</h3>
                                <div class="creator-info">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div 
                                                class="avatar-placeholder bg-primary text-white d-flex align-items-center justify-content-center"
                                                style="width: 60px; height: 60px; border-radius: 50%;"
                                                role="img"
                                                aria-label="Avatar di <?php echo htmlspecialchars($project['creator_name']); ?>"
                                            >
                                                <span class="h4 mb-0">
                                                    <?php echo strtoupper(substr($project['creator_name'], 0, 2)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <h4 class="h5 mb-1"><?php echo htmlspecialchars($project['creator_name']); ?></h4>
                                            <p class="text-muted mb-2">Creatore del progetto</p>
                                            <p class="mb-0">
                                                Membro di BOSTARTER dal <?php echo $project['formatted_creation']; ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <h5 class="h6">Contatta il Creatore</h5>
                                        <p class="small text-muted">
                                            Hai domande su questo progetto? Contatta direttamente il creatore.
                                        </p>
                                        <a 
                                            href="mailto:<?php echo htmlspecialchars($project['creator_email']); ?>?subject=Domanda su <?php echo urlencode($project['titolo']); ?>"
                                            class="btn btn-outline-primary btn-sm"
                                            role="button"
                                        >
                                            Invia Messaggio
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ Panel -->
                            <div 
                                class="tab-pane fade" 
                                id="faq-panel" 
                                role="tabpanel" 
                                aria-labelledby="faq-tab"
                            >
                                <h3 class="h4 mb-3">Domande Frequenti</h3>
                                <div class="accordion" id="faq-accordion">
                                    <div class="accordion-item">
                                        <h4 class="accordion-header" id="faq-heading-1">
                                            <button 
                                                class="accordion-button collapsed" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#faq-collapse-1" 
                                                aria-expanded="false" 
                                                aria-controls="faq-collapse-1"
                                            >
                                                Come funziona il finanziamento?
                                            </button>
                                        </h4>
                                        <div 
                                            id="faq-collapse-1" 
                                            class="accordion-collapse collapse" 
                                            aria-labelledby="faq-heading-1" 
                                            data-bs-parent="#faq-accordion"
                                        >
                                            <div class="accordion-body">
                                                Il progetto deve raggiungere l'obiettivo di finanziamento entro la scadenza. 
                                                Solo se l'obiettivo viene raggiunto, i fondi vengono trasferiti al creatore 
                                                e il progetto può procedere.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="accordion-item">
                                        <h4 class="accordion-header" id="faq-heading-2">
                                            <button 
                                                class="accordion-button collapsed" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#faq-collapse-2" 
                                                aria-expanded="false" 
                                                aria-controls="faq-collapse-2"
                                            >
                                                Cosa succede se il progetto non raggiunge l'obiettivo?
                                            </button>
                                        </h4>
                                        <div 
                                            id="faq-collapse-2" 
                                            class="accordion-collapse collapse" 
                                            aria-labelledby="faq-heading-2" 
                                            data-bs-parent="#faq-accordion"
                                        >
                                            <div class="accordion-body">
                                                Se il progetto non raggiunge l'obiettivo di finanziamento entro la scadenza, 
                                                tutti i contributori riceveranno un rimborso completo.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="accordion-item">
                                        <h4 class="accordion-header" id="faq-heading-3">
                                            <button 
                                                class="accordion-button collapsed" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#faq-collapse-3" 
                                                aria-expanded="false" 
                                                aria-controls="faq-collapse-3"
                                            >
                                                Posso modificare o annullare il mio contributo?
                                            </button>
                                        </h4>
                                        <div 
                                            id="faq-collapse-3" 
                                            class="accordion-collapse collapse" 
                                            aria-labelledby="faq-heading-3" 
                                            data-bs-parent="#faq-accordion"
                                        >
                                            <div class="accordion-body">
                                                Puoi modificare o annullare il tuo contributo fino a 48 ore prima 
                                                della scadenza del progetto. Dopo questo termine, le modifiche non sono più possibili.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Funding Sidebar -->
                <div class="col-lg-4">
                    <aside id="funding-section" aria-labelledby="funding-heading">
                        <div class="funding-card p-4 sticky-top" style="top: 2rem;">
                            <h2 id="funding-heading" class="h4 mb-4">Sostieni il Progetto</h2>
                            
                            <!-- Funding Progress -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold fs-4 text-success">
                                        €<?php echo number_format($project['finanziamento_attuale'], 0, ',', '.'); ?>
                                    </span>
                                    <span class="text-muted">
                                        <?php echo number_format($progress_percent, 1); ?>%
                                    </span>
                                </div>
                                
                                <div 
                                    class="progress progress-enhanced mb-3"
                                    role="progressbar" 
                                    aria-label="Progresso finanziamento"
                                    aria-valuenow="<?php echo $progress_percent; ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100"
                                    aria-valuetext="<?php echo number_format($progress_percent, 1); ?>% dell'obiettivo di €<?php echo number_format($project['obiettivo_finanziario'], 0, ',', '.'); ?> raggiunto"
                                >
                                    <div 
                                        class="progress-bar bg-success" 
                                        style="width: <?php echo $progress_percent; ?>%"
                                        aria-hidden="true"
                                    ></div>
                                </div>
                                
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="fw-bold">€<?php echo number_format($project['obiettivo_finanziario'], 0, ',', '.'); ?></div>
                                        <div class="small text-muted">Obiettivo</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold"><?php echo number_format($project['backers_count'] ?? 0); ?></div>
                                        <div class="small text-muted">Sostenitori</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold"><?php echo $days_left; ?></div>
                                        <div class="small text-muted"><?php echo $days_left === 1 ? 'Giorno' : 'Giorni'; ?> rimasti</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Funding Button -->
                            <?php if (!$is_expired && !$is_funded): ?>
                                <a 
                                    href="fund.php?id=<?php echo $project_id; ?>" 
                                    class="btn btn-fund w-100 mb-3"
                                    role="button"
                                    aria-describedby="funding-help"
                                >
                                    <span class="fw-bold">Sostieni Questo Progetto</span>
                                </a>
                                <p id="funding-help" class="small text-muted text-center mb-3">
                                    Il pagamento verrà elaborato solo se il progetto raggiunge l'obiettivo
                                </p>
                            <?php elseif ($is_funded): ?>
                                <div class="alert alert-success text-center" role="status">
                                    <strong>🎉 Progetto Finanziato!</strong><br>
                                    Questo progetto ha raggiunto il suo obiettivo
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning text-center" role="status">
                                    <strong>⏰ Progetto Scaduto</strong><br>
                                    Il tempo per finanziare questo progetto è terminato
                                </div>
                            <?php endif; ?>

                            <!-- Quick Actions -->
                            <div class="d-grid gap-2">
                                <button 
                                    type="button" 
                                    class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#share-modal"
                                    aria-label="Condividi questo progetto"
                                >
                                    <span aria-hidden="true">📤</span> Condividi
                                </button>
                                <button 
                                    type="button" 
                                    class="btn btn-outline-secondary btn-sm"
                                    onclick="toggleFavorite(<?php echo $project_id; ?>)"
                                    aria-label="Aggiungi ai preferiti"
                                    aria-pressed="false"
                                    id="favorite-btn"
                                >
                                    <span aria-hidden="true">♡</span> Aggiungi ai Preferiti
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>

            <!-- Similar Projects -->
            <?php if (!empty($similar_projects)): ?>
            <section class="mt-5" aria-labelledby="similar-projects-heading">
                <h2 id="similar-projects-heading" class="h3 mb-4">Progetti Simili</h2>
                <div class="row g-4">
                    <?php foreach ($similar_projects as $similar): 
                        $similar_progress = min(100, ($similar['finanziamento_attuale'] / $similar['obiettivo_finanziario']) * 100);
                    ?>
                    <div class="col-md-4">
                        <article class="card h-100">
                            <?php if ($similar['immagine_copertina']): ?>
                                <img 
                                    src="../<?php echo htmlspecialchars($similar['immagine_copertina']); ?>" 
                                    class="card-img-top" 
                                    style="height: 200px; object-fit: cover;"
                                    alt="Immagine di copertina per <?php echo htmlspecialchars($similar['titolo']); ?>"
                                    loading="lazy"
                                >
                            <?php endif; ?>
                            <div class="card-body">
                                <h3 class="card-title h6">
                                    <a 
                                        href="detail_accessible.php?id=<?php echo $similar['id']; ?>"
                                        class="text-decoration-none stretched-link"
                                    >
                                        <?php echo htmlspecialchars($similar['titolo']); ?>
                                    </a>
                                </h3>
                                <div 
                                    class="progress mt-2"
                                    role="progressbar"
                                    aria-label="Progresso finanziamento progetto simile"
                                    aria-valuenow="<?php echo $similar_progress; ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                >
                                    <div 
                                        class="progress-bar bg-success" 
                                        style="width: <?php echo $similar_progress; ?>%"
                                    ></div>
                                </div>
                                <div class="small text-muted mt-1">
                                    <?php echo number_format($similar_progress, 1); ?>% finanziato
                                </div>
                            </div>
                        </article>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>

    <!-- Share Modal -->
    <div class="modal fade" id="share-modal" tabindex="-1" aria-labelledby="share-modal-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5" id="share-modal-title">Condividi Progetto</h3>
                    <button 
                        type="button" 
                        class="btn-close" 
                        data-bs-dismiss="modal" 
                        aria-label="Chiudi finestra di condivisione"
                    ></button>
                </div>
                <div class="modal-body">
                    <p>Condividi questo progetto sui social media o copia il link:</p>
                    <div class="d-grid gap-2">
                        <a 
                            href="https://facebook.com/sharer/sharer.php?u=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                            class="btn btn-primary"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Condividi su Facebook (opens in new tab)"
                        >
                            Facebook
                        </a>
                        <a 
                            href="https://twitter.com/intent/tweet?url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($project['titolo']); ?>" 
                            class="btn btn-info text-white"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Condividi su Twitter (opens in new tab)"
                        >
                            Twitter
                        </a>
                        <button 
                            type="button" 
                            class="btn btn-outline-secondary"
                            onclick="copyToClipboard()"
                            aria-label="Copia link negli appunti"
                        >
                            Copia Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../components/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Project Detail Enhancement Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced tab navigation with keyboard support
            const tabs = document.querySelectorAll('[role="tab"]');
            
            tabs.forEach(tab => {
                tab.addEventListener('keydown', function(e) {
                    const tabList = this.closest('[role="tablist"]');
                    const tabs = Array.from(tabList.querySelectorAll('[role="tab"]'));
                    const currentIndex = tabs.indexOf(this);
                    
                    let nextTab;
                    
                    switch(e.key) {
                        case 'ArrowLeft':
                            e.preventDefault();
                            nextTab = tabs[currentIndex - 1] || tabs[tabs.length - 1];
                            break;
                        case 'ArrowRight':
                            e.preventDefault();
                            nextTab = tabs[currentIndex + 1] || tabs[0];
                            break;
                        case 'Home':
                            e.preventDefault();
                            nextTab = tabs[0];
                            break;
                        case 'End':
                            e.preventDefault();
                            nextTab = tabs[tabs.length - 1];
                            break;
                    }
                    
                    if (nextTab) {
                        nextTab.focus();
                        nextTab.click();
                    }
                });
            });
            
            // Progress bar animation
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                const targetWidth = progressBar.style.width;
                progressBar.style.width = '0%';
                
                setTimeout(() => {
                    progressBar.style.width = targetWidth;
                }, 500);
            }
            
            // Sticky sidebar scroll behavior
            const sidebar = document.querySelector('.sticky-top');
            if (sidebar) {
                window.addEventListener('scroll', function() {
                    const scrolled = window.pageYOffset;
                    const rate = scrolled * -0.5;
                    
                    if (scrolled > 100) {
                        sidebar.style.transform = `translateY(${rate}px)`;
                    }
                });
            }
        });
        
        // Favorite functionality
        function toggleFavorite(projectId) {
            const btn = document.getElementById('favorite-btn');
            const icon = btn.querySelector('span[aria-hidden]');
            const isPressed = btn.getAttribute('aria-pressed') === 'true';
            
            // Toggle state
            btn.setAttribute('aria-pressed', !isPressed);
            icon.textContent = isPressed ? '♡' : '♥';
            btn.classList.toggle('btn-outline-secondary');
            btn.classList.toggle('btn-danger');
            
            // Announce to screen readers
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.className = 'visually-hidden';
            announcement.textContent = isPressed ? 'Rimosso dai preferiti' : 'Aggiunto ai preferiti';
            document.body.appendChild(announcement);
            
            setTimeout(() => {
                document.body.removeChild(announcement);
            }, 2000);
            
            // Here you would typically make an AJAX call to save the preference
            console.log(`Project ${projectId} ${isPressed ? 'removed from' : 'added to'} favorites`);
        }
        
        // Copy to clipboard functionality
        function copyToClipboard() {
            const url = window.location.href;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    showCopySuccess();
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showCopySuccess();
            }
        }
        
        function showCopySuccess() {
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.className = 'visually-hidden';
            announcement.textContent = 'Link copiato negli appunti';
            document.body.appendChild(announcement);
            
            // Visual feedback
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Copiato!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-secondary');
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
                document.body.removeChild(announcement);
            }, 2000);
        }
        
        // Enhanced accordion accessibility
        const accordionButtons = document.querySelectorAll('.accordion-button');
        
        accordionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const expanded = this.getAttribute('aria-expanded') === 'true';
                
                // Announce state change
                setTimeout(() => {
                    const announcement = document.createElement('div');
                    announcement.setAttribute('aria-live', 'polite');
                    announcement.className = 'visually-hidden';
                    announcement.textContent = expanded ? 'Sezione chiusa' : 'Sezione aperta';
                    document.body.appendChild(announcement);
                    
                    setTimeout(() => {
                        document.body.removeChild(announcement);
                    }, 1000);
                }, 300);
            });
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Set focus for accessibility
                    setTimeout(() => {
                        target.focus();
                    }, 500);
                }
            });
        });
    </script>
</body>
</html>
