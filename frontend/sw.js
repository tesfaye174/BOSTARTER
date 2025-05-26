const CACHE_NAME = 'bostarter-v1';
const STATIC_CACHE = 'static-v1';
const DYNAMIC_CACHE = 'dynamic-v1';

// Risorse da memorizzare nella cache
const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/frontend/css/main.css',
    '/frontend/js/main.js',
    '/frontend/js/auth.js',
    '/frontend/js/notifications.js',
    '/frontend/images/logo1.svg',
    '/frontend/manifest.json'
];

// Installazione del Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('Cache aperta');
                return cache.addAll(STATIC_ASSETS);
            })
    );
});

// Attivazione del Service Worker
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Strategia di caching: Cache First, fallback a Network
self.addEventListener('fetch', (event) => {
    // Ignora le richieste non GET
    if (event.request.method !== 'GET') return;

    // Ignora le richieste di API
    if (event.request.url.includes('/api/')) {
        event.respondWith(networkFirst(event.request));
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                if (response) {
                    return response;
                }
                return fetch(event.request)
                    .then((networkResponse) => {
                        // Cache dinamica per risorse non statiche
                        if (event.request.url.match(/\.(jpg|jpeg|png|gif|svg)$/)) {
                            return caches.open(DYNAMIC_CACHE)
                                .then((cache) => {
                                    cache.put(event.request, networkResponse.clone());
                                    return networkResponse;
                                });
                        }
                        return networkResponse;
                    })
                    .catch(() => {
                        // Fallback per immagini
                        if (event.request.url.match(/\.(jpg|jpeg|png|gif|svg)$/)) {
                            return caches.match('/frontend/images/placeholder.jpg');
                        }
                        // Fallback per HTML
                        if (event.request.headers.get('accept').includes('text/html')) {
                            return caches.match('/offline.html');
                        }
                    });
            })
    );
});

// Strategia Network First per le API
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        const cache = await caches.open(DYNAMIC_CACHE);
        cache.put(request, networkResponse.clone());
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response(JSON.stringify({ error: 'Offline' }), {
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Gestione delle notifiche push
self.addEventListener('push', (event) => {
    const options = {
        body: event.data.text(),
        icon: '/frontend/images/icon-192x192.png',
        badge: '/frontend/images/badge.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Vedi dettagli',
                icon: '/frontend/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Chiudi',
                icon: '/frontend/images/xmark.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('BOSTARTER', options)
    );
});

// Gestione delle azioni delle notifiche
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
}); 