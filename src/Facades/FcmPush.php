<?php

namespace Tocaan\FcmFirebase\Facades;

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
        return  \Tocaan\FcmFirebase\Contracts\FcmInterface::class;
    }
}
