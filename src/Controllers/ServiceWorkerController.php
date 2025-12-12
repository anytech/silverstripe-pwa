<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\SiteConfig\SiteConfig;

class ServiceWorkerController extends Controller
{
    private static $allowed_actions = [
        'index'
    ];

    public function index($url)
    {
        $config = SiteConfig::current_site_config();

        // Check if service worker is disabled
        if ($config->hasField('ServiceWorkerEnabled') && !$config->ServiceWorkerEnabled) {
            $this->getResponse()->addHeader('Content-Type', 'application/javascript; charset="utf-8"');
            return '// Service worker is disabled';
        }

        $this->getResponse()->addHeader('Content-Type', 'application/javascript; charset="utf-8"');
        return $this->renderWith('ServiceWorker');
    }

    public function BaseUrl()
    {
        return Director::baseURL();
    }

    public function PublicKey()
    {
        $config = SiteConfig::current_site_config();
        return $config->VapidPublicKey ?: '';
    }

    public function DebugMode()
    {
        $config = SiteConfig::current_site_config();

        if (Director::isDev()) {
            return true;
        }

        return $config->hasField('ServiceWorkerDebug') ? $config->ServiceWorkerDebug : false;
    }

    public function CacheStrategy()
    {
        $config = SiteConfig::current_site_config();
        return $config->hasField('CacheStrategy') ? $config->CacheStrategy : 'network-first';
    }

    public function CacheVersion()
    {
        $config = SiteConfig::current_site_config();
        return $config->hasField('CacheVersion') ? $config->CacheVersion : 'v1';
    }

    public function OfflineModeEnabled()
    {
        $config = SiteConfig::current_site_config();
        return !$config->hasField('OfflineModeEnabled') || $config->OfflineModeEnabled;
    }

    public function PushNotificationsEnabled()
    {
        $config = SiteConfig::current_site_config();
        return !$config->hasField('PushNotificationsEnabled') || $config->PushNotificationsEnabled;
    }

    public function PrecacheUrls()
    {
        $config = SiteConfig::current_site_config();

        if ($config->hasMethod('getPrecacheUrlsArray')) {
            return json_encode($config->getPrecacheUrlsArray());
        }

        return '[]';
    }

    public function ExcludeUrlPatterns()
    {
        $config = SiteConfig::current_site_config();

        if ($config->hasMethod('getExcludeUrlPatternsArray')) {
            return json_encode($config->getExcludeUrlPatternsArray());
        }

        return '[]';
    }

    public function CacheMaxAge()
    {
        $config = SiteConfig::current_site_config();
        return $config->hasField('CacheMaxAge') ? (int)$config->CacheMaxAge : 86400;
    }

    /**
     * Get notification action buttons as JSON
     */
    public function NotificationActions()
    {
        $config = SiteConfig::current_site_config();

        if ($config->hasMethod('getNotificationActions')) {
            return json_encode($config->getNotificationActions());
        }

        return '[]';
    }
}
