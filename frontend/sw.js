// BOSTARTER Service Worker
const CACHE_NAME = "bostarter-v1.0.0";
const urlsToCache = [
    "/BOSTARTER/frontend/",
    "/BOSTARTER/frontend/css/bootstrap.min.css",
    "/BOSTARTER/frontend/css/modern-ui.css",
    "/BOSTARTER/frontend/js/modern-interactions.js",
    "/BOSTARTER/frontend/js/notifications.js",
    "/BOSTARTER/frontend/images/logo.png"
];

self.addEventListener("install", event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

self.addEventListener("fetch", event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});