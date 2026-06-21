// PG Life - offline caching service worker
var CACHE_NAME = 'pglife-v1';
var STATIC_ASSETS = [
    '/home',
    '/css/common.css',
    '/css/home.css',
    '/css/bootstrap.min.css',
    '/js/jquery.js',
    '/js/bootstrap.min.js',
    '/js/common.js',
    '/img/logo.png',
    '/favicon.ico'
];

// Cache core assets on install
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(STATIC_ASSETS).catch(function() {
                // Silently skip assets that fail to cache
            });
        })
    );
    self.skipWaiting();
});

// Clean up old cache versions
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(names) {
            return Promise.all(
                names.filter(function(name) {
                    return name !== CACHE_NAME;
                }).map(function(name) {
                    return caches.delete(name);
                })
            );
        })
    );
    self.clients.claim();
});

// Serve requests: cache-first for assets, network-first for pages
self.addEventListener('fetch', function(event) {
    var url = new URL(event.request.url);

    // Skip non-GET and API calls
    if (event.request.method !== 'GET') return;
    if (url.pathname.startsWith('/api/')) return;

    // Static assets (css, js, images): try cache first
    if (url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|webp|ico|woff2?|ttf|svg)$/)) {
        event.respondWith(
            caches.match(event.request).then(function(cached) {
                if (cached) return cached;
                return fetch(event.request).then(function(response) {
                    if (response.ok) {
                        var clone = response.clone();
                        caches.open(CACHE_NAME).then(function(cache) {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                }).catch(function() {
                    return cached;
                });
            })
        );
        return;
    }

    // Pages: try network first, fall back to cache if offline
    event.respondWith(
        fetch(event.request).then(function(response) {
            if (response.ok) {
                var clone = response.clone();
                caches.open(CACHE_NAME).then(function(cache) {
                    cache.put(event.request, clone);
                });
            }
            return response;
        }).catch(function() {
            return caches.match(event.request);
        })
    );
});
