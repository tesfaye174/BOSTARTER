<?php
// Homepage principale
require_once __DIR__ . '/includes/init.php';

$page_title = 'Home';

// Prepara messaggio di logout
$logout_message = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $username = $_GET['user'] ?? '';
    $logout_message = $username 
        ? "Arrivederci " . htmlspecialchars($username) . "! Grazie per aver utilizzato BOSTARTER."
        : "Logout effettuato con successo! Grazie per aver utilizzato BOSTARTER.";
}

// Recupera progetti in evidenza
$progetti_evidenza = [];
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->query("
        SELECT id, nome, descrizione, foto, budget, data_limite 
        FROM progetti 
        WHERE stato = 'aperto' 
        ORDER BY data_inserimento DESC 
        LIMIT 3
    ");
    $progetti_evidenza = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Errore nel recupero dei progetti in evidenza: ' . $e->getMessage());
}

// Recupera statistiche generali
$stats = [];
try {
    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM utenti WHERE tipo = 'creatore') AS creatori_totali,
            (SELECT COUNT(*) FROM progetti WHERE stato = 'aperto') AS progetti_aperti,
            (SELECT SUM(importo) FROM finanziamenti) AS fondi_totali
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Errore nel recupero delle statistiche generali: ' . $e->getMessage());
    $stats = [
        'creatori_totali' => 0,
        'progetti_aperti' => 0,
        'fondi_totali' => 0
    ];
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

    <?php include __DIR__ . '/includes/scripts.php'; ?>

    <script>
    // Initialize logout success message if present
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($logout_message): ?>
        // Show logout success notification
        if (typeof BOSTARTER !== 'undefined' && BOSTARTER.logout) {
            BOSTARTER.logout.showLogoutSuccess('<?= addslashes($_GET['user'] ?? '') ?>');
        }
        <?php endif; ?>
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