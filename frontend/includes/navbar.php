<?php
// Navbar include - assumiamo che $_SESSION e $is_logged_in siano già definiti
$is_logged_in = isset($_SESSION["user_id"]);
$username = $_SESSION["nickname"] ?? "";
$user_type = $_SESSION["user_type"] ?? "";
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
                <li class="nav-item">
                    <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>statistiche.php">Statistiche</a>
                </li>
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>skill.php">Le Mie Skill</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="navbar-nav">
                <?php if ($is_logged_in): ?>
                    <span class="nav-link">Ciao, <?= htmlspecialchars($username) ?></span>
                    <?php if ($user_type === 'admin'): ?>
                        <a class="nav-link text-warning" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '' : 'admin/' ?>competenze.php">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                    <?php endif; ?>
                    <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>home.php">Dashboard</a>
                    <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>auth/exit.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>auth/login.php">Accedi</a>
                    <a class="btn btn-light btn-sm ms-2" href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>auth/signup.php">Registrati</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
