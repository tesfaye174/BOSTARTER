// Importa la configurazione
import { SW_CONFIG } from './config.js';

// Nome del cache
const CACHE_NAME = SW_CONFIG.cacheName;

// Risorse da memorizzare nella cache
const ASSETS = SW_CONFIG.assets;

// Installazione del Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                return cache.addAll(ASSETS);
            })
            .then(() => {
                return self.skipWaiting();
            })
    );
});

// Attivazione del Service Worker
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => caches.delete(name))
                );
            })
            .then(() => {
                return self.clients.claim();
            })
    );
});

// Gestione delle richieste
self.addEventListener('fetch', (event) => {
    // Ignora le richieste non GET
    if (event.request.method !== 'GET') return;

    // Ignora le richieste di chrome-extension
    if (event.request.url.startsWith('chrome-extension://')) return;

    // Strategia Cache First per le risorse statiche
    if (ASSETS.some(asset => event.request.url.includes(asset))) {
        event.respondWith(
            caches.match(event.request)
                .then((response) => {
                    if (response) {
                        return response;
                    }
                    return fetch(event.request)
                        .then((response) => {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME)
                                .then((cache) => {
                                    cache.put(event.request, responseClone);
                                });
                            return response;
                        });
                })
        );
        return;
    }

    // Strategia Network First per le API
    if (event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME)
                        .then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request)
                        .then((response) => {
                            if (response) {
                                return response;
                            }
                            return new Response(
                                JSON.stringify({ error: 'Offline' }),
                                {
                                    headers: { 'Content-Type': 'application/json' }
                                }
                            );
                        });
                })
        );
        return;
    }

    // Strategia Network First per le altre richieste
    event.respondWith(
        fetch(event.request)
            .catch(() => {
                return caches.match(event.request);
            })
    );
});

// Gestione delle notifiche push
self.addEventListener('push', (event) => {
    const options = {
        body: event.data.text(),
        icon: '/images/logo.png',
        badge: '/images/badge.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Vedi dettagli',
                icon: '/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Chiudi',
                icon: '/images/xmark.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('BoStarter', options)
    );
});

// Gestione dei click sulle notifiche
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Gestione della sincronizzazione in background
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-projects') {
        event.waitUntil(
            syncProjects()
        );
    }
});

// Funzione per sincronizzare i progetti
async function syncProjects() {
    const db = await openDB();
    const projects = await db.getAll('pendingProjects');
    
    for (const project of projects) {
        try {
            const response = await fetch('/api/projects', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(project)
            });
            
            if (response.ok) {
                await db.delete('pendingProjects', project.id);
            }
        } catch (error) {
            console.error('Errore nella sincronizzazione:', error);
        }
    }
}

// Funzione per aprire IndexedDB
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('bostarter-db', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pendingProjects')) {
                db.createObjectStore('pendingProjects', { keyPath: 'id' });
            }
        };
    });
} 