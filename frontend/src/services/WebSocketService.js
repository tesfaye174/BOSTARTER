class WebSocketService {
    constructor() {
        this.socket = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.listeners = new Map();
        this.authCallback = null;
    }

    connect(userId, token) {
        if (this.socket) {
            this.socket.close();
        }

        this.socket = new WebSocket('ws://localhost:8080');

        this.socket.onopen = () => {
            console.log('WebSocket connesso');
            this.isConnected = true;
            this.reconnectAttempts = 0;
            
            // Invia l'autenticazione
            this.socket.send(JSON.stringify({
                type: 'auth',
                user_id: userId,
                token: token
            }));
        };

        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            
            switch (data.type) {
                case 'auth_success':
                    console.log('Autenticazione WebSocket riuscita');
                    if (this.authCallback) {
                        this.authCallback(true);
                    }
                    break;

                case 'error':
                    console.error('Errore WebSocket:', data.message);
                    if (data.message === 'Autenticazione fallita' && this.authCallback) {
                        this.authCallback(false);
                    }
                    break;

                case 'notification':
                    this.notifyListeners('notification', data.data);
                    break;

                case 'pong':
                    // Gestione del ping/pong per mantenere la connessione attiva
                    break;

                default:
                    console.warn('Tipo di messaggio non gestito:', data.type);
            }
        };

        this.socket.onclose = () => {
            console.log('WebSocket disconnesso');
            this.isConnected = false;
            
            // Tentativo di riconnessione
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnectAttempts++;
                setTimeout(() => {
                    console.log(`Tentativo di riconnessione ${this.reconnectAttempts}...`);
                    this.connect(userId, token);
                }, this.reconnectDelay * this.reconnectAttempts);
            }
        };

        this.socket.onerror = (error) => {
            console.error('Errore WebSocket:', error);
        };
    }

    disconnect() {
        if (this.socket) {
            this.socket.close();
            this.socket = null;
            this.isConnected = false;
        }
    }

    setAuthCallback(callback) {
        this.authCallback = callback;
    }

    addListener(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, new Set());
        }
        this.listeners.get(event).add(callback);
    }

    removeListener(event, callback) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).delete(callback);
        }
    }

    notifyListeners(event, data) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).forEach(callback => {
                callback(data);
            });
        }
    }

    // Invia un ping per mantenere la connessione attiva
    ping() {
        if (this.isConnected) {
            this.socket.send(JSON.stringify({
                type: 'ping',
                timestamp: Date.now()
            }));
        }
    }
}

// Esporta un'istanza singleton
export default new WebSocketService(); 