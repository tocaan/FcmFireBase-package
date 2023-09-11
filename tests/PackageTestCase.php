<?php

namespace KrisOzolins\LaravelPackageExample\Tests;

use Orchestra\TestBench\TestCase as OrchestraTestCase;
use KrisOzolins\LaravelPackageExample\LaravelPackageExampleServiceProvider;

abstract class PackageTestCase extends OrchestraTestCase
{
    // Here you can add global testing functions

    protected function getPackageProviders($app)
    {
        return [LaravelPackageExampleServiceProvider::class];
    }
}
