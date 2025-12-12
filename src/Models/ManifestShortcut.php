<?php

namespace SilverStripePWA\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * PWA Manifest Shortcut
 *
 * Shortcuts appear when long-pressing the app icon (mobile)
 * or right-clicking on the taskbar icon (desktop)
 */
class ManifestShortcut extends DataObject
{
    private static $table_name = 'ManifestShortcut';

    private static $db = [
        'Name' => 'Varchar(255)',
        'ShortName' => 'Varchar(25)',
        'Description' => 'Text',
        'Url' => 'Varchar(255)',
        'SortOrder' => 'Int'
    ];

    private static $has_one = [
        'Icon' => Image::class,
        'SiteConfig' => SiteConfig::class
    ];

    private static $owns = [
        'Icon'
    ];

    private static $summary_fields = [
        'Name' => 'Name',
        'Url' => 'URL',
        'Icon.CMSThumbnail' => 'Icon'
    ];

    private static $default_sort = 'SortOrder ASC';

    public function getCMSFields()
    {
        $fields = FieldList::create(
            TextField::create('Name', 'Name')
                ->setDescription('Display name for the shortcut'),
            TextField::create('ShortName', 'Short Name (Optional)')
                ->setDescription('Shorter name if space is limited'),
            TextareaField::create('Description', 'Description (Optional)')
                ->setRows(2)
                ->setDescription('Brief description of what this shortcut does'),
            TextField::create('Url', 'URL')
                ->setDescription('URL to open (e.g., /contact, /dashboard)'),
            UploadField::create('Icon', 'Icon (Optional)')
                ->setFolderName('pwa-assets/shortcuts')
                ->setAllowedExtensions(['png', 'svg'])
                ->setDescription('96x96px icon for this shortcut')
        );

        return $fields;
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        $icon = $this->Icon();
        if ($icon && $icon->exists() && !$icon->isPublished()) {
            $icon->publishSingle();
        }
    }

    /**
     * Convert to manifest shortcut array
     */
    public function toManifestArray(): array
    {
        $shortcut = [
            'name' => $this->Name,
            'url' => $this->Url
        ];

        if ($this->ShortName) {
            $shortcut['short_name'] = $this->ShortName;
        }

        if ($this->Description) {
            $shortcut['description'] = $this->Description;
        }

        if ($this->Icon() && $this->Icon()->exists()) {
            $shortcut['icons'] = [
                [
                    'src' => $this->Icon()->Fill(96, 96)->getAbsoluteURL(),
                    'sizes' => '96x96',
                    'type' => 'image/png'
                ]
            ];
        }

        return $shortcut;
    }
}
