<?php

namespace Tocaanco\FcmFirebase\Traits;

trait FcmDeviceTrait
{
    public function deviceTokens()
    {
        return $this->hasMany(config("fcm-firebase.model"), config("fcm-firebase.user_colum"));
    }
}
