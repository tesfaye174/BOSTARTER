<?php
// Avviamo la sessione per gestire l'utente loggato
session_start();

// Includiamo le dipendenze necessarie
require_once '../backend/config/database.php';
require_once '../backend/services/MongoLogger.php';
require_once '../backend/utils/NavigationHelper.php';

// Protezione contro loop di redirect
$redirect_count = $_SESSION['redirect_count'] ?? 0;
if ($redirect_count > 3) {
    // Troppi redirect, pulisci la sessione e vai alla home
    session_unset();
    session_destroy();
    header('Location: /BOSTARTER/frontend/index.php');
    exit();
}

// Controlliamo che l'utente sia effettivamente loggato
if (!NavigationHelper::isLoggedIn()) {
    // Incrementa contatore redirect
    $_SESSION['redirect_count'] = $redirect_count + 1;
    // Se non è loggato, lo reindirizziamo al login
    NavigationHelper::redirect('login', ['redirect' => 'dashboard']);
}

// Reset contatore se arriviamo qui con successo
unset($_SESSION['redirect_count']);

// Inizializziamo la connessione al database e i servizi
$database = Database::getInstance();
$connessioneDb = $database->getConnection();
$logger = new MongoLogger();

// Registriamo l'accesso alla dashboard per monitoraggio
$logger->registraAttivitaUtente($_SESSION['user_id'], 'accesso_dashboard', [
    'timestamp' => date('Y-m-d H:i:s')
]);

// Otteniamo le informazioni complete dell'utente
$queryUtente = "SELECT * FROM utenti WHERE id = :user_id";
$statementUtente = $connessioneDb->prepare($queryUtente);
$statementUtente->bindParam(':user_id', $_SESSION['user_id']);
$statementUtente->execute();
$utente = $statementUtente->fetch(PDO::FETCH_ASSOC);

// Verifichiamo che l'utente esista ancora nel database
if (!$utente) {
    // Se l'utente non esiste più, distruggiamo la sessione
    session_destroy();
    NavigationHelper::redirect('login', ['error' => 'Utente non trovato']);
}

// Impostiamo valori predefiniti per i campi mancanti dell'utente
$utente['username'] = $utente['nickname'] ?? $utente['nome'] ?? 'Utente';
$utente['user_type'] = $utente['tipo_utente'] ?? 'standard';

// Inizializziamo le variabili per la dashboard
$progetti_in_evidenza = [];
$progetti_recenti = [];
$statistiche = [
    'progetti_totali' => 0,
    'finanziamenti_totali' => 0,
    'sostenitori_totali' => 0,
    'tasso_successo' => 0
];
$categorie = [
    [
        'id' => 'arte',
        'name' => 'Arte',
        'icon' => 'fas fa-palette',
        'color' => 'from-purple-500 to-pink-500',
        'description' => 'Pittura, scultura, installazioni artistiche e opere d\'arte contemporanee'
    ],
    [
        'id' => 'artigianato',
        'name' => 'Artigianato',
        'icon' => 'fas fa-hammer',
        'color' => 'from-orange-500 to-red-500',
        'description' => 'Creazioni artigianali, oggetti fatti a mano e prodotti tradizionali'
    ],
    [
        'id' => 'cibo',
        'name' => 'Cibo',
        'icon' => 'fas fa-utensils',
        'color' => 'from-green-500 to-emerald-500',
        'description' => 'Progetti culinari, ristoranti, prodotti alimentari e bevande innovative'
    ],
    [
        'id' => 'danza',
        'name' => 'Danza',
        'icon' => 'fas fa-running',
        'color' => 'from-pink-500 to-rose-500',
        'description' => 'Spettacoli di danza, coreografie e progetti di movimento artistico'
    ],
    [
        'id' => 'design',
        'name' => 'Design',
        'icon' => 'fas fa-drafting-compass',
        'color' => 'from-blue-500 to-cyan-500',
        'description' => 'Design industriale, grafico, di prodotto e soluzioni creative'
    ],
    [
        'id' => 'editoriale',
        'name' => 'Editoriale',
        'icon' => 'fas fa-book',
        'color' => 'from-indigo-500 to-purple-500',
        'description' => 'Libri, riviste, pubblicazioni e progetti editoriali indipendenti'
    ],
    [
        'id' => 'film',
        'name' => 'Film',
        'icon' => 'fas fa-film',
        'color' => 'from-gray-600 to-gray-800',
        'description' => 'Cortometraggi, documentari, lungometraggi e produzioni cinematografiche'
    ],
    [
        'id' => 'fotografia',
        'name' => 'Fotografia',
        'icon' => 'fas fa-camera',
        'color' => 'from-yellow-500 to-orange-500',
        'description' => 'Progetti fotografici, mostre, reportage e opere fotografiche artistiche'
    ],
    [
        'id' => 'fumetti',
        'name' => 'Fumetti',
        'icon' => 'fas fa-comments',
        'color' => 'from-red-500 to-pink-500',
        'description' => 'Graphic novel, fumetti, manga e narrativa illustrata'
    ],
    [
        'id' => 'giochi',
        'name' => 'Giochi',
        'icon' => 'fas fa-gamepad',
        'color' => 'from-violet-500 to-purple-500',
        'description' => 'Giochi da tavolo, videogiochi indie e progetti ludici innovativi'
    ],
    [
        'id' => 'giornalismo',
        'name' => 'Giornalismo',
        'icon' => 'fas fa-newspaper',
        'color' => 'from-slate-500 to-gray-600',
        'description' => 'Inchieste giornalistiche, podcast informativi e progetti di citizen journalism'
    ],
    [
        'id' => 'moda',
        'name' => 'Moda',
        'icon' => 'fas fa-tshirt',
        'color' => 'from-fuchsia-500 to-pink-500',
        'description' => 'Collezioni di moda, accessori, abbigliamento sostenibile e fashion tech'
    ],
    [
        'id' => 'musica',
        'name' => 'Musica',
        'icon' => 'fas fa-music',
        'color' => 'from-emerald-500 to-teal-500',
        'description' => 'Album musicali, concerti, strumenti innovativi e progetti sonori'
    ],
    [
        'id' => 'teatro',
        'name' => 'Teatro',
        'icon' => 'fas fa-theater-masks',
        'color' => 'from-amber-500 to-yellow-500',
        'description' => 'Spettacoli teatrali, performance artistiche e produzioni sceniche'
    ],
    [
        'id' => 'tecnologia',
        'name' => 'Tecnologia',
        'icon' => 'fas fa-microchip',
        'color' => 'from-cyan-500 to-blue-500',
        'description' => 'Hardware, software, app, elettronica, robotica, IoT e innovazione tecnologica'
    ]
];
// Get user's projects
try {
    $projects_query = "SELECT p.*, 
                              COALESCE(pf.totale_finanziato, 0) as totale_finanziato,
                              COALESCE(pf.percentuale_finanziamento, 0) as percentuale_finanziamento,
                              COALESCE(pf.numero_sostenitori, 0) as numero_sostenitori,                       DATEDIFF(p.data_limite, NOW()) as giorni_rimasti
                       FROM progetti p
                       LEFT JOIN view_progetti pf ON p.id = pf.progetto_id
                       WHERE p.creatore_id = :user_id
                       ORDER BY p.data_inserimento DESC";
    $projects_stmt = $connessioneDb->prepare($projects_query);
    $projects_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $projects_stmt->execute();
    $user_projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mongoLogger->logActivity($_SESSION['user_id'], 'dashboard_error', [
        'error' => 'Failed to fetch user projects: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    $user_projects = [];
}

// Get user's funding history
try {
    $fundings_query = "SELECT f.*, p.nome as titolo_progetto, p.tipo_progetto,
                              u.nickname as nome_creatore, r.descrizione as titolo_ricompensa
                       FROM finanziamenti f
                       JOIN progetti p ON f.progetto_id = p.id
                       JOIN utenti u ON p.creatore_id = u.id
                       LEFT JOIN reward r ON f.reward_id = r.id
                       WHERE f.utente_id = :user_id                       ORDER BY f.data_finanziamento DESC
                       LIMIT 10";
    $fundings_stmt = $connessioneDb->prepare($fundings_query);
    $fundings_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $fundings_stmt->execute();
    $user_fundings = $fundings_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mongoLogger->logActivity($_SESSION['user_id'], 'dashboard_error', [
        'error' => 'Failed to fetch user fundings: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    $user_fundings = [];
}

// Get user's applications (for software projects)
try {
    $applications_query = "SELECT c.*, p.nome as titolo_progetto, ps.nome as nome_profilo,
                                  u.nickname as nome_creatore
                           FROM candidature c
                           JOIN progetti p ON c.progetto_id = p.id
                           JOIN profili_software ps ON c.profilo_id = ps.id
                           JOIN utenti u ON p.creatore_id = u.id
                           WHERE c.utente_id = :user_id
                           ORDER BY c.data_candidatura DESC";
    $applications_stmt = $connessioneDb->prepare($applications_query);
    $applications_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $applications_stmt->execute();
    $user_applications = $applications_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mongoLogger->logActivity($_SESSION['user_id'], 'dashboard_error', [
        'error' => 'Failed to fetch user applications: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    $user_applications = [];
}

// Get recent activities
try {
    $activities_query = "SELECT 'progetto_creato' as tipo_attivita, p.nome, p.data_inserimento as data_attivita, 
                                'Hai creato un nuovo progetto' as descrizione
                         FROM progetti p 
                         WHERE p.creatore_id = ?
                         UNION ALL
                         SELECT 'finanziamento_effettuato' as tipo_attivita, p.nome, f.data_finanziamento as data_attivita,
                                CONCAT('Hai finanziato €', f.importo) as descrizione
                         FROM finanziamenti f
                         JOIN progetti p ON f.progetto_id = p.id
                         WHERE f.utente_id = ?
                         UNION ALL
                         SELECT 'candidatura_inviata' as tipo_attivita, p.nome, c.data_candidatura as data_attivita,
                                CONCAT('Hai inviato candidatura per ', ps.nome) as descrizione
                         FROM candidature c
                         JOIN progetti p ON c.progetto_id = p.id
                         JOIN profili_software ps ON c.profilo_id = ps.id
                         WHERE c.utente_id = ?
                         ORDER BY data_attivita DESC                       LIMIT 10";
    $activities_stmt = $connessioneDb->prepare($activities_query);
    $activities_stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $recent_activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mongoLogger->logActivity($_SESSION['user_id'], 'dashboard_error', [
        'error' => 'Failed to fetch recent activities: ' . $e->getMessage(),
        'query' => 'recent_activities',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    $recent_activities = [];
}

// Calculate user stats
$total_created = count($user_projects);
$total_funded_amount = array_sum(array_column($user_fundings, 'importo'));
$total_applications = count($user_applications);
$successful_projects = count(array_filter($user_projects, function($p) { return $p['stato'] === 'finanziato'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Personal BOSTARTER Dashboard - Manage your projects and profile">
    <title>User Dashboard - BOSTARTER</title>
      <!-- Preload critical resources -->
    <link rel="preload" href="/BOSTARTER/frontend/js/dashboard.js" as="script">
    <link rel="preload" href="/BOSTARTER/frontend/css/dashboard.css" as="style">
      <!-- Core CSS -->
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/critical.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/main.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/color-system.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/components.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/utilities.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/accessibility.css">
    
    <!-- Dashboard-specific CSS -->
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/dashboard.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/notifications.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/animations.css">
    
    <!-- Optimized fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    
    <!-- Optimized icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/tailwind-config.js"></script>
    
    <!-- CSS -->    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard-custom.css">
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Skip link -->
    <a href="#main-content" class="skip-link" tabindex="0">Go to main content</a>

    <!-- Notifications container -->
    <div id="notifications-container" class="fixed top-4 right-4 z-50 space-y-2 max-w-sm" role="alert" aria-live="polite"></div>    <!-- Modern Dashboard Header -->
    <header class="bg-white/80 dark:bg-gray-800/80 shadow-sm sticky top-0 z-50 transition-colors duration-300 backdrop-blur-sm">
        <nav class="container mx-auto px-4 py-3" role="navigation" aria-label="Navigazione principale dashboard">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="index.php" class="flex items-center font-brand text-2xl text-primary hover:text-primary-600 transition-colors focus-visible" aria-label="BOSTARTER Homepage">
                    <i class="fas fa-rocket mr-2 text-primary" aria-hidden="true"></i>
                    BOSTARTER
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center space-x-6" role="navigation" aria-label="Menu principale">
                    <a href="dashboard.php" class="flex items-center text-primary font-medium transition-colors hover:text-primary-600 focus-visible">
                        <i class="fas fa-tachometer-alt mr-2" aria-hidden="true"></i>
                        Dashboard
                    </a>
                    <a href="projects/list_open.php" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-primary transition-colors focus-visible">
                        <i class="fas fa-list mr-2" aria-hidden="true"></i>
                        Progetti
                    </a>
                    <a href="projects/create.php" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-primary transition-colors focus-visible">
                        <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                        Crea Progetto
                    </a>
                    
                    <!-- Statistics Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center text-gray-600 dark:text-gray-300 hover:text-primary transition-colors focus-visible">
                            <i class="fas fa-chart-bar mr-2" aria-hidden="true"></i>
                            Statistiche
                            <i class="fas fa-chevron-down ml-1 text-xs" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown absolute top-full left-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <a href="stats/top_creators.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Top Creators</a>
                            <a href="stats/close_to_goal.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Close to Goal</a>
                        </div>
                    </div>                    <!-- Admin Menu (if applicable) -->
                    <?php if ($utente['user_type'] === 'admin'): ?>
                    <div class="relative group">
                        <button class="flex items-center text-gray-600 dark:text-gray-300 hover:text-primary transition-colors focus-visible">
                            <i class="fas fa-cog mr-2" aria-hidden="true"></i>
                            Admin
                            <i class="fas fa-chevron-down ml-1 text-xs" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown absolute top-full left-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <a href="admin/add_skill.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Manage Skills</a>
                            <a href="admin/mongodb_monitor.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">MongoDB Monitor</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- User Actions -->
                <div class="flex items-center gap-3">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" 
                            class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus-visible" 
                            aria-label="Cambia tema">
                        <i class="ri-sun-line dark:hidden text-xl" aria-hidden="true"></i>
                        <i class="ri-moon-line hidden dark:block text-xl" aria-hidden="true"></i>
                    </button>

                    <!-- Modern User Menu (from index.php) -->
                    <div class="user-menu-container" id="user-menu-container">
                        <button id="user-menu-button" 
                                class="user-menu-button flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                aria-label="Menu utente"
                                aria-expanded="false"
                                aria-haspopup="true">                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($utente['username']); ?>&background=667eea&color=fff" 
                                 alt="" class="w-8 h-8 rounded-full" aria-hidden="true">
                            <span class="hidden sm:block text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($utente['username']); ?></span>
                            <i class="fas fa-chevron-down text-sm transition-transform" aria-hidden="true"></i>
                        </button>
                        <div id="user-menu-dropdown" class="user-menu-dropdown absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 opacity-0 invisible transition-all duration-200">
                            <a href="<?php echo NavigationHelper::url('dashboard'); ?>" class="user-menu-item block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-tachometer-alt mr-2" aria-hidden="true"></i>Dashboard
                            </a>
                            <a href="<?php echo NavigationHelper::url('profile'); ?>" class="user-menu-item block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-user-edit mr-2" aria-hidden="true"></i>Profilo
                            </a>
                            <a href="<?php echo NavigationHelper::url('create_project'); ?>" class="user-menu-item block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-plus-circle mr-2" aria-hidden="true"></i>Crea Progetto
                            </a>
                            <div class="user-menu-divider border-t border-gray-200 dark:border-gray-600 my-1"></div>
                            <a href="<?php echo NavigationHelper::url('logout'); ?>" class="user-menu-item block px-4 py-2 text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i>Logout
                            </a>
                        </div>
                    </div>

                    <!-- Mobile Menu Toggle -->
                    <button id="mobile-menu-toggle" 
                            class="lg:hidden p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus-visible"
                            aria-label="Apri menu mobile"
                            aria-expanded="false"
                            aria-controls="mobile-menu">
                        <i class="fas fa-bars text-xl" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <nav id="mobile-menu" class="lg:hidden hidden mt-4 pb-4 border-t border-gray-200 dark:border-gray-700 pt-4" role="menu" aria-label="Menu mobile">
                <div class="space-y-2">
                    <a href="dashboard.php" class="flex items-center px-3 py-2 text-primary font-medium rounded-lg bg-primary/10" role="menuitem">
                        <i class="fas fa-tachometer-alt mr-3" aria-hidden="true"></i>
                        Dashboard
                    </a>
                    <a href="projects/list_open.php" class="flex items-center px-3 py-2 text-gray-600 dark:text-gray-300 hover:text-primary hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors" role="menuitem">
                        <i class="fas fa-list mr-3" aria-hidden="true"></i>
                        Progetti
                    </a>
                    <a href="projects/create.php" class="flex items-center px-3 py-2 text-gray-600 dark:text-gray-300 hover:text-primary hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors" role="menuitem">
                        <i class="fas fa-plus mr-3" aria-hidden="true"></i>
                        Crea Progetto
                    </a>
                    <a href="stats/top_creators.php" class="flex items-center px-3 py-2 text-gray-600 dark:text-gray-300 hover:text-primary hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors" role="menuitem">
                        <i class="fas fa-chart-bar mr-3" aria-hidden="true"></i>
                        Statistiche                    </a>
                    <?php if ($utente['user_type'] === 'admin'): ?>
                    <a href="admin/add_skill.php" class="flex items-center px-3 py-2 text-gray-600 dark:text-gray-300 hover:text-primary hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors" role="menuitem">
                        <i class="fas fa-cog mr-3" aria-hidden="true"></i>
                        Admin
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
            
                <!-- User Actions Enhanced -->
                <div class="flex items-center space-x-3" role="group" aria-label="Azioni utente">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" 
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus-visible" 
                            aria-label="Cambia tema">
                        <i class="ri-sun-line dark:hidden text-xl text-gray-600" aria-hidden="true"></i>
                        <i class="ri-moon-line hidden dark:block text-xl text-gray-300" aria-hidden="true"></i>
                    </button>

                    <!-- Notification Bell (for logged in users) -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors focus-visible" 
                            aria-label="Notifiche">
                        <i class="fas fa-bell text-xl text-gray-600" aria-hidden="true"></i>
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                    </button>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Enhanced User Menu -->
                        <div class="user-menu-container-enhanced" id="user-menu-container">
                            <button id="user-menu-button" 
                                    class="user-menu-button-enhanced group flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-50 transition-all duration-200"
                                    aria-label="Menu utente"
                                    aria-expanded="false"
                                    aria-haspopup="true">
                                <div class="relative">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username'] ?? 'User'); ?>&background=667eea&color=fff" 
                                         alt="" class="w-10 h-10 rounded-full border-2 border-white shadow-lg" aria-hidden="true">
                                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
                                </div>
                                <div class="hidden md:block text-left">
                                    <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                                    <p class="text-xs text-gray-500">Online</p>
                                </div>
                                <i class="fas fa-chevron-down text-sm text-gray-400 group-hover:text-gray-600 transition-all duration-200 group-hover:rotate-180" aria-hidden="true"></i>
                            </button>
                            <div id="user-menu-dropdown" class="user-menu-dropdown-enhanced absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 opacity-0 invisible transition-all duration-200">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                                    <p class="text-xs text-gray-500">Membro dal 2024</p>
                                </div>
                                <a href="<?php echo NavigationHelper::url('dashboard'); ?>" class="user-menu-item-enhanced flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-tachometer-alt mr-3 text-blue-500" aria-hidden="true"></i>Dashboard
                                </a>
                                <a href="<?php echo NavigationHelper::url('profile'); ?>" class="user-menu-item-enhanced flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-user-edit mr-3 text-green-500" aria-hidden="true"></i>Profilo
                                </a>
                                <a href="<?php echo NavigationHelper::url('create_project'); ?>" class="user-menu-item-enhanced flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-plus-circle mr-3 text-purple-500" aria-hidden="true"></i>Crea Progetto
                                </a>
                                <a href="<?php echo NavigationHelper::url('settings'); ?>" class="user-menu-item-enhanced flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-cog mr-3 text-gray-500" aria-hidden="true"></i>Impostazioni
                                </a>
                                <div class="border-t border-gray-100 mt-1"></div>
                                <a href="<?php echo NavigationHelper::url('logout'); ?>" class="user-menu-item-enhanced flex items-center px-4 py-3 text-red-600 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-sign-out-alt mr-3" aria-hidden="true"></i>Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        
                    <!-- Enhanced Mobile Menu Toggle -->
                    <button id="mobile-menu-toggle" 
                            class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors focus-visible"
                            aria-label="Apri menu di navigazione"
                            aria-expanded="false"
                            aria-controls="mobile-menu"
                            type="button">
                        <div class="w-6 h-6 flex flex-col justify-center space-y-1">
                            <span class="w-full h-0.5 bg-gray-600 rounded transition-all duration-300"></span>
                            <span class="w-full h-0.5 bg-gray-600 rounded transition-all duration-300"></span>
                            <span class="w-full h-0.5 bg-gray-600 rounded transition-all duration-300"></span>
                        </div>                        <span class="sr-only">Menu</span>
                    </button>
                <?php endif; ?>
                </div>
            </div>
        </nav><!-- Enhanced Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu-enhanced lg:hidden overflow-hidden transition-all duration-300 max-h-0" role="menu" aria-label="Menu di navigazione mobile">
            <div class="px-4 py-6 space-y-4 bg-white border-t border-gray-100">
                <!-- Mobile Navigation Links -->
                <div class="space-y-2">
                    <a href="<?php echo NavigationHelper::url('hardware_projects'); ?>" 
                       class="mobile-nav-link-enhanced flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors" 
                       role="menuitem">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-microchip text-white text-sm" aria-hidden="true"></i>
                        </div>
                        <span class="font-medium text-gray-700">Hardware</span>
                    </a>
                    <a href="<?php echo NavigationHelper::url('software_projects'); ?>" 
                       class="mobile-nav-link-enhanced flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors" 
                       role="menuitem">
                        <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-code text-white text-sm" aria-hidden="true"></i>
                        </div>
                        <span class="font-medium text-gray-700">Software</span>
                    </a>
                    <a href="<?php echo NavigationHelper::url('projects'); ?>" 
                       class="mobile-nav-link-enhanced flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors" 
                       role="menuitem">
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-list text-white text-sm" aria-hidden="true"></i>
                        </div>
                        <span class="font-medium text-gray-700">Tutti i Progetti</span>
                    </a>
                    <a href="<?php echo NavigationHelper::url('about'); ?>" 
                       class="mobile-nav-link-enhanced flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors" 
                       role="menuitem">
                        <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-info-circle text-white text-sm" aria-hidden="true"></i>
                        </div>
                        <span class="font-medium text-gray-700">Chi Siamo</span>
                    </a>
                </div>

                <!-- Mobile Auth Actions -->
                <div class="pt-4 border-t border-gray-200" role="group" aria-label="Azioni utente mobile">
                    <?php if (NavigationHelper::isLoggedIn()): ?>
                        <!-- Logged in mobile menu -->
                        <div class="space-y-2">
                            <a href="<?php echo NavigationHelper::url('dashboard'); ?>" 
                               class="mobile-auth-link-enhanced flex items-center space-x-3 p-3 bg-primary/5 text-primary rounded-lg font-medium">
                               <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                               <span>Dashboard</span>
                            </a>
                            <a href="<?php echo NavigationHelper::url('create_project'); ?>" 
                               class="mobile-auth-link-enhanced flex items-center space-x-3 p-3 bg-green-50 text-green-700 rounded-lg font-medium">
                               <i class="fas fa-plus-circle" aria-hidden="true"></i>
                               <span>Crea Progetto</span>
                            </a>
                            <a href="<?php echo NavigationHelper::url('profile'); ?>" 
                               class="mobile-auth-link-enhanced flex items-center space-x-3 p-3 hover:bg-gray-50 text-gray-700 rounded-lg">
                               <i class="fas fa-user-edit" aria-hidden="true"></i>
                               <span>Profilo</span>
                            </a>
                            <a href="<?php echo NavigationHelper::url('logout'); ?>" 
                               class="mobile-auth-link-enhanced flex items-center space-x-3 p-3 bg-red-50 text-red-600 rounded-lg font-medium">
                               <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                               <span>Logout</span>
                            </a>                        </div>
                    <?php else: ?>
                        <!-- Guest content could go here -->
                    <?php endif; ?>
        </nav>
    </header>

    <!-- Main Content -->
    <main id="main-content" class="container mx-auto px-4 py-8">        <!-- Hero Section -->        <section class="mb-8 animate-fade-in">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex justify-between items-center mb-2">                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Welcome back, <?php echo htmlspecialchars($utente['username']); ?>! 👋
                    </h1>
                    <button onclick="refreshDashboard()" 
                            class="flex items-center px-3 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors focus-visible">
                        <i class="fas fa-sync-alt mr-2" aria-hidden="true"></i>
                        Refresh
                    </button>
                </div>
                <p class="text-gray-600 dark:text-gray-300">Here's your BOSTARTER activity overview</p>
            </div>
        </section>

        <!-- Statistics Cards -->
        <section class="mb-8 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="stat-card p-6 text-center">
                    <i class="fas fa-project-diagram text-3xl mb-4" aria-hidden="true"></i>
                    <h3 class="text-2xl font-bold"><?php echo $total_created; ?></h3>
                    <p class="opacity-90">Projects Created</p>
                </div>
                <div class="stat-card p-6 text-center">
                    <i class="fas fa-dollar-sign text-3xl mb-4" aria-hidden="true"></i>
                    <h3 class="text-2xl font-bold">$<?php echo number_format($total_funded_amount, 0); ?></h3>
                    <p class="opacity-90">Total Funded</p>
                </div>
                <div class="stat-card p-6 text-center">
                    <i class="fas fa-user-plus text-3xl mb-4" aria-hidden="true"></i>
                    <h3 class="text-2xl font-bold"><?php echo $total_applications; ?></h3>
                    <p class="opacity-90">Applications</p>
                </div>
                <div class="stat-card p-6 text-center">
                    <i class="fas fa-trophy text-3xl mb-4" aria-hidden="true"></i>
                    <h3 class="text-2xl font-bold"><?php echo $successful_projects; ?></h3>
                    <p class="opacity-90">Successful Projects</p>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- My Projects Section -->
            <div class="lg:col-span-2 animate-fade-in" style="animation-delay: 0.2s;">
                <div class="dashboard-card p-6">                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-folder mr-3 text-primary" aria-hidden="true"></i>
                            My Projects
                        </h2>
                        <a href="projects/create.php" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors focus-visible">
                            <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                            Create New
                        </a>
                    </div>

                    <?php if (empty($user_projects)): ?>
                        <div class="text-center py-12">
                            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-folder-open text-3xl text-gray-400" aria-hidden="true"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No projects yet</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">Ready to launch your first project?</p>
                            <a href="projects/create.php" class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors focus-visible">
                                <i class="fas fa-rocket mr-2" aria-hidden="true"></i>
                                Create Your First Project
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach (array_slice($user_projects, 0, 4) as $project): ?>                                <div class="project-card border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="font-medium text-gray-900 dark:text-white line-clamp-1"><?php echo htmlspecialchars($project['nome']); ?></h3>
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            <?php echo $project['stato'] === 'aperto' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                     ($project['stato'] === 'finanziato' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                      'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'); ?>">
                                            <?php echo ucfirst($project['stato']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3"><?php echo ucfirst($project['tipo_progetto']); ?> Project</p>
                                    
                                    <div class="mb-3">
                                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                            <span>$<?php echo number_format($project['totale_finanziato'], 0); ?></span>
                                            <span><?php echo round($project['percentuale_finanziamento'], 1); ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="progress-bar-custom h-2 rounded-full transition-all duration-500" 
                                                 style="width: <?php echo min(100, $project['percentuale_finanziamento']); ?>%"></div>
                                        </div>
                                    </div>
                                      <div class="flex gap-2">
                                        <a href="projects/detail.php?id=<?php echo $project['id']; ?>" 
                                           class="flex-1 text-center px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                            View Details
                                        </a>
                                        <?php if ($project['stato'] === 'aperto'): ?>
                                            <a href="projects/add_reward.php?id=<?php echo $project['id']; ?>" 
                                               class="flex-1 text-center px-3 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                                                Manage
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($user_projects) > 4): ?>
                            <div class="text-center mt-6">
                                <p class="text-gray-500 dark:text-gray-400">And <?php echo count($user_projects) - 4; ?> more projects...</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-card p-6 mt-6 animate-fade-in" style="animation-delay: 0.3s;">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center mb-6">
                        <i class="fas fa-clock mr-3 text-primary" aria-hidden="true"></i>
                        Recent Activity
                    </h2>
                    
                    <?php if (empty($recent_activities)): ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-2xl text-gray-400" aria-hidden="true"></i>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">No recent activity</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item p-4 rounded-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-medium text-gray-900 dark:text-white mb-1"><?php echo htmlspecialchars($activity['nome']); ?></h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($activity['descrizione']); ?></p>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('M j', strtotime($activity['data_attivita'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6 animate-fade-in" style="animation-delay: 0.4s;">
                <!-- Quick Actions -->
                <div class="dashboard-card p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center mb-4">
                        <i class="fas fa-bolt mr-3 text-primary" aria-hidden="true"></i>
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="projects/create.php" class="w-full flex items-center justify-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors focus-visible">
                            <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                            Create Project
                        </a>
                        <a href="projects/list_open.php" class="w-full flex items-center justify-center px-4 py-3 border border-primary text-primary rounded-lg hover:bg-primary/5 transition-colors focus-visible">
                            <i class="fas fa-search mr-2" aria-hidden="true"></i>
                            Browse Projects
                        </a>
                    </div>
                </div>

                <!-- Recent Support -->
                <div class="dashboard-card p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center mb-4">
                        <i class="fas fa-heart mr-3 text-primary" aria-hidden="true"></i>
                        Recent Support
                    </h3>
                    <?php if (empty($user_fundings)): ?>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No funding history yet</p>
                    <?php else: ?>
                        <div class="space-y-3">                            <?php foreach (array_slice($user_fundings, 0, 3) as $funding): ?>
                                <div class="flex justify-between items-center">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($funding['titolo_progetto']); ?></h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">by <?php echo htmlspecialchars($funding['nome_creatore']); ?></p>
                                    </div>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 text-xs rounded-full">
                                        $<?php echo number_format($funding['importo'], 0); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Applications Status -->
                <?php if (!empty($user_applications)): ?>
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center mb-4">
                            <i class="fas fa-file-alt mr-3 text-primary" aria-hidden="true"></i>
                            My Applications
                        </h3>
                        <div class="space-y-3">
                            <?php foreach (array_slice($user_applications, 0, 3) as $application): ?>                                <div class="flex justify-between items-center">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($application['titolo_progetto']); ?></h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($application['nome_profilo']); ?></p>
                                    </div>                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $application['stato'] === 'accettata' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                 ($application['stato'] === 'in_attesa' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                                  'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'); ?>">
                                        <?php echo ucfirst($application['stato']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modern Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-rocket text-2xl text-primary" aria-hidden="true"></i>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">BOSTARTER</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <a href="/about" class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">About Us</a>
                        <a href="/contact" class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">Contact</a>
                        <a href="/faq" class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">FAQ</a>
                    </div>
                    <div class="space-y-2">
                        <a href="/terms" class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">Terms</a>
                        <a href="/privacy" class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">Privacy</a>
                        <a href="/help" class="block text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">Help</a>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-primary transition-colors" aria-label="Twitter">
                        <i class="fab fa-twitter text-xl" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-primary transition-colors" aria-label="Facebook">
                        <i class="fab fa-facebook text-xl" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-primary transition-colors" aria-label="Instagram">
                        <i class="fab fa-instagram text-xl" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-primary transition-colors" aria-label="LinkedIn">
                        <i class="fab fa-linkedin text-xl" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-6 text-center">
                <p class="text-gray-600 dark:text-gray-300">
                    &copy; <span id="current-year">2024</span> BOSTARTER. All rights reserved.
                </p>
            </div>
        </div>
    </footer>    <!-- Profile Modal -->
    <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center" onclick="closeModalOnOutsideClick(event)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 transform transition-transform" onclick="event.stopPropagation()">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">User Profile</h2>
                    <button onclick="document.getElementById('profileModal').style.display='none'" 
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors">
                        <i class="fas fa-times text-gray-500" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-center mb-4">                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($utente['username']); ?>&size=80&background=667eea&color=fff" 
                             alt="Profile Avatar" class="w-20 h-20 rounded-full">
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Username:</span>
                            <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($utente['username']); ?></span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Email:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($utente['email']); ?></span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">User Type:</span>                                <span class="px-2 py-1 bg-primary text-white text-sm rounded-full">
                                    <?php echo ucfirst($utente['user_type']); ?>
                                </span>
                            </div>
                        </div>                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Member Since:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo isset($utente['data_registrazione']) ? date('F j, Y', strtotime($utente['data_registrazione'])) : 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                <button onclick="document.getElementById('profileModal').style.display='none'" 
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Close
                </button>
            </div>        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settingsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center" onclick="closeSettingsModalOnOutsideClick(event)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 transform transition-transform" onclick="event.stopPropagation()">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Dashboard Settings</h2>
                    <button onclick="document.getElementById('settingsModal').style.display='none'" 
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors">
                        <i class="fas fa-times text-gray-500" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-6">
                    <!-- Theme Settings -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Appearance</h3>
                        <div class="space-y-3">
                            <label class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">Dark Mode</span>
                                <input type="checkbox" id="settings-dark-mode" class="toggle-switch" onchange="toggleThemeFromSettings()">
                            </label>
                            <label class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">Animations</span>
                                <input type="checkbox" id="settings-animations" class="toggle-switch" checked onchange="toggleAnimations()">
                            </label>
                        </div>
                    </div>
                    
                    <!-- Notification Settings -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Notifications</h3>
                        <div class="space-y-3">
                            <label class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">Project Updates</span>
                                <input type="checkbox" id="settings-project-notifications" class="toggle-switch" checked>
                            </label>
                            <label class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">Funding Alerts</span>
                                <input type="checkbox" id="settings-funding-notifications" class="toggle-switch" checked>
                            </label>
                            <label class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">Email Notifications</span>
                                <input type="checkbox" id="settings-email-notifications" class="toggle-switch">
                            </label>
                        </div>
                    </div>

                    <!-- Dashboard Layout -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Dashboard Layout</h3>
                        <div class="space-y-3">
                            <label class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">Compact View</span>
                                <input type="checkbox" id="settings-compact-view" class="toggle-switch" onchange="toggleCompactView()">
                            </label>
                            <label class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">Auto-refresh Data</span>
                                <input type="checkbox" id="settings-auto-refresh" class="toggle-switch" checked onchange="toggleAutoRefresh()">
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                <button onclick="resetSettings()" 
                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                    Reset to Default
                </button>
                <div class="space-x-3">
                    <button onclick="document.getElementById('settingsModal').style.display='none'" 
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button onclick="saveSettings()" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                        Save Settings
                    </button>
                </div>
            </div>
    </div>
    </div>    <!-- Core JavaScript -->
    <script src="js/core/Utils.js"></script>
    <script src="js/theme.js"></script>
    <script src="js/notifications.js"></script>
    <script src="js/navigation.js"></script>
    
    <!-- Dashboard Features -->
    <script src="js/projects.js"></script>
    <script src="js/modal-accessibility.js"></script>
    
    <!-- Chart Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <!-- Dashboard Core -->
    <script src="js/dashboard.js" type="module"></script>
    
    <!-- Dashboard Initialization -->
    <script src="js/dashboard-init.js"></script>
      <!-- Performance Monitoring -->
    <script src="js/performance.js" async></script>
</body>
</html>
