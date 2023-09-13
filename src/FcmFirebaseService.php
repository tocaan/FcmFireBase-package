<?php

namespace Tocaanco\FcmFirebase;

use Tocaanco\FcmFirebase\Exceptions\InvalidConfiguration;

class FcmFirebaseService
{
    public $deviceModel ;
    public function __construct()
    {
        $this->deviceModel = app()->make(config(config("fcm-firebase.model")));
    }


    public function registerToken($data)
    {
        $this->deviceModel->updateOrCreate(
            [
            'device_token' => $data['device_token'],
            ],
            [
            'device_token' => $data['device_token'],
            'user_id'      => $data['user_id'],
            'platform'     => $data['platform'],
            'lang'         => $data["lang"] ?? app()->getLocale(),
            ]
        );
    }

    public function logoutUser($user)
    {
        if($user instanceof IFcmFirebaseDevice) {
            $user->devices()->update([
                config("fcm-firebase.model") => null
            ]);
        } else {
            throw InvalidConfiguration::userNotSupportFcm();
        }
    }

    public function sendForUser($user, $data)
    {


        // langue
        foreach (config('translatable.locales') as $lang) {

            // platform
            foreach (["IOS", "ANDROID"] as  $platform) {

                $skip = 0;
                $limit = 999;
                // devices
                while (true) {
                    $devices = $user->devices()->distinct("token")
                                ->where("platform", $platform)
                                ->where("lang", $lang)
                                ->skip($skip)->take($limit)->pluck("token")->toArray();
                    $this->{"push".$platform}($data, $devices, $lang);

                    if(count($devices) > $limit) {
                        break;
                    }
                }
            }
        }

    }

    public function getTokenFromDeviceTokens($deviceTokens)
    {
        $tokens = ["ios" => [], "android" => []];
        if ($deviceTokens->count() > 0) {
            $tokens['ios'] 		= $deviceTokens->where('platform', 'IOS');

            $tokens['android']  = $deviceTokens->where('platform', 'ANDROID');
        }

        return $tokens;
    }

    public function sendNotification($devices, $request)
    {
        foreach (config('translatable.locales') as $lang) {

            // FILTER IOS DEVICES
            if ($devices['ios']) {
                $iosTokens = $devices['ios']->where('lang', $lang)->pluck('device_token')->toArray();


                $regIdIOS = array_chunk($chunkIOS, 999);

                foreach ($regIdIOS as $iTokens) {
                    $this->PushIOS($request, $iTokens, $lang);
                }
            }

            // FILTER ANDROID DEVICES
            if ($devices['android']) {
                $androidTokens = $devices['android']->where('lang', $lang)->pluck('device_token')->toArray();

                $tokensAndroid = $this->uniqueTokens($androidTokens);

                $regIdAndroid = array_chunk($tokensAndroid, 999);

                foreach ($regIdAndroid as $aTokens) {
                    $this->PushANDROID($request, $aTokens, $lang);
                }
            }
        }

        return true;
    }



    public function pushIOS($data, $tokens, $lang)
    {
        $notification = [
          'title'    => $data['title'][$lang],
          'body'     => $data["description"][$lang],
          'sound'    => 'default',
          'priority' => 'high',
          'badge' 	 => '0',
        ];

        $data = [
         "type"     =>  $data['type'] ??  'general',
          "id"       => $data['id'] ?? -1,
        ];

        $fields_ios = [
            'registration_ids' => $tokens,
            'notification'     => $notification,
            'data'             => $data,
        ];



        return $this->push($fields_ios);
    }

    public function pushANDROID($data, $tokens, $lang)
    {
        $notification = [
          'title'    => $data['title'][$lang],
                    'body'     => $data['description'][$lang],
          'sound'    => 'default',
          'priority' => 'high',
          "type"     => $data['type'] ? $data['type'] : 'general',
          "id"       => $data['id'],
        ];

        $fields_android = [
            'registration_ids' => $tokens,
            'data'             => $notification
        ];

        return $this->push($fields_android);
    }

    public function push($fields)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $server_key = config("fcm-firebase.server_key");

        $headers = array(
                'Content-Type:application/json',
                'Authorization:key='.$server_key
            );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === false) {
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
        if(config("fcm-firebase.allow_fcm_log")) {
            Log::channel('single')->debug("================================== FCM ==============");
            Log::channel('single')->debug($result);
            Log::channel('single')->debug('Sent: ' . count($fields['registration_ids']));
            Log::channel('single')->debug("================================== End FCM ==============");
        }
        return $result;
    }
}
