<?php

namespace SilverStripePWA\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripePWA\Models\PushAnnouncement;
use SilverStripePWA\Models\Subscriber;

class SubscriberAdmin extends ModelAdmin
{
    private static $managed_models = [
        PushAnnouncement::class => ['title' => 'Announcements'],
        Subscriber::class => ['title' => 'Subscribers'],
    ];

    private static $url_segment = 'push';

    private static $menu_title = 'Push Notifications';

    private static $menu_icon_class = 'font-icon-mobile';

    public function getList() {
        $list = parent::getList();
        return $list->sort('Created', 'DESC');
    }

    public function getExportFields() {
        if ($this->modelClass === Subscriber::class) {
            return [
                'ID' => 'ID',
                'Type' => 'Type',
                'Platform' => 'Platform',
                'Member.Email' => 'Member Email',
                'Member.FirstName' => 'First Name',
                'Member.Surname' => 'Surname',
                'endpoint' => 'Endpoint / Token',
                'Created' => 'Subscribed Date',
            ];
        }

        return parent::getExportFields();
    }
}
