<?php

namespace Tocaan\FcmFirebase\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    // This is an example.
    // Adapt this to yur config validation checks!
    public static function versionNotSpecified()
    {
        return new static('EXAMPLE: You must provide a valid version.');
    }

    public static function userNotSupportFcm()
    {
        return new static('User Not support fcm');
    }

    public static function serviceAccountNotConfigure()
    {
        return new static('serviceAccount not configure');
    }

}
