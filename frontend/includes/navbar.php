<?php
/**
 * Barra di Navigazione BOSTARTER
 *
 * Menu dinamico basato sul ruolo utente:
 * - Utenti non autenticati: solo login/signup
 * - Utenti normali: progetti, skill, candidature
 * - Creatori: + pulsante "Crea Progetto"
 * - Admin: pannello gestione + competenze
 */

// Verifica stato autenticazione
$is_logged_in = isset($_SESSION["user_id"]);
$username = $_SESSION["nickname"] ?? "";
$user_type = $_SESSION["tipo_utente"] ?? "";
$is_creator = ($user_type === 'creatore');
$is_admin = ($user_type === 'amministratore');

// Connessione database per navbar dinamica
require_once '../backend/config/database.php';
$database = Database::getInstance();
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $is_admin ? 'admin/dashboard.php' : 'home.php'; ?>">
            <i class="fas fa-rocket me-2"></i>BOSTARTER
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Link comuni a tutti -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_admin ? 'admin/dashboard.php' : 'home.php'; ?>">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>

                <!-- Non mostrare questi link agli admin (hanno il loro pannello) -->
                <?php if (!$is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="backend/api/project.php">
                        <i class="fas fa-project-diagram me-1"></i>Progetti
                    </a>
                </li>

                <?php if ($is_logged_in): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/frontend/skill.php">
                        <i class="fas fa-tools me-1"></i>Le Mie Skill
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/frontend/candidature.php">
                        <i class="fas fa-users-cog me-1"></i>Candidature
                    </a>
                </li>
                <?php endif; ?>

                <!-- Link specifici per creatori -->
                <?php if ($is_logged_in && $is_creator): ?>
                <li class="nav-item">
                    <a class="nav-link text-warning" href="/frontend/new.php">
                        <i class="fas fa-plus-circle me-1"></i>Crea Progetto
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Link specifici per admin -->
                <?php if ($is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/frontend/admin/competenze.php">
                        <i class="fas fa-tools me-1"></i>Competenze
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/frontend/statistiche.php">
                        <i class="fas fa-chart-bar me-1"></i>Statistiche
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <?php if ($is_logged_in): ?>
                <!-- Menu utente autenticato -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i>
                        <?php echo htmlspecialchars($username); ?>

                        <!-- Badge per tipo utente -->
                        <?php if ($is_creator): ?>
                        <span class="badge bg-warning ms-1">Creatore</span>
                        <?php elseif ($is_admin): ?>
                        <span class="badge bg-danger ms-1">Admin</span>
                        <?php else: ?>
                        <span class="badge bg-info ms-1">Utente</span>
                        <?php endif; ?>
                    </a>

                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dash.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a></li>

                        <?php if ($is_admin): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="admin/dashboard.php">
                            <i class="fas fa-user-shield me-2"></i>Pannello Admin
                        </a></li>
                        <li><a class="dropdown-item" href="admin/competenze.php">
                            <i class="fas fa-tools me-2"></i>Gestisci Competenze
                        </a></li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
                <?php else: ?>
                <a class="nav-link"
                    href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>auth/login.php">
                    <i class="fas fa-sign-in-alt"></i> Accedi
                </a>
                <a class="btn btn-light btn-sm ms-2"
                    href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>auth/signup.php">
                    <i class="fas fa-user-plus"></i> Registrati
                </a>
                <?php endif; ?>
                <li class="nav-item">
                    <button class="btn btn-outline-primary btn-sm theme-toggle" id="themeToggle" title="Cambia tema">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
// Flash messages (set in session by server-side handlers)
if (isset($_SESSION['flash_success'])) {
    echo '<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">';
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['flash_success']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    echo '</div>';
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    echo '<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">';
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['flash_error']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    echo '</div>';
    unset($_SESSION['flash_error']);
}
?>