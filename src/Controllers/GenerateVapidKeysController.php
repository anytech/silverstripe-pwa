<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Permission;
use SilverStripePWA\Services\WebPushService;

class GenerateVapidKeysController extends Controller
{
    private static $allowed_actions = [
        'index'
    ];

    public function index(HTTPRequest $request)
    {
        // Only allow admins to generate keys
        if (!Permission::check('ADMIN')) {
            $this->getResponse()->addHeader('Content-Type', 'application/json');
            return json_encode(['success' => false, 'error' => 'Permission denied']);
        }

        try {
            $keys = WebPushService::generateVapidKeys();

            $this->getResponse()->addHeader('Content-Type', 'application/json');
            return json_encode([
                'success' => true,
                'publicKey' => $keys['publicKey'],
                'privateKey' => $keys['privateKey']
            ]);
        } catch (\Exception $e) {
            $this->getResponse()->addHeader('Content-Type', 'application/json');
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
