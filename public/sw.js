const CACHE_NAME = 'domo-xango-v2';
const APP_SHELL = [
    '/',
    '/manifest.json',
    '/images/logo.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'CACHE_STAY' && event.data.url) {
        event.waitUntil(
            caches.open(CACHE_NAME).then((cache) => cache.add(event.data.url))
        );
    }
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET') return;

    if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (url.pathname === '/' || url.pathname.startsWith('/stay/')) {
        event.respondWith(networkFirst(request));
        return;
    }

    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkOnly(request));
    }
});

async function cacheFirst(request) {
    const cached = await caches.match(request);
    return cached || fetch(request).then((response) => {
        if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        }
        return response;
    });
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;
        if (request.mode === 'navigate') {
            return caches.match('/');
        }
        throw new Error('Offline');
    }
}

async function networkOnly(request) {
    return fetch(request);
}
