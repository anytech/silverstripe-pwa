<?php

namespace SilverStripePWA\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripePWA\Models\PWASettings;

class PWAAdmin extends ModelAdmin {
    private static $menu_title = 'PWA';
    private static $url_segment = 'pwa';
    private static $menu_icon_class = 'font-icon-mobile';

    private static $managed_models = [
        PWASettings::class => [
            'title' => 'Settings',
        ],
    ];
}
