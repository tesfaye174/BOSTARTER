<?php
$is_logged_in = isset($_SESSION["user_id"]);
$username = $_SESSION["nickname"] ?? "";

require_once (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../../' : '../') . 'backend/utils/RoleManager.php';
$roleManager = new RoleManager();
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>index.php">
            <i class="fas fa-rocket me-2"></i>BOSTARTER
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>home.php">Progetti</a>
                </li>
                
                <!-- Menu per utenti autenticati -->
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>dash.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>skill.php">Le Mie Skill</a>
                    </li>
                <?php endif; ?>
                
                <!-- Menu per creatori di progetti -->
                <?php if ($is_logged_in && $roleManager->hasPermission('can_create_project')): ?>
                    <li class="nav-item">
                        <a class="nav-link text-success" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>new.php">
                            <i class="fas fa-plus"></i> Crea Progetto
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Menu amministratori -->
                <?php if ($is_logged_in && $roleManager->isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-warning" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '' : 'admin/' ?>competenze.php">Gestione Competenze</a></li>
                            <li><a class="dropdown-item" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>statistiche.php">Statistiche Sistema</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '' : 'admin/' ?>add_skill.php">Aggiungi Skill</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>statistiche.php">Statistiche</a>
                </li>
            </ul>
            
            <div class="navbar-nav">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown navbar-nav">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($username) ?>
                            <?php if ($roleManager->isAdmin()): ?>
                                <span class="badge bg-danger ms-1">Admin</span>
                            <?php elseif ($roleManager->isCreator()): ?>
                                <span class="badge bg-primary ms-1">Creatore</span>
                            <?php else: ?>
                                <span class="badge bg-secondary ms-1">Utente</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>view.php?type=user&id=<?= $_SESSION['user_id'] ?>">
                                <i class="fas fa-user-circle"></i> Il Mio Profilo
                            </a></li>
                            <li><a class="dropdown-item" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>candidature.php">
                                <i class="fas fa-briefcase"></i> Le Mie Candidature
                            </a></li>
                            <?php if ($roleManager->hasPermission('can_create_project')): ?>
                                <li><a class="dropdown-item" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>dash.php?view=my-projects">
                                    <i class="fas fa-project-diagram"></i> I Miei Progetti
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>auth/exit.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>auth/login.php">
                        <i class="fas fa-sign-in-alt"></i> Accedi
                    </a>
                    <a class="btn btn-light btn-sm ms-2" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>auth/signup.php">
                        <i class="fas fa-user-plus"></i> Registrati
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>


