/*
 * SIMPEL DINSOS — Service Worker
 *
 * Strategi:
 *   - precache shell statis (manifest, ikon, halaman offline)
 *   - network-first untuk halaman HTML (dengan fallback ke offline.html)
 *   - cache-first untuk asset Vite (CSS/JS hash di nama)
 *   - jangan cache POST/API (selalu live)
 */
const CACHE_VERSION = 'v1.2026.05.21';
const SHELL_CACHE = `simpel-shell-${CACHE_VERSION}`;
const ASSETS_CACHE = `simpel-assets-${CACHE_VERSION}`;

const SHELL_URLS = [
  '/',
  '/layanan',
  '/cek-status',
  '/pengaduan',
  '/offline',
  '/manifest.webmanifest',
  '/icons/icon-192.svg',
  '/icons/icon-512.svg',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(SHELL_CACHE).then((cache) => cache.addAll(SHELL_URLS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((k) => ![SHELL_CACHE, ASSETS_CACHE].includes(k))
            .map((k) => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Hanya GET yang di-cache
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // Endpoint API & webhook: selalu live
  if (url.pathname.startsWith('/api/') ||
      url.pathname.startsWith('/webhook/') ||
      url.pathname.startsWith('/admin/') ||
      url.pathname.startsWith('/livewire/') ||
      url.pathname.startsWith('/filament/') ||
      url.pathname.startsWith('/tv/live') ||
      url.pathname.startsWith('/tv/debug')) {
    // Live data — JANGAN di-cache, langsung passthrough ke network
    return;
  }

  // Asset build: cache-first (hash di nama → aman)
  if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
    event.respondWith(cacheFirst(req, ASSETS_CACHE));
    return;
  }

  // Halaman HTML: network-first, fallback cache, fallback offline
  if (req.headers.get('accept')?.includes('text/html')) {
    event.respondWith(networkFirst(req));
    return;
  }

  // Default: network-first dengan cache fallback
  event.respondWith(
    fetch(req).catch(() => caches.match(req))
  );
});

async function cacheFirst(req, cacheName) {
  const cached = await caches.match(req);
  if (cached) return cached;
  const res = await fetch(req);
  if (res.ok) {
    const cache = await caches.open(cacheName);
    cache.put(req, res.clone());
  }
  return res;
}

async function networkFirst(req) {
  try {
    const res = await fetch(req);
    if (res.ok) {
      const cache = await caches.open(SHELL_CACHE);
      cache.put(req, res.clone());
    }
    return res;
  } catch (err) {
    const cached = await caches.match(req);
    if (cached) return cached;
    return caches.match('/offline');
  }
}

// Push notification stub — produksi pakai Web Push + VAPID
self.addEventListener('push', (event) => {
  const data = event.data?.json() ?? { title: 'SIMPEL DINSOS', body: 'Update baru' };
  event.waitUntil(
    self.registration.showNotification(data.title || 'SIMPEL DINSOS', {
      body: data.body || '',
      icon: '/icons/icon-192.svg',
      badge: '/icons/icon-192.svg',
      data: data.url || '/',
      vibrate: [100, 50, 100],
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    self.clients.matchAll({ type: 'window' }).then((wins) => {
      const url = event.notification.data || '/';
      const existing = wins.find((w) => w.url.includes(url));
      if (existing) return existing.focus();
      return self.clients.openWindow(url);
    })
  );
});
