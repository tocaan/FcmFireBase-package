<?php

namespace Tocaan\FcmFirebase;

use Exception;
use Tocaan\FcmFirebase\Contracts\FcmInterface;
use Tocaan\FcmFirebase\Contracts\IFcmFirebaseDevice;
use Tocaan\FcmFirebase\Exceptions\InvalidConfiguration;

class FcmFirebaseService
{
    public $deviceModel ;
    public $fcmService ;
    public function __construct(FcmInterface $fcmService)
    {
        $this->deviceModel = new (config("fcm-firebase.device_model"));
        $this->fcmService = $fcmService;
    }
    public function registerToken($data)
    {
        $data = [
            'device_token' => $data['device_token'],
            'platform'     => $data['platform'],
            'lang'         => $data["lang"] ?? app()->getLocale(),
            'model'         => $data["model"] ?? null,
            'app_version'   => $data["app_version"] ?? null,
            'os_version'    => $data["os_version"] ?? null,
        ];

        if(config("fcm-firebase.allow_morph")) {
            $morph      = config("fcm-firebase.morph");
            $data[$morph."_id"] = $data["user_id"] ?? null;
            $data[$morph."_type"] = $data["user_type"] ?? null;
        } else {
            $data[config("fcm-firebase.user_colum")] = $data['user_id'];
        }

        $this->deviceModel->updateOrCreate(
            [
            'device_token' => $data['device_token'],
            ],
            $data
        );
    }

    public function logoutUser($user, $deviceId = null)
    {
        if($user instanceof IFcmFirebaseDevice) {
            $baseQuery = $user->devices();
            if($deviceId) {
                $baseQuery->where("id", $deviceId);
            }
            if(config("fcm-firebase.allow_morph")) {
                $morph = config("fcm-firebase.morph");
                $baseQuery->update([
                    $morph."_id" => null,$morph."_type" => null
                ]);
            } else {
                $baseQuery->update([
                    config("fcm-firebase.user_colum") => null
                ]);
            }
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

        } else {
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

        return $this->fcmService->push($fields_ios, "IOS", $lang);
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

        return $this->fcmService->push($fields_android, "Android", $lang);
    }

    public function setServiceAccount($firebaseCredentialsPath)
    {
        $this->fcmService->setServiceAccount($firebaseCredentialsPath);
        return $this;
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
