<?php

namespace Tocaanco\FcmFirebase\Traits;

trait FcmDeviceTrait
{
    public function devices()
    {
        return $this->hasMany(config("fcm-firebase.model"), config("fcm-firebase.user_colum"));
    }
}
