<?php

namespace SilverStripePWA\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class Subscriber extends DataObject
{
    private static $table_name = "Subscriber";

    private static $db = [
        'Type' => "Enum('web,expo','web')",
        'Platform' => "Enum('ios,android','')",
        'endpoint' => 'Text',
        'publicKey' => 'Text',
        'authToken' => 'Text',
        'contentEncoding' => 'Text',
    ];

    private static $has_one = [
        'Member' => Member::class,
    ];

    private static $summary_fields = [
        'ID' => 'ID',
        'Type' => 'Type',
        'Platform' => 'Platform',
        'Member.Email' => 'Member',
        'EndpointSummary' => 'Endpoint / Token',
        'Created' => 'Subscribed',
    ];

    public function getCMSFields() {
        $fields = FieldList::create(
            ReadonlyField::create('Type', 'Type'),
            ReadonlyField::create('Platform', 'Platform'),
            ReadonlyField::create('endpoint', 'Endpoint / Token')
        );

        if ($this->Type === 'web') {
            $fields->push(ReadonlyField::create('publicKey', 'Public Key'));
            $fields->push(ReadonlyField::create('authToken', 'Auth Token'));
            $fields->push(ReadonlyField::create('contentEncoding', 'Content Encoding'));
        }

        if ($this->Member()->exists()) {
            $fields->push(ReadonlyField::create('MemberEmail', 'Member', $this->Member()->Email));
        }

        return $fields;
    }

    public function getEndpointSummary(): string {
        if (strlen($this->endpoint) > 50) {
            return substr($this->endpoint, 0, 50) . '...';
        }
        return $this->endpoint;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        if (!$this->Type) {
            $this->Type = 'web';
        }

        if (!$this->MemberID && $member = Security::getCurrentUser()) {
            $this->MemberID = $member->ID;
        }
    }
}
