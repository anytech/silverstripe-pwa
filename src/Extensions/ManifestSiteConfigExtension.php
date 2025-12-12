<?php

namespace SilverStripePWA\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripePWA\Services\WebPushService;
use SilverStripePWA\Models\ManifestShortcut;

class ManifestSiteConfigExtension extends DataExtension
{
    private static $db = [
        // Core manifest properties
        'ManifestName' => 'Varchar(255)',
        'ManifestShortName' => 'Varchar(25)',
        'ManifestDescription' => 'Text',
        'ManifestColor' => 'Varchar(7)',
        'ManifestBackgroundColor' => 'Varchar(7)',
        'ManifestOrientation' => 'Varchar',
        'ManifestDisplay' => 'Varchar',

        // Modern PWA properties (2025 standards)
        'ManifestId' => 'Varchar(255)',
        'ManifestScope' => 'Varchar(255)',
        'ManifestStartUrl' => 'Varchar(255)',
        'ManifestCategories' => 'Varchar(255)',
        'ManifestLang' => 'Varchar(10)',
        'ManifestDir' => 'Varchar(5)',

        // Push notifications & VAPID
        'PushNotification' => 'Boolean',
        'VapidSubject' => 'Varchar(255)',
        'VapidPublicKey' => 'Varchar(255)',
        'VapidPrivateKey' => 'Varchar(255)'
    ];

    private static $displays = [
        'standalone' => 'Standalone (Recommended)',
        'fullscreen' => 'Fullscreen',
        'minimal-ui' => 'Minimal UI',
        'browser' => 'Browser'
    ];

    private static $orientations = [
        'any' => 'Any',
        'natural' => 'Natural',
        'portrait' => 'Portrait',
        'portrait-primary' => 'Portrait Primary',
        'portrait-secondary' => 'Portrait Secondary',
        'landscape' => 'Landscape',
        'landscape-primary' => 'Landscape Primary',
        'landscape-secondary' => 'Landscape Secondary'
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
        'weather' => 'Weather'
    ];

    private static $text_directions = [
        'auto' => 'Auto',
        'ltr' => 'Left to Right',
        'rtl' => 'Right to Left'
    ];

    private static $has_one = [
        'ManifestLogo' => Image::class,
        'ManifestMaskableIcon' => Image::class,
        'ManifestScreenshotWide' => Image::class,
        'ManifestScreenshotNarrow' => Image::class
    ];

    private static $has_many = [
        'ManifestShortcuts' => ManifestShortcut::class
    ];

    private static $owns = [
        'ManifestLogo',
        'ManifestMaskableIcon',
        'ManifestScreenshotWide',
        'ManifestScreenshotNarrow'
    ];

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        $images = [
            $this->owner->ManifestLogo(),
            $this->owner->ManifestMaskableIcon(),
            $this->owner->ManifestScreenshotWide(),
            $this->owner->ManifestScreenshotNarrow()
        ];

        foreach ($images as $image) {
            if ($image && $image->exists() && !$image->isPublished()) {
                $image->publishSingle();
            }
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        // Core Settings
        $fields->addFieldToTab('Root.Manifest', HeaderField::create('ManifestCoreHeader', 'Core Settings'));

        $fields->addFieldToTab('Root.Manifest', TextField::create('ManifestName', 'App Name')
            ->setDescription('Full name of your application (displayed in install prompts and app listings)'));

        $fields->addFieldToTab('Root.Manifest', TextField::create('ManifestShortName', 'Short Name')
            ->setDescription('Short name displayed on home screen (max 12 characters recommended)'));

        $fields->addFieldToTab('Root.Manifest', TextareaField::create('ManifestDescription', 'Description')
            ->setRows(3)
            ->setDescription('Description of your app for app stores and install prompts'));

        // Appearance
        $fields->addFieldToTab('Root.Manifest', HeaderField::create('ManifestAppearanceHeader', 'Appearance'));

        $fields->addFieldToTab('Root.Manifest', TextField::create('ManifestColor', 'Theme Color')
            ->setAttribute('type', 'color')
            ->setDescription('Color for browser UI elements (address bar, status bar)'));

        $fields->addFieldToTab('Root.Manifest', TextField::create('ManifestBackgroundColor', 'Background Color')
            ->setAttribute('type', 'color')
            ->setDescription('Background color for splash screen while app loads'));

        $fields->addFieldToTab('Root.Manifest', DropdownField::create('ManifestDisplay', 'Display Mode', self::$displays)
            ->setDescription('How the app appears when launched'));

        $fields->addFieldToTab('Root.Manifest', DropdownField::create('ManifestOrientation', 'Orientation', self::$orientations)
            ->setDescription('Preferred screen orientation'));

        // Icons
        $fields->addFieldToTab('Root.Manifest', HeaderField::create('ManifestIconsHeader', 'Icons'));

        $fields->addFieldToTab('Root.Manifest', UploadField::create('ManifestLogo', 'App Icon')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'svg'])
            ->setDescription('Square icon, minimum 512x512px (PNG or SVG). Used for home screen, app launcher, etc.'));

        $fields->addFieldToTab('Root.Manifest', UploadField::create('ManifestMaskableIcon', 'Maskable Icon (Optional)')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png'])
            ->setDescription('Adaptive icon for Android. Should have safe zone padding (at least 10% on each side). 512x512px PNG.'));

        // Screenshots
        $fields->addFieldToTab('Root.Manifest', HeaderField::create('ManifestScreenshotsHeader', 'Screenshots (Optional)'));

        $fields->addFieldToTab('Root.Manifest', LiteralField::create('ScreenshotInfo',
            '<p class="message info">Screenshots are shown in app store listings and install prompts. Recommended sizes: Wide (1280x720), Narrow (540x720).</p>'));

        $fields->addFieldToTab('Root.Manifest', UploadField::create('ManifestScreenshotWide', 'Wide Screenshot')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'jpg', 'jpeg', 'webp'])
            ->setDescription('Desktop/tablet screenshot (landscape, e.g., 1280x720)'));

        $fields->addFieldToTab('Root.Manifest', UploadField::create('ManifestScreenshotNarrow', 'Narrow Screenshot')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'jpg', 'jpeg', 'webp'])
            ->setDescription('Mobile screenshot (portrait, e.g., 540x720)'));

        // Advanced Settings
        $fields->addFieldToTab('Root.Manifest', HeaderField::create('ManifestAdvancedHeader', 'Advanced Settings'));

        $fields->addFieldToTab('Root.Manifest', TextField::create('ManifestId', 'App ID (Optional)')
            ->setDescription('Unique identifier for your app. Leave blank to auto-generate from start URL.'));

        $fields->addFieldToTab('Root.Manifest', TextField::create('ManifestStartUrl', 'Start URL (Optional)')
            ->setDescription('URL opened when app is launched. Leave blank for site root.'));

        $fields->addFieldToTab('Root.Manifest', TextField::create('ManifestScope', 'Scope (Optional)')
            ->setDescription('Navigation scope of the app. Leave blank to use start URL directory.'));

        $fields->addFieldToTab('Root.Manifest', DropdownField::create('ManifestCategories', 'Category', self::$categories)
            ->setDescription('App category for store listings'));

        $fields->addFieldToTab('Root.Manifest', TextField::create('ManifestLang', 'Language Code (Optional)')
            ->setDescription('Primary language (e.g., "en", "en-US", "fr"). Leave blank to auto-detect.'));

        $fields->addFieldToTab('Root.Manifest', DropdownField::create('ManifestDir', 'Text Direction', self::$text_directions)
            ->setDescription('Text direction for the app'));

        // Shortcuts
        $fields->addFieldToTab('Root.Manifest', HeaderField::create('ManifestShortcutsHeader', 'App Shortcuts'));

        $fields->addFieldToTab('Root.Manifest', LiteralField::create('ShortcutsInfo',
            '<p class="message info">Shortcuts appear when long-pressing the app icon (mobile) or right-clicking on the taskbar (desktop). Maximum 4 shortcuts recommended.</p>'));

        if ($this->owner->ID) {
            $shortcutsGrid = GridField::create(
                'ManifestShortcuts',
                'Shortcuts',
                $this->owner->ManifestShortcuts(),
                GridFieldConfig_RecordEditor::create()
            );
            $fields->addFieldToTab('Root.Manifest', $shortcutsGrid);
        } else {
            $fields->addFieldToTab('Root.Manifest', LiteralField::create('ShortcutsSaveFirst',
                '<p class="message warning">Save the settings first to add shortcuts.</p>'));
        }

        // VAPID Settings
        $fields->addFieldToTab('Root.Manifest', HeaderField::create('ManifestVapidHeader', 'Push Notification Settings'));

        $fields->addFieldToTab('Root.Manifest', TextField::create('VapidSubject', 'VAPID Subject')
            ->setDescription('Contact email for push notifications (e.g., mailto:admin@example.com)'));

        // VAPID Keys
        if ($this->owner->VapidPublicKey && $this->owner->VapidPrivateKey) {
            $fields->addFieldToTab('Root.Manifest', ReadonlyField::create('VapidPublicKey', 'VAPID Public Key')
                ->setDescription('This key is used by browsers to verify push notifications. Share this in your service worker registration.'));

            $fields->addFieldToTab('Root.Manifest', ReadonlyField::create('VapidPrivateKey', 'VAPID Private Key')
                ->setDescription('Keep this secret! Used to sign push notifications.'));

            $fields->addFieldToTab('Root.Manifest', LiteralField::create('VapidKeysGenerated',
                '<p class="message good">VAPID keys are configured. Push notifications are ready to use.</p>'));
        } else {
            $fields->addFieldToTab('Root.Manifest', LiteralField::create('VapidKeysNotGenerated',
                '<p class="message warning">VAPID keys not configured. Click "Generate VAPID Keys" below, then save.</p>'));

            $fields->addFieldToTab('Root.Manifest', LiteralField::create('GenerateVapidButton',
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

            $fields->addFieldToTab('Root.Manifest', TextField::create('VapidPublicKey', 'VAPID Public Key')
                ->setDescription('Will be auto-generated'));

            $fields->addFieldToTab('Root.Manifest', TextField::create('VapidPrivateKey', 'VAPID Private Key')
                ->setDescription('Will be auto-generated'));
        }
    }
}