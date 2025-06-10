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
    }    /**
     * Verifica se l'utente è loggato (richiede sessione avviata)
     * @return bool
     */
    public static function isLoggedIn() {
        // Assicuriamoci che la sessione sia iniziata
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Controllo di base per evitare loop di redirect
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }
        
        // Controllo aggiuntivo per timeout di sessione (opzionale)
        if (isset($_SESSION['login_time'])) {
            $sessionLifetime = 7200; // 2 ore in secondi
            if ((time() - $_SESSION['login_time']) > $sessionLifetime) {
                // Sessione scaduta, pulisci
                session_unset();
                session_destroy();
                return false;
            }
        }
        
        return true;
    }/**
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
