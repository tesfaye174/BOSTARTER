<?php
/**
 * Homepage BOSTARTER
 *
 * Pagina principale con progetti in evidenza e statistiche:
 * - Progetti in evidenza
 * - Statistiche piattaforma
 * - Creatori top per affidabilità
 * - Progetti vicini completamento
 * - Migliori finanziatori
 */

// Abilita errori per debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Avvia sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
 * @return bool True se loggato
 */
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

// Titolo pagina
$page_title = 'Home - BOSTARTER';

// Inizializza CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Sanitizza input
 * @param mixed $data Input da pulire
 * @return mixed Input pulito
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Gestione messaggio di logout dalla URL
$logout_message = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $user = isset($_GET['user']) ? sanitizeInput($_GET['user']) : '';
    $logout_message = !empty($user)
        ? "Arrivederci " . $user . "! Grazie per aver utilizzato BOSTARTER."
        : "Logout effettuato con successo! Grazie per aver utilizzato BOSTARTER.";
}

// Inizializzazione array per progetti e statistiche
$progetti_evidenza = [];     // Progetti da mostrare in evidenza
$top_creatori = [];          // Classifica creatori per affidabilità
$progetti_vicini = [];       // Projects Near Completion
$top_finanziatori = [];      // Classifica migliori finanziatori

// Array delle statistiche generali della piattaforma
$stats = [
    'totale_progetti' => 0,   // Numero totale progetti
    'progetti_attivi' => 0,   // Progetti attualmente attivi
    'totale_raccolto' => 0,   // Totale finanziamenti ricevuti
    'totale_utenti' => 0      // Numero totale utenti registrati
];

/**
 * Funzione per effettuare chiamate API con gestione errori
 * @param string $url URL dell'endpoint API
 * @return mixed Risultato della chiamata API o array di errore
 */
function callAPI($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout di 5 secondi
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verifica se la risposta HTTP è positiva
    if ($httpCode !== 200) {
        return ['error' => "Chiamata API fallita con codice HTTP $httpCode"];
    }

    // Decodifica la risposta JSON
    return json_decode($response, true);
}

// Definizione URL base per le API
$api_base = 'http://localhost/BOSTARTER/backend/api/';

// Recupero progetti in evidenza tramite API
$progetti_evidenza_data = callAPI($api_base . 'project.php?limit=3');
if (isset($progetti_evidenza_data['error'])) {
    $progetti_evidenza = [];
    echo "<!-- Debug: Errore API progetti: " . $progetti_evidenza_data['error'] . " -->";
} else {
    $progetti_evidenza = $progetti_evidenza_data['data'] ?? [];
    echo "<!-- Debug: API progetti restituiti: " . count($progetti_evidenza) . " progetti -->";
}

// Recupero statistiche generali tramite API
$stats_data = callAPI($api_base . 'statistiche.php');
if (isset($stats_data['error'])) {
    $stats = [];
    echo "<!-- Debug: Errore API statistiche: " . $stats_data['error'] . " -->";
} else {
    $stats = $stats_data['data'] ?? [];
    echo "<!-- Debug: API statistiche caricate correttamente -->";
}

// Recupero classifica creatori top
$top_creatori_data = callAPI($api_base . 'statistiche.php?tipo=creatori');
if (isset($top_creatori_data['error'])) {
    $top_creatori = [];
} else {
    $top_creatori = $top_creatori_data['data'] ?? [];
}

// Recupero progetti vicini al completamento
$progetti_vicini_data = callAPI($api_base . 'statistiche.php?tipo=progetti');
if (isset($progetti_vicini_data['error'])) {
    $progetti_vicini = [];
} else {
    $progetti_vicini = $progetti_vicini_data['data'] ?? [];
}

// Recupero classifica migliori finanziatori
$top_finanziatori_data = callAPI($api_base . 'statistiche.php?tipo=finanziatori');
if (isset($top_finanziatori_data['error'])) {
    $top_finanziatori = [];
} else {
    $top_finanziatori = $top_finanziatori_data['data'] ?? [];
}

// Includi header comune
require_once __DIR__.'/../backend/config/SecurityConfig.php';
require_once __DIR__.'/includes/head.php';

// Inizio del contenuto HTML dopo gli include
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Home Page Styles -->
    <link href="assets/css/home.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>

            <!-- Mobile menu button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">
                            <i class="fas fa-project-diagram me-1"></i>Progetti
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="new.php">
                            <i class="fas fa-plus me-1"></i>Crea Progetto
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="statistiche.php">
                            <i class="fas fa-chart-bar me-1"></i>Statistiche
                        </a>
                    </li>
                </ul>

                <!-- User menu -->
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_nickname'] ?? $_SESSION['user_name'] ?? 'Utente'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-circle me-2"></i>Il Mio Profilo
                                </a></li>
                                <li><a class="dropdown-item" href="my-projects.php">
                                    <i class="fas fa-folder me-2"></i>I Miei Progetti
                                </a></li>
                                <li><a class="dropdown-item" href="finanziamenti.php">
                                    <i class="fas fa-coins me-2"></i>I Miei Finanziamenti
                                </a></li>
                                <li><a class="dropdown-item" href="skill.php">
                                    <i class="fas fa-tools me-2"></i>Le Mie Skill
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Accedi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light ms-2" href="auth/signup.php">
                                <i class="fas fa-user-plus me-1"></i>Registrati
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section py-5 text-center fade-in-up">
        <div class="container py-5">
            <h1 class="display-4 fw-bold mb-4">Dai Vita ai Progetti Creativi</h1>
            <p class="lead mb-4">Sostieni creatori ambiziosi e porta idee innovative alla realtà</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="frontend/view.php" class="btn btn-primary btn-lg px-4" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Esplora tutti i progetti disponibili">
                    <i class="fas fa-search me-2"></i>Esplora Progetti
                </a>
                <a href="auth/signup.php" class="btn btn-outline-secondary btn-lg px-4" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Registrati gratuitamente">
                    <i class="fas fa-user-plus me-2"></i>Unisciti Ora
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Projects -->
    <?php if (!empty($progetti_evidenza)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Progetti in Evidenza</h2>
            <div class="row g-4">
                <?php foreach ($progetti_evidenza as $project): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($project['immagine'])): ?>
                            <img src="uploads/projects/<?php echo htmlspecialchars($project['immagine']); ?>"
                                 class="card-img-top" alt="<?php echo htmlspecialchars($project['nome']); ?>"
                                 style="height: 200px; object-fit: cover;" onerror="this.src='assets/images/lamp.jpg'">
                        <?php else: ?>
                            <img src="assets/images/lamp.jpg" class="card-img-top"
                                 alt="Immagine progetto" style="height: 200px; object-fit: cover;">
                        <?php endif; ?>

                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($project['nome']); ?></h3>
                            <p class="card-text"><?= htmlspecialchars(substr($project['descrizione'], 0, 100)); ?>...</p>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Raccolto</small>
                                    <small class="text-muted"><?php echo $project['percentuale_raccolta'] ?? 0; ?>%</small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar"
                                         style="width: <?php echo min($project['percentuale_raccolta'] ?? 0, 100); ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small><?php echo $project['percentuale_raccolta'] ?? 0; ?>% finanziato</small>
                                    <small>€<?php echo number_format($project['finanziamento_attuale'] ?? 0, 0, ',', '.'); ?> raccolti</small>
                                </div>
                            </div>

                            <a href="project.php?id=<?php echo $project['id'] ?? 0; ?>" class="btn btn-outline-primary w-100">
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

    <!-- Statistics -->
    <?php if (!empty($stats)): ?>
    <section class="py-5 fade-in-up">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="p-4 bg-white rounded shadow-sm">
                        <h3 class="display-4 fw-bold text-primary counter" data-target="<?= $stats['totale_progetti'] ?? '0' ?>">0</h3>
                        <p class="text-muted">Progetti Finanziati</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="p-4 bg-white rounded shadow-sm">
                        <h3 class="display-4 fw-bold text-success counter" data-target="<?= number_format($stats['totale_raccolto'] ?? 0, 0, '', '') ?>">0</h3>
                        <p class="text-muted">Totale Raccolto</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="p-4 bg-white rounded shadow-sm">
                        <h3 class="display-4 fw-bold text-info counter" data-target="<?= $stats['totale_utenti'] ?? '0' ?>">0</h3>
                        <p class="text-muted">Membri della Community</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Projects Near Completion -->
    <?php if (!empty($progetti_vicini)): ?>
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Progetti Vicini al Completamento</h2>
            <div class="row g-4">
                <?php foreach ($progetti_vicini as $project): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-1"><?= htmlspecialchars($project['nome'] ?? $project['titolo'] ?? 'Project') ?></h5>
                                <span class="badge bg-warning text-dark">Quasi lì!</span>
                            </div>
                            <p class="card-text small text-muted mb-2">Di <?= htmlspecialchars($project['creatore'] ?? $project['creatore_nickname'] ?? 'Creator') ?></p>
                            <p class="card-text small mb-3"><?= htmlspecialchars(substr($project['descrizione'] ?? $project['descrizione_breve'] ?? '', 0, 80)) ?>...</p>
                            
                            <div class="mb-3">
                                <div class="progress">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $project['percentuale_completamento'] ?? $project['percentuale_raccolta'] ?? 0 ?>%;" aria-valuenow="<?= $project['percentuale_completamento'] ?? $project['percentuale_raccolta'] ?? 0 ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="fw-bold"><?= $project['percentuale_completamento'] ?? $project['percentuale_raccolta'] ?? 0 ?>% finanziato</small>
                                    <small>€<?= number_format($project['budget_raccolto'] ?? $project['finanziamento_attuale'] ?? 0, 0, ',', '.') ?> of €<?= number_format($project['budget_richiesto'] ?? 0, 0, ',', '.') ?></small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= $project['giorni_rimanenti'] ?? 'N/A' ?> giorni rimasti
                                </small>
                                <a href="project.php?id=<?= $project['id'] ?? 0 ?>" class="btn btn-warning btn-sm">Aiuta a Completare</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Top Financiers -->
    <?php if (!empty($top_finanziatori)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">I Migliori Sostenitori</h2>
            <div class="row g-4 justify-content-center">
                <?php foreach (array_slice($top_finanziatori, 0, 6) as $index => $financier): ?>
                <div class="col-md-4 col-lg-2">
                    <div class="card h-100 shadow-sm text-center">
                        <div class="position-relative mb-3">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 24px;">
                                <i class="fas fa-crown"></i>
                            </div>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                #<?= $index + 1 ?>
                            </span>
                        </div>
                        <h6 class="card-title mb-1"><?= htmlspecialchars($financier['nickname'] ?? $financier['nome'] ?? 'Supporter') ?></h6>
                        <small class="text-muted mb-2">
                            <i class="fas fa-coins me-1"></i>
                            €<?= number_format($financier['totale_finanziato'] ?? $financier['totale_finanziato'] ?? 0, 0, ',', '.') ?>
                        </small>
                        <div class="mt-auto">
                            <small class="text-success fw-bold">
                                <i class="fas fa-heart me-1"></i>
                                <?= $financier['numero_finanziamenti'] ?? 0 ?> progetti
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Call to Action -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="mb-4">Pronto a Lanciare il Tuo Progetto?</h2>
            <p class="lead mb-4">Unisciti a migliaia di creatori che hanno dato vita alle loro idee con BOSTARTER</p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="new.php" class="btn btn-light btn-lg px-4 me-md-2">
                    <i class="fas fa-plus me-2"></i>Lancia un Progetto
                </a>
                <a href="projects.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="fas fa-search me-2"></i>Sfoglia Progetti
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="mb-3">
                        <i class="fas fa-rocket me-2"></i>BOSTARTER
                    </h5>
                    <p class="mb-3">La piattaforma di crowdfunding che connette creatori e sostenitori per dare vita a progetti innovativi.</p>
                    <div class="d-flex">
                        <a href="#" class="text-light me-3" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-light me-3" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-light me-3" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-light" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-3">
                    <h6 class="mb-3">Piattaforma</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="projects.php" class="text-light text-decoration-none">Progetti</a></li>
                        <li class="mb-2"><a href="new.php" class="text-light text-decoration-none">Crea Progetto</a></li>
                        <li class="mb-2"><a href="skill.php" class="text-light text-decoration-none">Le Mie Skill</a></li>
                        <li class="mb-2"><a href="statistiche.php" class="text-light text-decoration-none">Statistiche</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-3">
                    <h6 class="mb-3">Supporto</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Centro Assistenza</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Linee Guida</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">API</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Contattaci</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4">
                    <h6 class="mb-3">Newsletter</h6>
                    <p class="mb-3">Ricevi aggiornamenti sui nuovi progetti e sulle novità della piattaforma.</p>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Il tuo email" aria-label="Email per newsletter">
                        <button class="btn btn-primary" type="button">Iscriviti</button>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 BOSTARTER. Tutti i diritti riservati.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-light text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-light text-decoration-none me-3">Termini di Servizio</a>
                    <a href="#" class="text-light text-decoration-none">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="btn btn-primary position-fixed" style="bottom: 20px; right: 20px; display: none; border-radius: 50%; width: 50px; height: 50px; z-index: 1050;" data-bs-toggle="tooltip" data-bs-placement="left" title="Torna in cima">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Home Page JavaScript -->
    <script src="assets/js/home.js"></script>
</body>
</html>
