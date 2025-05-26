<?php
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth/auth.php';

session_start();

// Verifica se l'utente è autenticato
$isLoggedIn = isset($_SESSION['user_id']);
$user = $isLoggedIn ? $_SESSION['user'] : null;

// Ottieni i progetti in evidenza
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT * FROM v_progetti_attivi ORDER BY percentuale_completamento DESC LIMIT 6");
$progettiInEvidenza = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ottieni i top creatori
$stmt = $conn->query("SELECT * FROM v_top_creatori");
$topCreatori = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSTARTER - Piattaforma di Crowdfunding per Progetti Creativi</title>
    
    <!-- SEO e Meta Tags -->
    <meta name="description" content="BOSTARTER è la piattaforma leader per il crowdfunding di progetti creativi in Italia. Scopri, supporta o lancia la tua idea e trasformala in realtà.">
    <meta name="keywords" content="crowdfunding, progetti creativi, finanziamento collettivo, startup, innovazione, arte, design, tecnologia">
    <meta name="author" content="BOSTARTER">
    <meta name="robots" content="index, follow">
    <meta name="language" content="Italian">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="BOSTARTER - Piattaforma di Crowdfunding">
    <meta property="og:description" content="Scopri e supporta progetti creativi innovativi sulla principale piattaforma di crowdfunding italiana.">
    <meta property="og:image" content="frontend/images/hero-left.svg">
    <meta property="og:url" content="https://www.bostarter.it">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@bostarter">
    
    <!-- Performance e Accessibilità -->
    <meta name="theme-color" content="#3176FF">
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