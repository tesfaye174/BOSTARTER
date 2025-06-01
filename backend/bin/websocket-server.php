#!/usr/bin/env php
<?php
/**
 * WebSocket Server Startup Script for BOSTARTER
 * This script starts the enhanced WebSocket server with proper error handling
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Change to the backend directory
$backendDir = __DIR__ . '/..';
chdir($backendDir);

// Check if Composer autoloader exists
if (!file_exists('vendor/autoload.php')) {
    echo "Error: Composer dependencies not installed.\n";
    echo "Please run 'composer install' in the backend directory.\n";
    exit(1);
}

require_once 'vendor/autoload.php';
require_once 'config/database.php';

// Load environment variables if .env file exists
if (file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// Load the WebSocket server class
require_once 'websocket/WebSocketServer.php';

// Configuration
$config = [
    'host' => $_ENV['WEBSOCKET_HOST'] ?? '0.0.0.0',
    'port' => $_ENV['WEBSOCKET_PORT'] ?? 8080,
    'max_connections' => $_ENV['WEBSOCKET_MAX_CONNECTIONS'] ?? 1000
];

echo "BOSTARTER WebSocket Server\n";
echo "==========================\n";
echo "Host: {$config['host']}\n";
echo "Port: {$config['port']}\n";
echo "Max Connections: {$config['max_connections']}\n";
echo "==========================\n";

try {
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "✓ Database connection successful\n";
    
    // Create and start the WebSocket server
    $wsServer = new \BOSTARTER\WebSocket\WebSocketServer();
    
    $server = IoServer::factory(
        new HttpServer(
            new WsServer($wsServer)
        ),
        $config['port'],
        $config['host']
    );

    echo "✓ WebSocket server initialized\n";
    echo "🚀 Server starting on {$config['host']}:{$config['port']}\n";
    echo "Press Ctrl+C to stop the server\n";
    echo "==========================\n";

    // Handle graceful shutdown
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGTERM, function() use ($server) {
            echo "\n💀 Received SIGTERM, shutting down gracefully...\n";
            exit(0);
        });
        
        pcntl_signal(SIGINT, function() use ($server) {
            echo "\n💀 Received SIGINT, shutting down gracefully...\n";
            exit(0);
        });
    }

    // Start the server
    $server->run();

} catch (Exception $e) {
    echo "❌ Error starting WebSocket server: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
