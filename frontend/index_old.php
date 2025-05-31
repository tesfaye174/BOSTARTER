<?php
session_start();
require_once '../backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get featured projects (newest and most funded)
$featured_query = "SELECT p.*, u.username as creator_name,
                          COALESCE(pf.total_funded, 0) as total_funded,
                          COALESCE(pf.funding_percentage, 0) as funding_percentage,
                          COALESCE(pf.backers_count, 0) as backers_count,
                          DATEDIFF(p.deadline, NOW()) as days_left
                   FROM PROJECTS p
                   JOIN USERS u ON p.creator_id = u.user_id
                   LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
                   WHERE p.status = 'open'
                   ORDER BY p.created_at DESC, pf.funding_percentage DESC
                   LIMIT 6";
$featured_stmt = $db->prepare($featured_query);
$featured_stmt->execute();
$featured_projects = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get platform statistics
$stats_query = "SELECT 
                    COUNT(DISTINCT p.project_id) as total_projects,
                    COUNT(DISTINCT CASE WHEN p.status = 'open' THEN p.project_id END) as active_projects,
                    COUNT(DISTINCT u.user_id) as total_users,
                    COALESCE(SUM(f.amount), 0) as total_funded
                FROM PROJECTS p
                LEFT JOIN USERS u ON p.creator_id = u.user_id
                LEFT JOIN FUNDINGS f ON p.project_id = f.project_id";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSTARTER - Crowdfunding Platform for Hardware & Software Projects</title>
    <meta name="description" content="BOSTARTER is the leading crowdfunding platform for hardware and software projects. Discover innovative projects, support creators, or launch your own idea.">
    <meta name="keywords" content="crowdfunding, hardware projects, software projects, startup funding, innovation">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-icon {
            font-size: 3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .project-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .stats-section {
            background: #f8f9fa;
            padding: 60px 0;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .cta-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 80px 0;
        }
        .progress-thin { height: 6px; }
        .badge-project-type {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 1;
        }
        .badge-days {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 1;
        }
    </style>>
    <meta name="color-scheme" content="light dark">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="canonical" href="https://www.bostarter.it">
    
    <!-- PWA Support -->
    <link rel="manifest" href="/frontend/manifest.json">
    <link rel="apple-touch-icon" href="/frontend/images/icon-192x192.png">
    <meta name="apple-mobile-web-app-title" content="BOSTARTER">
    
    <!-- Preload risorse critiche -->
    <link rel="preload" href="frontend/css/main.css" as="style">
    <link rel="preload" href="frontend/js/main.js" as="script">
    <link rel="preload" href="frontend/images/logo1.svg" as="image">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" as="style">
    
    <!-- Preconnect a domini esterni -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { 
                        primary: {
                            DEFAULT: "#3176FF",
                            dark: "#2563eb",
                            light: "#60A5FA"
                        },
                        secondary: {
                            DEFAULT: "#FF6B35",
                            dark: "#ea580c",
                            light: "#FB923C"
                        }
                    }
                }
            }
        };
    </script>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Scripts -->
    <script src="js/main.js" defer></script>
    <script src="js/auth.js" defer></script>
    <script src="js/notifications.js" defer></script>
</head>
<body class="bg-gray-50 min-h-screen dark:bg-gray-900 transition-colors duration-300">
    <!-- Skip link per accessibilità -->
    <a href="#main-content" class="skip-link" tabindex="0">Vai al contenuto principale</a>

    <div id="notifications-container" class="fixed top-0 right-0 p-4 z-50 space-y-2" style="max-width: 400px;"></div>

    <?php include 'components/header.php'; ?>

    <main id="main-content">
        <!-- HERO SECTION -->
        <section class="hero-section flex flex-col md:flex-row items-center justify-between bg-white rounded-3xl shadow-lg p-8 md:p-16 mb-12 mt-8 relative overflow-hidden">
            <div class="relative w-full md:w-1/2 flex justify-center items-center mb-8 md:mb-0">
                <img src="frontend/images/hero-creative.jpg" alt="Creatrice con progetto" class="rounded-3xl shadow-lg object-cover w-80 h-80" style="clip-path: ellipse(70% 60% at 50% 40%);">
                <svg class="absolute -left-8 -top-8 w-64 h-64 z-0" viewBox="0 0 200 200" fill="none"><ellipse cx="100" cy="100" rx="100" ry="100" fill="#FDE68A"/></svg>
            </div>
            <div class="w-full md:w-1/2 z-10 text-center md:text-left">
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 leading-tight mb-4">
                    <span class="block">Sfrutta il</span>
                    <span class="text-primary">potenziale</span>
                    <span class="block">del finanziamento</span>
                </h1>
                <p class="text-lg text-gray-700 mb-6">
                    Qualunque sia il tuo sogno, su BOSTARTER puoi trovare le risorse per realizzarlo. Entra a far parte della community di sostenitori per trasformare le tue idee in realtà.
                </p>
                <?php if (!$isLoggedIn): ?>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                        <a href="auth/register.php" class="bg-primary text-white px-8 py-3 rounded-button font-medium hover:bg-primary-dark transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg">
                            Inizia ora
                            <i class="ri-arrow-right-line ml-2"></i>
                        </a>
                        <a href="explore.php" class="bg-transparent text-primary border-2 border-primary px-8 py-3 rounded-button font-medium hover:bg-primary/10 transition-all duration-300 transform hover:-translate-y-1">
                            Esplora progetti
                            <i class="ri-search-line ml-2"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- PROGETTI IN EVIDENZA -->
        <section class="featured-projects bg-gray-50 dark:bg-gray-800 py-16">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white animate-fade-in">Progetti in Evidenza</h2>
                    <a href="explore.php" class="text-primary hover:text-primary-dark transition-colors duration-300 flex items-center gap-2">
                        Vedi tutti
                        <i class="ri-arrow-right-line"></i>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($progettiInEvidenza as $progetto): ?>
                        <div class="project-card bg-white dark:bg-gray-700 rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:-translate-y-2 hover:shadow-xl">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($progetto['immagine_principale']); ?>" 
                                     alt="<?php echo htmlspecialchars($progetto['nome']); ?>" 
                                     class="w-full h-48 object-cover">
                                <div class="absolute top-4 right-4 bg-primary text-white px-3 py-1 rounded-full text-sm">
                                    <?php echo htmlspecialchars($progetto['categoria']); ?>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    <?php echo htmlspecialchars($progetto['nome']); ?>
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300 mb-4">
                                    <?php echo htmlspecialchars($progetto['descrizione_breve']); ?>
                                </p>
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600 dark:text-gray-300">Raccolto</span>
                                        <span class="text-primary font-medium">€<?php echo number_format($progetto['budget_raccolto'], 2); ?></span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="bg-primary h-2 rounded-full" style="width: <?php echo $progetto['percentuale_completamento']; ?>%"></div>
                                    </div>
                                </div>
                                <a href="progetti/dettaglio.php?id=<?php echo $progetto['id']; ?>" 
                                   class="btn-primary w-full text-center py-2 rounded-lg hover:bg-primary-dark transition-colors duration-300">
                                    Scopri
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- TOP CREATORI -->
        <section class="top-creators bg-white dark:bg-gray-800 py-16">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-12">Top Creatori</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <?php foreach ($topCreatori as $creatore): ?>
                        <div class="creator-card bg-white dark:bg-gray-700 rounded-xl shadow-lg p-6 text-center transform hover:-translate-y-1 transition-all duration-300">
                            <img src="<?php echo htmlspecialchars($creatore['avatar']); ?>" 
                                 alt="<?php echo htmlspecialchars($creatore['nickname']); ?>" 
                                 class="w-24 h-24 rounded-full mx-auto mb-4 object-cover">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                <?php echo htmlspecialchars($creatore['nickname']); ?>
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                <?php echo $creatore['progetti_completati']; ?> progetti completati
                            </p>
                            <a href="creatori/profilo.php?id=<?php echo $creatore['id']; ?>" 
                               class="text-primary hover:text-primary-dark transition-colors duration-300">
                                Visita profilo
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include 'components/footer.php'; ?>

    <!-- Modali -->
    <?php if (!$isLoggedIn): ?>
        <?php include 'components/modals/login.php'; ?>
        <?php include 'components/modals/register.php'; ?>
    <?php endif; ?>

    <script>
        // Registrazione Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/frontend/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registrato:', registration);
                    })
                    .catch(error => {
                        console.log('Errore nella registrazione del ServiceWorker:', error);
                    });
            });
        }
    </script>
</body>
</html> 