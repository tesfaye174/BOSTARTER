/**
 * BOSTARTER Service Worker
 * Provides offline support, caching, and PWA functionality
 */

const CACHE_NAME = 'bostarter-v1.0.0';
const STATIC_CACHE = 'bostarter-static-v1.0.0';
const DYNAMIC_CACHE = 'bostarter-dynamic-v1.0.0';
const API_CACHE = 'bostarter-api-v1.0.0';

// Core files to cache immediately (App Shell)
const CORE_FILES = [
    '/frontend/',
    '/frontend/index.php',
    '/frontend/dashboard.php',
    '/frontend/css/unified-styles.css',
    '/frontend/css/main.css',
    '/frontend/css/components.css',
    '/frontend/css/critical.css',
    '/frontend/css/accessibility.css',
    '/frontend/css/components/common-styles.css',
    '/frontend/js/main.js',
    '/frontend/js/error-handler.js',
    '/frontend/js/core/NotificationSystem.js',
    '/frontend/js/core/Utils.js',
    '/frontend/js/core/loader.js', '/frontend/js/utils/common-functions.js', '/frontend/js/utils/verification-utils.js',
    '/frontend/js/managers/base-category-manager.js',
    '/frontend/js/managers/generic-category-manager.js',
    '/frontend/js/sw-register.js'
];

// Assets to cache on demand
const CACHE_STRATEGIES = {
    // Static assets - Cache First
    static: [
        /\.css$/,
        /\.js$/,
        /\.png$/,
        /\.jpg$/,
        /\.jpeg$/,
        /\.gif$/,
        /\.svg$/,
        /\.ico$/,
        /\.woff$/,
        /\.woff2$/,
        /\.ttf$/,
        /\.eot$/
    ],
    // API calls - Network First with cache fallback
    api: [
        /\/backend\/api\//,
        /\/backend\/utils\//
    ],
    // Pages - Network First
    pages: [
        /\.php$/,
        /\.html$/
    ]
};

// Install event - cache core files
self.addEventListener('install', event => {
    console.log('🔧 Service Worker installing...');

    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('📦 Caching core files...');
                return cache.addAll(CORE_FILES);
            })
            .then(() => {
                console.log('✅ Core files cached successfully');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('❌ Failed to cache core files:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('🚀 Service Worker activating...');

    event.waitUntil(
        Promise.all([
            // Clean up old caches
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE &&
                            cacheName !== DYNAMIC_CACHE &&
                            cacheName !== API_CACHE) {
                            console.log('🗑️ Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            }),
            // Take control of all clients
            self.clients.claim()
        ]).then(() => {
            console.log('✅ Service Worker activated successfully');
        })
    );
});

// Fetch event - handle requests with appropriate caching strategy
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip external requests
    if (!url.origin.includes(location.origin) && !url.pathname.startsWith('/frontend/')) {
        return;
    }

    event.respondWith(handleRequest(request));
});

async function handleRequest(request) {
    const url = new URL(request.url);
    const pathname = url.pathname;

    try {
        // Determine caching strategy based on request type
        if (isStaticAsset(pathname)) {
            return await cacheFirst(request, STATIC_CACHE);
        } else if (isApiRequest(pathname)) {
            return await networkFirst(request, API_CACHE);
        } else if (isPageRequest(pathname)) {
            return await networkFirst(request, DYNAMIC_CACHE);
        } else {
            // Default to network first
            return await networkFirst(request, DYNAMIC_CACHE);
        }
    } catch (error) {
        console.error('❌ Request failed:', pathname, error);
        return await getOfflineFallback(request);
    }
}

// Cache First strategy (for static assets)
async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
        // Serve from cache
        return cachedResponse;
    }

    // Fetch from network and cache
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        throw error;
    }
}

// Network First strategy (for API calls and pages)
async function networkFirst(request, cacheName) {
    const cache = await caches.open(cacheName);

    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            // Cache successful responses
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        // Network failed, try cache
        const cachedResponse = await cache.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

// Utility functions to determine request type
function isStaticAsset(pathname) {
    return CACHE_STRATEGIES.static.some(pattern => pattern.test(pathname));
}

function isApiRequest(pathname) {
    return CACHE_STRATEGIES.api.some(pattern => pattern.test(pathname));
}

function isPageRequest(pathname) {
    return CACHE_STRATEGIES.pages.some(pattern => pattern.test(pathname));
}

// Offline fallback
async function getOfflineFallback(request) {
    const url = new URL(request.url);
    const pathname = url.pathname;

    // Try to get a cached version first
    const cacheNames = [STATIC_CACHE, DYNAMIC_CACHE, API_CACHE];

    for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const cachedResponse = await cache.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
    }

    // Provide fallback responses based on request type
    if (isPageRequest(pathname)) {
        return getOfflinePage();
    } else if (isApiRequest(pathname)) {
        return getOfflineApiResponse();
    } else {
        return new Response('Resource not available offline', {
            status: 503,
            statusText: 'Service Unavailable'
        });
    }
}

// Create offline fallback page
function getOfflinePage() {
    const offlineHTML = `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BOSTARTER - Offline</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            .offline-container {
                text-align: center;
                padding: 2rem;
                max-width: 500px;
            }
            .offline-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }
            .offline-title {
                font-size: 2rem;
                margin-bottom: 1rem;
                font-weight: 300;
            }
            .offline-message {
                font-size: 1.1rem;
                margin-bottom: 2rem;
                opacity: 0.9;
            }
            .retry-btn {
                background: rgba(255,255,255,0.2);
                border: 2px solid rgba(255,255,255,0.3);
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 25px;
                cursor: pointer;
                font-size: 1rem;
                transition: all 0.3s ease;
            }
            .retry-btn:hover {
                background: rgba(255,255,255,0.3);
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="offline-container">
            <div class="offline-icon">📡</div>
            <h1 class="offline-title">You're Offline</h1>
            <p class="offline-message">
                BOSTARTER is not available right now. 
                Please check your internet connection and try again.
            </p>
            <button class="retry-btn" onclick="window.location.reload()">
                Try Again
            </button>
        </div>
    </body>
    </html>
    `;

    return new Response(offlineHTML, {
        headers: { 'Content-Type': 'text/html' }
    });
}

// Create offline API response
function getOfflineApiResponse() {
    const offlineResponse = {
        success: false,
        error: 'offline',
        message: 'This feature is not available offline. Please try again when you have an internet connection.'
    };

    return new Response(JSON.stringify(offlineResponse), {
        headers: {
            'Content-Type': 'application/json',
            'X-Offline': 'true'
        },
        status: 503
    });
}

// Handle background sync (for future enhancement)
self.addEventListener('sync', event => {
    console.log('🔄 Background sync triggered:', event.tag);

    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    // Placeholder for background sync functionality
    // Could be used to sync form data, analytics, etc.
    console.log('📊 Performing background sync...');
}

// Handle push notifications (for future enhancement)
self.addEventListener('push', event => {
    console.log('🔔 Push notification received');

    if (event.data) {
        const data = event.data.json();

        event.waitUntil(
            self.registration.showNotification(data.title, {
                body: data.body,
                icon: '/frontend/assets/images/icon-192x192.png',
                badge: '/frontend/assets/images/badge-72x72.png',
                tag: data.tag || 'default',
                requireInteraction: false,
                actions: data.actions || []
            })
        );
    }
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    console.log('🔔 Notification clicked:', event.notification.tag);

    event.notification.close();

    event.waitUntil(
        self.clients.openWindow('/')
    );
});

// Message handling for communication with main thread
self.addEventListener('message', event => {
    console.log('📨 Message received:', event.data);

    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: CACHE_NAME });
    }

    if (event.data && event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(clearAllCaches());
    }
});

async function clearAllCaches() {
    const cacheNames = await caches.keys();
    return Promise.all(
        cacheNames.map(cacheName => caches.delete(cacheName))
    );
}

console.log('🏁 BOSTARTER Service Worker loaded successfully');