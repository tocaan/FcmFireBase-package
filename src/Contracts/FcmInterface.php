<?php

namespace Tocaan\FcmFirebase\Contracts;

use Kreait\Firebase\Messaging\CloudMessage;

interface FcmInterface
{
    public function buildFirebaseCloudMessage(array $notificationData, array $data = []): CloudMessage;

    public function sendToTokens(array $tokens, CloudMessage $message);

    public function buildTranslationNotification($notificationData, $locale, $defaultLang = "ar");

    public function push(array $field, string $platform="andorid");

    public function setServiceAccount($firebaseCredentialsPath);


}
