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
        'endpoint' => 'Text',
        'publicKey' => 'Text',
        'authToken' => 'Text',
        'contentEncoding' => 'Text'
    ];

    private static $has_one = [
        'Member' => Member::class
    ];

    private static $summary_fields = [
        'ID' => 'ID',
        'Member.Email' => 'Member',
        'EndpointSummary' => 'Endpoint',
        'Created' => 'Subscribed'
    ];

    public function getCMSFields()
    {
        $fields = FieldList::create(
            ReadonlyField::create('endpoint', 'Endpoint'),
            ReadonlyField::create('publicKey', 'Public Key'),
            ReadonlyField::create('authToken', 'Auth Token'),
            ReadonlyField::create('contentEncoding', 'Content Encoding')
        );

        if ($this->Member()->exists()) {
            $fields->push(ReadonlyField::create('MemberEmail', 'Member', $this->Member()->Email));
        }

        return $fields;
    }

    /**
     * Get truncated endpoint for display
     */
    public function getEndpointSummary(): string
    {
        if (strlen($this->endpoint) > 50) {
            return substr($this->endpoint, 0, 50) . '...';
        }
        return $this->endpoint;
    }

    /**
     * Associate current logged-in member when creating subscription
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Auto-assign current member if not set
        if (!$this->MemberID && $member = Security::getCurrentUser()) {
            $this->MemberID = $member->ID;
        }
    }
}
