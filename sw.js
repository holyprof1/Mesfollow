(function () {
    "use strict";

    const staticCacheName = "pwa-v" + new Date().getTime();
    const filesToCache = [
        '/',
    ];

    // Cache on install
    self.addEventListener("install", function (event) {
        self.skipWaiting();
        event.waitUntil(
            caches.open(staticCacheName)
                .then(function (cache) {
                    return cache.addAll(filesToCache);
                })
        );
    });

    // Clear cache on activate
    self.addEventListener("activate", function (event) {
        event.waitUntil(
            caches.keys().then(function (cacheNames) {
                return Promise.all(
                    cacheNames
                        .filter(function (cacheName) {
                            return cacheName.startsWith("pwa-") && cacheName !== staticCacheName;
                        })
                        .map(function (cacheName) {
                            return caches.delete(cacheName);
                        })
                );
            })
        );
    });

})();