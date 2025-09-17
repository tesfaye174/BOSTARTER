<?php
/**
 * BOSTARTER - Homepage Moderna e Pulita
 *
 * Design minimalista con colori acromatici (bianco, nero, grigio)
 * - Layout moderno e responsive
 * - Animazioni sottili e eleganti
 * - Dark mode integrato
 * - Performance ottimizzata
 */

// Avvia sessione sicura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurazione errori per debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connessione database
require_once __DIR__ . '/../backend/config/database.php';

try {
    $conn = Database::getInstance();
} catch(Exception $e) {
    error_log('Errore connessione database: ' . $e->getMessage());
    header('Location: /error.php?code=500');
    exit();
}

/**
 * Verifica autenticazione utente
 */
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

/**
 * Genera token CSRF sicuro
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Sanitizza output HTML
 */
function sanitize_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formatta numero con separatore italiano
 */
function format_number($number) {
    return number_format($number, 0, ',', '.');
}

// Titolo pagina
$page_title = 'BOSTARTER - Piattaforma di Crowdfunding';

// Inizializza CSRF token
$csrf_token = generate_csrf_token();

// Gestione messaggio di logout
$logout_message = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $user = isset($_GET['user']) ? sanitize_output($_GET['user']) : '';
    $logout_message = !empty($user)
        ? "Arrivederci " . $user . "! Grazie per aver utilizzato BOSTARTER."
        : "Logout effettuato con successo!";
}

// Inizializzazione dati
$progetti_evidenza = [];
$top_creatori = [];
$progetti_vicini = [];
$top_finanziatori = [];
$stats = [
    'totale_progetti' => 0,
    'progetti_attivi' => 0,
    'totale_raccolto' => 0,
    'totale_utenti' => 0
];

/**
 * Chiamata API sicura
 */
function callAPI($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "API call failed with HTTP code $httpCode"];
    }

    return json_decode($response, true);
}

// API base URL
$api_base = 'http://localhost/BOSTARTER/backend/api/';

// Carica dati dalle API
$progetti_evidenza_data = callAPI($api_base . 'project.php?limit=3');
$progetti_evidenza = $progetti_evidenza_data['data'] ?? [];

$stats_data = callAPI($api_base . 'statistiche.php');
$stats = $stats_data['data'] ?? $stats;

$top_creatori_data = callAPI($api_base . 'statistiche.php?tipo=creatori');
$top_creatori = $top_creatori_data['data'] ?? [];

$progetti_vicini_data = callAPI($api_base . 'statistiche.php?tipo=progetti');
$progetti_vicini = $progetti_vicini_data['data'] ?? [];

$top_finanziatori_data = callAPI($api_base . 'statistiche.php?tipo=finanziatori');
$top_finanziatori = $top_finanziatori_data['data'] ?? [];
?>

<!DOCTYPE html>
<html lang="it" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title><?php echo sanitize_output($page_title); ?></title>

    <!-- Preconnect per performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Font moderna -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- CSS Ottimizzato e Consolidato -->
    <link href="assets/css/bostarter-optimized.min.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>

<body>
    <!-- Navbar Minimalista -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progetti.php">
                            <i class="fas fa-lightbulb me-1"></i>Progetti
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="skill.php">
                            <i class="fas fa-brain me-1"></i>Le Mie Skill
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="candidature.php">
                            <i class="fas fa-user-check me-1"></i>Candidature
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="crea_progetto.php">
                            <i class="fas fa-plus-circle me-1"></i>Crea Progetto
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav align-items-center">
                    <!-- Dark Mode Toggle -->
                    <li class="nav-item me-3">
                        <button class="theme-toggle" id="themeToggle" title="Cambia tema">
                            <span class="sr-only">Toggle theme</span>
                        </button>
                    </li>

                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo sanitize_output($_SESSION['nickname'] ?? 'Utente'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profilo.php">
                                <i class="fas fa-user me-2"></i>Profilo
                            </a></li>
                            <li><a class="dropdown-item" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">Accedi</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-secondary ms-2" href="auth/signup.php">
                            <i class="fas fa-user-plus me-1"></i>Registrati
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section Pulita -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="hero-title animate-fade-up">
                        Dai Vita ai Progetti<br>
                        <span class="text-gradient">Creativi</span>
                    </h1>
                    <p class="hero-subtitle animate-fade-up">
                        Sostieni creatori ambiziosi e porta idee innovative alla realtà con una piattaforma semplice e affidabile.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap animate-fade-up">
                        <a href="progetti.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Esplora Progetti
                        </a>
                        <a href="auth/signup.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user-plus me-2"></i>Unisciti Ora
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Progetti in Evidenza -->
    <?php if (!empty($progetti_evidenza)): ?>
    <section class="py-5 section-alternate">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-6">
                    <h2 class="animate-fade-up">Progetti in Evidenza</h2>
                    <p class="text-muted animate-fade-up">Scopri i progetti più interessanti della nostra comunità</p>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($progetti_evidenza as $index => $project): ?>
                <div class="col-lg-4 col-md-6 animate-fade-up" style="animation-delay: <?php echo $index * 0.1; ?>s">
                    <div class="card h-100">
                        <?php
                        $imagePath = !empty($project['immagine']) ? "../backend/uploads/{$project['immagine']}" : "assets/images/placeholder.jpg";
                        $fallbackImage = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='200' viewBox='0 0 400 200'%3E%3Crect width='400' height='200' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='%239ca3af' font-size='16'%3EImmagine non disponibile%3C/text%3E%3C/svg%3E";
                        ?>
                        <img src="<?php echo $imagePath; ?>"
                             class="card-img-top"
                             alt="<?php echo sanitize_output($project['nome'] ?? 'Progetto'); ?>"
                             onerror="this.src='<?php echo $fallbackImage; ?>'">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo sanitize_output($project['nome'] ?? 'Progetto'); ?></h5>
                            <p class="card-text flex-grow-1">
                                <?php echo sanitize_output(substr($project['descrizione'] ?? '', 0, 120) . '...'); ?>
                            </p>

                            <?php if (isset($project['percentuale_raccolta']) || isset($project['finanziamento_attuale'])): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Progresso</span>
                                    <span><?php echo $project['percentuale_raccolta'] ?? 0; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo min($project['percentuale_raccolta'] ?? 0, 100); ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between small mt-1">
                                    <span>€<?php echo format_number($project['finanziamento_attuale'] ?? 0); ?> raccolti</span>
                                    <span>Obiettivo: €<?php echo format_number($project['budget_richiesto'] ?? 0); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <a href="view.php?id=<?php echo $project['id'] ?? 0; ?>" class="btn btn-outline-secondary mt-auto">
                                <i class="fas fa-eye me-1"></i>Vedi Progetto
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Statistiche -->
    <?php if (!empty($stats)): ?>
    <section class="py-5 stats-section">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-6">
                    <h2 class="animate-fade-up">La Nostra Community</h2>
                    <p class="text-muted animate-fade-up">Numeri che parlano da soli</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6 animate-fade-left">
                    <div class="stat-card">
                        <div class="stat-number" data-target="<?php echo $stats['totale_progetti'] ?? 0; ?>">0</div>
                        <div class="stat-label">Progetti</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 animate-fade-up">
                    <div class="stat-card">
                        <div class="stat-number" data-target="<?php echo intval(($stats['totale_raccolto'] ?? 0) / 1000); ?>">0</div>
                        <div class="stat-label">Milioni Raccolti</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 animate-fade-right">
                    <div class="stat-card">
                        <div class="stat-number" data-target="<?php echo $stats['totale_utenti'] ?? 0; ?>">0</div>
                        <div class="stat-label">Membri</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 animate-fade-up">
                    <div class="stat-card">
                        <div class="stat-number" data-target="<?php echo $stats['progetti_attivi'] ?? 0; ?>">0</div>
                        <div class="stat-label">Progetti Attivi</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Progetti Vicini al Completamento -->
    <?php if (!empty($progetti_vicini)): ?>
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-6">
                    <h2 class="animate-fade-up">Quasi al Goal</h2>
                    <p class="text-muted animate-fade-up">Questi progetti hanno bisogno solo di un ultimo sforzo</p>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach (array_slice($progetti_vicini, 0, 3) as $project): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title"><?php echo sanitize_output($project['nome'] ?? $project['titolo'] ?? 'Progetto'); ?></h5>
                                <span class="badge bg-warning text-dark">Quasi lì!</span>
                            </div>

                            <p class="card-text small text-muted mb-2">
                                Di <?php echo sanitize_output($project['creatore'] ?? $project['creatore_nickname'] ?? 'Creatore'); ?>
                            </p>
                            <p class="card-text small mb-3">
                                <?php echo sanitize_output(substr($project['descrizione'] ?? '', 0, 100) . '...'); ?>
                            </p>

                            <div class="mb-3">
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-warning" style="width: <?php echo $project['percentuale_completamento'] ?? $project['percentuale_raccolta'] ?? 0; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span><?php echo $project['percentuale_completamento'] ?? $project['percentuale_raccolta'] ?? 0; ?>% finanziato</span>
                                    <span>€<?php echo format_number($project['budget_raccolto'] ?? $project['finanziamento_attuale'] ?? 0); ?> di €<?php echo format_number($project['budget_richiesto'] ?? 0); ?></span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo $project['giorni_rimanenti'] ?? 'N/A'; ?> giorni rimasti
                                </small>
                                <a href="view.php?id=<?php echo $project['id'] ?? 0; ?>" class="btn btn-warning btn-sm">
                                    Aiuta a Completare
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Call to Action -->
    <section class="py-5 cta-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-6">
                    <h2 class="cta-title">Pronto a Lanciarti?</h2>
                    <p class="cta-subtitle">
                        Unisciti a centinaia di creatori che hanno trasformato le loro idee in realtà
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="crea_progetto.php" class="btn btn-light">
                            <i class="fas fa-plus me-2"></i>Lancia il Tuo Progetto
                        </a>
                        <a href="progetti.php" class="btn btn-outline-light">
                            <i class="fas fa-search me-2"></i>Sfoglia Tutti i Progetti
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Pulito -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="mb-3">
                        <i class="fas fa-rocket me-2"></i>BOSTARTER
                    </h5>
                    <p class="mb-3">
                        La piattaforma che connette creatori e sostenitori per dare vita a progetti innovativi.
                    </p>
                    <div class="d-flex">
                        <a href="#" class="me-3" style="color: var(--color-gray-400);">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="me-3" style="color: var(--color-gray-400);">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="me-3" style="color: var(--color-gray-400);">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" style="color: var(--color-gray-400);">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-3">
                    <h6 class="mb-3">Piattaforma</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="progetti.php">Progetti</a></li>
                        <li class="mb-2"><a href="crea_progetto.php">Crea Progetto</a></li>
                        <li class="mb-2"><a href="skill.php">Le Mie Skill</a></li>
                        <li class="mb-2"><a href="statistiche.php">Statistiche</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-3">
                    <h6 class="mb-3">Supporto</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#">Centro Assistenza</a></li>
                        <li class="mb-2"><a href="#">Linee Guida</a></li>
                        <li class="mb-2"><a href="#">API</a></li>
                        <li class="mb-2"><a href="#">Contattaci</a></li>
                    </ul>
                </div>

                <div class="col-lg-4">
                    <h6 class="mb-3">Resta Aggiornato</h6>
                    <p class="mb-3">
                        Ricevi aggiornamenti sui nuovi progetti e sulle novità della piattaforma.
                    </p>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Il tuo email">
                        <button class="btn btn-light" type="button">Iscriviti</button>
                    </div>
                </div>
            </div>

            <hr class="my-4" style="border-color: var(--color-gray-700);">

            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 BOSTARTER. Tutti i diritti riservati.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="me-3">Privacy Policy</a>
                    <a href="#" class="me-3">Termini di Servizio</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTopBtn" title="Torna in cima">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript Ottimizzato e Consolidato -->
    <script src="assets/js/bostarter-optimized.min.js"></script>

</body>
</html>
