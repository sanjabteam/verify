<?php

namespace SanjabVerify;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class VerifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'verify');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('verify.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/verify'),
        ], 'lang');

        Validator::extend('sanjab_verify', function ($attribute, $value, $parameters, $validator) {
            if (isset($validator->getData()[$parameters[0] ?? 'reciver']) && !empty($validator->getData()[$parameters[0]])) {
                $result = app(Verify::class)->verify($validator->getData()[$parameters[0] ?? 'reciver'], $value);
                App::singleton('sanjab_verify_validation_message', function () use ($result) {
                    return $result['message'];
                });
                return $result['success'];
            }
            return false;
        });
        Validator::replacer('sanjab_verify', function ($message) {
            return app('sanjab_verify_validation_message');
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'verify');

        $this->app->singleton('verify', function () {
            return new Verify;
        });
    }
}
