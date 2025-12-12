<?php

namespace SilverStripePWA\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;

class ServiceWorkerSiteConfigExtension extends DataExtension
{
    private static $db = [
        // Master toggles
        'PWAEnabled' => 'Boolean',
        'ServiceWorkerEnabled' => 'Boolean',
        'OfflineModeEnabled' => 'Boolean',
        'PushNotificationsEnabled' => 'Boolean',

        // Cache settings
        'CacheStrategy' => 'Varchar(50)',
        'CacheVersion' => 'Varchar(20)',
        'PrecacheUrls' => 'Text',
        'ExcludeUrlPatterns' => 'Text',
        'CacheMaxAge' => 'Int',

        // Debug
        'ServiceWorkerDebug' => 'Boolean'
    ];

    private static $defaults = [
        'PWAEnabled' => true,
        'ServiceWorkerEnabled' => true,
        'OfflineModeEnabled' => true,
        'PushNotificationsEnabled' => true,
        'CacheStrategy' => 'network-first',
        'CacheVersion' => 'v1',
        'CacheMaxAge' => 86400
    ];

    private static $cache_strategies = [
        'network-first' => 'Network First (Recommended) - Try network, fall back to cache',
        'cache-first' => 'Cache First - Try cache, fall back to network',
        'network-only' => 'Network Only - Always fetch from network',
        'stale-while-revalidate' => 'Stale While Revalidate - Return cache, update in background'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Master Controls
        $fields->addFieldToTab('Root.ServiceWorker', HeaderField::create('SWMasterHeader', 'PWA Controls'));

        $fields->addFieldToTab('Root.ServiceWorker', LiteralField::create('SWMasterInfo',
            '<p class="message info">Use these toggles to enable or disable PWA features. Disabling the Service Worker will disable all offline and caching functionality.</p>'));

        $fields->addFieldToTab('Root.ServiceWorker', CheckboxField::create('PWAEnabled', 'Enable PWA')
            ->setDescription('Master switch - disable to turn off all PWA functionality'));

        $fields->addFieldToTab('Root.ServiceWorker', CheckboxField::create('ServiceWorkerEnabled', 'Enable Service Worker')
            ->setDescription('Enable/disable the service worker (caching and offline support)'));

        $fields->addFieldToTab('Root.ServiceWorker', CheckboxField::create('OfflineModeEnabled', 'Enable Offline Mode')
            ->setDescription('Show offline page when network is unavailable'));

        $fields->addFieldToTab('Root.ServiceWorker', CheckboxField::create('PushNotificationsEnabled', 'Enable Push Notifications')
            ->setDescription('Allow push notification subscriptions'));

        // Cache Settings
        $fields->addFieldToTab('Root.ServiceWorker', HeaderField::create('SWCacheHeader', 'Cache Settings'));

        $fields->addFieldToTab('Root.ServiceWorker', DropdownField::create('CacheStrategy', 'Cache Strategy', self::$cache_strategies)
            ->setDescription('How the service worker handles requests'));

        $fields->addFieldToTab('Root.ServiceWorker', TextField::create('CacheVersion', 'Cache Version')
            ->setDescription('Change this to force browsers to clear their cache (e.g., v1, v2, v3)'));

        $fields->addFieldToTab('Root.ServiceWorker', TextField::create('CacheMaxAge', 'Cache Max Age (seconds)')
            ->setDescription('How long to keep items in cache. Default: 86400 (24 hours)'));

        $fields->addFieldToTab('Root.ServiceWorker', TextareaField::create('PrecacheUrls', 'Pre-cache URLs')
            ->setRows(5)
            ->setDescription('URLs to cache when service worker installs (one per line). These will be available offline immediately.'));

        $fields->addFieldToTab('Root.ServiceWorker', TextareaField::create('ExcludeUrlPatterns', 'Exclude URL Patterns')
            ->setRows(5)
            ->setDescription('URL patterns to never cache (one per line). Supports wildcards: /admin/*, /api/*, *.json'));

        // Debug
        $fields->addFieldToTab('Root.ServiceWorker', HeaderField::create('SWDebugHeader', 'Developer Options'));

        $fields->addFieldToTab('Root.ServiceWorker', CheckboxField::create('ServiceWorkerDebug', 'Enable Debug Mode')
            ->setDescription('Log service worker events to browser console'));

        $fields->addFieldToTab('Root.ServiceWorker', LiteralField::create('SWDebugInfo',
            '<p class="message warning">Debug mode should be disabled in production. It may expose internal information in the browser console.</p>'));
    }

    /**
     * Get pre-cache URLs as array
     */
    public function getPrecacheUrlsArray(): array
    {
        if (!$this->owner->PrecacheUrls) {
            return [];
        }

        $urls = array_filter(
            array_map('trim', explode("\n", $this->owner->PrecacheUrls))
        );

        return $urls;
    }

    /**
     * Get exclude patterns as array
     */
    public function getExcludeUrlPatternsArray(): array
    {
        if (!$this->owner->ExcludeUrlPatterns) {
            return [];
        }

        return array_filter(
            array_map('trim', explode("\n", $this->owner->ExcludeUrlPatterns))
        );
    }
}
