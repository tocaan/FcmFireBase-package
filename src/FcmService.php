<?php

namespace Tocaan\FcmFirebase;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Tocaan\FcmFirebase\Contracts\FcmInterface;
use Tocaan\FcmFirebase\Events\InvalidTokensEvent;
use Tocaan\FcmFirebase\Exceptions\InvalidConfiguration;

class FcmService implements FcmInterface
{
    public Messaging $messaging;

    public function __construct()
    {
        if(config("fcm-firebase.parse_service_account_in_init")) {
            $factory = (new Factory())->withServiceAccount(config("fcm-firebase.firebase_credentials"));
            $this->messaging = $factory->create()->getMessaging();
        }
    }

    public function setServiceAccount($firebaseCredentialsPath)
    {
        $factory = (new Factory())->withServiceAccount($firebaseCredentialsPath);
        $this->messaging = $factory->create()->getMessaging();
        return $this;
    }

    /**
     * @param array $notificationData
     * @param array $data
     * @param bool  $platformSupportNotification
     * @return CloudMessage
     */
    public function buildFirebaseCloudMessage(array $notificationData, array $data = [], $platformSupportNotification = true): CloudMessage
    {
        $config = ApnsConfig::fromArray([
            'payload' => [
                'aps' => [
                    'badge' => (int)$notificationData["badge"] ?? 0,
                    'sound' => 'default',
                ],
            ],
        ]);
        $message =  CloudMessage::new()
            ->withNotification(Notification::fromArray($notificationData))
            ->withData($data)
            ->withApnsConfig($config)
        ;

//        if($platformSupportNotification) {
//            $message =  $message->withNotification(Notification::fromArray($notificationData))
//                      ->withDefaultSounds();
//
//        }

        return $message;
    }

    public function buildTranslationNotification($notificationData, $locale, $defaultLang = "ar")
    {
        return array_merge(
            $notificationData,
            [
                "title" => $notificationData["title"][$locale] ?? $notificationData["title"][$defaultLang] ?? "" ,
                "body"  => $notificationData["body"][$locale] ?? $notificationData["body"][$defaultLang] ?? ""
            ]
        );
    }

    /**
     * Send to tokens
     *
     * @param array $tokens
     * @param CloudMessage $message
     * @return void
     */
    public function sendToTokens(array $tokens, CloudMessage $message)
    {
        if(!$this->messaging) {
            InvalidConfiguration::serviceAccountNotConfigure();
        }

        if (count($tokens) == 0) {
            $this->logger("Firebase Admin SDK : 0 Devices found");
            return;
        }
        if(config("fcm-firebase.allow_fcm_token_log")) {
            $this->logger("Firebase Admin SDk : Tokens : " . json_encode($tokens));
        }
        $this->logger("Firebase Admin SDK : Sending " . count($tokens) . " firebase notifications.");
        $sendReport = $this->messaging->sendMulticast($message, $tokens);
        $this->logger("Firebase Admin SDK : {$sendReport->failures()->count()} notifications failed");
        $this->logger("Firebase Admin SDK : {$sendReport->successes()->count()} notifications were successful");
        $hasFailures = $sendReport->hasFailures();
        if($hasFailures) {
            $this->logger("Firebase Admin SDK : Fire event Invalid Tokens");
            $this->logger("Firebase Admin SDK : Fire event Invalid Tokens");
        }
    }

    /**
     * Push function
     *
     * @param array $field
     * @param string $platform
     * @return void
     */
    public function push(array $field, string $platform = "android", $lang = "ar")
    {
        $platformSupportNotification = $this->platformSupportNotificationKey($platform);
        $this->logger("*************** Pushing ******************");

        $this->logger("$platform Support Notification key  : " .  ($platformSupportNotification ? "yes" : "no"));
        $this->logger("Start Push Fcm for $platform and lang ($lang)");
        $fieldData = isset($field["data"]) ? $field["data"] : [] ;
        $fieldData = isset($field["notification"]) ? array_merge($fieldData, $field["notification"]) : $fieldData;

        $message = $this->buildFirebaseCloudMessage(
            [
            "title" => $fieldData["title"],
            "body" => $fieldData["body"],
            "badge" => $fieldData["badge"] ?? 0 ,
            "icon"  => $fieldData["icon"] ?? null,
            "domain" => $fieldData["domain"] ?? null
            ],
            array_merge(
                $fieldData,
                [
                    "id" => isset($fieldData["id"]) ? $fieldData["id"] : -1
                ]
            ),
            $platformSupportNotification
        );

        $this->sendToTokens($field["registration_ids"], $message);
        $this->logger("End Push Fcm for $platform and lang ($lang)");

        $this->logger("*****************  End  ****************");


    }

    /**
     * Logger
     *
     * @param string $message
     * @return void
     */
    public function logger(string $message): void
    {
        if(config("fcm-firebase.allow_fcm_log")) {
            logger($message);
        }
    }

    /**
     * Check if platform support notification key
     *
     * @param string $platform
     * @return bool
     */
    public function platformSupportNotificationKey($platform)
    {
        return !in_array(strtolower($platform), config("fcm-firebase.platform_not_need_notifications", ["android"]));
    }
}
