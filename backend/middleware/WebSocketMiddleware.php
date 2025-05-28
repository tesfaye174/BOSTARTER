<?php
namespace BOSTARTER\Middleware;

use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebSocketMiddleware implements MessageComponentInterface {
    protected $clients;
    protected $config;
    protected $authenticatedConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->config = require __DIR__ . '/../config/websocket.php';
        $this->authenticatedConnections = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        // Verifica il numero massimo di connessioni
        if ($this->clients->count() >= $this->config['max_connections']) {
            $conn->close();
            return;
        }

        // Verifica l'origine della connessione
        $origin = $conn->httpRequest->getHeader('Origin')[0] ?? '';
        if (!in_array($origin, $this->config['allowed_origins'])) {
            $conn->close();
            return;
        }

        $this->clients->attach($conn);
        echo "Nuova connessione! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        // Verifica la dimensione del messaggio
        if (strlen($msg) > $this->config['max_message_size']) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Messaggio troppo grande'
            ]));
            return;
        }

        // Gestione dell'autenticazione
        if ($data['type'] === 'auth') {
            $this->handleAuthentication($from, $data);
            return;
        }

        // Verifica che la connessione sia autenticata
        if (!isset($this->authenticatedConnections[$from->resourceId])) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Non autenticato'
            ]));
            return;
        }

        // Gestione dei messaggi
        $this->handleMessage($from, $data);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->authenticatedConnections[$conn->resourceId]);
        echo "Connessione {$conn->resourceId} chiusa\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Errore: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function handleAuthentication(ConnectionInterface $conn, $data) {
        if (!isset($data['user_id']) || !isset($data['token'])) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Dati di autenticazione mancanti'
            ]));
            return;
        }

        // Verifica il token JWT
        try {
            $decoded = \Firebase\JWT\JWT::decode(
                $data['token'],
                $_ENV['JWT_SECRET'],
                ['HS256']
            );

            if ($decoded->user_id != $data['user_id']) {
                throw new \Exception('Token non valido');
            }

            $this->authenticatedConnections[$conn->resourceId] = $data['user_id'];
            $conn->send(json_encode([
                'type' => 'auth_success',
                'message' => 'Autenticazione riuscita'
            ]));
        } catch (\Exception $e) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Autenticazione fallita'
            ]));
            $conn->close();
        }
    }

    protected function handleMessage(ConnectionInterface $from, $data) {
        // Implementa la logica di gestione dei messaggi
        // Questo è un esempio, dovresti implementare la logica effettiva
        switch ($data['type']) {
            case 'ping':
                $from->send(json_encode([
                    'type' => 'pong',
                    'timestamp' => time()
                ]));
                break;

            default:
                $from->send(json_encode([
                    'type' => 'error',
                    'message' => 'Tipo di messaggio non supportato'
                ]));
        }
    }
} 