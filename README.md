# FcmFirebase 

![Tests](https://github.com/spatie/laravel-package-tools/workflows/Tests/badge.svg)

The package allow to send fcm firebase message

### Installation

Install via composer:

must add this in composer.json  before require (must sure you have permission in github repo)
```
"repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:Tocaanco/FcmFireBase.git"
        }
    ],
```

if package still private will need to add `github-oauth` in config attribute in composer.json
`github.com` this token generate from setting can create or get from admin of repo 

```
 "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "github-oauth": {
            "github.com": "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"
        }
    },
```

```
composer require tocaanco/fcmfirebase dev-master
```

And add the service provider in config/app.php:

```php
Tocaanco\\FcmFirebase\\FcmFirebaseServiceProvider,
```

Then register Facade class aliase:

```php
'FcmFirebase' => \Tocaanco\FcmFirebase\Facades\FcmFirebase::class,
```

### Publish assets:

```
php artisan vendor:publish
```
### Getting Started

To start use in User Model to enable use FcmChannelTokens and sendForUser in FcmFirebase Facade 
-  must implements `\Tocaanco\FcmFirebase\Contracts\IFcmFirebaseDevice`and use trait `  \Tocaanco\FcmFirebase\Traits\FcmDeviceTrait`
```
class User extends Authenticatable implements  \Tocaanco\FcmFirebase\Contracts\IFcmFirebaseDevice
{
   use \Tocaanco\FcmFirebase\Traits\FcmDeviceTrait
}
```

to send all device token in database can use this
```
 $data = [
        "title" => [
            "ar" => "test",
            "en" => "test"
        ],
        "description" => [
            "ar" => "test",
            "en" => "test"
        ],
        "type"=>"general",
        "id"  => -1
 ];
FcmFirebase::sendToAllDevices($data)`;
```
to send  for user can use this (but must handle model User first)

```
 $data = [
        "title" => [
            "ar" => "test",
            "en" => "test"
        ],
        "description" => [
            "ar" => "test",
            "en" => "test"
        ],
        "type"=>"general",
        "id"  => -1
 ];
FcmFirebase::sendForUser($user,$data)`;
```

to register new device tokens can use this method

```
$data = [
'device_token' => "dd"
'user_id'      => "1",
'platform'     => "ANDRIOD",
'lang'         => $data["lang"] // OPTIONAL,
];`
FcmFirebase::registerToken($data)
```

to logout user

```
FcmFirebase::logoutUser($user)
```        


to use it in the Notification class  can use this channel `FcmChannelTokens` and implement this method `toFcm`

```
     /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [FcmChannelTokens::class];
    }


     /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toFcm($notifiable)
    {
        return [
        "title" => [
            "ar" => "test",
            "en" => "test"
        ],
        "description" => [
            "ar" => "test",
            "en" => "test"
        ],
        "type"=>"general",
        "id"  => -1
        ];
    }

   
        
```

To allow debug the response from firebase need to allow this in .env

```
FCM_ALLOW_LOG= true 
```



To allow morph in device model go to config and change the following config

```
"allow_morph"=> true,
"morph" =>"owner", the morph relation name
"morph_index"=> false, // if need to make morph index

```


To User package in old package will need to follow this step

```
-  instal package 
-  remove call this `$this->Push` and use this FcmPush::push($fields, $platform, $lang)

```