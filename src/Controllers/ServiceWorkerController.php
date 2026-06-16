<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripePWA\Models\PWASettings;

class ServiceWorkerController extends Controller
{
    private static $allowed_actions = [
        'index'
    ];

    public function index($url)
    {
        // Suppress PHP warnings/errors from appearing in JS output
        @ini_set('display_errors', 0);
        
        $config = PWASettings::current();

        // Check if service worker is disabled
        if ($config->hasField('ServiceWorkerEnabled') && !$config->ServiceWorkerEnabled) {
            $this->getResponse()->addHeader('Content-Type', 'application/javascript; charset="utf-8"');
            return '// Service worker is disabled';
        }

        $this->getResponse()->addHeader('Content-Type', 'application/javascript; charset="utf-8"');
        $this->getResponse()->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        return $this->renderWith('ServiceWorker');
    }

    public function BaseUrl()
    {
        return Director::baseURL();
    }

    public function PublicKey()
    {
        $config = PWASettings::current();
        return $config->VapidPublicKey ?: '';
    }

    public function DebugMode()
    {
        $config = PWASettings::current();

        if (Director::isDev()) {
            return true;
        }

        return $config->hasField('ServiceWorkerDebug') ? $config->ServiceWorkerDebug : false;
    }

    public function CacheStrategy()
    {
        $config = PWASettings::current();
        $strategy = $config->hasField('CacheStrategy') ? $config->CacheStrategy : null;
        return $strategy ?: 'network-first';
    }

    public function CacheVersion()
    {
        $config = PWASettings::current();
        $version = $config->hasField('CacheVersion') ? $config->CacheVersion : null;
        return $version ?: 'v1';
    }

    public function OfflineModeEnabled()
    {
        $config = PWASettings::current();
        return !$config->hasField('OfflineModeEnabled') || $config->OfflineModeEnabled;
    }

    public function PushNotificationsEnabled()
    {
        $config = PWASettings::current();
        return !$config->hasField('PushNotificationsEnabled') || $config->PushNotificationsEnabled;
    }

    public function PrecacheUrls()
    {
        $config = PWASettings::current();

        if ($config->hasMethod('getPrecacheUrlsArray')) {
            return json_encode($config->getPrecacheUrlsArray());
        }

        return '[]';
    }

    public function ExcludeUrlPatterns()
    {
        $config = PWASettings::current();

        if ($config->hasMethod('getExcludeUrlPatternsArray')) {
            return json_encode($config->getExcludeUrlPatternsArray());
        }

        return '[]';
    }

    public function CacheMaxAge()
    {
        $config = PWASettings::current();
        $maxAge = $config->hasField('CacheMaxAge') ? (int)$config->CacheMaxAge : 0;
        return $maxAge ?: 86400;
    }

    /**
     * Get notification action buttons as JSON
     */
    public function NotificationActions()
    {
        $config = PWASettings::current();

        if ($config->hasMethod('getNotificationActions')) {
            return json_encode($config->getNotificationActions());
        }

        return '[]';
    }
}
