<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'fb_core/firebaseInterface.php';
require_once 'fb_core/firebaseStub.php';
require_once 'fb_core/firebaseLib.php';

require_once 'fb_core/token/BeforeValidException.php';
require_once 'fb_core/token/ExpiredException.php';
require_once 'fb_core/token/JWT.php';
require_once 'fb_core/token/SignatureInvalidException.php';

const DEFAULT_URL = 'https://swipr-dev.firebaseio.com/';
const COUNTER = "counter/";
const USER_PRODUCT = "user-product/";

use \Firebase\JWT\JWT;

function getToken() {
    $date = new DateTime();
    $key = "c8u2CBUlz7P3KtbYEg2TAgUT1tbuBpCyGpNzM1Kc";
    $token = array(
        'iat' => $date->getTimestamp(),
        'v' => 0,
        'd' => array(
            'username' => 'rest',
            'type' => 'admin',
            'fullname' => 'Vitaliy Mitin'
        )
     );
    return JWT::encode($token, $key);
}

function fbDeleteProduct($userId, $productId) {
    $fireBase = getFireBaseInstance();
    $product = json_decode($fireBase->get(USER_PRODUCT . $userId . "/$productId"), true);
    if (!is_null($product) && is_array($product)) {
        $product['status'] = 1;
        $fireBase->set(USER_PRODUCT . $userId . "/$productId", $product);
    }

    $userCounters = json_decode($fireBase->get(COUNTER . $userId), true);

    if (!is_null($userCounters)
        && is_array($userCounters)
        && array_key_exists("active-posts", $userCounters)) {
        
        $newValue = $userCounters['active-posts'] - 1;
        $userCounters['active-posts'] = $newValue;
        $fireBase->set(COUNTER . $userId . "/active-posts", $newValue);
    }
}

function getFireBaseInstance() {
    return new \Firebase\FirebaseLib(DEFAULT_URL, getToken());
}