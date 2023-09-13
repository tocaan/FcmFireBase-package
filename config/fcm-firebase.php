<?php

return [

    "device_model" => \Tocaanco\FcmFirebase\Models\DeviceToken::class,
    "user_colum"   => "user_id",
    "allow_fcm_log"=> env("FCM_ALLOW_LOG", false),
    "server_key"   => env("FCM_SERVER_KEY"),

];
