/**
 * Enhanced WebSocket Service for BOSTARTER Frontend
 * Handles real-time communication with the backend WebSocket server
 */

class WebSocketClient {
    constructor() {
        this.socket = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.listeners = new Map();
        this.authCallback = null;
        this.userId = null;
        this.token = null;
        this.heartbeatInterval = null;
        this.connectionId = null;

        // Bind methods to preserve context
        this.handleOpen = this.handleOpen.bind(this);
        this.handleMessage = this.handleMessage.bind(this);
        this.handleClose = this.handleClose.bind(this);
        this.handleError = this.handleError.bind(this);
    }

    /**
     * Connect to WebSocket server
     */
    connect(userId, token) {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.disconnect();
        }

        this.userId = userId;
        this.token = token;

        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.hostname}:8080`;

            console.log('Connecting to WebSocket:', wsUrl);
            this.socket = new WebSocket(wsUrl);

            this.socket.onopen = this.handleOpen;
            this.socket.onmessage = this.handleMessage;
            this.socket.onclose = this.handleClose;
            this.socket.onerror = this.handleError;

        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.handleConnectionError();
        }
    }

    /**
     * Disconnect from WebSocket server
     */
    disconnect() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }

        if (this.socket) {
            this.socket.onopen = null;
            this.socket.onmessage = null;
            this.socket.onclose = null;
            this.socket.onerror = null;

            if (this.socket.readyState === WebSocket.OPEN) {
                this.socket.close();
            }
            this.socket = null;
        }

        this.isConnected = false;
        this.connectionId = null;
        this.reconnectAttempts = 0;
    }

    /**
     * Handle WebSocket connection open
     */
    handleOpen() {
        console.log('WebSocket connected successfully');
        this.isConnected = true;
        this.reconnectAttempts = 0;

        // Authenticate immediately after connection
        this.authenticate();
    }

    /**
     * Handle incoming WebSocket messages
     */
    handleMessage(event) {
        try {
            const data = JSON.parse(event.data);
            console.log('WebSocket message received:', data);

            switch (data.type) {
                case 'welcome':
                    this.connectionId = data.connectionId;
                    console.log('WebSocket welcome received, connection ID:', this.connectionId);
                    break;

                case 'auth_success':
                    console.log('WebSocket authentication successful');
                    this.startHeartbeat();
                    if (this.authCallback) {
                        this.authCallback(true);
                    }
                    break;

                case 'error':
                    console.error('WebSocket error:', data.message);
                    if (data.message.includes('Authentication')) {
                        if (this.authCallback) {
                            this.authCallback(false);
                        }
                    }
                    break;

                case 'notification':
                    this.notifyListeners('notification', data.data);
                    break;

                case 'pending_notifications':
                    this.notifyListeners('pending_notifications', data);
                    break;

                case 'pong':
                    // Handle heartbeat response
                    console.log('Heartbeat response received');
                    break;

                case 'room_joined':
                    this.notifyListeners('room_joined', data);
                    break;

                case 'room_left':
                    this.notifyListeners('room_left', data);
                    break;

                case 'notifications_subscribed':
                    console.log('Successfully subscribed to notifications');
                    break;

                default:
                    console.warn('Unknown WebSocket message type:', data.type);
                    this.notifyListeners('unknown_message', data);
            }

        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    }

    /**
     * Handle WebSocket connection close
     */
    handleClose(event) {
        console.log('WebSocket connection closed:', event.code, event.reason);
        this.isConnected = false;

        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }

        // Attempt to reconnect if not a clean close
        if (event.code !== 1000 && this.reconnectAttempts < this.maxReconnectAttempts) {
            this.attemptReconnect();
        }
    }

    /**
     * Handle WebSocket errors
     */
    handleError(error) {
        console.error('WebSocket error:', error);
        this.handleConnectionError();
    }

    /**
     * Handle connection errors
     */
    handleConnectionError() {
        this.isConnected = false;
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.attemptReconnect();
        } else {
            console.error('Max reconnection attempts reached');
            this.notifyListeners('connection_failed', {
                attempts: this.reconnectAttempts
            });
        }
    }

    /**
     * Attempt to reconnect with exponential backoff
     */
    attemptReconnect() {
        if (!this.userId || !this.token) {
            console.error('Cannot reconnect: missing credentials');
            return;
        }

        this.reconnectAttempts++;
        const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);

        console.log(`Attempting to reconnect in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

        setTimeout(() => {
            if (this.reconnectAttempts <= this.maxReconnectAttempts) {
                this.connect(this.userId, this.token);
            }
        }, delay);
    }

    /**
     * Authenticate with the server
     */
    authenticate() {
        if (!this.userId || !this.token) {
            console.error('Cannot authenticate: missing credentials');
            return;
        }

        this.send({
            type: 'auth',
            user_id: this.userId,
            token: this.token
        });
    }

    /**
     * Start heartbeat to keep connection alive
     */
    startHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }

        this.heartbeatInterval = setInterval(() => {
            if (this.isConnected) {
                this.ping();
            }
        }, 30000); // Send ping every 30 seconds
    }

    /**
     * Send ping to server
     */
    ping() {
        this.send({
            type: 'ping',
            timestamp: Date.now()
        });
    }

    /**
     * Send message to server
     */
    send(data) {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            try {
                this.socket.send(JSON.stringify(data));
                return true;
            } catch (error) {
                console.error('Error sending WebSocket message:', error);
                return false;
            }
        } else {
            console.warn('WebSocket is not connected, cannot send message');
            return false;
        }
    }

    /**
     * Join a room for group communications
     */
    joinRoom(roomId) {
        return this.send({
            type: 'join_room',
            room_id: roomId
        });
    }

    /**
     * Leave a room
     */
    leaveRoom(roomId) {
        return this.send({
            type: 'leave_room',
            room_id: roomId
        });
    }

    /**
     * Subscribe to notifications
     */
    subscribeToNotifications() {
        return this.send({
            type: 'subscribe_notifications'
        });
    }

    /**
     * Set authentication callback
     */
    setAuthCallback(callback) {
        this.authCallback = callback;
    }

    /**
     * Add event listener
     */
    addListener(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, new Set());
        }
        this.listeners.get(event).add(callback);
    }

    /**
     * Remove event listener
     */
    removeListener(event, callback) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).delete(callback);
        }
    }

    /**
     * Notify all listeners for an event
     */
    notifyListeners(event, data) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in ${event} listener:`, error);
                }
            });
        }
    }

    /**
     * Get connection status
     */
    getStatus() {
        return {
            isConnected: this.isConnected,
            connectionId: this.connectionId,
            reconnectAttempts: this.reconnectAttempts,
            readyState: this.socket ? this.socket.readyState : null
        };
    }

    /**
     * Check if WebSocket is connected and ready
     */
    isReady() {
        return this.isConnected && this.socket && this.socket.readyState === WebSocket.OPEN;
    }
}

// Create singleton instance
const webSocketClient = new WebSocketClient();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = webSocketClient;
} else if (typeof window !== 'undefined') {
    window.WebSocketClient = webSocketClient;
}

// Auto-connect if user credentials are available
document.addEventListener('DOMContentLoaded', () => {
    const userId = localStorage.getItem('user_id') || sessionStorage.getItem('user_id');
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');

    if (userId && token) {
        console.log('Auto-connecting WebSocket with stored credentials');
        webSocketClient.connect(userId, token);
    }
});
