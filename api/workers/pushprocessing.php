<?php
/**
 * Created by PhpStorm.
 * User: Домашний
 * Date: 13.11.2016
 * Time: 20:38
 */

require_once '../core/fb.php';


const FCM_API_KEY = 'AAAACiVgbxk:APA91bG7fGZ_TJBxQCRJcgYqDinHn1D-rGAuukbYs9VRuR2M0Gl05FiTI4xHV5dS7LnBbmOgLw5HaGS5IEDLtYOqhbpAKjXDWJSHGffgBTMKe-1cKymsnOE9UDbP1hNF_YvxHtlfdea96bRuOVV2JeqorTlTl8tglw';

const PUSH_PATH = "push-notifications/";
const USER_TOKENS = "user-tokens/";
const USER_DATA = "user-data/";

// platforms
const ANDROID = 1001;
const IOS = 1002;
const ANY = 1003;

// statuses
const PENDING = 0;
const PROCESSING = 1;
const FINISHED = 3;

sendPushApps();

function sendPushApps() {
    $fireBase = getFireBaseInstance();

    $pushes = json_decode($fireBase->get(PUSH_PATH, array('orderBy' => '"status"', 'equalTo' => PENDING)), true);
    foreach($pushes as $pushKey => $pushData) {
        $pushData['status'] = PROCESSING;
        $fireBase->set(PUSH_PATH . $pushKey, $pushData);
        $userId = $pushData['uid'];
        $userLocale = json_decode($fireBase->get(USER_DATA . $userId . "/locale"), true);
        if (is_null($userLocale)) {
            $userLocale = "en";
        }
        $userTokens = json_decode($fireBase->get(USER_TOKENS . $userId), true);

        if ($userTokens == null) {
            $pushData['status'] = FINISHED;
            $fireBase->set(PUSH_PATH . $pushKey, $pushData);
            continue;
        }

        $pushText = $pushData['text'][$userLocale];
        $pushTitle = $pushData['title'][$userLocale];
        $photoUrl = array_key_exists("photoUrl", $pushData) ? $pushData['photoUrl'] : "";
        $senderName = array_key_exists("senderName", $pushData) ? $pushData['senderName'] : "";
        $messageData = array(
            'title' => $pushTitle,
            'text' => $pushText,
            'photoUrl' => $photoUrl,
            'senderName' => $senderName
        );
        if ($pushText != null) {
            sendMessage($messageData, $userTokens, $userId);
        }
        $pushData['status'] = FINISHED;
        $fireBase->set(PUSH_PATH . $pushKey, $pushData);
    }
}

function sendMessage($messageData, $byIds, $userId) {
    $title = $messageData['title'];
    $text = $messageData['text'];
    $photoUrl = $messageData['photoUrl'];
    $senderName = $messageData['senderName'];

    $apiKey = FCM_API_KEY;
    $url = 'https://fcm.googleapis.com/fcm/send';
    $post = array(
        'registration_ids' => array_keys($byIds),
        'notification' => array('title' => $title, 'body' => $text, 'sound' => 'default'),
        'data' => array('photoUrl' => $photoUrl, 'senderName' => $senderName),
    );

    $headers = array(
        'Authorization: key=' . $apiKey,
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    $result = curl_exec($ch);
    if (!curl_errno($ch)) {
        handleFCMResponse($byIds, $result, $userId);
    }
    curl_close($ch);
}

function handleFCMResponse($ids, $response, $userId) {
    $simpleIds = array_keys($ids);
    $fireBase = getFireBaseInstance();

    $resp = json_decode($response);
    if ($resp == null) {
        return;
    }
    $i = 0;
    foreach ($resp->results as $r) {
        if (isset($r->error)) {
            $fireBase->delete(USER_TOKENS . $userId . "/" . $simpleIds[$i]);
        }
        $i++;
    }
}