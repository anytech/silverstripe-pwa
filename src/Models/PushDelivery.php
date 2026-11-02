<?php

namespace SilverStripePWA\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Member;

class PushDelivery extends DataObject
{
    private static $table_name = 'PushDelivery';
    private static $singular_name = 'Delivery';
    private static $plural_name = 'Deliveries';

    private static $db = [
        'Type' => "Enum('web,expo','web')",
        'Platform' => "Enum('ios,android','')",
        'Endpoint' => 'Text',
        'Status' => "Enum('Delivered,Failed,Expired','Delivered')",
        'Message' => 'Text',
    ];

    private static $has_one = [
        'PushLog' => PushLog::class,
        'Member' => Member::class,
    ];

    private static $default_sort = 'ID ASC';

    private static $summary_fields = [
        'Status' => 'Status',
        'Type' => 'Type',
        'Platform' => 'Platform',
        'Member.Email' => 'Member',
        'EndpointSummary' => 'Endpoint / Token',
        'Message.Summary' => 'Error',
    ];

    public function getCMSFields() {
        $fields = FieldList::create(
            ReadonlyField::create('Status', 'Status'),
            ReadonlyField::create('Type', 'Type'),
            ReadonlyField::create('Platform', 'Platform'),
            ReadonlyField::create('Endpoint', 'Endpoint / Token'),
            ReadonlyField::create('Message', 'Error')
        );

        if ($this->Member()->exists()) {
            $fields->push(ReadonlyField::create('MemberEmail', 'Member', $this->Member()->Email));
        }

        return $fields;
    }

    public function getEndpointSummary(): string {
        if (strlen($this->Endpoint) > 50) {
            return substr($this->Endpoint, 0, 50) . '...';
        }
        return $this->Endpoint;
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
