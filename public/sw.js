/*
 * VokaTrack Service Worker — PWA Siswa
 *
 * Strategi:
 *  - Halaman siswa (GET /siswa/*)  : network-first dengan batas waktu, cadangan dari cache,
 *                                    lalu /offline.html. Hanya respons 200 HTML non-redirect yang disimpan.
 *  - Aset build Vite (/build/*)    : cache-first (nama berkas sudah ber-hash).
 *  - Ikon, logo, manifest, offline : cache-first (precache saat install).
 *  - Google Fonts                  : stale-while-revalidate.
 *  - POST (presensi, jurnal, dll.) : TIDAK pernah di-cache. Navigasi POST yang gagal karena
 *                                    offline diarahkan ke /offline.html, bukan halaman error browser.
 *  - Berkas privat (/berkas/*), API, unduhan PDF: selalu langsung ke jaringan.
 *
 * Naikkan VERSION setiap kali isi berkas ini atau daftar PRECACHE berubah.
 */
const VERSION = 'v4';
const STATIC_CACHE = `vt-static-${VERSION}`;
const PAGES_CACHE = `vt-pages-${VERSION}`;
const FONTS_CACHE = `vt-fonts-${VERSION}`;
const OFFLINE_URL = '/offline.html';
const NETWORK_TIMEOUT_MS = 6000;

const PRECACHE = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/manifest-guru.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
    '/images/logo.png',
];

// Halaman siswa yang aman disimpan untuk dibuka tanpa sinyal.
const CACHEABLE_PAGE = /^\/siswa\/(beranda|absensi|jurnal|profil|pengumuman|kendala)\/?$/;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    const keep = [STATIC_CACHE, PAGES_CACHE, FONTS_CACHE];
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('vt-') && !keep.includes(k)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    const type = event.data && event.data.type;
    if (type === 'clear-pages') {
        event.waitUntil(caches.delete(PAGES_CACHE));
    }
});

// ---------------------------------------------------------------------------
// Web Push Notifications (Android Chrome/Edge/Samsung Internet & iOS 16.4+ PWA)
// ---------------------------------------------------------------------------

self.addEventListener('push', (event) => {
    let payload = {};
    if (event.data) {
        try {
            payload = event.data.json();
        } catch (e) {
            payload = { title: 'VokaTrack PKL', body: event.data.text() };
        }
    }

    const title = payload.title || 'VokaTrack PKL';
    const targetUrl = (payload.data && payload.data.url) || payload.url || '/siswa/beranda';
    const options = {
        body: payload.body || 'Ada pembaruan baru di aplikasi PKL Anda.',
        icon: payload.icon || '/images/logo.png',
        badge: payload.badge || '/images/logo.png',
        tag: payload.tag || (payload.data && payload.data.tag) || 'vokatrack-notif',
        renotify: payload.renotify !== false,
        requireInteraction: Boolean(payload.requireInteraction),
        vibrate: Array.isArray(payload.vibrate) ? payload.vibrate : [150, 80, 150],
        actions: Array.isArray(payload.actions) ? payload.actions : [],
        data: {
            url: targetUrl,
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const rawUrl = (event.notification.data && event.notification.data.url) || '/siswa/beranda';
    const targetUrl = new URL(rawUrl, self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    if ('navigate' in client && client.url !== targetUrl) {
                        return client.navigate(targetUrl).then((navClient) => (navClient || client).focus());
                    }
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Navigasi POST (submit form presensi/jurnal) — jangan cache, tapi tampilkan halaman offline bila gagal.
    if (request.method !== 'GET') {
        if (request.mode === 'navigate') {
            event.respondWith(fetch(request).catch(() => offlineFallback()));
        }
        return;
    }

    if (url.origin === self.location.origin) {
        if (request.mode === 'navigate') {
            if (CACHEABLE_PAGE.test(url.pathname)) {
                event.respondWith(networkFirstPage(event, request));
            } else {
                event.respondWith(fetch(request).catch(() => offlineFallback()));
            }
            return;
        }

        if (url.pathname.startsWith('/build/') || PRECACHE.includes(url.pathname) || url.pathname.startsWith('/icons/')) {
            event.respondWith(cacheFirst(request, STATIC_CACHE));
        }
        return;
    }

    if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
        event.respondWith(staleWhileRevalidate(event, request, FONTS_CACHE));
    }
});

async function networkFirstPage(event, request) {
    const cache = await caches.open(PAGES_CACHE);
    // Abaikan query string (?source=pwa, ?page=2) agar satu salinan per halaman.
    const cacheKey = new URL(request.url);
    cacheKey.search = '';

    const network = fetch(request).then((response) => {
        if (isCacheablePage(response)) {
            event.waitUntil(cache.put(cacheKey.href, response.clone()));
        }
        return response;
    });

    try {
        return await withTimeout(network, NETWORK_TIMEOUT_MS);
    } catch (e) {
        const cached = await cache.match(cacheKey.href);
        if (cached) {
            // Tetap biarkan permintaan jaringan selesai untuk memperbarui cache di latar.
            event.waitUntil(network.catch(() => {}));
            return cached;
        }
        try {
            return await network;
        } catch (e2) {
            return offlineFallback();
        }
    }
}

function isCacheablePage(response) {
    return response
        && response.status === 200
        && !response.redirected
        && response.type === 'basic'
        && (response.headers.get('Content-Type') || '').includes('text/html');
}

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (response && response.ok) {
        cache.put(request, response.clone());
    }
    return response;
}

async function staleWhileRevalidate(event, request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response && (response.ok || response.type === 'opaque')) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => cached);
    if (cached) {
        event.waitUntil(network);
        return cached;
    }
    return network;
}

async function offlineFallback() {
    const cached = await caches.match(OFFLINE_URL);
    return cached || new Response('Tidak ada koneksi internet.', {
        status: 503,
        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
    });
}

function withTimeout(promise, ms) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('timeout')), ms);
        promise.then(
            (value) => { clearTimeout(timer); resolve(value); },
            (err) => { clearTimeout(timer); reject(err); }
        );
    });
}
