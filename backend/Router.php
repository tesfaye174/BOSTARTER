<?php

namespace BOSTARTER\Backend;

// Semplice router basato su URL
// In un'applicazione reale, si userebbe una libreria di routing più robusta (es. FastRoute, Symfony Routing)

class Router {
    private $routes = [];
    private $basePath = '/BOSTARTER'; // Imposta il percorso base della tua applicazione

    /**
     * Aggiunge una rotta.
     *
     * @param string $method Metodo HTTP (GET, POST, etc.)
     * @param string $path Percorso URL (es. '/users')
     * @param callable|array $handler Funzione o [ClasseController, 'metodo']
     */
    public function addRoute(string $method, string $path, $handler): void {
        $this->routes[strtoupper($method)][$this->basePath . $path] = $handler;
    }

    /**
     * Utility method for sending JSON responses.
     * Controllers can use this method.
     */
    public static function jsonResponse($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        // Ensure header is set, though index.php might set it globally
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data);
        exit; // Terminate script execution after sending the response
    }

    /**
     * Gestisce la richiesta corrente.
     */
    public function handleRequest(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

        // Rimuovi query string se presente
        $requestPath = strtok($requestPath, '?');

        $matchedHandler = null;
        $params = [];

        if (isset($this->routes[strtoupper($requestMethod)])) {
            foreach ($this->routes[strtoupper($requestMethod)] as $routePattern => $handler) {
                // Convert route pattern with placeholders like {paramName} to a regex
                // Example: /BOSTARTER/api/projects/{projectId}/rewards -> #^/BOSTARTER/api/projects/([^/]+)/rewards$#
                $regexPattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routePattern);
                $regexPattern = '#^' . $regexPattern . '$#';

                if (preg_match($regexPattern, $requestPath, $matches)) {
                    array_shift($matches); // Remove the full match (index 0)
                    
                    // Extract parameter names from the route pattern
                    // Example: /BOSTARTER/api/projects/{projectId}/rewards -> ['projectId']
                    preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $routePattern, $paramNames);
                    $paramNames = $paramNames[1]; // Get the captured group names

                    if (count($paramNames) === count($matches)) {
                        $params = array_combine($paramNames, $matches);
                    } else {
                        // Fallback if param names extraction doesn't align, or for simple numeric array
                        // This case might need adjustment based on how you want to handle unnamed params
                        $params = $matches; 
                    }
                    
                    $matchedHandler = $handler;
                    break; // First matched route wins
                }
            }
        }

        if ($matchedHandler) {
            if (is_callable($matchedHandler)) {
                // For anonymous functions or invokable objects
                call_user_func_array($matchedHandler, $params);
            } elseif (is_array($matchedHandler) && count($matchedHandler) === 2) {
                // For [ControllerClass, 'methodName']
                $controllerClass = $matchedHandler[0];
                $controllerMethod = $matchedHandler[1];

                if (class_exists($controllerClass) && method_exists($controllerClass, $controllerMethod)) {
                    $controllerInstance = new $controllerClass();
                    // Pass extracted params as a single associative array to the controller method
                    // Controller methods should expect an array, e.g., public function myMethod(array $urlParams)
                    call_user_func_array([$controllerInstance, $controllerMethod], [$params]);
                } else {
                    $this->sendNotFound("Controller or method not found: {$controllerClass}::{$controllerMethod}");
                }
            } else {
                $this->sendNotFound("Invalid handler for route: {$requestPath}");
            }
        } else {
            $this->sendNotFound("No route found for {$requestMethod} {$requestPath}");
        }
    }

    /**
     * Invia una risposta 404 Not Found.
     *
     * @param string $message Messaggio di errore opzionale.
     */
    private function sendNotFound(string $message = 'Resource not found'): void {
        // Use the static jsonResponse method for consistency
        self::jsonResponse(['error' => $message], 404);
    }
}

?>