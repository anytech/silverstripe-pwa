<?php

namespace SilverStripePWA\Controllers;

use SilverStripePWA\Models\Subscriber;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Security;

class RegisterMobileSubscriptionController extends Controller
{
    private static $allowed_actions = ['index'];

    public function index(HTTPRequest $request) {
        $response = $this->getResponse();
        $response->addHeader('Content-Type', 'application/json');

        if ($request->httpMethod() !== 'POST') {
            $response->setStatusCode(405);
            $response->setBody(json_encode(['error' => 'POST required']));
            return $response;
        }

        $body = json_decode($request->getBody(), true) ?: [];
        $token = $body['token'] ?? null;
        $platform = $body['platform'] ?? null;

        if (!$token) {
            $response->setStatusCode(400);
            $response->setBody(json_encode(['error' => 'token required']));
            return $response;
        }

        if (!in_array($platform, ['ios', 'android'], true)) {
            $response->setStatusCode(400);
            $response->setBody(json_encode(['error' => 'platform must be ios or android']));
            return $response;
        }

        $member = Security::getCurrentUser();

        $existing = Subscriber::get()->filter([
            'Type' => 'expo',
            'endpoint' => $token,
        ])->first();

        if ($existing) {
            if ($member && $existing->MemberID !== $member->ID) {
                $existing->MemberID = $member->ID;
                $existing->Platform = $platform;
                $existing->write();
            }
            $response->setBody(json_encode(['status' => 'already-subscribed', 'id' => $existing->ID]));
            return $response;
        }

        $subscriber = Subscriber::create();
        $subscriber->Type = 'expo';
        $subscriber->Platform = $platform;
        $subscriber->endpoint = $token;
        if ($member) {
            $subscriber->MemberID = $member->ID;
        }
        $subscriber->write();

        $response->setBody(json_encode(['status' => 'subscribed', 'id' => $subscriber->ID]));
        return $response;
    }
}
