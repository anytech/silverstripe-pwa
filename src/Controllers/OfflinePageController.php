<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripePWA\Models\PWASettings;

class OfflinePageController extends Controller
{
    private static $allowed_actions = [
        'index'
    ];

    public function index($url)
    {
        $this->getResponse()->addHeader('Content-Type', 'text/html; charset="utf-8"');
        return $this->renderWith('Offline');
    }

    public function OfflineTitle()
    {
        $config = PWASettings::current();
        return $config->OfflineTitle ?: "You're Offline";
    }

    public function OfflineMessage()
    {
        $config = PWASettings::current();
        return $config->OfflineMessage ?: "It looks like you've lost your internet connection. Please check your network and try again.";
    }

    public function OfflineButtonText()
    {
        $config = PWASettings::current();
        return $config->OfflineButtonText ?: 'Try Again';
    }

    public function OfflineBackgroundColor()
    {
        $config = PWASettings::current();
        return $config->OfflineBackgroundColor ?: '#1a1a2e';
    }

    public function OfflineTextColor()
    {
        $config = PWASettings::current();
        return $config->OfflineTextColor ?: '#ffffff';
    }

    public function OfflineAccentColor()
    {
        $config = PWASettings::current();
        return $config->OfflineAccentColor ?: '#e94560';
    }

    public function OfflineIcon()
    {
        $config = PWASettings::current();
        return $config->OfflineIcon ?: '📡';
    }
}
