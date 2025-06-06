<?php
session_start();
require_once '../backend/config/database.php';
require_once '../backend/services/MongoLogger.php';
require_once '../backend/utils/NavigationHelper.php';

// Check if user is logged in using NavigationHelper
if (!NavigationHelper::isLoggedIn()) {
    NavigationHelper::redirect('login', ['redirect' => 'dashboard']);
}

$database = Database::getInstance();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

// Log dashboard access
$mongoLogger->logActivity($_SESSION['user_id'], 'dashboard_access', [
    'timestamp' => date('Y-m-d H:i:s')
]);

// Get user info
$user_query = "SELECT * FROM USERS WHERE user_id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(':user_id', $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get user's projects
$projects_query = "SELECT p.*, 
                          COALESCE(pf.total_funded, 0) as total_funded,
                          COALESCE(pf.funding_percentage, 0) as funding_percentage,
                          COALESCE(pf.backers_count, 0) as backers_count,
                          DATEDIFF(p.deadline, NOW()) as days_left
                   FROM PROJECTS p
                   LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
                   WHERE p.creator_id = :user_id
                   ORDER BY p.created_at DESC";
$projects_stmt = $db->prepare($projects_query);
$projects_stmt->bindParam(':user_id', $_SESSION['user_id']);
$projects_stmt->execute();
$user_projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's funding history
$fundings_query = "SELECT f.*, p.title as project_title, p.project_type,
                          u.username as creator_name, r.title as reward_title
                   FROM FUNDINGS f
                   JOIN PROJECTS p ON f.project_id = p.project_id
                   JOIN USERS u ON p.creator_id = u.user_id
                   LEFT JOIN REWARDS r ON f.reward_id = r.reward_id
                   WHERE f.user_id = :user_id
                   ORDER BY f.funding_date DESC
                   LIMIT 10";
$fundings_stmt = $db->prepare($fundings_query);
$fundings_stmt->bindParam(':user_id', $_SESSION['user_id']);
$fundings_stmt->execute();
$user_fundings = $fundings_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's applications (for software projects)
$applications_query = "SELECT c.*, p.title as project_title, s.skill_name,
                              u.username as creator_name
                       FROM CANDIDATURE c
                       JOIN PROJECTS p ON c.project_id = p.project_id
                       JOIN SKILLS s ON c.skill_id = s.skill_id
                       JOIN USERS u ON p.creator_id = u.user_id
                       WHERE c.user_id = :user_id
                       ORDER BY c.application_date DESC";
$applications_stmt = $db->prepare($applications_query);
$applications_stmt->bindParam(':user_id', $_SESSION['user_id']);
$applications_stmt->execute();
$user_applications = $applications_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activities
$activities_query = "SELECT 'project_created' as activity_type, p.title, p.created_at as activity_date, 
                            'You created a new project' as description
                     FROM PROJECTS p 
                     WHERE p.creator_id = :user_id
                     UNION ALL
                     SELECT 'funding_made' as activity_type, p.title, f.funding_date as activity_date,
                            CONCAT('You funded $', f.amount) as description
                     FROM FUNDINGS f
                     JOIN PROJECTS p ON f.project_id = p.project_id
                     WHERE f.user_id = :user_id
                     UNION ALL
                     SELECT 'application_made' as activity_type, p.title, c.application_date as activity_date,
                            CONCAT('You applied for ', s.skill_name) as description
                     FROM CANDIDATURE c
                     JOIN PROJECTS p ON c.project_id = p.project_id
                     JOIN SKILLS s ON c.skill_id = s.skill_id
                     WHERE c.user_id = :user_id
                     ORDER BY activity_date DESC
                     LIMIT 10";
$activities_stmt = $db->prepare($activities_query);
$activities_stmt->bindParam(':user_id', $_SESSION['user_id']);
$activities_stmt->execute();
$recent_activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate user stats
$total_created = count($user_projects);
$total_funded_amount = array_sum(array_column($user_fundings, 'amount'));
$total_applications = count($user_applications);
$successful_projects = count(array_filter($user_projects, function($p) { return $p['status'] === 'funded'; }));
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
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
        };
    </script>
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/main.css">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .stat-card:nth-child(2) { 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);
        }
        .stat-card:nth-child(3) { 
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); 
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }
        .stat-card:nth-child(4) { 
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); 
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.3);
        }
        .stat-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        .activity-item { 
            border-left: 3px solid #667eea; 
            background: rgba(102, 126, 234, 0.05);
            border-radius: 0 8px 8px 0;
        }
        .project-card { 
            transition: all 0.3s ease;
            border-radius: 15px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        .project-card:hover { 
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        .dashboard-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #667eea;
            color: white;
            padding: 8px;
            z-index: 100;
            transition: top 0.2s;
            text-decoration: none;
            border-radius: 4px;
        }
        .skip-link:focus {
            top: 6px;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        .progress-bar-custom {
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 10px;
        }
        .focus-visible {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
        .dropdown {
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }
        .dropdown.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Skip link -->
    <a href="#main-content" class="skip-link" tabindex="0">Go to main content</a>

    <!-- Notifications container -->
    <div id="notifications-container" class="fixed top-4 right-4 z-50 space-y-2 max-w-sm" role="alert" aria-live="polite"></div>

    <!-- Modern Header -->
    <header class="bg-white/80 dark:bg-gray-800/80 shadow-sm sticky top-0 z-50 transition-colors duration-300 backdrop-blur-sm">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="index.php" class="flex items-center font-brand text-2xl text-primary hover:text-primary-600 transition-colors focus-visible" aria-label="BOSTARTER Homepage">
                    <i class="fas fa-rocket mr-2 text-primary" aria-hidden="true"></i>
                    BOSTARTER
                </a>

                <!-- Navigation -->
                <nav class="hidden lg:flex items-center space-x-6" role="navigation" aria-label="Main navigation">
                    <a href="dashboard.php" class="flex items-center text-primary font-medium transition-colors hover:text-primary-600 focus-visible">
                        <i class="fas fa-tachometer-alt mr-2" aria-hidden="true"></i>
                        Dashboard
                    </a>
                    <a href="projects/list_open.php" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-primary transition-colors focus-visible">
                        <i class="fas fa-list mr-2" aria-hidden="true"></i>
                        Browse Projects
                    </a>
                    <a href="projects/create.php" class="flex items-center text-gray-600 dark:text-gray-300 hover:text-primary transition-colors focus-visible">
                        <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                        Create Project
                    </a>
                    <div class="relative group">
                        <button class="flex items-center text-gray-600 dark:text-gray-300 hover:text-primary transition-colors focus-visible">
                            <i class="fas fa-chart-bar mr-2" aria-hidden="true"></i>
                            Statistics
                            <i class="fas fa-chevron-down ml-1 text-xs" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown absolute top-full left-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <a href="stats/top_creators.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Top Creators</a>
                            <a href="stats/close_to_goal.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Close to Goal</a>
                        </div>
                    </div>
                    <?php if ($user['user_type'] === 'admin'): ?>
                        <div class="relative group">
                            <button class="flex items-center text-gray-600 dark:text-gray-300 hover:text-primary transition-colors focus-visible">
                                <i class="fas fa-cog mr-2" aria-hidden="true"></i>
                                Admin
                                <i class="fas fa-chevron-down ml-1 text-xs" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown absolute top-full left-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <a href="admin/add_skill.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Manage Skills</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </nav>

                <!-- User Actions -->
                <div class="flex gap-3 items-center">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" 
                            class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus-visible" 
                            aria-label="Toggle theme">
                        <i class="ri-sun-line dark:hidden text-xl" aria-hidden="true"></i>
                        <i class="ri-moon-line hidden dark:block text-xl" aria-hidden="true"></i>
                    </button>

                    <!-- User Menu -->
                    <div class="relative group">
                        <button class="flex items-center gap-2 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus-visible"
                                aria-label="User menu">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=667eea&color=fff" 
                                 alt="" class="w-8 h-8 rounded-full" aria-hidden="true">
                            <span class="text-gray-700 dark:text-gray-300 hidden sm:block"><?php echo htmlspecialchars($user['username']); ?></span>
                            <i class="ri-arrow-down-s-line text-sm" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200"
                             role="menu">                            <button onclick="document.getElementById('profileModal').style.display='block'" 
                                    class="block w-full text-left px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" 
                                    role="menuitem">
                                <i class="ri-user-line mr-2" aria-hidden="true"></i>
                                Profile
                            </button>
                            <button onclick="document.getElementById('settingsModal').style.display='block'" 
                                    class="block w-full text-left px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" 
                                    role="menuitem">
                                <i class="ri-settings-line mr-2" aria-hidden="true"></i>
                                Settings
                            </button>
                            <hr class="my-1 border-gray-200 dark:border-gray-700">
                            <a href="auth/logout.php" 
                               class="block px-4 py-2 text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" 
                               role="menuitem">
                                <i class="ri-logout-box-line mr-2" aria-hidden="true"></i>
                                Logout
                            </a>
                        </div>
                    </div>

                    <!-- Mobile Menu Toggle -->
                    <button id="mobile-menu-toggle" class="lg:hidden p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus-visible">
                        <i class="fas fa-bars text-xl" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <nav id="mobile-menu" class="lg:hidden hidden mt-4 pb-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="space-y-2">
                    <a href="dashboard.php" class="flex items-center px-3 py-2 text-primary font-medium rounded-lg bg-primary/10">
                        <i class="fas fa-tachometer-alt mr-3" aria-hidden="true"></i>
                        Dashboard
                    </a>
                    <a href="projects/list_open.php" class="flex items-center px-3 py-2 text-gray-600 dark:text-gray-300 hover:text-primary hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <i class="fas fa-list mr-3" aria-hidden="true"></i>
                        Browse Projects
                    </a>
                    <a href="projects/create.php" class="flex items-center px-3 py-2 text-gray-600 dark:text-gray-300 hover:text-primary hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <i class="fas fa-plus mr-3" aria-hidden="true"></i>
                        Create Project
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content" class="container mx-auto px-4 py-8">        <!-- Hero Section -->        <section class="mb-8 animate-fade-in">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex justify-between items-center mb-2">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Welcome back, <?php echo htmlspecialchars($user['username']); ?>! 👋
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
                <div class="dashboard-card p-6">
                    <div class="flex justify-content-between items-center mb-6">
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
                            <?php foreach (array_slice($user_projects, 0, 4) as $project): ?>
                                <div class="project-card border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="font-medium text-gray-900 dark:text-white line-clamp-1"><?php echo htmlspecialchars($project['title']); ?></h3>
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            <?php echo $project['status'] === 'open' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                     ($project['status'] === 'funded' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                      'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'); ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3"><?php echo ucfirst($project['project_type']); ?> Project</p>
                                    
                                    <div class="mb-3">
                                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                            <span>$<?php echo number_format($project['total_funded'], 0); ?></span>
                                            <span><?php echo round($project['funding_percentage'], 1); ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="progress-bar-custom h-2 rounded-full transition-all duration-500" 
                                                 style="width: <?php echo min(100, $project['funding_percentage']); ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-2">
                                        <a href="projects/detail.php?id=<?php echo $project['project_id']; ?>" 
                                           class="flex-1 text-center px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                            View Details
                                        </a>
                                        <?php if ($project['status'] === 'open'): ?>
                                            <a href="projects/add_reward.php?id=<?php echo $project['project_id']; ?>" 
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
                        <div class="space-y-4">
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item p-4 rounded-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-medium text-gray-900 dark:text-white mb-1"><?php echo htmlspecialchars($activity['title']); ?></h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('M j', strtotime($activity['activity_date'])); ?></span>
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
                        <div class="space-y-3">
                            <?php foreach (array_slice($user_fundings, 0, 3) as $funding): ?>
                                <div class="flex justify-between items-center">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($funding['project_title']); ?></h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">by <?php echo htmlspecialchars($funding['creator_name']); ?></p>
                                    </div>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 text-xs rounded-full">
                                        $<?php echo number_format($funding['amount'], 0); ?>
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
                            <?php foreach (array_slice($user_applications, 0, 3) as $application): ?>
                                <div class="flex justify-between items-center">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($application['project_title']); ?></h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($application['skill_name']); ?></p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $application['status'] === 'accepted' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                 ($application['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                                  'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'); ?>">
                                        <?php echo ucfirst($application['status']); ?>
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
                    <div class="flex items-center justify-center mb-4">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&size=80&background=667eea&color=fff" 
                             alt="Profile Avatar" class="w-20 h-20 rounded-full">
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Username:</span>
                            <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Email:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">User Type:</span>
                                <span class="px-2 py-1 bg-primary text-white text-sm rounded-full">
                                    <?php echo ucfirst($user['user_type']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Member Since:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
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
    </div>
    
    <style>
        .toggle-switch {
            appearance: none;
            width: 44px;
            height: 24px;
            background: #cbd5e0;
            border-radius: 12px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .toggle-switch:checked {
            background: #667eea;
        }
        
        .toggle-switch:before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
        }
          .toggle-switch:checked:before {
            transform: translateX(20px);
        }
        
        /* Compact view styles */
        .compact-view .stat-card {
            padding: 1rem;
        }
        
        .compact-view .stat-card h3 {
            font-size: 1.5rem;
        }
        
        .compact-view .project-card {
            padding: 1rem;
        }
        
        .compact-view .activity-item {
            padding: 0.75rem;
        }
        
        /* Animation disable styles */
        .disable-animations * {
            animation-duration: 0s !important;
            transition-duration: 0s !important;
        }
    </style>

    <!-- Core JavaScript -->
    <script src="/frontend/js/utils.js"></script>
    <script src="/frontend/js/theme.js"></script>
    <script src="/frontend/js/notifications.js"></script>
    
    <!-- Dashboard Features -->
    <script src="/frontend/js/projects.js"></script>
    <script src="/frontend/js/modal-accessibility.js"></script>
    
    <!-- Chart Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <!-- Dashboard Core -->
    <script src="/frontend/js/dashboard.js" type="module"></script>
    
    <!-- Performance Monitoring -->
    <script src="/frontend/js/performance.js" async></script>
    
    <script>
        // Initialize dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initDashboard();
        });
        
        function initDashboard() {
            // Setup theme
            const themeToggle = document.getElementById('theme-toggle');
            const html = document.documentElement;
            const currentTheme = localStorage.getItem('theme') || 'light';
            html.classList.toggle('dark', currentTheme === 'dark');
            
            // Setup mobile menu
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            if (mobileMenuToggle && mobileMenu) {
                mobileMenuToggle.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                });
            }
            
            // Initialize notifications
            initNotifications();
            
            // Start performance monitoring
            if (window.PerformanceObserver) {
                initPerformanceMonitoring();
            }
        }
        
        // Cleanup function for SPA navigation
        function cleanup() {
            // Cleanup chart instances
            Chart.helpers.each(Chart.instances, (instance) => {
                instance.destroy();
            });
            
            // Clear any intervals/timeouts
            clearInterval(autoRefreshInterval);
        }
        
        // Export for module usage
        window.dashboardUtils = {
            initDashboard,
            cleanup
        };
    </script>
</body>
</html>
