<?php

namespace Tocaanco\FcmFirebase\Traits;

trait FcmDeviceTrait
{
    public function deviceTokens()
    {
        if(config("fcm-firebase.allow_morph", false)) {
            return $this->morphMany(config("fcm-firebase.model"), config("fcm-firebase.morph"));
        } else {
            return $this->hasMany(config("fcm-firebase.model"), config("fcm-firebase.user_colum"));
        }
    }
}
