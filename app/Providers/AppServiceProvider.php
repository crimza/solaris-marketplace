<?php

namespace App\Providers;

use App\Providers\Extensions\ValidationExtender;
use DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $extenders = [
        ValidationExtender::class,
    ];
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->extenders as $extender) {
            /** @var  \App\Providers\Extensions\Extender  $extender */

            $extender = $this->app->make($extender);
            $extender->extend();
        }

        if (config('app.debug')) {
            $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');
            $kernel->pushMiddleware('\Clockwork\Support\Laravel\ClockworkMiddleware');
            DB::enableQueryLog();
        }

        \Validator::extend('pgp_public_key', function($attribute, $value, $parameters, $validator) {
            return (bool) preg_match('/^-----BEGIN PGP PUBLIC KEY BLOCK-----(.*?)-----END PGP PUBLIC KEY BLOCK-----$/s', $value);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require __DIR__ . '/../Packages/helpers.php';
    }
}
