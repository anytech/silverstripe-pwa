<?php

namespace SilverStripePWA\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripePWA\Models\Subscriber;

class SubscriberAdmin extends ModelAdmin
{
    private static $managed_models = [
        Subscriber::class
    ];

    private static $url_segment = 'push-subscribers';

    private static $menu_title = 'Push Subscribers';

    private static $menu_icon_class = 'font-icon-attention';

    public function getList()
    {
        $list = parent::getList();

        // Default sort by most recent
        return $list->sort('Created', 'DESC');
    }

    public function getExportFields()
    {
        return [
            'ID' => 'ID',
            'Member.Email' => 'Member Email',
            'Member.FirstName' => 'First Name',
            'Member.Surname' => 'Surname',
            'endpoint' => 'Endpoint',
            'Created' => 'Subscribed Date'
        ];
    }
}
