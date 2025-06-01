<?php
/**
 * Enhanced WebSocket Server for BOSTARTER Real-time Notifications
 * Provides secure, scalable real-time communication
 */

namespace BOSTARTER\WebSocket;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Auth.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use BOSTARTER\Utils\Auth;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;
    protected $rooms;
    protected $db;
    protected $auth;
    protected $heartbeatInterval;
    protected $lastActivity;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->rooms = [];
        $this->lastActivity = [];
        
        // Initialize database connection
        $database = new \Database();
        $this->db = $database->getConnection();
        $this->auth = new Auth($this->db);
        
        // Start heartbeat checker
        $this->startHeartbeat();
        
        echo "WebSocket Server initialized\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->lastActivity[$conn->resourceId] = time();
        
        // Send welcome message
        $conn->send(json_encode([
            'type' => 'welcome',
            'message' => 'Connected to BOSTARTER WebSocket Server',
            'connectionId' => $conn->resourceId
        ]));
        
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $this->lastActivity[$from->resourceId] = time();
        
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                $this->sendError($from, 'Invalid message format');
                return;
            }

            switch ($data['type']) {
                case 'auth':
                    $this->handleAuthentication($from, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($from);
                    break;
                    
                case 'join_room':
                    $this->handleJoinRoom($from, $data);
                    break;
                    
                case 'leave_room':
                    $this->handleLeaveRoom($from, $data);
                    break;
                    
                case 'subscribe_notifications':
                    $this->handleSubscribeNotifications($from, $data);
                    break;
                    
                default:
                    $this->sendError($from, 'Unknown message type: ' . $data['type']);
            }
            
        } catch (Exception $e) {
            $this->sendError($from, 'Error processing message: ' . $e->getMessage());
            echo "Error processing message: " . $e->getMessage() . "\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Remove from user connections
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                echo "User {$userId} disconnected\n";
                break;
            }
        }
        
        // Remove from rooms
        foreach ($this->rooms as $roomId => $connections) {
            if (($key = array_search($conn, $connections, true)) !== false) {
                unset($this->rooms[$roomId][$key]);
                $this->rooms[$roomId] = array_values($this->rooms[$roomId]);
            }
        }
        
        // Clean up activity tracking
        unset($this->lastActivity[$conn->resourceId]);
        
        echo "Connection {$conn->resourceId} closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error on connection {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function handleAuthentication(ConnectionInterface $conn, $data) {
        if (!isset($data['token']) || !isset($data['user_id'])) {
            $this->sendError($conn, 'Missing authentication credentials');
            return;
        }

        try {
            // Verify the JWT token or session
            $isValid = $this->auth->validateToken($data['token'], $data['user_id']);
            
            if ($isValid) {
                $userId = $data['user_id'];
                $this->userConnections[$userId] = $conn;
                
                $conn->send(json_encode([
                    'type' => 'auth_success',
                    'message' => 'Authentication successful',
                    'user_id' => $userId
                ]));
                
                // Send any pending notifications
                $this->sendPendingNotifications($userId);
                
                echo "User {$userId} authenticated successfully\n";
            } else {
                $this->sendError($conn, 'Authentication failed');
                $conn->close();
            }
        } catch (Exception $e) {
            $this->sendError($conn, 'Authentication error: ' . $e->getMessage());
            $conn->close();
        }
    }

    protected function handlePing(ConnectionInterface $conn) {
        $conn->send(json_encode([
            'type' => 'pong',
            'timestamp' => time()
        ]));
    }

    protected function handleJoinRoom(ConnectionInterface $conn, $data) {
        if (!isset($data['room_id'])) {
            $this->sendError($conn, 'Missing room_id');
            return;
        }

        $roomId = $data['room_id'];
        
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
        }
        
        if (!in_array($conn, $this->rooms[$roomId], true)) {
            $this->rooms[$roomId][] = $conn;
        }
        
        $conn->send(json_encode([
            'type' => 'room_joined',
            'room_id' => $roomId,
            'members_count' => count($this->rooms[$roomId])
        ]));
        
        echo "Connection {$conn->resourceId} joined room {$roomId}\n";
    }

    protected function handleLeaveRoom(ConnectionInterface $conn, $data) {
        if (!isset($data['room_id'])) {
            $this->sendError($conn, 'Missing room_id');
            return;
        }

        $roomId = $data['room_id'];
        
        if (isset($this->rooms[$roomId])) {
            if (($key = array_search($conn, $this->rooms[$roomId], true)) !== false) {
                unset($this->rooms[$roomId][$key]);
                $this->rooms[$roomId] = array_values($this->rooms[$roomId]);
            }
        }
        
        $conn->send(json_encode([
            'type' => 'room_left',
            'room_id' => $roomId
        ]));
        
        echo "Connection {$conn->resourceId} left room {$roomId}\n";
    }

    protected function handleSubscribeNotifications(ConnectionInterface $conn, $data) {
        // This is handled automatically after authentication
        $conn->send(json_encode([
            'type' => 'notifications_subscribed',
            'message' => 'Successfully subscribed to notifications'
        ]));
    }

    protected function sendError(ConnectionInterface $conn, $message) {
        $conn->send(json_encode([
            'type' => 'error',
            'message' => $message,
            'timestamp' => time()
        ]));
    }

    protected function sendPendingNotifications($userId) {
        try {
            // Get unread notifications from database
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = false 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($notifications)) {
                $this->sendToUser($userId, [
                    'type' => 'pending_notifications',
                    'notifications' => $notifications,
                    'count' => count($notifications)
                ]);
            }
        } catch (Exception $e) {
            echo "Error sending pending notifications: " . $e->getMessage() . "\n";
        }
    }

    public function sendToUser($userId, $data) {
        if (isset($this->userConnections[$userId])) {
            $this->userConnections[$userId]->send(json_encode($data));
            return true;
        }
        return false;
    }

    public function sendToRoom($roomId, $data) {
        if (isset($this->rooms[$roomId])) {
            foreach ($this->rooms[$roomId] as $conn) {
                $conn->send(json_encode($data));
            }
            return count($this->rooms[$roomId]);
        }
        return 0;
    }

    public function broadcastToAll($data) {
        foreach ($this->clients as $client) {
            $client->send(json_encode($data));
        }
        return $this->clients->count();
    }

    public function sendNotification($userId, $notification) {
        $data = [
            'type' => 'notification',
            'data' => $notification,
            'timestamp' => time()
        ];
        
        return $this->sendToUser($userId, $data);
    }

    protected function startHeartbeat() {
        // This would normally be handled by a separate process or timer
        // For now, we'll rely on client-side ping messages
    }

    public function getStats() {
        return [
            'total_connections' => $this->clients->count(),
            'authenticated_users' => count($this->userConnections),
            'active_rooms' => count($this->rooms),
            'memory_usage' => memory_get_usage(true)
        ];
    }
}

// Auto-start server if this file is run directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $config = require __DIR__ . '/../config/websocket.php';
    
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new WebSocketServer()
            )
        ),
        $config['port'] ?? 8080,
        $config['host'] ?? '0.0.0.0'
    );

    echo "BOSTARTER WebSocket Server started on {$config['host']}:{$config['port']}\n";
    $server->run();
}
