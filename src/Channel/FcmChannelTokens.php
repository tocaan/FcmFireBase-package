<?php

namespace Tocaanco\FcmFirebase\Channel;

use Illuminate\Notifications\Notification;
use Modules\DeviceToken\Traits\FCMTraitUserTokens;

class FcmChannelTokens
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toFcm($notifiable);
        \Tocaanco\FcmFirebase\Facades\FcmFirebase::sendForUser($notifiable, $message);
    }
}
