<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

use SilverStripePWA\Controllers\PushController;
 

class SendPushNotificationController extends Controller 
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

        // REST-service for sending pushnotification (not used in SilverStripe)
        $method = $request->httpMethod();

        switch ($method) {
            case 'POST':
                $data = $request->getBody();
                $pushController = new PushController();
                return $pushController->sendPush($data);

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