# SilverStripe PWA Module

A zero-dependency Progressive Web App (PWA) module for SilverStripe 4 and 5. Add installable app capabilities, offline support, and push notifications to your SilverStripe website.

## Features

- **Web App Manifest** - Dynamically generated manifest with full CMS configuration
- **Service Worker** - Offline-first strategy with customizable caching
- **Push Notifications** - Native PHP Web Push implementation (no external libraries)
- **VAPID Key Generation** - Generate keys directly from the CMS
- **Installable** - Make your site installable on mobile and desktop
- **Modern Standards** - Follows 2025 PWA best practices
- **Zero Dependencies** - Only requires SilverStripe and PHP extensions

## Requirements

- PHP 8.0+
- PHP Extensions: `openssl`, `curl`
- SilverStripe CMS 4.10+ or 5.x
- HTTPS (required for service workers and push notifications)

## Installation

```bash
composer require anytech/silverstripe-pwa
```

After installation, run the database migration:

```bash
vendor/bin/sake dev/build flush=1
```

## Quick Start

### 1. Add PWA Meta Tags

Add the following to your page template's `<head>` section (typically `Page.ss` or `Header.ss`):

```html
<meta name="theme-color" content="$SiteConfig.ManifestColor">
<link rel="manifest" href="{$BaseHref}manifest.json">
<script src="{$BaseHref}RegisterServiceWorker.js"></script>
```

### 2. Configure in CMS

Navigate to **Settings > Manifest** in the SilverStripe CMS to configure:

#### Core Settings
- **App Name** - Full name displayed in install prompts
- **Short Name** - Name shown on home screen (max 12 characters)
- **Description** - App description for store listings

#### Appearance
- **Theme Color** - Browser UI color (address bar, status bar)
- **Background Color** - Splash screen background
- **Display Mode** - How the app appears (standalone recommended)
- **Orientation** - Screen orientation preference

#### Icons
- **App Icon** - Square PNG/SVG, minimum 512x512px
- **Maskable Icon** - Adaptive icon for Android with safe zone padding

#### Screenshots (Optional)
- **Wide Screenshot** - Desktop/tablet screenshot (landscape)
- **Narrow Screenshot** - Mobile screenshot (portrait)

### 3. Generate VAPID Keys (for Push Notifications)

VAPID keys are required for push notifications. You can generate them directly from the CMS:

1. Go to **Settings > Manifest**
2. Scroll to **Push Notification Settings**
3. Click **Generate VAPID Keys**
4. Click **Save**

That's it! No command line or external tools needed.

### 4. Configure VAPID Subject

In **Settings > Manifest**, set your **VAPID Subject** to a contact email (e.g., `mailto:admin@yoursite.com`).

## Push Notifications

### Enabling Push on Pages

To allow push notifications when publishing specific page types, add the extension in your `app/_config/config.yml`:

```yaml
App\Models\BlogPost:
  extensions:
    - SilverStripePWA\Extensions\PushPageExtension

Page:
  extensions:
    - SilverStripePWA\Extensions\PushPageExtension
```

When editing a page with this extension, you'll see a "Send Push Notification" checkbox. Checking this before publishing will send a notification to all subscribers.

### Sending Push Notifications from Code

Use `PushNotificationService` to send notifications from anywhere in your application:

```php
use SilverStripePWA\Services\PushNotificationService;

// Simple: Send to all subscribers
PushNotificationService::notify('New Update', 'Check out the latest features!');

// Send to a specific member
$member = Member::get()->byID(123);
PushNotificationService::notifyMember($member, 'Hello!', 'You have a new message');

// Send to multiple members
$members = Member::get()->filter('GroupID', 5);
PushNotificationService::notifyMembers($members, 'Team Update', 'New task assigned');

// Full control with fluent API
PushNotificationService::create()
    ->setTitle('Order Shipped')
    ->setBody('Your order #1234 has been shipped!')
    ->setUrl('/account/orders/1234')
    ->setIcon('/assets/icons/shipping.png')
    ->setTag('order-1234')  // Groups/replaces notifications with same tag
    ->setData(['orderId' => 1234])
    ->sendToMember($member);
```

### Member-Targeted Notifications

Subscribers are automatically linked to logged-in members. This enables targeted notifications:

```php
// When a user receives a message
public function onMessageReceived(Message $message)
{
    PushNotificationService::notifyMember(
        $message->Recipient(),
        'New Message',
        "From: {$message->Sender()->Name}",
        $message->Link()
    );
}

// When sending an email, also send a push
public function sendOrderConfirmation(Order $order)
{
    // Send email
    $email = Email::create()->to($order->Customer()->Email);
    $email->send();

    // Also send push notification
    PushNotificationService::notifyMember(
        $order->Customer(),
        'Order Confirmed',
        "Order #{$order->ID} has been confirmed",
        $order->Link()
    );
}
```

### Push Notification Settings

Configure push notification defaults in **Settings > PushNotifications**:
- **Message** - Default notification body text
- **TTL** - Time to live in seconds
- **Vibration Pattern** - Device vibration pattern
- **Icon** - Notification icon (512x512px)
- **Badge** - Monochrome badge icon (128x128px)

## API Endpoints

The module exposes the following endpoints:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/manifest.json` | GET | Web app manifest |
| `/service-worker.js` | GET | Service worker script |
| `/RegisterServiceWorker.js` | GET | Service worker registration script |
| `/offline.html` | GET | Offline fallback page |
| `/RegisterSubscription` | POST | Register push subscription |
| `/push` | POST | Send push notification (internal) |

## Manifest Properties

The generated manifest includes modern PWA properties:

```json
{
  "name": "Your App Name",
  "short_name": "App",
  "description": "Your app description",
  "start_url": "/",
  "id": "/",
  "display": "standalone",
  "orientation": "any",
  "theme_color": "#ffffff",
  "background_color": "#ffffff",
  "lang": "en",
  "categories": ["business"],
  "icons": [...],
  "screenshots": [...]
}
```

## Service Worker

The service worker implements:

- **Network-first strategy** - Always tries network, falls back to cache
- **Offline page** - Custom offline page when network unavailable
- **Push notifications** - Receives and displays push messages
- **Cache management** - Automatic cleanup of old caches
- **Message handling** - Skip waiting and cache clearing via postMessage

## Browser Support

| Feature | Chrome | Firefox | Safari | Edge |
|---------|--------|---------|--------|------|
| Manifest | Yes | Yes | Yes | Yes |
| Service Worker | Yes | Yes | Yes | Yes |
| Push Notifications | Yes | Yes | Limited | Yes |
| Install Prompt | Yes | No | Limited | Yes |

## Troubleshooting

### Service Worker Not Registering
- Ensure your site is served over HTTPS
- Check browser console for errors
- Verify `/service-worker.js` returns valid JavaScript

### Push Notifications Not Working
- Generate VAPID keys in Settings > Manifest
- Check that VAPID subject is configured (must be a mailto: URL)
- Ensure user has granted notification permissions
- Check browser support for Web Push

### Manifest Not Loading
- Verify `/manifest.json` returns valid JSON
- Check for CORS issues if using CDN
- Ensure app icon is uploaded and published

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This module is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Author

**Kayne Middleton**
[Anytech](https://anytech.ca)
kayne@anytech.ca

## Links

- [GitHub Repository](https://github.com/anytech/silverstripe-pwa)
- [Issue Tracker](https://github.com/anytech/silverstripe-pwa/issues)
- [PWA Documentation (MDN)](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Web App Manifest Reference](https://developer.mozilla.org/en-US/docs/Web/Manifest)
