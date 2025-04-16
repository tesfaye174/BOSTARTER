<?php
declare(strict_types=1);

namespace Server;

use Config\Logger;
use Utils\Response;
use Throwable;
use Exception;
use RuntimeException;
use InvalidArgumentException;

class Router {
    private static ?self $instance = null;
    private array $routes = [];
    private array $middlewares = [];
    private array $globalMiddlewares = [];
    private array $errorHandlers = [];
    private array $rateLimits = []; // Placeholder for rate limiting config
    private bool $csrfEnabled = true; // CSRF enabled by default

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Adds a route to the router.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path Route path (e.g., /users/{id})
     * @param callable $handler The function or [class, method] to execute
     * @param array $middlewares Route-specific middleware callables
     */
    public function addRoute(string $method, string $path, callable $handler, array $middlewares = []): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Adds a middleware that applies to a specific path prefix.
     *
     * @param string $pathPrefix The path prefix (e.g., /api)
     * @param callable $middleware The middleware callable
     */
    public function addMiddleware(string $pathPrefix, callable $middleware): void {
        $this->middlewares[$this->normalizePath($pathPrefix)][] = $middleware;
    }

    /**
     * Adds a global middleware that runs for every request.
     *
     * @param callable $middleware The middleware callable
     */
    public function addGlobalMiddleware(callable $middleware): void {
        $this->globalMiddlewares[] = $middleware;
    }

    /**
     * Adds a custom error handler for a specific HTTP status code.
     *
     * @param int $code HTTP status code
     * @param callable $handler The error handling callable
     */
    public function addErrorHandler(int $code, callable $handler): void {
        $this->errorHandlers[$code] = $handler;
    }

    /**
     * Disables CSRF protection globally (use with caution).
     */
    public function disableCsrf(): void {
        $this->csrfEnabled = false;
    }

    /**
     * Sets rate limiting parameters for a path prefix.
     * Note: Actual implementation requires external storage (Redis, Memcached).
     *
     * @param string $pathPrefix Path prefix
     * @param int $limit Max requests
     * @param int $window Time window in seconds
     */
    public function setRateLimit(string $pathPrefix, int $limit, int $window): void {
        $this->rateLimits[$this->normalizePath($pathPrefix)] = ['limit' => $limit, 'window' => $window];
    }

    /**
     * Handles the incoming HTTP request.
     */
    public function handleRequest(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = $this->normalizePath(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

        try {
            $matchedRoute = $this->matchRoute($requestMethod, $requestPath);

            if (!$matchedRoute) {
                throw new RuntimeException('Route not found', 404);
            }

            // --- Middleware Execution --- 
            $middlewareStack = array_merge(
                $this->globalMiddlewares,
                $this->getMiddlewaresForPath($requestPath),
                $matchedRoute['middlewares']
            );

            // Create a handler function that includes middleware execution
            $handler = $matchedRoute['handler'];
            $params = $matchedRoute['params'];

            $pipeline = array_reduce(
                array_reverse($middlewareStack), // Process in reverse order for onion-layer execution
                function ($next, $middleware) {
                    return function (...$args) use ($middleware, $next) {
                        // Pass $next callable to the middleware
                        return $middleware($next, ...$args);
                    };
                },
                // The innermost function is the actual route handler
                function (...$args) use ($handler) {
                    return $handler(...$args);
                }
            );

            // Execute the pipeline with route parameters
            $response = $pipeline(...$params);

            // Send the response (assuming handler returns data to be JSON encoded)
            if ($response !== null) {
                Response::sendJson($response);
            }
            // If handler returns null, assume response was handled manually (e.g., file download)

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }

    /**
     * Finds a matching route for the given method and path.
     *
     * @param string $method
     * @param string $path
     * @return array|null Matched route details or null
     */
    private function matchRoute(string $method, string $path): ?array {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertPathToRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove the full match
                // Filter numeric keys from matches (parameters)
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'middlewares' => $route['middlewares'],
                    'original_path' => $route['path'] // Keep original path for rate limiting etc.
                ];
            }
        }
        return null;
    }

    /**
     * Gets middlewares applicable to the given path based on prefixes.
     *
     * @param string $path
     * @return array
     */
    private function getMiddlewaresForPath(string $path): array {
        $applicableMiddlewares = [];
        foreach ($this->middlewares as $prefix => $handlers) {
            if (str_starts_with($path, $prefix)) {
                $applicableMiddlewares = array_merge($applicableMiddlewares, $handlers);
            }
        }
        return $applicableMiddlewares;
    }

    /**
     * Converts a route path with placeholders (e.g., /users/{id}) to a regex.
     *
     * @param string $path
     * @return string
     */
    private function convertPathToRegex(string $path): string {
        // Replace {param} with a named capture group (?<param>[^/]+)
        $pattern = preg_replace('/\{([^}]+)\}/', '(?<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Normalizes a path string (adds leading slash, removes trailing slash).
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string {
        $path = trim($path, '/');
        return '/' . $path;
    }

    /**
     * Handles exceptions and errors, sending an appropriate response.
     *
     * @param Throwable $e
     */
    private function handleError(Throwable $e): void {
        $statusCode = match (true) {
            $e instanceof InvalidArgumentException => 400,
            $e instanceof RuntimeException => $e->getCode() !== 0 ? $e->getCode() : 500, // Use code if set, else 500
            default => 500,
        };
        $statusCode = ($statusCode >= 400 && $statusCode < 600) ? $statusCode : 500;

        // Log the error
        Logger::getInstance()->log('error', [
            'message' => $e->getMessage(),
            'code' => $statusCode,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            // 'trace' => $e->getTraceAsString() // Avoid in production unless necessary
        ]);

        // Check for custom error handler
        if (isset($this->errorHandlers[$statusCode])) {
            try {
                $this->errorHandlers[$statusCode]($e);
                return; // Assume custom handler sends response
            } catch (Throwable $handlerError) {
                // Log error in the error handler itself
                Logger::getInstance()->log('critical', [
                    'message' => 'Error in custom error handler',
                    'handler_exception' => $handlerError->getMessage(),
                    'original_exception' => $e->getMessage(),
                ]);
                // Fallback to default handler
            }
        }

        // Default error response
        $clientMessage = ($statusCode >= 500) ? 'Internal Server Error' : $e->getMessage();
        Response::sendError($clientMessage, $statusCode);
    }

    // --- Placeholder Methods (Implement with proper storage) ---

    private function checkRateLimit(string $path): void {
        // Placeholder: Implement using Redis/Memcached
        // Example logic:
        /*
        foreach ($this->rateLimits as $prefix => $limit) {
            if (str_starts_with($path, $prefix)) {
                $key = 'rate_limit:' . $_SERVER['REMOTE_ADDR'] . ':' . $prefix;
                // $count = $storage->increment($key, $limit['window']);
                // if ($count > $limit['limit']) {
                //     throw new RuntimeException('Rate limit exceeded', 429);
                // }
                break; // Apply first matching limit
            }
        }
        */
    }
}