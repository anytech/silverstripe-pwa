<?php

namespace SilverStripePWA\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Security\Member;
use SilverStripePWA\Services\PushNotificationService;

class PushNotificationsSiteConfigExtension extends Extension
{
    private static $db = [
        // Test Mode
        'PushTestMode' => 'Boolean',
        'PushTestEmail' => 'Varchar(255)',

        // Default content
        'PushDefaultTitle' => 'Varchar(255)',
        'Message' => 'Text',
        'ttl' => 'Int',

        // Behavior
        'PushRequireInteraction' => 'Boolean',
        'PushSilent' => 'Boolean',
        'PushRenotify' => 'Boolean',

        // Vibration
        'vibrate' => 'Text',
        'PushCustomVibrate' => 'Varchar(255)',

        // Actions
        'PushAction1Text' => 'Varchar(50)',
        'PushAction1Url' => 'Varchar(255)',
        'PushAction2Text' => 'Varchar(50)',
        'PushAction2Url' => 'Varchar(255)'
    ];

    private static $has_one = [
        'icon' => Image::class,
        'badge' => Image::class,
        'PushTestMember' => Member::class
    ];

    private static $owns = [
        'icon',
        'badge'
    ];

    private static $defaults = [
        'PushTestMode' => false,
        'PushDefaultTitle' => 'New Notification',
        'Message' => 'You have a new update',
        'ttl' => 86400,
        'PushRequireInteraction' => false,
        'PushSilent' => false,
        'PushRenotify' => false,
        'vibrate' => '[200,100,200]'
    ];

    private static $vibrationPatterns = [
        '[200,100,200]' => 'Default - Short buzz',
        '[500,110,500,110,450,110,200,110,170,40,450,110,200,110,170,40,500]' => 'Star Wars - Imperial March',
        '[100,50,100,50,100]' => 'Quick Triple',
        '[400,100,400]' => 'Double Long',
        '[100]' => 'Single Short',
        '[1000]' => 'Single Long',
        'none' => 'No Vibration',
        'custom' => 'Custom Pattern'
    ];

    public function onAfterWrite()
    {
        $icon = $this->owner->icon();
        $badge = $this->owner->badge();

        if ($icon && $icon->exists() && !$icon->isPublished()) {
            $icon->publishSingle();
        }
        if ($badge && $badge->exists() && !$badge->isPublished()) {
            $badge->publishSingle();
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.PWA.PushNotifications', HeaderField::create('PushVapidHeader', 'VAPID Keys (Required)'));

        $vapidConfigured = $this->owner->VapidPublicKey && $this->owner->VapidPrivateKey;

        if (!$vapidConfigured) {
            $fields->addFieldToTab('Root.PWA.PushNotifications', LiteralField::create('VapidKeysWarning',
                '<p class="message error" style="font-weight:600;">'
                . '⚠ VAPID keys are not configured. Web push notifications will not work until you generate them.'
                . '</p>'));

            $fields->addFieldToTab('Root.PWA.PushNotifications', LiteralField::create('GenerateVapidButton',
                '<p><button type="button" class="btn action btn-primary" onclick="generateVapidKeys()">Generate VAPID Keys</button></p>
                <script>
                function generateVapidKeys() {
                    fetch("/pwa-generate-vapid-keys")
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.querySelector(\'input[name="VapidPublicKey"]\').value = data.publicKey;
                                document.querySelector(\'input[name="VapidPrivateKey"]\').value = data.privateKey;
                                alert("VAPID keys generated! Click Save to store them.");
                            } else {
                                alert("Error: " + data.error);
                            }
                        })
                        .catch(err => alert("Error generating keys: " + err));
                }
                </script>'));

            $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('VapidPublicKey', 'VAPID Public Key')
                ->setDescription('Will be auto-generated'));

            $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('VapidPrivateKey', 'VAPID Private Key')
                ->setDescription('Will be auto-generated'));
        } else {
            $fields->addFieldToTab('Root.PWA.PushNotifications', LiteralField::create('VapidKeysGenerated',
                '<p class="message good">✓ VAPID keys are configured. Web push is ready.</p>'));

            $fields->addFieldToTab('Root.PWA.PushNotifications', ReadonlyField::create('VapidPublicKey', 'VAPID Public Key')
                ->setDescription('Used by browsers to verify push notifications.'));

            $fields->addFieldToTab('Root.PWA.PushNotifications', ReadonlyField::create('VapidPrivateKey', 'VAPID Private Key')
                ->setDescription('Keep this secret. Used to sign push notifications.'));
        }

        $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('VapidSubject', 'VAPID Subject')
            ->setDescription('Contact email for push notifications, e.g. mailto:admin@example.com'));

        // Test Mode
        $fields->addFieldToTab('Root.PWA.PushNotifications', HeaderField::create('PushTestHeader', 'Test Mode'));

        if ($this->owner->PushTestMode) {
            $fields->addFieldToTab('Root.PWA.PushNotifications', LiteralField::create('PushTestWarning',
                '<p class="message warning"><strong>TEST MODE ACTIVE</strong> - Push notifications will only be sent to the test user specified below. All other subscribers will be ignored.</p>'));
        }

        $fields->addFieldToTab('Root.PWA.PushNotifications', CheckboxField::create('PushTestMode', 'Enable Test Mode')
            ->setDescription('When enabled, push notifications are only sent to the test user below'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', DropdownField::create(
            'PushTestMemberID',
            'Test User',
            Member::get()->map('ID', 'Email')->toArray()
        )->setEmptyString('-- Select Test User --')
            ->setDescription('Only this user will receive push notifications when test mode is enabled'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('PushTestEmail', 'Or Test Email')
            ->setDescription('Alternative: Enter email address to find test user by email'));

        // Default Content
        $fields->addFieldToTab('Root.PWA.PushNotifications', HeaderField::create('PushContentHeader', 'Default Notification Content'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', LiteralField::create('PushContentInfo',
            '<p class="message info">These are default values used when sending notifications. They can be overridden programmatically.</p>'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('PushDefaultTitle', 'Default Title')
            ->setDescription('Default notification title if none is specified'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', TextareaField::create('Message', 'Default Message')
            ->setRows(2)
            ->setDescription('Default message body for notifications'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', NumericField::create('ttl', 'Time to Live (seconds)')
            ->setDescription('How long the push service should try to deliver the notification. Default: 86400 (24 hours)'));

        // Icons
        $fields->addFieldToTab('Root.PWA.PushNotifications', HeaderField::create('PushIconsHeader', 'Notification Icons'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', UploadField::create('icon', 'Notification Icon')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'jpg', 'jpeg'])
            ->setDescription('Main notification icon (recommended: 512x512px PNG)'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', UploadField::create('badge', 'Badge Icon')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png'])
            ->setDescription('Small monochrome icon for status bar (recommended: 128x128px PNG)'));

        // Behavior
        $fields->addFieldToTab('Root.PWA.PushNotifications', HeaderField::create('PushBehaviorHeader', 'Notification Behavior'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', CheckboxField::create('PushRequireInteraction', 'Require Interaction')
            ->setDescription('Notification stays visible until user interacts (may not work on all platforms)'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', CheckboxField::create('PushSilent', 'Silent Notifications')
            ->setDescription('Suppress sound and vibration (useful for data-only updates)'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', CheckboxField::create('PushRenotify', 'Renotify')
            ->setDescription('Vibrate/sound again when replacing an existing notification with the same tag'));

        // Vibration
        $fields->addFieldToTab('Root.PWA.PushNotifications', HeaderField::create('PushVibrateHeader', 'Vibration Pattern'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', DropdownField::create('vibrate', 'Vibration Pattern', self::$vibrationPatterns)
            ->setDescription('Pattern of vibration for notification alerts'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('PushCustomVibrate', 'Custom Vibration Pattern')
            ->setDescription('Enter custom pattern as comma-separated milliseconds (e.g., 200,100,200). Only used if "Custom Pattern" is selected above.'));

        // Action Buttons
        $fields->addFieldToTab('Root.PWA.PushNotifications', HeaderField::create('PushActionsHeader', 'Action Buttons (Optional)'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', LiteralField::create('PushActionsInfo',
            '<p class="message info">Add up to 2 action buttons to notifications. Support varies by platform.</p>'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('PushAction1Text', 'Action 1 Text')
            ->setDescription('Text for first action button (e.g., "View", "Open")'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('PushAction1Url', 'Action 1 URL')
            ->setDescription('URL to open when action 1 is clicked'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('PushAction2Text', 'Action 2 Text')
            ->setDescription('Text for second action button (e.g., "Dismiss", "Later")'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', TextField::create('PushAction2Url', 'Action 2 URL')
            ->setDescription('URL to open when action 2 is clicked (leave empty to just dismiss)'));

        // Send Test Push button
        $fields->addFieldToTab('Root.PWA.PushNotifications', HeaderField::create('PushSendTestHeader', 'Send Test Notification'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', LiteralField::create('PushSendTestInfo',
            '<p class="message info">Send a test notification using the default title and message configured above. ' .
            'If test mode is enabled, only the test user will receive it.</p>'));

        $fields->addFieldToTab('Root.PWA.PushNotifications', LiteralField::create('SendTestPushButton',
            '<a href="/pwa-send-test-push" class="btn btn-primary font-icon-rocket" target="_blank" ' .
            'onclick="window.open(this.href, \'_blank\', \'width=500,height=300\'); return false;">' .
            'Send Test Push Notification</a>'));
    }

    /**
     * Get the test member (if test mode is enabled)
     */
    public function getTestMember(): ?Member
    {
        if (!$this->owner->PushTestMode) {
            return null;
        }

        // Try by ID first
        if ($this->owner->PushTestMemberID) {
            return Member::get()->byID($this->owner->PushTestMemberID);
        }

        // Fall back to email
        if ($this->owner->PushTestEmail) {
            return Member::get()->filter('Email', $this->owner->PushTestEmail)->first();
        }

        return null;
    }

    /**
     * Check if test mode is active
     */
    public function isTestModeActive(): bool
    {
        return (bool)$this->owner->PushTestMode;
    }

    /**
     * Get the vibration pattern as array
     */
    public function getVibrationPattern(): array
    {
        $pattern = $this->owner->vibrate;

        if ($pattern === 'none') {
            return [];
        }

        if ($pattern === 'custom' && $this->owner->PushCustomVibrate) {
            return array_map('intval', explode(',', $this->owner->PushCustomVibrate));
        }

        // Parse the stored JSON array string
        if ($pattern && $pattern !== 'custom') {
            $decoded = json_decode($pattern);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [200, 100, 200];
    }

    /**
     * Get action buttons as array
     */
    public function getNotificationActions(): array
    {
        $actions = [];

        if ($this->owner->PushAction1Text) {
            $actions[] = [
                'action' => 'action1',
                'title' => $this->owner->PushAction1Text,
                'url' => $this->owner->PushAction1Url ?: '/'
            ];
        }

        if ($this->owner->PushAction2Text) {
            $actions[] = [
                'action' => 'action2',
                'title' => $this->owner->PushAction2Text,
                'url' => $this->owner->PushAction2Url ?: ''
            ];
        }

        return $actions;
    }
}
