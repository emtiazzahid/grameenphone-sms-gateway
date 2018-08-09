<?php

namespace Emtiaz\GrameenphoneSmsGateway\Providers;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use Emtiaz\GrameenphoneSmsGateway\Services\Grameenphone;
use Laravel\Lumen\Application as LumenApplication;
use Emtiaz\GrameenphoneSmsGateway\Facades\Grameenphone as GrameenphoneFacade;
use Illuminate\Foundation\Application as LaravelApplication;

class GrameenphoneSmsGatewayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerGrameenphone();
    }

    /**
     * Setup the config.
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__.'/../../config/grameenphone-sms-gateway.php');
        // Check if the application is a Laravel OR Lumen instance to properly merge the configuration file.
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('grameenphone-sms-gateway.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('grameenphone-sms-gateway');
        }
        $this->mergeConfigFrom($source, 'grameenphone-sms-gateway');
    }

    /**
     * Register Banglalink class.
     */
    protected function registerGrameenphone()
    {
        $this->app->bind('grameenphone', function (Container $app) {
            return new Grameenphone($app['config']->get('grameenphone-sms-gateway'));
        });
        $this->app->alias('grameenphone', GrameenphoneFacade::class);
    }
}
