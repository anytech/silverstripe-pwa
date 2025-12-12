<?php

namespace SilverStripePWA\Extensions;

use SilverStripe\ORM\DataExtension;
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
use SilverStripe\Security\Member;

class PushNotificationsSiteConfigExtension extends DataExtension
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
        parent::onAfterWrite();

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
        // Test Mode - Make this prominent at the top
        $fields->addFieldToTab('Root.PushNotifications', HeaderField::create('PushTestHeader', 'Test Mode'));

        if ($this->owner->PushTestMode) {
            $fields->addFieldToTab('Root.PushNotifications', LiteralField::create('PushTestWarning',
                '<p class="message warning"><strong>TEST MODE ACTIVE</strong> - Push notifications will only be sent to the test user specified below. All other subscribers will be ignored.</p>'));
        }

        $fields->addFieldToTab('Root.PushNotifications', CheckboxField::create('PushTestMode', 'Enable Test Mode')
            ->setDescription('When enabled, push notifications are only sent to the test user below'));

        $fields->addFieldToTab('Root.PushNotifications', DropdownField::create(
            'PushTestMemberID',
            'Test User',
            Member::get()->map('ID', 'Email')->toArray()
        )->setEmptyString('-- Select Test User --')
            ->setDescription('Only this user will receive push notifications when test mode is enabled'));

        $fields->addFieldToTab('Root.PushNotifications', TextField::create('PushTestEmail', 'Or Test Email')
            ->setDescription('Alternative: Enter email address to find test user by email'));

        // Default Content
        $fields->addFieldToTab('Root.PushNotifications', HeaderField::create('PushContentHeader', 'Default Notification Content'));

        $fields->addFieldToTab('Root.PushNotifications', LiteralField::create('PushContentInfo',
            '<p class="message info">These are default values used when sending notifications. They can be overridden programmatically.</p>'));

        $fields->addFieldToTab('Root.PushNotifications', TextField::create('PushDefaultTitle', 'Default Title')
            ->setDescription('Default notification title if none is specified'));

        $fields->addFieldToTab('Root.PushNotifications', TextareaField::create('Message', 'Default Message')
            ->setRows(2)
            ->setDescription('Default message body for notifications'));

        $fields->addFieldToTab('Root.PushNotifications', NumericField::create('ttl', 'Time to Live (seconds)')
            ->setDescription('How long the push service should try to deliver the notification. Default: 86400 (24 hours)'));

        // Icons
        $fields->addFieldToTab('Root.PushNotifications', HeaderField::create('PushIconsHeader', 'Notification Icons'));

        $fields->addFieldToTab('Root.PushNotifications', UploadField::create('icon', 'Notification Icon')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png', 'jpg', 'jpeg'])
            ->setDescription('Main notification icon (recommended: 512x512px PNG)'));

        $fields->addFieldToTab('Root.PushNotifications', UploadField::create('badge', 'Badge Icon')
            ->setFolderName('pwa-assets')
            ->setAllowedExtensions(['png'])
            ->setDescription('Small monochrome icon for status bar (recommended: 128x128px PNG)'));

        // Behavior
        $fields->addFieldToTab('Root.PushNotifications', HeaderField::create('PushBehaviorHeader', 'Notification Behavior'));

        $fields->addFieldToTab('Root.PushNotifications', CheckboxField::create('PushRequireInteraction', 'Require Interaction')
            ->setDescription('Notification stays visible until user interacts (may not work on all platforms)'));

        $fields->addFieldToTab('Root.PushNotifications', CheckboxField::create('PushSilent', 'Silent Notifications')
            ->setDescription('Suppress sound and vibration (useful for data-only updates)'));

        $fields->addFieldToTab('Root.PushNotifications', CheckboxField::create('PushRenotify', 'Renotify')
            ->setDescription('Vibrate/sound again when replacing an existing notification with the same tag'));

        // Vibration
        $fields->addFieldToTab('Root.PushNotifications', HeaderField::create('PushVibrateHeader', 'Vibration Pattern'));

        $fields->addFieldToTab('Root.PushNotifications', DropdownField::create('vibrate', 'Vibration Pattern', self::$vibrationPatterns)
            ->setDescription('Pattern of vibration for notification alerts'));

        $fields->addFieldToTab('Root.PushNotifications', TextField::create('PushCustomVibrate', 'Custom Vibration Pattern')
            ->setDescription('Enter custom pattern as comma-separated milliseconds (e.g., 200,100,200). Only used if "Custom Pattern" is selected above.'));

        // Action Buttons
        $fields->addFieldToTab('Root.PushNotifications', HeaderField::create('PushActionsHeader', 'Action Buttons (Optional)'));

        $fields->addFieldToTab('Root.PushNotifications', LiteralField::create('PushActionsInfo',
            '<p class="message info">Add up to 2 action buttons to notifications. Support varies by platform.</p>'));

        $fields->addFieldToTab('Root.PushNotifications', TextField::create('PushAction1Text', 'Action 1 Text')
            ->setDescription('Text for first action button (e.g., "View", "Open")'));

        $fields->addFieldToTab('Root.PushNotifications', TextField::create('PushAction1Url', 'Action 1 URL')
            ->setDescription('URL to open when action 1 is clicked'));

        $fields->addFieldToTab('Root.PushNotifications', TextField::create('PushAction2Text', 'Action 2 Text')
            ->setDescription('Text for second action button (e.g., "Dismiss", "Later")'));

        $fields->addFieldToTab('Root.PushNotifications', TextField::create('PushAction2Url', 'Action 2 URL')
            ->setDescription('URL to open when action 2 is clicked (leave empty to just dismiss)'));
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
