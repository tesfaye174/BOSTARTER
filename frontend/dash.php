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

// Titolo pagina per header moderno
$page_title = 'Dashboard - BOSTARTER';

// Includi header moderno
require_once 'includes/head.php';

// Includi navbar moderno
require_once 'includes/navbar.php';
?>

<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <?php
                    $welcome_messages = [
                        "Ciao {name}! Bentornato nella tua dashboard.",
                        "Benvenuto {name}! Ecco il riepilogo delle tue attività.",
                        "Salve {name}! Pronto per gestire i tuoi progetti?",
                        "Eccoti qui {name}! Vediamo i tuoi progressi.",
                        "Buongiorno {name}! La tua dashboard ti aspetta."
                    ];
                    $random_welcome = str_replace('{name}', htmlspecialchars($nickname), $welcome_messages[array_rand($welcome_messages)]);
                    ?>
                    <h1 class="hero-title animate-fade-up"><?php echo $random_welcome; ?></h1>
                    <p class="hero-subtitle animate-fade-up">
                        <?php if ($tipo_utente === "creatore"): ?>
                        Gestisci i tuoi progetti e monitora i tuoi progressi di crescita.
                        <?php else: ?>
                        Tieni traccia dei progetti che hai supportato e scopri nuove opportunità.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid py-5">
        <div class="container">
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 shadow-sm animate-fade-up mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistics Section -->
            <div class="row g-4 mb-5">
                <?php if ($tipo_utente === "creatore"): ?>
                <!-- Creator Statistics -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 stat-card animate-fade-up">
                        <div class="card-body text-center">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-lightbulb fa-2x text-primary"></i>
                            </div>
                            <h3 class="stat-number" data-target="<?php echo $stats['progetti_creati']; ?>"><?php echo $stats['progetti_creati']; ?></h3>
                            <h6 class="stat-label text-muted">Progetti Creati</h6>
                            <small class="text-muted d-block mt-2">
                                Idee innovative portate al mondo
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 stat-card animate-fade-up">
                        <div class="card-body text-center">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-euro-sign fa-2x text-success"></i>
                            </div>
                            <h3 class="stat-number" data-target="<?php echo intval($stats['fondi_raccolti']); ?>"><?php echo number_format($stats['fondi_raccolti'], 0); ?></h3>
                            <h6 class="stat-label text-muted">Fondi Raccolti</h6>
                            <small class="text-muted d-block mt-2">
                                di €<?php echo number_format($stats['fondi_richiesti'], 0); ?> richiesti
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 stat-card animate-fade-up">
                        <div class="card-body text-center">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-star fa-2x text-warning"></i>
                            </div>
                            <h3 class="stat-number" data-target="<?php echo intval($stats['affidabilita']); ?>"><?php echo number_format($stats['affidabilita'], 1); ?></h3>
                            <h6 class="stat-label text-muted">Affidabilità</h6>
                            <small class="text-muted d-block mt-2">
                                Basata sui progetti completati
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 stat-card animate-fade-up">
                        <div class="card-body text-center">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-chart-line fa-2x text-info"></i>
                            </div>
                            <h3 class="stat-number" data-target="<?php echo intval($stats['success_rate']); ?>"><?php echo $stats['success_rate']; ?></h3>
                            <h6 class="stat-label text-muted">Success Rate</h6>
                            <small class="text-muted d-block mt-2">
                                Progetti completati con successo
                            </small>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- User Statistics -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 stat-card animate-fade-up">
                        <div class="card-body text-center">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-hand-holding-heart fa-2x text-primary"></i>
                            </div>
                            <h3 class="stat-number" data-target="<?php echo $stats['finanziamenti_fatti']; ?>"><?php echo $stats['finanziamenti_fatti']; ?></h3>
                            <h6 class="stat-label text-muted">Progetti Supportati</h6>
                            <small class="text-muted d-block mt-2">
                                Idee che hai aiutato a crescere
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 stat-card animate-fade-up">
                        <div class="card-body text-center">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-coins fa-2x text-success"></i>
                            </div>
                            <h3 class="stat-number" data-target="<?php echo intval($stats['totale_investito']); ?>"><?php echo number_format($stats['totale_investito'], 0); ?></h3>
                            <h6 class="stat-label text-muted">Totale Investito</h6>
                            <small class="text-muted d-block mt-2">
                                Il tuo impatto sulla comunità
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 stat-card animate-fade-up">
                        <div class="card-body text-center">
                            <div class="stat-icon mb-3">
                                <i class="fas fa-heart fa-2x text-danger"></i>
                            </div>
                            <h3 class="stat-number" data-target="<?php echo count($finanziamenti); ?>"><?php echo count($finanziamenti); ?></h3>
                            <h6 class="stat-label text-muted">Progetti Amati</h6>
                            <small class="text-muted d-block mt-2">
                                Visioni che hai scelto di supportare
                            </small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4 mb-5">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-bolt me-2 text-warning"></i>Azioni Rapide
                            </h5>
                            <div class="row g-3">
                                <?php if ($tipo_utente === "creatore"): ?>
                                <div class="col-md-3">
                                    <a href="new.php" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-plus-circle me-2"></i>
                                        Nuovo Progetto
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="skill.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-brain me-2"></i>
                                        Le Mie Skill
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="col-md-4">
                                    <a href="view.php" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-search me-2"></i>
                                        Esplora Progetti
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="skill.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-brain me-2"></i>
                                        Le Mie Skill
                                    </a>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-<?php echo $tipo_utente === "creatore" ? "3" : "4"; ?>">
                                    <a href="candidature.php" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-user-check me-2"></i>
                                        Candidature
                                    </a>
                                </div>
                                <div class="col-md-<?php echo $tipo_utente === "creatore" ? "3" : "4"; ?>">
                                    <a href="profilo.php" class="btn btn-outline-info w-100 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-user-edit me-2"></i>
                                        Modifica Profilo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Projects/Financements -->
            <div class="row g-4">
                <?php if ($tipo_utente === "creatore" && !empty($progetti)): ?>
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0">
                                <i class="fas fa-project-diagram me-2 text-primary"></i>I Tuoi Progetti Recenti
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <?php foreach (array_slice($progetti, 0, 3) as $progetto): ?>
                                <div class="col-lg-4 col-md-6">
                                    <div class="card h-100 shadow-sm project-card animate-fade-up">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($progetto['nome']); ?></h6>
                                                <span class="badge bg-<?php
                                                    switch($progetto['stato']) {
                                                        case 'attivo': echo 'success'; break;
                                                        case 'in_attesa': echo 'warning'; break;
                                                        case 'completato': echo 'info'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo htmlspecialchars($progetto['stato']); ?>
                                                </span>
                                            </div>
                                            <p class="card-text text-muted small mb-2">
                                                <?php echo htmlspecialchars(substr($progetto['descrizione'], 0, 100)); ?>...
                                            </p>
                                            <div class="mb-2">
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-success"
                                                         style="width: <?php echo $progetto['budget_totale'] > 0 ? min(100, ($progetto['budget_raccolto'] / $progetto['budget_totale']) * 100) : 0; ?>%"></div>
                                                </div>
                                                <small class="text-muted">
                                                    €<?php echo number_format($progetto['budget_raccolto'], 0); ?> di €<?php echo number_format($progetto['budget_totale'], 0); ?>
                                                </small>
                                            </div>
                                            <a href="view.php?id=<?php echo $progetto['id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fas fa-eye me-1"></i>Vedi Progetto
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($progetti) > 3): ?>
                            <div class="text-center mt-4">
                                <a href="view.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-right me-1"></i>Vedi Tutti i Progetti
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php elseif (!empty($finanziamenti)): ?>
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0">
                                <i class="fas fa-heart me-2 text-danger"></i>I Tuoi Finanziamenti Recenti
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <?php foreach (array_slice($finanziamenti, 0, 3) as $finanziamento): ?>
                                <div class="col-lg-4 col-md-6">
                                    <div class="card h-100 shadow-sm project-card animate-fade-up">
                                        <div class="card-body">
                                            <h6 class="card-title mb-2"><?php echo htmlspecialchars($finanziamento['nome_progetto']); ?></h6>
                                            <div class="mb-2">
                                                <span class="badge bg-primary mb-2">
                                                    <i class="fas fa-coins me-1"></i>€<?php echo number_format($finanziamento['importo'], 2); ?>
                                                </span>
                                            </div>
                                            <p class="card-text text-muted small mb-2">
                                                Finanziato il <?php echo date('d/m/Y', strtotime($finanziamento['data_finanziamento'])); ?>
                                            </p>
                                            <span class="badge bg-<?php
                                                switch($finanziamento['stato_pagamento']) {
                                                    case 'completed': echo 'success'; break;
                                                    case 'pending': echo 'warning'; break;
                                                    case 'failed': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <i class="fas fa-<?php
                                                    switch($finanziamento['stato_pagamento']) {
                                                        case 'completed': echo 'check-circle'; break;
                                                        case 'pending': echo 'clock'; break;
                                                        case 'failed': echo 'times-circle'; break;
                                                        default: echo 'question-circle';
                                                    }
                                                ?> me-1"></i>
                                                <?php echo htmlspecialchars($finanziamento['stato_pagamento']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($finanziamenti) > 3): ?>
                            <div class="text-center mt-4">
                                <a href="candidature.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-right me-1"></i>Vedi Tutti i Finanziamenti
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTopBtn" title="Torna in cima">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- JavaScript Ottimizzato -->
    <script src="assets/js/bostarter-optimized.min.js"></script>

    <!-- Custom Dashboard Script -->
    <script>
        // Initialize dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize counters
            BOSTARTER.animations.counter(document.querySelectorAll('.stat-number')[0]);

            // Add smooth scrolling to action buttons
            document.querySelectorAll('.btn[href^="#"]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });

            // Add loading animation to cards
            document.querySelectorAll('.project-card').forEach(function(card, index) {
                setTimeout(function() {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>

</html>