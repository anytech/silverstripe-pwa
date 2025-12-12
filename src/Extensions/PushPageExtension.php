<?php

namespace SilverStripePWA\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripePWA\Services\PushNotificationService;

class PushPageExtension extends DataExtension
{
    private static $db = [
        'SendPushNotification' => 'Boolean',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Main',
            CheckboxField::create('SendPushNotification', 'Send Push Notification')
                ->setDescription('Send a push notification to all subscribers when this page is published'),
            'Content'
        );
    }

    public function onAfterPublish()
    {
        if (!$this->owner->SendPushNotification) {
            return;
        }

        $config = SiteConfig::current_site_config();

        PushNotificationService::create()
            ->setTitle($this->owner->getTitle())
            ->setBody($config->Message ?: 'New content available')
            ->setUrl($this->owner->Link())
            ->setTTL((int)($config->ttl ?: 86400))
            ->sendToAll();

        // Reset the checkbox so it doesn't send again on next publish
        $this->owner->SendPushNotification = false;
        $this->owner->writeWithoutVersion();
    }
}
