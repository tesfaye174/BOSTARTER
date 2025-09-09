<?php
// Homepage principale
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

// Validate and sanitize GET parameters
$get = [];
$get['logout'] = isset($_GET['logout']) ? sanitizeInput($_GET['logout']) : '';
$get['user'] = isset($_GET['user']) ? sanitizeInput($_GET['user']) : '';

// Prepare logout message with validation
$logout_message = '';
if ($get['logout'] === 'success') {
    $logout_message = !empty($get['user']) 
        ? "Arrivederci " . $get['user'] . "! Grazie per aver utilizzato BOSTARTER."
        : "Logout effettuato con successo! Grazie per aver utilizzato BOSTARTER.";
}

// Initialize database connection
$progetti_evidenza = [];
$stats = [
    'creatori_totali' => 0,
    'progetti_aperti' => 0,
    'fondi_totali' => 0,
    'totale_progetti' => 0,
    'progetti_attivi' => 0,
    'totale_raccolto' => 0,
    'totale_utenti' => 0
];

// Include the header
include __DIR__ . '/includes/head.php';
?>

<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container py-4">
    
    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare and execute query with parameterized statement
    $query = "
        SELECT 
            p.id AS id,
            p.titolo AS nome,
            p.descrizione AS descrizione,
            p.immagine AS immagine,
            p.budget_richiesto AS budget_richiesto,
            COALESCE(p.budget_raccolto, 0) AS raccolti,
            p.data_fine AS data_limite,
            LOWER(p.tipo_progetto) AS tipo,
            u.nickname AS creatore_nickname
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        WHERE p.stato = 'ATTIVO'
        ORDER BY p.data_inserimento DESC
        LIMIT 3
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $progetti_evidenza = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Errore nel recupero dei progetti in evidenza: ' . $e->getMessage());
    $progetti_evidenza = [];
    // Log the error but don't expose details to the user
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
    }
} catch (Exception $e) {
    error_log('Errore generico: ' . $e->getMessage());
    $progetti_evidenza = [];
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
    }
}

// Recupera statistiche generali con gestione errori migliorata
$stats = [
    'creatori_totali' => 0,
    'progetti_aperti' => 0,
    'fondi_totali' => 0,
    'totale_progetti' => 0,
    'progetti_attivi' => 0,
    'totale_raccolto' => 0,
    'totale_utenti' => 0
];

try {
    // Usa prepared statements per tutte le query
    $queries = [
        'creatori_totali' => "SELECT COUNT(*) as count FROM utenti WHERE tipo_utente = 'CREATORE'",
        'progetti_aperti' => "SELECT COUNT(*) as count FROM progetti WHERE stato = 'ATTIVO'",
        'fondi_totali' => "SELECT COALESCE(SUM(importo), 0) as total FROM finanziamenti WHERE stato = 'COMPLETATO'",
        'totale_progetti' => "SELECT COUNT(*) as count FROM progetti",
        'progetti_attivi' => "SELECT COUNT(*) as count FROM progetti WHERE stato = 'ATTIVO'",
        'totale_raccolto' => "SELECT COALESCE(SUM(importo), 0) as total FROM finanziamenti WHERE stato = 'COMPLETATO'",
        'totale_utenti' => "SELECT COUNT(*) as count FROM utenti"
    ];

    foreach ($queries as $key => $query) {
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (strpos($key, 'total') !== false) {
                $stats[$key] = (float)($result['total'] ?? 0);
            } else {
                $stats[$key] = (int)($result['count'] ?? 0);
            }
        } catch (PDOException $e) {
            error_log("Errore nel recupero della statistica {$key}: " . $e->getMessage());
            // Continua con le altre query anche se una fallisce
            continue;
        }
    }
} catch (Exception $e) {
    error_log('Errore nel recupero delle statistiche generali: ' . $e->getMessage());
    // Usa i valori di default in caso di errore
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>

<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid">

        <!-- Messaggio di logout -->
        <?php if ($logout_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= $logout_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Hero Section -->
        <div class="hero-section text-white mb-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <h1 class="hero-title animate-fade-up">
                            Trasforma le tue <span class="text-gradient-secondary">idee</span> in realtà
                        </h1>
                        <p class="lead mb-4">
                            BOSTARTER è la piattaforma di crowdfunding dedicata a progetti innovativi
                            hardware e software. Unisciti alla community e finanzia il futuro!
                        </p>
                        <div class="d-flex gap-3">
                            <?php if (isLoggedIn()): ?>
                                <a href="new.php" class="btn btn-warning btn-lg px-4">
                                    <i class="fas fa-plus me-2"></i>Crea Progetto
                                </a>
                                <a href="view.php" class="btn btn-outline-light btn-lg px-4">
                                    <i class="fas fa-search me-2"></i>Esplora Progetti
                                </a>
                            <?php else: ?>
                                <a href="auth/signup.php" class="btn btn-warning btn-lg px-4">
                                    <i class="fas fa-user-plus me-2"></i>Registrati
                                </a>
                                <a href="auth/login.php" class="btn btn-outline-light btn-lg px-4">
                                    <i class="fas fa-sign-in-alt me-2"></i>Accedi
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-6 text-center">
                        <img src="images/lamp.jpeg" alt="Innovazione" class="img-fluid rounded shadow"
                            style="max-height: 400px;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiche -->
        <div class="container mb-5">
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="card h-100 project-card animate-fade-up">
                        <div class="card-body">
                            <i class="fas fa-project-diagram text-primary fa-3x mb-3"></i>
                            <h3 class="fw-bold text-primary stats-number">
                                <?= number_format($stats['totale_progetti']) ?></h3>
                            <p class="text-muted mb-0 stats-label">Progetti Totali</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 project-card animate-fade-up" style="animation-delay: 0.1s">
                        <div class="card-body">
                            <i class="fas fa-rocket text-success fa-3x mb-3"></i>
                            <h3 class="fw-bold text-success stats-number">
                                <?= number_format($stats['progetti_attivi']) ?></h3>
                            <p class="text-muted mb-0 stats-label">Progetti Attivi</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 project-card animate-fade-up" style="animation-delay: 0.2s">
                        <div class="card-body">
                            <i class="fas fa-euro-sign text-warning fa-3x mb-3"></i>
                            <h3 class="fw-bold text-warning stats-number">
                                €<?= number_format($stats['totale_raccolto']) ?></h3>
                            <p class="text-muted mb-0 stats-label">Fondi Raccolti</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 project-card animate-fade-up" style="animation-delay: 0.3s">
                        <div class="card-body">
                            <i class="fas fa-users text-info fa-3x mb-3"></i>
                            <h3 class="fw-bold text-info stats-number"><?= number_format($stats['totale_utenti']) ?>
                            </h3>
                            <p class="text-muted mb-0 stats-label">Utenti Registrati</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progetti in Evidenza -->
        <div class="container mb-5">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-center mb-4">🔥 Progetti in Evidenza</h2>
                </div>
            </div>

            <?php if (empty($progetti_evidenza)): ?>
            <div class="text-center py-5">
                <i class="fas fa-lightbulb fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Nessun progetto al momento</h4>
                <p class="text-muted">Sii il primo a creare un progetto innovativo!</p>
                <?php if (isLoggedIn()): ?>
                <a href="new.php" class="btn btn-primary">Crea il Primo Progetto</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($progetti_evidenza as $progetto): 
                    $percentuale = $progetto['budget_richiesto'] > 0 ? 
                        min(100, ($progetto['raccolti'] / $progetto['budget_richiesto']) * 100) : 0;
                    $giorni_rimanenti = ceil((strtotime($progetto['data_limite']) - time()) / 86400);
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <?php if (!empty($progetto['immagine'])): ?>
                        <img src="uploads/<?= htmlspecialchars($progetto['immagine']) ?>" class="card-img-top"
                            style="height: 200px; object-fit: cover;" alt="<?= htmlspecialchars($progetto['nome']) ?>">
                        <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                            style="height: 200px;">
                            <i
                                class="fas fa-<?= $progetto['tipo'] === 'hardware' ? 'cog' : 'code' ?> fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-<?= $progetto['tipo'] === 'hardware' ? 'primary' : 'info' ?>">
                                    <?= ucfirst($progetto['tipo']) ?>
                                </span>
                                <small class="text-muted">
                                    <?= $giorni_rimanenti > 0 ? $giorni_rimanenti . ' giorni' : 'Scaduto' ?>
                                </small>
                            </div>

                            <h5 class="card-title"><?= htmlspecialchars($progetto['nome']) ?></h5>
                            <p class="card-text text-muted flex-grow-1">
                                <?= htmlspecialchars(substr($progetto['descrizione'], 0, 100)) ?>...
                            </p>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Progresso</small>
                                    <small class="text-muted"><?= number_format($percentuale, 1) ?>%</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" style="width: <?= $percentuale ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">€<?= number_format($progetto['raccolti']) ?></small>
                                    <small
                                        class="text-muted">€<?= number_format($progetto['budget_richiesto']) ?></small>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    <?= htmlspecialchars($progetto['creatore_nickname']) ?>
                                </small>
                                <a href="view.php?id=<?= $progetto['id'] ?>" class="btn btn-primary btn-sm">
                                    Scopri di più
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-4">
                <a href="view.php" class="btn btn-outline-primary">
                    <i class="fas fa-search me-2"></i>Esplora Tutti i Progetti
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Come Funziona -->
        <div class="bg-light py-5">
            <div class="container">
                <h2 class="text-center mb-5">Come Funziona BOSTARTER</h2>
                <div class="row">
                    <div class="col-lg-4 text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-lightbulb fa-4x text-warning"></i>
                        </div>
                        <h4>1. Crea</h4>
                        <p class="text-muted">
                            Presenta la tua idea innovativa con descrizioni dettagliate,
                            immagini e obiettivi di finanziamento chiari.
                        </p>
                    </div>
                    <div class="col-lg-4 text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-share-alt fa-4x text-primary"></i>
                        </div>
                        <h4>2. Condividi</h4>
                        <p class="text-muted">
                            Promuovi il tuo progetto nella community e raggiungi
                            potenziali sostenitori interessati alle tue idee.
                        </p>
                    </div>
                    <div class="col-lg-4 text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-rocket fa-4x text-success"></i>
                        </div>
                        <h4>3. Realizza</h4>
                        <p class="text-muted">
                            Raccogli i fondi necessari e trasforma la tua idea
                            in un prodotto reale con il supporto della community.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Includi CSRF token come meta tag per JavaScript -->
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    
    <?php 
    // Includi gli script
    include __DIR__ . '/includes/scripts.php'; 
    
    // Aggiungi script per gestire le richieste AJAX con CSRF
    ?>
    
    <script>
    // Handle global AJAX errors
    document.addEventListener('DOMContentLoaded', function() {
        // Logout success message
        <?php if ($logout_message): ?>
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        alertDiv.role = 'alert';
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <?= addslashes($logout_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
            if (alert) alert.close();
        }, 5000);
        <?php endif; ?>

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>

    <style>
    .hero-section {
        background: linear-gradient(135deg, var(--bostarter-primary) 0%, var(--bostarter-secondary) 100%);
    }

    .hover-card {
        transition: transform 0.2s ease-in-out;
    }

    .hover-card:hover {
        transform: translateY(-5px);
    }

    .progress-bar {
        background: linear-gradient(90deg, var(--bostarter-primary), var(--bostarter-secondary));
    }
    </style>
</body>

</html>