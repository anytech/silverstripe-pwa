<?php

namespace SilverStripePWA\Extensions;

use SilverStripe\Core\Extension;
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

class ManifestSiteConfigExtension extends Extension
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

        // Related native apps (powers getInstalledRelatedApps + native install hints)
        'ManifestAndroidPackage' => 'Varchar(255)',
        'ManifestIOSAppStoreUrl' => 'Varchar(255)',
        'ManifestPreferRelated' => 'Boolean',

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
        $fields->addFieldToTab('Root.PWA.Manifest', HeaderField::create('ManifestCoreHeader', 'Core Settings'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestName', 'App Name')
            ->setDescription('Full name of your application (displayed in install prompts and app listings)'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestShortName', 'Short Name')
            ->setDescription('Short name displayed on home screen (max 12 characters recommended)'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextareaField::create('ManifestDescription', 'Description')
            ->setRows(3)
            ->setDescription('Description of your app for app stores and install prompts'));

        // Appearance
        $fields->addFieldToTab('Root.PWA.Manifest', HeaderField::create('ManifestAppearanceHeader', 'Appearance'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestColor', 'Theme Color')
            ->setAttribute('type', 'color')
            ->setDescription('Color for browser UI elements (address bar, status bar)'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestBackgroundColor', 'Background Color')
            ->setAttribute('type', 'color')
            ->setDescription('Background color for splash screen while app loads'));

        $fields->addFieldToTab('Root.PWA.Manifest', DropdownField::create('ManifestDisplay', 'Display Mode', self::$displays)
            ->setDescription('How the app appears when launched'));

        $fields->addFieldToTab('Root.PWA.Manifest', DropdownField::create('ManifestOrientation', 'Orientation', self::$orientations)
            ->setDescription('Preferred screen orientation'));

        // Icons
        $fields->addFieldToTab('Root.PWA.Manifest', HeaderField::create('ManifestIconsHeader', 'Icons'));

        $fields->addFieldToTab('Root.PWA.Manifest', UploadField::create('ManifestLogo', 'App Icon')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'svg'])
            ->setDescription('Square icon, minimum 512x512px (PNG or SVG). Used for home screen, app launcher, etc.'));

        $fields->addFieldToTab('Root.PWA.Manifest', UploadField::create('ManifestMaskableIcon', 'Maskable Icon (Optional)')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png'])
            ->setDescription('Adaptive icon for Android. Should have safe zone padding (at least 10% on each side). 512x512px PNG.'));

        // Screenshots
        $fields->addFieldToTab('Root.PWA.Manifest', HeaderField::create('ManifestScreenshotsHeader', 'Screenshots (Optional)'));

        $fields->addFieldToTab('Root.PWA.Manifest', LiteralField::create('ScreenshotInfo',
            '<p class="message info">Screenshots are shown in app store listings and install prompts. Recommended sizes: Wide (1280x720), Narrow (540x720).</p>'));

        $fields->addFieldToTab('Root.PWA.Manifest', UploadField::create('ManifestScreenshotWide', 'Wide Screenshot')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'jpg', 'jpeg', 'webp'])
            ->setDescription('Desktop/tablet screenshot (landscape, e.g., 1280x720)'));

        $fields->addFieldToTab('Root.PWA.Manifest', UploadField::create('ManifestScreenshotNarrow', 'Narrow Screenshot')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'jpg', 'jpeg', 'webp'])
            ->setDescription('Mobile screenshot (portrait, e.g., 540x720)'));

        // Advanced Settings
        $fields->addFieldToTab('Root.PWA.Manifest', HeaderField::create('ManifestAdvancedHeader', 'Advanced Settings'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestId', 'App ID (Optional)')
            ->setDescription('Unique identifier for your app. Leave blank to auto-generate from start URL.'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestStartUrl', 'Start URL (Optional)')
            ->setDescription('URL opened when app is launched. Leave blank for site root.'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestScope', 'Scope (Optional)')
            ->setDescription('Navigation scope of the app. Leave blank to use start URL directory.'));

        $fields->addFieldToTab('Root.PWA.Manifest', DropdownField::create('ManifestCategories', 'Category', self::$categories)
            ->setDescription('App category for store listings'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestLang', 'Language Code (Optional)')
            ->setDescription('Primary language (e.g., "en", "en-US", "fr"). Leave blank to auto-detect.'));

        $fields->addFieldToTab('Root.PWA.Manifest', DropdownField::create('ManifestDir', 'Text Direction', self::$text_directions)
            ->setDescription('Text direction for the app'));

        // Related native apps
        $fields->addFieldToTab('Root.PWA.Manifest', HeaderField::create('ManifestRelatedHeader', 'Related Native Apps'));

        $fields->addFieldToTab('Root.PWA.Manifest', LiteralField::create('RelatedInfo',
            '<p class="message info">Link a published native app so the browser can detect when a visitor already has it installed (Android, via getInstalledRelatedApps) and show native install hints. Android also requires a matching /.well-known/assetlinks.json on this domain.</p>'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestAndroidPackage', 'Android Package Name')
            ->setDescription('e.g. nz.co.example.app - the Play Store application ID'));

        $fields->addFieldToTab('Root.PWA.Manifest', TextField::create('ManifestIOSAppStoreUrl', 'iOS App Store URL')
            ->setDescription('Full App Store listing URL (iOS cannot be install-detected, but this enables native install hints)'));

        $fields->addFieldToTab('Root.PWA.Manifest', DropdownField::create('ManifestPreferRelated', 'Prefer Native App', [
            0 => 'No - keep the PWA installable (recommended)',
            1 => 'Yes - point install prompts at the native app',
        ])->setDescription('Leave as "No" so browsers still offer the PWA install'));

        // Shortcuts
        $fields->addFieldToTab('Root.PWA.Manifest', HeaderField::create('ManifestShortcutsHeader', 'App Shortcuts'));

        $fields->addFieldToTab('Root.PWA.Manifest', LiteralField::create('ShortcutsInfo',
            '<p class="message info">Shortcuts appear when long-pressing the app icon (mobile) or right-clicking on the taskbar (desktop). Maximum 4 shortcuts recommended.</p>'));

        if ($this->owner->ID) {
            $shortcutsGrid = GridField::create(
                'ManifestShortcuts',
                'Shortcuts',
                $this->owner->ManifestShortcuts(),
                GridFieldConfig_RecordEditor::create()
            );
            $fields->addFieldToTab('Root.PWA.Manifest', $shortcutsGrid);
        } else {
            $fields->addFieldToTab('Root.PWA.Manifest', LiteralField::create('ShortcutsSaveFirst',
                '<p class="message warning">Save the settings first to add shortcuts.</p>'));
        }

    }
}