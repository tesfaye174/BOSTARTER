<?php
class NavigationHelper {
    public static function url($route, $params = []) {
        $routes = [
            'home' => '/BOSTARTER/frontend/index.php',
            'dashboard' => '/BOSTARTER/frontend/dashboard.php',
            'projects' => '/BOSTARTER/frontend/projects/view_projects.php',
            'create_project' => '/BOSTARTER/frontend/projects/create.php',
            'logout' => '/BOSTARTER/frontend/logout.php',
            'login' => '/BOSTARTER/frontend/auth/login.php',
            'register' => '/BOSTARTER/frontend/auth/register.php',
        ];
        $url = $routes[$route] ?? '/BOSTARTER/frontend/index.php';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }    
    public static function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }
        if (isset($_SESSION['login_time'])) {
            $sessionLifetime = 7200; 
            if ((time() - $_SESSION['login_time']) > $sessionLifetime) {
                session_unset();
                session_destroy();
                return false;
            }
        }
        return true;
    }
    public static function hasRole($role) {
        return isset($_SESSION['user']['tipo_utente']) && $_SESSION['user']['tipo_utente'] === $role;
    }
    public static function redirect($route, $params = []) {
        $url = self::url($route, $params);
        header("Location: $url");
        exit();
    }
}
