<?php

namespace SilverStripePWA\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;

class OfflinePageSiteConfigExtension extends DataExtension
{
    private static $db = [
        // Offline page content
        'OfflineTitle' => 'Varchar(255)',
        'OfflineMessage' => 'Text',
        'OfflineButtonText' => 'Varchar(100)',

        // Offline page styling
        'OfflineBackgroundColor' => 'Varchar(7)',
        'OfflineTextColor' => 'Varchar(7)',
        'OfflineAccentColor' => 'Varchar(7)',
        'OfflineIcon' => 'Varchar(10)'
    ];

    private static $defaults = [
        'OfflineTitle' => "You're Offline",
        'OfflineMessage' => "It looks like you've lost your internet connection. Please check your network and try again.",
        'OfflineButtonText' => 'Try Again',
        'OfflineBackgroundColor' => '#1a1a2e',
        'OfflineTextColor' => '#ffffff',
        'OfflineAccentColor' => '#e94560',
        'OfflineIcon' => '📡'
    ];

    private static $offline_icons = [
        '📡' => '📡 Signal',
        '🔌' => '🔌 Plug',
        '📴' => '📴 Phone Off',
        '🌐' => '🌐 Globe',
        '⚡' => '⚡ Lightning',
        '🔄' => '🔄 Refresh',
        '⏳' => '⏳ Hourglass',
        '🛜' => '🛜 WiFi',
        '❌' => '❌ Cross',
        '⚠️' => '⚠️ Warning'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Content
        $fields->addFieldToTab('Root.OfflinePage', HeaderField::create('OfflineContentHeader', 'Offline Page Content'));

        $fields->addFieldToTab('Root.OfflinePage', LiteralField::create('OfflineInfo',
            '<p class="message info">Customize the page shown when users are offline and try to navigate to a page that isn\'t cached.</p>'));

        $fields->addFieldToTab('Root.OfflinePage', TextField::create('OfflineTitle', 'Title')
            ->setDescription('Main heading shown on the offline page'));

        $fields->addFieldToTab('Root.OfflinePage', TextareaField::create('OfflineMessage', 'Message')
            ->setRows(3)
            ->setDescription('Explanation text shown below the title'));

        $fields->addFieldToTab('Root.OfflinePage', TextField::create('OfflineButtonText', 'Button Text')
            ->setDescription('Text for the retry button'));

        $fields->addFieldToTab('Root.OfflinePage', \SilverStripe\Forms\DropdownField::create('OfflineIcon', 'Icon', self::$offline_icons)
            ->setDescription('Emoji icon shown on the offline page'));

        // Styling
        $fields->addFieldToTab('Root.OfflinePage', HeaderField::create('OfflineStyleHeader', 'Offline Page Styling'));

        $fields->addFieldToTab('Root.OfflinePage', TextField::create('OfflineBackgroundColor', 'Background Color')
            ->setAttribute('type', 'color')
            ->setDescription('Background color of the offline page'));

        $fields->addFieldToTab('Root.OfflinePage', TextField::create('OfflineTextColor', 'Text Color')
            ->setAttribute('type', 'color')
            ->setDescription('Color of the text on the offline page'));

        $fields->addFieldToTab('Root.OfflinePage', TextField::create('OfflineAccentColor', 'Accent Color')
            ->setAttribute('type', 'color')
            ->setDescription('Color for the title and button'));

        // Preview
        $fields->addFieldToTab('Root.OfflinePage', HeaderField::create('OfflinePreviewHeader', 'Preview'));

        $bgColor = $this->owner->OfflineBackgroundColor ?: '#1a1a2e';
        $textColor = $this->owner->OfflineTextColor ?: '#ffffff';
        $accentColor = $this->owner->OfflineAccentColor ?: '#e94560';
        $icon = $this->owner->OfflineIcon ?: '📡';
        $title = $this->owner->OfflineTitle ?: "You're Offline";
        $message = $this->owner->OfflineMessage ?: "It looks like you've lost your internet connection.";
        $button = $this->owner->OfflineButtonText ?: 'Try Again';

        $fields->addFieldToTab('Root.OfflinePage', LiteralField::create('OfflinePreview',
            '<div style="background: ' . htmlspecialchars($bgColor) . '; color: ' . htmlspecialchars($textColor) . '; padding: 40px; text-align: center; border-radius: 8px; margin-top: 10px;">
                <div style="font-size: 48px; margin-bottom: 16px;">' . htmlspecialchars($icon) . '</div>
                <h2 style="color: ' . htmlspecialchars($accentColor) . '; margin: 0 0 12px 0;">' . htmlspecialchars($title) . '</h2>
                <p style="opacity: 0.8; margin: 0 0 24px 0;">' . htmlspecialchars($message) . '</p>
                <span style="background: ' . htmlspecialchars($accentColor) . '; color: #fff; padding: 10px 24px; border-radius: 6px; display: inline-block;">' . htmlspecialchars($button) . '</span>
            </div>'));
    }
}
