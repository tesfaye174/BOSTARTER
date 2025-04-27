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
     * Gestisce la richiesta corrente.
     */
    public function handleRequest(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

        // Rimuovi query string se presente
        $path = strtok($path, '?');

        // Trova la rotta corrispondente
        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];

            if (is_callable($handler)) {
                // Se è una funzione anonima
                call_user_func($handler);
            } elseif (is_array($handler) && count($handler) === 2) {
                // Se è nel formato [ClasseController, 'metodo']
                $controllerClass = $handler[0];
                $controllerMethod = $handler[1];

                // Verifica che la classe e il metodo esistano
                if (class_exists($controllerClass) && method_exists($controllerClass, $controllerMethod)) {
                    $controllerInstance = new $controllerClass();
                    // Chiama il metodo del controller
                    // In futuro, potresti passare parametri estratti dall'URL o dal corpo della richiesta
                    call_user_func([$controllerInstance, $controllerMethod]);
                } else {
                    $this->sendNotFound("Controller o metodo non trovato: {$controllerClass}::{$controllerMethod}");
                }
            } else {
                $this->sendNotFound("Handler non valido per la rotta: {$path}");
            }
        } else {
            $this->sendNotFound("Nessuna rotta trovata per {$method} {$path}");
        }
    }

    /**
     * Invia una risposta 404 Not Found.
     *
     * @param string $message Messaggio di errore opzionale.
     */
    private function sendNotFound(string $message = 'Risorsa non trovata'): void {
        http_response_code(404);
        // In un'app reale, avresti una pagina 404 dedicata
        echo json_encode(['error' => $message]);
        exit;
    }

    /**
     * Invia una risposta JSON.
     *
     * @param mixed $data Dati da inviare.
     * @param int $statusCode Codice di stato HTTP (default 200).
     */
    public static function jsonResponse($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}

?>