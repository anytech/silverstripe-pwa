<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripePWA\Models\PWASettings;

class RegisterServiceWorkerController extends Controller {

    /**
     * @var array
     */
    private static $allowed_actions = [
        'index'
    ];
    
    /**
     * @config
     */
    private static $debug_mode = false;

    /**
     * Default controller action for the service-worker.js file
     *
     * @return mixed
     */
    public function index($url) {
        $this->getResponse()->addHeader('Content-Type', 'application/javascript; charset="utf-8"');
        return $this->renderWith('RegisterServiceWorker');
    }
    
    /**
     * Base URL
     * @return varchar
     */
    public function BaseUrl() {
        return Director::baseURL();
    }

    /**
     * Public Key
     * @return string
     */
    public function PublicKey() {
        $config = PWASettings::current();
        return $config->VapidPublicKey ?: '';
    }
    
    /**
     * Debug mode
     * @return bool
     */
    public function DebugMode() {
        if (Director::isDev()) {
            return true;
        }
        if (PWASettings::current()->ServiceWorkerDebug) {
            return true;
        }
        return $this->config()->get('debug_mode');
    }
}
