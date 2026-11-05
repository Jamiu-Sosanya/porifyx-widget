const CACHE_VERSION = 'v2';
const APP_SHELL_CACHE  = `app-shell-${CACHE_VERSION}`;
const DYNAMIC_CACHE    = `dynamic-${CACHE_VERSION}`;
const OFFLINE_URL      = '/offline';

// Assets to pre-cache on install (app shell)
const APP_SHELL_ASSETS = [
    '/',
    '/offline',
    '/manifest.json',
    '/images/icon-192x192.png',
    '/images/icon-512x512.png',
];

// ─── Install: pre-cache app shell ────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(APP_SHELL_CACHE)
            .then(cache => cache.addAll(APP_SHELL_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// ─── Activate: purge old caches ───────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(key => key !== APP_SHELL_CACHE && key !== DYNAMIC_CACHE)
                    .map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// ─── Fetch: network-first with cache fallback ────────────────────────────────
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET, cross-origin, and API/broadcast requests
    if (
        request.method !== 'GET' ||
        url.origin !== self.location.origin ||
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/broadcasting/')
    ) {
        return;
    }

    // For navigation requests: network-first, fallback to offline page
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(DYNAMIC_CACHE).then(cache => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() =>
                    caches.match(request)
                        .then(cached => cached || caches.match(OFFLINE_URL))
                )
        );
        return;
    }

    // For static assets: cache-first
    if (
        url.pathname.match(/\.(css|js|png|jpg|jpeg|svg|gif|woff2?|ttf|ico)$/)
    ) {
        event.respondWith(
            caches.match(request).then(cached => {
                if (cached) return cached;
                return fetch(request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(APP_SHELL_CACHE).then(cache => cache.put(request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // For everything else: network-first, cache as fallback
    event.respondWith(
        fetch(request)
            .then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(DYNAMIC_CACHE).then(cache => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => caches.match(request))
    );
});

// ─── Push: show notification ──────────────────────────────────────────────────
self.addEventListener('push', event => {
    let data = { title: 'PoriFyx Widget', message: 'You have a new notification', url: '/dashboard' };

    try {
        if (event.data) {
            data = { ...data, ...event.data.json() };
        }
    } catch (e) {
        if (event.data) data.message = event.data.text();
    }

    const options = {
        body: data.message,
        icon: '/images/icon-192x192.png',
        badge: '/images/icon-192x192.png',
        vibrate: [100, 50, 100],
        data: { url: data.url || '/dashboard' },
        actions: [
            { action: 'open',    title: 'Open Dashboard' },
            { action: 'dismiss', title: 'Dismiss' }
        ],
        requireInteraction: data.requireInteraction || false,
        tag: data.tag || 'default',
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// ─── Notification click: focus or open the app ────────────────────────────────
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'dismiss') return;

    const targetUrl = event.notification.data?.url || '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            // Focus an existing window if open
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            // Otherwise open a new window
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// ─── Message: skip waiting on demand ─────────────────────────────────────────
self.addEventListener('message', event => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
