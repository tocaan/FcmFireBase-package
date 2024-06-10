<?php

namespace Tocaan\FcmFirebase\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use \Tocaan\FcmFirebase\Traits\UsesUuid;
    protected $fillable = ['platform','user_id','device_token','lang', "model", "app_version", "os_version"];
}
