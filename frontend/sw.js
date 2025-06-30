// BOSTARTER Service Worker - Enhanced Caching Strategy
const CACHE_NAME = "bostarter-v1.0.0";
const OFFLINE_PAGE = "/BOSTARTER/frontend/offline.html";
// Resources to cache
const STATIC_RESOURCES = [
    "/BOSTARTER/frontend/",
    "/BOSTARTER/frontend/css/bootstrap.min.css",
    "/BOSTARTER/frontend/css/modern-ui.css",
    "/BOSTARTER/frontend/js/modern-interactions.js",
    "/BOSTARTER/frontend/js/notifications.js",
    "/BOSTARTER/frontend/images/logo.png",
    OFFLINE_PAGE
];
// Install event - Cache static resources
self.addEventListener("install", event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(STATIC_RESOURCES))
            .then(() => self.skipWaiting())
    );
});
// Activate event - Clean old caches
self.addEventListener("activate", event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(cacheName => cacheName !== CACHE_NAME)
                        .map(cacheName => caches.delete(cacheName))
                );
            })
            .then(() => self.clients.claim())
    );
});
// Fetch event - Network-first strategy with fallback to cache
self.addEventListener("fetch", event => {
    if (event.request.method !== 'GET') return;
    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Cache successful responses
                if (response && response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME)
                        .then(cache => cache.put(event.request, responseClone));
                }
                return response;
            })
            .catch(async () => {
                const cache = await caches.open(CACHE_NAME);
                const cachedResponse = await cache.match(event.request);
                return cachedResponse || cache.match(OFFLINE_PAGE);
            }));
});
