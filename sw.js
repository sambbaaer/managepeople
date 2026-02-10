const CACHE_NAME = 'managpeople-v2';
const SHELL_ASSETS = [
    'assets/css/variables.css',
    'assets/css/style.css',
    'assets/images/favicon.png',
    'assets/images/icon.svg'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    // Network-first for HTML/PHP pages, cache-first for static assets
    if (e.request.mode === 'navigate') {
        e.respondWith(
            fetch(e.request).catch(() => caches.match('index.php'))
        );
    } else {
        e.respondWith(
            caches.match(e.request).then((cached) => cached || fetch(e.request))
        );
    }
});
