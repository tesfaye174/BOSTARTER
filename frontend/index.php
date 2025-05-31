<?php
session_start();
require_once '../backend/config/database.php';
require_once '../backend/services/MongoLogger.php';

$database = new Database();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

// Log page visit
if (isset($_SESSION['user_id'])) {
    $mongoLogger->logActivity($_SESSION['user_id'], 'homepage_visit', [
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    $mongoLogger->logSystem('anonymous_homepage_visit', [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

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
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 100px 0;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .btn-hero {
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 0 10px 10px 0;
        }
        
        .btn-primary-hero {
            background: white;
            color: var(--primary-color);
            border: 2px solid white;
        }
        
        .btn-primary-hero:hover {
            background: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .btn-outline-hero {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline-hero:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .stats-section {
            background: #f8f9fa;
            padding: 80px 0;
        }
        
        .stat-card {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 30px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
            color: #2c3e50;
        }
        
        .project-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            height: 100%;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .project-image {
            height: 200px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            position: relative;
        }
        
        .project-type-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255,255,255,0.9);
            color: var(--primary-color);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .project-card-body {
            padding: 25px;
        }
        
        .project-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .project-creator {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .funding-progress {
            margin-bottom: 20px;
        }
        
        .progress {
            height: 8px;
            border-radius: 10px;
        }
        
        .progress-bar {
            border-radius: 10px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .funding-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .funding-amount {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .days-left {
            color: #6c757d;
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--accent-color) 0%, #f5576c 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .feature-section {
            padding: 80px 0;
        }
        
        .feature-icon {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }
        
        .feature-card {
            text-align: center;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .navbar {
            padding: 1rem 0;
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-link {
            font-weight: 500;
            margin: 0 10px;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .btn-nav {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        footer {
            background: #2c3e50;
            color: white;
            padding: 50px 0 30px 0;
        }
        
        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-rocket text-primary me-2"></i>
                BOSTARTER
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="projects/list_open.php">Browse Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stats/top_creators.php">Top Creators</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="projects/create.php">
                                <i class="fas fa-plus me-1"></i>
                                Start Project
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-nav text-white" href="auth/register.php">Get Started</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">
                        Bring Your <span style="color: #f093fb;">Ideas</span> to Life
                    </h1>
                    <p class="hero-subtitle">
                        The premier crowdfunding platform for innovative hardware and software projects. 
                        Connect with supporters who believe in your vision and make it happen.
                    </p>
                    
                    <?php if (!$isLoggedIn): ?>
                    <div class="mt-4">
                        <a href="auth/register.php" class="btn-hero btn-primary-hero">
                            <i class="fas fa-rocket me-2"></i>
                            Start Your Project
                        </a>
                        <a href="projects/list_open.php" class="btn-hero btn-outline-hero">
                            <i class="fas fa-search me-2"></i>
                            Explore Projects
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="mt-4">
                        <a href="projects/create.php" class="btn-hero btn-primary-hero">
                            <i class="fas fa-plus me-2"></i>
                            Create Project
                        </a>
                        <a href="dashboard.php" class="btn-hero btn-outline-hero">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Go to Dashboard
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6 text-center">
                    <div style="font-size: 15rem; opacity: 0.1;">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Platform Statistics -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['total_projects']); ?></div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['active_projects']); ?></div>
                        <div class="stat-label">Active Projects</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">Community Members</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number">€<?php echo number_format($stats['total_funded'], 0); ?></div>
                        <div class="stat-label">Total Funded</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Projects -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Featured Projects</h2>
            <div class="row">
                <?php if (empty($featured_projects)): ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No projects available yet. Be the first to create one!</p>
                        <?php if ($isLoggedIn): ?>
                            <a href="projects/create.php" class="btn btn-primary btn-lg mt-3">
                                <i class="fas fa-plus me-2"></i>Create First Project
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($featured_projects as $project): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="project-card">
                                <div class="project-image">
                                    <div class="project-type-badge">
                                        <?php echo ucfirst(htmlspecialchars($project['project_type'])); ?>
                                    </div>
                                    <i class="<?php echo $project['project_type'] == 'hardware' ? 'fas fa-microchip' : 'fas fa-code'; ?>"></i>
                                </div>
                                <div class="project-card-body">
                                    <h5 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                    <p class="project-creator">
                                        <i class="fas fa-user me-1"></i>
                                        by <?php echo htmlspecialchars($project['creator_name']); ?>
                                    </p>
                                    <p class="text-muted mb-3">
                                        <?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>...
                                    </p>
                                    
                                    <div class="funding-progress">
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?php echo min(100, $project['funding_percentage']); ?>%"></div>
                                        </div>
                                        <div class="funding-stats">
                                            <span class="funding-amount">€<?php echo number_format($project['total_funded'], 0); ?></span>
                                            <span class="days-left">
                                                <?php 
                                                if ($project['days_left'] > 0) {
                                                    echo $project['days_left'] . ' days left';
                                                } else {
                                                    echo 'Ended';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <a href="projects/detail.php?id=<?php echo $project['project_id']; ?>" class="btn btn-outline-primary w-100">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="projects/list_open.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>
                    Browse All Projects
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="feature-section bg-light">
        <div class="container">
            <h2 class="section-title">How BOSTARTER Works</h2>
            <div class="row">
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h4>1. Share Your Idea</h4>
                        <p>Create a compelling project page with your vision, goals, and rewards for supporters.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>2. Build Community</h4>
                        <p>Connect with backers who share your passion and want to see your project succeed.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h4>3. Launch & Deliver</h4>
                        <p>Reach your funding goal and bring your innovative hardware or software project to life.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mb-4">Ready to Launch Your Project?</h2>
                    <p class="mb-4 fs-5">
                        Join thousands of creators who have successfully funded their innovative projects on BOSTARTER.
                    </p>
                    <?php if (!$isLoggedIn): ?>
                    <a href="auth/register.php" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-user-plus me-2"></i>
                        Sign Up Now
                    </a>
                    <a href="projects/list_open.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-search me-2"></i>
                        Explore Projects
                    </a>
                    <?php else: ?>
                    <a href="projects/create.php" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-plus me-2"></i>
                        Start Your Project
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        View Dashboard
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h5 class="mb-3">BOSTARTER</h5>
                    <p>The premier platform for funding innovative hardware and software projects. Turning ideas into reality through community support.</p>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-3">Platform</h6>
                    <div class="footer-links">
                        <a href="projects/list_open.php" class="d-block mb-2">Browse Projects</a>
                        <a href="projects/create.php" class="d-block mb-2">Start Project</a>
                        <a href="stats/top_creators.php" class="d-block mb-2">Top Creators</a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-3">Support</h6>
                    <div class="footer-links">
                        <a href="#" class="d-block mb-2">Help Center</a>
                        <a href="#" class="d-block mb-2">Guidelines</a>
                        <a href="#" class="d-block mb-2">Contact Us</a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-3">Company</h6>
                    <div class="footer-links">
                        <a href="#" class="d-block mb-2">About Us</a>
                        <a href="#" class="d-block mb-2">Privacy Policy</a>
                        <a href="#" class="d-block mb-2">Terms of Service</a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-3">Connect</h6>
                    <div>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 BOSTARTER. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Made with <i class="fas fa-heart text-danger"></i> for innovators</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
