<?php

namespace SilverStripePWA\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;

class PushLog extends DataObject
{
    private static $table_name = 'PushLog';
    private static $singular_name = 'Push';
    private static $plural_name = 'Push History';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Body' => 'Text',
        'URL' => 'Varchar(500)',
        'Target' => "Enum('All,Member,Members,Subscribers,API','Subscribers')",
        'TestMode' => 'Boolean',
        'Recipients' => 'Int',
        'Delivered' => 'Int',
        'Failed' => 'Int',
        'Expired' => 'Int',
    ];

    private static $has_many = [
        'Deliveries' => PushDelivery::class,
    ];

    private static $cascade_deletes = [
        'Deliveries',
    ];

    private static $default_sort = 'Created DESC';

    private static $summary_fields = [
        'Created.Nice' => 'Sent',
        'Title' => 'Title',
        'Body.Summary' => 'Message',
        'Target' => 'Target',
        'TestMode.Nice' => 'Test Mode',
        'Recipients' => 'Recipients',
        'Delivered' => 'Delivered',
        'Failed' => 'Failed',
        'Expired' => 'Expired',
    ];

    private static $searchable_fields = [
        'Title',
        'Body',
        'Target',
    ];

    public static function start(string $title, string $body, ?string $url, string $target, int $recipients): self
    {
        $settings = PWASettings::current();
        self::prune($settings->PushHistoryDays);

        $log = self::create();
        $log->Title = $title;
        $log->Body = $body;
        $log->URL = $url;
        $log->Target = $target;
        $log->TestMode = (bool)$settings->PushTestMode;
        $log->Recipients = $recipients;
        $log->write();
        return $log;
    }

    public static function prune(int $days): void
    {
        if ($days <= 0) {
            return;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

        SQLDelete::create('"PushDelivery"', ['"Created" < ?' => $cutoff])->execute();
        SQLDelete::create('"PushLog"', ['"Created" < ?' => $cutoff])->execute();
    }

    public function record(Subscriber $subscriber, array $result): void
    {
        $status = 'Delivered';
        if (!$result['success']) {
            $status = $result['expired'] ? 'Expired' : 'Failed';
        }

        PushDelivery::create([
            'PushLogID' => $this->ID,
            'MemberID' => $subscriber->MemberID,
            'Type' => $subscriber->Type,
            'Platform' => $subscriber->Platform,
            'Endpoint' => $subscriber->endpoint,
            'Status' => $status,
            'Message' => $result['success'] ? '' : $result['message'],
        ])->write();

        $this->$status++;
    }

    public function getCMSFields() {
        $fields = FieldList::create(
            ReadonlyField::create('Created', 'Sent'),
            ReadonlyField::create('Title', 'Title'),
            ReadonlyField::create('Body', 'Message'),
            ReadonlyField::create('URL', 'Click URL'),
            ReadonlyField::create('Target', 'Target'),
            ReadonlyField::create('TestModeNice', 'Test Mode', $this->dbObject('TestMode')->Nice()),
            ReadonlyField::create('Recipients', 'Recipients'),
            ReadonlyField::create('Delivered', 'Delivered'),
            ReadonlyField::create('Failed', 'Failed'),
            ReadonlyField::create('Expired', 'Expired')
        );

        if ($this->exists()) {
            $fields->push(GridField::create(
                'Deliveries',
                'Deliveries',
                $this->Deliveries(),
                GridFieldConfig_RecordViewer::create()
            ));
        }

        return $fields;
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canEdit($member = null)
    {
        return false;
    }
}
