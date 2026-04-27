# SilverStripe PWA Module

Progressive Web App and unified push notifications module for SilverStripe 4, 5, and 6. Adds installable app capabilities, offline support, web push **and** native mobile push (Expo / React Native) notifications, all manageable from the CMS.

## Features

- **Web App Manifest** — dynamically generated manifest, fully configurable in the CMS
- **Service Worker** — offline-first strategy with customizable caching
- **Web Push** — native PHP Web Push (no third-party libs)
- **Native Mobile Push** — first-class support for Expo Push (React Native apps) alongside web push
- **Unified Subscriber Model** — one `Subscriber` table for both web and mobile, polymorphic by `Type`
- **Push Announcements** — CMS-managed `PushAnnouncement` records with one-click "Send" button
- **VAPID Generation** — generate keys directly from the CMS, no shell required
- **Auto-inject** — manifest link and service-worker registration injected into every page automatically (with opt-out)
- **Grouped Settings** — all PWA configuration nested under a single **PWA** tab in Settings
- **Test Mode** — gate broadcast sends to a single test member while you're tuning copy/UI

## Requirements

- PHP 8.0+
- PHP extensions: `openssl`, `curl`
- SilverStripe CMS 4.10+, 5.x, or 6.x
- HTTPS in production (required for service workers and push)

## Installation

```bash
composer require anytech/silverstripe-pwa
```

That's all. The plugin auto-injects the manifest link and service-worker registration on every page render. The deploy pipeline runs `dev/build` automatically. Visit the CMS, fill in app name/icon, generate VAPID keys, ship it.

## Configuration

All settings live under **Settings → PWA**, with sub-tabs for clarity:

- **Manifest** — app name, icons, theme color, screenshots, app shortcuts
- **Push Notifications** — VAPID keys, test mode, default content, behaviour
- **Service Worker** — master toggles, cache strategy, debug mode, auto-inject toggle
- **Offline Page** — content + styling for the offline fallback

### Master toggles (Settings → PWA → Service Worker)

- `Enable PWA` — master switch; everything depends on this
- `Enable Service Worker` — caching + offline support
- `Enable Offline Mode` — fallback page when network fails
- `Enable Push Notifications` — allow subscriptions
- `Auto-inject PWA Assets` — automatically inject manifest + SW registration on every page (default ON; disable if your theme is wiring these manually)

### VAPID keys

Settings → PWA → Push Notifications shows a prominent warning if VAPID keys aren't set. Click **Generate VAPID Keys**, save, done. Keys are stored on `SiteConfig` and used by `WebPushService` for signing payloads.

## Web Push

Web subscribers are auto-collected by the included `RegisterServiceWorker.js`. When a visitor grants notification permission, the script POSTs to `/RegisterSubscription`, which creates a `Subscriber` row of `Type='web'` linked to the current member if logged in.

## Native Mobile Push (Expo)

For React Native apps using Expo, the plugin accepts Expo push tokens at `/RegisterMobileSubscription`:

```bash
POST /RegisterMobileSubscription
Content-Type: application/json
{
    "token": "ExponentPushToken[xxxxxxxxxxxx]",
    "platform": "ios"  // or "android"
}
```

Subscribers from this endpoint are stored as `Type='expo'` in the same `Subscriber` table. `PushNotificationService` automatically detects subscriber type and routes to either `WebPushService` (W3C Push API) or `ExpoPushService` (`exp.host/--/api/v2/push/send`) — your sending code stays the same.

The endpoint resolves the member from the active session. If your app uses a custom auth scheme (e.g. bearer tokens), implement your own thin endpoint that creates `Subscriber` records directly using the `MemberID` from your auth check.

### Expo App Side

```bash
npx expo install expo-notifications expo-device
```

```ts
import * as Notifications from 'expo-notifications';
import Constants from 'expo-constants';
import { Platform } from 'react-native';

async function registerPushToken() {
    const settings = await Notifications.getPermissionsAsync();
    if (!settings.granted) {
        const req = await Notifications.requestPermissionsAsync();
        if (!req.granted) return;
    }
    const projectId = Constants.expoConfig?.extra?.eas?.projectId;
    const { data: token } = await Notifications.getExpoPushTokenAsync({ projectId });
    await fetch('https://yoursite.com/RegisterMobileSubscription', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, platform: Platform.OS }),
    });
}
```

## Push Announcements

CMS users can compose and send broadcast push notifications without touching code:

1. CMS sidebar → **Push Notifications** → **Announcements** → Add
2. Fill in Title, Message, optional Click URL
3. Save → click **Send Push Notification**
4. Status flips to `Sent`, recipient count is recorded

Sent announcements are read-only for an audit trail. The Subscribers tab in the same admin shows every subscriber (web + mobile) with their type and platform.

## Sending from Code

Use `PushNotificationService` to dispatch from anywhere — it fans out across web AND native subscribers automatically:

```php
use SilverStripePWA\Services\PushNotificationService;

// Broadcast to all subscribers (web + mobile)
PushNotificationService::notify('New Update', 'Check out the latest features!');

// Specific member — covers all their devices/browsers
$member = Member::get()->byID(123);
PushNotificationService::notifyMember($member, 'Hello!', 'You have a new message');

// Multiple members
$members = Member::get()->filter('Groups.Code', 'staff');
PushNotificationService::notifyMembers($members, 'Team Update', 'New task assigned');

// Fluent API for full control
PushNotificationService::create()
    ->setTitle('Order Shipped')
    ->setBody('Your order #1234 has been shipped!')
    ->setUrl('/account/orders/1234')
    ->setTag('order-1234')
    ->setData(['orderId' => 1234])
    ->sendToMember($member);
```

### Page-publish trigger

Add `PushPageExtension` to any page type. Editors get a "Send Push Notification" checkbox; ticking it before publishing fans out a notification to all subscribers.

```yaml
# app/_config/config.yml
Page:
  extensions:
    - SilverStripePWA\Extensions\PushPageExtension
```

## Test Mode

Settings → PWA → Push Notifications → **Enable Test Mode** restricts broadcasts to a single configured test member. Useful while iterating on copy/UI without spamming subscribers.

The "Send Test Push" button in Settings always targets the test user — independent of whether test mode is enabled.

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/manifest.json` | GET | Web app manifest |
| `/service-worker.js` | GET | Service worker script |
| `/RegisterServiceWorker.js` | GET | Service worker registration script |
| `/offline.html` | GET | Offline fallback page |
| `/RegisterSubscription` | POST | Register web push subscription |
| `/RegisterMobileSubscription` | POST | Register Expo mobile push token |
| `/pwa-generate-vapid-keys` | GET | Generate VAPID keys (admin only) |
| `/pwa-send-test-push` | GET | Send test push to test user (admin only) |

## Browser & Platform Support

| Surface | Status |
|---|---|
| Chrome / Edge | Full web push, install prompt, badging |
| Firefox | Full web push |
| Safari (desktop / iOS 16.4+) | Web push only when "Add to Home Screen" |
| Android (native via Expo) | Full Expo push |
| iOS (native via Expo) | Full Expo push (requires Apple Developer account) |

## Subscriber Schema

```
Subscriber
├── Type           Enum('web','expo')
├── Platform       Enum('ios','android', null)   // native only
├── endpoint       Text                           // URL for web, token for expo
├── publicKey      Text                           // web only
├── authToken      Text                           // web only
├── contentEncoding Text                          // web only
└── MemberID       Int                            // optional, links to Member
```

The same `PushNotificationService` call routes a notification to the right pipe based on `Type`. Sites can install the plugin and use it for web only, mobile only, or both — the routing is transparent.

## Troubleshooting

### Auto-inject is enabled but the script tag isn't on the page
Confirm both `Enable PWA` and `Auto-inject PWA Assets` are ticked in Settings → PWA → Service Worker. Defaults only apply on a fresh `SiteConfig` row — sites upgrading from earlier plugin versions need to tick them once after install.

### "VAPID keys not configured" warning won't go away
Click **Generate VAPID Keys** in Settings → PWA → Push Notifications. Don't paste keys generated elsewhere unless you know the key format matches what `WebPushService` expects.

### Mobile token registers but no notifications arrive
- For Expo: confirm the token is `ExponentPushToken[…]` not a raw FCM/APNS token
- iOS native push needs a paid Apple Developer account configured with EAS — Expo Go won't deliver
- Check the `pwa-debug.log` (project root) when **Service Worker Debug Mode** is on — `ExpoPushService` writes its HTTP responses there

### Service worker won't unregister
DevTools → Application → Service Workers can fail. Try the console nuke instead:
```js
navigator.serviceWorker.getRegistrations().then(rs => rs.forEach(r => r.unregister()));
```

## Author

**Kayne Middleton** — [Anytech](https://anytech.ca) — kayne@anytech.ca

## License

MIT — see [LICENSE](LICENSE).
