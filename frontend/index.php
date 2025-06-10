<?php
// Gestione degli errori e caricamento sicuro delle dipendenze
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Caricamento sicuro del SecurityMiddleware
    require_once __DIR__ . '/../backend/middleware/SecurityMiddleware.php';
    
    // Verifica che la classe e il metodo esistano prima di chiamarli
    if (class_exists('SecurityMiddleware') && method_exists('SecurityMiddleware', 'initialize')) {
        SecurityMiddleware::initialize();
    } else {
        // Fallback per inizializzazione di sicurezza base
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
        error_log("SecurityMiddleware non disponibile - usando configurazione di sicurezza base");
    }
} catch (Exception $e) {
    error_log("Errore nel caricamento SecurityMiddleware: " . $e->getMessage());
    // Inizializzazione di sicurezza minimale
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Impostazioni di produzione - errori disabilitati per sicurezza
error_reporting(0);
ini_set('display_errors', 0);

// Includi dipendenze principali
try {
    require_once __DIR__ . '/../backend/config/database.php';
    require_once __DIR__ . '/../backend/services/MongoLogger.php';
    require_once __DIR__ . '/../backend/utils/NavigationHelper.php';
} catch (Exception $e) {
    error_log("Impossibile includere i file richiesti: " . $e->getMessage()); // Commento in italiano
}

// Inizializza variabili
$featured_projects = [];
$recent_projects = [];
$stats = [
    'total_projects' => 0,
    'total_funding' => 0,
    'total_backers' => 0,
    'success_rate' => 0
];
$categories = [
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
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Connessione DB e logger
$db = null;
$mongoLogger = null;
try {
    if (class_exists('Database')) {
        $db = Database::getInstance()->getConnection();
        if ($db && class_exists('MongoLogger')) {
            $mongoLogger = new MongoLogger();
            // Log della visita alla homepage
            if ($is_logged_in) {
                $mongoLogger->logActivity($_SESSION['user_id'], 'homepage_visit', [
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Utente non loggato, nessun log
            }
        }
    }
} catch (Exception $e) {
    error_log("Errore nella connessione al database o logger: " . $e->getMessage());
    $db = null;
}

// Statistiche piattaforma
if ($db) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM progetti WHERE stato IN ('aperto', 'chiuso')");
        $stats['total_projects'] = ($row = $stmt->fetch(PDO::FETCH_ASSOC)) ? (int)$row['count'] : 0;
        $stmt = $db->query("SELECT SUM(importo) as total FROM finanziamenti WHERE stato_pagamento = 'completato'");
        $stats['total_funding'] = ($row = $stmt->fetch(PDO::FETCH_ASSOC)) ? (float)$row['total'] : 0;
        $stmt = $db->query("SELECT COUNT(DISTINCT utente_id) as count FROM finanziamenti WHERE stato_pagamento = 'completato'");
        $stats['total_backers'] = ($row = $stmt->fetch(PDO::FETCH_ASSOC)) ? (int)$row['count'] : 0;
        $total_projects = $stats['total_projects'];
        if ($total_projects > 0) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM progetti WHERE stato = 'chiuso'");
            $completed = ($row = $stmt->fetch(PDO::FETCH_ASSOC)) ? (int)$row['count'] : 0;
            $stats['success_rate'] = round(($completed / $total_projects) * 100, 1);
        }
    } catch (Exception $e) {
        error_log("Statistics query error: " . $e->getMessage());
    }
}

// Progetti in evidenza (top 3)
if ($db) {
    try {
        $stmt = $db->prepare("
            SELECT p.id, p.nome as title, p.descrizione as description, p.budget_richiesto as funding_goal,
                   COALESCE(SUM(f.importo), 0) as current_funding, p.foto as image, p.tipo_progetto as category,
                   p.data_limite as deadline, u.nickname as creator_name, u.id as creator_id,
                   ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) as funding_percentage,
                   DATEDIFF(p.data_limite, NOW()) as days_left, COUNT(DISTINCT f.utente_id) as backers_count
            FROM progetti p
            JOIN utenti u ON p.creatore_id = u.id
            LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato'
            WHERE p.stato = 'aperto' AND p.data_limite > NOW()
            GROUP BY p.id, p.nome, p.descrizione, p.budget_richiesto, p.foto, p.tipo_progetto, p.data_limite, u.nickname, u.id
            ORDER BY current_funding DESC
            LIMIT 3");
        $stmt->execute();
        $featured_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $featured_projects = [];
        error_log("Error fetching featured projects: " . $e->getMessage());
    }
}

// Progetti recenti (ultimi 6)
if ($db) {
    try {
        $stmt = $db->prepare("
            SELECT p.id, p.nome as title, p.descrizione as description, p.budget_richiesto as funding_goal,
                   COALESCE(SUM(f.importo), 0) as current_funding, p.foto as image, p.tipo_progetto as category,
                   u.nickname as creator_name, ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) as funding_percentage,
                   DATEDIFF(p.data_limite, NOW()) as days_left
            FROM progetti p
            JOIN utenti u ON p.creatore_id = u.id
            LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato'
            WHERE p.stato = 'aperto' AND p.data_limite > NOW()
            GROUP BY p.id, p.nome, p.descrizione, p.budget_richiesto, p.foto, p.tipo_progetto, u.nickname
            ORDER BY p.data_inserimento DESC
            LIMIT 6");
        $stmt->execute();
        $recent_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $recent_projects = [];
        error_log("Error fetching recent projects: " . $e->getMessage());
    }
}

// Formatta statistiche per la view
$formatted_stats = [
    'projects' => number_format($stats['total_projects']) . '+',
    'funded' => '€' . number_format($stats['total_funding'] / 1000, 1) . 'K',
    'backers' => number_format($stats['total_backers']) . '+',
    'success_rate' => $stats['success_rate'] . '%'
];

// Funzioni helper
function formatCurrency($amount) {
    if ($amount >= 1000000) return '€' . number_format($amount / 1000000, 1) . 'M';
    if ($amount >= 1000) return '€' . number_format($amount / 1000, 1) . 'K';
    return '€' . number_format($amount);
}
function getDaysLeftText($days) {
    if ($days <= 0) return 'Scaduto';
    if ($days == 1) return '1 giorno rimasto';
    return $days . ' giorni rimasti';
}
function truncateText($text, $length = 100) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSTARTER </title>
    <meta name="description" content="BOSTARTER è la piattaforma italiana di crowdfunding specializzata in progetti Hardware e Software. Scopri, sostieni o lancia il tuo progetto tecnologico innovativo.">
    <meta name="keywords" content="crowdfunding, hardware, software, progetti tecnologici, finanziamento collettivo, startup tech, innovazione, elettronica, applicazioni">
    <meta name="author" content="BOSTARTER">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#3176FF">
    <link rel="canonical" href="https://www.bostarter.it">     <!--  Alcuni file CSS  -->
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/design-system.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/components.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/critical.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/main.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/index-enhancements.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/homepage-enhancements.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/accessibility.css">
    
    <!-- Icon Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Debug assets existence check (dev only) -->
    <?php    // Controllo esistenza risorse statiche principali (solo in dev)
    if (isset($_GET['debug']) && $_GET['debug'] === 'assets') {
        $assets = [
            __DIR__ . '/images/logo1.svg',
            __DIR__ . '/css/design-system.css',
            __DIR__ . '/css/components.css',
            __DIR__ . '/css/critical.css',
            __DIR__ . '/css/main.css',
            __DIR__ . '/css/index-enhancements.css',
            __DIR__ . '/css/homepage-enhancements.css',
            __DIR__ . '/css/accessibility.css',
        ];
        foreach ($assets as $asset) {
            if (!file_exists($asset)) {
                echo '<div style="background:#ffdddd;color:#a00;padding:8px 16px;margin:8px 0;border:1px solid #a00;font-family:monospace;">Risorsa mancante: ' . htmlspecialchars(basename($asset)) . ' (' . htmlspecialchars($asset) . ')</div>';
            }
        }
    }
    ?>
</head>

<body class="bg-tertiary min-h-screen">
    <!-- Skip Links for Accessibility -->
    <a href="#main-content" class="skip-link focus-visible">Salta al contenuto principale</a>
    <a href="#navigation" class="skip-link focus-visible">Salta alla navigazione</a>
    
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300" aria-hidden="true">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand"></div>
        <span class="sr-only">Caricamento in corso...</span>
    </div>
    
    <!-- Notifications Container -->
    <div id="notifications-container" class="fixed top-4 right-4 z-40 space-y-2 max-w-sm" role="region" aria-live="polite" aria-label="Notifiche"></div>    <!-- Modern Enhanced Header -->
    <header class="modern-header bg-white/95 backdrop-blur-md shadow-xl border-b border-gray-100 sticky top-0 z-50 transition-all duration-300" role="banner">
        <nav class="container mx-auto px-4 py-4" role="navigation" aria-label="Navigazione principale">
            <div class="flex items-center justify-between">
                <!-- Enhanced Logo/Brand -->
                <a href="/BOSTARTER/frontend/" class="navbar-brand-enhanced group">
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary to-primary-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110">
                                <i class="fas fa-rocket text-white text-lg group-hover:animate-pulse" aria-hidden="true"></i>
                            </div>
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="font-brand text-2xl font-bold bg-gradient-to-r from-primary to-primary-600 bg-clip-text text-transparent">
                                BOSTARTER
                            </h1>
                            <p class="text-xs text-gray-500 -mt-1">Crowdfunding Innovativo</p>
                        </div>
                    </div>
                </a>                <!-- Desktop Navigation Enhanced -->
                <div class="hidden lg:flex items-center space-x-1" role="menubar">
                    <a href="<?php echo NavigationHelper::url('hardware_projects'); ?>" 
                       class="nav-link-enhanced group" 
                       role="menuitem">
                        <div class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-primary/5 transition-all duration-200">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-microchip text-white text-sm" aria-hidden="true"></i>
                            </div>
                            <span class="font-medium text-gray-700 group-hover:text-primary transition-colors">Hardware</span>
                        </div>
                    </a>
                    <a href="<?php echo NavigationHelper::url('software_projects'); ?>" 
                       class="nav-link-enhanced group" 
                       role="menuitem">
                        <div class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-primary/5 transition-all duration-200">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-code text-white text-sm" aria-hidden="true"></i>
                            </div>
                            <span class="font-medium text-gray-700 group-hover:text-primary transition-colors">Software</span>
                        </div>
                    </a>
                    <a href="<?php echo NavigationHelper::url('projects'); ?>" 
                       class="nav-link-enhanced group" 
                       role="menuitem">
                        <div class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-primary/5 transition-all duration-200">
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-list text-white text-sm" aria-hidden="true"></i>
                            </div>
                            <span class="font-medium text-gray-700 group-hover:text-primary transition-colors">Tutti i Progetti</span>
                        </div>
                    </a>
                    <a href="<?php echo NavigationHelper::url('about'); ?>" 
                       class="nav-link-enhanced group" 
                       role="menuitem">
                        <div class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-primary/5 transition-all duration-200">
                            <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-info-circle text-white text-sm" aria-hidden="true"></i>
                            </div>
                            <span class="font-medium text-gray-700 group-hover:text-primary transition-colors">Chi Siamo</span>
                        </div>
                    </a>
                </div>

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
                        <!-- Enhanced Auth Buttons -->
                        <div class="hidden md:flex items-center space-x-3">
                            <a href="<?php echo NavigationHelper::url('login'); ?>" 
                               class="btn-auth-outline flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:border-primary hover:text-primary transition-all duration-200"
                               aria-label="Accedi al tuo account esistente">
                               <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                               <span>Accedi</span>
                            </a>
                            <a href="<?php echo NavigationHelper::url('register'); ?>" 
                               class="btn-auth-primary flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-primary to-primary-600 text-white rounded-lg hover:shadow-lg transform hover:scale-105 transition-all duration-200"
                               aria-label="Registrati per creare un nuovo account">
                               <i class="fas fa-user-plus" aria-hidden="true"></i>
                               <span>Registrati</span>
                            </a>
                        </div>
                    <?php endif; ?>

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
                        </div>
                        <span class="sr-only">Menu</span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Enhanced Mobile Menu -->
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
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Not logged in mobile menu -->
                        <div class="space-y-3">
                            <a href="<?php echo NavigationHelper::url('login'); ?>"
                               class="mobile-auth-button-enhanced block w-full text-center py-3 border border-gray-300 text-gray-700 rounded-lg hover:border-primary hover:text-primary transition-colors">
                               <i class="fas fa-sign-in-alt mr-2" aria-hidden="true"></i>Accedi
                            </a>
                            <a href="<?php echo NavigationHelper::url('register'); ?>"
                               class="mobile-auth-button-enhanced block w-full text-center py-3 bg-gradient-to-r from-primary to-primary-600 text-white rounded-lg hover:shadow-lg transition-all">
                               <i class="fas fa-user-plus mr-2" aria-hidden="true"></i>Registrati
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile Theme Toggle -->
                <div class="pt-4 border-t border-gray-200">
                    <button id="mobile-theme-toggle" 
                            class="w-full flex items-center justify-center space-x-2 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors" 
                            aria-label="Cambia tema">
                        <i class="ri-sun-line dark:hidden text-gray-600" aria-hidden="true"></i>
                        <i class="ri-moon-line hidden dark:block text-gray-300" aria-hidden="true"></i>
                        <span class="text-gray-700 dark:text-gray-300">Cambia Tema</span>
                    </button>
                </div>
            </div>
        </div>
    </header><!-- Hero Section -->
    <main id="main-content">
        <section class="hero-enhanced" role="banner">
            <!-- Animated Background Elements -->
            <div class="hero-content-enhanced container mx-auto px-4">
                <h1 class="hero-title-enhanced">
                    Trasforma le tue <span class="hero-title-gradient">idee creative</span><br>
                    in <span class="text-green-300">realtà</span>
                </h1>
                <p class="hero-subtitle-enhanced">
                    La piattaforma italiana di crowdfunding per progetti creativi, artistici e tecnologici.<br>
                    <span class="text-yellow-200 font-medium">Oltre 15 categorie</span> per dare vita alle tue passioni.
                </p>
                <div class="hero-cta-enhanced" role="group" aria-label="Azioni principali">
                    <a href="/BOSTARTER/frontend/create-project.html" 
                       class="btn-hero-primary group"
                       aria-label="Inizia a creare il tuo progetto">
                        <i class="fas fa-rocket group-hover:animate-bounce" aria-hidden="true"></i>Lancia il tuo progetto
                    </a>
                    <a href="/BOSTARTER/projects/view_projects.php" 
                       class="btn-hero-secondary group"
                       aria-label="Scopri tutti i progetti disponibili">
                        <i class="fas fa-search group-hover:scale-110 transition-transform duration-300" aria-hidden="true"></i>Esplora progetti
                    </a>
                </div>
                
                <!-- Quick Stats in Hero -->
                <div class="hero-stats-enhanced">
                    <div class="hero-stat-enhanced">
                        <div class="hero-stat-number-enhanced"><?php echo $formatted_stats['projects']; ?></div>
                        <div class="hero-stat-label-enhanced">Progetti</div>
                    </div>
                    <div class="hero-stat-enhanced">
                        <div class="hero-stat-number-enhanced"><?php echo $formatted_stats['funded']; ?></div>
                        <div class="hero-stat-label-enhanced">Finanziati</div>
                    </div>
                    <div class="hero-stat-enhanced">
                        <div class="hero-stat-number-enhanced"><?php echo $formatted_stats['backers']; ?></div>
                        <div class="hero-stat-label-enhanced">Sostenitori</div>
                    </div>
                    <div class="hero-stat-enhanced">
                        <div class="hero-stat-number-enhanced"><?php echo $formatted_stats['success_rate']; ?></div>
                        <div class="hero-stat-label-enhanced">Successo</div>
                    </div>
                </div>
            </div>
        </section>        <!-- Advanced Search Section -->
        <section class="search-section-enhanced py-16" role="region" aria-labelledby="search-heading">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="text-center mb-8">
                        <h2 id="search-heading" class="text-3xl font-bold text-primary mb-4">
                            Trova il Progetto Perfetto
                        </h2>
                        <p class="text-gray-600 max-w-2xl mx-auto">
                            Esplora migliaia di progetti innovativi con la nostra ricerca intelligente
                        </p>
                    </div>

                    <div class="search-container-enhanced p-8">
                        <form class="space-y-6" action="/BOSTARTER/backend/api/search.php" method="GET">
                            <!-- Barra di ricerca principale -->
                            <div class="relative">
                                <input type="text" 
                                       name="q" 
                                       placeholder="Cerca progetti, creator, tecnologie..." 
                                       class="search-input-enhanced w-full px-6 py-4 pl-12">
                                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <button type="submit" 
                                        class="search-btn-enhanced absolute right-2 top-1/2 transform -translate-y-1/2 px-6 py-2">
                                    Cerca
                                </button>
                            </div>

                            <!-- Filtri avanzati -->
                            <div class="grid md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                                    <select name="category" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand focus:border-transparent">
                                        <option value="">Tutte le categorie</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Budget</label>
                                    <select name="budget" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand focus:border-transparent">
                                        <option value="">Qualsiasi budget</option>
                                        <option value="0-5000">€0 - €5.000</option>
                                        <option value="5000-15000">€5.000 - €15.000</option>
                                        <option value="15000-50000">€15.000 - €50.000</option>
                                        <option value="50000+">€50.000+</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Stato</label>
                                    <select name="status" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand focus:border-transparent">
                                        <option value="">Tutti gli stati</option>
                                        <option value="aperto">In corso</option>
                                        <option value="quasi_finito">In scadenza</option>
                                        <option value="chiuso">Completati</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tags popolari -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Tag Popolari</label>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="tag-filter px-4 py-2 bg-white border border-gray-300 rounded-full text-sm hover:bg-brand hover:text-white hover:border-brand transition-all" data-tag="AI">
                                        🤖 Intelligenza Artificiale
                                    </button>
                                    <button type="button" class="tag-filter px-4 py-2 bg-white border border-gray-300 rounded-full text-sm hover:bg-brand hover:text-white hover:border-brand transition-all" data-tag="IoT">
                                        🌐 IoT
                                    </button>
                                    <button type="button" class="tag-filter px-4 py-2 bg-white border border-gray-300 rounded-full text-sm hover:bg-brand hover:text-white hover:border-brand transition-all" data-tag="sostenibile">
                                        🌱 Sostenibile
                                    </button>
                                    <button type="button" class="tag-filter px-4 py-2 bg-white border border-gray-300 rounded-full text-sm hover:bg-brand hover:text-white hover:border-brand transition-all" data-tag="startup">
                                        🚀 Startup
                                    </button>
                                    <button type="button" class="tag-filter px-4 py-2 bg-white border border-gray-300 rounded-full text-sm hover:bg-brand hover:text-white hover:border-brand transition-all" data-tag="mobile">
                                        📱 Mobile
                                    </button>
                                    <button type="button" class="tag-filter px-4 py-2 bg-white border border-gray-300 rounded-full text-sm hover:bg-brand hover:text-white hover:border-brand transition-all" data-tag="arte">
                                        🎨 Arte Digitale
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Quick stats di ricerca -->
                        <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4 pt-6 border-t border-gray-200">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-brand"><?php echo $stats['total_projects']; ?></div>
                                <div class="text-sm text-gray-600">Progetti Totali</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">85%</div>
                                <div class="text-sm text-gray-600">Tasso Successo</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">15</div>
                                <div class="text-sm text-gray-600">Categorie</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600">24h</div>
                                <div class="text-sm text-gray-600">Tempo Approvazione</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Categories Section - MIGLIORATA -->
        <section class="py-20 bg-gradient-to-b from-tertiary to-white" role="region" aria-labelledby="categories-heading">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 id="categories-heading" class="text-4xl md:text-5xl font-bold text-primary mb-6">
                        Categorie di Progetti
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                        Esplora progetti innovativi in <span class="font-semibold text-brand">15 categorie diverse</span>: 
                        dall'arte alla tecnologia, dalla musica al design. 
                        <span class="text-gray-800">Trova la tua passione e sostieni i creativi italiani.</span>
                    </p>
                </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 max-w-7xl mx-auto">
                    <?php foreach ($categories as $category): ?>
                    <article class="group bg-secondary rounded-xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border-2 border-transparent hover:border-brand transform hover:scale-105">                        <div class="bg-gradient-to-br <?php echo $category['color']; ?> w-14 h-14 mx-auto rounded-lg flex items-center justify-center mb-4 text-white text-xl group-hover:scale-110 transition-transform duration-300">
                            <i class="<?php echo $category['icon']; ?>" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-lg font-bold text-primary mb-2 group-hover:text-brand transition-colors duration-300"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars($category['description']); ?></p>
                        <a href="<?php echo NavigationHelper::url($category['id'].'_projects'); ?>" 
                           class="inline-flex items-center text-brand hover:text-brand-dark font-medium text-sm group-hover:translate-x-1 transition-transform duration-300"
                           aria-label="Esplora progetti nella categoria <?php echo htmlspecialchars($category['name']); ?>">
                            Esplora <i class="fas fa-arrow-right ml-1 text-xs" aria-hidden="true"></i>
                        </a>
                    </article>
                    <?php endforeach; ?>
                </div>
                  <!-- Vantaggi Piattaforma -->
                <div class="mt-16 bg-white rounded-2xl shadow-lg p-8 max-w-4xl mx-auto">
                    <h3 class="text-2xl font-bold text-center text-primary mb-8">Perché Scegliere BOSTARTER?</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="bg-gradient-to-br from-blue-500 to-cyan-500 w-12 h-12 mx-auto rounded-lg flex items-center justify-center mb-4 text-white">
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Sicurezza Garantita</h4>
                            <p class="text-gray-600 text-sm">Pagamenti sicuri e protezione per tutti i progetti finanziati</p>
                        </div>
                        <div class="text-center">
                            <div class="bg-gradient-to-br from-green-500 to-emerald-500 w-12 h-12 mx-auto rounded-lg flex items-center justify-center mb-4 text-white">
                                <i class="fas fa-users" aria-hidden="true"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Community Attiva</h4>
                            <p class="text-gray-600 text-sm">Migliaia di creativi e sostenitori pronti a supportare le tue idee</p>
                        </div>
                        <div class="text-center">
                            <div class="bg-gradient-to-br from-purple-500 to-pink-500 w-12 h-12 mx-auto rounded-lg flex items-center justify-center mb-4 text-white">
                                <i class="fas fa-chart-line" aria-hidden="true"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Analytics Avanzate</h4>
                            <p class="text-gray-600 text-sm">Strumenti professionali per monitorare e ottimizzare i tuoi progetti</p>
                        </div>
                    </div>
                </div>
                
                <!-- Compliance Notice Migliorata -->
                <div class="mt-12 text-center">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-8 max-w-3xl mx-auto shadow-sm" role="note" aria-labelledby="compliance-title">
                        <h4 id="compliance-title" class="text-blue-800 font-bold text-lg mb-3">
                            <i class="fas fa-info-circle mr-2" aria-hidden="true"></i>Piattaforma Completa e Versatile
                        </h4>
                        <p class="text-blue-700 leading-relaxed">
                            BOSTARTER supporta progetti creativi e innovativi in <strong>tutte le categorie artistiche, tecnologiche e culturali</strong>. 
                            Dalla tecnologia all'arte, dalla musica al design: ogni idea merita di essere realizzata.
                        </p>
                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">🎨 Arte & Design</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">💻 Tecnologia</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">🎵 Musica & Teatro</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">📚 Editoria</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">🎮 Gaming</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>        <!-- Featured Projects Section -->
        <?php if (!empty($featured_projects)): ?>
        <section class="py-20 bg-gradient-to-b from-white to-gray-50" role="region" aria-labelledby="featured-heading">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 id="featured-heading" class="text-4xl md:text-5xl font-bold text-primary mb-6">
                        Progetti in Evidenza
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                        Scopri le <span class="font-semibold text-brand">storie di successo</span> della nostra community. 
                        Progetti che hanno superato i loro obiettivi e stanno cambiando il panorama creativo italiano.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" role="list" aria-label="Lista progetti in evidenza">
                    <?php foreach ($featured_projects as $project): ?>
                    <article class="bg-white rounded-2xl shadow-sm hover:shadow-2xl transition-all duration-500 overflow-hidden group transform hover:-translate-y-2" 
                             role="listitem" aria-labelledby="project-title-<?php echo $project['id']; ?>">
                        
                        <!-- Project Image -->
                        <div class="relative overflow-hidden">
                            <?php if (!empty($project['image'])): ?>
                            <img src="<?php echo htmlspecialchars($project['image']); ?>" 
                                 alt="Immagine del progetto <?php echo htmlspecialchars($project['title']); ?>"
                                 class="w-full h-48 object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php else: ?>
                            <div class="w-full h-48 bg-gradient-to-br from-blue-500 via-purple-500 to-indigo-600 flex items-center justify-center relative">
                                <i class="fas <?php echo $project['category'] === 'hardware' ? 'fa-microchip' : 'fa-code'; ?> text-4xl text-white/50"></i>
                                <div class="absolute inset-0 bg-black/10 group-hover:bg-black/20 transition-colors duration-300"></div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Category Badge -->
                            <div class="absolute top-4 left-4">
                                <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium backdrop-blur-sm 
                                    <?php echo $project['category'] === 'hardware' ? 'bg-blue-500/90 text-white' : 'bg-green-500/90 text-white'; ?>">
                                    <i class="fas <?php echo $project['category'] === 'hardware' ? 'fa-microchip' : 'fa-code'; ?> mr-1"></i>
                                    <?php echo ucfirst($project['category']); ?>
                                </span>
                            </div>
                        </div>                        
                        <!-- Project Content -->
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 id="project-title-<?php echo $project['id']; ?>" class="text-xl font-bold text-primary group-hover:text-brand transition-colors line-clamp-2">
                                        <?php echo htmlspecialchars($project['title']); ?>
                                    </h3>
                                    <p class="text-gray-600 text-sm mt-1">
                                        di <a href="<?php echo NavigationHelper::url('user_profile', ['id' => $project['creator_id']]); ?>" 
                                           class="hover:text-brand font-medium transition-colors"><?php echo htmlspecialchars($project['creator_name']); ?></a>
                                    </p>
                                </div>
                            </div>
                            
                            <p class="text-gray-600 mb-4 line-clamp-3 text-sm leading-relaxed">
                                <?php echo htmlspecialchars(truncateText($project['description'], 120)); ?>
                            </p>
                            
                            <!-- Progress Section Enhanced -->
                            <div class="mb-4">
                                <div class="flex justify-between items-center text-sm mb-2">
                                    <span class="font-bold text-lg text-gray-800"><?php echo formatCurrency($project['current_funding']); ?></span>
                                    <span class="bg-brand/10 text-brand px-2 py-1 rounded-full text-xs font-medium">
                                        <?php echo $project['funding_percentage']; ?>% completato
                                    </span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-brand to-brand-dark rounded-full transition-all duration-1000" 
                                         style="width: <?php echo min(100, $project['funding_percentage']); ?>%"
                                         role="progressbar" 
                                         aria-valuenow="<?php echo $project['funding_percentage']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Obiettivo: <?php echo formatCurrency($project['goal'] ?? 0); ?>
                                </div>
                            </div>
                            
                            <!-- Project Stats Enhanced -->
                            <div class="flex justify-between items-center text-sm text-gray-600 mb-4 p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="far fa-clock mr-1 text-brand" aria-hidden="true"></i>
                                    <span class="font-medium"><?php echo getDaysLeftText($project['days_left']); ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="far fa-heart mr-1 text-red-500" aria-hidden="true"></i>
                                    <span class="font-medium"><?php echo $project['backers_count']; ?> sostenitori</span>
                                </div>
                            </div>
                            
                            <!-- Action Button Enhanced -->
                            <a href="<?php echo NavigationHelper::url('project_detail', ['id' => $project['id']]); ?>" 
                               class="block w-full text-center bg-gradient-to-r from-brand to-brand-dark text-white py-3 rounded-lg hover:from-brand-dark hover:to-brand transition-all duration-300 focus-visible font-medium group-hover:shadow-lg transform group-hover:scale-105"
                               aria-labelledby="project-title-<?php echo $project['id']; ?>">
                                <i class="fas fa-arrow-right mr-2" aria-hidden="true"></i>Scopri il progetto
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

    <!-- Recent Projects Section -->
    <?php if (!empty($recent_projects)): ?>
    <section class="py-16 bg-tertiary">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-primary mb-4">Progetti Recenti</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Le ultime idee innovative dalla nostra community
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($recent_projects as $project): ?>
                <div class="bg-secondary rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <?php if (!empty($project['image'])): ?>
                    <img src="<?php echo htmlspecialchars($project['image']); ?>" 
                         alt="Immagine del progetto <?php echo htmlspecialchars($project['title']); ?>"
                         class="w-full h-48 object-cover">
                    <?php else: ?>
                    <div class="w-full h-48 bg-gradient-to-r from-green-400 to-blue-500 flex items-center justify-center">
                        <i class="fas <?php echo $project['category'] === 'hardware' ? 'fa-microchip' : 'fa-code'; ?> text-4xl text-white opacity-50"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold text-gray-900">
                                <?php echo htmlspecialchars($project['title']); ?>
                            </h3>
                            <span class="inline-flex px-3 py-1 rounded-full text-sm <?php echo $project['category'] === 'hardware' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo ucfirst($project['category']); ?>
                            </span>
                        </div>
                        
                        <p class="text-gray-600 mb-4">
                            <?php echo htmlspecialchars(truncateText($project['description'], 120)); ?>
                        </p>
                        
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium"><?php echo formatCurrency($project['current_funding']); ?></span>
                                <span class="text-gray-600"><?php echo $project['funding_percentage']; ?>%</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-brand rounded-full transition-all duration-500" 
                                     style="width: <?php echo min(100, $project['funding_percentage']); ?>%">
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>
                                <i class="far fa-clock mr-1"></i>
                                <?php echo getDaysLeftText($project['days_left']); ?>
                            </span>
                            <span>
                                <i class="far fa-user mr-1"></i>
                                di <?php echo htmlspecialchars($project['creator_name']); ?>
                            </span>
                        </div>
                        
                        <a href="<?php echo NavigationHelper::url('project_detail', ['id' => $project['id']]); ?>" 
                           class="block w-full text-center bg-brand text-white mt-4 py-2 rounded-lg hover:bg-brand-dark transition-colors">
                            Scopri di più
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>            <div class="text-center mt-12">
                <a href="projects/view_projects.php" 
                   class="bg-brand text-white px-8 py-3 rounded-lg font-semibold hover:bg-brand-dark transition-colors inline-block focus-visible"
                   aria-label="Visualizza tutti i progetti disponibili sulla piattaforma">
                    <i class="fas fa-list mr-2" aria-hidden="true"></i>Vedi Tutti i Progetti
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>    <!-- Testimonials Section - NUOVA -->
    <section class="py-20 bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50" role="region" aria-labelledby="testimonials-heading">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 id="testimonials-heading" class="text-4xl md:text-5xl font-bold text-primary mb-6">
                        Storie di Successo
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                        Ascolta le esperienze di <span class="font-semibold text-brand">creator e sostenitori</span> che hanno trasformato 
                        le loro idee in realtà attraverso la nostra piattaforma.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <!-- Testimonial 1 -->
                    <article class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                M
                            </div>
                            <div class="ml-4">
                                <h3 class="font-bold text-gray-800">Marco Rossi</h3>
                                <p class="text-gray-600 text-sm">Founder, TechArt Studio</p>
                                <div class="flex text-yellow-400 mt-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <blockquote class="text-gray-700 italic leading-relaxed">
                            "Grazie a BOSTARTER ho raccolto 45.000€ per il mio progetto di arte digitale interattiva. 
                            La piattaforma è intuitiva e la community incredibilmente supportiva."
                        </blockquote>
                        <div class="mt-4 text-sm text-gray-500">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            Progetto finanziato al 180%
                        </div>
                    </article>

                    <!-- Testimonial 2 -->
                    <article class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                S
                            </div>
                            <div class="ml-4">
                                <h3 class="font-bold text-gray-800">Sofia Bianchi</h3>
                                <p class="text-gray-600 text-sm">Chef & Food Blogger</p>
                                <div class="flex text-yellow-400 mt-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <blockquote class="text-gray-700 italic leading-relaxed">
                            "Ho aperto il mio ristorante vegano grazie ai sostenitori BOSTARTER. 
                            In 30 giorni ho superato l'obiettivo di 25.000€ con una community fantastica!"
                        </blockquote>
                        <div class="mt-4 text-sm text-gray-500">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            142 sostenitori attivi
                        </div>
                    </article>

                    <!-- Testimonial 3 -->
                    <article class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                L
                            </div>
                            <div class="ml-4">
                                <h3 class="font-bold text-gray-800">Luca Ferrari</h3>
                                <p class="text-gray-600 text-sm">Game Developer Indie</p>
                                <div class="flex text-yellow-400 mt-1">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <blockquote class="text-gray-700 italic leading-relaxed">
                            "BOSTARTER mi ha permesso di sviluppare il mio videogioco indie. 
                            Gli strumenti di analytics sono professionali e il supporto eccellente."
                        </blockquote>
                        <div class="mt-4 text-sm text-gray-500">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            Lancio previsto Q2 2025
                        </div>
                    </article>
                </div>

                <!-- Call to Action per Testimonials -->
                <div class="text-center mt-12">
                    <div class="bg-white rounded-2xl p-8 max-w-2xl mx-auto shadow-sm">
                        <h3 class="text-2xl font-bold mb-4">Vuoi essere il prossimo?</h3>
                        <p class="text-gray-600 mb-6">
                            Unisciti a centinaia di creator che hanno realizzato i loro sogni con BOSTARTER
                        </p>
                        <a href="/BOSTARTER/frontend/create-project.html" 
                           class="inline-flex items-center bg-gradient-to-r from-brand to-brand-dark text-white px-8 py-4 rounded-lg font-bold hover:from-brand-dark hover:to-brand transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            <i class="fas fa-rocket mr-2"></i>Inizia ora il tuo progetto
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics & Impact Section - MIGLIORATA -->
        <section class="py-20 bg-gradient-to-r from-brand via-indigo-700 to-purple-800 text-white relative overflow-hidden" role="region" aria-labelledby="stats-heading">
            <!-- Background Decorations -->
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="absolute top-0 left-0 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
            
            <div class="relative container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 id="stats-heading" class="text-4xl md:text-5xl font-bold mb-6">
                        L'Impatto di BOSTARTER
                    </h2>
                    <p class="text-xl opacity-90 max-w-3xl mx-auto leading-relaxed">
                        Numeri che raccontano una storia di <span class="text-yellow-300 font-semibold">innovazione</span>, 
                        <span class="text-green-300 font-semibold">creatività</span> e <span class="text-pink-300 font-semibold">successo condiviso</span>
                    </p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto mb-16">
                    <div class="text-center group">
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 group-hover:bg-white/20 transition-all duration-300 transform group-hover:scale-105">
                            <div class="text-4xl md:text-5xl font-bold text-yellow-300 mb-2" data-counter="<?php echo $stats['total_projects']; ?>">
                                <?php echo number_format($stats['total_projects']); ?>
                            </div>
                            <div class="text-white/80 font-medium">Progetti Lanciati</div>
                            <div class="text-yellow-200 text-sm mt-1">
                                <i class="fas fa-chart-line mr-1"></i>+15% questo mese
                            </div>
                        </div>
                    </div>

                    <div class="text-center group">
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 group-hover:bg-white/20 transition-all duration-300 transform group-hover:scale-105">
                            <div class="text-4xl md:text-5xl font-bold text-green-300 mb-2">
                                €<?php echo number_format($stats['total_funding']); ?>
                            </div>
                            <div class="text-white/80 font-medium">Finanziati</div>
                            <div class="text-green-200 text-sm mt-1">
                                <i class="fas fa-euro-sign mr-1"></i>Media: €8.750/progetto
                            </div>
                        </div>
                    </div>

                    <div class="text-center group">
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 group-hover:bg-white/20 transition-all duration-300 transform group-hover:scale-105">
                            <div class="text-4xl md:text-5xl font-bold text-purple-300 mb-2">
                                <?php echo number_format($stats['total_backers']); ?>
                            </div>
                            <div class="text-white/80 font-medium">Sostenitori</div>
                            <div class="text-purple-200 text-sm mt-1">
                                <i class="fas fa-users mr-1"></i>Community in crescita
                            </div>
                        </div>
                    </div>

                    <div class="text-center group">
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 group-hover:bg-white/20 transition-all duration-300 transform group-hover:scale-105">
                            <div class="text-4xl md:text-5xl font-bold text-blue-300 mb-2">
                                <?php echo $stats['success_rate']; ?>%
                            </div>
                            <div class="text-white/80 font-medium">Tasso di Successo</div>
                            <div class="text-blue-200 text-sm mt-1">
                                <i class="fas fa-trophy mr-1"></i>Sopra la media
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Milestone Achievements -->
                <div class="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-center">
                        <div class="text-6xl mb-4">🎨</div>
                        <h3 class="text-xl font-bold mb-2">350+ Progetti Artistici</h3>
                        <p class="text-white/80 text-sm">Dalla pittura alla scultura digitale</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-center">
                        <div class="text-6xl mb-4">💻</div>
                        <h3 class="text-xl font-bold mb-2">200+ Innovazioni Tech</h3>
                        <p class="text-white/80 text-sm">Hardware, software e IoT</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-center">
                        <div class="text-6xl mb-4">🌍</div>
                        <h3 class="text-xl font-bold mb-2">Impatto Sociale</h3>
                        <p class="text-white/80 text-sm">150 posti di lavoro creati</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter & Community Section - NUOVA -->
        <section class="py-20 bg-white" role="region" aria-labelledby="newsletter-heading">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-3xl p-8 md:p-12 text-center border border-gray-200 shadow-sm">
                        <h2 id="newsletter-heading" class="text-3xl md:text-4xl font-bold text-primary mb-6">
                            Resta Sempre Aggiornato
                        </h2>
                        <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                            Ricevi le ultime novità sui progetti più interessanti, consigli per creator 
                            e opportunità esclusive della community BOSTARTER.
                        </p>

                        <form class="max-w-lg mx-auto mb-8" action="/BOSTARTER/backend/newsletter/subscribe.php" method="POST">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <input type="email" 
                                       name="email" 
                                       placeholder="Inserisci la tua email..."
                                       class="flex-1 px-6 py-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand focus:border-transparent text-lg"
                                       required>
                                <button type="submit" 
                                        class="bg-gradient-to-r from-brand to-brand-dark text-white px-8 py-4 rounded-lg font-bold hover:from-brand-dark hover:to-brand transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                                    <i class="fas fa-paper-plane mr-2"></i>Iscriviti
                                </button>
                            </div>
                            <p class="text-sm text-gray-500 mt-4">
                                <i class="fas fa-shield-alt mr-1"></i>
                                I tuoi dati sono protetti. Niente spam, promesso!
                            </p>
                        </form>

                        <!-- Social Proof -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pt-8 border-t border-gray-200">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-brand mb-1">12.5K+</div>
                                <div class="text-gray-600 text-sm">Newsletter Subscribers</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-brand mb-1">8.2K</div>
                                <div class="text-gray-600 text-sm">Discord Members</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-brand mb-1">15.8K</div>
                                <div class="text-gray-600 text-sm">Instagram Followers</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-brand mb-1">22.1K</div>
                                <div class="text-gray-600 text-sm">LinkedIn Connections</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>        </section>

        <!-- CTA Section Enhanced -->
    <section class="py-20 bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700 text-white relative overflow-hidden" role="region" aria-labelledby="cta-heading">
        <!-- Background Elements -->
        <div class="absolute inset-0 bg-black/10" aria-hidden="true"></div>
        <div class="absolute top-0 left-0 w-96 h-96 bg-white/5 rounded-full blur-3xl animate-pulse" aria-hidden="true"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl animate-pulse" aria-hidden="true" style="animation-delay: 2s;"></div>
        
        <div class="relative container mx-auto px-4 text-center">
            <h2 id="cta-heading" class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
                Pronto a <span class="text-yellow-300">trasformare</span><br>
                la tua <span class="bg-gradient-to-r from-pink-300 to-yellow-300 bg-clip-text text-transparent">passione</span> in successo?
            </h2>
            <p class="text-xl md:text-2xl mb-12 max-w-4xl mx-auto leading-relaxed opacity-90">
                Unisciti a <span class="font-bold text-yellow-300">migliaia di creativi</span> che hanno trasformato le loro idee in realtà con BOSTARTER.<br>
                <span class="text-purple-200">La tua storia di successo inizia oggi.</span>
            </p>
            
            <!-- Statistics Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-yellow-300 mb-2"><?php echo $formatted_stats['projects']; ?></div>
                    <div class="text-purple-200">Progetti Lanciati</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-green-300 mb-2"><?php echo $formatted_stats['funded']; ?></div>
                    <div class="text-purple-200">Fondi Raccolti</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-pink-300 mb-2"><?php echo $formatted_stats['backers']; ?></div>
                    <div class="text-purple-200">Sostenitori Attivi</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-blue-300 mb-2"><?php echo $formatted_stats['success_rate']; ?></div>
                    <div class="text-purple-200">Tasso Successo</div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-6 justify-center" role="group" aria-label="Azioni call-to-action">
                <a href="/frontend/create-project.html" 
                   class="bg-gradient-to-r from-yellow-400 to-orange-400 text-gray-900 px-10 py-4 rounded-xl font-bold text-lg hover:from-yellow-300 hover:to-orange-300 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 focus-visible group"
                   aria-label="Inizia a creare il tuo progetto ora">
                   <i class="fas fa-rocket mr-3 group-hover:animate-bounce" aria-hidden="true"></i>Lancia il Tuo Progetto
                </a>
                <a href="projects/view_projects.php" 
                   class="border-2 border-white/80 text-white px-10 py-4 rounded-xl font-bold text-lg hover:bg-white hover:text-purple-600 transition-all duration-300 backdrop-blur-sm focus-visible group"
                   aria-label="Esplora tutti i progetti disponibili">
                   <i class="fas fa-compass mr-3 group-hover:rotate-45 transition-transform duration-300" aria-hidden="true"></i>Esplora Progetti
                </a>
            </div>
            
            <!-- Trust Indicators -->
            <div class="mt-12 flex flex-wrap justify-center items-center gap-8 opacity-80">
                <div class="flex items-center text-sm">
                    <i class="fas fa-shield-alt mr-2 text-green-300" aria-hidden="true"></i>
                    <span>Pagamenti Sicuri</span>
                </div>
                <div class="flex items-center text-sm">
                    <i class="fas fa-users mr-2 text-blue-300" aria-hidden="true"></i>
                    <span>Community Verificata</span>
                </div>
                <div class="flex items-center text-sm">
                    <i class="fas fa-headset mr-2 text-purple-300" aria-hidden="true"></i>
                    <span>Supporto 24/7</span>
                </div>
                <div class="flex items-center text-sm">
                    <i class="fas fa-chart-line mr-2 text-yellow-300" aria-hidden="true"></i>
                    <span>Analytics Professionali</span>
                </div>
            </div>
        </div>
    </section>
    </main>    
    <!-- Footer -->
    <?php
// Enhanced accessible footer component for BOSTARTER
?>
<footer class="bg-dark text-light py-5 mt-5" role="contentinfo" aria-label="Informazioni del sito e collegamenti">
    <div class="container">
        <div class="row">
            <!-- Brand Section -->
            <div class="col-md-4 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <img src="images/logo1.svg" 
                         alt="" 
                         class="me-2" 
                         style="height: 24px;"
                         role="img"
                         aria-hidden="true">
                    <span class="fs-5 fw-bold">BOSTARTER</span>
                </div>
                <p class="text-muted">
                    La piattaforma italiana che trasforma idee creative in realtà attraverso il crowdfunding.
                    Supporta innovazione, tecnologia e creatività.
                </p>
            </div>
            
            <!-- Quick Links -->
            <div class="col-md-2 mb-4">
                <nav aria-labelledby="explore-heading">
                    <h6 class="fw-bold mb-3" id="explore-heading">Esplora</h6>
                    <ul class="list-unstyled" role="list">
                        <li>
                            <a href="projects/list.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Visualizza tutti i progetti disponibili">
                                Tutti i Progetti
                            </a>
                        </li>
                        <li>
                            <a href="projects/category.php?type=hardware" 
                               class="text-muted text-decoration-none"
                               aria-label="Esplora progetti hardware">
                                Hardware
                            </a>
                        </li>
                        <li>
                            <a href="projects/category.php?type=software" 
                               class="text-muted text-decoration-none"
                               aria-label="Esplora progetti software">
                                Software
                            </a>
                        </li>
                        <li>
                            <a href="stats/index.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Visualizza statistiche della piattaforma">
                                Statistiche
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <!-- For Creators -->
            <div class="col-md-2 mb-4">
                <nav aria-labelledby="creators-heading">
                    <h6 class="fw-bold mb-3" id="creators-heading">Creatori</h6>
                    <ul class="list-unstyled" role="list">
                        <li>
                            <a href="projects/create.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Inizia a creare il tuo progetto">
                                Crea Progetto
                            </a>
                        </li>
                        <li>
                            <a href="help/guidelines.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Leggi le linee guida per i creatori">
                                Linee Guida
                            </a>
                        </li>
                        <li>
                            <a href="help/fees.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Informazioni su commissioni e costi">
                                Commissioni
                            </a>
                        </li>
                        <li>
                            <a href="help/support.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Ottieni supporto e assistenza">
                                Supporto
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <!-- Social & Contact -->
            <div class="col-md-4 mb-4">
                <section aria-labelledby="social-heading">
                    <h6 class="fw-bold mb-3" id="social-heading">Seguici</h6>
                    <div class="d-flex mb-3" role="list" aria-label="Collegamenti social media">
                        <a href="#" 
                           class="text-muted me-3 fs-5" 
                           aria-label="Seguici su Facebook (link esterno)"
                           rel="noopener noreferrer"
                           target="_blank">
                            <i class="fab fa-facebook-f" aria-hidden="true"></i>
                        </a>
                        <a href="#" 
                           class="text-muted me-3 fs-5" 
                           aria-label="Seguici su Twitter (link esterno)"
                           rel="noopener noreferrer"
                           target="_blank">
                            <i class="fab fa-twitter" aria-hidden="true"></i>
                        </a>
                        <a href="#" 
                           class="text-muted me-3 fs-5" 
                           aria-label="Seguici su Instagram (link esterno)"
                           rel="noopener noreferrer"
                           target="_blank">
                            <i class="fab fa-instagram" aria-hidden="true"></i>
                        </a>
                        <a href="#" 
                           class="text-muted me-3 fs-5" 
                           aria-label="Collegati con noi su LinkedIn (link esterno)"
                           rel="noopener noreferrer"
                           target="_blank">
                            <i class="fab fa-linkedin" aria-hidden="true"></i>
                        </a>
                    </div>
                    <address class="text-muted small mb-0">
                        <i class="fas fa-envelope me-2" aria-hidden="true"></i>
                        <a href="mailto:info@bostarter.it" 
                           class="text-muted text-decoration-none"
                           aria-label="Invia email a info@bostarter.it">
                            info@bostarter.it
                        </a>
                    </address>
                </section>
            </div>
        </div>
        
        <hr class="my-4" aria-hidden="true">
        
        <!-- Copyright and Legal -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-muted small mb-0" role="contentinfo">
                    &copy; 2025 BOSTARTER. Tutti i diritti riservati. Made with ❤️ in Italy
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <nav aria-label="Collegamenti legali">
                    <a href="legal/privacy.php" 
                       class="text-muted text-decoration-none small me-3"
                       aria-label="Leggi la nostra privacy policy">
                        Privacy Policy
                    </a>
                    <a href="legal/terms.php" 
                       class="text-muted text-decoration-none small"
                       aria-label="Leggi i termini di servizio">
                        Termini di Servizio
                    </a>
                </nav>
            </div>
        </div>
    </div>
</footer>

    <!-- Scripts -->
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script src="/BOSTARTER/frontend/js/navigation.js"></script>
    
    <!-- Initialize features -->
    <script>
        // Initialize Modern Header
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize navigation
            if (typeof BOStarterNavigation !== 'undefined') {
                window.navigation = new BOStarterNavigation();
            }
            
            // Welcome notification
            if (window.boNotifications && !sessionStorage.getItem('welcomeShown')) {
                setTimeout(() => {
                    window.boNotifications.info('Benvenuto su BOSTARTER! Esplora progetti innovativi e lancia la tua idea.', 6000);
                    sessionStorage.setItem('welcomeShown', 'true');
                }, 1500);
            }
            
            // Add smooth scrolling to anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Add loading states to buttons
            document.querySelectorAll('.btn-auth-primary').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!this.classList.contains('loading')) {
                        const originalText = this.innerHTML;
                        this.classList.add('loading');
                        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Caricamento...';
                        
                        setTimeout(() => {
                            this.classList.remove('loading');
                            this.innerHTML = originalText;
                        }, 2000);
                    }
                });
            });
        });
    </script>
    
</body>
</html>