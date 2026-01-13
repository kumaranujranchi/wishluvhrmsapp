// Service Worker for HRMS PWA
const CACHE_NAME = 'hrms-v1.0.2';
const OFFLINE_URL = '/offline.html';

// Files to cache
const STATIC_CACHE = [
    '/',
    '/index.php',
    '/attendance_view.php',
    '/leave_apply.php',
    '/view_holidays.php',
    '/assets/css/style.css',
    '/offline.html',
    '/manifest.json'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Caching static assets');
            return cache.addAll(STATIC_CACHE);
        }).then(() => {
            return self.skipWaiting();
        })
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from network first for dynamic content, cache first for static assets
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    const isPhpRequest = url.pathname.endsWith('.php') || url.pathname === '/';

    // Skip non-GET requests and logout page
    if (event.request.method !== 'GET' || url.pathname.includes('logout.php')) {
        return;
    }

    if (isPhpRequest) {
        // Network First Strategy for dynamic PHP pages
        event.respondWith(
            fetch(event.request).then((response) => {
                // Cache the latest version of the page
                if (response && response.status === 200) {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return response;
            }).catch(() => {
                // If offline, serve from cache
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return caches.match(OFFLINE_URL);
                });
            })
        );
    } else {
        // Cache First Strategy for static assets (images, css, js, fonts)
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then((response) => {
                    // Cache static assets that were not in the initial STATIC_CACHE
                    if (response && response.status === 200) {
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseToCache);
                        });
                    }
                    return response;
                });
            })
        );
    }
});

// Background sync for attendance (future enhancement)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-attendance') {
        event.waitUntil(syncAttendance());
    }
});

async function syncAttendance() {
    // Placeholder for syncing offline attendance data
    console.log('[SW] Syncing attendance data...');
}
