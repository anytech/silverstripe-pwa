<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;

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
        $config = SiteConfig::current_site_config();
        return $config->OfflineTitle ?: "You're Offline";
    }

    public function OfflineMessage()
    {
        $config = SiteConfig::current_site_config();
        return $config->OfflineMessage ?: "It looks like you've lost your internet connection. Please check your network and try again.";
    }

    public function OfflineButtonText()
    {
        $config = SiteConfig::current_site_config();
        return $config->OfflineButtonText ?: 'Try Again';
    }

    public function OfflineBackgroundColor()
    {
        $config = SiteConfig::current_site_config();
        return $config->OfflineBackgroundColor ?: '#1a1a2e';
    }

    public function OfflineTextColor()
    {
        $config = SiteConfig::current_site_config();
        return $config->OfflineTextColor ?: '#ffffff';
    }

    public function OfflineAccentColor()
    {
        $config = SiteConfig::current_site_config();
        return $config->OfflineAccentColor ?: '#e94560';
    }

    public function OfflineIcon()
    {
        $config = SiteConfig::current_site_config();
        return $config->OfflineIcon ?: '📡';
    }
}
