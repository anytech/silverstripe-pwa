<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripePWA\Services\PushNotificationService;

class SendTestPushController extends Controller
{
    private static $allowed_actions = [
        'index'
    ];

    public function index(HTTPRequest $request)
    {
        // Check admin permission
        if (!Permission::check('ADMIN')) {
            return $this->httpError(403, 'Admin access required');
        }

        $config = SiteConfig::current_site_config();

        $title = $config->PushDefaultTitle ?: 'Test Notification';
        $message = $config->Message ?: 'This is a test push notification';

        $result = PushNotificationService::create()
            ->setTitle($title)
            ->setBody($message)
            ->setUrl('/')
            ->sendToAll();

        $html = '<html><head><title>Test Push Result</title>';
        $html .= '<style>body { font-family: sans-serif; padding: 20px; } ';
        $html .= '.success { color: green; } .error { color: red; } ';
        $html .= 'pre { background: #f5f5f5; padding: 10px; overflow: auto; }</style></head>';
        $html .= '<body><h2>Test Push Result</h2>';

        if (isset($result['status'])) {
            $html .= '<p class="error">' . htmlspecialchars($result['status']) . '</p>';
        } elseif (isset($result['error'])) {
            $html .= '<p class="error">' . htmlspecialchars($result['error']) . '</p>';
        } else {
            $successCount = 0;
            $failCount = 0;

            foreach ($result as $endpoint => $status) {
                if ($status === 'Delivered') {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }

            $html .= '<p class="success">Sent: ' . $successCount . '</p>';
            if ($failCount > 0) {
                $html .= '<p class="error">Failed: ' . $failCount . '</p>';
            }

            $html .= '<pre>' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . '</pre>';
        }

        $html .= '<p><button onclick="window.close()">Close</button></p>';
        $html .= '</body></html>';

        return $html;
    }
}
