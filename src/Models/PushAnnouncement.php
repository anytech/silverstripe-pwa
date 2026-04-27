<?php

namespace SilverStripePWA\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripePWA\Services\PushNotificationService;
use LeKoala\CmsActions\CustomAction;

class PushAnnouncement extends DataObject
{
    private static $table_name = 'PushAnnouncement';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Message' => 'Text',
        'URL' => 'Varchar(500)',
        'SentAt' => 'Datetime',
        'SubscriberCount' => 'Int',
        'Status' => "Enum('Draft,Sent','Draft')",
    ];

    private static $default_sort = 'Created DESC';

    private static $summary_fields = [
        'Created.Nice' => 'Created',
        'Title' => 'Title',
        'Message.Summary' => 'Message',
        'Status' => 'Status',
        'SentAt.Nice' => 'Sent At',
        'SubscriberCount' => 'Recipients',
    ];

    private static $searchable_fields = [
        'Title',
        'Message',
        'Status',
    ];

    public function getCMSFields() {
        $fields = FieldList::create();

        if ($this->Status === 'Sent') {
            $fields->push(ReadonlyField::create('Title', 'Title'));
            $fields->push(ReadonlyField::create('Message', 'Message'));
            $fields->push(ReadonlyField::create('URL', 'Click URL'));
            $fields->push(ReadonlyField::create('Status', 'Status'));
            $fields->push(ReadonlyField::create('SentAt', 'Sent At'));
            $fields->push(ReadonlyField::create('SubscriberCount', 'Recipients'));
            return $fields;
        }

        $fields->push(TextField::create('Title', 'Title')
            ->setDescription('The notification title (shown in bold).'));
        $fields->push(TextareaField::create('Message', 'Message')
            ->setRows(3)
            ->setDescription('The notification body text.'));
        $fields->push(TextField::create('URL', 'Click URL (optional)')
            ->setDescription('Where to navigate when tapped, e.g. /shop/sale/'));

        return $fields;
    }

    public function getCMSActions() {
        $actions = parent::getCMSActions();

        if ($this->exists() && $this->Status !== 'Sent') {
            $actions->push(CustomAction::create('doSendPush', 'Send Push Notification')
                ->setButtonIcon('rocket')
                ->setButtonType('primary')
                ->setShouldRefresh(true));
        }

        return $actions;
    }

    public function doSendPush(): string {
        if ($this->Status === 'Sent') {
            return 'This announcement has already been sent.';
        }
        if (!$this->Title || !$this->Message) {
            return 'Title and Message are required.';
        }

        $push = PushNotificationService::create()
            ->setTitle($this->Title)
            ->setBody($this->Message);

        if ($this->URL) {
            $push->setUrl($this->URL);
        }

        $result = $push->sendToAll();

        $count = 0;
        foreach ($result as $status) {
            if ($status === 'Delivered') {
                $count++;
            }
        }

        $this->Status = 'Sent';
        $this->SentAt = date('Y-m-d H:i:s');
        $this->SubscriberCount = $count;
        $this->write();

        if (isset($result['status'])) {
            return $result['status'];
        }

        return 'Push notification sent to ' . $count . ' subscriber(s).';
    }
}
