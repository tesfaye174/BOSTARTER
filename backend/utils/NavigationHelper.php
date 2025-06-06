<?php
/**
 * Navigation Helper - Sistema di navigazione centralizzato
 * Gestisce tutti i collegamenti e la navigazione del sito
 */

class NavigationHelper {
    private static $routes = [
        // Authentication routes
        'login' => '/frontend/auth/login.php',
        'register' => '/frontend/auth/register.php',
        'logout' => '/frontend/auth/logout.php',
        
        // Main pages
        'home' => '/frontend/index.php',
        'dashboard' => '/frontend/dashboard_compliant.php',
        'about' => '/frontend/about.php',
        
        // Projects
        'projects' => '/frontend/projects/view_projects.php',
        'create_project' => '/frontend/projects/create_project.php',
        'project_detail' => '/frontend/projects/project_details.php',
        
        // Categories - Conforme al PDF (solo hardware e software)
        'hardware_projects' => '/frontend/projects/view_projects.php?category=hardware',
        'software_projects' => '/frontend/projects/view_projects.php?category=software',
        
        // Statistics and Analysis
        'stats' => '/frontend/stats/dashboard.php',
        'volume_analysis' => '/frontend/stats/volume_analysis.php',
        'top_creators' => '/frontend/stats/top_creators.php',
        'close_to_goal' => '/frontend/stats/close_to_goal.php',
        
        // User management
        'profile' => '/frontend/user/profile.php',
        'user_settings' => '/frontend/user/settings.php',
        'user_projects' => '/frontend/user/my_projects.php',
        
        // Admin
        'admin_dashboard' => '/frontend/admin/dashboard.php',
        'admin_users' => '/frontend/admin/users.php',
        'admin_projects' => '/frontend/admin/projects.php',
        'mongodb_monitor' => '/frontend/admin/mongodb_monitor.php',
    ];
    
    /**
     * Get URL for a route
     */
    public static function url($route, $params = []) {
        $base_url = self::getBaseUrl();
        
        if (!isset(self::$routes[$route])) {
            error_log("Route not found: $route");
            return $base_url . '/frontend/index.php';
        }
        
        $url = $base_url . self::$routes[$route];
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Get base URL
     */
    private static function getBaseUrl() {
        return defined('APP_URL') ? APP_URL : 'http://localhost/BOSTARTER';
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        return self::isLoggedIn() && 
               isset($_SESSION['user']['tipo_utente']) && 
               $_SESSION['user']['tipo_utente'] === $role;
    }
    
    /**
     * Redirect to route
     */
    public static function redirect($route, $params = []) {
        header('Location: ' . self::url($route, $params));
        exit;
    }
    
    /**
     * Get navigation menu items
     */
    public static function getNavigationItems() {
        $items = [
            [
                'label' => 'Hardware',
                'url' => self::url('hardware_projects'),
                'icon' => 'fas fa-microchip',
                'description' => 'Progetti di elettronica e dispositivi fisici'
            ],
            [
                'label' => 'Software',
                'url' => self::url('software_projects'),
                'icon' => 'fas fa-code',
                'description' => 'Applicazioni e soluzioni digitali'
            ],
            [
                'label' => 'Tutti i Progetti',
                'url' => self::url('projects'),
                'icon' => 'fas fa-list',
                'description' => 'Esplora tutti i progetti disponibili'
            ],
            [
                'label' => 'Chi Siamo',
                'url' => self::url('about'),
                'icon' => 'fas fa-info-circle',
                'description' => 'Scopri di più su BOSTARTER'
            ]
        ];
        
        // Add statistics for logged users
        if (self::isLoggedIn()) {
            $items[] = [
                'label' => 'Statistiche',
                'url' => self::url('stats'),
                'icon' => 'fas fa-chart-bar',
                'description' => 'Analisi e statistiche della piattaforma'
            ];
        }
        
        return $items;
    }
    
    /**
     * Get user menu items
     */
    public static function getUserMenuItems() {
        if (!self::isLoggedIn()) {
            return [
                [
                    'label' => 'Accedi',
                    'url' => self::url('login'),
                    'icon' => 'fas fa-sign-in-alt',
                    'class' => 'btn-outline'
                ],
                [
                    'label' => 'Registrati',
                    'url' => self::url('register'),
                    'icon' => 'fas fa-user-plus',
                    'class' => 'btn-primary'
                ]
            ];
        }
        
        $items = [
            [
                'label' => 'Dashboard',
                'url' => self::url('dashboard'),
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'label' => 'I Miei Progetti',
                'url' => self::url('user_projects'),
                'icon' => 'fas fa-folder'
            ],
            [
                'label' => 'Profilo',
                'url' => self::url('profile'),
                'icon' => 'fas fa-user'
            ]
        ];
        
        // Add admin items if user is admin
        if (self::hasRole('amministratore')) {
            $items[] = [
                'label' => 'Amministrazione',
                'url' => self::url('admin_dashboard'),
                'icon' => 'fas fa-cog'
            ];
        }
        
        // Add creator items if user can create projects
        if (self::hasRole('creatore') || self::hasRole('amministratore')) {
            array_unshift($items, [
                'label' => 'Crea Progetto',
                'url' => self::url('create_project'),
                'icon' => 'fas fa-plus-circle',
                'class' => 'btn-primary'
            ]);
        }
        
        $items[] = [
            'label' => 'Esci',
            'url' => self::url('logout'),
            'icon' => 'fas fa-sign-out-alt'
        ];
        
        return $items;
    }
    
    /**
     * Get breadcrumb navigation
     */
    public static function getBreadcrumb($current_page, $custom_items = []) {
        $breadcrumb = [
            [
                'label' => 'Home',
                'url' => self::url('home'),
                'active' => false
            ]
        ];
        
        // Add custom items
        foreach ($custom_items as $item) {
            $breadcrumb[] = [
                'label' => $item['label'],
                'url' => $item['url'] ?? null,
                'active' => false
            ];
        }
        
        // Add current page
        $breadcrumb[] = [
            'label' => $current_page,
            'url' => null,
            'active' => true
        ];
        
        return $breadcrumb;
    }
}
?>
