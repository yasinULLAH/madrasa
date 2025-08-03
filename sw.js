const isGithub = self.location.hostname.includes('github.io');
const BASE = isGithub ? '/' : './';
const CACHE_NAME = 'smart-auto-cache-v1';

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(c => c.add(BASE))
  );
});

self.addEventListener('fetch', e => {
  e.respondWith(
    caches.match(e.request).then(res => {
      return res || fetch(e.request).then(r => {
        if (!e.request.url.startsWith('http')) return r;
        const rClone = r.clone();
        caches.open(CACHE_NAME).then(c => c.put(e.request, rClone));
        return r;
      }).catch(() => caches.match(BASE));
    })
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.map(k => (k !== CACHE_NAME) && caches.delete(k)))
    )
  );
});