const CACHE = 'oficina-v1';
const ASSETS = ['/assets/css/app.css', '/assets/js/app.js', '/assets/img/logo-oficina.png'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET' || !e.request.url.includes('/assets/')) return;
    e.respondWith(
        caches.match(e.request).then((r) => r || fetch(e.request).then((res) => {
            const copy = res.clone();
            caches.open(CACHE).then((c) => c.put(e.request, copy));
            return res;
        }))
    );
});
