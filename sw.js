const CACHE_NAME = 'lamgugob-static-20260920-final-fix-4';
const STATIC_ASSETS = [
  './assets/css/style.css?v=20260916.4',
  './assets/css/redesign.css?v=20260916.4',
  './assets/css/jawa-inspired.css?v=20260920.1',
  './assets/css/jawa-account.css?v=20260920.1',
  './assets/css/eye-comfort.css?v=20260920.2',
  './assets/css/navbar-fix.css?v=20260920.3',
  './assets/css/stability.css?v=20260920.4',
  './assets/js/icons.js?v=20260920.5',
  './assets/js/app.js?v=20260920.5',
  './assets/js/navbar-fit.js?v=20260920.5',
  './assets/images/icon-192.png',
  './assets/images/icon-512.png',
  './assets/images/favicon-32.png',
  './assets/images/apple-touch-icon.png',
  './assets/images/geuchik-placeholder.svg',
  './assets/images/gallery-placeholder.svg',
  './assets/images/peta-administrasi-lamgugob.jpg'
];

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .catch(() => undefined)
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

function bypassRequest(url) {
  return url.pathname.endsWith("/api.php") ||
    url.pathname.includes('/backend/') ||
    url.pathname.includes('/admin/') ||
    url.pathname.endsWith('/login.html') ||
    url.pathname.endsWith('/register.html') ||
    url.pathname.endsWith('/profile.html') ||
    url.pathname.endsWith('/manifest.php') ||
    url.pathname.endsWith('/robots.php') ||
    url.pathname.endsWith('/sitemap.php') ||
    url.pathname.endsWith('/post.php');
}

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin || bypassRequest(url)) return;
  if (request.mode === 'navigate') return;

  const isStatic = url.pathname.includes('/assets/');
  if (!isStatic) return;

  event.respondWith(
    caches.match(request).then(cached => {
      const network = fetch(request).then(response => {
        if (response && response.ok) {
          const copy = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
        }
        return response;
      });
      return cached || network;
    }).catch(() => caches.match(request))
  );
});
