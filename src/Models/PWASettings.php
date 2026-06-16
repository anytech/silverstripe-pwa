<?php

namespace SilverStripePWA\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use SilverStripe\Security\Member;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TabSet;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

/**
 * All PWA configuration (manifest, offline page, push notifications, service
 * worker) in one table, edited under the "PWA" admin. Singleton - one row.
 */
class PWASettings extends DataObject
{
    private static $table_name = 'PWASettings';
    private static $singular_name = 'PWA Settings';
    private static $plural_name = 'PWA Settings';

    private static $db = [
        // Manifest - core
        'ManifestName' => 'Varchar(255)',
        'ManifestShortName' => 'Varchar(25)',
        'ManifestDescription' => 'Text',
        'ManifestColor' => 'Varchar(7)',
        'ManifestBackgroundColor' => 'Varchar(7)',
        'ManifestOrientation' => 'Varchar',
        'ManifestDisplay' => 'Varchar',
        'ManifestId' => 'Varchar(255)',
        'ManifestScope' => 'Varchar(255)',
        'ManifestStartUrl' => 'Varchar(255)',
        'ManifestCategories' => 'Varchar(255)',
        'ManifestLang' => 'Varchar(10)',
        'ManifestDir' => 'Varchar(5)',
        'ManifestAndroidPackage' => 'Varchar(255)',
        'ManifestIOSAppStoreUrl' => 'Varchar(255)',
        'ManifestPreferRelated' => 'Boolean',

        // Push / VAPID
        'PushNotification' => 'Boolean',
        'VapidSubject' => 'Varchar(255)',
        'VapidPublicKey' => 'Varchar(255)',
        'VapidPrivateKey' => 'Varchar(255)',
        'PushTestMode' => 'Boolean',
        'PushTestEmail' => 'Varchar(255)',
        'PushDefaultTitle' => 'Varchar(255)',
        'Message' => 'Text',
        'ttl' => 'Int',
        'PushRequireInteraction' => 'Boolean',
        'PushSilent' => 'Boolean',
        'PushRenotify' => 'Boolean',
        'vibrate' => 'Text',
        'PushCustomVibrate' => 'Varchar(255)',
        'PushAction1Text' => 'Varchar(50)',
        'PushAction1Url' => 'Varchar(255)',
        'PushAction2Text' => 'Varchar(50)',
        'PushAction2Url' => 'Varchar(255)',

        // Offline page
        'OfflineTitle' => 'Varchar(255)',
        'OfflineMessage' => 'Text',
        'OfflineButtonText' => 'Varchar(100)',
        'OfflineBackgroundColor' => 'Varchar(7)',
        'OfflineTextColor' => 'Varchar(7)',
        'OfflineAccentColor' => 'Varchar(7)',
        'OfflineIcon' => 'Varchar(10)',

        // Service worker
        'PWAEnabled' => 'Boolean',
        'ServiceWorkerEnabled' => 'Boolean',
        'OfflineModeEnabled' => 'Boolean',
        'PushNotificationsEnabled' => 'Boolean',
        'AutoInjectPwaAssets' => 'Boolean',
        'CacheStrategy' => 'Varchar(50)',
        'CacheVersion' => 'Varchar(20)',
        'PrecacheUrls' => 'Text',
        'ExcludeUrlPatterns' => 'Text',
        'CacheMaxAge' => 'Int',
        'ServiceWorkerDebug' => 'Boolean',
    ];

    private static $has_one = [
        'ManifestLogo' => Image::class,
        'ManifestMaskableIcon' => Image::class,
        'ManifestScreenshotWide' => Image::class,
        'ManifestScreenshotNarrow' => Image::class,
        'icon' => Image::class,
        'badge' => Image::class,
        'PushTestMember' => Member::class,
    ];

    private static $has_many = [
        'ManifestShortcuts' => ManifestShortcut::class,
    ];

    private static $owns = [
        'ManifestLogo',
        'ManifestMaskableIcon',
        'ManifestScreenshotWide',
        'ManifestScreenshotNarrow',
        'icon',
        'badge',
    ];

    private static $defaults = [
        // Offline
        'OfflineTitle' => "You're Offline",
        'OfflineMessage' => "It looks like you've lost your internet connection. Please check your network and try again.",
        'OfflineButtonText' => 'Try Again',
        'OfflineBackgroundColor' => '#1a1a2e',
        'OfflineTextColor' => '#ffffff',
        'OfflineAccentColor' => '#e94560',
        'OfflineIcon' => '📡',
        // Service worker
        'PWAEnabled' => true,
        'ServiceWorkerEnabled' => true,
        'OfflineModeEnabled' => true,
        'PushNotificationsEnabled' => true,
        'AutoInjectPwaAssets' => true,
        'CacheStrategy' => 'network-first',
        'CacheVersion' => 'v1',
        'CacheMaxAge' => 86400,
        // Push
        'PushTestMode' => false,
        'PushDefaultTitle' => 'New Notification',
        'Message' => 'You have a new update',
        'ttl' => 86400,
        'PushRequireInteraction' => false,
        'PushSilent' => false,
        'PushRenotify' => false,
        'vibrate' => '[200,100,200]',
    ];

    private static $displays = [
        'standalone' => 'Standalone (Recommended)',
        'fullscreen' => 'Fullscreen',
        'minimal-ui' => 'Minimal UI',
        'browser' => 'Browser',
    ];

    private static $orientations = [
        'any' => 'Any',
        'natural' => 'Natural',
        'portrait' => 'Portrait',
        'portrait-primary' => 'Portrait Primary',
        'portrait-secondary' => 'Portrait Secondary',
        'landscape' => 'Landscape',
        'landscape-primary' => 'Landscape Primary',
        'landscape-secondary' => 'Landscape Secondary',
    ];

    private static $categories = [
        '' => '-- Select Category --',
        'business' => 'Business',
        'education' => 'Education',
        'entertainment' => 'Entertainment',
        'finance' => 'Finance',
        'fitness' => 'Fitness',
        'food' => 'Food',
        'games' => 'Games',
        'government' => 'Government',
        'health' => 'Health',
        'kids' => 'Kids',
        'lifestyle' => 'Lifestyle',
        'magazines' => 'Magazines',
        'medical' => 'Medical',
        'music' => 'Music',
        'navigation' => 'Navigation',
        'news' => 'News',
        'personalization' => 'Personalization',
        'photo' => 'Photo',
        'politics' => 'Politics',
        'productivity' => 'Productivity',
        'security' => 'Security',
        'shopping' => 'Shopping',
        'social' => 'Social',
        'sports' => 'Sports',
        'travel' => 'Travel',
        'utilities' => 'Utilities',
        'weather' => 'Weather',
    ];

    private static $text_directions = [
        'auto' => 'Auto',
        'ltr' => 'Left to Right',
        'rtl' => 'Right to Left',
    ];

    private static $offline_icons = [
        '📡' => '📡 Signal',
        '🔌' => '🔌 Plug',
        '📴' => '📴 Phone Off',
        '🌐' => '🌐 Globe',
        '⚡' => '⚡ Lightning',
        '🔄' => '🔄 Refresh',
        '⏳' => '⏳ Hourglass',
        '🛜' => '🛜 WiFi',
        '❌' => '❌ Cross',
        '⚠️' => '⚠️ Warning',
    ];

    private static $cache_strategies = [
        'network-first' => 'Network First (Recommended) - Try network, fall back to cache',
        'cache-first' => 'Cache First - Try cache, fall back to network',
        'network-only' => 'Network Only - Always fetch from network',
        'stale-while-revalidate' => 'Stale While Revalidate - Return cache, update in background',
    ];

    private static $vibrationPatterns = [
        '[200,100,200]' => 'Default - Short buzz',
        '[500,110,500,110,450,110,200,110,170,40,450,110,200,110,170,40,500]' => 'Star Wars - Imperial March',
        '[100,50,100,50,100]' => 'Quick Triple',
        '[400,100,400]' => 'Double Long',
        '[100]' => 'Single Short',
        '[1000]' => 'Single Long',
        'none' => 'No Vibration',
        'custom' => 'Custom Pattern',
    ];

    private static $cached;

    public static function current(): self
    {
        if (self::$cached !== null) {
            return self::$cached;
        }
        $settings = self::get()->first();
        if (!$settings) {
            $settings = self::create();
            $settings->write();
        }
        return self::$cached = $settings;
    }

    public function getTitle()
    {
        return 'Settings';
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        $images = [
            $this->ManifestLogo(),
            $this->ManifestMaskableIcon(),
            $this->ManifestScreenshotWide(),
            $this->ManifestScreenshotNarrow(),
            $this->icon(),
            $this->badge(),
        ];

        foreach ($images as $image) {
            if ($image && $image->exists() && !$image->isPublished()) {
                $image->publishSingle();
            }
        }
    }

    public function canCreate($member = null, $context = [])
    {
        return self::get()->count() === 0;
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        self::current();
    }

    public function getCMSFields(): FieldList
    {
        $fields = FieldList::create(TabSet::create('Root'));

        $this->buildManifestFields($fields);
        $this->buildOfflineFields($fields);
        $this->buildPushFields($fields);
        $this->buildServiceWorkerFields($fields);

        return $fields;
    }

    private function buildManifestFields(FieldList $fields): void
    {
        $tab = 'Root.Manifest';

        $fields->addFieldToTab($tab, HeaderField::create('ManifestCoreHeader', 'Core Settings'));
        $fields->addFieldToTab($tab, TextField::create('ManifestName', 'App Name')
            ->setDescription('Full name of your application (displayed in install prompts and app listings)'));
        $fields->addFieldToTab($tab, TextField::create('ManifestShortName', 'Short Name')
            ->setDescription('Short name displayed on home screen (max 12 characters recommended)'));
        $fields->addFieldToTab($tab, TextareaField::create('ManifestDescription', 'Description')
            ->setRows(3)
            ->setDescription('Description of your app for app stores and install prompts'));

        $fields->addFieldToTab($tab, HeaderField::create('ManifestAppearanceHeader', 'Appearance'));
        $fields->addFieldToTab($tab, TextField::create('ManifestColor', 'Theme Color')
            ->setAttribute('type', 'color')
            ->setDescription('Color for browser UI elements (address bar, status bar)'));
        $fields->addFieldToTab($tab, TextField::create('ManifestBackgroundColor', 'Background Color')
            ->setAttribute('type', 'color')
            ->setDescription('Background color for splash screen while app loads'));
        $fields->addFieldToTab($tab, DropdownField::create('ManifestDisplay', 'Display Mode', self::$displays)
            ->setDescription('How the app appears when launched'));
        $fields->addFieldToTab($tab, DropdownField::create('ManifestOrientation', 'Orientation', self::$orientations)
            ->setDescription('Preferred screen orientation'));

        $fields->addFieldToTab($tab, HeaderField::create('ManifestIconsHeader', 'Icons'));
        $fields->addFieldToTab($tab, UploadField::create('ManifestLogo', 'App Icon')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'svg'])
            ->setDescription('Square icon, minimum 512x512px (PNG or SVG). Used for home screen, app launcher, etc.'));
        $fields->addFieldToTab($tab, UploadField::create('ManifestMaskableIcon', 'Maskable Icon (Optional)')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png'])
            ->setDescription('Adaptive icon for Android. Should have safe zone padding (at least 10% on each side). 512x512px PNG.'));

        $fields->addFieldToTab($tab, HeaderField::create('ManifestScreenshotsHeader', 'Screenshots (Optional)'));
        $fields->addFieldToTab($tab, LiteralField::create('ScreenshotInfo',
            '<p class="message info">Screenshots are shown in app store listings and install prompts. Recommended sizes: Wide (1280x720), Narrow (540x720).</p>'));
        $fields->addFieldToTab($tab, UploadField::create('ManifestScreenshotWide', 'Wide Screenshot')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'jpg', 'jpeg', 'webp'])
            ->setDescription('Desktop/tablet screenshot (landscape, e.g., 1280x720)'));
        $fields->addFieldToTab($tab, UploadField::create('ManifestScreenshotNarrow', 'Narrow Screenshot')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'jpg', 'jpeg', 'webp'])
            ->setDescription('Mobile screenshot (portrait, e.g., 540x720)'));

        $fields->addFieldToTab($tab, HeaderField::create('ManifestAdvancedHeader', 'Advanced Settings'));
        $fields->addFieldToTab($tab, TextField::create('ManifestId', 'App ID (Optional)')
            ->setDescription('Unique identifier for your app. Leave blank to auto-generate from start URL.'));
        $fields->addFieldToTab($tab, TextField::create('ManifestStartUrl', 'Start URL (Optional)')
            ->setDescription('URL opened when app is launched. Leave blank for site root.'));
        $fields->addFieldToTab($tab, TextField::create('ManifestScope', 'Scope (Optional)')
            ->setDescription('Navigation scope of the app. Leave blank to use start URL directory.'));
        $fields->addFieldToTab($tab, DropdownField::create('ManifestCategories', 'Category', self::$categories)
            ->setDescription('App category for store listings'));
        $fields->addFieldToTab($tab, TextField::create('ManifestLang', 'Language Code (Optional)')
            ->setDescription('Primary language (e.g., "en", "en-US", "fr"). Leave blank to auto-detect.'));
        $fields->addFieldToTab($tab, DropdownField::create('ManifestDir', 'Text Direction', self::$text_directions)
            ->setDescription('Text direction for the app'));

        $fields->addFieldToTab($tab, HeaderField::create('ManifestRelatedHeader', 'Related Native Apps'));
        $fields->addFieldToTab($tab, LiteralField::create('RelatedInfo',
            '<p class="message info">Link a published native app so the browser can detect when a visitor already has it installed (Android, via getInstalledRelatedApps) and show native install hints. Android also requires a matching /.well-known/assetlinks.json on this domain.</p>'));
        $fields->addFieldToTab($tab, TextField::create('ManifestAndroidPackage', 'Android Package Name')
            ->setDescription('e.g. nz.co.example.app - the Play Store application ID'));
        $fields->addFieldToTab($tab, TextField::create('ManifestIOSAppStoreUrl', 'iOS App Store URL')
            ->setDescription('Full App Store listing URL (iOS cannot be install-detected, but this enables native install hints)'));
        $fields->addFieldToTab($tab, DropdownField::create('ManifestPreferRelated', 'Prefer Native App', [
            0 => 'No - keep the PWA installable (recommended)',
            1 => 'Yes - point install prompts at the native app',
        ])->setDescription('Leave as "No" so browsers still offer the PWA install'));

        $fields->addFieldToTab($tab, HeaderField::create('ManifestShortcutsHeader', 'App Shortcuts'));
        $fields->addFieldToTab($tab, LiteralField::create('ShortcutsInfo',
            '<p class="message info">Shortcuts appear when long-pressing the app icon (mobile) or right-clicking on the taskbar (desktop). Maximum 4 shortcuts recommended.</p>'));
        if ($this->ID) {
            $fields->addFieldToTab($tab, GridField::create(
                'ManifestShortcuts',
                'Shortcuts',
                $this->ManifestShortcuts(),
                GridFieldConfig_RecordEditor::create()
            ));
        } else {
            $fields->addFieldToTab($tab, LiteralField::create('ShortcutsSaveFirst',
                '<p class="message warning">Save the settings first to add shortcuts.</p>'));
        }
    }

    private function buildOfflineFields(FieldList $fields): void
    {
        $tab = 'Root.Offline';

        $fields->addFieldToTab($tab, HeaderField::create('OfflineContentHeader', 'Offline Page Content'));
        $fields->addFieldToTab($tab, LiteralField::create('OfflineInfo',
            '<p class="message info">Customize the page shown when users are offline and try to navigate to a page that isn\'t cached.</p>'));
        $fields->addFieldToTab($tab, TextField::create('OfflineTitle', 'Title')
            ->setDescription('Main heading shown on the offline page'));
        $fields->addFieldToTab($tab, TextareaField::create('OfflineMessage', 'Message')
            ->setRows(3)
            ->setDescription('Explanation text shown below the title'));
        $fields->addFieldToTab($tab, TextField::create('OfflineButtonText', 'Button Text')
            ->setDescription('Text for the retry button'));
        $fields->addFieldToTab($tab, DropdownField::create('OfflineIcon', 'Icon', self::$offline_icons)
            ->setDescription('Emoji icon shown on the offline page'));

        $fields->addFieldToTab($tab, HeaderField::create('OfflineStyleHeader', 'Offline Page Styling'));
        $fields->addFieldToTab($tab, TextField::create('OfflineBackgroundColor', 'Background Color')
            ->setAttribute('type', 'color')
            ->setDescription('Background color of the offline page'));
        $fields->addFieldToTab($tab, TextField::create('OfflineTextColor', 'Text Color')
            ->setAttribute('type', 'color')
            ->setDescription('Color of the text on the offline page'));
        $fields->addFieldToTab($tab, TextField::create('OfflineAccentColor', 'Accent Color')
            ->setAttribute('type', 'color')
            ->setDescription('Color for the title and button'));
    }

    private function buildPushFields(FieldList $fields): void
    {
        $tab = 'Root.Push';

        $fields->addFieldToTab($tab, HeaderField::create('PushVapidHeader', 'VAPID Keys (Required)'));
        $vapidConfigured = $this->VapidPublicKey && $this->VapidPrivateKey;
        if (!$vapidConfigured) {
            $fields->addFieldToTab($tab, LiteralField::create('VapidKeysWarning',
                '<p class="message error" style="font-weight:600;">⚠ VAPID keys are not configured. Web push notifications will not work until you generate them.</p>'));
            $fields->addFieldToTab($tab, LiteralField::create('GenerateVapidButton',
                '<p><button type="button" class="btn action btn-primary" onclick="generateVapidKeys()">Generate VAPID Keys</button></p>
                <script>
                function generateVapidKeys() {
                    fetch("/pwa-generate-vapid-keys")
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.querySelector(\'input[name="VapidPublicKey"]\').value = data.publicKey;
                                document.querySelector(\'input[name="VapidPrivateKey"]\').value = data.privateKey;
                                alert("VAPID keys generated! Click Save to store them.");
                            } else {
                                alert("Error: " + data.error);
                            }
                        })
                        .catch(err => alert("Error generating keys: " + err));
                }
                </script>'));
            $fields->addFieldToTab($tab, TextField::create('VapidPublicKey', 'VAPID Public Key')
                ->setDescription('Will be auto-generated'));
            $fields->addFieldToTab($tab, TextField::create('VapidPrivateKey', 'VAPID Private Key')
                ->setDescription('Will be auto-generated'));
        } else {
            $fields->addFieldToTab($tab, LiteralField::create('VapidKeysGenerated',
                '<p class="message good">✓ VAPID keys are configured. Web push is ready.</p>'));
            $fields->addFieldToTab($tab, ReadonlyField::create('VapidPublicKey', 'VAPID Public Key')
                ->setDescription('Used by browsers to verify push notifications.'));
            $fields->addFieldToTab($tab, ReadonlyField::create('VapidPrivateKey', 'VAPID Private Key')
                ->setDescription('Keep this secret. Used to sign push notifications.'));
        }
        $fields->addFieldToTab($tab, TextField::create('VapidSubject', 'VAPID Subject')
            ->setDescription('Contact email for push notifications, e.g. mailto:admin@example.com'));

        $fields->addFieldToTab($tab, HeaderField::create('PushTestHeader', 'Test Mode'));
        if ($this->PushTestMode) {
            $fields->addFieldToTab($tab, LiteralField::create('PushTestWarning',
                '<p class="message warning"><strong>TEST MODE ACTIVE</strong> - Push notifications will only be sent to the test user specified below. All other subscribers will be ignored.</p>'));
        }
        $fields->addFieldToTab($tab, CheckboxField::create('PushTestMode', 'Enable Test Mode')
            ->setDescription('When enabled, push notifications are only sent to the test user below'));
        $fields->addFieldToTab($tab, DropdownField::create(
            'PushTestMemberID',
            'Test User',
            Member::get()->map('ID', 'Email')->toArray()
        )->setEmptyString('-- Select Test User --')
            ->setDescription('Only this user will receive push notifications when test mode is enabled'));
        $fields->addFieldToTab($tab, TextField::create('PushTestEmail', 'Or Test Email')
            ->setDescription('Alternative: Enter email address to find test user by email'));

        $fields->addFieldToTab($tab, HeaderField::create('PushContentHeader', 'Default Notification Content'));
        $fields->addFieldToTab($tab, LiteralField::create('PushContentInfo',
            '<p class="message info">These are default values used when sending notifications. They can be overridden programmatically.</p>'));
        $fields->addFieldToTab($tab, TextField::create('PushDefaultTitle', 'Default Title')
            ->setDescription('Default notification title if none is specified'));
        $fields->addFieldToTab($tab, TextareaField::create('Message', 'Default Message')
            ->setRows(2)
            ->setDescription('Default message body for notifications'));
        $fields->addFieldToTab($tab, NumericField::create('ttl', 'Time to Live (seconds)')
            ->setDescription('How long the push service should try to deliver the notification. Default: 86400 (24 hours)'));

        $fields->addFieldToTab($tab, HeaderField::create('PushIconsHeader', 'Notification Icons'));
        $fields->addFieldToTab($tab, UploadField::create('icon', 'Notification Icon')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'jpg', 'jpeg'])
            ->setDescription('Main notification icon (recommended: 512x512px PNG)'));
        $fields->addFieldToTab($tab, UploadField::create('badge', 'Badge Icon')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png'])
            ->setDescription('Small monochrome icon for status bar (recommended: 128x128px PNG)'));

        $fields->addFieldToTab($tab, HeaderField::create('PushBehaviorHeader', 'Notification Behavior'));
        $fields->addFieldToTab($tab, CheckboxField::create('PushRequireInteraction', 'Require Interaction')
            ->setDescription('Notification stays visible until user interacts (may not work on all platforms)'));
        $fields->addFieldToTab($tab, CheckboxField::create('PushSilent', 'Silent Notifications')
            ->setDescription('Suppress sound and vibration (useful for data-only updates)'));
        $fields->addFieldToTab($tab, CheckboxField::create('PushRenotify', 'Renotify')
            ->setDescription('Vibrate/sound again when replacing an existing notification with the same tag'));

        $fields->addFieldToTab($tab, HeaderField::create('PushVibrateHeader', 'Vibration Pattern'));
        $fields->addFieldToTab($tab, DropdownField::create('vibrate', 'Vibration Pattern', self::$vibrationPatterns)
            ->setDescription('Pattern of vibration for notification alerts'));
        $fields->addFieldToTab($tab, TextField::create('PushCustomVibrate', 'Custom Vibration Pattern')
            ->setDescription('Enter custom pattern as comma-separated milliseconds (e.g., 200,100,200). Only used if "Custom Pattern" is selected above.'));

        $fields->addFieldToTab($tab, HeaderField::create('PushActionsHeader', 'Action Buttons (Optional)'));
        $fields->addFieldToTab($tab, LiteralField::create('PushActionsInfo',
            '<p class="message info">Add up to 2 action buttons to notifications. Support varies by platform.</p>'));
        $fields->addFieldToTab($tab, TextField::create('PushAction1Text', 'Action 1 Text')
            ->setDescription('Text for first action button (e.g., "View", "Open")'));
        $fields->addFieldToTab($tab, TextField::create('PushAction1Url', 'Action 1 URL')
            ->setDescription('URL to open when action 1 is clicked'));
        $fields->addFieldToTab($tab, TextField::create('PushAction2Text', 'Action 2 Text')
            ->setDescription('Text for second action button (e.g., "Dismiss", "Later")'));
        $fields->addFieldToTab($tab, TextField::create('PushAction2Url', 'Action 2 URL')
            ->setDescription('URL to open when action 2 is clicked (leave empty to just dismiss)'));

        $fields->addFieldToTab($tab, HeaderField::create('PushSendTestHeader', 'Send Test Notification'));
        $fields->addFieldToTab($tab, LiteralField::create('PushSendTestInfo',
            '<p class="message info">Send a test notification using the default title and message configured above. Always delivered to the configured test user only — never to other subscribers.</p>'));
        $fields->addFieldToTab($tab, LiteralField::create('SendTestPushButton',
            '<a href="/pwa-send-test-push" class="btn btn-primary font-icon-rocket" target="_blank" '
            . 'onclick="window.open(this.href, \'_blank\', \'width=500,height=300\'); return false;">'
            . 'Send Test Push Notification</a>'));
    }

    private function buildServiceWorkerFields(FieldList $fields): void
    {
        $tab = 'Root.ServiceWorker';

        $fields->addFieldToTab($tab, HeaderField::create('SWMasterHeader', 'PWA Controls'));
        $fields->addFieldToTab($tab, LiteralField::create('SWMasterInfo',
            '<p class="message info">Use these toggles to enable or disable PWA features. Disabling the Service Worker will disable all offline and caching functionality.</p>'));
        $fields->addFieldToTab($tab, CheckboxField::create('PWAEnabled', 'Enable PWA')
            ->setDescription('Master switch - disable to turn off all PWA functionality'));
        $fields->addFieldToTab($tab, CheckboxField::create('ServiceWorkerEnabled', 'Enable Service Worker')
            ->setDescription('Enable/disable the service worker (caching and offline support)'));
        $fields->addFieldToTab($tab, CheckboxField::create('OfflineModeEnabled', 'Enable Offline Mode')
            ->setDescription('Show offline page when network is unavailable'));
        $fields->addFieldToTab($tab, CheckboxField::create('PushNotificationsEnabled', 'Enable Push Notifications')
            ->setDescription('Allow push notification subscriptions'));
        $fields->addFieldToTab($tab, CheckboxField::create('AutoInjectPwaAssets', 'Auto-inject PWA Assets')
            ->setDescription('Automatically inject the manifest link and service-worker registration script into every page. Disable if your theme is wiring these manually.'));

        $fields->addFieldToTab($tab, HeaderField::create('SWCacheHeader', 'Cache Settings'));
        $fields->addFieldToTab($tab, DropdownField::create('CacheStrategy', 'Cache Strategy', self::$cache_strategies)
            ->setDescription('How the service worker handles requests'));
        $fields->addFieldToTab($tab, TextField::create('CacheVersion', 'Cache Version')
            ->setDescription('Change this to force browsers to clear their cache (e.g., v1, v2, v3)'));
        $fields->addFieldToTab($tab, TextField::create('CacheMaxAge', 'Cache Max Age (seconds)')
            ->setDescription('How long to keep items in cache. Default: 86400 (24 hours)'));
        $fields->addFieldToTab($tab, TextareaField::create('PrecacheUrls', 'Pre-cache URLs')
            ->setRows(5)
            ->setDescription('URLs to cache when service worker installs (one per line). These will be available offline immediately.'));
        $fields->addFieldToTab($tab, TextareaField::create('ExcludeUrlPatterns', 'Exclude URL Patterns')
            ->setRows(5)
            ->setDescription('URL patterns to never cache (one per line). Supports wildcards: /admin/*, /api/*, *.json'));

        $fields->addFieldToTab($tab, HeaderField::create('SWDebugHeader', 'Developer Options'));
        $fields->addFieldToTab($tab, CheckboxField::create('ServiceWorkerDebug', 'Enable Debug Mode')
            ->setDescription('Log service worker events to browser console'));
        $fields->addFieldToTab($tab, LiteralField::create('SWDebugInfo',
            '<p class="message warning">Debug mode should be disabled in production. It may expose internal information in the browser console.</p>'));
    }

    public function getPrecacheUrlsArray(): array
    {
        if (!$this->PrecacheUrls) {
            return [];
        }
        return array_filter(array_map('trim', explode("\n", $this->PrecacheUrls)));
    }

    public function getExcludeUrlPatternsArray(): array
    {
        if (!$this->ExcludeUrlPatterns) {
            return [];
        }
        return array_filter(array_map('trim', explode("\n", $this->ExcludeUrlPatterns)));
    }

    public function getTestMember(): ?Member
    {
        if ($this->PushTestMemberID) {
            return Member::get()->byID($this->PushTestMemberID);
        }
        if ($this->PushTestEmail) {
            return Member::get()->filter('Email', $this->PushTestEmail)->first();
        }
        return null;
    }

    public function isTestModeActive(): bool
    {
        return (bool)$this->PushTestMode;
    }

    public function getVibrationPattern(): array
    {
        $pattern = $this->vibrate;
        if ($pattern === 'none') {
            return [];
        }
        if ($pattern === 'custom' && $this->PushCustomVibrate) {
            return array_map('intval', explode(',', $this->PushCustomVibrate));
        }
        if ($pattern && $pattern !== 'custom') {
            $decoded = json_decode($pattern);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [200, 100, 200];
    }

    public function getNotificationActions(): array
    {
        $actions = [];
        if ($this->PushAction1Text) {
            $actions[] = [
                'action' => 'action1',
                'title' => $this->PushAction1Text,
                'url' => $this->PushAction1Url ?: '/',
            ];
        }
        if ($this->PushAction2Text) {
            $actions[] = [
                'action' => 'action2',
                'title' => $this->PushAction2Text,
                'url' => $this->PushAction2Url ?: '',
            ];
        }
        return $actions;
    }
}
