// Configurazione dell'API con opzioni avanzate
const API_CONFIG = {
    baseUrl: 'http://localhost:8080/api',
    timeout: 10000,
    retryAttempts: 3,
    retryDelay: 1000,
    cacheTime: 5 * 60 * 1000, // 5 minuti in ms
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    // Nuove configurazioni
    maxCacheSize: 100, // Numero massimo di elementi in cache
    logLevel: 'error', // Livello di logging: 'error', 'warn', 'info', 'debug'
    enableCompression: true, // Abilita la compressione dei dati
    validateStatus: status => status >= 200 && status < 300 // Validazione personalizzata dello status HTTP
};

// Cache manager avanzato con LRU (Least Recently Used)
class CacheManager {
    constructor(maxSize = API_CONFIG.maxCacheSize) {
        this.cache = new Map();
        this.maxSize = maxSize;
        this.accessOrder = [];
    }

    set(key, value, ttl) {
        if (this.cache.size >= this.maxSize) {
            const oldestKey = this.accessOrder.shift();
            this.cache.delete(oldestKey);
        }

        const item = {
            value,
            expiry: Date.now() + ttl,
            lastAccessed: Date.now()
        };

        this.cache.set(key, item);
        this.accessOrder.push(key);
        this.cleanExpired();
    }

    get(key) {
        const item = this.cache.get(key);
        if (!item) return null;

        if (Date.now() > item.expiry) {
            this.cache.delete(key);
            this.accessOrder = this.accessOrder.filter(k => k !== key);
            return null;
        }

        // Aggiorna l'ordine di accesso
        item.lastAccessed = Date.now();
        this.accessOrder = this.accessOrder.filter(k => k !== key);
        this.accessOrder.push(key);

        return item.value;
    }

    cleanExpired() {
        const now = Date.now();
        for (const [key, item] of this.cache.entries()) {
            if (now > item.expiry) {
                this.cache.delete(key);
                this.accessOrder = this.accessOrder.filter(k => k !== key);
            }
        }
    }

    clear() {
        this.cache.clear();
        this.accessOrder = [];
    }

    getStats() {
        return {
            size: this.cache.size,
            maxSize: this.maxSize,
            hitRate: this.hits / (this.hits + this.misses),
            oldestItem: this.accessOrder[0]
        };
    }
}

// Logger avanzato
class ApiLogger {
    constructor(level = API_CONFIG.logLevel) {
        this.level = level;
        this.levels = ['error', 'warn', 'info', 'debug'];
    }

    shouldLog(level) {
        return this.levels.indexOf(level) <= this.levels.indexOf(this.level);
    }

    log(level, ...args) {
        if (this.shouldLog(level)) {
            const timestamp = new Date().toISOString();
            console[level](`[${timestamp}] [${level.toUpperCase()}]`, ...args);
        }
    }

    error(...args) { this.log('error', ...args); }
    warn(...args) { this.log('warn', ...args); }
    info(...args) { this.log('info', ...args); }
    debug(...args) { this.log('debug', ...args); }
}

// Classe per la gestione delle API migliorata
class ApiManager {
    constructor(config = API_CONFIG) {
        this.config = config;
        this.baseUrl = config.baseUrl;
        this.cache = new CacheManager(config.maxCacheSize);
        this.logger = new ApiLogger(config.logLevel);
        this.interceptors = {
            request: [],
            response: []
        };
        this.metrics = {
            requestCount: 0,
            errorCount: 0,
            averageResponseTime: 0
        };
    }

    // Gestione avanzata degli errori con dettagli
    handleError(error, endpoint) {
        const errorDetails = {
            timestamp: new Date().toISOString(),
            endpoint,
            message: error.message,
            type: 'API_ERROR',
            requestId: Math.random().toString(36).substring(7)
        };

        if (error.response) {
            errorDetails.status = error.response.status;
            errorDetails.statusText = error.response.statusText;
            errorDetails.data = error.response.data;
            errorDetails.type = 'RESPONSE_ERROR';
        } else if (error.request) {
            errorDetails.type = 'REQUEST_ERROR';
        }

        this.logger.error('API Error:', errorDetails);
        this.metrics.errorCount++;

        throw new Error(JSON.stringify(errorDetails));
    }

    // Implementazione retry logic con backoff esponenziale
    async retryRequest(requestFn, attempts = this.config.retryAttempts) {
        for (let i = 0; i < attempts; i++) {
            try {
                const startTime = Date.now();
                const result = await requestFn();
                this.updateMetrics(Date.now() - startTime);
                return result;
            } catch (error) {
                const isLastAttempt = i === attempts - 1;
                const delay = this.config.retryDelay * Math.pow(2, i);

                this.logger.warn(
                    `Request failed (attempt ${i + 1}/${attempts})`,
                    { error, delay, willRetry: !isLastAttempt }
                );

                if (isLastAttempt) throw error;
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
    }

    // Metodo GET migliorato con caching e compressione
    async get(endpoint, params = {}, options = {}) {
        const queryString = new URLSearchParams(params).toString();
        const cacheKey = `${endpoint}?${queryString}`;

        if (!options.forceRefresh) {
            const cachedData = this.cache.get(cacheKey);
            if (cachedData) {
                this.logger.debug('Cache hit:', { endpoint, params });
                return cachedData;
            }
        }

        const requestFn = async () => {
            const url = new URL(`${this.baseUrl}${endpoint}`);
            Object.entries(params).forEach(([key, value]) => {
                url.searchParams.append(key, value);
            });

            const config = await this.applyRequestInterceptors({
                method: 'GET',
                headers: {
                    ...this.config.headers,
                    ...(this.config.enableCompression && { 'Accept-Encoding': 'gzip, deflate' })
                },
                ...options
            });

            const response = await fetch(url, config);
            if (!this.config.validateStatus(response.status)) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            const processedData = await this.applyResponseInterceptors(data);

            if (!options.noCache) {
                this.cache.set(cacheKey, processedData, this.config.cacheTime);
            }

            return processedData;
        };

        return this.retryRequest(requestFn);
    }

    // Metodo POST migliorato con compressione
    async post(endpoint, data = {}, options = {}) {
        const requestFn = async () => {
            const config = await this.applyRequestInterceptors({
                method: 'POST',
                headers: {
                    ...this.config.headers,
                    ...(this.config.enableCompression && { 'Accept-Encoding': 'gzip, deflate' })
                },
                body: JSON.stringify(data),
                ...options
            });

            const response = await fetch(`${this.baseUrl}${endpoint}`, config);
            if (!this.config.validateStatus(response.status)) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseData = await response.json();
            return this.applyResponseInterceptors(responseData);
        };

        return this.retryRequest(requestFn);
    }

    // Aggiornamento metriche di performance
    updateMetrics(responseTime) {
        this.metrics.requestCount++;
        this.metrics.averageResponseTime = (
            (this.metrics.averageResponseTime * (this.metrics.requestCount - 1) + responseTime) /
            this.metrics.requestCount
        );
    }

    // Applicazione degli interceptor di richiesta
    async applyRequestInterceptors(config) {
        let modifiedConfig = { ...config };
        for (const interceptor of this.interceptors.request) {
            try {
                modifiedConfig = await interceptor(modifiedConfig);
            } catch (error) {
                this.logger.error('Request interceptor error:', error);
                throw error;
            }
        }
        return modifiedConfig;
    }

    // Applicazione degli interceptor di risposta
    async applyResponseInterceptors(data) {
        let modifiedData = { ...data };
        for (const interceptor of this.interceptors.response) {
            try {
                modifiedData = await interceptor(modifiedData);
            } catch (error) {
                this.logger.error('Response interceptor error:', error);
                throw error;
            }
        }
        return modifiedData;
    }

    // Gestione token di autenticazione con refresh token
    setAuthToken(token, refreshToken = null) {
        if (token) {
            this.config.headers['Authorization'] = `Bearer ${token}`;
            localStorage.setItem('auth_token', token);
            if (refreshToken) {
                localStorage.setItem('refresh_token', refreshToken);
            }
        } else {
            delete this.config.headers['Authorization'];
            localStorage.removeItem('auth_token');
            localStorage.removeItem('refresh_token');
        }
    }

    // Recupero token salvato con gestione refresh token
    async restoreAuthToken() {
        const token = localStorage.getItem('auth_token');
        const refreshToken = localStorage.getItem('refresh_token');

        if (token) {
            this.setAuthToken(token);
            // Verifica validità token
            try {
                await this.get('/auth/verify');
            } catch (error) {
                if (refreshToken) {
                    try {
                        const response = await this.post('/auth/refresh', { refreshToken });
                        this.setAuthToken(response.token, response.refreshToken);
                    } catch (refreshError) {
                        this.logger.error('Token refresh failed:', refreshError);
                        this.setAuthToken(null);
                    }
                } else {
                    this.setAuthToken(null);
                }
            }
        }
    }

    // Ottieni metriche di performance
    getMetrics() {
        return {
            ...this.metrics,
            cacheStats: this.cache.getStats()
        };
    }
}

// Crea un'istanza globale del gestore API
const apiManager = new ApiManager();

// Ripristina il token se presente
apiManager.restoreAuthToken();

// Esporta l'istanza e la classe
export { apiManager, ApiManager };