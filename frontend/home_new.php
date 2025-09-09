<?php
require_once __DIR__ . '/includes/init.php';

$page_title = 'Home';

// Initialize CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to validate and sanitize input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Process GET parameters
$get = [
    'logout' => isset($_GET['logout']) ? sanitizeInput($_GET['logout']) : '',
    'user' => isset($_GET['user']) ? sanitizeInput($_GET['user']) : ''
];

// Set logout message if needed
$logout_message = '';
if ($get['logout'] === 'success') {
    $logout_message = !empty($get['user']) 
        ? "Arrivederci " . $get['user'] . "! Grazie per aver utilizzato BOSTARTER."
        : "Logout effettuato con successo! Grazie per aver utilizzato BOSTARTER.";
}

// Initialize data arrays
$progetti_evidenza = [];
$stats = [
    'totale_progetti' => 0,
    'progetti_attivi' => 0,
    'totale_raccolto' => 0,
    'totale_utenti' => 0
];

// Database operations
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get featured projects
    $stmt = $conn->query("
        SELECT p.id, p.titolo, p.descrizione, p.immagine, 
               p.budget_richiesto, COALESCE(p.budget_raccolto, 0) as raccolti,
               p.data_fine, LOWER(p.tipo_progetto) as tipo,
               u.nickname as creatore_nickname
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        WHERE p.stato = 'ATTIVO'
        ORDER BY p.data_inserimento DESC
        LIMIT 3
    ");
    $progetti_evidenza = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats['totale_progetti'] = $conn->query("SELECT COUNT(*) FROM progetti")->fetchColumn();
    $stats['progetti_attivi'] = $conn->query("SELECT COUNT(*) FROM progetti WHERE stato = 'ATTIVO'")->fetchColumn();
    $stats['totale_raccolto'] = $conn->query("SELECT COALESCE(SUM(importo), 0) FROM finanziamenti WHERE stato = 'COMPLETATO'")->fetchColumn();
    $stats['totale_utenti'] = $conn->query("SELECT COUNT(*) FROM utenti")->fetchColumn();
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
}

// Include header
include __DIR__ . '/includes/head.php';
?>

<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container py-4">
        <?php if ($logout_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= $logout_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Hero Section -->
        <div class="bg-primary text-white p-5 rounded-3 mb-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Trasforma le tue idee in realtà</h1>
                    <p class="lead mb-4">
                        BOSTARTER è la piattaforma di crowdfunding dedicata a progetti innovativi
                        hardware e software. Unisciti alla community e finanzia il futuro!
                    </p>
                    <div class="d-flex gap-3">
                        <?php if (isLoggedIn()): ?>
                            <a href="new.php" class="btn btn-warning btn-lg px-4">
                                <i class="fas fa-plus me-2"></i>Crea Progetto
                            </a>
                        <?php else: ?>
                            <a href="auth/signup.php" class="btn btn-warning btn-lg px-4">
                                <i class="fas fa-user-plus me-2"></i>Registrati
                            </a>
                        <?php endif; ?>
                        <a href="view.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-search me-2"></i>Esplora Progetti
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="images/creative.svg" alt="Innovazione" class="img-fluid">
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-project-diagram text-primary fa-2x"></i>
                        </div>
                        <h3 class="fw-bold mb-1"><?= number_format($stats['totale_progetti']) ?></h3>
                        <p class="text-muted mb-0">Progetti Totali</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-rocket text-success fa-2x"></i>
                        </div>
                        <h3 class="fw-bold mb-1"><?= number_format($stats['progetti_attivi']) ?></h3>
                        <p class="text-muted mb-0">Progetti Attivi</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-euro-sign text-warning fa-2x"></i>
                        </div>
                        <h3 class="fw-bold mb-1">€<?= number_format($stats['totale_raccolto']) ?></h3>
                        <p class="text-muted mb-0">Fondi Raccolti</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-users text-info fa-2x"></i>
                        </div>
                        <h3 class="fw-bold mb-1"><?= number_format($stats['totale_utenti']) ?></h3>
                        <p class="text-muted mb-0">Utenti Registrati</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Projects -->
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">🔥 Progetti in Evidenza</h2>
                <a href="view.php" class="btn btn-outline-primary">
                    Vedi tutti <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>

            <?php if (empty($progetti_evidenza)): ?>
            <div class="text-center py-5 bg-light rounded-3">
                <i class="fas fa-lightbulb fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Nessun progetto al momento</h4>
                <p class="text-muted mb-4">Sii il primo a creare un progetto innovativo!</p>
                <?php if (isLoggedIn()): ?>
                <a href="new.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Crea il Primo Progetto
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($progetti_evidenza as $progetto): 
                    $percentuale = $progetto['budget_richiesto'] > 0 ? 
                        min(100, ($progetto['raccolti'] / $progetto['budget_richiesto']) * 100) : 0;
                    $giorni_rimanenti = ceil((strtotime($progetto['data_fine']) - time()) / 86400);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <?php if (!empty($progetto['immagine'])): ?>
                        <img src="uploads/<?= htmlspecialchars($progetto['immagine']) ?>" class="card-img-top"
                            style="height: 200px; object-fit: cover;" alt="<?= htmlspecialchars($progetto['titolo']) ?>">
                        <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                            style="height: 200px;">
                            <i class="fas fa-<?= $progetto['tipo'] === 'hardware' ? 'cog' : 'code' ?> fa-4x text-muted"></i>
                        </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-<?= $progetto['tipo'] === 'hardware' ? 'primary' : 'info' ?> rounded-pill px-3">
                                    <?= ucfirst($progetto['tipo']) ?>
                                </span>
                                <small class="text-muted">
                                    <i class="far fa-clock me-1"></i>
                                    <?= $giorni_rimanenti > 0 ? $giorni_rimanenti . ' giorni' : 'Scaduto' ?>
                                </small>
                            </div>

                            <h5 class="card-title mb-2"><?= htmlspecialchars($progetto['titolo']) ?></h5>
                            <p class="card-text text-muted flex-grow-1 mb-3">
                                <?= htmlspecialchars(substr($progetto['descrizione'], 0, 100)) ?>...
                            </p>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Raccolto: <?= number_format($percentuale, 1) ?>%</small>
                                    <small class="text-muted">€<?= number_format($progetto['raccolti']) ?> di €<?= number_format($progetto['budget_richiesto']) ?></small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $percentuale ?>%" 
                                         aria-valuenow="<?= $percentuale ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle fa-lg text-muted me-2"></i>
                                    <small class="text-muted"><?= htmlspecialchars($progetto['creatore_nickname']) ?></small>
                                </div>
                                <a href="view.php?id=<?= $progetto['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    Dettagli <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- How It Works -->
        <div class="bg-light p-5 rounded-3 mb-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Come Funziona BOSTARTER</h2>
                <p class="lead text-muted">Semplice, veloce e alla portata di tutti</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 bg-white shadow-sm p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-lightbulb text-primary"></i>
                            </div>
                            <h4 class="mb-0">1. Crea</h4>
                        </div>
                        <p class="text-muted mb-0">
                            Presenta la tua idea innovativa con descrizioni dettagliate,
                            immagini e obiettivi di finanziamento chiari.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 bg-white shadow-sm p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-share-alt text-success"></i>
                            </div>
                            <h4 class="mb-0">2. Condividi</h4>
                        </div>
                        <p class="text-muted mb-0">
                            Promuovi il tuo progetto nella community e raggiungi
                            potenziali sostenitori interessati alle tue idee.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 bg-white shadow-sm p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-rocket text-warning"></i>
                            </div>
                            <h4 class="mb-0">3. Realizza</h4>
                        </div>
                        <p class="text-muted mb-0">
                            Raccogli i fondi necessari e trasforma la tua idea
                            in un prodotto reale con il supporto della community.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="<?= isLoggedIn() ? 'new.php' : 'auth/signup.php' ?>" class="btn btn-primary btn-lg px-4">
                    <i class="fas fa-rocket me-2"></i>Inizia il tuo viaggio
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- Scripts -->
    <?php include __DIR__ . '/includes/scripts.php'; ?>
    
    <style>
    .hover-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
    }
    
    .progress {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-bar {
        background: linear-gradient(90deg, #2563eb, #7c3aed);
    }
    
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 500;
    }
    
    .btn-primary {
        background: linear-gradient(90deg, #2563eb, #7c3aed);
        border: none;
    }
    
    .btn-primary:hover {
        background: linear-gradient(90deg, #1d4ed8, #6d28d9);
    }
    </style>
    
    <script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</body>
</html>
