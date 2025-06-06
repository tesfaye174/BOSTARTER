<?php
session_start();

// Initialize error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files with error handling
try {
    require_once __DIR__ . '/../backend/config/database.php';
    require_once __DIR__ . '/../backend/services/MongoLogger.php';
    require_once __DIR__ . '/../backend/utils/NavigationHelper.php';
} catch (Exception $e) {
    error_log("Failed to include required files: " . $e->getMessage());
    // Continue without fatal error to show basic HTML
    $db = null;
}

// Initialize variables with defaults
$featured_projects = [];
$recent_projects = [];
$stats = [
    'total_projects' => 0,
    'total_funding' => 0,
    'total_backers' => 0,
    'success_rate' => 0
];
$categories = [];
$is_logged_in = isset($_SESSION['user_id']);

// Initialize database and logger with error handling
try {
    if (class_exists('Database')) {
        $database = Database::getInstance();
        $db = $database->getConnection();
        
        if ($db && class_exists('MongoLogger')) {
            $mongoLogger = new MongoLogger();
            
            // Log homepage visit
            if ($is_logged_in) {
                $mongoLogger->logActivity($_SESSION['user_id'], 'homepage_visit', [
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                $mongoLogger->logSystem('anonymous_homepage_visit', [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
        }
    }
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    $db = null;
}

// Get platform statistics
if ($db) {
    try {        // Total projects (conforme al PDF)
        $stmt = $db->query("SELECT COUNT(*) as count FROM progetti WHERE stato IN ('aperto', 'chiuso')");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_projects'] = $result ? $result['count'] : 0;
        
        // Total funding (conforme al PDF)
        $stmt = $db->query("SELECT SUM(importo) as total FROM finanziamenti WHERE stato_pagamento = 'completato'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_funding'] = $result ? $result['total'] : 0;
        
        // Total backers (conforme al PDF)
        $stmt = $db->query("SELECT COUNT(DISTINCT utente_id) as count FROM finanziamenti WHERE stato_pagamento = 'completato'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_backers'] = $result ? $result['count'] : 0;
        
        // Success rate (conforme al PDF)
        $total_projects = $stats['total_projects'];
        if ($total_projects > 0) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM progetti WHERE stato = 'chiuso'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = $result ? $result['count'] : 0;
            $stats['success_rate'] = round(($completed / $total_projects) * 100, 1);
        }
    } catch (Exception $e) {
        error_log("Statistics query error: " . $e->getMessage());
    }
}

// Get top 3 most funded projects
if ($db) {
    try {        // Query conformi al database PDF-compliant
        $top_projects_query = "
            SELECT 
                p.id,
                p.nome as title,
                p.descrizione as description,
                p.budget_richiesto as funding_goal,
                COALESCE(SUM(f.importo), 0) as current_funding,
                p.foto as image,
                p.tipo_progetto as category,
                p.data_limite as deadline,
                u.nickname as creator_name,
                u.id as creator_id,
                ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) as funding_percentage,
                DATEDIFF(p.data_limite, NOW()) as days_left,
                COUNT(DISTINCT f.utente_id) as backers_count
            FROM progetti p
            JOIN utenti u ON p.creatore_id = u.id
            LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato'
            WHERE p.stato = 'aperto' AND p.data_limite > NOW()
            GROUP BY p.id, p.nome, p.descrizione, p.budget_richiesto, p.foto, p.tipo_progetto, p.data_limite, u.nickname, u.id
            ORDER BY current_funding DESC
            LIMIT 3
        ";
        $stmt = $db->prepare($top_projects_query);
        $stmt->execute();
        $featured_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $featured_projects = [];
        error_log("Error fetching featured projects: " . $e->getMessage());
    }
}

// Get recent projects
if ($db) {
    try {        // Query per progetti recenti conformi al PDF
        $recent_projects_query = "
            SELECT 
                p.id,
                p.nome as title,
                p.descrizione as description,
                p.budget_richiesto as funding_goal,
                COALESCE(SUM(f.importo), 0) as current_funding,
                p.foto as image,
                p.tipo_progetto as category,
                u.nickname as creator_name,
                ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) as funding_percentage,
                DATEDIFF(p.data_limite, NOW()) as days_left
            FROM progetti p
            JOIN utenti u ON p.creatore_id = u.id
            LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato'
            WHERE p.stato = 'aperto' AND p.data_limite > NOW()
            GROUP BY p.id, p.nome, p.descrizione, p.budget_richiesto, p.foto, p.tipo_progetto, u.nickname
            ORDER BY p.data_inserimento DESC
            LIMIT 6
        ";
        
        $stmt = $db->prepare($recent_projects_query);
        $stmt->execute();
        $recent_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $recent_projects = [];
        error_log("Error fetching recent projects: " . $e->getMessage());
    }
}

// Get project categories (CONFORME AL PDF - SOLO HARDWARE E SOFTWARE)
$categories = [
    [
        'id' => 'hardware', 
        'name' => 'Hardware', 
        'icon' => 'fas fa-microchip', 
        'color' => 'from-blue-500 to-indigo-500',
        'description' => 'Progetti di elettronica, robotica, IoT e dispositivi fisici'
    ],
    [
        'id' => 'software', 
        'name' => 'Software', 
        'icon' => 'fas fa-code', 
        'color' => 'from-green-500 to-emerald-500',
        'description' => 'Applicazioni, piattaforme e soluzioni digitali'
    ]
];

// Format statistics for display
$formatted_stats = [
    'projects' => number_format($stats['total_projects']) . '+',
    'funded' => '€' . number_format($stats['total_funding'] / 1000, 1) . 'K',
    'backers' => number_format($stats['total_backers']) . '+',
    'success_rate' => $stats['success_rate'] . '%'
];

// Helper functions
function formatCurrency($amount) {
    if ($amount >= 1000000) {
        return '€' . number_format($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        return '€' . number_format($amount / 1000, 1) . 'K';
    } else {
        return '€' . number_format($amount);
    }
}

function getDaysLeftText($days) {
    if ($days <= 0) {
        return 'Scaduto';
    } elseif ($days == 1) {
        return '1 giorno rimasto';
    } else {
        return $days . ' giorni rimasti';
    }
}

function truncateText($text, $length = 100) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSTARTER - Piattaforma Crowdfunding Italiana per Progetti Hardware e Software</title>
    <meta name="description" content="BOSTARTER è la piattaforma italiana di crowdfunding specializzata in progetti Hardware e Software. Scopri, sostieni o lancia il tuo progetto tecnologico innovativo.">
    <meta name="keywords" content="crowdfunding, hardware, software, progetti tecnologici, finanziamento collettivo, startup tech, innovazione, elettronica, applicazioni">
    <meta name="author" content="BOSTARTER">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#3176FF">
    <link rel="canonical" href="https://www.bostarter.it">    <link rel="icon" type="image/svg+xml" href="/BOSTARTER/frontend/images/logo1.svg">
      <!-- Accessibility improvements -->
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    
    <!-- Skip Links for Screen Readers -->
    <style>
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            z-index: 1000;
            text-decoration: none;
            border-radius: 4px;
        }
        .skip-link:focus {
            top: 6px;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .focus-visible:focus {
            outline: 2px solid #3176FF;
            outline-offset: 2px;
        }
    </style>
      <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="BOSTARTER - Crowdfunding per Progetti Hardware e Software">
    <meta property="og:description" content="La piattaforma italiana per finanziare progetti tecnologici innovativi">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.bostarter.it">
    <meta property="og:image" content="/BOSTARTER/frontend/images/logo1.svg">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="BOSTARTER - Crowdfunding Tech">
    <meta name="twitter:description" content="Piattaforma italiana per progetti Hardware e Software">
    <meta name="twitter:image" content="/BOSTARTER/frontend/images/logo1.svg">
    
    <!-- Performance optimizations -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.tailwindcss.com">    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">    <!-- Critical CSS -->
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/critical.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/accessibility.css">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/color-system.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/components.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/main.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/utilities.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/animations.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/sections.css">
    
    <!-- Page Specific CSS -->
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/notifications.css">
    
    <!-- External CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#3176FF',
                        'brand-dark': '#1e4fc4',
                        primary: '#111827',
                        secondary: '#ffffff',
                        tertiary: '#f3f4f6'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        brand: ['Pacifico', 'cursive']
                    }
                }
            }
        }
    </script>
      <!-- Preload Key Resources -->
    <link rel="preload" href="/BOSTARTER/frontend/js/main.js" as="script">
    <link rel="preload" href="/BOSTARTER/frontend/js/utils.js" as="script">
    <link rel="preload" href="/BOSTARTER/frontend/js/theme.js" as="script">
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
    <div id="notifications-container" class="fixed top-4 right-4 z-40 space-y-2 max-w-sm" role="region" aria-live="polite" aria-label="Notifiche"></div>    <!-- Header -->
    <header class="bg-secondary/90 backdrop-blur-md shadow-sm border-b sticky top-0 z-30" role="banner">
        <nav id="navigation" class="container mx-auto px-4 py-3 flex items-center justify-between" role="navigation" aria-label="Navigazione principale">              <!-- Logo and Brand -->            <a href="/BOSTARTER/frontend/index.php" class="flex items-center font-brand text-2xl text-brand hover:text-brand-dark transition-colors focus-visible" aria-label="BOSTARTER - Torna alla homepage">
                <img src="/BOSTARTER/frontend/images/logo1.svg" alt="Logo BOSTARTER" class="h-8 w-auto mr-2">
                BOSTARTER
            </a>

            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-toggle" 
                    class="md:hidden p-2 rounded-lg hover:bg-tertiary transition-colors focus-visible"
                    aria-label="Apri menu di navigazione"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    type="button">
                <i class="fas fa-bars text-xl text-primary" aria-hidden="true"></i>
                <span class="sr-only">Menu</span>
            </button>            <!-- Desktop Navigation - CONFORME AL PDF -->
            <ul class="hidden md:flex gap-6 font-medium text-lg text-primary" role="menubar">
                <li role="none">
                    <a href="<?php echo NavigationHelper::url('hardware_projects'); ?>" 
                       class="hover:text-brand transition-colors focus-visible" 
                       role="menuitem">Hardware</a>
                </li>
                <li role="none">
                    <a href="<?php echo NavigationHelper::url('software_projects'); ?>" 
                       class="hover:text-brand transition-colors focus-visible" 
                       role="menuitem">Software</a>
                </li>
                <li role="none">
                    <a href="<?php echo NavigationHelper::url('projects'); ?>" 
                       class="hover:text-brand transition-colors focus-visible" 
                       role="menuitem">Tutti i Progetti</a>
                </li>
                <li role="none">
                    <a href="<?php echo NavigationHelper::url('about'); ?>" 
                       class="hover:text-brand transition-colors focus-visible" 
                       role="menuitem">Chi Siamo</a>
                </li>
            </ul>            <!-- User Actions -->
            <div class="hidden md:flex gap-3 items-center" role="group" aria-label="Azioni utente">
                <?php if (NavigationHelper::isLoggedIn()): ?>
                    <a href="<?php echo NavigationHelper::url('create_project'); ?>" 
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors focus-visible"
                       aria-label="Crea un nuovo progetto">
                        <i class="fas fa-plus mr-1" aria-hidden="true"></i>
                        Crea Progetto
                    </a>
                    <a href="<?php echo NavigationHelper::url('dashboard'); ?>" 
                       class="px-4 py-2 bg-brand text-white rounded-lg hover:bg-brand-dark transition-colors focus-visible"
                       aria-label="Vai alla tua dashboard personale">
                        <i class="fas fa-tachometer-alt mr-1" aria-hidden="true"></i>
                        Dashboard
                    </a>
                    <a href="<?php echo NavigationHelper::url('logout'); ?>" 
                       class="px-4 py-2 border border-brand text-brand rounded-lg hover:bg-brand hover:text-white transition-colors focus-visible"
                       aria-label="Disconnetti dal tuo account">
                        <i class="fas fa-sign-out-alt mr-1" aria-hidden="true"></i>
                        Esci
                    </a>
                <?php else: ?>
                    <a href="<?php echo NavigationHelper::url('login'); ?>" 
                       class="px-4 py-2 border border-brand text-brand rounded-lg hover:bg-brand hover:text-white transition-colors focus-visible"
                       aria-label="Accedi al tuo account esistente">
                        <i class="fas fa-sign-in-alt mr-1" aria-hidden="true"></i>
                        Accedi
                    </a>
                    <a href="<?php echo NavigationHelper::url('register'); ?>" 
                       class="px-4 py-2 bg-brand text-white rounded-lg hover:bg-brand-dark transition-colors focus-visible"
                       aria-label="Registrati per creare un nuovo account">
                        <i class="fas fa-user-plus mr-1" aria-hidden="true"></i>
                        Registrati
                    </a>
                <?php endif; ?>
            </div>
        </nav>        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-secondary border-t" role="menu" aria-label="Menu di navigazione mobile">
            <div class="px-4 py-3 space-y-3">
                <a href="<?php echo NavigationHelper::url('hardware_projects'); ?>" 
                   class="block py-2 text-primary hover:text-brand focus-visible" 
                   role="menuitem">
                   <i class="fas fa-microchip mr-2" aria-hidden="true"></i>Hardware
                </a>
                <a href="<?php echo NavigationHelper::url('software_projects'); ?>" 
                   class="block py-2 text-primary hover:text-brand focus-visible" 
                   role="menuitem">
                   <i class="fas fa-code mr-2" aria-hidden="true"></i>Software
                </a>
                <a href="<?php echo NavigationHelper::url('projects'); ?>" 
                   class="block py-2 text-primary hover:text-brand focus-visible" 
                   role="menuitem">
                   <i class="fas fa-list mr-2" aria-hidden="true"></i>Tutti i Progetti
                </a>
                <a href="<?php echo NavigationHelper::url('about'); ?>" 
                   class="block py-2 text-primary hover:text-brand focus-visible" 
                   role="menuitem">
                   <i class="fas fa-info-circle mr-2" aria-hidden="true"></i>Chi Siamo
                </a>
                <div class="pt-3 border-t space-y-2" role="group" aria-label="Azioni utente mobile">
                    <?php if (NavigationHelper::isLoggedIn()): ?>
                        <a href="<?php echo NavigationHelper::url('create_project'); ?>" 
                           class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg focus-visible"
                           aria-label="Crea un nuovo progetto">
                           <i class="fas fa-plus mr-1" aria-hidden="true"></i>Crea Progetto
                        </a>
                        <a href="<?php echo NavigationHelper::url('dashboard'); ?>" 
                           class="block w-full text-center px-4 py-2 bg-brand text-white rounded-lg focus-visible"
                           aria-label="Vai alla tua dashboard personale">
                           <i class="fas fa-tachometer-alt mr-1" aria-hidden="true"></i>Dashboard
                        </a>
                        <a href="<?php echo NavigationHelper::url('logout'); ?>" 
                           class="block w-full text-center px-4 py-2 border border-brand text-brand rounded-lg focus-visible"
                           aria-label="Disconnetti dal tuo account">
                           <i class="fas fa-sign-out-alt mr-1" aria-hidden="true"></i>Esci
                        </a>
                    <?php else: ?>
                        <a href="<?php echo NavigationHelper::url('login'); ?>" 
                           class="block w-full text-center px-4 py-2 border border-brand text-brand rounded-lg focus-visible"
                           aria-label="Accedi al tuo account esistente">
                           <i class="fas fa-sign-in-alt mr-1" aria-hidden="true"></i>Accedi
                        </a>
                        <a href="<?php echo NavigationHelper::url('register'); ?>" 
                           class="block w-full text-center px-4 py-2 bg-brand text-white rounded-lg focus-visible"
                           aria-label="Registrati per creare un nuovo account">
                           <i class="fas fa-user-plus mr-1" aria-hidden="true"></i>Registrati
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>    <!-- Hero Section -->
    <main id="main-content">
        <section class="relative bg-gradient-to-br from-brand to-indigo-700 text-white py-20 overflow-hidden" role="banner">
            <div class="absolute inset-0 bg-black/20" aria-hidden="true"></div>
            <div class="relative container mx-auto px-4 text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6 animate-fade-in">
                    Trasforma le tue <span class="text-yellow-300">idee tech</span><br>
                    in <span class="text-green-300">realtà</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90">
                    La piattaforma italiana di crowdfunding specializzata in progetti Hardware e Software
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center" role="group" aria-label="Azioni principali">
                <a href="/BOSTARTER/frontend/create-project.html" 
                       class="bg-yellow-400 text-gray-900 px-8 py-4 rounded-lg font-bold text-lg hover:bg-yellow-300 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 focus-visible"
                       aria-label="Inizia a creare il tuo progetto tecnologico">
                        <i class="fas fa-rocket mr-2" aria-hidden="true"></i>Lancia il tuo progetto
                    </a>
                    <a href="/BOSTARTER/projects/view_projects.php" 
                       class="border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-brand transition-all duration-300 focus-visible"
                       aria-label="Scopri tutti i progetti tecnologici disponibili">
                        <i class="fas fa-search mr-2" aria-hidden="true"></i>Esplora progetti
                    </a>
                </div>
            </div>
        </section>        <!-- Stats Section -->
        <section class="py-16 bg-secondary" role="region" aria-labelledby="stats-heading">
            <div class="container mx-auto px-4">
                <h2 id="stats-heading" class="text-2xl font-bold text-center mb-8 sr-only">Statistiche della piattaforma BOSTARTER</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    <div class="space-y-2">
                        <div class="text-3xl md:text-4xl font-bold text-brand" 
                             aria-label="<?php echo number_format($stats['total_projects']); ?> progetti finanziati totali">
                            <?php echo $formatted_stats['projects']; ?>
                        </div>
                        <div class="text-gray-600 font-medium">Progetti Finanziati</div>
                        <div class="sr-only">
                            Numero totale di progetti che hanno ricevuto finanziamenti sulla piattaforma
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-3xl md:text-4xl font-bold text-green-600" 
                             aria-label="<?php echo formatCurrency($stats['total_funding']); ?> euro raccolti totali">
                            <?php echo $formatted_stats['funded']; ?>
                        </div>
                        <div class="text-gray-600 font-medium">Fondi Raccolti</div>
                        <div class="sr-only">
                            Importo totale in euro raccolto da tutti i progetti sulla piattaforma
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-3xl md:text-4xl font-bold text-purple-600" 
                             aria-label="<?php echo number_format($stats['total_backers']); ?> sostenitori attivi totali">
                            <?php echo $formatted_stats['backers']; ?>
                        </div>
                        <div class="text-gray-600 font-medium">Sostenitori Attivi</div>
                        <div class="sr-only">
                            Numero di persone che hanno sostenuto almeno un progetto sulla piattaforma
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-3xl md:text-4xl font-bold text-orange-600" 
                             aria-label="<?php echo $stats['success_rate']; ?> percento di tasso di successo">
                            <?php echo $formatted_stats['success_rate']; ?>
                        </div>
                        <div class="text-gray-600 font-medium">Tasso di Successo</div>
                        <div class="sr-only">
                            Percentuale di progetti che hanno raggiunto con successo il loro obiettivo di finanziamento
                        </div>
                    </div>
                </div>
            </div>
        </section>        <!-- Categories Section - CONFORME AL PDF -->
        <section class="py-16 bg-tertiary" role="region" aria-labelledby="categories-heading">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 id="categories-heading" class="text-3xl md:text-4xl font-bold text-primary mb-4">
                        Categorie Progetti
                    </h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        BOSTARTER supporta esclusivamente progetti Hardware e Software in conformità alle specifiche del sistema
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                    <?php foreach ($categories as $category): ?>
                    <article class="group bg-secondary rounded-xl p-8 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border-2 border-transparent hover:border-brand">
                        <a href="projects/view_projects.php?category=<?php echo $category['id']; ?>" 
                           class="block focus-visible rounded-xl"
                           aria-label="Esplora progetti <?php echo htmlspecialchars($category['name']); ?>: <?php echo htmlspecialchars($category['description']); ?>">
                            <div class="bg-gradient-to-r <?php echo $category['color']; ?> w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg"
                                 role="img" 
                                 aria-label="Icona categoria <?php echo htmlspecialchars($category['name']); ?>">
                                <i class="<?php echo $category['icon']; ?> text-3xl text-white" aria-hidden="true"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-primary group-hover:text-brand transition-colors mb-3">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </h3>
                            <p class="text-gray-600 leading-relaxed mb-4">
                                <?php echo htmlspecialchars($category['description']); ?>
                            </p>
                            <div class="inline-flex items-center text-brand font-semibold group-hover:text-brand-dark">
                                Esplora progetti <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform" aria-hidden="true"></i>
                            </div>
                        </a>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Compliance Notice -->
                <div class="mt-12 text-center">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 max-w-2xl mx-auto" role="note" aria-labelledby="compliance-title">
                        <i class="fas fa-info-circle text-blue-600 text-xl mb-2" aria-hidden="true"></i>
                        <h3 id="compliance-title" class="text-blue-800 font-medium mb-1">
                            BOSTARTER è conforme alle specifiche del progetto di Basi di Dati A.A. 2024/2025
                        </h3>
                        <p class="text-blue-600 text-sm">
                            Supportiamo esclusivamente progetti nelle categorie Hardware e Software come specificato nel PDF di riferimento
                        </p>
                    </div>
                </div>
            </div>
        </section>        <!-- Featured Projects Section -->
        <?php if (!empty($featured_projects)): ?>
        <section class="py-16 bg-secondary" role="region" aria-labelledby="featured-heading">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 id="featured-heading" class="text-3xl md:text-4xl font-bold text-primary mb-4">
                        Progetti in Evidenza
                    </h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        I progetti più sostenuti della nostra community
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" role="list" aria-label="Lista progetti in evidenza">
                    <?php foreach ($featured_projects as $index => $project): ?>
                    <article class="bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group" 
                             role="listitem"
                             aria-labelledby="featured-project-<?php echo $index; ?>">
                        <?php if (!empty($project['image'])): ?>
                        <div class="relative overflow-hidden">
                            <img src="<?php echo htmlspecialchars($project['image']); ?>" 
                                 alt="Immagine del progetto <?php echo htmlspecialchars($project['title']); ?>"
                                 class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute top-4 left-4">
                                <span class="bg-brand text-white text-xs font-semibold px-3 py-1 rounded-full" role="status">
                                    IN EVIDENZA
                                </span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="w-full h-48 bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center relative" 
                             role="img" 
                             aria-label="Immagine placeholder per il progetto <?php echo htmlspecialchars($project['title']); ?>">
                            <i class="fas fa-image text-4xl text-white/50" aria-hidden="true"></i>
                            <div class="absolute top-4 left-4">
                                <span class="bg-brand text-white text-xs font-semibold px-3 py-1 rounded-full" role="status">
                                    IN EVIDENZA
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full uppercase"
                                      role="category">
                                    <?php echo htmlspecialchars($project['category']); ?>
                                </span>
                                <span class="text-sm text-gray-500 font-medium" aria-label="Tempo rimanente">
                                    <i class="fas fa-clock mr-1" aria-hidden="true"></i>
                                    <?php echo getDaysLeftText($project['days_left']); ?>
                                </span>
                            </div>
                            
                            <h3 id="featured-project-<?php echo $index; ?>" class="text-xl font-bold text-primary mb-2 group-hover:text-brand transition-colors">
                                <?php echo htmlspecialchars($project['title']); ?>
                            </h3>
                            
                            <p class="text-gray-600 mb-4 line-clamp-2">
                                <?php echo truncateText(htmlspecialchars($project['description']), 80); ?>
                            </p>
                            
                            <div class="mb-4" role="group" aria-label="Progressi finanziamento">
                                <div class="flex justify-between items-center text-sm text-gray-600 mb-2">
                                    <span class="font-semibold" aria-label="Importo raccolto">
                                        <?php echo formatCurrency($project['current_funding']); ?> raccolti
                                    </span>
                                    <span class="font-semibold" aria-label="Percentuale completamento">
                                        <?php echo $project['funding_percentage']; ?>%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2" role="progressbar" 
                                     aria-valuenow="<?php echo $project['funding_percentage']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"
                                     aria-label="Progresso finanziamento: <?php echo $project['funding_percentage']; ?>%">
                                    <div class="bg-gradient-to-r from-green-500 to-blue-500 h-2 rounded-full transition-all duration-500" 
                                         style="width: <?php echo min($project['funding_percentage'], 100); ?>%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span aria-label="Obiettivo finanziamento">
                                        Obiettivo: <?php echo formatCurrency($project['funding_goal']); ?>
                                    </span>
                                    <span aria-label="Numero sostenitori">
                                        <?php echo $project['backers_count']; ?> sostenitori
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center" aria-label="Creatore progetto">
                                    <?php if (!empty($project['creator_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($project['creator_avatar']); ?>" 
                                         alt="Avatar di <?php echo htmlspecialchars($project['creator_name']); ?>"
                                         class="w-8 h-8 rounded-full mr-2">
                                    <?php else: ?>
                                    <div class="w-8 h-8 bg-gray-300 rounded-full mr-2 flex items-center justify-center" 
                                         role="img" 
                                         aria-label="Avatar placeholder di <?php echo htmlspecialchars($project['creator_name']); ?>">
                                        <i class="fas fa-user text-xs text-gray-600" aria-hidden="true"></i>
                                    </div>
                                    <?php endif; ?>
                                    <span class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($project['creator_name']); ?>
                                    </span>
                                </div>
                                <a href="/frontend/project.php?id=<?php echo $project['id']; ?>" 
                                   class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-dark transition-colors focus-visible"
                                   aria-label="Sostieni il progetto <?php echo htmlspecialchars($project['title']); ?>">
                                    Sostieni
                                </a>
                            </div>
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
                         alt="<?php echo htmlspecialchars($project['title']); ?>"
                         class="w-full h-48 object-cover">
                    <?php else: ?>
                    <div class="w-full h-48 bg-gradient-to-r from-green-400 to-blue-500 flex items-center justify-center">
                        <i class="fas fa-image text-4xl text-white/50"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full uppercase">
                                <?php echo htmlspecialchars($project['category']); ?>
                            </span>
                            <span class="text-sm text-gray-500">
                                <?php echo getDaysLeftText($project['days_left']); ?>
                            </span>
                        </div>
                        
                        <h3 class="text-xl font-bold text-primary mb-2">
                            <?php echo htmlspecialchars($project['title']); ?>
                        </h3>
                        
                        <p class="text-gray-600 mb-4">
                            <?php echo truncateText(htmlspecialchars($project['description']), 80); ?>
                        </p>
                        
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span><?php echo formatCurrency($project['current_funding']); ?> raccolti</span>
                                <span><?php echo $project['funding_percentage']; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" 
                                     style="width: <?php echo min($project['funding_percentage'], 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">
                                by <?php echo htmlspecialchars($project['creator_name']); ?>
                            </span>
                            <a href="/frontend/project.php?id=<?php echo $project['id']; ?>" 
                               class="text-brand hover:text-brand-dark font-semibold text-sm">
                                Vedi Dettagli →
                            </a>
                        </div>
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
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-purple-600 to-blue-600 text-white" role="region" aria-labelledby="cta-heading">
        <div class="container mx-auto px-4 text-center">
            <h2 id="cta-heading" class="text-3xl md:text-4xl font-bold mb-4">
                Pronto a lanciare il tuo progetto?
            </h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto">
                Unisciti a migliaia di creatori che hanno trasformato le loro idee in successo con BOSTARTER
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center" role="group" aria-label="Azioni call-to-action">
                <a href="/frontend/create-project.html" 
                   class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors focus-visible"
                   aria-label="Inizia a creare il tuo progetto ora">
                    <i class="fas fa-plus-circle mr-2" aria-hidden="true"></i>Crea il tuo progetto
                </a>
                <a href="projects/view_projects.php" 
                   class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition-colors focus-visible"
                   aria-label="Esplora tutti i progetti disponibili">
                    <i class="fas fa-search mr-2" aria-hidden="true"></i>Esplora progetti
                </a>
            </div>
        </div>
    </section>
    </main>    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12" role="contentinfo">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <img src="/frontend/images/logo1.svg" alt="Logo BOSTARTER" class="h-8 w-auto mr-2">
                        <span class="text-xl font-bold">BOSTARTER</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        La piattaforma italiana che trasforma idee creative in realtà attraverso il crowdfunding.
                    </p>
                    <div class="flex space-x-4" role="list" aria-label="Link ai social media">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors focus-visible" 
                           aria-label="Seguici su Facebook" role="listitem">
                            <i class="fab fa-facebook-f" aria-hidden="true"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors focus-visible" 
                           aria-label="Seguici su Twitter" role="listitem">
                            <i class="fab fa-twitter" aria-hidden="true"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors focus-visible" 
                           aria-label="Seguici su Instagram" role="listitem">
                            <i class="fab fa-instagram" aria-hidden="true"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors focus-visible" 
                           aria-label="Seguici su LinkedIn" role="listitem">
                            <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
                
                <nav aria-labelledby="footer-projects-heading">
                    <h4 id="footer-projects-heading" class="font-semibold mb-4">Progetti</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="projects/view_projects.php" class="hover:text-white transition-colors focus-visible">Tutti i Progetti</a></li>
                        <li><a href="projects/view_projects.php?category=hardware" class="hover:text-white transition-colors focus-visible">Hardware</a></li>
                        <li><a href="projects/view_projects.php?category=software" class="hover:text-white transition-colors focus-visible">Software</a></li>
                        <li><a href="/frontend/create-project.html" class="hover:text-white transition-colors focus-visible">Crea Progetto</a></li>
                    </ul>
                </nav>
                
                <nav aria-labelledby="footer-support-heading">
                    <h4 id="footer-support-heading" class="font-semibold mb-4">Supporto</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors focus-visible">Come Funziona</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus-visible">FAQ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus-visible">Contatti</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus-visible">Centro Assistenza</a></li>
                    </ul>
                </nav>
                
                <nav aria-labelledby="footer-legal-heading">
                    <h4 id="footer-legal-heading" class="font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors focus-visible">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus-visible">Termini di Servizio</a></li>
                        <li><a href="#" class="hover:text-white transition-colors focus-visible">Cookie Policy</a></li>
                    </ul>
                </nav>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 BOSTARTER. Tutti i diritti riservati. Made with <span aria-label="amore">❤️</span> in Italy</p>
            </div>
        </div>
    </footer><!-- JavaScript Optimized -->
    <script>        // Critical JavaScript for immediate functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle with enhanced accessibility
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuToggle && mobileMenu) {
                mobileMenuToggle.addEventListener('click', function() {
                    const isExpanded = !mobileMenu.classList.contains('hidden');
                    
                    // Toggle menu visibility
                    mobileMenu.classList.toggle('hidden');
                    
                    // Update ARIA attributes
                    mobileMenuToggle.setAttribute('aria-expanded', !isExpanded);
                    
                    // Update button text for screen readers
                    const buttonText = mobileMenuToggle.querySelector('.sr-only');
                    if (buttonText) {
                        buttonText.textContent = !isExpanded ? 'Chiudi menu' : 'Menu';
                    }
                    
                    // Focus management
                    if (!isExpanded) {
                        // Menu is now open - focus first menu item
                        const firstMenuItem = mobileMenu.querySelector('a');
                        if (firstMenuItem) {
                            firstMenuItem.focus();
                        }
                    }
                });
                
                // Close menu on Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                        mobileMenuToggle.setAttribute('aria-expanded', 'false');
                        mobileMenuToggle.focus();
                    }
                });
            }

            // Enhanced loading overlay fade out with accessibility
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                setTimeout(() => {
                    overlay.style.opacity = '0';
                    overlay.setAttribute('aria-hidden', 'true');
                    setTimeout(() => {
                        overlay.style.display = 'none';
                    }, 300);
                }, 100);
            }

            // Progressive enhancement for animations with reduced motion respect
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            
            if ('IntersectionObserver' in window && !prefersReducedMotion.matches) {
                const observerOptions = {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                };

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }
                    });
                }, observerOptions);

                // Observe sections for scroll animations
                document.querySelectorAll('section').forEach(section => {
                    section.style.opacity = '0';
                    section.style.transform = 'translateY(20px)';
                    section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    observer.observe(section);
                });
            }

            // Enhanced smooth scroll for anchor links with accessibility
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        // Announce navigation to screen readers
                        const announcement = document.createElement('div');
                        announcement.setAttribute('aria-live', 'polite');
                        announcement.setAttribute('aria-atomic', 'true');
                        announcement.className = 'sr-only';
                        announcement.textContent = `Navigando a: ${target.textContent || target.getAttribute('aria-label') || 'sezione'}`;
                        document.body.appendChild(announcement);
                        
                        // Scroll to target
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                        
                        // Focus target for keyboard users
                        target.setAttribute('tabindex', '-1');
                        target.focus();
                        
                        // Clean up announcement
                        setTimeout(() => {
                            document.body.removeChild(announcement);
                        }, 1000);
                    }
                });
            });

            // Enhanced keyboard navigation for cards and interactive elements
            document.querySelectorAll('[role="listitem"], article').forEach(card => {
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        const link = card.querySelector('a');
                        if (link) {
                            e.preventDefault();
                            link.click();
                        }
                    }
                });
            });

            // Preload critical resources on user interaction
            let preloadTriggered = false;
            function preloadResources() {
                if (preloadTriggered) return;
                preloadTriggered = true;
                
                // Preload CSS
                const criticalCSS = [
                    '/frontend/css/animations.css',
                    '/frontend/css/enhanced-styles.css'
                ];
                
                criticalCSS.forEach(href => {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = href;
                    document.head.appendChild(link);
                });
            }

            // Trigger preload on first user interaction
            ['mousedown', 'touchstart', 'keydown'].forEach(event => {
                document.addEventListener(event, preloadResources, { once: true });
            });
            
            // Announce dynamic content changes to screen readers
            const notificationsContainer = document.getElementById('notifications-container');
            if (notificationsContainer) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            // Focus management for new notifications
                            const newNotification = mutation.addedNodes[0];
                            if (newNotification.nodeType === Node.ELEMENT_NODE) {
                                newNotification.setAttribute('role', 'alert');
                            }
                        }
                    });
                });
                
                observer.observe(notificationsContainer, {
                    childList: true,
                    subtree: true
                });
            }
        });

        // Service Worker registration for PWA capabilities
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/frontend/sw.js')
                    .then(registration => console.log('SW registered'))
                    .catch(error => console.log('SW registration failed'));
            });
        }
    </script>    <!-- Core JavaScript -->
    <script src="/BOSTARTER/frontend/js/utils.js"></script>
    <script src="/BOSTARTER/frontend/js/theme.js"></script>
    <script src="/BOSTARTER/frontend/js/mobile-menu.js"></script>
    <script src="/BOSTARTER/frontend/js/notifications.js"></script>
    
    <!-- Feature-specific JavaScript -->
    <script src="/BOSTARTER/frontend/js/projects.js" defer></script>
    <script src="/BOSTARTER/frontend/js/sections.js" defer></script>
    <script src="/BOSTARTER/frontend/js/newsletter.js" defer></script>
    
    <!-- Main JavaScript -->
    <script src="/BOSTARTER/frontend/js/main.js" type="module"></script>
    
    <!-- Performance Monitoring -->
    <script src="/BOSTARTER/frontend/js/performance.js" async></script>
      <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/BOSTARTER/frontend/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registered');
                    })
                    .catch(error => {
                        console.log('ServiceWorker registration failed:', error);
                    });
            });
        }
    </script>
</body>
</html>
