<?php

namespace SilverStripePWA\Services;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Security\Member;
use SilverStripePWA\Models\Subscriber;
use SilverStripePWA\Services\WebPushService;

/**
 * Flexible Push Notification Service
 *
 * Use this service to send push notifications from anywhere in your application.
 *
 * Examples:
 *
 * // Send to all subscribers
 * PushNotificationService::notify('New Update', 'Check out the latest features!');
 *
 * // Send to a specific member
 * PushNotificationService::notifyMember($member, 'Hello!', 'You have a new message');
 *
 * // Send to multiple members
 * PushNotificationService::notifyMembers($memberList, 'Alert', 'Important update');
 *
 * // Send with full options
 * PushNotificationService::create()
 *     ->setTitle('New Message')
 *     ->setBody('You have received a new message')
 *     ->setUrl('/messages/')
 *     ->setIcon('/path/to/icon.png')
 *     ->setTag('message-123')
 *     ->sendToMember($member);
 */
class PushNotificationService
{
    private string $title = '';
    private string $body = '';
    private ?string $url = null;
    private ?string $icon = null;
    private ?string $badge = null;
    private ?string $tag = null;
    private array $vibrate = [200, 100, 200];
    private int $ttl = 86400;
    private array $data = [];
    private bool $debug = false;

    public function __construct()
    {
        $config = SiteConfig::current_site_config();
        $this->debug = (bool)$config->ServiceWorkerDebug;
    }

    /**
     * Log debug message if debug mode is enabled
     */
    private function log(string $message, array $context = []): void
    {
        if (!$this->debug) {
            return;
        }

        $logger = Injector::inst()->get(LoggerInterface::class);
        $logger->debug('[PWA PushService] ' . $message, $context);
    }

    /**
     * Create a new instance for fluent API
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Quick send to all subscribers
     */
    public static function notify(string $title, string $body, ?string $url = null): array
    {
        return self::create()
            ->setTitle($title)
            ->setBody($body)
            ->setUrl($url)
            ->sendToAll();
    }

    /**
     * Quick send to a specific member
     */
    public static function notifyMember(Member $member, string $title, string $body, ?string $url = null): array
    {
        return self::create()
            ->setTitle($title)
            ->setBody($body)
            ->setUrl($url)
            ->sendToMember($member);
    }

    /**
     * Quick send to multiple members
     */
    public static function notifyMembers($members, string $title, string $body, ?string $url = null): array
    {
        return self::create()
            ->setTitle($title)
            ->setBody($body)
            ->setUrl($url)
            ->sendToMembers($members);
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function setBadge(?string $badge): self
    {
        $this->badge = $badge;
        return $this;
    }

    /**
     * Set notification tag (used for grouping/replacing notifications)
     */
    public function setTag(?string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function setVibrate(array $vibrate): self
    {
        $this->vibrate = $vibrate;
        return $this;
    }

    public function setTTL(int $ttl): self
    {
        $this->ttl = $ttl;
        return $this;
    }

    /**
     * Set custom data payload
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Send to all subscribers (respects test mode)
     */
    public function sendToAll(): array
    {
        $config = SiteConfig::current_site_config();

        $this->log('sendToAll called');

        // Check if push is globally disabled
        if ($config->hasMethod('PushNotificationsEnabled') && !$config->PushNotificationsEnabled) {
            $this->log('Push notifications are disabled globally');
            return ['status' => 'Push notifications are disabled'];
        }

        // Check test mode - only send to test user
        if ($config->PushTestMode) {
            $this->log('Test mode is enabled');
            $testMember = $config->getTestMember();
            if ($testMember) {
                $this->log('Sending to test member only', ['member_id' => $testMember->ID, 'email' => $testMember->Email]);
                return $this->sendToMember($testMember);
            }
            $this->log('Test mode enabled but no test user configured');
            return ['status' => 'Test mode enabled but no test user configured'];
        }

        $subscribers = Subscriber::get();
        $this->log('Sending to all subscribers', ['count' => $subscribers->count()]);
        return $this->sendToSubscribers($subscribers);
    }

    /**
     * Send to a specific member's subscriptions
     */
    public function sendToMember(Member $member): array
    {
        $config = SiteConfig::current_site_config();

        // Check if push is globally disabled
        if ($config->hasMethod('PushNotificationsEnabled') && !$config->PushNotificationsEnabled) {
            return ['status' => 'Push notifications are disabled'];
        }

        // In test mode, only allow sending to test member
        if ($config->PushTestMode) {
            $testMember = $config->getTestMember();
            if (!$testMember || $testMember->ID !== $member->ID) {
                return ['status' => 'Test mode: skipped (not test user)'];
            }
        }

        $subscribers = Subscriber::get()->filter('MemberID', $member->ID);
        return $this->sendToSubscribers($subscribers);
    }

    /**
     * Send to multiple members (respects test mode)
     *
     * @param \SilverStripe\ORM\DataList|array $members
     */
    public function sendToMembers($members): array
    {
        $config = SiteConfig::current_site_config();

        // Check if push is globally disabled
        if ($config->hasMethod('PushNotificationsEnabled') && !$config->PushNotificationsEnabled) {
            return ['status' => 'Push notifications are disabled'];
        }

        // In test mode, only send to test member if in the list
        if ($config->PushTestMode) {
            $testMember = $config->getTestMember();
            if ($testMember) {
                foreach ($members as $member) {
                    if ($member->ID === $testMember->ID) {
                        return $this->sendToMember($testMember);
                    }
                }
            }
            return ['status' => 'Test mode: no matching test user in recipients'];
        }

        $memberIDs = [];

        foreach ($members as $member) {
            $memberIDs[] = $member->ID;
        }

        if (empty($memberIDs)) {
            return ['status' => 'No members provided'];
        }

        $subscribers = Subscriber::get()->filter('MemberID', $memberIDs);
        return $this->sendToSubscribers($subscribers);
    }

    /**
     * Send to specific subscriber records (bypasses test mode - use with caution)
     *
     * @param \SilverStripe\ORM\DataList $subscribers
     */
    public function sendToSubscribers($subscribers): array
    {
        $count = $subscribers->count();
        $this->log('Starting push notification send', [
            'subscriber_count' => $count,
            'title' => $this->title,
            'body' => $this->body
        ]);

        if ($count === 0) {
            $this->log('No subscribers found');
            return ['status' => 'No subscribers found'];
        }

        $config = SiteConfig::current_site_config();

        if (!$config->VapidPublicKey || !$config->VapidPrivateKey) {
            $this->log('VAPID keys not configured');
            return ['error' => 'VAPID keys not configured. Please generate keys in Settings > Manifest.'];
        }

        $vapidSubject = $config->VapidSubject ?: 'mailto:admin@example.com';
        $this->log('Using VAPID subject: ' . $vapidSubject);

        $webPush = new WebPushService(
            $config->VapidPublicKey,
            $config->VapidPrivateKey,
            $vapidSubject
        );

        $payload = $this->buildPayload($config);
        $this->log('Built payload', ['payload' => $payload]);

        $response = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($subscribers as $subscriber) {
            $this->log('Sending to subscriber', [
                'id' => $subscriber->ID,
                'member_id' => $subscriber->MemberID,
                'endpoint' => substr($subscriber->endpoint, 0, 50) . '...'
            ]);

            $subscription = [
                'endpoint' => $subscriber->endpoint,
                'publicKey' => $subscriber->publicKey,
                'authToken' => $subscriber->authToken
            ];

            $result = $webPush->send($subscription, $payload, $this->ttl);

            if ($result['success']) {
                $response[$subscriber->endpoint] = 'Delivered';
                $successCount++;
            } else {
                if ($result['expired']) {
                    $subscriber->delete();
                    $response[$subscriber->endpoint] = 'Subscription expired - removed';
                    $this->log('Removed expired subscription', ['id' => $subscriber->ID]);
                } else {
                    $response[$subscriber->endpoint] = 'Failed: ' . $result['message'];
                }
                $failCount++;
            }
        }

        $this->log('Push notification send complete', [
            'success' => $successCount,
            'failed' => $failCount
        ]);

        return $response;
    }

    /**
     * Build the notification payload
     */
    private function buildPayload(SiteConfig $config): string
    {
        $payload = [
            'title' => $this->title,
            'message' => $this->body,
        ];

        // Use provided values or fall back to config defaults
        if ($this->url) {
            $payload['url'] = $this->url;
        }

        if ($this->icon) {
            $payload['icon'] = $this->icon;
        } elseif ($config->icon() && $config->icon()->exists()) {
            $payload['icon'] = $config->icon()->Fill(512, 512)->getAbsoluteURL();
        }

        if ($this->badge) {
            $payload['badge'] = $this->badge;
        } elseif ($config->badge() && $config->badge()->exists()) {
            $payload['badge'] = $config->badge()->Fill(128, 128)->getAbsoluteURL();
        }

        if ($this->tag) {
            $payload['tag'] = $this->tag;
        }

        $payload['vibrate'] = $this->vibrate;
        $payload['ttl'] = $this->ttl;

        if (!empty($this->data)) {
            $payload['data'] = $this->data;
        }

        return json_encode($payload);
    }
}
