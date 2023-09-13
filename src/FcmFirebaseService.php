<?php

namespace Tocaanco\FcmFirebase;

use Exception;
use Tocaanco\FcmFirebase\Exceptions\InvalidConfiguration;

class FcmFirebaseService
{
    public $deviceModel ;
    public function __construct()
    {
        $this->deviceModel = new (config("fcm-firebase.device_model"));
    }

    public function x()
    {

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

    public function sendToAllDevices($data)
    {
        // langue
        foreach (config("fcm-firebase.langues") as $lang) {

            // platform
            foreach (["IOS", "ANDROID"] as  $platform) {

                $skip = 0;
                $limit = 999;
                // devices
                while (true) {
                    $devices = $this->deviceModel->select("device_token")->distinct()
                                ->where("platform", $platform)
                                ->where("lang", $lang)
                                ->skip($skip)->take($limit)->pluck("device_token")->toArray();
                    $countDevices = count($devices);
                    if($countDevices > 0) {
                        $this->{"push".$platform}($data, $devices, $lang);

                    }
                    if($countDevices < $limit) {
                        break;
                    }
                    $skip += 999;
                }
            }
        }
    }

    public function sendToToken($token, $platform, $data, $lang)
    {
        $method = "push".$platform;
        if(method_exists($this, $method)) {
            $this->{"push".$platform}($data, [$token], $lang);
        }
        throw new Exception("$platform not support");

    }

    public function sendToDevice($deviceId, $data)
    {
        $device = $this->deviceModel->findOrFail($deviceId);
        $this->sendToToken($device->device_token, $device->platform, $data, $device->lang);

    }


    public function sendForUser($user, $data)
    {
        if($user instanceof IFcmFirebaseDevice) {


            // langue
            foreach (config("fcm-firebase.langues") as $lang) {

                // platform
                foreach (["IOS", "ANDROID"] as  $platform) {

                    $skip = 0;
                    $limit = 999;
                    // devices
                    while (true) {
                        $devices = $user->deviceTokens()->select("device_token")->distinct()
                                    ->where("platform", $platform)
                                    ->where("lang", $lang)
                                    ->skip($skip)->take($limit)->pluck("device_token")->toArray();
                        $countDevices = count($devices);
                        if($countDevices > 0) {
                            $this->{"push".$platform}($data, $devices, $lang);

                        }
                        if($countDevices < $limit) {
                            break;
                        }
                        $skip += 999;
                    }
                }
            }

        }else{
            throw InvalidConfiguration::userNotSupportFcm();
        }

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



        return $this->push($fields_ios, "IOS - $lang");
    }

    public function pushANDROID($data, $tokens, $lang)
    {
        $notification = [
          'title'    => $data['title'][$lang],
          'body'     => $data['description'][$lang],
          'sound'    => 'default',
          'priority' => 'high',
          "type"     => $data['type'] ??  'general',
          "id"       => $data['id'] ?? -1,
        ];

        $fields_android = [
            'registration_ids' => $tokens,
            'data'             => $notification
        ];

        return $this->push($fields_android, "Andriod - $lang");
    }

    public function push($fields, $platform = "")
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
            \Log::channel('single')->debug("FCM Send Error: ". curl_error($ch));
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
        if(config("fcm-firebase.allow_fcm_log")) {
            \Log::channel('single')->debug("================================== FCM $platform ==============");
            \Log::channel('single')->debug($result);
            \Log::channel('single')->debug('Sent: ' . count($fields['registration_ids']));
            \Log::channel('single')->debug("================================== End FCM ==============");
        }
        return $result;
    }
}