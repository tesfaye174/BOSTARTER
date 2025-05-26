// Configurazione del database
const DB_CONFIG = {
    name: 'bostarter-db',
    version: 1,
    stores: {
        projects: {
            keyPath: 'id',
            indexes: [
                { name: 'title', keyPath: 'title' },
                { name: 'category', keyPath: 'category' },
                { name: 'createdAt', keyPath: 'createdAt' }
            ]
        },
        categories: {
            keyPath: 'id',
            indexes: [
                { name: 'name', keyPath: 'name' }
            ]
        },
        user: {
            keyPath: 'id'
        },
        settings: {
            keyPath: 'key'
        }
    }
};

// Classe per la gestione del database
class DatabaseManager {
    constructor() {
        this.db = null;
        this.init();
    }

    // Inizializzazione del database
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_CONFIG.name, DB_CONFIG.version);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve();
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Crea gli object store
                for (const [storeName, config] of Object.entries(DB_CONFIG.stores)) {
                    if (!db.objectStoreNames.contains(storeName)) {
                        const store = db.createObjectStore(storeName, {
                            keyPath: config.keyPath
                        });

                        // Crea gli indici
                        if (config.indexes) {
                            config.indexes.forEach(index => {
                                store.createIndex(index.name, index.keyPath);
                            });
                        }
                    }
                }
            };
        });
    }

    // Aggiunge un elemento
    async add(storeName, item) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.add(item);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Aggiorna un elemento
    async update(storeName, item) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.put(item);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Elimina un elemento
    async delete(storeName, key) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.delete(key);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Ottiene un elemento
    async get(storeName, key) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(key);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Ottiene tutti gli elementi
    async getAll(storeName) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Ottiene elementi per indice
    async getByIndex(storeName, indexName, value) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readonly');
            const store = transaction.objectStore(storeName);
            const index = store.index(indexName);
            const request = index.getAll(value);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Ottiene elementi in un range
    async getByRange(storeName, indexName, range) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readonly');
            const store = transaction.objectStore(storeName);
            const index = store.index(indexName);
            const request = index.getAll(range);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Conta gli elementi
    async count(storeName) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.count();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Pulisce uno store
    async clear(storeName) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(storeName, 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Pulisce il database
    async clearAll() {
        return Promise.all(
            Object.keys(DB_CONFIG.stores).map(storeName => this.clear(storeName))
        );
    }

    // Chiude il database
    close() {
        if (this.db) {
            this.db.close();
            this.db = null;
        }
    }
}

// Crea un'istanza globale del gestore database
const db = new DatabaseManager();

// Esporta l'istanza e la classe
export { db, DatabaseManager }; 