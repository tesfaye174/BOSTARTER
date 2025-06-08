<?php
/**
 * NavigationHelper - Helper per la generazione di URL e la gestione della navigazione
 * Utilizzato dal frontend per generare link e verificare lo stato di login/ruolo utente
 */
class NavigationHelper {
    /**
     * Genera un URL per una determinata route del frontend
     * @param string $route Nome della route
     * @param array $params Parametri aggiuntivi
     * @return string URL generato
     */
    public static function url($route, $params = []) {
        $routes = [
            // Rotte principali
            'home' => '/BOSTARTER/frontend/index.php',
            'dashboard' => '/BOSTARTER/frontend/dashboard.php',
            'projects' => '/BOSTARTER/frontend/projects/view_projects.php',
            'create_project' => '/BOSTARTER/frontend/projects/create.php',
            'logout' => '/BOSTARTER/frontend/logout.php',
            'login' => '/BOSTARTER/frontend/auth/login.php',
            'register' => '/BOSTARTER/frontend/auth/register.php',
            // ...aggiungi altre rotte se necessario...
        ];
        $url = $routes[$route] ?? '/BOSTARTER/frontend/index.php';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    /**
     * Verifica se l'utente è loggato (richiede sessione avviata)
     * @return bool
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }    /**
     * Verifica se l'utente ha un determinato ruolo
     * @param string $role
     * @return bool
     */
    public static function hasRole($role) {
        return isset($_SESSION['user']['tipo_utente']) && $_SESSION['user']['tipo_utente'] === $role;
    }

    /**
     * Reindirizza l'utente a una determinata route
     * @param string $route Nome della route
     * @param array $params Parametri aggiuntivi
     * @return void
     */
    public static function redirect($route, $params = []) {
        $url = self::url($route, $params);
        header("Location: $url");
        exit();
    }
}
