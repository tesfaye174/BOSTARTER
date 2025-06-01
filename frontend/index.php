<?php
session_start();
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/services/MongoLogger.php';

// Initialize database and logger
$database = new Database();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

// Log homepage visit
if (isset($_SESSION['user_id'])) {
    $mongoLogger->logActivity($_SESSION['user_id'], 'homepage_visit', [
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    $mongoLogger->logSystem('anonymous_homepage_visit', [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}

// Get top 3 most funded projects using the view
try {
    $top_projects_query = "
        SELECT 
            p.id,
            p.nome as title,
            p.descrizione as description,
            p.budget_richiesto as funding_goal,
            p.budget_raccolto as current_funding,
            p.immagine_principale as image,
            p.categoria as category,
            p.tipo_progetto as project_type,
            p.data_scadenza as deadline,
            u.nickname as creator_name,
            u.avatar as creator_avatar,
            ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 1) as funding_percentage,
            DATEDIFF(p.data_scadenza, NOW()) as days_left,
            (SELECT COUNT(*) FROM finanziamenti f WHERE f.progetto_id = p.id AND f.stato_pagamento = 'completato') as backers_count
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
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

// Get platform statistics
try {
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM progetti WHERE stato IN ('completato', 'aperto')) as total_projects,
            (SELECT COUNT(DISTINCT creatore_id) FROM progetti WHERE stato IN ('completato', 'aperto')) as total_creators,
            (SELECT COUNT(DISTINCT utente_id) FROM finanziamenti WHERE stato_pagamento = 'completato') as total_backers,
            (SELECT COALESCE(SUM(importo), 0) FROM finanziamenti WHERE stato_pagamento = 'completato') as total_funded
    ";
    
    $stmt = $db->prepare($stats_query);
    $stmt->execute();
    $platform_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $platform_stats = [
        'total_projects' => 0,
        'total_creators' => 0, 
        'total_backers' => 0,
        'total_funded' => 0
    ];
    error_log("Error fetching platform stats: " . $e->getMessage());
}

// Get project categories
$categories = [
    ['id' => 'arte', 'name' => 'Arte', 'icon' => 'ri-brush-line', 'color' => 'bg-pink-500'],
    ['id' => 'design', 'name' => 'Design', 'icon' => 'ri-palette-line', 'color' => 'bg-purple-500'],
    ['id' => 'musica', 'name' => 'Musica', 'icon' => 'ri-music-line', 'color' => 'bg-blue-500'],
    ['id' => 'tecnologia', 'name' => 'Tecnologia', 'icon' => 'ri-computer-line', 'color' => 'bg-green-500'],
    ['id' => 'fumetti', 'name' => 'Fumetti', 'icon' => 'ri-book-line', 'color' => 'bg-yellow-500'],
    ['id' => 'altro', 'name' => 'Altro', 'icon' => 'ri-more-line', 'color' => 'bg-gray-500']
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSTARTER - Piattaforma di Crowdfunding</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="BOSTARTER è la piattaforma leader per il crowdfunding di progetti creativi in Italia. Scopri, supporta o lancia la tua idea e trasformala in realtà.">
    <meta name="keywords" content="crowdfunding, progetti creativi, finanziamento collettivo, startup, innovazione, arte, design, tecnologia">
    
    <!-- CSS Framework -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    
    <!-- Custom CSS -->
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .stat-counter {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-indigo-600">
                        <i class="ri-rocket-line mr-2"></i>BOSTARTER
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="flex-1 max-w-lg mx-8">
                    <form action="frontend/projects/list_open.php" method="GET" class="relative">
                        <input type="text" 
                               name="search" 
                               placeholder="Cerca progetti..." 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <i class="ri-search-line absolute left-3 top-3 text-gray-400"></i>
                    </form>
                </div>

                <!-- Navigation -->
                <nav class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Logged in user menu -->
                        <a href="frontend/dashboard.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md">
                            <i class="ri-dashboard-line mr-1"></i>Dashboard
                        </a>
                        <a href="frontend/projects/create.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                            <i class="ri-add-line mr-1"></i>Crea Progetto
                        </a>
                        <div class="relative group">
                            <button class="flex items-center text-gray-700 hover:text-indigo-600">
                                <i class="ri-user-line mr-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user']['nickname'] ?? 'Utente'); ?>
                                <i class="ri-arrow-down-s-line ml-1"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <a href="frontend/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="ri-user-line mr-2"></i>Profilo
                                </a>
                                <a href="frontend/projects/list_open.php?creator=<?php echo $_SESSION['user_id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="ri-folder-line mr-2"></i>I miei progetti
                                </a>
                                <hr class="my-1">
                                <a href="frontend/auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <i class="ri-logout-box-line mr-2"></i>Esci
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Guest menu -->
                        <a href="frontend/auth/login.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md">
                            Accedi
                        </a>
                        <a href="frontend/auth/register.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                            Registrati
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl md:text-6xl font-bold mb-6">
                Trasforma le tue <span class="text-yellow-300">idee creative</span> in realtà
            </h1>
            <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90">
                Scopri, sostieni e lancia progetti innovativi sulla piattaforma di crowdfunding più dinamica d'Italia
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="frontend/projects/list_open.php" class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-300">
                    <i class="ri-compass-3-line mr-2"></i>
                    Esplora Progetti
                </a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="frontend/auth/register.php" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition-all duration-300">
                    <i class="ri-rocket-line mr-2"></i>
                    Lancia il tuo progetto
                </a>
                <?php else: ?>
                <a href="frontend/projects/create.php" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition-all duration-300">
                    <i class="ri-rocket-line mr-2"></i>
                    Crea un progetto
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured Projects -->
    <?php if (!empty($featured_projects)): ?>
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Progetti in Evidenza</h2>
                <p class="text-xl text-gray-600">I progetti più finanziati del momento</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php foreach ($featured_projects as $project): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
                    <!-- Project Image -->
                    <div class="relative h-48 bg-gradient-to-br from-indigo-400 to-purple-500">
                        <?php if ($project['image']): ?>
                            <img src="<?php echo htmlspecialchars($project['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($project['title']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-white">
                                <i class="ri-image-line text-4xl"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Category Badge -->
                        <div class="absolute top-4 left-4">
                            <span class="px-3 py-1 bg-white/90 text-indigo-600 rounded-full text-sm font-medium">
                                <?php echo ucfirst(htmlspecialchars($project['category'])); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Project Info -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                            <?php echo htmlspecialchars($project['title']); ?>
                        </h3>
                        
                        <p class="text-gray-600 mb-4 line-clamp-2">
                            <?php echo htmlspecialchars(substr($project['description'], 0, 120)); ?>...
                        </p>

                        <!-- Creator Info -->
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                <i class="ri-user-line text-gray-600"></i>
                            </div>
                            <span class="text-gray-700 font-medium">
                                <?php echo htmlspecialchars($project['creator_name']); ?>
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span><?php echo number_format($project['funding_percentage'], 1); ?>% finanziato</span>
                                <span><?php echo $project['days_left']; ?> giorni rimasti</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500" 
                                     style="width: <?php echo min($project['funding_percentage'], 100); ?>%"></div>
                            </div>
                        </div>

                        <!-- Funding Info -->
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <div class="text-2xl font-bold text-gray-900">
                                    €<?php echo number_format($project['current_funding'], 0, ',', '.'); ?>
                                </div>
                                <div class="text-sm text-gray-600">
                                    di €<?php echo number_format($project['funding_goal'], 0, ',', '.'); ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-semibold text-gray-900">
                                    <?php echo $project['backers_count']; ?>
                                </div>
                                <div class="text-sm text-gray-600">sostenitori</div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <a href="frontend/projects/detail.php?id=<?php echo $project['id']; ?>" 
                           class="w-full inline-flex items-center justify-center px-4 py-3 btn-primary text-white rounded-lg font-medium">
                            <i class="ri-eye-line mr-2"></i>
                            Scopri di più
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-12">
                <a href="frontend/projects/list_open.php" 
                   class="inline-flex items-center px-6 py-3 border-2 border-indigo-600 text-indigo-600 rounded-lg font-medium hover:bg-indigo-600 hover:text-white transition-all duration-300">
                    <i class="ri-grid-line mr-2"></i>
                    Vedi tutti i progetti
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Categories Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Esplora per Categoria</h2>
                <p class="text-xl text-gray-600">Trova progetti che ti appassionano</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <?php foreach ($categories as $category): ?>
                <a href="frontend/projects/list_open.php?category=<?php echo $category['id']; ?>" 
                   class="group bg-white rounded-xl p-6 text-center card-hover">
                    <div class="w-16 h-16 <?php echo $category['color']; ?> rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="<?php echo $category['icon']; ?> text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
                        <?php echo $category['name']; ?>
                    </h3>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">I numeri di BOSTARTER</h2>
                <p class="text-xl text-gray-600">La community che fa la differenza</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-indigo-600 mb-2 stat-counter">
                        <?php echo number_format($platform_stats['total_projects']); ?>
                    </div>
                    <div class="text-gray-600 font-medium">Progetti Finanziati</div>
                </div>
                
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-indigo-600 mb-2 stat-counter">
                        <?php echo number_format($platform_stats['total_creators']); ?>
                    </div>
                    <div class="text-gray-600 font-medium">Creatori Attivi</div>
                </div>
                
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-indigo-600 mb-2 stat-counter">
                        <?php echo number_format($platform_stats['total_backers']); ?>
                    </div>
                    <div class="text-gray-600 font-medium">Sostenitori</div>
                </div>
                
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-indigo-600 mb-2 stat-counter">
                        €<?php echo number_format($platform_stats['total_funded'], 0, ',', '.'); ?>
                    </div>
                    <div class="text-gray-600 font-medium">Fondi Raccolti</div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Come Funziona</h2>
                <p class="text-xl text-gray-600">Tre semplici passi per trasformare la tua idea in realtà</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-indigo-600 text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
                        1
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Presenta il tuo progetto</h3>
                    <p class="text-gray-600">
                        Crea una pagina accattivante per il tuo progetto con descrizioni, immagini e video.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-indigo-600 text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
                        2
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Raccogli fondi</h3>
                    <p class="text-gray-600">
                        Condividi il tuo progetto e raccogli finanziamenti dalla community interessata.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-indigo-600 text-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
                        3
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Realizza il progetto</h3>
                    <p class="text-gray-600">
                        Una volta raggiunto l'obiettivo, utilizza i fondi per realizzare la tua idea.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="hero-gradient text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">
                Pronto a realizzare il tuo progetto?
            </h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto opacity-90">
                Unisciti a migliaia di creatori che hanno trasformato le loro idee in realtà
            </p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="frontend/projects/create.php" 
                   class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-300">
                    <i class="ri-rocket-line mr-2"></i>
                    Crea il tuo progetto
                </a>
            <?php else: ?>
                <a href="frontend/auth/register.php" 
                   class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-300">
                    <i class="ri-rocket-line mr-2"></i>
                    Inizia ora
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="text-2xl font-bold mb-4">
                        <i class="ri-rocket-line mr-2"></i>BOSTARTER
                    </div>
                    <p class="text-gray-400 mb-4">
                        La piattaforma italiana di crowdfunding per progetti creativi e innovativi.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-facebook-fill text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-twitter-fill text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="ri-instagram-line text-xl"></i></a>
                    </div>
                </div>

                <!-- Platform -->
                <div>
                    <h3 class="font-semibold mb-4">Piattaforma</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="frontend/projects/list_open.php" class="hover:text-white">Esplora progetti</a></li>
                        <li><a href="frontend/projects/create.php" class="hover:text-white">Crea progetto</a></li>
                        <li><a href="frontend/stats/" class="hover:text-white">Statistiche</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="font-semibold mb-4">Supporto</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Centro assistenza</a></li>
                        <li><a href="#" class="hover:text-white">Contattaci</a></li>
                        <li><a href="#" class="hover:text-white">FAQ</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h3 class="font-semibold mb-4">Legale</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Termini di servizio</a></li>
                        <li><a href="#" class="hover:text-white">Privacy policy</a></li>
                        <li><a href="#" class="hover:text-white">Cookie policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 BOSTARTER. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript for enhanced functionality -->
    <script>
        // Counter animation for statistics
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-counter');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent.replace(/[^\d]/g, ''));
                let current = 0;
                const increment = target / 100;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = counter.textContent.replace(/\d+/, target.toLocaleString());
                        clearInterval(timer);
                    } else {
                        counter.textContent = counter.textContent.replace(/\d+/, Math.floor(current).toLocaleString());
                    }
                }, 20);
            });
        }

        // Trigger counter animation when section comes into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        });

        const statsSection = document.querySelector('.stat-counter').closest('section');
        if (statsSection) {
            observer.observe(statsSection);
        }

        // Smooth scrolling for anchor links
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
    </script>
</body>
</html>
