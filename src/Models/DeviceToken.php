<?php

namespace Tocaanco\FcmFirebase\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use \Tocaanco\FcmFirebase\Traits\UsesUuid;
    protected $fillable = ['platform','user_id','device_token','lang'];
}
