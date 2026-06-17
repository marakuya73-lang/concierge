const CACHE_NAME = 'domo-xango-v4';
const APP_SHELL = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/images/logo.png',
    '/images/favicon.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) =>
            Promise.allSettled(APP_SHELL.map((url) => cache.add(url)))
        )
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
    const { type, url, urls } = event.data || {};

    if (type === 'SKIP_WAITING') {
        self.skipWaiting();
        return;
    }

    if (type === 'CACHE_STAY' && url) {
        event.waitUntil(cacheUrls([url]));
        return;
    }

    if (type === 'CACHE_URLS' && Array.isArray(urls) && urls.length) {
        event.waitUntil(cacheUrls(urls));
    }
});

async function cacheUrls(urls) {
    const cache = await caches.open(CACHE_NAME);
    await Promise.allSettled(urls.map((path) => cache.add(path)));
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET') return;
    if (url.origin !== self.location.origin) return;

    if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/build/')) {
        event.respondWith(staleWhileRevalidate(request, event));
        return;
    }

    if (
        url.pathname.startsWith('/uploads/')
        || url.pathname.startsWith('/images/')
        || url.pathname.startsWith('/icons/')
    ) {
        event.respondWith(staleWhileRevalidate(request, event));
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
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        }
        return response;
    } catch {
        return new Response('Offline', { status: 503, statusText: 'Offline' });
    }
}

async function staleWhileRevalidate(request, event) {
    const cached = await caches.match(request);

    const network = fetch(request)
        .then((response) => {
            if (response.ok) {
                const clone = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
            }
            return response;
        })
        .catch(() => null);

    if (cached) {
        event.waitUntil(network);
        return cached;
    }

    const response = await network;
    return response || new Response('Offline', { status: 503, statusText: 'Offline' });
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
            const offline = await caches.match('/offline.html');
            if (offline) return offline;

            const home = await caches.match('/');
            if (home) return home;
        }

        return new Response('Offline', { status: 503, statusText: 'Offline' });
    }
}

async function networkOnly(request) {
    return fetch(request);
}

self.addEventListener('push', (event) => {
    let data = {
        title: 'Domo Xangô',
        body: 'Nova solicitação de extra',
        url: '/admin',
        tag: 'extra-request',
    };

    try {
        if (event.data) {
            data = { ...data, ...event.data.json() };
        }
    } catch {
        // use defaults
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            vibrate: [200, 100, 200, 100, 400],
            tag: data.tag,
            renotify: true,
            requireInteraction: true,
            data: { url: data.url },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/admin';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});
