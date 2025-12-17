<?php

namespace SilverStripePWA\Controllers;

use SilverStripePWA\Models\Subscriber;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Security;

class RegisterSubscriptionController extends Controller
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'index'
    ];

    /**
     * Default controller action
     *
     * @return mixed
     */
    public function index(HTTPRequest $request) {

        // REST-service for Subscriber model
        $method = $request->httpMethod();

        switch ($method) {
            case 'POST':
                $subscription = json_decode($request->getBody(), true);

                // Check if subscription already exists
                $existing = Subscriber::get()->filter('endpoint', $subscription['endpoint'])->first();
                if ($existing) {
                    // Update member link if user is now logged in
                    $member = Security::getCurrentUser();
                    if ($member && !$existing->MemberID) {
                        $existing->MemberID = $member->ID;
                        $existing->write();
                    }
                    echo "Already subscribed";
                    break;
                }

                $subscriber = new Subscriber();
                $subscriber->endpoint = $subscription['endpoint'];
                $subscriber->publicKey = $subscription['publicKey'];
                $subscriber->authToken = $subscription['authToken'];
                $subscriber->contentEncoding = $subscription['contentEncoding'];

                // Link to current member if logged in
                if ($member = Security::getCurrentUser()) {
                    $subscriber->MemberID = $member->ID;
                }

                $subscriber->write();

                echo "Subscription added!";

                break;
            case 'PUT':
                echo "Error: PUT-method not handled";
                break;
            case 'DELETE':
                echo "Error: DELETE-method not handled";
                break;
            case 'GET':
                echo "Error: GET-method not handled";
                break;
            default:
                echo "Error: method not handled";
                return;
        }
    }
}