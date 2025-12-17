let isSubscribed = false;
let swRegistration = null;
const applicationKey = "{$PublicKey}";
const debug = <% if $DebugMode %>true<% else %>false<% end_if %>;
const baseURL = "{$BaseUrl}";

// Console.log proxy for quick enabling/disabling
function log(...args) {
    if (debug) {
        console.log('[PWA]', ...args);
    }
}

// Installing service worker
if ('serviceWorker' in navigator && 'PushManager' in window) {
    log('Service Worker and Push is supported');
    navigator.serviceWorker.register('service-worker.js')
        .then(function (swReg) {
            log('service worker registered');

            swRegistration = swReg;

            swRegistration.pushManager.getSubscription()
                .then(function (subscription) {
                    isSubscribed = !(subscription === null);

                    if (isSubscribed) {
                        log('User is already subscribed');
                        // Always sync with server to update member link if logged in
                        saveSubscription(subscription);
                    } else {
                        // Check current permission state
                        if (Notification.permission === 'denied') {
                            log('Notifications are blocked by user');
                            return;
                        }

                        // Request permission first if not granted
                        if (Notification.permission === 'default') {
                            Notification.requestPermission().then(function(permission) {
                                if (permission === 'granted') {
                                    subscribeToPush();
                                } else {
                                    log('Notification permission denied');
                                }
                            });
                        } else if (Notification.permission === 'granted') {
                            subscribeToPush();
                        }
                    }
                })
        })
        .catch(function (error) {
            if (debug) console.error('[PWA] Service Worker Error', error);
        });
} else {
    if (debug) console.warn('[PWA] Push messaging is not supported');
}

// Subscribe to push notifications
function subscribeToPush() {
    swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlB64ToUint8Array(applicationKey)
    })
    .then(function (subscription) {
        log('User is subscribed');
        saveSubscription(subscription);
        isSubscribed = true;
    })
    .catch(function (err) {
        log('Failed to subscribe user: ' + err);
    });
}

// Save the subscription to the database via POST-request
function saveSubscription(subscription) {
    const key = subscription.getKey('p256dh');
    const token = subscription.getKey('auth');
    const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

    return fetch(baseURL + "RegisterSubscription", {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            endpoint: subscription.endpoint,
            publicKey: key ? arrayBufferToBase64Url(key) : null,
            authToken: token ? arrayBufferToBase64Url(token) : null,
            contentEncoding,
        }),
    }).then(() => subscription);
}

// Convert ArrayBuffer to Base64URL string
function arrayBufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).split('+').join('-').split('/').join('_').split('=').join('');
}

// Base64 encryption
function urlB64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
