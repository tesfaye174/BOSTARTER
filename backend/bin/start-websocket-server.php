<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use BOSTARTER\WebSocket\NotificationServer;

// Carica le variabili d'ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Crea il server WebSocket
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    $_ENV['WEBSOCKET_PORT'] ?? 8080
);

echo "Server WebSocket avviato sulla porta " . ($_ENV['WEBSOCKET_PORT'] ?? 8080) . "\n";
$server->run(); 