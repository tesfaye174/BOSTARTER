<?php
/**
 * Barra di Navigazione BOSTARTER - Versione Moderna e Ottimizzata
 *
 * Menu dinamico basato sul ruolo utente con design moderno:
 * - Design acromatico pulito
 * - Dark mode integrato
 * - Tooltips informativi
 * - Responsive ottimizzato
 * - Animazioni sottili
 */

// Verifica stato autenticazione
$is_logged_in = isset($_SESSION["user_id"]);
$username = $_SESSION["nickname"] ?? "";
$user_type = $_SESSION["tipo_utente"] ?? "";
$is_creator = ($user_type === 'creatore');
$is_admin = ($user_type === 'amministratore');

// Determina il path corretto per gli asset
$currentPath = $_SERVER['PHP_SELF'];
$basePath = '';

if (strpos($currentPath, '/admin/') !== false) {
    $basePath = '../';
} elseif (strpos($currentPath, '/auth/') !== false) {
    $basePath = '../';
} elseif (strpos($currentPath, '/includes/') !== false) {
    $basePath = '../';
}

// Connessione database per navbar dinamica (opzionale)
if (!isset($database)) {
    try {
        require_once $basePath . '../backend/config/database.php';
        $database = Database::getInstance();
    } catch (Exception $e) {
        $database = null;
    }
}
?>

<!-- Navbar Minimalista e Moderna -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $is_admin ? ($basePath . 'admin/dashboard.php') : ($basePath . 'home.php'); ?>">
            <i class="fas fa-rocket me-2"></i>BOSTARTER
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Link comuni a tutti -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_admin ? ($basePath . 'admin/dashboard.php') : ($basePath . 'home.php'); ?>"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Torna alla homepage">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>

                <!-- Non mostrare questi link agli admin (hanno il loro pannello) -->
                <?php if (!$is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>progetti.php"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Esplora tutti i progetti">
                        <i class="fas fa-lightbulb me-1"></i>Progetti
                    </a>
                </li>

                <?php if ($is_logged_in): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>skill.php"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Gestisci le tue competenze">
                        <i class="fas fa-brain me-1"></i>Le Mie Skill
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>candidature.php"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Le tue candidature">
                        <i class="fas fa-user-check me-1"></i>Candidature
                    </a>
                </li>
                <?php endif; ?>

                <!-- Link specifici per creatori -->
                <?php if ($is_logged_in && $is_creator): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>crea_progetto.php"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Crea un nuovo progetto">
                        <i class="fas fa-plus-circle me-1"></i>Crea Progetto
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Link specifici per admin -->
                <?php if ($is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>admin/competenze.php"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Gestisci competenze">
                        <i class="fas fa-tools me-1"></i>Competenze
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>statistiche.php"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Visualizza statistiche">
                        <i class="fas fa-chart-bar me-1"></i>Statistiche
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

                <?php if ($is_logged_in): ?>
                <!-- Menu utente autenticato -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-menu" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($username); ?>

                        <!-- Badge per tipo utente -->
                        <?php if ($is_creator): ?>
                        <span class="badge bg-info text-dark ms-1" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Utente Creatore">Creatore</span>
                        <?php elseif ($is_admin): ?>
                        <span class="badge bg-dark text-white ms-1" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Amministratore">Admin</span>
                        <?php else: ?>
                        <span class="badge bg-secondary text-white ms-1" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Utente Standard">Utente</span>
                        <?php endif; ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                        <li><a class="dropdown-item" href="<?php echo $basePath; ?>profilo.php">
                            <i class="fas fa-user me-2"></i>Il Mio Profilo
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo $basePath; ?>dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a></li>

                        <?php if ($is_admin): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $basePath; ?>admin/dashboard.php">
                            <i class="fas fa-user-shield me-2"></i>Pannello Admin
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo $basePath; ?>admin/competenze.php">
                            <i class="fas fa-tools me-2"></i>Gestisci Competenze
                        </a></li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo $basePath; ?>auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>auth/login.php"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Accedi al tuo account">
                        <i class="fas fa-sign-in-alt me-1"></i>Accedi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-secondary ms-2" href="<?php echo $basePath; ?>auth/signup.php"
                       data-bs-toggle="tooltip" data-bs-placement="bottom" title="Registrati gratuitamente">
                        <i class="fas fa-user-plus me-1"></i>Registrati
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages Moderni -->
<?php if (isset($_SESSION['flash_success'])): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
    <div class="toast toast-bostarter show align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['flash_success']); ?>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<script>
    setTimeout(function() {
        const toast = document.querySelector('.toast-bostarter');
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
</script>
<?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
    <div class="toast toast-bostarter show align-items-center text-white bg-danger border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['flash_error']); ?>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<script>
    setTimeout(function() {
        const toast = document.querySelector('.toast-bostarter');
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
</script>
<?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- JavaScript Ottimizzato -->
<script src="<?php echo $basePath; ?>assets/js/bostarter-optimized.min.js"></script>