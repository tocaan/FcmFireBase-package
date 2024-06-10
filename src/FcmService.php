<?php

namespace Tocaan\FcmFirebase;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Messaging;
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
            $this->messaging = $factory->createMessaging();
        }
    }

    public function setServiceAccount($firebaseCredentialsPath)
    {
        $factory = (new Factory())->withServiceAccount($firebaseCredentialsPath);
        $this->messaging = $factory->createMessaging();
        return $this;
    }

    /**
     * @param array $notificationData
     * @param array $data
     * @return CloudMessage
     */
    public function buildFirebaseCloudMessage(array $notificationData, array $data = []): CloudMessage
    {
        return CloudMessage::new()
            ->withNotification(Notification::fromArray($notificationData))
            ->withData($data)
            ->withHighestPossiblePriority()
            ->withDefaultSounds()
            ->withApnsConfig(
                ApnsConfig::new()
                ->withBadge($notificationData["badge"] ?? 0)
            )
        ;
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
        $invalidTokens = $sendReport->invalidTokens();
        if(count($invalidTokens)) {
            $this->logger("Firebase Admin SDK : Fire event Invalid Tokens");
            $this->logger("Firebase Admin SDK : Fire event Invalid Tokens");
            event(new InvalidTokensEvent($invalidTokens));
        }
    }

    /**
     * Push function
     *
     * @param array $field
     * @param string $platform
     * @return void
     */
    public function push(array $field, string $platform = "andorid", $lang = "ar")
    {
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
            [
                "type" => $fieldData["type"] ?? "general",
                "id"  => $fieldData["id"] ?? -1,
                "domain" => $fieldData["domain"] ?? null,
                "click_action" => $fieldData["click_action"] ?? null,
            ]
        );

        $this->sendToTokens($field["registration_ids"], $message);
        $this->logger("End Push Fcm for $platform and lang ($lang)");

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
}
