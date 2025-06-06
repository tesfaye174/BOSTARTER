<?php
// Enhanced accessible header component for BOSTARTER
require_once __DIR__ . '/../../backend/utils/NavigationHelper.php';

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = NavigationHelper::isLoggedIn();
$user_name = $is_logged_in ? ($_SESSION['user']['nome'] ?? $_SESSION['user']['nickname'] ?? 'User') : '';
?>

<!-- Skip Links for Accessibility -->
<a href="#main-content" class="skip-link">Vai al contenuto principale</a>
<a href="#search-form" class="skip-link">Vai alla ricerca</a>
<a href="#user-navigation" class="skip-link">Vai alla navigazione utente</a>

<header class="bg-white shadow-sm border-b border-gray-200 sticky-top" role="banner">
    <div class="container-fluid px-4 py-3">
        <div class="row align-items-center">            <!-- Logo and Brand -->
            <div class="col-md-3">
                <a href="<?php echo NavigationHelper::url('home'); ?>" 
                   class="navbar-brand d-flex align-items-center text-decoration-none"
                   aria-label="BOSTARTER - Torna alla homepage">
                    <img src="images/logo1.svg" 
                         alt="" 
                         class="me-2" 
                         style="height: 32px;"
                         role="img"
                         aria-hidden="true">
                    <span class="fw-bold fs-4 text-primary">BOSTARTER</span>
                </a>
            </div>
            
            <!-- Search Bar -->
            <div class="col-md-5">
                <form class="d-flex" 
                      id="search-form" 
                      action="projects/search.php" 
                      method="GET"
                      role="search"
                      aria-label="Ricerca progetti">
                    <div class="input-group">
                        <label for="search-input" class="sr-only">Cerca progetti nella piattaforma</label>
                        <input type="text" 
                               id="search-input"
                               class="form-control" 
                               name="q" 
                               placeholder="Cerca progetti..." 
                               aria-label="Inserisci termini di ricerca"
                               aria-describedby="search-button"
                               autocomplete="off">
                        <button class="btn btn-outline-primary" 
                                type="submit"
                                id="search-button"
                                aria-label="Avvia ricerca">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span class="sr-only">Cerca</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Navigation Links -->
            <div class="col-md-4">
                <nav class="d-flex align-items-center justify-content-end" 
                     id="user-navigation"
                     aria-label="Navigazione principale utente"
                     role="navigation">
                    <?php if ($is_logged_in): ?>                        <!-- Logged in user menu -->
                        <a href="<?php echo NavigationHelper::url('dashboard'); ?>" 
                           class="btn btn-outline-primary me-2"
                           aria-label="Vai alla dashboard personale">
                            <i class="fas fa-tachometer-alt me-1" aria-hidden="true"></i>
                            Dashboard
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" 
                                    type="button" 
                                    id="userDropdown" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false"
                                    aria-haspopup="true"
                                    aria-label="Menu utente: <?php echo htmlspecialchars($user_name); ?>">
                                <i class="fas fa-user me-1" aria-hidden="true"></i>
                                <?php echo htmlspecialchars($user_name); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" 
                                aria-labelledby="userDropdown"
                                role="menu">
                                <li role="none">
                                    <a class="dropdown-item" 
                                       href="<?php echo NavigationHelper::url('profile'); ?>"
                                       role="menuitem"
                                       aria-label="Gestisci il tuo profilo">
                                        <i class="fas fa-user-edit me-2" aria-hidden="true"></i>Profilo
                                    </a>
                                </li>
                                <?php if (NavigationHelper::hasRole('creatore') || NavigationHelper::hasRole('amministratore')): ?>
                                <li role="none">
                                    <a class="dropdown-item" 
                                       href="<?php echo NavigationHelper::url('create_project'); ?>"
                                       role="menuitem"
                                       aria-label="Crea un nuovo progetto">
                                        <i class="fas fa-plus me-2" aria-hidden="true"></i>Nuovo Progetto
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li role="none"><hr class="dropdown-divider"></li>
                                <li role="none">
                                    <a class="dropdown-item" 
                                       href="<?php echo NavigationHelper::url('logout'); ?>"
                                       role="menuitem"
                                       aria-label="Esci dal tuo account">
                                        <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>                    <?php else: ?>
                        <!-- Guest user buttons -->
                        <a href="<?php echo NavigationHelper::url('login'); ?>" 
                           class="btn btn-outline-primary me-2"
                           aria-label="Accedi al tuo account">
                            <i class="fas fa-sign-in-alt me-1" aria-hidden="true"></i>
                            Accedi
                        </a>
                        <a href="<?php echo NavigationHelper::url('register'); ?>" 
                           class="btn btn-primary"
                           aria-label="Crea un nuovo account">
                            <i class="fas fa-user-plus me-1" aria-hidden="true"></i>
                            Registrati
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
</header>
