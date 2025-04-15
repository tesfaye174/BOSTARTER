<?php
namespace Config;

use Server\Router;
use Controllers\AuthController;
use Controllers\ProjectController;
use Controllers\UserController;
use Controllers\AdminController;
use Controllers\StatsController;
use Middleware\AuthMiddleware;

class Routes {
    private static function registerAuthRoutes(Router $router): void {
        $router->addRoute('POST', '/api/auth/login', [AuthController::class, 'login']);
        $router->addRoute('POST', '/api/auth/register', [AuthController::class, 'register']);
        $router->addRoute('GET', '/api/auth/check', [AuthController::class, 'checkAuth']);
        $router->addRoute('POST', '/api/auth/reset-password', [AuthController::class, 'resetPassword']);
        $router->addRoute('POST', '/api/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, 'authenticate']);
    }

    private static function registerProjectRoutes(Router $router): void {
        // Public project endpoints
        $router->addRoute('GET', '/api/projects/list', [ProjectController::class, 'listProjects']);
        $router->addRoute('GET', '/api/projects/view/{id}', [ProjectController::class, 'viewProject']);
        
        // Protected project endpoints
        $projectMiddleware = [AuthMiddleware::class, 'authenticate'];
        $router->addRoute('POST', '/api/projects/create', [ProjectController::class, 'createProject'], $projectMiddleware);
        $router->addRoute('POST', '/api/projects/fund/{id}', [ProjectController::class, 'fundProject'], $projectMiddleware);
        $router->addRoute('POST', '/api/projects/comment/{id}', [ProjectController::class, 'addComment'], $projectMiddleware);
        $router->addRoute('POST', '/api/projects/respond/{id}', [ProjectController::class, 'respondToComment'], $projectMiddleware);
    }

    private static function registerUserRoutes(Router $router): void {
        $userMiddleware = [AuthMiddleware::class, 'authenticate'];
        $router->addRoute('GET', '/api/users/profile', [UserController::class, 'getProfile'], $userMiddleware);
        $router->addRoute('PUT', '/api/users/update', [UserController::class, 'updateProfile'], $userMiddleware);
        $router->addRoute('PUT', '/api/users/skills', [UserController::class, 'updateSkills'], $userMiddleware);
        $router->addRoute('GET', '/api/users/view/{id}', [UserController::class, 'viewUser'], $userMiddleware);
    }

    private static function registerAdminRoutes(Router $router): void {
        $adminMiddleware = [AuthMiddleware::class, 'adminOnly'];
        $router->addRoute('POST', '/api/admin/competencies', [AdminController::class, 'manageCompetencies'], $adminMiddleware);
        $router->addRoute('GET', '/api/admin/users', [AdminController::class, 'manageUsers'], $adminMiddleware);
        $router->addRoute('GET', '/api/admin/logs', [AdminController::class, 'viewLogs'], $adminMiddleware);
    }

    private static function registerStatsRoutes(Router $router): void {
        // Public stats endpoints
        $router->addRoute('GET', '/api/stats/top-creators', [StatsController::class, 'getTopCreators']);
        $router->addRoute('GET', '/api/stats/top-projects', [StatsController::class, 'getTopProjects']);
        $router->addRoute('GET', '/api/stats/top-funders', [StatsController::class, 'getTopFunders']);
        $router->addRoute('GET', '/api/stats/project-stats/{id}', [StatsController::class, 'getProjectStats']);
        
        // Protected stats endpoints
        $router->addRoute('GET', '/api/stats/platform', [StatsController::class, 'getPlatformStats'], [AuthMiddleware::class, 'adminOnly']);
    }

    public static function register(Router $router): void {
        // Apply global middlewares
        $router->addGlobalMiddleware(function() {
            header('Content-Type: application/json');
        });
        
        // Add error handling middleware
        $router->addGlobalMiddleware([\Middleware\ErrorMiddleware::class, 'handleErrors']);
        
        // Add rate limiting for sensitive endpoints
        $router->setRateLimit('/api/auth/login', 5, 300); // 5 attempts per 5 minutes
        $router->setRateLimit('/api/auth/register', 3, 3600); // 3 attempts per hour
        $router->setRateLimit('/api/projects/create', 10, 3600); // 10 projects per hour

        // Register routes by group
        self::registerAuthRoutes($router);
        self::registerProjectRoutes($router);
        self::registerUserRoutes($router);
        self::registerAdminRoutes($router);
        self::registerStatsRoutes($router);

        // Add default error handlers
        $router->addErrorHandler(404, function() {
            http_response_code(404);
            echo json_encode(['error' => 'Resource not found']);
        });

        $router->addErrorHandler(405, function() {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        });
    }
}