<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nuova connessione! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if ($data['type'] === 'auth') {
            // Autentica la connessione
            $userId = $data['user_id'];
            $this->userConnections[$userId] = $from;
            echo "Utente {$userId} autenticato\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Rimuovi la connessione dall'array userConnections
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                break;
            }
        }
        
        echo "Connessione {$conn->resourceId} chiusa\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Errore: {$e->getMessage()}\n";
        $conn->close();
    }

    public function sendNotification($userId, $notification) {
        if (isset($this->userConnections[$userId])) {
            $this->userConnections[$userId]->send(json_encode([
                'type' => 'notification',
                'data' => $notification
            ]));
        }
    }
}

// Avvia il server WebSocket
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    8080
);

echo "Server WebSocket avviato sulla porta 8080\n";
$server->run(); 