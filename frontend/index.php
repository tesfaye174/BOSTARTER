<?php
/**
 * BOSTARTER Frontend - Homepage
 * Sistema ottimizzato con sicurezza e performance migliorate
 * Versione: 2.1.0 - Gennaio 2025
 */

// ===============================
// INIZIALIZZAZIONE SICUREZZA E SESSIONE
// ===============================
declare(strict_types=1);

if (!defined('BOSTARTER_SECURE')) {
    define('BOSTARTER_SECURE', true);
}

// Configurazione moderna della sessione
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Impostazioni ambiente moderne
match(true) {
    defined('PRODUCTION') && PRODUCTION => [
        error_reporting(E_ALL),
        ini_set('display_errors', '0'),
        ini_set('log_errors', '1'),
        ini_set('error_log', __DIR__ . '/../logs/production-errors.log')
    ],
    default => [
        error_reporting(E_ALL),
        ini_set('display_errors', '1'),
        ini_set('error_log', __DIR__ . '/../logs/development-errors.log')
    ]
};

// ===============================
// FUNZIONE CARICAMENTO DIPENDENZE MODERNA
// ===============================
final class DependencyLoader {
    private static array $loadedDependencies = [];
    
    public static function load(string $path, ?string $className = null): bool {
        $fullPath = __DIR__ . '/../backend/' . $path;
        $cacheKey = $className ?? basename($path, '.php');
        
        if (isset(self::$loadedDependencies[$cacheKey])) {
            return self::$loadedDependencies[$cacheKey];
        }
        
        try {
            if (!file_exists($fullPath)) {
                throw new RuntimeException("File non trovato: {$path}");
            }
            
            require_once $fullPath;
            
            if ($className && !class_exists($className)) {
                throw new RuntimeException("Classe non trovata: {$className}");
            }
            
            self::$loadedDependencies[$cacheKey] = true;
            return true;
            
        } catch (Throwable $e) {
            error_log("Errore caricamento dipendenza {$path}: " . $e->getMessage());
            self::$loadedDependencies[$cacheKey] = false;
            return false;
        }
    }
}

// Caricamento delle dipendenze principali
$coreDependencies = [
    'config/database.php' => 'Database',
    'services/MongoLogger.php' => 'MongoLogger',
    'utils/NavigationHelper.php' => 'NavigationHelper',
    'middleware/SecurityMiddleware.php' => 'SecurityMiddleware',
    'utils/FrontendSecurity.php' => 'FrontendSecurity',
    'utils/ResourceOptimizer.php' => null,
    'utils/CacheManager.php' => 'CacheManager'
];

foreach ($coreDependencies as $path => $class) {
    DependencyLoader::load($path, $class);
}

// ===============================
// INIZIALIZZAZIONE SICUREZZA
// ===============================
try {
    if (class_exists('SecurityMiddleware') && method_exists('SecurityMiddleware', 'initialize')) {
        SecurityMiddleware::initialize();
    }
    if (class_exists('FrontendSecurity') && method_exists('FrontendSecurity', 'setCSPWithNonce')) {
        // Usa la CSP con nonce per gli script esterni
        $script_nonce = FrontendSecurity::setCSPWithNonce();
    } else if (class_exists('FrontendSecurity')) {
        FrontendSecurity::setSecurityHeaders();
    } else {
        // Headers di sicurezza di fallback
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        // CSP aggiornata per permettere cdn.jsdelivr.net e altri CDN necessari
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self';");
    }
} catch (Throwable $e) {
    error_log("Errore inizializzazione sicurezza: " . $e->getMessage());
}

// ===============================
// INIZIALIZZAZIONE VARIABILI UTENTE
// ===============================
$featured_projects = $recent_projects = [];
$stats = [
    'total_projects' => 0,
    'total_funding' => 0,
    'total_backers' => 0,
    'success_rate' => 0
];
$is_logged_in = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
$user_id = $is_logged_in ? (int)$_SESSION['user_id'] : null;
$username = $is_logged_in ? htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8') : 'Guest';

// ===============================
// Definizione Categorie (con metadati)
// ===============================
$categories = [
    'tech' => ['label' => 'Tecnologia', 'icon' => 'fas fa-microchip', 'gradient' => 'from-blue-500 to-cyan-500'],
    'design' => ['label' => 'Design', 'icon' => 'fas fa-paintbrush', 'gradient' => 'from-purple-500 to-pink-500'],
    'arte' => ['label' => 'Arte', 'icon' => 'fas fa-palette', 'gradient' => 'from-red-500 to-orange-500'],
    'musica' => ['label' => 'Musica', 'icon' => 'fas fa-music', 'gradient' => 'from-green-500 to-teal-500'],
    'film' => ['label' => 'Film', 'icon' => 'fas fa-video', 'gradient' => 'from-indigo-500 to-purple-500'],
    'editoriale' => ['label' => 'Editoriale', 'icon' => 'fas fa-book', 'gradient' => 'from-yellow-500 to-orange-500'],
    'food' => ['label' => 'Food', 'icon' => 'fas fa-utensils', 'gradient' => 'from-green-500 to-lime-500'],
    'moda' => ['label' => 'Moda', 'icon' => 'fas fa-tshirt', 'gradient' => 'from-pink-500 to-rose-500'],
    'gaming' => ['label' => 'Gaming', 'icon' => 'fas fa-gamepad', 'gradient' => 'from-blue-500 to-purple-500'],
    'hardware' => ['label' => 'Hardware', 'icon' => 'fas fa-cogs', 'gradient' => 'from-gray-500 to-slate-500'],
    'software' => ['label' => 'Software', 'icon' => 'fas fa-code', 'gradient' => 'from-green-500 to-emerald-500'],
    'altro' => ['label' => 'Altro', 'icon' => 'fas fa-ellipsis-h', 'gradient' => 'from-gray-400 to-gray-500']
];

// ===============================
// CARICAMENTO DATI DATABASE MODERNO
// ===============================
final class ProjectDataLoader {
    private PDO $conn;
    
    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }
    
    public function getFeaturedProjects(): array {
        $stmt = $this->conn->prepare("
            SELECT 
                p.*, 
                u.nickname as creator_name,
                COALESCE((
                    SELECT SUM(importo) 
                    FROM finanziamenti f 
                    WHERE f.progetto_id = p.id
                ), 0) as total_funding,
                COUNT(DISTINCT f.utente_id) as backers_count
            FROM progetti p 
            LEFT JOIN utenti u ON p.creatore_id = u.id 
            LEFT JOIN finanziamenti f ON p.id = f.progetto_id
            WHERE p.stato = 'aperto' 
            GROUP BY p.id, u.nickname
            ORDER BY total_funding DESC, p.data_inserimento DESC 
            LIMIT 6
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProjectStats(): array {
        $stmt = $this->conn->prepare("
            WITH project_stats AS (
                SELECT 
                    p.id,
                    COALESCE(SUM(f.importo), 0) as funding,
                    p.budget_richiesto
                FROM progetti p
                LEFT JOIN finanziamenti f ON p.id = f.progetto_id
                WHERE p.stato IN ('aperto', 'chiuso')
                GROUP BY p.id, p.budget_richiesto
            )
            SELECT 
                COUNT(DISTINCT ps.id) as total_projects,
                SUM(ps.funding) as total_funding,
                COUNT(DISTINCT f.utente_id) as total_backers,
                ROUND(
                    (COUNT(CASE WHEN ps.funding >= ps.budget_richiesto THEN 1 END)::FLOAT / 
                    NULLIF(COUNT(*), 0) * 100
                ), 2) as success_rate
            FROM project_stats ps
            LEFT JOIN finanziamenti f ON ps.id = f.progetto_id
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_projects' => 0,
            'total_funding' => 0,
            'total_backers' => 0,
            'success_rate' => 0
        ];
    }
}

try {
    if (DependencyLoader::load('config/database.php', 'Database')) {
        $database = Database::getInstance();
        $conn = $database->getConnection();
        
        $projectLoader = new ProjectDataLoader($conn);
        $featured_projects = $projectLoader->getFeaturedProjects();
        $stats = $projectLoader->getProjectStats();
    }
} catch (Throwable $e) {
    error_log("Errore caricamento dati: " . $e->getMessage());
    $featured_projects = [];
    $stats = [
        'total_projects' => 0,
        'total_funding' => 0,
        'total_backers' => 0,
        'success_rate' => 0
    ];
}

// ===============================
// FUNZIONI HELPER MODERNE
// ===============================
final class ProjectHelper {
    public static function getDaysLeft(string $deadline): int {
        $now = new DateTimeImmutable();
        $end = new DateTimeImmutable($deadline);
        return (int) $now->diff($end)->format('%r%a');
    }
    
    public static function getDaysLeftText(string $deadline): string {
        $days = self::getDaysLeft($deadline);
        return match(true) {
            $days < 0 => "Scaduto",
            $days === 0 => "Ultimo giorno",
            $days === 1 => "1 giorno rimasto",
            default => "{$days} giorni rimasti"
        };
    }
    
    public static function truncateText(string $text, int $maxLength = 100): string {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        return mb_substr($text, 0, $maxLength - 3) . '...';
    }
    
    public static function formatCurrency(float $amount): string {
        return number_format($amount, 2, ',', '.') . ' €';
    }
    
    public static function calculateProgress(float $current, float $target): int {
        if ($target <= 0) return 0;
        return min(100, (int)(($current / $target) * 100));
    }
}

// ===============================
// LOGGING E MONITORAGGIO MODERNO
// ===============================
final class PageLogger {
    private MongoLogger $logger;
    
    public function __construct(MongoLogger $logger) {
        $this->logger = $logger;
    }
    
    public function logPageView(?int $userId): void {
        try {
            $this->logger->logEvent('homepage_view', [
                'user_id' => $userId,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                'timestamp' => (new DateTime())->format('Y-m-d H:i:s'),
                'session_id' => session_id(),
                'referrer' => $_SERVER['HTTP_REFERER'] ?? 'Direct',
                'platform' => $this->detectPlatform(),
            ]);
        } catch (Throwable $e) {
            error_log("Errore logging pagina: " . $e->getMessage());
        }
    }
    
    private function detectPlatform(): string {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return match(true) {
            str_contains($userAgent, 'Mobile') => 'mobile',
            str_contains($userAgent, 'Tablet') => 'tablet',
            default => 'desktop'
        };
    }
}

try {
    if (DependencyLoader::load('services/MongoLogger.php', 'MongoLogger')) {
        $pageLogger = new PageLogger(new MongoLogger());
        $pageLogger->logPageView($user_id);
    }
} catch (Throwable $e) {
    error_log("Errore inizializzazione logger: " . $e->getMessage());
}

// Inizializzazione ottimizzazioni
if (class_exists('PerformanceHelper')) {
    PerformanceHelper::initPageMetrics();
}

// Verifica che la classe CacheManager esista prima di usarla
$cachedStats = null;
if (class_exists('CacheManager')) {
    $cachedStats = CacheManager::get('homepage_stats');
}

// Se la cache non esiste o non è disponibile, carica i dati dal database
if (!$cachedStats) {
    if (class_exists('PerformanceHelper')) {
        PerformanceHelper::startMeasurement('stats_query');
    }
    try {
        if (DependencyLoader::load('config/database.php', 'Database')) {
            $database = Database::getInstance();
            $conn = $database->getConnection();
            
            $projectLoader = new ProjectDataLoader($conn);
            $featured_projects = $projectLoader->getFeaturedProjects();
            $stats = $projectLoader->getProjectStats();
        }
    } catch (Throwable $e) {
        error_log("Errore caricamento dati: " . $e->getMessage());
        $featured_projects = [];
        $stats = [
            'total_projects' => 0,
            'total_funding' => 0,
            'total_backers' => 0,
            'success_rate' => 0
        ];
    }
    if (class_exists('PerformanceHelper')) {
        PerformanceHelper::endMeasurement('stats_query');
    }
    if (class_exists('CacheManager')) {
        CacheManager::set('homepage_stats', $stats, 1800); // Cache per 30 minuti
    }
} else {
    $stats = $cachedStats;
}

// Cache dei progetti in evidenza
$cachedProjects = null;
if (class_exists('CacheManager')) {
    $cachedProjects = CacheManager::get('featured_projects');
}
if (!$cachedProjects) {
    if (class_exists('PerformanceHelper')) {
        PerformanceHelper::startMeasurement('projects_query');
    }
    try {
        if (DependencyLoader::load('config/database.php', 'Database')) {
            $database = Database::getInstance();
            $conn = $database->getConnection();
            
            $projectLoader = new ProjectDataLoader($conn);
            $featured_projects = $projectLoader->getFeaturedProjects();
            $stats = $projectLoader->getProjectStats();
        }
    } catch (Throwable $e) {
        error_log("Errore caricamento dati: " . $e->getMessage());
        $featured_projects = [];
        $stats = [
            'total_projects' => 0,
            'total_funding' => 0,
            'total_backers' => 0,
            'success_rate' => 0
        ];
    }
    if (class_exists('PerformanceHelper')) {
        PerformanceHelper::endMeasurement('projects_query');
    }
    if (class_exists('CacheManager')) {
        CacheManager::set('featured_projects', $featured_projects, 900); // Cache per 15 minuti
    }
} else {
    $featured_projects = $cachedProjects;
}

// Definizione CSS critico
$criticalCSS = "
    .modern-nav { 
        position: sticky;
        top: 0;
        z-index: 100;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(10px);
    }
    .hero-section {
        min-height: 60vh;
        display: flex;
        align-items: center;
    }
    .stats-card {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards;
    }
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
";
?>

<!-- =============================== -->
<!-- HTML: Homepage BOSTARTER -->
<!-- =============================== -->
<!DOCTYPE html>
<html lang="it" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSTARTER - Piattaforma di Crowdfunding Italiana</title>
    <meta name="description" content="BOSTARTER è la piattaforma italiana di crowdfunding per progetti innovativi in ambito tecnologico, artistico e creativo.">
    
    <!-- Favicon e PWA -->
    <link rel="icon" href="/BOSTARTER/frontend/images/favicon.ico" type="image/x-icon">
    <link rel="manifest" href="/BOSTARTER/frontend/manifest.json">
    <link rel="apple-touch-icon" href="/BOSTARTER/frontend/images/icon-144x144.png">
    <meta name="theme-color" content="#3176FF">
    
    <!-- Font e stili -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/BOSTARTER/frontend/css/main-styles.css" rel="stylesheet">
    
    <?php
    // CSS critico inline
    if (class_exists('ResourceOptimizer')) {
        ResourceOptimizer::inlineCriticalCSS($criticalCSS);
    }
    ?>
</head>
<body class="font-sans antialiased">
    <?php include __DIR__ . '/includes/homepage-content.php'; ?>
    
    <!-- Script -->
    <script src="/BOSTARTER/frontend/js/main.js" defer></script>
    
    <?php if (isset($script_nonce)): ?>
    <!-- Caricamento sicuro di script esterni -->
    <script nonce="<?php echo $script_nonce; ?>">
        // Funzione per caricare Chart.js in modo sicuro
        function loadChartJS() {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.defer = true;
            if (script.nonce) script.nonce = '<?php echo $script_nonce; ?>';
            document.head.appendChild(script);
        }
        // Carica Chart.js dopo il caricamento della pagina
        window.addEventListener('DOMContentLoaded', loadChartJS);
    </script>
    <?php endif; ?>
</body>
</html>
</html>
