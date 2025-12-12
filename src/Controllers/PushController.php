<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripePWA\Models\Subscriber;
use SilverStripePWA\Services\WebPushService;

class PushController extends Controller
{
    /**
     * Send push notification to all subscribers
     *
     * @param string $content JSON payload for the notification
     * @return string JSON response with delivery status
     */
    public function sendPush(string $content): string
    {
        $subscribers = Subscriber::get();

        if ($subscribers->count() === 0) {
            return json_encode(['status' => 'No subscribers found']);
        }

        $config = SiteConfig::current_site_config();

        if (!$config->VapidPublicKey || !$config->VapidPrivateKey) {
            return json_encode(['error' => 'VAPID keys not configured. Please generate keys in Settings > Manifest.']);
        }

        $vapidSubject = $config->VapidSubject ?: 'mailto:admin@example.com';

        $webPush = new WebPushService(
            $config->VapidPublicKey,
            $config->VapidPrivateKey,
            $vapidSubject
        );

        $response = [];

        foreach ($subscribers as $subscriber) {
            $subscription = [
                'endpoint' => $subscriber->endpoint,
                'publicKey' => $subscriber->publicKey,
                'authToken' => $subscriber->authToken
            ];

            $result = $webPush->send($subscription, $content);

            if ($result['success']) {
                $response[$subscriber->endpoint] = 'Delivered';
            } else {
                if ($result['expired']) {
                    $subscriber->delete();
                    $response[$subscriber->endpoint] = 'Subscription expired - removed from database';
                } else {
                    $response[$subscriber->endpoint] = 'Failed: ' . $result['message'];
                }
            }
        }

        $this->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
        return json_encode($response);
    }
}
