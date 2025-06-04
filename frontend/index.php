<?php
session_start();

// Initialize error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files with error handling
try {
    require_once __DIR__ . '/../backend/config/database.php';
    require_once __DIR__ . '/../backend/services/MongoLogger.php';
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
    try {
        // Total projects
        $stmt = $db->query("SELECT COUNT(*) as count FROM progetti WHERE stato IN ('aperto', 'completato')");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_projects'] = $result ? $result['count'] : 0;
        
        // Total funding
        $stmt = $db->query("SELECT SUM(budget_raccolto) as total FROM progetti WHERE stato = 'completato'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_funding'] = $result ? $result['total'] : 0;
        
        // Total backers
        $stmt = $db->query("SELECT COUNT(DISTINCT utente_id) as count FROM finanziamenti WHERE stato_pagamento = 'completato'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_backers'] = $result ? $result['count'] : 0;
        
        // Success rate
        $total_projects = $stats['total_projects'];
        if ($total_projects > 0) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM progetti WHERE stato = 'completato'");
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
    try {        $top_projects_query = "
            SELECT 
                p.id,
                p.nome as title,
                p.descrizione as description,
                p.budget_richiesto as funding_goal,
                p.budget_raccolto as current_funding,
                p.immagine_principale as image,
                cp.nome as category,
                p.data_scadenza as deadline,
                u.nickname as creator_name,
                u.avatar as creator_avatar,
                ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 1) as funding_percentage,
                DATEDIFF(p.data_scadenza, NOW()) as days_left,
                p.nr_sostenitori as backers_count
            FROM progetti p
            JOIN utenti u ON p.creatore_id = u.id
            JOIN categorie_progetti cp ON p.categoria_id = cp.id
            WHERE p.stato = 'aperto' AND p.data_scadenza > NOW()
            ORDER BY p.budget_raccolto DESC
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
    try {        $recent_projects_query = "
            SELECT 
                p.id,
                p.nome as title,
                p.descrizione as description,
                p.budget_richiesto as funding_goal,
                p.budget_raccolto as current_funding,
                p.immagine_principale as image,
                cp.nome as category,
                u.nickname as creator_name,
                ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 1) as funding_percentage,
                DATEDIFF(p.data_scadenza, NOW()) as days_left
            FROM progetti p
            JOIN utenti u ON p.creatore_id = u.id
            JOIN categorie_progetti cp ON p.categoria_id = cp.id
            WHERE p.stato = 'aperto' AND p.data_scadenza > NOW()
            ORDER BY p.data_creazione DESC
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

// Get project categories
$categories = [
    ['id' => 'arte', 'name' => 'Arte', 'icon' => 'fas fa-palette', 'color' => 'from-pink-500 to-rose-500'],
    ['id' => 'design', 'name' => 'Design', 'icon' => 'fas fa-drafting-compass', 'color' => 'from-purple-500 to-indigo-500'],
    ['id' => 'musica', 'name' => 'Musica', 'icon' => 'fas fa-music', 'color' => 'from-blue-500 to-cyan-500'],
    ['id' => 'tecnologia', 'name' => 'Tecnologia', 'icon' => 'fas fa-microchip', 'color' => 'from-green-500 to-emerald-500'],
    ['id' => 'fumetti', 'name' => 'Fumetti', 'icon' => 'fas fa-book-open', 'color' => 'from-yellow-500 to-orange-500'],
    ['id' => 'altro', 'name' => 'Altro', 'icon' => 'fas fa-ellipsis-h', 'color' => 'from-gray-500 to-slate-500']
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
?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSTARTER - Piattaforma di Crowdfunding Italiana</title>
    <meta name="description" content="BOSTARTER è la piattaforma leader per il crowdfunding di progetti creativi in Italia. Scopri, sostieni o lancia la tua idea e trasformala in realtà.">
    <meta name="keywords" content="crowdfunding, progetti creativi, finanziamento collettivo, startup, innovazione, arte, design, tecnologia">
    <meta name="author" content="BOSTARTER">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#3176FF">
    <link rel="canonical" href="https://www.bostarter.it">
    <link rel="icon" type="image/svg+xml" href="/frontend/images/logo1.svg">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/frontend/css/main.css">
    <link rel="stylesheet" href="/frontend/css/color-system.css">
    <link rel="stylesheet" href="/frontend/css/animations.css">
    <link rel="stylesheet" href="/frontend/css/components.css">
    <link rel="stylesheet" href="/frontend/css/enhanced-styles.css">
    <link rel="stylesheet" href="/frontend/css/utilities.css">
    <link rel="stylesheet" href="/frontend/css/critical.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a1a1a',
                        secondary: '#ffffff',
                        tertiary: '#f8f9fa',
                        brand: '#3176FF',
                        'brand-dark': '#2563eb',
                        accent: '#10b981'
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        brand: ['Poppins', 'Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-tertiary min-h-screen">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand"></div>
    </div>

    <!-- Notifications Container -->
    <div id="notifications-container" class="fixed top-4 right-4 z-40 space-y-2 max-w-sm"></div>

    <!-- Header -->
    <header class="bg-secondary/90 backdrop-blur-md shadow-sm border-b sticky top-0 z-30">
        <nav class="container mx-auto px-4 py-3 flex items-center justify-between">
            <!-- Logo and Brand -->
            <a href="/frontend/img/logo1.svg" class="flex items-center font-brand text-2xl text-brand hover:text-brand-dark transition-colors">
                <img src="frontend/images/logo1.svg" alt="" class="h-8 w-auto mr-2">
            </a>

            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg hover:bg-tertiary transition-colors">
                <i class="fas fa-bars text-xl text-primary"></i>
            </button>

            <!-- Desktop Navigation -->
            <ul class="hidden md:flex gap-6 font-medium text-lg text-primary">
                <li><a href="/frontend/assets/arte/index.html" class="hover:text-brand transition-colors">Arte</a></li>
                <li><a href="/frontend/assets/fumetti/index.html" class="hover:text-brand transition-colors">Fumetti</a></li>
                <li><a href="/frontend/assets/design/index.html" class="hover:text-brand transition-colors">Design</a></li>
                <li><a href="/frontend/assets/musica/index.html" class="hover:text-brand transition-colors">Musica</a></li>
                <li><a href="/frontend/assets/tecnologia/index.html" class="hover:text-brand transition-colors">Tecnologia</a></li>
                <li><a href="/frontend/assets/scopritutti/index.html" class="hover:text-brand transition-colors">Scopri Tutti</a></li>
            </ul>

            <!-- User Actions -->
            <div class="hidden md:flex gap-3 items-center">
                <?php if ($is_logged_in): ?>
                    <a href="/frontend/dashboard.php" class="px-4 py-2 bg-brand text-white rounded-lg hover:bg-brand-dark transition-colors">
                        Dashboard
                    </a>
                    <a href="/frontend/logout.php" class="px-4 py-2 border border-brand text-brand rounded-lg hover:bg-brand hover:text-white transition-colors">
                        Esci
                    </a>
                <?php else: ?>
                    <a href="/backend/api/login.php" class="px-4 py-2 border border-brand text-brand rounded-lg hover:bg-brand hover:text-white transition-colors">
                        Accedi
                    </a>
                    <a href="/frontend/create-project.html" class="px-4 py-2 bg-brand text-white rounded-lg hover:bg-brand-dark transition-colors">
                        Crea Progetto
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-secondary border-t">
            <div class="px-4 py-3 space-y-3">
                <a href="/frontend/assets/arte/index.html" class="block py-2 text-primary hover:text-brand">Arte</a>
                <a href="/frontend/assets/fumetti/index.html" class="block py-2 text-primary hover:text-brand">Fumetti</a>
                <a href="/frontend/assets/design/index.html" class="block py-2 text-primary hover:text-brand">Design</a>
                <a href="/frontend/assets/musica/index.html" class="block py-2 text-primary hover:text-brand">Musica</a>
                <a href="/frontend/assets/tecnologia/index.html" class="block py-2 text-primary hover:text-brand">Tecnologia</a>
                <a href="/frontend/assets/scopritutti/index.html" class="block py-2 text-primary hover:text-brand">Scopri Tutti</a>
                <div class="pt-3 border-t space-y-2">
                    <?php if ($is_logged_in): ?>
                        <a href="/frontend/dashboard.php" class="block w-full text-center px-4 py-2 bg-brand text-white rounded-lg">Dashboard</a>
                        <a href="/frontend/logout.php" class="block w-full text-center px-4 py-2 border border-brand text-brand rounded-lg">Esci</a>
                    <?php else: ?>
                        <a href="backend/api/login.php" class="block w-full text-center px-4 py-2 border border-brand text-brand rounded-lg">Accedi</a>
                        <a href="/frontend/create-project.html" class="block w-full text-center px-4 py-2 bg-brand text-white rounded-lg">Crea Progetto</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-brand to-indigo-700 text-white py-20 overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 animate-fade-in">
                Trasforma le tue <span class="text-yellow-300">idee creative</span><br>
                in <span class="text-green-300">successo</span>
            </h1>
            <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90">
                La piattaforma di crowdfunding italiana che connette creators visionari con sostenitori appassionati
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/frontend/create-project.html" class="bg-yellow-400 text-gray-900 px-8 py-4 rounded-lg font-bold text-lg hover:bg-yellow-300 transition-colors shadow-lg">
                    Lancia il tuo progetto
                </a>
                <a href="/frontend/assets/scopritutti/index.html" class="border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-brand transition-colors">
                    Esplora progetti
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-secondary">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="space-y-2">
                    <div class="text-3xl md:text-4xl font-bold text-brand"><?php echo $formatted_stats['projects']; ?></div>
                    <div class="text-gray-600 font-medium">Progetti Finanziati</div>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl md:text-4xl font-bold text-green-600"><?php echo $formatted_stats['funded']; ?></div>
                    <div class="text-gray-600 font-medium">Fondi Raccolti</div>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl md:text-4xl font-bold text-purple-600"><?php echo $formatted_stats['backers']; ?></div>
                    <div class="text-gray-600 font-medium">Sostenitori Attivi</div>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl md:text-4xl font-bold text-orange-600"><?php echo $formatted_stats['success_rate']; ?></div>
                    <div class="text-gray-600 font-medium">Tasso di Successo</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-16 bg-tertiary">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-primary mb-4">Esplora per Categoria</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Trova progetti incredibili in ogni settore che ti appassiona
                </p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <?php foreach ($categories as $category): ?>
                <a href="/frontend/assets/<?php echo $category['id']; ?>/index.html" 
                   class="group bg-secondary rounded-xl p-6 text-center hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                    <div class="bg-gradient-to-r <?php echo $category['color']; ?> w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="<?php echo $category['icon']; ?> text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold text-primary group-hover:text-brand transition-colors">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </h3>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Projects Section -->
    <?php if (!empty($featured_projects)): ?>
    <section class="py-16 bg-secondary">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-primary mb-4">Progetti in Evidenza</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    I progetti più sostenuti della nostra community
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($featured_projects as $project): ?>
                <div class="bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <?php if (!empty($project['image'])): ?>
                    <div class="relative overflow-hidden">
                        <img src="<?php echo htmlspecialchars($project['image']); ?>" 
                             alt="<?php echo htmlspecialchars($project['title']); ?>"
                             class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute top-4 left-4">
                            <span class="bg-brand text-white text-xs font-semibold px-3 py-1 rounded-full">
                                IN EVIDENZA
                            </span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="w-full h-48 bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center relative">
                        <i class="fas fa-image text-4xl text-white/50"></i>
                        <div class="absolute top-4 left-4">
                            <span class="bg-brand text-white text-xs font-semibold px-3 py-1 rounded-full">
                                IN EVIDENZA
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full uppercase">
                                <?php echo htmlspecialchars($project['category']); ?>
                            </span>
                            <span class="text-sm text-gray-500 font-medium">
                                <?php echo getDaysLeftText($project['days_left']); ?>
                            </span>
                        </div>
                        
                        <h3 class="text-xl font-bold text-primary mb-2 group-hover:text-brand transition-colors">
                            <?php echo htmlspecialchars($project['title']); ?>
                        </h3>
                        
                        <p class="text-gray-600 mb-4 line-clamp-2">
                            <?php echo truncateText(htmlspecialchars($project['description']), 80); ?>
                        </p>
                        
                        <div class="mb-4">
                            <div class="flex justify-between items-center text-sm text-gray-600 mb-2">
                                <span class="font-semibold"><?php echo formatCurrency($project['current_funding']); ?> raccolti</span>
                                <span class="font-semibold"><?php echo $project['funding_percentage']; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-500 to-blue-500 h-2 rounded-full transition-all duration-500" 
                                     style="width: <?php echo min($project['funding_percentage'], 100); ?>%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>Obiettivo: <?php echo formatCurrency($project['funding_goal']); ?></span>
                                <span><?php echo $project['backers_count']; ?> sostenitori</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <?php if (!empty($project['creator_avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($project['creator_avatar']); ?>" 
                                     alt="<?php echo htmlspecialchars($project['creator_name']); ?>"
                                     class="w-8 h-8 rounded-full mr-2">
                                <?php else: ?>
                                <div class="w-8 h-8 bg-gray-300 rounded-full mr-2 flex items-center justify-center">
                                    <i class="fas fa-user text-xs text-gray-600"></i>
                                </div>
                                <?php endif; ?>
                                <span class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($project['creator_name']); ?>
                                </span>
                            </div>
                            <a href="/frontend/project.php?id=<?php echo $project['id']; ?>" 
                               class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-dark transition-colors">
                                Sostieni
                            </a>
                        </div>
                    </div>
                </div>
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
            </div>
            
            <div class="text-center mt-12">
                <a href="/frontend/assets/scopritutti/index.html" 
                   class="bg-brand text-white px-8 py-3 rounded-lg font-semibold hover:bg-brand-dark transition-colors inline-block">
                    Vedi Tutti i Progetti
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-purple-600 to-blue-600 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">
                Pronto a lanciare il tuo progetto?
            </h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto">
                Unisciti a migliaia di creatori che hanno trasformato le loro idee in successo con BOSTARTER
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/frontend/create-project.html" 
                   class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    Crea il tuo progetto
                </a>
                <a href="/frontend/assets/scopritutti/index.html" 
                   class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition-colors">
                    Esplora progetti
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <img src="/frontend/images/logo1.svg" alt="BOSTARTER Logo" class="h-8 w-auto mr-2">
                        <span class="text-xl font-bold">BOSTARTER</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        La piattaforma italiana che trasforma idee creative in realtà attraverso il crowdfunding.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Progetti</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/frontend/assets/scopritutti/index.html" class="hover:text-white transition-colors">Esplora</a></li>
                        <li><a href="/frontend/assets/arte/index.html" class="hover:text-white transition-colors">Arte</a></li>
                        <li><a href="/frontend/assets/tecnologia/index.html" class="hover:text-white transition-colors">Tecnologia</a></li>
                        <li><a href="/frontend/assets/design/index.html" class="hover:text-white transition-colors">Design</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Supporto</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Come Funziona</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">FAQ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contatti</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Centro Assistenza</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Termini di Servizio</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 BOSTARTER. Tutti i diritti riservati. Made with ❤️ in Italy</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Loading overlay
        window.addEventListener('load', function() {
            const overlay = document.getElementById('loading-overlay');
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.style.pointerEvents = 'none';
            }, 300);
        });

        // Smooth scroll for anchor links
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

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('section').forEach(section => {
            observer.observe(section);
        });
    </script>

    <!-- Additional Scripts -->
    <script src="/frontend/js/main.js" defer></script>
    <script src="/frontend/js/animations.js" defer></script>
</body>
</html>
