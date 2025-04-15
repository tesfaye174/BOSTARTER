<?php
namespace Server;

use Config\Logger;

class Router {
    private static $instance = null;
    private $routes = [];
    private $middlewares = [];
    private $globalMiddlewares = [];
    private $errorHandlers = [];
    private $rateLimits = [];
    private $csrfEnabled = true;
    
    private function __construct() {}
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function addRoute(string $method, string $path, callable $handler, array $middlewares = []): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }
    
    public function addMiddleware(string $path, callable $middleware): void {
        $this->middlewares[$path][] = $middleware;
    }
    
    public function addGlobalMiddleware(callable $middleware): void {
        $this->globalMiddlewares[] = $middleware;
    }
    
    public function addErrorHandler(int $code, callable $handler): void {
        $this->errorHandlers[$code] = $handler;
    }
    
    public function setRateLimit(string $path, int $limit, int $window): void {
        $this->rateLimits[$path] = ['limit' => $limit, 'window' => $window];
    }
    
    public function disableCsrf(): void {
        $this->csrfEnabled = false;
    }
    
    private function validateCsrf(): bool {
        if (!$this->csrfEnabled) return true;
        
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$token || !isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new \Exception('Invalid CSRF token', 403);
        }
        return true;
    }
    
    private function checkRateLimit(string $path): void {
        if (!isset($this->rateLimits[$path])) return;
        
        $limit = $this->rateLimits[$path];
        $key = 'rate_limit:' . $_SERVER['REMOTE_ADDR'] . ':' . $path;
        
        // Implement rate limiting logic here
        // This is a placeholder - you would typically use Redis or similar for production
        if (isset($_SESSION[$key]) && $_SESSION[$key]['count'] >= $limit['limit']) {
            if (time() - $_SESSION[$key]['start'] < $limit['window']) {
                throw new \Exception('Rate limit exceeded', 429);
            }
            $_SESSION[$key]['count'] = 0;
            $_SESSION[$key]['start'] = time();
        }
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'start' => time()];
        }
        $_SESSION[$key]['count']++;
    }
    
    public function matchRoute(string $method, string $path): ?array {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->convertPathToRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove full match
                return [
                    'handler' => $route['handler'],
                    'params' => $matches,
                    'middlewares' => array_merge(
                        $this->globalMiddlewares,
                        $route['middlewares'],
                        $this->getMiddlewaresForPath($path)
                    ),
                    'path' => $route['path']
                ];
            }
        }
        return null;
    }
    
    private function convertPathToRegex(string $path): string {
        return '#^' . preg_replace('/\{([^}]+)\}/', '([^/]+)', $path) . '$#';
    }
    
    private function getMiddlewaresForPath(string $path): array {
        $middlewares = [];
        foreach ($this->middlewares as $pattern => $handlers) {
            if (strpos($path, $pattern) === 0) {
                $middlewares = array_merge($middlewares, $handlers);
            }
        }
        return $middlewares;
    }
    
    public function handleRequest(): void {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            // Validate CSRF token for non-GET requests
            if ($method !== 'GET') {
                $this->validateCsrf();
            }
            
            $route = $this->matchRoute($method, $path);
            if (!$route) {
                throw new \Exception('Route not found', 404);
            }
            
            // Check rate limits
            $this->checkRateLimit($route['path']);
            
            // Execute middlewares
            foreach ($route['middlewares'] as $middleware) {
                $middleware();
            }
            
            // Sanitize input
            $_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING) ?: [];
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING) ?: [];
            
            // Execute route handler
            $response = $route['handler'](...$route['params']);
            
            // Send response
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            $this->handleError($code, $e);
            
            // Log error
            if (class_exists('Config\Logger')) {
                Logger::getInstance()->log('router_error', [
                    'message' => $e->getMessage(),
                    'code' => $code,
                    'path' => $_SERVER['REQUEST_URI'],
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
    
    private function handleError(int $code, \Exception $e): void {
        if (isset($this->errorHandlers[$code])) {
            $this->errorHandlers[$code]($e);
            return;
        }
        
        http_response_code($code);
        echo json_encode([
            'error' => $e->getMessage(),
            'code' => $code
        ]);
    }
}
?>