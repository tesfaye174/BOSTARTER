<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . "/../backend/config/database.php";

/**
 * Pagina Statistiche BOSTARTER
 * Implementa le 3 statistiche richieste dalla specifica
 */

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Top 3 creatori per affidabilità
    $stmt = $conn->query("
        SELECT 
            u.nickname,
            u.affidabilita,
            u.nr_progetti
        FROM utenti u
        WHERE u.tipo_utente = 'creatore'
        ORDER BY u.affidabilita DESC, u.nr_progetti DESC
        LIMIT 3
    ");
    $top_creatori = $stmt->fetchAll();
    
    // 2. Top 3 progetti aperti più vicini al completamento
    $stmt = $conn->query("
        SELECT 
            p.id,
            p.nome,
            p.budget_richiesto,
            p.budget_raccolto,
            (p.budget_richiesto - p.budget_raccolto) as differenza_budget,
            ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale_completamento,
            u.nickname as creatore_nickname
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        WHERE p.stato = 'aperto'
        ORDER BY differenza_budget ASC, percentuale_completamento DESC
        LIMIT 3
    ");
    $top_progetti = $stmt->fetchAll();
    
    // 3. Top 3 utenti per totale finanziamenti erogati
    $stmt = $conn->query("
        SELECT 
            u.nickname,
            SUM(f.importo) as totale_finanziamenti,
            COUNT(f.id) as numero_finanziamenti
        FROM utenti u
        JOIN finanziamenti f ON u.id = f.utente_id
        GROUP BY u.id, u.nickname
        ORDER BY totale_finanziamenti DESC
        LIMIT 3
    ");
    $top_finanziatori = $stmt->fetchAll();
    
} catch(Exception $e) {
    $error = "Errore nel caricamento delle statistiche: " . $e->getMessage();
}

$is_logged_in = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<?php $page_title = 'Statistiche'; include __DIR__ . '/includes/head.php'; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <div class="navbar-nav ms-auto">
                <?php if ($is_logged_in): ?>
                    <a class="nav-link" href="dash.php">Dashboard</a>
                    <a class="nav-link" href="auth/exit.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="auth/login.php">Login</a>
                    <a class="nav-link" href="auth/signup.php">Registrati</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-5">
                    <i class="fas fa-chart-line me-3"></i>
                    Statistiche Piattaforma
                </h1>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>

        <!-- Top Creatori per Affidabilità -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-crown me-2"></i>
                            Top 3 Creatori per Affidabilità
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_creatori)): ?>
                            <p class="text-muted">Nessun creatore presente</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($top_creatori as $index => $creatore): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <div class="position-relative d-inline-block mb-3">
                                                <i class="fas fa-user-circle fa-3x text-primary"></i>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                                    #<?php echo $index + 1; ?>
                                                </span>
                                            </div>
                                            <h5 class="card-title"><?php echo htmlspecialchars($creatore['nickname']); ?></h5>
                                            <p class="card-text">
                                                <strong>Affidabilità:</strong> <?php echo number_format($creatore['affidabilita'], 1); ?>%<br>
                                                <strong>Progetti:</strong> <?php echo $creatore['nr_progetti']; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Progetti vicini al Completamento -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-target me-2"></i>
                            Top 3 Progetti vicini al Completamento
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_progetti)): ?>
                            <p class="text-muted">Nessun progetto aperto presente</p>
                        <?php else: ?>
                            <?php foreach ($top_progetti as $index => $progetto): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-1 text-center">
                                            <span class="badge bg-success fs-5">#<?php echo $index + 1; ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <h5 class="mb-1">
                                                <a href="view.php?id=<?php echo $progetto['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($progetto['nome']); ?>
                                                </a>
                                            </h5>
                                            <small class="text-muted">
                                                Creato da: <?php echo htmlspecialchars($progetto['creatore_nickname']); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress">
                                                <div class="progress-bar" style="--progress: <?php echo $progetto['percentuale_completamento']; ?>%">
                                                    <?php echo $progetto['percentuale_completamento']; ?>%
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <strong>€<?php echo number_format($progetto['budget_raccolto'], 0); ?></strong><br>
                                            <small class="text-muted">di €<?php echo number_format($progetto['budget_richiesto'], 0); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Finanziatori -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-coins me-2"></i>
                            Top 3 Finanziatori
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_finanziatori)): ?>
                            <p class="text-muted">Nessun finanziamento presente</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($top_finanziatori as $index => $finanziatore): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <div class="position-relative d-inline-block mb-3">
                                                <i class="fas fa-hand-holding-heart fa-3x text-warning"></i>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                    #<?php echo $index + 1; ?>
                                                </span>
                                            </div>
                                            <h5 class="card-title"><?php echo htmlspecialchars($finanziatore['nickname']); ?></h5>
                                            <p class="card-text">
                                                <strong>Totale:</strong> €<?php echo number_format($finanziatore['totale_finanziamenti'], 0); ?><br>
                                                <strong>Finanziamenti:</strong> <?php echo $finanziatore['numero_finanziamenti']; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 BOSTARTER. Tutti i diritti riservati.</p>
        </div>
    </footer>

    <?php include __DIR__ . '/includes/scripts.php'; ?>
</body>
</html>
