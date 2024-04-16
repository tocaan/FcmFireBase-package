<?php

namespace Tocaanco\FcmFirebase\Facades;

use Illuminate\Support\Facades\Facade;

class FcmPush extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return  \Tocaanco\FcmFirebase\Contracts\FcmInterface::class;
    }
}
