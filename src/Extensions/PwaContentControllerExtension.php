<?php

namespace SilverStripePWA\Extensions;

use SilverStripe\Core\Extension;
use SilverStripePWA\Models\PWASettings;
use SilverStripe\View\Requirements;

class PwaContentControllerExtension extends Extension
{
    public function onAfterInit() {
        $request = $this->owner->getRequest();
        if ($request && str_starts_with(ltrim($request->getURL(), '/'), 'admin')) {
            return;
        }

        $config = PWASettings::current();

        if (!$config->PWAEnabled) {
            return;
        }
        if (!$config->AutoInjectPwaAssets) {
            return;
        }

        Requirements::insertHeadTags(
            '<link rel="manifest" href="/manifest.json">',
            'pwa-manifest',
        );

        if ($config->ServiceWorkerEnabled) {
            Requirements::javascript('/RegisterServiceWorker.js', ['defer' => true]);
        }
    }
}
