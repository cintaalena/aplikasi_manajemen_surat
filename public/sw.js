const CACHE_NAME = 'surat-fatubesi-v1';

// Aset statis yang di-precache saat install
const PRECACHE_ASSETS = [
  '/',
  '/manifest.json',
  '/images/logo.png',
  '/images/icons/icon-192x192.png',
  '/images/icons/icon-512x512.png',
];

// ── Install: precache aset utama ──────────────────────────────────
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS).catch(() => {
        // Abaikan jika beberapa aset belum tersedia
      });
    }).then(() => self.skipWaiting())
  );
});

// ── Activate: hapus cache lama ────────────────────────────────────
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((key) => key !== CACHE_NAME)
          .map((key) => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch: strategi Network-First untuk halaman, Cache-First untuk aset statis ──
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Lewati request non-GET, WebSocket, dan API calls
  if (
    request.method !== 'GET' ||
    url.pathname.startsWith('/api/') ||
    url.pathname.startsWith('/sanctum/') ||
    request.headers.get('X-Inertia') === 'true'
  ) {
    return;
  }

  // Aset statis (gambar, font, build assets): Cache-First
  if (
    url.pathname.startsWith('/images/') ||
    url.pathname.startsWith('/build/') ||
    url.pathname.match(/\.(png|jpg|jpeg|svg|gif|ico|woff2?|ttf|css|js)$/)
  ) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) return cached;
        return fetch(request).then((response) => {
          if (response && response.status === 200) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          }
          return response;
        }).catch(() => cached);
      })
    );
    return;
  }

  // Halaman navigasi: Network-First dengan fallback offline
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(() =>
          caches.match(request).then((cached) => cached || caches.match('/'))
        )
    );
  }
});
