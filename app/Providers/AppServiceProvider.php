<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS on Heroku/production so generated asset & route URLs use https
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
    }
}
