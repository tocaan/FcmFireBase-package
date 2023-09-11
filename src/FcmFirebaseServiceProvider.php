<?php

namespace Tocaanco\FcmFirebase;

use Illuminate\Support\ServiceProvider;
use Tocaanco\FcmFirebase\FcmFirebaseService;
use Tocaanco\FcmFirebase\Facades\FcmFirebase;
use Tocaanco\FcmFirebase\Exceptions\InvalidConfiguration;

class FcmFirebaseServiceProvider extends ServiceProvider
{
    /**
     * This will be used to register config & view in your package namespace.
     *
     * --> Replace with your package name <--
     *
     * @var  string
     */
    protected $vendorName = 'tocaanco';
    protected $packageName = 'fmcfirebase';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * A list of artisan commands for your package.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', $this->vendorName);
        // $this->loadViewsFrom(__DIR__.'/../resources/views', $this->vendorName);
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services and bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/'.$this->packageName.'.php', $this->packageName);

        $config = config($this->packageName);

        // Register the service the package provides.
        $this->app->singleton(FcmFirebaseService::class, function ($app) use ($config) {
            // Checks if configuration is valid
            $this->guardAgainstInvalidConfiguration($config);

            return new FcmFirebaseService;
        });

        // Make alias for use with package name
        $this->app->alias(FcmFirebase::class, "FcmFirebase");
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [$this->packageName];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file
        $this->publishes([
            __DIR__.'/../config/'.$this->packageName.'.php' => config_path($this->packageName.'.php'),
        ], 'config');

        // // Publishing the views
        // $this->publishes([
        //     __DIR__.'/../resources/views' => resource_path('views/vendor/'.$this->vendorName.'/'.$this->packageName),
        // ], 'views');

        // // Publishing assets
        // $this->publishes([
        //     __DIR__.'/../resources/css' => public_path('vendor/'.$this->vendorName.'/'.$this->packageName.'/css'),
        // ], 'public');

        // $this->publishes([
        //     __DIR__.'/../resources/js' => public_path('vendor/'.$this->vendorName.'/'.$this->packageName.'/js'),
        // ], 'public');

        // $this->publishes([
        //     __DIR__.'/../resources/img' => public_path('vendor/'.$this->vendorName.'/'.$this->packageName.'/img'),
        // ], 'public');

        // // Publishing the translation files
        // $this->publishes([
        //     __DIR__.'/../resources/lang' => resource_path('lang/vendor/'.$this->vendorName.'/'.$this->packageName),
        // ], 'translations');

        // Publishing seed's
        $this->publishes([
            __DIR__.'/../database' => base_path('/database'),
        ], 'seeds');

        // Registering package commands
        $this->commands($this->commands);
    }

    /**
     * Checks if the config is valid.
     *
     * @param  array|null $config the package configuration
     * @throws InvalidConfiguration exception or null
     * @see  \Tocaanco\FcmFirebase\Exceptions\InvalidConfiguration
     */
    protected function guardAgainstInvalidConfiguration(array $config = null)
    {
        // Here you can add as many checks as your package config needed to
        // consider it valid.
        // @see \Tocaanco\FcmFirebase\Exceptions\InvalidConfiguration
        if (empty($config['version'])) {
            throw InvalidConfiguration::versionNotSpecified();
        }
    }

    /**
     * Check if package is running under Lumen app.
     *
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen') === true;
    }
}
