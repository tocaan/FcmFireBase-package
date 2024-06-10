# FcmFirebase 

![Tests](https://github.com/spatie/laravel-package-tools/workflows/Tests/badge.svg)

The package allow to send fcm firebase message

### Installation

Install via composer:

```
composer require tocaan/fcmfirebase dev-master
```

And add the service provider in config/app.php:

```php
Tocaan\\FcmFirebase\\FcmFirebaseServiceProvider,
```

Then register Facade class aliase:

```php
'FcmFirebase' => \Tocaan\FcmFirebase\Facades\FcmFirebase::class,
```

### Publish assets:

```
php artisan vendor:publish
```
### Getting Started

To start use in User Model to enable use FcmChannelTokens and sendForUser in FcmFirebase Facade 
-  must implements `\Tocaan\FcmFirebase\Contracts\IFcmFirebaseDevice`and use trait `  \Tocaan\FcmFirebase\Traits\FcmDeviceTrait`
```
class User extends Authenticatable implements  \Tocaan\FcmFirebase\Contracts\IFcmFirebaseDevice
{
   use \Tocaan\FcmFirebase\Traits\FcmDeviceTrait
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

To Disabled init parse SERVICE_ACCOUNT when init but must call `setServiceAccount` and set path to avoid get exception `serviceAccountNotConfigure`

```
FCM_PARSE_SERVICE_ACCOUNT_IN_INIT= false
```

`setServiceAccount` method allow user to override SERVICE_ACCOUNT file 

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