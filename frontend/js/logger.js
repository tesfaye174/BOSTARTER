// Livelli di log
const LOG_LEVELS = {
    DEBUG: 0,
    INFO: 1,
    WARN: 2,
    ERROR: 3
};

// Configurazione del logger
const config = {
    minLevel: LOG_LEVELS.DEBUG,
    enableConsole: true,
    enableStorage: true,
    maxStorageItems: 100,
    storageKey: 'bostarter_logs'
};

class Logger {
    constructor() {
        this.logs = [];
        this.loadLogs();
    }

    // Carica i log salvati
    loadLogs() {
        if (config.enableStorage) {
            try {
                const savedLogs = localStorage.getItem(config.storageKey);
                if (savedLogs) {
                    this.logs = JSON.parse(savedLogs);
                }
            } catch (error) {
                console.error('Errore nel caricamento dei log:', error);
            }
        }
    }

    // Salva i log
    saveLogs() {
        if (config.enableStorage) {
            try {
                // Mantiene solo gli ultimi N log
                if (this.logs.length > config.maxStorageItems) {
                    this.logs = this.logs.slice(-config.maxStorageItems);
                }
                localStorage.setItem(config.storageKey, JSON.stringify(this.logs));
            } catch (error) {
                console.error('Errore nel salvataggio dei log:', error);
            }
        }
    }

    // Formatta il messaggio di log
    formatMessage(level, message, data) {
        const timestamp = new Date().toISOString();
        return {
            timestamp,
            level,
            message,
            data: data || null
        };
    }

    // Log di debug
    debug(message, data) {
        if (LOG_LEVELS.DEBUG >= config.minLevel) {
            const logEntry = this.formatMessage('DEBUG', message, data);
            this.logs.push(logEntry);
            if (config.enableConsole) {
                console.debug(`[${logEntry.timestamp}] ${message}`, data);
            }
            this.saveLogs();
        }
    }

    // Log informativo
    info(message, data) {
        if (LOG_LEVELS.INFO >= config.minLevel) {
            const logEntry = this.formatMessage('INFO', message, data);
            this.logs.push(logEntry);
            if (config.enableConsole) {
                console.info(`[${logEntry.timestamp}] ${message}`, data);
            }
            this.saveLogs();
        }
    }

    // Log di warning
    warn(message, data) {
        if (LOG_LEVELS.WARN >= config.minLevel) {
            const logEntry = this.formatMessage('WARN', message, data);
            this.logs.push(logEntry);
            if (config.enableConsole) {
                console.warn(`[${logEntry.timestamp}] ${message}`, data);
            }
            this.saveLogs();
        }
    }

    // Log di errore
    error(message, data) {
        if (LOG_LEVELS.ERROR >= config.minLevel) {
            const logEntry = this.formatMessage('ERROR', message, data);
            this.logs.push(logEntry);
            if (config.enableConsole) {
                console.error(`[${logEntry.timestamp}] ${message}`, data);
            }
            this.saveLogs();
        }
    }

    // Ottiene tutti i log
    getLogs() {
        return this.logs;
    }

    // Ottiene i log filtrati per livello
    getLogsByLevel(level) {
        return this.logs.filter(log => log.level === level);
    }

    // Ottiene i log filtrati per data
    getLogsByDate(startDate, endDate) {
        return this.logs.filter(log => {
            const logDate = new Date(log.timestamp);
            return logDate >= startDate && logDate <= endDate;
        });
    }

    // Pulisce i log
    clearLogs() {
        this.logs = [];
        if (config.enableStorage) {
            localStorage.removeItem(config.storageKey);
        }
    }

    // Esporta i log
    exportLogs() {
        const blob = new Blob([JSON.stringify(this.logs, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `bostarter_logs_${new Date().toISOString()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}

// Crea un'istanza singleton del logger
const logger = new Logger();

// Esporta il logger
export default logger; 