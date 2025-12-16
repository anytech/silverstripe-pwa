/**
 * SilverStripe PWA Service Worker
 * Implements configurable caching strategies with push notification support
 * Version: 2.1.0
 */

const CACHE_VERSION = '$CacheVersion';
const CACHE_NAME = 'pwa-cache-' + CACHE_VERSION;
const OFFLINE_CACHE = 'offline-cache';
const DEBUG = <% if $DebugMode %>true<% else %>false<% end_if %>;
const BASE_URL = '$BaseUrl';
const CACHE_STRATEGY = '$CacheStrategy';
const OFFLINE_ENABLED = <% if $OfflineModeEnabled %>true<% else %>false<% end_if %>;
const PUSH_ENABLED = <% if $PushNotificationsEnabled %>true<% else %>false<% end_if %>;
const CACHE_MAX_AGE = $CacheMaxAge;

// Custom URLs to pre-cache
const CUSTOM_PRECACHE = $PrecacheUrls.RAW;

// URL patterns to exclude from caching
const EXCLUDE_PATTERNS = $ExcludeUrlPatterns.RAW;

// Notification action buttons
const NOTIFICATION_ACTIONS = $NotificationActions.RAW;

// Assets to pre-cache during installation
const PRECACHE_ASSETS = OFFLINE_ENABLED
    ? [BASE_URL + 'offline.html', ...CUSTOM_PRECACHE]
    : [...CUSTOM_PRECACHE];

// Log helper for debug mode
const log = (...args) => DEBUG && console.log('[SW]', ...args);

/**
 * Check if URL should be excluded from caching
 */
function shouldExclude(url) {
    const urlPath = new URL(url).pathname;

    // Always exclude admin and dev routes
    if (urlPath.startsWith('/admin') || urlPath.startsWith('/dev') || urlPath.startsWith('/Security')) {
        return true;
    }

    // Check custom exclude patterns
    for (const pattern of EXCLUDE_PATTERNS) {
        if (pattern.includes('*')) {
            const regex = new RegExp('^' + pattern.split('*').join('.*') + '$');
            if (regex.test(urlPath)) {
                return true;
            }
        } else if (urlPath === pattern || urlPath.startsWith(pattern)) {
            return true;
        }
    }

    return false;
}

/**
 * Install Event
 * Pre-cache essential assets for offline support
 */
self.addEventListener('install', (event) => {
    log('Installing service worker...');

    event.waitUntil(
        caches.open(OFFLINE_CACHE)
            .then((cache) => {
                log('Pre-caching assets:', PRECACHE_ASSETS);
                return cache.addAll(PRECACHE_ASSETS.filter(url => url));
            })
            .then(() => self.skipWaiting())
    );
});

/**
 * Activate Event
 * Clean up old caches when new service worker activates
 */
self.addEventListener('activate', (event) => {
    log('Activating service worker...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME && name !== OFFLINE_CACHE)
                        .map((name) => {
                            log('Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

/**
 * Fetch Event
 * Handle requests based on configured cache strategy
 */
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only handle GET requests
    if (request.method !== 'GET') return;

    // Skip non-HTTP(S) requests
    if (!request.url.startsWith('http')) return;

    // Skip cross-origin requests - let browser handle them directly
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    // Skip excluded URLs
    if (shouldExclude(request.url)) {
        log('Excluding from cache:', request.url);
        return;
    }

    event.respondWith(handleFetch(request));
});

/**
 * Handle fetch based on cache strategy
 */
async function handleFetch(request) {
    switch (CACHE_STRATEGY) {
        case 'cache-first':
            return cacheFirst(request);
        case 'network-only':
            return networkOnly(request);
        case 'stale-while-revalidate':
            return staleWhileRevalidate(request);
        case 'network-first':
        default:
            return networkFirst(request);
    }
}

/**
 * Network First Strategy
 * Try network, fall back to cache, then offline page
 */
async function networkFirst(request) {
    try {
        const response = await fetch(request);

        // Cache successful responses
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        log('Network failed, trying cache:', request.url);

        const cached = await caches.match(request);
        if (cached) return cached;

        // For navigation requests, show offline page
        if (OFFLINE_ENABLED && request.mode === 'navigate') {
            return caches.match(BASE_URL + 'offline.html');
        }

        throw error;
    }
}

/**
 * Cache First Strategy
 * Try cache, fall back to network
 */
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) {
        log('Cache hit:', request.url);
        return cached;
    }

    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        if (OFFLINE_ENABLED && request.mode === 'navigate') {
            return caches.match(BASE_URL + 'offline.html');
        }
        throw error;
    }
}

/**
 * Stale While Revalidate Strategy
 * Return cache immediately, update cache in background
 */
async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);

    const fetchPromise = fetch(request).then((response) => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch((error) => {
        log('Background fetch failed:', request.url);
        return null;
    });

    if (cached) {
        log('Returning stale:', request.url);
        return cached;
    }

    const response = await fetchPromise;
    if (response) return response;

    if (OFFLINE_ENABLED && request.mode === 'navigate') {
        return caches.match(BASE_URL + 'offline.html');
    }

    throw new Error('Network unavailable');
}

/**
 * Network Only Strategy
 * Always fetch from network, with offline fallback
 */
async function networkOnly(request) {
    try {
        return await fetch(request);
    } catch (error) {
        if (OFFLINE_ENABLED && request.mode === 'navigate') {
            return caches.match(BASE_URL + 'offline.html');
        }
        throw error;
    }
}

<% if $PushNotificationsEnabled %>
/**
 * Push Event
 * Handle incoming push notifications
 */
self.addEventListener('push', (event) => {
    if (!PUSH_ENABLED) return;

    log('Push notification received');

    let data = {
        title: 'New Notification',
        message: '',
        icon: '',
        badge: '',
        url: '/',
        tag: 'default',
        vibrate: [200, 100, 200],
        requireInteraction: false,
        silent: false,
        renotify: false
    };

    try {
        if (event.data) {
            const payload = event.data.json();
            data = { ...data, ...payload };
        }
    } catch (e) {
        log('Error parsing push data:', e);
    }

    const options = {
        body: data.message,
        icon: data.icon,
        badge: data.badge,
        tag: data.tag,
        vibrate: data.silent ? [] : data.vibrate,
        silent: data.silent,
        renotify: data.renotify,
        requireInteraction: data.requireInteraction,
        data: {
            url: data.url,
            ...data.data
        }
    };

    // Add action buttons if configured
    if (NOTIFICATION_ACTIONS.length > 0) {
        options.actions = NOTIFICATION_ACTIONS.map(action => ({
            action: action.action,
            title: action.title
        }));
    }

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

/**
 * Notification Click Event
 * Handle user interaction with notifications
 */
self.addEventListener('notificationclick', (event) => {
    log('Notification clicked:', event.action);

    event.notification.close();

    let urlToOpen = event.notification.data?.url || '/';

    // Handle action buttons
    if (event.action && NOTIFICATION_ACTIONS.length > 0) {
        const action = NOTIFICATION_ACTIONS.find(a => a.action === event.action);
        if (action && action.url) {
            urlToOpen = action.url;
        } else if (event.action === 'action2' && !action?.url) {
            // If action 2 has no URL, just dismiss (don't open anything)
            return;
        }
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if there's already a window open with this URL
                for (const client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }

                // Open a new window if none exists
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});
<% end_if %>

/**
 * Message Event
 * Handle messages from the main thread
 */
self.addEventListener('message', (event) => {
    log('Message received:', event.data);

    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data?.type === 'CLEAR_CACHE') {
        caches.keys().then((names) => {
            names.forEach((name) => caches.delete(name));
        });
    }

    if (event.data?.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: CACHE_VERSION });
    }
});
