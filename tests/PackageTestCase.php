<?php

namespace Tocaanco\FcmFirebase\Tests;

use Tocaanco\FcmFirebase\FcmFirebaseServiceProvider;
use Orchestra\TestBench\TestCase as OrchestraTestCase;

abstract class PackageTestCase extends OrchestraTestCase
{
    // Here you can add global testing functions

    protected function getPackageProviders($app)
    {
        return [FcmFirebaseServiceProvider::class];
    }
}
